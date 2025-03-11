<?php

namespace Pushbase\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use Pushbase\Database\Database;
use Pushbase\Analytics\SubscribersAnalytics;
use Pushbase\Config\Config;
use Pushbase\Auth;
use GeoIp2\Database\Reader;
use Nyholm\Psr7\Response;
use Ramsey\Uuid\Uuid;
use MeekroDB;

class SubscriberController extends BaseController
{
    private const GEOIP_DB_PATH = __DIR__ . '/../../config/GeoLite2-City.mmdb';
    private $subscribersAnalytics;
    protected $db;
    protected $config;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct();
        $this->db = Database::getInstance();
        $this->config = $container->get(Config::class);
        $this->subscribersAnalytics = new SubscribersAnalytics();
    }

    public function subscribe(ServerRequestInterface $request): ResponseInterface
    {
        $rawBody = $request->getBody()->getContents();

        try {
            $data = json_decode($rawBody, true);
        } catch (\Exception $e) {
            return new Response(
                400,
                ['Content-Type' => 'application/json'],
                json_encode(['error' => _e('error_invalid_json')])
            );
        }

        if (!$data) {
            return new Response(
                400,
                ['Content-Type' => 'application/json'],
                json_encode(['error' => _e('error_invalid_request_body')])
            );
        }

        $required = ['endpoint', 'p256dh', 'authKey'];
        $missingFields = [];

        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            return new Response(
                400,
                ['Content-Type' => 'application/json'],
                json_encode(['error' => _e('error_missing_required_fields_with_list') . implode(', ', $missingFields)])
            );
        }

        $analyticsData = $data['analyticsData'] ?? null;

        $existingSubscriber = $this->db->queryFirstRow(
            "SELECT * FROM subscribers WHERE endpoint = %s",
            $data['endpoint']
        );

        $now = $this->db->sqleval('NOW()');

        try {
            $this->db->startTransaction();

            if ($existingSubscriber) {
                $this->db->update('subscribers', [
                    'p256dh' => $data['p256dh'],
                    'authKey' => $data['authKey'],
                    'last_active' => $this->db->sqleval('NOW()'),
                    'status' => 'active'
                ], "id = %i", $existingSubscriber['id']);
                
                $subscriber = $this->db->queryFirstRow(
                    "SELECT * FROM subscribers WHERE id = %i",
                    $existingSubscriber['id']
                );
            } else {
                $this->db->insert('subscribers', [
                    'uuid' => Uuid::uuid4()->toString(),
                    'endpoint' => $data['endpoint'],
                    'p256dh' => $data['p256dh'],
                    'authKey' => $data['authKey'],
                    'subscribed_at' => $this->db->sqleval('NOW()'),
                    'error_count' => 0,
                    'last_active' => $this->db->sqleval('NOW()'),
                    'status' => 'active'
                ]);

                $subscriberId = $this->db->queryFirstField("SELECT LAST_INSERT_ID()");
                if (!$subscriberId) {
                    $this->db->rollback();
                    throw new \Exception("Failed to get ID for new subscriber");
                }

                $subscriber = $this->db->queryFirstRow(
                    "SELECT * FROM subscribers WHERE id = %i",
                    $subscriberId
                );

                if (!$subscriber) {
                    $this->db->rollback();
                    throw new \Exception("Failed to verify subscriber record after creation");
                }
            }

            $this->db->commit();

            try {
                $analyticsToStore = $analyticsData ?? [];
                $locationData = $this->getLocationData();
                if (!empty($locationData)) {
                    $analyticsToStore['location'] = $locationData;
                }

                if (!empty($analyticsToStore)) {
                    $this->storeAnalyticsData($subscriber['id'], $analyticsToStore);
                }
            } catch (\Exception $e) {
                // Continue with subscription creation/update even if analytics fails
            }

            try {
                $this->subscribersAnalytics->recordSubscriberActivity(
                    $subscriber['id'],
                    'active'
                );
            } catch (\Exception $e) {
                // Continue
            }

            $statusCode = $existingSubscriber ? 200 : 201;
            $message = $existingSubscriber ? _e('success_subscription_updated') : _e('success_subscription_created');
            
            return new Response(
                $statusCode,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'uuid' => $subscriber['uuid'],
                    'message' => $message
                ])
            );
        } catch (\Exception $e) {
            $this->db->rollback();
            return new Response(
                500,
                ['Content-Type' => 'application/json'],
                json_encode(['error' => _e('error_failed_create_subscription')])
            );
        }
    }

    public function status(ServerRequestInterface $request): ResponseInterface
    {
        $data = json_decode($request->getBody()->getContents(), true);

        $token = $data['token'] ?? null;
        $appkey = $data['appkey'] ?? null;

        if (!$token || !$appkey) {
            return new Response(
                400,
                ['Content-Type' => 'application/json'],
                json_encode(['error' => _e('error_token_appkey_required')])
            );
        }

        $subscriber = $this->db->queryFirstRow(
            "SELECT * FROM subscribers WHERE endpoint = %s AND status = 'active'",
            $token
        );

        if ($subscriber) {
            return new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'uuid' => $subscriber['uuid'],
                    'status' => 'active'
                ])
            );
        }

        return new Response(
            404,
            ['Content-Type' => 'application/json'],
            json_encode(['error' => _e('error_subscriber_not_found')])
        );
    }

    public function unsubscribe(ServerRequestInterface $request): ResponseInterface
    {
        $data = json_decode($request->getBody()->getContents(), true);
        $token = $data['token'] ?? $request->getQueryParams()['token'] ?? null;

        if (!$token) {
            return new Response(
                400,
                ['Content-Type' => 'application/json'],
                json_encode(['error' => _e('error_token_required')])
            );
        }

        try {
            $result = $this->db->query(
                "UPDATE subscribers SET status = 'unsubscribed', unsubscribed_at = NOW() WHERE endpoint = %s",
                $token
            );

            if ($this->db->affectedRows() > 0) {
                return new Response(
                    200,
                    ['Content-Type' => 'application/json'],
                    json_encode(['message' => _e('success_unsubscribed')])
                );
            }

            return new Response(
                404,
                ['Content-Type' => 'application/json'],
                json_encode(['error' => _e('error_subscriber_not_found')])
            );
        } catch (\Exception $e) {
            return new Response(
                500,
                ['Content-Type' => 'application/json'],
                json_encode(['error' => _e('error_failed_unsubscribe')])
            );
        }
    }

    public function listSubscribers(ServerRequestInterface $request): ResponseInterface
    {
        $page = $request->getQueryParams()['page'] ?? 1;
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $filters = $request->getQueryParams();
        $whereConditions = [];
        $queryParams = [];

        if (!empty($filters['status'])) {
            $whereConditions[] = "status = %s";
            $queryParams[] = $filters['status'];
        }

        $whereClause = !empty($whereConditions)
            ? "WHERE " . implode(" AND ", $whereConditions)
            : "";

        $totalCount = $this->db->queryFirstField(
            "SELECT COUNT(*) FROM subscribers {$whereClause}",
            ...$queryParams
        );

        $subscribers = $this->db->query(
            "SELECT * FROM subscribers {$whereClause} 
             ORDER BY subscribed_at DESC 
             LIMIT %i, %i",
            ...[...$queryParams, $offset, $perPage]
        );

        $data = [
            'subscribers' => $subscribers,
            'total' => $totalCount,
            'page' => $page,
            'perPage' => $perPage
        ];

        return new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode($data)
        );
    }

    public function exportSubscribers(ServerRequestInterface $request): ResponseInterface
    {
        $subscribers = $this->db->query(
            "SELECT id, uuid, endpoint, subscribed_at, last_active, status 
             FROM subscribers 
             ORDER BY subscribed_at DESC"
        );

        $csvContent = "ID,UUID,Endpoint,Subscribed At,Last Active,Status\n";
        foreach ($subscribers as $subscriber) {
            $csvContent .= implode(',', [
                $subscriber['id'],
                $subscriber['uuid'],
                $subscriber['endpoint'],
                $subscriber['subscribed_at'],
                $subscriber['last_active'],
                $subscriber['status']
            ]) . "\n";
        }

        return new Response(
            200,
            [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="subscribers_' . date('Y-m-d_His') . '.csv"'
            ],
            $csvContent
        );
    }

    private function getLocationData(): array
    {
        if (!file_exists(self::GEOIP_DB_PATH)) {
            return [];
        }

        try {
            $ip = null;
            $ipServices = [
                'https://icanhazip.com',
                'https://ifconfig.me/ip'
            ];
            
            foreach ($ipServices as $service) {
                try {
                    $context = stream_context_create([
                        'http' => [
                            'timeout' => 2
                        ]
                    ]);
                    $ip = trim(file_get_contents($service, false, $context));
                    if ($ip && filter_var($ip, FILTER_VALIDATE_IP)) {
                        break;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
            
            if (!$ip) {
                return [];
            }

            if (!$ip || !filter_var($ip, FILTER_VALIDATE_IP)) {
                return [];
            }

            $reader = new Reader(self::GEOIP_DB_PATH);
            $record = $reader->city($ip);

            if (!$record) {
                return [];
            }

            $locationData = [
                'timezone' => $record->location->timeZone ?? null,
                'country' => $record->country->name ?? null,
                'country_code' => $record->country->isoCode ?? null,
                'region' => $record->mostSpecificSubdivision->name ?? null,
                'region_code' => $record->mostSpecificSubdivision->isoCode ?? null,
                'city' => $record->city->name ?? null,
                'postal_code' => $record->postal->code ?? null,
                'latitude' => $record->location->latitude ?? null,
                'longitude' => $record->location->longitude ?? null
            ];

            return array_filter($locationData, function ($value) {
                return $value !== null;
            });
        } catch (\Exception $e) {
            return [];
        }
    }

    private function storeAnalyticsData(int $subscriberId, array $analyticsData): void
    {
        $db = Database::getInstance();
        $now = $db->sqleval('NOW()');

        $segmentData = [];

        $addSegment = function ($name, $value) use (&$segmentData) {
            if ($value !== null && $value !== '') {
                $segmentData[] = [
                    'segment_name' => $name,
                    'value' => is_array($value) ? json_encode($value) : (string)$value
                ];
            }
        };

        if (!empty($analyticsData['browser'])) {
            $browser = $analyticsData['browser'];
            $addSegment('browser_name', $browser['name'] ?? null);
            $addSegment('browser_version', $browser['version'] ?? null);
        }

        if (!empty($analyticsData['os'])) {
            $os = $analyticsData['os'];
            $addSegment('os_name', $os['name'] ?? null);
            $addSegment('os_version', $os['version'] ?? null);
        }

        $addSegment('device_type', $analyticsData['device'] ?? null);

        if (!empty($analyticsData['screen'])) {
            $screen = $analyticsData['screen'];
            if (isset($screen['width']) && isset($screen['height'])) {
                $addSegment('screen_resolution', $screen['width'] . 'x' . $screen['height']);
            }
        }

        $addSegment('language', $analyticsData['language'] ?? null);

        if (!empty($analyticsData['location'])) {
            foreach ($analyticsData['location'] as $key => $value) {
                if ($value !== null && $key !== 'accuracy_radius') {
                    $addSegment($key, $value);
                }
            }
        }

        $addSegment('category', $analyticsData['category'] ?? null);
        $addSegment('tag', $analyticsData['tag'] ?? null);

        $subscriber = $db->queryFirstRow(
            "SELECT id FROM subscribers WHERE id = %i",
            $subscriberId
        );

        if (!$subscriber) {
            throw new \Exception("Subscriber {$subscriberId} not found");
        }

        $segmentIds = [];
        foreach ($segmentData as $data) {
            $segment = $db->queryFirstRow(
                "SELECT id FROM segments WHERE name = %s",
                $data['segment_name']
            );

            if ($segment) {
                $segmentIds[$data['segment_name']] = $segment['id'];
                continue;
            }

            try {
                $db->startTransaction();
                $segment = $db->queryFirstRow(
                    "SELECT id FROM segments WHERE name = %s FOR UPDATE",
                    $data['segment_name']
                );

                if ($segment) {
                    $segmentIds[$data['segment_name']] = $segment['id'];
                    $db->commit();
                    continue;
                }

                $db->query(
                    "INSERT INTO segments (name, created_at) 
                     VALUES (%s, NOW())",
                    $data['segment_name']
                );

                $segmentId = $db->queryFirstField("SELECT LAST_INSERT_ID()");
                if (!$segmentId) {
                    throw new \Exception("Failed to get ID for new segment: " . $data['segment_name']);
                }

                $segmentIds[$data['segment_name']] = $segmentId;

                $db->commit();
            } catch (\Exception $e) {
                $db->rollback();
                throw $e;
            }
        }

        try {
            $db->startTransaction();

            foreach ($segmentData as $data) {
                $normalizedValue = is_array($data['value']) ? json_encode($data['value']) : (string)$data['value'];

                $existingGoals = $db->query(
                    "SELECT id, value, created_at FROM segment_goals 
                     WHERE segment_id = %i AND subscriber_id = %i
                     ORDER BY created_at DESC",
                    $segmentIds[$data['segment_name']],
                    $subscriberId
                );

                $shouldInsert = true;
                if (!empty($existingGoals)) {
                    $latestGoal = $existingGoals[0];
                    if ((string)$latestGoal['value'] === $normalizedValue) {
                        $shouldInsert = false;
                    }
                }

                if ($shouldInsert) {
                    $db->insert('segment_goals', [
                        'segment_id' => $segmentIds[$data['segment_name']],
                        'subscriber_id' => $subscriberId,
                        'value' => $normalizedValue,
                        'created_at' => $now
                    ]);

                    $existingRecord = $db->query(
                        "SELECT id, `count` FROM analytics_segments
                         WHERE segment_id = %i AND segment_value = %s",
                        $segmentIds[$data['segment_name']],
                        $normalizedValue
                    );

                    $now = date('Y-m-d H:i:s');

                    if (!empty($existingRecord)) {
                        $db->query(
                            "UPDATE analytics_segments
                             SET `count` = `count` + 1,
                                 last_occurred_at = %s
                             WHERE id = %i",
                            $now,
                            $existingRecord[0]['id']
                        );
                    } else {
                        $db->query(
                            "INSERT INTO analytics_segments
                             (segment_id, segment_value, `count`, last_occurred_at)
                             VALUES (%i, %s, 1, %s)",
                            $segmentIds[$data['segment_name']],
                            $normalizedValue,
                            $now
                        );
                    }
                }
            }

            $db->commit();
        } catch (\Exception $e) {
            $db->rollback();
            throw $e;
        }
    }
}
