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

    public function viewUsers(ServerRequestInterface $request, array $args = []): ResponseInterface
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

    public function viewUserCreate(ServerRequestInterface $request): ResponseInterface
    {
        return $this->render('main/user', [
            'title' => _e('user_create'),
            'isEdit' => false,
        ]);
    }

    public function viewUserEdit(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $id = $args['id'] ?? null;

        $userData = $this->db->queryFirstRow(
            "SELECT * FROM users WHERE id = %d",
            $id
        );

        if (!$userData) {
            return new Response(404, ['Location' => "/users?error=error_user_not_found"]);
        }

        $queryParams = $request->getQueryParams();
        $successMessage = null;
        $errorMessage = null;
        $token = null;

        if (isset($queryParams['success'])) {
            $successMessage = _e($queryParams['success']);
            if (isset($queryParams['token'])) {
                $token = $queryParams['token'];
                $successMessage .= '<b>' . htmlspecialchars($token) . '</b>';
            }
        }

        if (isset($queryParams['error'])) {
            $errorMessage = _e($queryParams['error']);
        }

        return $this->render('main/user', [
            'title' => _e('user_edit'),
            'isEdit' => true,
            'userData' => $userData,
            'success' => $successMessage,
            'error' => $errorMessage,
        ]);
    }

    public function createUser(ServerRequestInterface $request): ResponseInterface
    {
        $postData = $request->getParsedBody();

        if (!isset($postData['email']) || !isset($postData['role'])) {
            return $this->render('main/user', [
                'title' => _e('user_create'),
                'error' => _e('error_missing_required_fields'),
                'isEdit' => false
            ]);
        }

        $password = PasswordService::generateSecurePassword();

        $userData = [
            'email' => $postData['email'],
            'role' => $postData['role'],
            'status' => $postData['status'] ?? 'active',
            'password' => password_hash($password, PASSWORD_DEFAULT)
        ];

        try {
            if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
                return $this->render('main/user', [
                    'title' => _e('user_create'),
                    'error' => _e('error_invalid_email_format'),
                    'isEdit' => false
                ]);
            }

            $existingUser = $this->db->queryFirstRow(
                "SELECT id FROM users WHERE email = %s",
                $userData['email']
            );

            if ($existingUser) {
                return $this->render('main/user', [
                    'title' => _e('user_create'),
                    'error' => _e('error_email_already_exists'),
                    'isEdit' => false
                ]);
            }

            $id = $this->db->insert('users', $userData);

            try {
                $emailController = new EmailController($this->container);
                $emailController->sendWelcomeEmail($userData['email'], $password);
            } catch (Exception $e) {
                error_log("Failed to send welcome email: " . $e->getMessage());
            }

            $userData['id'] = $id;

            return new Response(302, [
                'Location' => '/users?success=' . urlencode('success_user_created')
            ]);
        } catch (Exception $e) {
            return $this->render('main/user', [
                'title' => _e('user_create'),
                'error' => _e('error_saving_user'),
                'isEdit' => false
            ]);
        }
    }

    public function updateUser(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $id = $args['id'] ?? null;
        if ($id == 1) {
            return new Response(302, ['Location' => '/users']);
        }

        $userData = $this->db->queryFirstRow(
            "SELECT * FROM users WHERE id = %d",
            $id
        );

        if (!$userData) {
            return new Response(404, ['Location' => "/users?error=error_user_not_found"]);
        }

        $postData = $request->getParsedBody();

        if (!isset($postData['email']) || !isset($postData['role'])) {
            return $this->render('main/user', [
                'title' => _e('user_edit'),
                'error' => _e('error_missing_required_fields'),
                'isEdit' => true,
                'userData' => $userData
            ]);
        }

        $updateData = [
            'email' => $postData['email'],
            'role' => $postData['role'],
            'status' => $postData['status'] ?? $userData['status'],
        ];

        if (isset($postData['status']) && $postData['status'] === 'inactive' && $userData['status'] !== 'inactive') {
            $updateData['api_key'] = null;
        }

        try {
            if (!filter_var($updateData['email'], FILTER_VALIDATE_EMAIL)) {
                return $this->render('main/user', [
                    'title' => _e('user_edit'),
                    'error' => _e('error_invalid_email_format'),
                    'isEdit' => true,
                    'userData' => $userData
                ]);
            }

            $existingUser = $this->db->queryFirstRow(
                "SELECT id FROM users WHERE email = %s AND id != %d",
                $updateData['email'],
                $id
            );

            if ($existingUser) {
                return $this->render('main/user', [
                    'title' => _e('user_edit'),
                    'error' => _e('error_email_already_exists'),
                    'isEdit' => true,
                    'userData' => $userData
                ]);
            }

            $this->db->update('users', $updateData, 'id=%d', $id);
            $updatedUserData = $this->db->queryFirstRow(
                "SELECT * FROM users WHERE id = %d",
                $id
            );

            return $this->render('main/user', [
                'title' => _e('user_edit'),
                'isEdit' => true,
                'userData' => $updatedUserData,
                'success' => _e('success_user_updated')
            ]);
        } catch (Exception $e) {
            return $this->render('main/user', [
                'title' => _e('user_edit'),
                'error' => _e('error_saving_user'),
                'isEdit' => true,
                'userData' => $userData
            ]);
        }
    }

    public function generateApiKey(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $id = $args['id'] ?? null;

        $userData = $this->db->queryFirstRow(
            "SELECT * FROM users WHERE id = %d",
            $id
        );

        if (!$userData) {
            return new Response(404, ['Location' => "/users?error=error_user_not_found"]);
        }

        $postData = $request->getParsedBody();
        
        if (!isset($postData['generateApiKey']) || $postData['generateApiKey'] !== 'true') {
            return new Response(302, [
                'Location' => "/user/edit/{$id}?error=error_invalid_request"
            ]);
        }

        try {
            $bearerToken = bin2hex(random_bytes(32));
            $this->db->update('users', ['api_key' => $bearerToken], 'id=%d', $id);

            return new Response(302, [
                'Location' => "/user/edit/{$id}?success=success_token_generated"
            ]);
        } catch (Exception $e) {
            return new Response(302, [
                'Location' => "/user/edit/{$id}?error=error_token_generation_failed"
            ]);
        }
    }
}
