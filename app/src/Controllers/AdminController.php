<?php

namespace Pushbase\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use Pushbase\Database\Database;
use Pushbase\Config\Config;
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
        // Segment analytics query function
        $getSegmentData = function ($segmentType) {
            $query = "
                SELECT 
                    a.segment_value as name, 
                    a.count 
                FROM 
                    segments s
                JOIN 
                    analytics_segments a ON s.id = a.segment_id
                WHERE 
                    s.name = %s
                ORDER BY 
                    a.count DESC
                LIMIT 5
            ";

            $results = $this->db->query($query, $segmentType);

            $labels = array_column($results, 'name');
            $data = array_column($results, 'count');

            return [
                'labels' => $labels ?: [],
                'data' => $data ?: []
            ];
        };

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
            'segments' => [
                'browser_name' => $getSegmentData('browser_name'),
                'os_name' => $getSegmentData('os_name'),
                'device_type' => $getSegmentData('device_type'),
                'category' => $getSegmentData('category')
            ]
        ];

        return $this->render('main/dashboard', $dashboard);
    }
}
