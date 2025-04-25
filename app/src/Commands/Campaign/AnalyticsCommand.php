<?php

namespace alo\Commands\Campaign;

use League\CLImate\CLImate;
use alo\Config\Config;
use alo\Database\Database;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Exception;

class AnalyticsCommand
{
    private $config;
    private $db;
    private $climate;
    private $processedMessages = 0;
    private $failedMessages = 0;
    private $hourlyStats = [];
    private $sleepInterval = 5;
    private $maxRetries = 3;
    private $errorSleepInterval = 30;
    private $batchSize = 100;

    public function __construct(Config $config, CLImate $climate)
    {
        $this->config = $config;
        $this->db = Database::getInstance();
        $this->climate = $climate;
    }

    private function processMessage($message)
    {
        $body = $message->body;
        $data = json_decode($body, true);

        if (!$data || !isset($data['campaignId']) || !isset($data['action']) || !isset($data['timestamp'])) {
            $this->climate->red()->out("Invalid message format: " . $body);
            $this->failedMessages++;
            return false;
        }

        $campaignId = $data['campaignId'];
        $action = $data['action'];
        $timestamp = $data['timestamp'];

        try {
            $campaignExists = $this->db->queryFirstField(
                "SELECT COUNT(*) FROM campaigns WHERE id = %i",
                $campaignId
            );

            if (!$campaignExists) {
                $this->climate->yellow()->out("Campaign ID {$campaignId} does not exist. Skipping.");
                $this->failedMessages++;
                return false;
            }

            $dateTime = new \DateTime($timestamp);
            $hourDateTime = $dateTime->format('Y-m-d H:00:00');

            $key = "{$campaignId}_{$action}_{$hourDateTime}";
            if (!isset($this->hourlyStats[$key])) {
                $this->hourlyStats[$key] = [
                    'campaign_id' => $campaignId,
                    'interaction_type' => $action,
                    'hour' => $hourDateTime,
                    'count' => 0
                ];
            }
            $this->hourlyStats[$key]['count']++;

            $this->processedMessages++;
            return true;
        } catch (Exception $e) {
            $this->climate->red()->out("Error processing message: " . $e->getMessage());
            $this->failedMessages++;
            return false;
        }
    }

    private function saveHourlyStats()
    {
        if (empty($this->hourlyStats)) {
            return;
        }

        $this->climate->out("Saving hourly statistics...");

        foreach ($this->hourlyStats as $stat) {
            try {
                $exists = $this->db->queryFirstField(
                    "SELECT id FROM analytics_campaign
                     WHERE campaign_id = %i
                     AND interaction_type = %s
                     AND hour = %s",
                    $stat['campaign_id'],
                    $stat['interaction_type'],
                    $stat['hour']
                );
                
                if ($exists) {
                    $this->db->query(
                        "UPDATE analytics_campaign
                         SET count = count + %i
                         WHERE id = %i",
                        $stat['count'],
                        $exists
                    );
                } else {
                    $this->db->query(
                        "INSERT INTO analytics_campaign
                         (campaign_id, interaction_type, hour, count)
                         VALUES (%i, %s, %s, %i)",
                        $stat['campaign_id'],
                        $stat['interaction_type'],
                        $stat['hour'],
                        $stat['count']
                    );
                }
            } catch (Exception $e) {
                $this->climate->red()->out("Error saving hourly stats: " . $e->getMessage());
            }
        }

        $this->hourlyStats = [];
    }

    public function execute(): int
    {
        set_time_limit(0);
        $errorCount = 0;
        
        while (true) {
            try {
                $this->climate->bold()->blue()->out('Campaign Analytics Processing (Background Mode)');
                $this->processedMessages = 0;
                $this->failedMessages = 0;
                $this->hourlyStats = [];

                $amqpConfig = $this->config->get('amqp');

                $connection = new AMQPStreamConnection(
                    $amqpConfig['host'],
                    $amqpConfig['port'],
                    $amqpConfig['user'],
                    $amqpConfig['password'],
                    $amqpConfig['vhost']
                );

                $channel = $connection->channel();

                $channel->queue_declare(
                    'analytics:campaign',
                    false,   // passive
                    true,    // durable
                    false,   // exclusive
                    false    // auto_delete
                );

                $this->climate->out("Processing messages from analytics:campaign queue...");

                $channel->basic_qos(null, $this->batchSize, null);

                $messagesAvailable = true;
                
                while ($messagesAvailable) {
                    $messages = [];
                    $messageCount = 0;
                    
                    for ($i = 0; $i < $this->batchSize; $i++) {
                        $message = $channel->basic_get('analytics:campaign');
                        if ($message === null) {
                            if ($i === 0) {
                                $messagesAvailable = false;
                            }
                            break;
                        }
                        $messages[] = $message;
                        $messageCount++;
                    }
                    
                    if ($messageCount > 0) {
                        $this->climate->info("Processing batch of {$messageCount} messages");
                        
                        foreach ($messages as $message) {
                            $result = $this->processMessage($message);
                            $channel->basic_ack($message->getDeliveryTag());
                        }
                        
                        $this->saveHourlyStats();
                        $this->climate->out("Processed {$this->processedMessages} messages, failed {$this->failedMessages}");
                    } else {
                        $this->climate->out("No messages available. Waiting {$this->sleepInterval} seconds...");
                        sleep($this->sleepInterval);
                    }
                }

                $this->saveHourlyStats();

                $channel->close();
                $connection->close();

                $this->climate->bold()->blue()->out('Campaign Analytics Processing Summary');
                $summaryTable = [
                    ['Processed Messages', 'Failed Messages'],
                    [$this->processedMessages, $this->failedMessages]
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