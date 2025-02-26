<?php

namespace Pushbase\Commands\Campaign;

use League\CLImate\CLImate;
use Pushbase\Config\Config;
use Pushbase\Database\Database;
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
    private $sleepInterval = 5;
    private $maxRetries = 3;
    private $errorSleepInterval = 30;

    public function __construct(Config $config, CLImate $climate)
    {
        $this->config = $config;
        $this->db = \Pushbase\Database\Database::getInstance();
        $this->climate = $climate;
    }

    public function execute(): int
    {
        $errorCount = 0;
        
        while (true) {
            try {
                $this->climate->bold()->blue()->out('Campaign Sending Process (Background Mode)');

                $rabbitmqConfig = $this->config->get('rabbitmq');

                $connection = new AMQPStreamConnection(
                    $rabbitmqConfig['host'],
                    $rabbitmqConfig['port'],
                    $rabbitmqConfig['user'],
                    $rabbitmqConfig['password'],
                    $rabbitmqConfig['vhost']
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

                        $webPush = new WebPush($vapidConfig);

                        $campaignQueueName = $campaign['uuid'];
                        $messagesProcessed = 0;
                        $messagesFailed = 0;

                        while (($message = $channel->basic_get($campaignQueueName)) !== null) {
                            try {
                                $payload = json_decode($message->getBody(), true);

                                $subscription = new Subscription(
                                    $payload['subscriber']['endpoint'],
                                    $payload['subscriber']['keys']['p256dh'],
                                    $payload['subscriber']['keys']['auth']
                                );

                                $notificationPayload = $payload;

                                $result = $webPush->sendOneNotification(
                                    $subscription,
                                    json_encode($notificationPayload)
                                );

                                $endpoint = $result->getEndpoint();

                                if ($result->isSuccess()) {
                                    $this->climate->success( "Message sent successfully for subscription {$endpoint}." );

                                    $messagesProcessed++;
                                    $totalNotificationsSent++;

                                    $channel->basic_ack($message->getDeliveryTag());
                                } else {
                                    $this->climate->error( "Message failed to sent for subscription {$endpoint}: {$result->getReason()}" );
                            
                                    $messagesFailed++;
                                    $totalNotificationsFailed++;

                                    if ($result->isSubscriptionExpired()) {
                                        $this->db->query("
                                            UPDATE subscribers
                                            SET status = 'inactive'
                                            WHERE endpoint = %s
                                        ", $payload['subscriber']['endpoint']);

                                        $this->db->query("
                                            INSERT INTO analytics_subscribers
                                            (subscriber_id, status)
                                            VALUES (%s, 'inactive')
                                        ", $payload['subscriber']['id']);
                                        
                                        $this->climate->error("Subscription expired for {$endpoint}. Subscriber marked as unsubscribed.");
                                        
                                        $channel->basic_ack($message->getDeliveryTag());
                                    } else {
                                        $channel->basic_reject($message->getDeliveryTag(), true);
                                    }
                                }
                            } catch (Exception $notificationError) {
                                $messagesFailed++;
                                $totalNotificationsFailed++;

                                $this->climate->error(sprintf(
                                    'Error sending notification: %s',
                                    $notificationError->getMessage()
                                ));
                            }
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
