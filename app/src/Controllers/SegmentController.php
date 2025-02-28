<?php

namespace Pushbase\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Nyholm\Psr7\Response;
use Pushbase\Database\Database;
use Pushbase\Auth;
use Pushbase\Config\Config;
use Pushbase\Utilities\PaginationHelper;
use Psr\Container\ContainerInterface;
use MeekroDB;

class SegmentController extends BaseController
{
    protected MeekroDB $db;
    protected Config $config;
    public function __construct(ContainerInterface $container)
    {
        parent::__construct();
        $this->db = Database::getInstance();
        $this->config = $container->get(Config::class);
    }

    public function viewSegments(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $page = isset($args['page']) ? (int)$args['page'] : 1;
        $page = max(1, $page);
        $itemsPerPage = 10;
        $offset = ($page - 1) * $itemsPerPage;

        $totalQuery = "SELECT COUNT(*) FROM segments";
        $totalSegments = $this->db->queryFirstField($totalQuery);
        $totalPages = ceil($totalSegments / $itemsPerPage);

        $query = "SELECT 
                    s.id, 
                    s.name, 
                    s.description
                   FROM segments s
                   LIMIT %i OFFSET %i";

        $segments = $this->db->query($query, $itemsPerPage, $offset);

        return $this->render('main/segments', [
            'segments' => $segments,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalSegments' => $totalSegments
        ]);
    }

    /**
     * View segment details (GET method)
     */
    public function viewSegment(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $id = $args['id'] ?? null;

        $segment = $this->db->queryFirstRow("SELECT * FROM segments WHERE id = %i", $id);
        if (!$segment) {
            return new Response(404, [], 'Segment not found');
        }

        return $this->render('main/segment', [
            'segment' => $segment
        ]);
    }

    /**
     * Update segment (POST method)
     */
    public function updateSegment(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $id = $args['id'] ?? null;

        $segment = $this->db->queryFirstRow("SELECT * FROM segments WHERE id = %i", $id);
        if (!$segment) {
            return new Response(404, [], 'Segment not found');
        }

        $postData = $request->getParsedBody();

        $this->db->update('segments', [
            'description' => $postData['description']
        ], "id=%i", $id);
        
        // Fetch the updated segment data after the update
        $updatedSegment = $this->db->queryFirstRow("SELECT * FROM segments WHERE id = %i", $id);

        return $this->render('main/segment', [
            'segment' => $updatedSegment,
            'success' => _e('success_segment_edited')
        ]);
    }

    public function getSegments(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $segment = $this->db->queryFirstRow("SELECT * FROM segments WHERE id = %i", $args['id']);
        if (!$segment) {
            return new Response(
                404,
                ['Content-Type' => 'application/json'],
                json_encode(['error' => 'Segment not found'])
            );
        }

        $values = $this->db->query(
            "SELECT DISTINCT value
             FROM segment_goals
             WHERE segment_id = %i
             ORDER BY value",
            $segment['id']
        );

        $valueList = array_map(function ($item) {
            return $item['value'];
        }, $values);

        return new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode($valueList)
        );
    }

    public function subscribersBySegments(ServerRequestInterface $request): ResponseInterface
    {
        $data = json_decode($request->getBody()->getContents(), true);

        if (empty($data)) {
            $userCount = $this->db->queryFirstField("SELECT COUNT(DISTINCT id) FROM subscribers");
            return new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode(['count' => $userCount])
            );
        }

        $query = "SELECT COUNT(DISTINCT s.id) as user_count
                    FROM subscribers s
                    INNER JOIN segment_goals sg ON s.id = sg.subscriber_id
                    WHERE 1=1";

        $queryParams = [];
        $conditions = [];

        foreach ($data as $segment) {
            if (!isset($segment['segmentId']) || !isset($segment['values']) || empty($segment['values'])) {
                continue;
            }

            $values = is_array($segment['values']) ? $segment['values'] : [$segment['values']];
            $placeholders = implode(',', array_fill(0, count($values), '%s'));

            $segmentCondition = "(sg.segment_id = %i AND sg.value IN ($placeholders))";
            $conditions[] = $segmentCondition;

            $queryParams[] = $segment['segmentId'];
            $queryParams = array_merge($queryParams, $values);
        }

        if (!empty($conditions)) {
            $query .= " AND (" . implode(" OR ", $conditions) . ")";
        }

        $userCount = $this->db->queryFirstField($query, ...$queryParams);

        return new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode(['count' => $userCount])
        );
    }
}
