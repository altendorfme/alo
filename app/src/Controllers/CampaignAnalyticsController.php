<?php

namespace alo\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use alo\Database\Database;
use alo\Analytics\CampaignsAnalytics;
use Nyholm\Psr7\Response;

class CampaignAnalyticsController extends BaseController
{
    protected $db;
    protected $analyticsManager;
    public function __construct(ContainerInterface $container)
    {
        parent::__construct();
        $this->db = Database::getInstance();
        $this->analyticsManager = new CampaignsAnalytics();
    }

    public function campaignAnalytics(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $campaignId = $args['id'] ?? null;

        if (!$campaignId) {
            return new Response(302, ['Location' => '/campaigns']);
        }

        try {
            $campaign = $this->db->query(
                "SELECT 
                    c.*, 
                    cu.email AS created_by_email, 
                    uu.email AS updated_by_email 
                FROM campaigns c
                LEFT JOIN users cu ON c.created_by = cu.id
                LEFT JOIN users uu ON c.updated_by = uu.id
                WHERE c.id = %i",
                $campaignId
            );
        } catch (\Exception $e) {
            return $this->render('main/campaigns', [
                'error' => _e('error_campaign_details_retrieval')
            ]);
        }

        if (empty($campaign)) {
            return $this->render('main/campaigns', [
                'error' => _e('error_campaign_not_found')
            ]);
        }

        $campaign = $campaign[0];

        try {
            $interactionTimeline = $this->db->query(
                "SELECT
                    DATE_FORMAT(hour, '%Y-%m-%d %H:00') AS datetime,
                    interaction_type,
                    count
                FROM
                    analytics_campaign
                WHERE
                    campaign_id = %i
                ORDER BY
                    hour",
                $campaignId
            );
        } catch (\Exception $e) {
            $interactionTimeline = [];
        }

        $labels = [];
        $datasets = [
            'sent' => [],
            'delivered' => [],
            'clicked' => [],
            'failed' => []
        ];

        $uniqueDatetimes = $interactionTimeline ? array_unique(array_column($interactionTimeline, 'datetime')) : [];
        sort($uniqueDatetimes);
        $labels = $uniqueDatetimes;

        foreach ($uniqueDatetimes as $datetime) {
            foreach (['sent', 'delivered', 'clicked', 'failed'] as $type) {
                $found = false;
                foreach ($interactionTimeline as $row) {
                    if ($row['datetime'] == $datetime && $row['interaction_type'] == $type) {
                        $datasets[$type][] = (int)$row['count'];
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $datasets[$type][] = 0;
                }
            }
        }

        try {
            $results = $this->db->query(
                "SELECT
                    c.total_recipients,
                    COALESCE(SUM(CASE WHEN ac.interaction_type = 'failed' THEN ac.count ELSE 0 END), 0) AS error_count,
                    COALESCE(SUM(CASE WHEN ac.interaction_type = 'delivered' THEN ac.count ELSE 0 END), 0) AS successfully_count,
                    COALESCE(SUM(CASE WHEN ac.interaction_type = 'clicked' THEN ac.count ELSE 0 END), 0) AS clicked_count
                FROM
                    campaigns c
                LEFT JOIN
                    analytics_campaign ac ON c.id = ac.campaign_id
                WHERE
                    c.id = %i
                GROUP BY
                    c.id, c.total_recipients",
                $campaignId
            );
        } catch (\Exception $e) {
            $results = [
                ['total_recipients' => 0, 'error_count' => 0, 'successfully_count' => 0, 'clicked_count' => 0]
            ];
        }

        return $this->render('main/campaign/analytics', [
            'campaign' => $campaign,
            'interactionTimeline' => [
                'labels' => $labels,
                'datasets' => $datasets
            ],
            'results' => $results[0] ?? [
                'total_recipients' => 0,
                'error_count' => 0,
                'successfully_count' => 0,
                'clicked_count' => 0
            ]
        ]);
    }
}
