<?php

namespace Pushbase\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use Pushbase\Database\Database;
use Pushbase\Config\Config;
use Pushbase\Auth;
use Nyholm\Psr7\Response;

class AuthController extends BaseController
{
    protected $auth;
    protected $db;
    protected $emailController;
    protected $config;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct();
        $this->db = Database::getInstance();
        $this->auth = Auth::getInstance();
        $this->config = $container->get(Config::class);
        $this->emailController = new EmailController($container);
    }

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        // Render the index page
        return $this->render('index/index');
    }

    public function login(ServerRequestInterface $request): ResponseInterface
    {

        if ($request->getMethod() === 'GET') {
            // Check if user is already authenticated
            if ($this->auth->isAuthenticated()) {
                // Redirect to dashboard if already logged in
                return new Response(
                    302,
                    ['Location' => '/dashboard']
                );
            }
            return $this->render('login/login');
        }

        if ($request->getMethod() === 'POST') {
            $parsedBody = $request->getParsedBody();
            $email = $parsedBody['email'] ?? null;
            $password = $parsedBody['password'] ?? null;
            $rememberMe = isset($parsedBody['remember_me']);

            if (!$email || !$password) {
                return $this->render('login/login', [
                    'error' => _e('error_email_password_required')
                ]);
            }

            // Attempt login
            if ($this->auth->login($email, $password, $rememberMe)) {
                $this->auth->setSessionCookie();
                return new Response(
                    302,
                    ['Location' => '/dashboard']
                );
            }

            // Login failed
            return $this->render('login/login', [
                'error' => _e('error_authentication_failed')
            ]);
        }
    }

    public function logout(ServerRequestInterface $request): ResponseInterface
    {
        // Get session code from cookies
        $cookies = $request->getCookieParams();
        $sessionCode = $cookies['session'] ?? null;

        // Check if session code exists and invalidate session
        if ($sessionCode) {
            $this->db->query(
                "UPDATE user_sessions SET status = 'expired' WHERE session_code = %s",
                $sessionCode
            );
        }

        // Redirect to login page after logout
        return new Response(
            302,
            [
                'Location' => '/login',
                'Set-Cookie' => 'session=; Path=/; Expires=Thu, 01 Jan 1970 00:00:00 GMT'
            ]
        );
    }

    private function generatePasswordResetToken(string $email): string
    {
        $token = bin2hex(random_bytes(32));

        $this->db->query(
            "UPDATE users SET password_reset_token = %s, password_reset_expires = %s WHERE email = %s",
            password_hash($token, PASSWORD_DEFAULT),
            date('Y-m-d H:i:s', strtotime('+30 minutes')),
            $email
        );

        return $token;
    }

    public function forgotPassword(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() === 'POST') {
            $parsedBody = $request->getParsedBody();
            $email = $parsedBody['email'] ?? null;

            if ($email === null || $email === '') {
                return $this->render('login/forgot_password', [
                    'templateBase' => 'login',
                    'bodyClasses' => 'd-flex flex-column vh-100',
                    'error' => _e('error_email_blank')
                ]);
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->render('login/forgot_password', [
                    'error' => _e('error_invalid_email_format_auth')
                ]);
            }

            // Check if email exists in the database
            $user = $this->db->query(
                "SELECT * FROM users WHERE email = %s",
                $email
            );

            if (!empty($user)) {
                $resetToken = $this->generatePasswordResetToken($email);

                $this->emailController->sendPasswordResetEmail($email, $resetToken);

                return $this->render('login/login', [
                    'message' => _e('success_password_reset_link_sent')
                ]);
            } else {
                // Email not found
                return $this->render('login/forgot_password', [
                    'error' => _e('error_email_not_found')
                ]);
            }
        }

        return $this->render('login/forgot_password');
    }

    public function resetPassword(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $token = $queryParams['token'] ?? null;
        $error = $queryParams['error'] ?? null;

        if (!$token) {
            return new Response(
                302,
                ['Location' => '/login']
            );
        }

        if ($request->getMethod() === 'POST') {
            $parsedBody = $request->getParsedBody();
            $newPassword = $parsedBody['password'] ?? null;
            $confirmPassword = $parsedBody['confirm_password'] ?? null;

            if ($newPassword !== $confirmPassword) {
                return $this->render('login/reset_password', [
                    'error' => _e('error_passwords_not_match'),
                    'token' => $token,
                ]);
            }

            $resetRequest = $this->db->query(
                "SELECT * FROM users WHERE password_reset_token IS NOT NULL AND password_reset_expires > NOW()"
            );

            if (!empty($resetRequest)) {
                $resetRequest = $resetRequest[0];
                if (password_verify($token, $resetRequest['password_reset_token'])) {
                    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
                    $this->db->query(
                        "UPDATE users SET password = %s, password_reset_token = NULL, password_reset_expires = NULL WHERE email = %s",
                        $hashedPassword,
                        $resetRequest['email']
                    );

                    return $this->render('login/login', [
                        'message' => _e('success_password_reset')
                    ]);
                }
            }

            return new Response(
                302,
                ['Location' => '/login']
            );
        }

        $resetRequest = $this->db->query(
            "SELECT * FROM users WHERE password_reset_token IS NOT NULL AND password_reset_expires > NOW()"
        );

        if (empty($resetRequest) || !password_verify($token, $resetRequest[0]['password_reset_token'])) {
            return $this->render('login/login', [
                'error' => _e('error_invalid_token')
            ]);
        }

        return $this->render('login/reset_password', [
            'token' => $token,
            'error' => $error
        ]);
    }
}
