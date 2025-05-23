<?php

namespace alo\Commands\Campaign;

use League\CLImate\CLImate;
use alo\Config\Config;
use alo\Database\Database;
use alo\Analytics\AnalyticsQueueService;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;
use Exception;
use Throwable;

class SendCommand
{
    private $config;
    private $db;
    private $climate;
    private $analyticsQueueService;
    private $sleepInterval = 5;
    private $maxRetries = 3;
    private $errorSleepInterval = 30;

    public function __construct(Config $config, CLImate $climate)
    {
        $this->config = $config;
        $this->db = \alo\Database\Database::getInstance();
        $this->climate = $climate;
        $this->analyticsQueueService = new AnalyticsQueueService($this->config, $this->climate);
    }

    public function execute(): int
    {
        set_time_limit(0);
        $errorCount = 0;
        
        while (true) {
            try {
                $this->climate->bold()->blue()->out('Campaign Sending Process (Background Mode)');

                $amqpConfig = $this->config->get('amqp');

                $connection = new AMQPStreamConnection(
                    $amqpConfig['host'],
                    $amqpConfig['port'],
                    $amqpConfig['user'],
                    $amqpConfig['password'],
                    $amqpConfig['vhost']
                );

                $channel = $connection->channel();

                $campaigns = $this->db->query("
                    SELECT * FROM campaigns 
                    WHERE status = 'sending'
                ");

                $totalCampaigns = count($campaigns);
                $this->climate->out("Found {$totalCampaigns} campaigns to send");

                $processedCampaigns = 0;
                $failedCampaigns = 0;
                $totalNotificationsSent = 0;
                $totalNotificationsFailed = 0;

                foreach ($campaigns as $campaign) {
                    $this->climate->out("Processing campaign: {$campaign['name']} (ID: {$campaign['id']}) (UUID: {$campaign['uuid']})");

                    try {
                        $vapidConfig = [
                            'VAPID' => [
                                'subject' => $this->config->get('client.url'),
                                'publicKey' => $this->config->get('firebase.vapid.public'),
                                'privateKey' => $this->config->get('firebase.vapid.private')
                            ]
                        ];

                        $timeout = 300;
                        $clientOptions = [
                            \GuzzleHttp\RequestOptions::ALLOW_REDIRECTS => false,
                            \GuzzleHttp\RequestOptions::VERIFY => false,
                            \GuzzleHttp\RequestOptions::CONNECT_TIMEOUT => 300,
                            \GuzzleHttp\RequestOptions::TIMEOUT => 300,
                        ];
                        $webPush = new WebPush($vapidConfig, [], $timeout, $clientOptions);
                        $webPush->setAutomaticPadding(true);

                        $campaignQueueName = 'campaigns';
                        $messagesProcessed = 0;
                        $messagesFailed = 0;
                        $batchSize = 500;

                        while (true) {
                            $messages = [];
                            $notifications = [];
                            $subscriberData = [];

                            for ($i = 0; $i < $batchSize; $i++) {
                                $message = $channel->basic_get($campaignQueueName);
                                if ($message === null) {
                                    break;
                                }
                                
                                try {
                                    $payload = json_decode($message->getBody(), true);
                                    
                                    $subscription = new Subscription(
                                        $payload['subscriber']['endpoint'],
                                        $payload['subscriber']['keys']['p256dh'],
                                        $payload['subscriber']['keys']['auth']
                                    );
                                    
                                    $messages[] = $message;
                                    $notifications[] = [
                                        'subscription' => $subscription,
                                        'payload' => json_encode($payload)
                                    ];
                                    $subscriberData[] = $payload['subscriber'];
                                } catch (Exception $e) {
                                    $this->climate->error("Error processing message: " . $e->getMessage());
                                    $channel->basic_reject($message->getDeliveryTag(), false);
                                    $messagesFailed++;
                                    $totalNotificationsFailed++;
                                }
                            }
                            
                            if (empty($messages)) {
                                $this->climate->info("No more messages in queue 'campaigns' for campaign {$campaign['name']}");
                                break;
                            }
                            
                            $this->climate->info(sprintf("Processing batch of %d notifications for campaign %s", count($messages), $campaign['name']));
                            
                            foreach ($notifications as $index => $notification) {
                                $webPush->queueNotification(
                                    $notification['subscription'],
                                    $notification['payload']
                                );
                            }
                            
                            $results = $webPush->flush();
                            
                            foreach ($results as $index => $result) {
                                $message = $messages[$index];
                                $subscriber = $subscriberData[$index];
                                $endpoint = $result->getEndpoint();
                                
                                if ($result->isSuccess()) {
                                    $this->climate->success("Message sent successfully for subscription {$endpoint}");
                                    
                                    $messagesProcessed++;
                                    $totalNotificationsSent++;
                                    
                                    $this->analyticsQueueService->sendToAnalyticsQueue(
                                        $campaign['id'],
                                        $subscriber['id'],
                                        'sent'
                                    );
                                    
                                    $channel->basic_ack($message->getDeliveryTag());
                                } else {
                                    $this->climate->error("Message failed to send for subscription {$endpoint}: {$result->getReason()}");
                                    
                                    $messagesFailed++;
                                    $totalNotificationsFailed++;
                                    
                                    $this->analyticsQueueService->sendToAnalyticsQueue(
                                        $campaign['id'],
                                        $subscriber['id'],
                                        'failed'
                                    );
                                    
                                    if ($result->isSubscriptionExpired()) {
                                        $this->db->query("
                                            UPDATE subscribers
                                            SET status = 'inactive'
                                            WHERE endpoint = %s
                                        ", $subscriber['endpoint']);
                                        
                                        $this->db->query("
                                            INSERT INTO analytics_subscribers
                                            (subscriber_id, status)
                                            VALUES (%s, 'inactive')
                                        ", $subscriber['id']);
                                        
                                        $this->climate->error("Subscription expired for {$endpoint}. Subscriber marked as unsubscribed.");
                                        
                                        $channel->basic_ack($message->getDeliveryTag());
                                    } else {
                                        $messageBody = json_decode($message->getBody(), true);
                                        $isRetry = isset($messageBody['_retry']) && $messageBody['_retry'] === true;
                                        
                                        if ($isRetry) {
                                            $this->climate->error("Message for {$endpoint} failed after retry. Removing from queue.");
                                            $channel->basic_ack($message->getDeliveryTag());
                                        } else {
                                            $this->climate->warning("Message for {$endpoint} failed. Scheduling one retry.");
                                            
                                            $messageBody['_retry'] = true;
                                            
                                            $retryMessage = new AMQPMessage(
                                                json_encode($messageBody),
                                                ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
                                            );
                                            
                                            $channel->basic_publish(
                                                $retryMessage,
                                                '',
                                                'campaigns'
                                            );
                                            
                                            $channel->basic_ack($message->getDeliveryTag());
                                        }
                                    }
                                }
                            }
                            
                            $timeout = 300;
                            $clientOptions = [
                                \GuzzleHttp\RequestOptions::ALLOW_REDIRECTS => false,
                                \GuzzleHttp\RequestOptions::VERIFY => false,
                                \GuzzleHttp\RequestOptions::CONNECT_TIMEOUT => 300,
                                \GuzzleHttp\RequestOptions::TIMEOUT => 300,
                            ];
                            $webPush = new WebPush($vapidConfig, [], $timeout, $clientOptions);
                            $webPush->setAutomaticPadding(true);
                        }

                        $this->db->query("
                            UPDATE campaigns
                            SET status = 'sent',
                                ended_at = NOW()
                            WHERE id = %s
                        ", $campaign['id']);

                        $this->db->query("
                            INSERT INTO analytics_campaigns
                            (campaign_id, error_count, successfully_count)
                            VALUES (%s, %d, %d)
                        ", $campaign['id'], $messagesFailed, $messagesProcessed);

                        $processedCampaigns++;
                        $this->climate->info(sprintf(
                            "Campaign %s completed. Sent: %d, Failed: %d",
                            $campaign['name'],
                            $messagesProcessed,
                            $messagesFailed
                        ));
                    } catch (Exception $campaignError) {
                        $failedCampaigns++;
                        $this->climate->error(sprintf(
                            'Error processing campaign %s: %s',
                            $campaign['name'],
                            $campaignError->getMessage()
                        ));
                    }
                }

                $channel->close();
                $connection->close();

                $this->climate->bold()->blue()->out('Campaign Sending Process Summary');
                $summaryTable = [
                    ['Total Campaigns', 'Processed', 'Failed', 'Total Notifications', 'Sent', 'Failed'],
                    [
                        $totalCampaigns,
                        $processedCampaigns,
                        $failedCampaigns,
                        $totalNotificationsSent + $totalNotificationsFailed,
                        $totalNotificationsSent,
                        $totalNotificationsFailed
                    ]
                ];
                $this->climate->table($summaryTable);

                $errorCount = 0;

                $this->climate->out("Waiting {$this->sleepInterval} seconds before next check...");
                sleep($this->sleepInterval);

            } catch (Exception $e) {
                $errorCount++;
                $this->climate->error("Critical Error: " . $e->getMessage());
                
                if ($errorCount >= $this->maxRetries) {
                    $this->climate->error("Maximum error retries reached. Exiting.");
                    return 1;
                }

                $sleepTime = min($this->errorSleepInterval * $errorCount, 300);
                $this->climate->out("Waiting {$sleepTime} seconds before retry...");
                sleep($sleepTime);
            }
        }

        return 0;
    }

}
