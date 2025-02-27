<?php

namespace Pushbase\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use Pushbase\Auth;
use Pushbase\Database\Database;
use Pushbase\Config\Config;
use MeekroDB;
use Exception;
use Nyholm\Psr7\Response;
use Pushbase\Utilities\PasswordService;

class UserController extends BaseController
{
    protected MeekroDB $db;
    protected $auth;
    protected Config $config;
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct();
        $this->container = $container;
        $this->db = Database::getInstance();
        $this->auth = Auth::getInstance();
        $this->config = $container->get(Config::class);
    }

    public function users(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $currentPage = isset($args['page']) ? (int)$args['page'] : 1;
        if ($currentPage < 1) $currentPage = 1;

        $queryParams = $request->getQueryParams();
        $statusFilter = $queryParams['status'] ?? null;
        $roleFilter = $queryParams['role'] ?? null;

        $perPage = 20;
        $offset = ($currentPage - 1) * $perPage;

        $conditions = [];
        $params = [];
        if ($statusFilter) {
            $conditions[] = "status = %s";
            $params[] = $statusFilter;
        }
        if ($roleFilter) {
            $conditions[] = "role = %s";
            $params[] = $roleFilter;
        }

        $whereClause = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

        $totalCount = $params
            ? $this->db->queryFirstField("SELECT COUNT(*) FROM users " . $whereClause, ...$params)
            : $this->db->queryFirstField("SELECT COUNT(*) FROM users");

        $totalPages = ceil($totalCount / $perPage);
        if ($currentPage > $totalPages && $totalPages > 0) {
            return new Response(302, ['Location' => '/users/page/' . $totalPages]);
        }

        $users = $params
            ? $this->db->query(
                "SELECT * FROM users " .
                    $whereClause .
                    " ORDER BY id DESC LIMIT %i, %i",
                ...[...$params, $offset, $perPage]
            )
            : $this->db->query(
                "SELECT * FROM users ORDER BY id DESC LIMIT %i, %i",
                $offset,
                $perPage
            );

        $data = [
            'users' => $users ?: [],
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'totalCount' => $totalCount,
            'statusFilter' => $statusFilter,
            'roleFilter' => $roleFilter
        ];

        return $this->render('main/users', $data);
    }

    public function user(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $id = $args['id'] ?? null;
        if ($id == 1) {
            return new Response(302, ['Location' => '/users']);
        }

        $isEdit = $id !== null;
        $userData = null;

        $userData = $this->db->queryFirstRow(
            "SELECT * FROM users WHERE id = %d",
            $id
        );

        if (!$userData) {
            return new Response(404, ['Location' => "/users?error=error_user_not_found"]);
        }

        if ($request->getMethod() === 'GET') {
            return $this->render('main/user', [
                'title' => $isEdit ? _e('user_edit') : _e('user_create'),
                'isEdit' => $isEdit,
                'userData' => $userData,
            ]);
        }

        if ($request->getMethod() === 'POST') {
            $postData = $request->getParsedBody();
            $isEdit = $id !== null;
            if (isset($postData['generateApiKey']) && $postData['generateApiKey'] == true) {
                try {
                    $bearerToken = bin2hex(random_bytes(32));
                    $this->db->update('users', ['api_key' => $bearerToken], 'id=%d', $id);

                    $userData = $this->db->queryFirstRow(
                        "SELECT * FROM users WHERE id = %d",
                        $id
                    );

                    return $this->render('main/user', [
                        'success' => _e('success_token_generated') . '<b>' . $bearerToken . '</b>',
                        'title' => $isEdit ? _e('user_edit') : _e('user_create'),
                        'isEdit' => $isEdit,
                        'userData' => $userData,
                    ]);
                } catch (Exception $e) {
                    return $this->render('main/user', [
                        'error' => _e('error_token_generation_failed'),
                        'title' => $isEdit ? _e('user_edit') : _e('user_create'),
                        'isEdit' => $isEdit,
                        'userData' => $userData,
                    ]);
                }
            }

            if (!isset($postData['email']) || !isset($postData['role'])) {
                return $this->render('main/user', [
                    'title' => $isEdit ? _e('user_edit') : _e('user_create'),
                    'error' => _e('error_missing_required_fields'),
                    'isEdit' => $isEdit
                ]);
            }

            $password = $isEdit ? null : PasswordService::generateSecurePassword();

            $updateData = [
                'email' => $postData['email'],
                'role' => $postData['role'],
                'status' => $postData['status'] ?? ($isEdit ? null : 'active'),
            ];

            if (!$isEdit) {
                $updateData['password'] = password_hash($password, PASSWORD_DEFAULT);
            }

            try {
                if (!filter_var($updateData['email'], FILTER_VALIDATE_EMAIL)) {
                    return $this->render('main/user', [
                        'title' => $isEdit ? _e('user_edit') : _e('user_create'),
                        'error' => _e('error_invalid_email_format'),
                        'isEdit' => $isEdit
                    ]);
                }

                $existingUser = $this->db->queryFirstRow(
                    "SELECT id FROM users WHERE email = %s AND id != %d",
                    $updateData['email'],
                    $isEdit ? $id : 0
                );

                if ($existingUser) {
                    return $this->render('main/user', [
                        'title' => $isEdit ? _e('user_edit') : _e('user_create'),
                        'error' => _e('error_email_already_exists'),
                        'isEdit' => $isEdit
                    ]);
                }

                if ($isEdit) {
                    $this->db->update('users', $updateData, 'id=%d', $id);
                    $userData = $this->db->queryFirstRow(
                        "SELECT * FROM users WHERE id = %d",
                        $id
                    );
                } else {
                    $id = $this->db->insert('users', $updateData);

                    try {
                        $emailController = new EmailController($this->container);
                        $emailController->sendWelcomeEmail($updateData['email'], $password);
                    } catch (Exception $e) {
                        error_log("Failed to send welcome email: " . $e->getMessage());
                    }

                    $userData = $updateData;
                    $userData['id'] = $id;
                }

                return $this->render('main/user', [
                    'title' => $isEdit ? _e('user_edit') : _e('user_create'),
                    'isEdit' => $isEdit,
                    'userData' => $userData,
                ], 302);
            } catch (Exception $e) {
                return $this->render('main/user', [
                    'title' => $isEdit ? _e('user_edit') : _e('user_create'),
                    'error' => _e('error_saving_user'),
                    'isEdit' => $isEdit
                ]);
            }
        }
    }
}
