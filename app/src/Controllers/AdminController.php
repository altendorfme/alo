<?php

namespace alo\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use alo\Database\Database;
use alo\Config\Config;
use MeekroDB;
use Nyholm\Psr7\Response;

class AdminController extends BaseController
{
    protected MeekroDB $db;
    protected Config $config;
    public function __construct(ContainerInterface $container)
    {
        parent::__construct();
        $this->db = Database::getInstance();
        $this->config = $container->get(Config::class);
    }

    public function dashboard(ServerRequestInterface $request): ResponseInterface
    {
        $subscribers_trend = [];
        try {
            $earliestDataQuery = "
                SELECT
                    MIN(DATE(created_at)) as earliest_date
                FROM
                    analytics_subscribers
            ";
            
            $earliestDate = $this->db->queryFirstField($earliestDataQuery);
            if ($earliestDate) {
                $startDate = date('Y-m-d', strtotime("-30 days"));

                if ($earliestDate > $startDate) {
                    $startDate = $earliestDate;
                }

                $query = "
                    SELECT
                        DATE(created_at) as date,
                        status,
                        COUNT(*) as count
                    FROM
                        analytics_subscribers
                    WHERE
                        created_at >= %s
                    GROUP BY
                        DATE(created_at), status
                    ORDER BY
                        date ASC
                ";
                
                $results = $this->db->query($query, $startDate);

                $dates = [];
                $data = [];
                
                $currentDate = new \DateTime($startDate);
                $today = new \DateTime();
                
                while ($currentDate <= $today) {
                    $dateStr = $currentDate->format('Y-m-d');
                    $dates[] = ['date' => $dateStr];
                    $data[$dateStr] = [
                        'active' => 0,
                        'inactive' => 0,
                        'unsubscribed' => 0
                    ];
                    $currentDate->modify('+1 day');
                }

                foreach ($results as $row) {
                    $date = $row['date'];
                    $status = $row['status'];
                    $count = (int)$row['count'];
                    
                    if (isset($data[$date])) {
                        $data[$date][$status] = $count;
                    }
                }

                $chartData = [];
                foreach ($data as $date => $statusCounts) {
                    $chartData[] = $statusCounts;
                }
                
                $subscribers_trend = [
                    'dates' => $dates,
                    'data' => $chartData
                ];
            } else {
                $subscribers_trend = [
                    'dates' => [],
                    'data' => []
                ];
            }
        } catch (\Exception $e) {
            $subscribers_trend = [
                'dates' => [],
                'data' => []
            ];
        }

        $dashboard = [
            'subscribers' => [
                'active' => $this->db->queryFirstRow("SELECT COUNT(*) as count FROM subscribers WHERE status = 'active'")['count'],
                'status' => [
                    'total' => $this->db->queryFirstRow("SELECT COUNT(*) as count FROM subscribers")['count'],
                    'inactive' => $this->db->queryFirstRow("SELECT COUNT(*) as count FROM subscribers WHERE status = 'inactive'")['count'],
                    'unsubscribed' => $this->db->queryFirstRow("SELECT COUNT(*) as count FROM subscribers WHERE status = 'unsubscribed'")['count']
                ]
            ],
            'campaigns' => [
                'total' => $this->db->queryFirstRow("SELECT COUNT(*) as count FROM campaigns")['count'],
                'status' => [
                    'draft' => $this->db->queryFirstRow("SELECT COUNT(*) as count FROM campaigns WHERE status = 'draft'")['count'],
                    'scheduled' => $this->db->queryFirstRow("SELECT COUNT(*) as count FROM campaigns WHERE status = 'scheduled'")['count'],
                    'sent' => $this->db->queryFirstRow("SELECT COUNT(*) as count FROM campaigns WHERE status = 'sent'")['count'],
                    'sending' => $this->db->queryFirstRow("SELECT COUNT(*) as count FROM campaigns WHERE status = 'sending'")['count'],
                    'cancelled' => $this->db->queryFirstRow("SELECT COUNT(*) as count FROM campaigns WHERE status = 'cancelled'")['count']
                ],
                'recent' => $this->db->query("SELECT * FROM campaigns WHERE `status` = 'sent' ORDER BY `ended_at` DESC LIMIT 10;")
            ],
            'subscribers_trend' => $subscribers_trend
        ];

        return $this->render('main/dashboard', $dashboard);
    }
}
