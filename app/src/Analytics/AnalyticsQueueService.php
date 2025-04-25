<?php

namespace alo\Analytics;

use alo\Config\Config;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Exception;

class AnalyticsQueueService
{
    private $config;
    private $logger;

    public function __construct(Config $config, $logger = null)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Send analytics data to the RabbitMQ queue
     *
     * @param int $campaignId The campaign ID
     * @param int $subscriberId The subscriber ID
     * @param string $interactionType The type of interaction (sent, failed, clicked, delivered, etc.)
     * @return bool True if successful, false otherwise
     */
    public function sendToAnalyticsQueue(int $campaignId, int $subscriberId, string $interactionType): bool
    {
        try {
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
            
            $data = [
                'campaignId' => $campaignId,
                'subscriberId' => $subscriberId,
                'action' => $interactionType,
                'timestamp' => date('Y-m-d\TH:i:s.v\Z', time())
            ];
            
            $msg = new AMQPMessage(
                json_encode($data),
                ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
            );
            
            $channel->basic_publish($msg, '', 'analytics:campaign');
            
            $channel->close();
            $connection->close();
            
            return true;
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error("Failed to send analytics to queue: " . $e->getMessage());
            }
            return false;
        }
    }
}