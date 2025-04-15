<?php

namespace alo\Commands\Campaign;

use League\CLImate\CLImate;
use alo\Config\Config;
use alo\Database\Database;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Exception;

class QueueCommand
{
    private $config;
    private $db;
    private $climate;

    public function __construct(Config $config, CLImate $climate)
    {
        $this->config = $config;
        $this->db = \alo\Database\Database::getInstance();
        $this->climate = $climate;
    }

    private function buildSegmentQuery($segments)
    {
        if (is_string($segments)) {
            $segments = json_decode($segments, true);
        }

        if (empty($segments) || !is_array($segments)) {
            $this->climate->yellow()->out(empty($segments) ? "No segments specified." : "Invalid segments format.");
            return [
                "SELECT id, uuid, endpoint, p256dh, authKey FROM subscribers WHERE status = 'active'",
                []
            ];
        }

        $segmentConditions = [];
        $params = [];

        foreach ($segments as $segment) {
            if (!isset($segment['type']) || !isset($segment['values'])) {
                $this->climate->yellow()->out("Invalid segment structure. Skipping.");
                continue;
            }

            $type = $segment['type'];
            $values = $segment['values'];

            if (!is_array($values)) {
                $this->climate->yellow()->out("Segment values must be an array. Skipping.");
                continue;
            }

            if (empty($values)) {
                $this->climate->yellow()->out("Empty segment values. Skipping.");
                continue;
            }

            switch ($type) {
                case '31':
                    $numericValues = array_map('floatval', $values);
                    if (count($numericValues) >= 2) {
                        $segmentConditions[] = "(id BETWEEN %f AND %f)";
                        $params[] = min($numericValues);
                        $params[] = max($numericValues);
                    }
                    break;
                case '33':
                    $placeholders = implode(',', array_fill(0, count($values), '%s'));
                    $segmentConditions[] = "(custom_field = %s AND custom_value IN ($placeholders))";
                    $params[] = $type;
                    $params = array_merge($params, $values);
                    break;
                default:
                    $placeholders = implode(',', array_fill(0, count($values), '%s'));
                    $segmentConditions[] = "EXISTS (
                        SELECT 1 FROM segment_goals sg
                        WHERE sg.subscriber_id = s.id
                        AND sg.segment_id = %s
                        AND sg.value IN ($placeholders)
                    )";
                    $params[] = $type;
                    $params = array_merge($params, $values);
            }
        }

        if (empty($segmentConditions)) {
            return [
                "SELECT id, uuid, endpoint, p256dh, authKey FROM subscribers WHERE status = 'active'",
                []
            ];
        }

        $whereClause = implode(' OR ', $segmentConditions);

        $query = "
            SELECT DISTINCT s.id, s.uuid, s.endpoint, s.p256dh, s.authKey
            FROM subscribers s
            WHERE s.status = 'active' AND ({$whereClause})
        ";

        return [$query, $params];
    }

    private function countSubscribersBySegments($segments)
    {
        list($query, $params) = $this->buildSegmentQuery($segments);
        
        // Convert the SELECT query to a COUNT query
        $countQuery = preg_replace('/SELECT.*?FROM/', 'SELECT COUNT(*) FROM', $query);
        
        $fullQueryParams = array_merge([$countQuery], $params);
        $count = call_user_func_array([$this->db, 'queryFirstField'], $fullQueryParams);
        
        return $count;
    }

    private function processSubscribersInBatches($campaign, $channel, $vapidConfig, $baseNotificationPayload)
    {
        $segments = empty($campaign['segments']) ? null : $campaign['segments'];
        $batchSize = 1000; // Process 1000 subscribers at a time
        $offset = 0;
        $totalProcessed = 0;
        
        // Get total count first
        $totalSubscribers = $this->countSubscribersBySegments($segments);
        $this->climate->out("Campaign has approximately {$totalSubscribers} subscribers after segment filtering");
        
        list($baseQuery, $params) = $this->buildSegmentQuery($segments);
        
        // Add pagination to the query
        $query = $baseQuery . " LIMIT %i OFFSET %i";
        
        while (true) {
            // Add pagination parameters
            $batchParams = array_merge($params, [$batchSize, $offset]);
            $fullQueryParams = array_merge([$query], $batchParams);
            
            // Execute the query for this batch
            $subscribers = call_user_func_array([$this->db, 'query'], $fullQueryParams);
            
            $batchCount = count($subscribers);
            if ($batchCount == 0) {
                break; // No more subscribers to process
            }
            
            $this->climate->out("Processing batch of {$batchCount} subscribers (offset: {$offset})");
            
            foreach ($subscribers as $subscriber) {
                $subscriberPayload = [
                    'campaign' => [
                        'id' => $campaign['id'],
                        'uuid' => $campaign['uuid']
                    ],
                    'notification' => $baseNotificationPayload,
                    'vapid' => $vapidConfig,
                    'subscriber' => [
                        'id' => $subscriber['id'],
                        'uuid' => $subscriber['uuid'],
                        'endpoint' => $subscriber['endpoint'],
                        'keys' => [
                            'p256dh' => $subscriber['p256dh'],
                            'auth' => $subscriber['authKey']
                        ]
                    ]
                ];
                
                $msg = new AMQPMessage(json_encode($subscriberPayload), [
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
                ]);
                
                $channel->basic_publish($msg, '', $campaign['uuid']);
            }
            
            $totalProcessed += $batchCount;
            $offset += $batchSize;
            
            // Free up memory
            unset($subscribers);
            gc_collect_cycles();
            
            $this->climate->out("Processed {$totalProcessed} of {$totalSubscribers} subscribers");
        }
        
        return $totalProcessed;
    }

    public function execute(): int
    {
        try {
            $this->climate->bold()->blue()->out('Campaign Queue Processing');

            $rabbitmqConfig = $this->config->get('rabbitmq');

            $connection = new AMQPStreamConnection(
                $rabbitmqConfig['host'],
                $rabbitmqConfig['port'],
                $rabbitmqConfig['user'],
                $rabbitmqConfig['password'],
                $rabbitmqConfig['vhost']
            );

            $channel = $connection->channel();

            $currentTimestamp = date('Y-m-d H:i:s');

            $campaigns = $this->db->query("
                SELECT * FROM campaigns 
                WHERE status = 'scheduled' 
                AND (send_at IS NULL OR send_at <= %s)
            ", $currentTimestamp);

            $totalCampaigns = count($campaigns);
            $this->climate->out("Found {$totalCampaigns} campaigns to process");

            $processedCampaigns = 0;
            $failedCampaigns = 0;

            foreach ($campaigns as $campaign) {
                $this->climate->out("Processing campaign: {$campaign['name']} (ID: {$campaign['id']}) (UUID: {$campaign['uuid']})");

                try {
                    try {
                        $channel->queue_delete($campaign['uuid']);
                        $this->climate->out("Existing queue {$campaign['uuid']} deleted successfully");
                    } catch (Exception $queueDeleteError) {
                        $this->climate->out("No existing queue found for {$campaign['uuid']}");
                    }

                    $channel->queue_declare(
                        $campaign['uuid'],
                        false,   // passive
                        true,    // durable
                        false,   // exclusive
                        true,    // auto_delete
                        false,   // nowait
                        [
                            'x-message-ttl' => ['I', 604800000] // 7 days in milliseconds
                        ]
                    );

                    $this->db->query("
                        UPDATE campaigns 
                        SET status = 'queuing' 
                        WHERE id = %s
                    ", $campaign['id']);

                    $vapidConfig = [
                        'subject' => $this->config->get('app.url'),
                        'publicKey' => $this->config->get('firebase.vapid.public'),
                        'privateKey' => $this->config->get('firebase.vapid.private')
                    ];

                    $baseNotificationPayload = [
                        'title' => $campaign['push_title'],
                        'body' => $campaign['push_body'],
                        'tag' => $campaign['uuid']
                    ];

                    if (!empty($campaign['push_icon'])) {
                        $baseNotificationPayload['icon'] = $campaign['push_icon'];
                    }
                    if (!empty($campaign['push_image'])) {
                        $baseNotificationPayload['image'] = $campaign['push_image'];
                    }
                    if (!empty($campaign['push_url'])) {
                        $baseNotificationPayload['click_action'] = $campaign['push_url'];
                    }
                    if (!empty($campaign['push_badge'])) {
                        $baseNotificationPayload['badge'] = $campaign['push_badge'];
                    }
                    if (!empty($campaign['push_requireInteraction'])) {
                        $baseNotificationPayload['requireInteraction'] = true;
                    }
                    if (!empty($campaign['push_renotify'])) {
                        $baseNotificationPayload['renotify'] = true;
                    }
                    if (!empty($campaign['push_silent'])) {
                        $baseNotificationPayload['silent'] = true;
                    }

                    // Process subscribers in batches to avoid memory issues
                    $subscriberCount = $this->processSubscribersInBatches(
                        $campaign,
                        $channel,
                        $vapidConfig,
                        $baseNotificationPayload
                    );

                    $this->db->query("
                        UPDATE campaigns 
                        SET status = 'sending', 
                            total_recipients = %i,
                            started_at = NOW()
                        WHERE id = %s
                    ", $subscriberCount, $campaign['id']);

                    $processedCampaigns++;
                    $this->climate->green()->bold()->out("Campaign {$campaign['name']} queued successfully in queue {$campaign['uuid']} with {$subscriberCount} messages");
                } catch (Exception $campaignError) {
                    $this->db->query("
                        UPDATE campaigns 
                        SET status = 'scheduled' 
                        WHERE id = %s
                    ", $campaign['id']);

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

            $this->climate->bold()->blue()->out('Campaign Queue Processing Summary');
            $summaryTable = [
                ['Total Campaigns', 'Processed', 'Failed'],
                [$totalCampaigns, $processedCampaigns, $failedCampaigns]
            ];
            $this->climate->table($summaryTable);

            return $failedCampaigns > 0 ? 1 : 0;
        } catch (Exception $e) {
            $this->climate->error("Critical Error: " . $e->getMessage());
            return 1;
        }
    }
}
