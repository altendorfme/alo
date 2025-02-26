<?php

namespace Pushbase\Analytics;

use Pushbase\Database\Database;
use MeekroDB;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Nyholm\Psr7\Response;

class SubscribersAnalytics
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
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

            // Prepare and execute the insert query
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
        // Validate input parameters
        if (!$subscriberId || !$campaignId) {
            return false;
        }

        // Validate interaction type
        $validInteractionTypes = ['clicked', 'delivered'];
        if (!in_array($interactionType, $validInteractionTypes)) {
            return false;
        }

        try {
            // Verify subscriber exists
            $subscriberExists = $this->db->queryFirstField(
                "SELECT COUNT(*) FROM subscribers WHERE id = %i",
                $subscriberId
            );

            if (!$subscriberExists) {
                return false;
            }

            // Verify campaign exists
            $campaignExists = $this->db->queryFirstField(
                "SELECT COUNT(*) FROM campaigns WHERE id = %i",
                $campaignId
            );

            if (!$campaignExists) {
                return false;
            }

            // Prepare and execute the insert query for analytics_campaign
            $query = "INSERT INTO analytics_campaign 
                      (campaign_id, subscriber_id, interaction_type, created_at) 
                      VALUES (%i, %i, %s, NOW())";

            $result = $this->db->query(
                $query,
                $campaignId,
                $subscriberId,
                $interactionType
            );

            if ($result === false) {
                return false;
            }

            return true;
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
}
