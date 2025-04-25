<?php

namespace alo\Analytics;

use alo\Database\Database;
use alo\Config\Config;
use MeekroDB;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Nyholm\Psr7\Response;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class SubscribersAnalytics
{
    private $db;
    private $config;

    public function __construct(Config $config = null)
    {
        $this->db = Database::getInstance();
        $this->config = $config ?? new Config();
    }
    public function recordSubscriberActivity(int $subscriberId, string $status, ?string $timestamp = null): bool
    {
        if (!$subscriberId) {
            return false;
        }

        $validStatuses = ['active', 'inactive', 'unsubscribed'];
        if (!in_array($status, $validStatuses)) {
            return false;
        }

        $timestamp = $timestamp ?? date('Y-m-d H:i:s');

        try {
            $subscriberExists = $this->db->queryFirstField(
                "SELECT COUNT(*) FROM subscribers WHERE id = %i",
                $subscriberId
            );

            if (!$subscriberExists) {
                return false;
            }

            $query = "INSERT INTO analytics_subscribers (subscriber_id, status, created_at) 
                       VALUES (%s, %s, %s)";

            $result = $this->db->query($query, $subscriberId, $status, $timestamp);

            if ($result === false) {
                return false;
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function recordPushNotificationEvent(
        int $subscriberId,
        int $campaignId,
        string $interactionType
    ): bool {
        if (!$subscriberId || !$campaignId) {
            return false;
        }

        $validInteractionTypes = ['clicked', 'delivered'];
        if (!in_array($interactionType, $validInteractionTypes)) {
            return false;
        }

        try {
            $subscriberExists = $this->db->queryFirstField(
                "SELECT COUNT(*) FROM subscribers WHERE id = %i",
                $subscriberId
            );

            if (!$subscriberExists) {
                return false;
            }

            $campaignExists = $this->db->queryFirstField(
                "SELECT COUNT(*) FROM campaigns WHERE id = %i",
                $campaignId
            );

            if (!$campaignExists) {
                return false;
            }

            try {
                $this->sendToAMQP($campaignId, $subscriberId, $interactionType);
                return true;
            } catch (\Exception $e) {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    public function trackAnalytics(ServerRequestInterface $request): ResponseInterface
    {
        $rawBody = $request->getBody()->getContents();

        try {
            $data = json_decode($rawBody, true);
        } catch (\Exception $e) {
            return $this->errorResponse('Invalid JSON', 400);
        }

        $requiredFields = ['subscriberId', 'type', 'campaignId'];
        $missingFields = [];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            return $this->errorResponse(
                'Missing required fields: ' . implode(', ', $missingFields),
                400
            );
        }

        $validTypes = ['clicked', 'delivered'];
        if (!in_array($data['type'], $validTypes)) {
            return $this->errorResponse(
                'Invalid analytics type. Must be one of: ' . implode(', ', $validTypes),
                400
            );
        }

        try {
            $result = $this->recordPushNotificationEvent(
                $data['subscriberId'],
                $data['campaignId'],
                $data['type']
            );

            if (!$result) {
                return $this->errorResponse('Failed to record analytics event', 500);
            }

            return $this->successResponse(
                ['id' => $data['subscriberId']],
                'Analytics event recorded successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to record analytics event', 500);
        }
    }

    private function errorResponse(string $message, int $statusCode = 400): ResponseInterface
    {
        return new Response(
            $statusCode,
            ['Content-Type' => 'application/json'],
            json_encode(['error' => $message])
        );
    }

    private function successResponse(array $data, string $message, int $statusCode = 200): ResponseInterface
    {
        return new Response(
            $statusCode,
            ['Content-Type' => 'application/json'],
            json_encode([
                'message' => $message,
                'data' => $data
            ])
        );
    }

    private function sendToAMQP(int $campaignId, int $subscriberId, string $interactionType): void
    {
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
    }
}
