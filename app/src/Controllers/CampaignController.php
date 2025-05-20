<?php

namespace alo\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use alo\Database\Database;
use alo\Auth;
use alo\Config\Config;
use MeekroDB;
use Exception;
use Nyholm\Psr7\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CampaignController extends BaseController
{
    protected MeekroDB $db;
    protected $auth;
    protected Config $config;
    public function __construct(ContainerInterface $container)
    {
        parent::__construct();
        $this->db = Database::getInstance();
        $this->auth = Auth::getInstance();
        $this->config = $container->get(Config::class);
    }

    private function getCurrentUserId(): ?int
    {
        $authCheck = $this->auth->check();
        if (!$authCheck) {
            return null;
        }

        $user = $this->auth->getUser();

        return $user['id'] ?? null;
    }

    public function viewCampaigns(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $currentPage = isset($args['page']) ? (int)$args['page'] : 1;
        if ($currentPage < 1) $currentPage = 1;

        $queryParams = $request->getQueryParams();
        $statusFilter = $queryParams['status'] ?? null;

        $perPage = 20;
        $offset = ($currentPage - 1) * $perPage;

        $baseAnalyticsSelect = "
            (SELECT IFNULL(SUM(count), 0) FROM analytics_campaign ac WHERE ac.campaign_id = c.id AND ac.interaction_type = 'delivered') AS successfully_count,
            (SELECT IFNULL(SUM(count), 0) FROM analytics_campaign ac WHERE ac.campaign_id = c.id AND ac.interaction_type = 'failed') AS error_count,
            (SELECT IFNULL(SUM(count), 0) FROM analytics_campaign ac WHERE ac.campaign_id = c.id AND ac.interaction_type = 'clicked') AS clicked_count,
            c.total_recipients
        ";

        if ($statusFilter) {
            $whereClause = "WHERE status = %s";
            $queryParams = [$statusFilter];

            $totalCount = $this->db->queryFirstField(
                "SELECT COUNT(*) FROM campaigns " . $whereClause,
                ...array_values($queryParams)
            );

            $totalPages = ceil($totalCount / $perPage);
            if ($currentPage > $totalPages && $totalPages > 0) {
                return new Response(302, ['Location' => '/campaigns/page/' . $totalPages]);
            }

            $campaigns = $this->db->query(
                "SELECT
                    c.*,
                    $baseAnalyticsSelect
                FROM campaigns c " .
                    $whereClause .
                    " ORDER BY c.created_at DESC LIMIT %i, %i",
                ...array_merge(array_values($queryParams), [$offset, $perPage])
            );

            $data = [
                'campaigns' => $campaigns,
                'currentPage' => $currentPage,
                'totalPages' => $totalPages,
                'statusFilter' => $statusFilter,
                'statuses' => $this->db->queryFirstColumn("SELECT DISTINCT status FROM campaigns ORDER BY status")
            ];
        } else {
            $draftCampaigns = $this->db->query(
                "SELECT
                    c.*,
                    $baseAnalyticsSelect
                FROM campaigns c
                WHERE c.status = 'draft'
                ORDER BY c.created_at ASC"
            );

            $scheduledCampaigns = $this->db->query(
                "SELECT
                    c.*,
                    $baseAnalyticsSelect
                FROM campaigns c
                WHERE c.status = 'scheduled'
                ORDER BY c.send_at ASC"
            );

            $sendingCampaigns = $this->db->query(
                "SELECT
                    c.*,
                    $baseAnalyticsSelect
                FROM campaigns c
                WHERE c.status = 'sending'
                ORDER BY c.send_at ASC"
            );

            $queuingCampaigns = $this->db->query(
                "SELECT
                    c.*,
                    $baseAnalyticsSelect
                FROM campaigns c
                WHERE c.status = 'queuing'
                ORDER BY c.send_at ASC"
            );

            $otherCampaigns = $this->db->query(
                "SELECT
                    c.*,
                    $baseAnalyticsSelect
                FROM campaigns c
                WHERE c.status IN ('sent', 'cancelled')
                ORDER BY c.created_at DESC
                LIMIT 20"
            );

            $totalCount = $this->db->queryFirstField(
                "SELECT COUNT(*) FROM campaigns WHERE status IN ('sent', 'cancelled')"
            );

            $totalPages = ceil($totalCount / $perPage);

            $data = [
                'sendingCampaigns' => $sendingCampaigns,
                'scheduledCampaigns' => $scheduledCampaigns,
                'queuingCampaigns' => $queuingCampaigns,
                'draftCampaigns' => $draftCampaigns,
                'otherCampaigns' => $otherCampaigns,
                'currentPage' => $currentPage,
                'totalPages' => $totalPages,
                'statusFilter' => null,
                'statuses' => $this->db->queryFirstColumn("SELECT DISTINCT status FROM campaigns ORDER BY status")
            ];
        }

        if (isset($queryParams['error'])) {
            $data['error'] = $queryParams['error'];
        }
        if (isset($queryParams['cancelled'])) {
            $data['cancelled'] = $queryParams['cancelled'];
        }
        if (isset($queryParams['deleted'])) {
            $data['deleted'] = $queryParams['deleted'];
        }

        return $this->render('main/campaigns', $data);
    }

    public function processCampaigns(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $params = (array)$request->getParsedBody();
        $data = [];

        if (isset($params['error'])) {
            $data['error'] = $params['error'];
        }
        if (isset($params['cancelled'])) {
            $data['cancelled'] = $params['cancelled'];
        }
        if (isset($params['deleted'])) {
            $data['deleted'] = $params['deleted'];
        }

        return new Response(302, [
            'Location' => '/campaigns?' . http_build_query($data)
        ]);
    }

    private function getListSegments(): array
    {
        $listSegments = $this->db->query(
            "SELECT
                s.id,
                s.name,
                s.description,
                (SELECT GROUP_CONCAT(DISTINCT `value` SEPARATOR '|')
                 FROM segment_goals sg
                 WHERE sg.segment_id = s.id) AS segment_values
             FROM segments s
             ORDER BY s.name"
        );

        $listSegments = array_map(function ($segment) {
            return [
                'id' => $segment['id'],
                'name' => $segment['name'],
                'description' => $segment['description'],
                'values' => $segment['segment_values'] ? explode('|', $segment['segment_values']) : []
            ];
        }, $listSegments);

        return array_filter($listSegments);
    }

    public function viewCampaign(ServerRequestInterface $request): ResponseInterface
    {
        $listSegments = $this->getListSegments();

        return $this->render('main/campaign', [
            'title' => _e('campaign_create'),
            'campaign' => null,
            'isEdit' => false,
            'listSegments' => $listSegments,
            'client' => [
                'icon' => $this->config->get('client.icon') ?? null,
                'badge' => $this->config->get('client.badge') ?? null,
            ]
        ]);
    }

    public function viewEditCampaign(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $id = $args['id'] ?? null;
        if (!$id) {
            return new Response(302, ['Location' => '/campaigns']);
        }

        $campaignObj = new \alo\Campaign();
        $campaign = $campaignObj->get($id);

        if (!$campaign || !in_array($campaign['status'], ['draft', 'cancelled'])) {
            return new Response(302, [
                'Location' => '/campaigns?error=' . urlencode('error_not_allowed_edit_campaign')
            ]);
        }

        $listSegments = $this->getListSegments();

        return $this->render('main/campaign', [
            'title' => _e('campaign_edit'),
            'campaign' => $campaign,
            'isEdit' => true,
            'listSegments' => $listSegments,
            'client' => [
                'icon' => $this->config->get('client.icon') ?? null,
                'badge' => $this->config->get('client.badge') ?? null,
            ]
        ]);
    }

    public function processCampaign(ServerRequestInterface $request): ResponseInterface
    {
        $listSegments = $this->getListSegments();
        $params = (array)$request->getParsedBody();
        $data = [
            'name' => $params['name'] ?? '',
            'push_title' => $params['push_title'] ?? '',
            'push_body' => $params['push_body'] ?? '',
            'push_icon' => $params['push_icon'] ?? null,
            'push_image' => $params['push_image'] ?? null,
            'push_url' => $params['push_url'] ?? null,
            'send_at' => $params['send_at'] ? date('Y-m-d H:i:s', strtotime($params['send_at'])) : null,
            'push_requireInteraction' => isset($params['push_requireInteraction']),
            'push_badge' => $params['push_badge'] ?? null,
            'push_renotify' => isset($params['push_renotify']),
            'push_silent' => isset($params['push_silent']),
            'segments' => (isset($params['segments']) && is_array($params['segments']) && !empty(array_filter($params['segments'])))
                ? array_filter($params['segments'])
                : null
        ];

        if (empty($data['name']) || empty($data['push_title']) || empty($data['push_body'])) {
            return $this->render('main/campaign', [
                'title' => _e('campaign_create'),
                'error' => _e('error_name_title_body_required'),
                'campaign' => $data,
                'isEdit' => false,
                'segments' => $params['segments'] ?? null,
                'listSegments' => $listSegments,
                'client' => [
                    'icon' => $this->config->get('client.icon') ?? null,
                    'badge' => $this->config->get('client.badge') ?? null,
                ]
            ]);
        }

        try {
            $action = $params['action'] ?? 'save';
            $data['status'] = $action === 'draft' ? 'draft' : 'scheduled';

            $currentUserId = $this->getCurrentUserId();
            if ($currentUserId === null) {
                return $this->render('main/campaign', [
                    'title' => _e('campaign_create'),
                    'error' => _e('error_authentication_required'),
                    'campaign' => $data,
                    'isEdit' => false,
                    'segments' => $params['segments'] ?? null,
                    'listSegments' => $listSegments,
                    'client' => [
                        'icon' => $this->config->get('client.icon') ?? null,
                        'badge' => $this->config->get('client.badge') ?? null,
                    ]
                ]);
            }

            $campaignObj = new \alo\Campaign();
            $result = $campaignObj->create($data, $currentUserId);

            if ($result) {
                return new Response(302, [
                    'Location' => '/campaigns?success=' . urlencode('success_campaign_published')
                ]);
            } else {
                return $this->render('main/campaign', [
                    'title' => _e('campaign_create'),
                    'error' => _e('error_failed_to') . _e('create'),
                    'campaign' => $data,
                    'isEdit' => false,
                    'segments' => $params['segments'] ?? null,
                    'listSegments' => $listSegments,
                    'client' => [
                        'icon' => $this->config->get('client.icon') ?? null,
                        'badge' => $this->config->get('client.badge') ?? null,
                    ]
                ]);
            }
        } catch (Exception $e) {
            return $this->render('main/campaign', [
                'title' => _e('campaign_create'),
                'error' => $e->getMessage(),
                'campaign' => $data,
                'isEdit' => false,
                'segments' => $params['segments'] ?? null,
                'listSegments' => $listSegments,
                'client' => [
                    'icon' => $this->config->get('client.icon') ?? null,
                    'badge' => $this->config->get('client.badge') ?? null,
                ]
            ]);
        }
    }

    public function processEditCampaign(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $id = $args['id'] ?? null;
        if (!$id) {
            return new Response(302, ['Location' => '/campaigns']);
        }

        $campaignObj = new \alo\Campaign();
        $campaign = $campaignObj->get($id);

        if (!$campaign || !in_array($campaign['status'], ['draft', 'scheduled', 'cancelled'])) {
            return new Response(302, [
                'Location' => '/campaigns?error=' . urlencode('error_not_allowed_edit_campaign')
            ]);
        }

        $listSegments = $this->getListSegments();
        $params = (array)$request->getParsedBody();
        $data = [
            'name' => $params['name'] ?? '',
            'push_title' => $params['push_title'] ?? '',
            'push_body' => $params['push_body'] ?? '',
            'push_icon' => $params['push_icon'] ?? null,
            'push_image' => $params['push_image'] ?? null,
            'push_url' => $params['push_url'] ?? null,
            'send_at' => $params['send_at'] ? date('Y-m-d H:i:s', strtotime($params['send_at'])) : null,
            'push_requireInteraction' => isset($params['push_requireInteraction']),
            'push_badge' => $params['push_badge'] ?? null,
            'push_renotify' => isset($params['push_renotify']),
            'push_silent' => isset($params['push_silent']),
            'segments' => (isset($params['segments']) && is_array($params['segments']) && !empty(array_filter($params['segments'])))
                ? array_filter($params['segments'])
                : null
        ];

        if (empty($data['name']) || empty($data['push_title']) || empty($data['push_body'])) {
            return $this->render('main/campaign', [
                'title' => _e('campaign_edit'),
                'error' => _e('error_name_title_body_required'),
                'campaign' => array_merge($campaign, $data),
                'isEdit' => true,
                'segments' => $params['segments'] ?? null,
                'listSegments' => $listSegments,
                'client' => [
                    'icon' => $this->config->get('client.icon') ?? null,
                    'badge' => $this->config->get('client.badge') ?? null,
                ]
            ]);
        }

        try {
            $action = $params['action'] ?? 'save';
            $data['status'] = $action === 'draft' ? 'draft' : 'scheduled';

            $currentUserId = $this->getCurrentUserId();
            if ($currentUserId === null) {
                return $this->render('main/campaign', [
                    'title' => _e('campaign_edit'),
                    'error' => _e('error_authentication_required'),
                    'campaign' => array_merge($campaign, $data),
                    'isEdit' => true,
                    'segments' => $params['segments'] ?? null,
                    'listSegments' => $listSegments,
                    'client' => [
                        'icon' => $this->config->get('client.icon') ?? null,
                        'badge' => $this->config->get('client.badge') ?? null,
                    ]
                ]);
            }

            if ($action !== 'draft') {
                $data['status'] = 'scheduled';
            }
            $result = $campaignObj->update($id, $data, $currentUserId);

            if ($result) {
                return $this->render('main/campaign', [
                    'title' => _e('campaign_edit'),
                    'success' => _e('success_to') . _e('update'),
                    'campaign' => array_merge($campaign, $data),
                    'isEdit' => true,
                    'segments' => $params['segments'] ?? null,
                    'listSegments' => $listSegments,
                    'client' => [
                        'icon' => $this->config->get('client.icon') ?? null,
                        'badge' => $this->config->get('client.badge') ?? null,
                    ]
                ]);
            } else {
                return $this->render('main/campaign', [
                    'title' => _e('campaign_edit'),
                    'error' => _e('error_failed_to') . _e('update'),
                    'campaign' => array_merge($campaign, $data),
                    'isEdit' => true,
                    'segments' => $params['segments'] ?? null,
                    'listSegments' => $listSegments,
                    'client' => [
                        'icon' => $this->config->get('client.icon') ?? null,
                        'badge' => $this->config->get('client.badge') ?? null,
                    ]
                ]);
            }
        } catch (Exception $e) {
            return $this->render('main/campaign', [
                'title' => _e('campaign_edit'),
                'error' => $e->getMessage(),
                'campaign' => array_merge($campaign, $data),
                'isEdit' => true,
                'segments' => $params['segments'] ?? null,
                'listSegments' => $listSegments,
                'client' => [
                    'icon' => $this->config->get('client.icon') ?? null,
                    'badge' => $this->config->get('client.badge') ?? null,
                ]
            ]);
        }
    }

    public function cancelCampaign(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $id = $args['id'] ?? null;
        if (!$id) {
            return new Response(302, ['Location' => '/campaigns']);
        }

        try {
            $campaignObj = new \alo\Campaign();
            $campaign = $campaignObj->get($id);

            if (!$campaign || $campaign['status'] !== 'scheduled') {
                return new Response(302, [
                    'Location' => '/campaigns?error=' . urlencode('error_not_allowed_cancel')
                ]);
            }

            $this->db->update('campaigns', [
                'status' => 'cancelled',
                'updated_at' => $this->db->sqleval('NOW()')
            ], 'id=%i', $campaign['id']);

            return new Response(302, [
                'Location' => '/campaigns?warning=' . urlencode('success_campaign_cancelled')
            ]);
        } catch (Exception $e) {
            return new Response(302, [
                'Location' => '/campaigns?error=' . urlencode($e->getMessage())
            ]);
        }
    }

    public function exportCampaigns(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $campaigns = $this->db->query("SELECT * FROM campaigns ORDER BY created_at DESC");

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'Name',
            'Push Title',
            'Push Body',
            'Status',
            'Success Count',
            'Error Count',
            'Unsub Count',
            'Created At',
            'Updated At'
        ];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }

        $row = 2;
        foreach ($campaigns as $campaign) {
            $sheet->setCellValue('A' . $row, $campaign['name']);
            $sheet->setCellValue('B' . $row, $campaign['push_title']);
            $sheet->setCellValue('C' . $row, $campaign['push_body']);
            $sheet->setCellValue('D' . $row, $campaign['status']);
            $sheet->setCellValue('E' . $row, $campaign['sent_success_count'] ?? 0);
            $sheet->setCellValue('F' . $row, $campaign['sent_error_count'] ?? 0);
            $sheet->setCellValue('G' . $row, $campaign['sent_unsub_count'] ?? 0);
            $sheet->setCellValue('H' . $row, $campaign['created_at']);
            $sheet->setCellValue('I' . $row, $campaign['updated_at']);
            $row++;
        }

        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E9ECEF']
            ]
        ];
        $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);
        $tempFile = tempnam(sys_get_temp_dir(), 'campaign_export_');

        $format = $args['format'];
        if ($format === 'csv') {
            $writer = new Csv($spreadsheet);
            $contentType = 'text/csv';
            $timestamp = date('Y-m-d_His');
            $filename = "campaigns_{$timestamp}.csv";
        } else {
            $writer = new Xlsx($spreadsheet);
            $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
            $timestamp = date('Y-m-d_His');
            $filename = "campaigns_{$timestamp}.xlsx";
        }
        $writer->save($tempFile);

        $content = file_get_contents($tempFile);
        unlink($tempFile);

        return new Response(
            200,
            [
                'Content-Type' => $contentType,
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'no-cache, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ],
            $content
        );
    }

    public function importMetadata(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getBody()->getContents();
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new Response(
                400,
                ['Content-Type' => 'application/json'],
                json_encode(['error' => _e('error_invalid_json'), 'details' => json_last_error_msg()])
            );
        }

        $url = $data['url'] ?? '';

        if (empty($url)) {
            return new Response(
                400,
                ['Content-Type' => 'application/json'],
                json_encode(['error' => _e('error_url_required')])
            );
        }

        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'ignore_errors' => true,
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ]);

            $html = false;
            $fetchMethods = [
                function () use ($url, $context) {
                    return file_get_contents($url, false, $context);
                },
                function () use ($url, $context) {
                    $ch = curl_init($url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
                    $result = curl_exec($ch);
                    $error = curl_error($ch);
                    curl_close($ch);

                    if ($error) {
                        return false;
                    }
                    return $result;
                }
            ];

            foreach ($fetchMethods as $method) {
                $html = $method();
                if ($html !== false) break;
            }

            if ($html === false) {
                $error = error_get_last();
                throw new Exception($error['message'] ?? 'Unknown error');
            }

            libxml_use_internal_errors(true);
            $doc = new \DOMDocument();
            $doc->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);

            $htmlErrors = libxml_get_errors();
            libxml_clear_errors();

            $getMeta = function ($properties) use ($doc) {
                foreach ($properties as $property) {
                    $tags = $doc->getElementsByTagName('meta');
                    foreach ($tags as $tag) {
                        if (!($tag instanceof \DOMElement)) {
                            continue;
                        }
                        if (
                            $tag->getAttribute('property') === $property ||
                            $tag->getAttribute('name') === $property
                        ) {
                            return $tag->getAttribute('content');
                        }
                    }
                }
                return null;
            };

            $titleTag = $doc->getElementsByTagName('title')->item(0);
            $title = $getMeta(['og:title', 'twitter:title']) ??
                ($titleTag instanceof \DOMElement ? trim($titleTag->textContent ?? '') : '');

            $description = $getMeta(['og:description', 'twitter:description', 'description']);

            $image = $getMeta(['og:image', 'twitter:image']);

            $icon = null;
            $icons = $doc->getElementsByTagName('link');
            foreach ($icons as $icon_tag) {
                if (!($icon_tag instanceof \DOMElement)) {
                    continue;
                }
                if (preg_match('/\b(icon|shortcut icon)\b/i', $icon_tag->getAttribute('rel'))) {
                    $icon = $icon_tag->getAttribute('href');
                    if ($icon && !preg_match('~^https?://~i', $icon)) {
                        $icon = rtrim(parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST), '/') . '/' . ltrim($icon, '/');
                    }
                    break;
                }
            }

            $metadata = [
                'title' => $title ?? '',
                'description' => $description ?? '',
                'image' => $image ?? '',
                'icon' => $icon ?? ''
            ];

            return new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode($metadata)
            );
        } catch (Exception $e) {
            return new Response(
                500,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'error' => _e('error_import_metadata'),
                    'details' => $e->getMessage()
                ])
            );
        }
    }

    public function deleteCampaign(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $id = $args['id'] ?? null;
        if (!$id) {
            return new Response(302, ['Location' => '/campaigns']);
        }

        try {
            $campaignObj = new \alo\Campaign();
            $result = $campaignObj->delete($id);

            $redirectParams = $result
                ? ['deleted' => 'success_campaign_deleted']
                : ['error' => 'error_not_allowed_delete'];

            return new Response(302, [
                'Location' => '/campaigns?' . http_build_query($redirectParams)
            ]);
        } catch (Exception $e) {
            return new Response(302, [
                'Location' => '/campaigns?error=' . urlencode($e->getMessage())
            ]);
        }
    }

    public function duplicateCampaign(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $id = $args['id'] ?? null;
        if (!$id) {
            return new Response(302, ['Location' => '/campaigns']);
        }

        try {
            $campaignObj = new \alo\Campaign();
            $campaign = $campaignObj->get($id);

            if (!$campaign) {
                return new Response(302, [
                    'Location' => '/campaigns?error=' . urlencode('error_campaign_not_found')
                ]);
            }

            $currentUserId = $this->getCurrentUserId();
            if ($currentUserId === null) {
                return new Response(302, [
                    'Location' => '/campaigns?error=' . urlencode('error_authentication_required')
                ]);
            }

            $newCampaignData = [
                'name' => $campaign['name'],
                'push_title' => $campaign['push_title'],
                'push_body' => $campaign['push_body'],
                'push_icon' => $campaign['push_icon'],
                'push_image' => $campaign['push_image'],
                'push_badge' => $campaign['push_badge'],
                'push_requireInteraction' => $campaign['push_requireInteraction'],
                'push_url' => $campaign['push_url'],
                'push_renotify' => $campaign['push_renotify'],
                'push_silent' => $campaign['push_silent'],
                'created_by' => $currentUserId,
                'segments' => !empty($campaign['segments']) ? json_decode($campaign['segments'], true) : null,
                'status' => 'draft'
            ];

            $result = $campaignObj->create($newCampaignData, $currentUserId);

            if ($result) {
                return new Response(302, [
                    'Location' => '/campaigns?success=' . urlencode('success_campaign_duplicated')
                ]);
            } else {
                return new Response(302, [
                    'Location' => '/campaigns?error=' . urlencode('error_failed_to_duplicate')
                ]);
            }
        } catch (Exception $e) {
            return new Response(302, [
                'Location' => '/campaigns?error=' . urlencode($e->getMessage())
            ]);
        }
    }

    public function apiCreateCampaign(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $user = $request->getAttribute('user');
            if (!$user) {
                return $this->createApiErrorResponse('Authentication required', 401);
            }

            $body = $request->getBody()->getContents();
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->createApiErrorResponse('Invalid JSON: ' . json_last_error_msg(), 400);
            }

            $requiredFields = ['name', 'push_title', 'push_body'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    return $this->createApiErrorResponse("Field '{$field}' is required", 400);
                }
            }

            $campaignData = [
                'name' => $data['name'],
                'push_title' => $data['push_title'],
                'push_body' => $data['push_body'],
                'push_icon' => $data['push_icon'] ?? null,
                'push_image' => $data['push_image'] ?? null,
                'push_url' => $data['push_url'] ?? null,
                'send_at' => isset($data['send_at']) ? date('Y-m-d H:i:s', strtotime($data['send_at'])) : null,
                'push_requireInteraction' => $data['push_requireInteraction'] ?? false,
                'push_badge' => $data['push_badge'] ?? null,
                'push_renotify' => $data['push_renotify'] ?? false,
                'push_silent' => $data['push_silent'] ?? false,
                'segments' => (isset($data['segments']) && is_array($data['segments']) && !empty(array_filter($data['segments'])))
                    ? array_filter($data['segments'])
                    : null,
                'status' => 'draft'
            ];

            $campaignObj = new \alo\Campaign();
            $result = $campaignObj->create($campaignData, $user['id']);

            if (!$result) {
                return $this->createApiErrorResponse('Failed to create campaign', 500);
            }

            return new Response(
                201,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'success' => true,
                    'message' => 'Campaign created successfully',
                    'campaign' => $result
                ])
            );
        } catch (Exception $e) {
            return $this->createApiErrorResponse('Error: ' . $e->getMessage(), 500);
        }
    }

    private function createApiErrorResponse(string $message, int $statusCode = 400): ResponseInterface
    {
        return new Response(
            $statusCode,
            ['Content-Type' => 'application/json'],
            json_encode(['error' => $message])
        );
    }

    public function batchScheduleCampaigns(ServerRequestInterface $request): ResponseInterface
    {
        $params = (array)$request->getParsedBody();
        $campaignIds = $params['campaign_ids'] ?? [];
        $campaignOrders = $params['campaign_order'] ?? [];
        $startDateTime = $params['start_datetime'] ?? null;
        $timeInterval = (int)($params['time_interval'] ?? 15);
        
        if (empty($campaignIds)) {
            return new Response(302, [
                'Location' => '/campaigns?error=' . urlencode(_e('error_no_campaigns_selected'))
            ]);
        }
        
        if (empty($startDateTime)) {
            return new Response(302, [
                'Location' => '/campaigns?error=' . urlencode(_e('error_start_datetime_required'))
            ]);
        }
        
        try {
            $currentUserId = $this->getCurrentUserId();
            if ($currentUserId === null) {
                return new Response(302, [
                    'Location' => '/campaigns?error=' . urlencode('error_authentication_required')
                ]);
            }
            
            $startTimestamp = strtotime($startDateTime);
            if ($startTimestamp === false) {
                return new Response(302, [
                    'Location' => '/campaigns?error=' . urlencode(_e('error_invalid_date_format'))
                ]);
            }
            
            $campaignObj = new \alo\Campaign();
            
            $orderedCampaigns = [];
            foreach ($campaignIds as $campaignId) {
                $order = isset($campaignOrders[$campaignId]) && !empty($campaignOrders[$campaignId])
                    ? (int)$campaignOrders[$campaignId]
                    : 999;
                $orderedCampaigns[] = [
                    'id' => $campaignId,
                    'order' => $order
                ];
            }
            
            usort($orderedCampaigns, function($a, $b) {
                return $a['order'] - $b['order'];
            });
            
            $successCount = 0;
            foreach ($orderedCampaigns as $index => $campaign) {
                $campaignId = $campaign['id'];
                
                $scheduledTimestamp = $startTimestamp + ($index * $timeInterval * 60);
                $scheduledDateTime = date('Y-m-d H:i:s', $scheduledTimestamp);

                $updateData = [
                    'status' => 'scheduled',
                    'send_at' => $scheduledDateTime
                ];
                
                $result = $campaignObj->update($campaignId, $updateData, $currentUserId);
                if ($result) {
                    $successCount++;
                }
            }
            
            if ($successCount > 0) {
                return new Response(302, [
                    'Location' => '/campaigns?success=' . urlencode(_e('success_scheduled_campaigns'))
                ]);
            } else {
                return new Response(302, [
                    'Location' => '/campaigns?error=' . urlencode(_e('error_failed_to_schedule'))
                ]);
            }
        } catch (Exception $e) {
            return new Response(302, [
                'Location' => '/campaigns?error=' . urlencode($e->getMessage())
            ]);
        }
    }
}
