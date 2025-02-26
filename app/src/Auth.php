<?php

namespace Pushbase;

use MeekroDB;
use Pushbase\Database\Database;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class Auth
{
    private static $instance = null;
    private $db;
    private $user = null;
    private $sessionCode = null;
    private $cookieExpiration = null;
    private $authErrorMessage = null;
    private const DEFAULT_SESSION_DURATION = 7;
    private const EXTENDED_SESSION_DURATION = 30;

    private function __construct()
    {
        $this->db = Database::getInstance();
    }

    public static function getInstance(): Auth
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function login(string $email, string $password, bool $rememberMe = false): bool
    {
        try {
            $user = $this->db->queryFirstRow(
                "SELECT * FROM users WHERE email = %s",
                $email
            );

            if (!$user || !password_verify($password, $user['password'])) {
                $this->authErrorMessage = "Invalid credentials";
                return false;
            }

            if ($user['status'] !== 'active') {
                $this->authErrorMessage = "User account is not active";
                return false;
            }

            $this->sessionCode = bin2hex(random_bytes(32));

            $sessionDays = $rememberMe
                ? self::EXTENDED_SESSION_DURATION
                : self::DEFAULT_SESSION_DURATION;

            $expiresAt = date('Y-m-d H:i:s', strtotime("+{$sessionDays} days"));

            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            if (!$ipAddress || $ipAddress === '127.0.0.1' || $ipAddress === '::1' || $ipAddress === 'localhost') {
                try {
                    $ipAddress = trim(file_get_contents('https://ifconfig.me/ip'));
                } catch (\Exception $e) {
                    $ipAddress = '127.0.0.1';
                }
            }

            $this->db->insert('user_sessions', [
                'user_id' => $user['id'],
                'session_code' => $this->sessionCode,
                'created_at' => $this->db->sqleval('NOW()'),
                'expires_at' => $expiresAt,
                'ip_address' => $ipAddress,
                'status' => 'active'
            ]);

            $this->db->update(
                'users',
                ['last_login' => $this->db->sqleval('NOW()')],
                'id=%i',
                $user['id']
            );

            $this->cookieExpiration = strtotime($expiresAt);

            $this->user = $user;
            $this->authErrorMessage = null;
            return true;
        } catch (\Exception $e) {
            $this->authErrorMessage = "Authentication error: " . $e->getMessage();
            return false;
        }
    }

    public function setSessionCookie(): void
    {
        if (!$this->sessionCode || !$this->cookieExpiration) {
            return;
        }

        setcookie(
            "session",
            $this->sessionCode,
            [
                'expires' => $this->cookieExpiration,
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Strict',
                'secure' => isset($_SERVER['HTTPS'])
            ]
        );
    }

    private function validateSession(?string $sessionCode = null): array|false
    {
        $sessionCode = $sessionCode ?? $_COOKIE['session'] ?? null;

        if (!$sessionCode) {
            $this->authErrorMessage = "No session cookie found";
            return false;
        }

        try {
            $session = $this->db->queryFirstRow(
                "SELECT u.*, s.status as session_status, s.expires_at 
                FROM users u
                JOIN user_sessions s ON u.id = s.user_id
                WHERE s.session_code = %s
                AND s.status = 'active'
                AND s.expires_at > NOW()",
                $sessionCode
            );

            if (!$session) {
                $this->authErrorMessage = "Invalid or expired session";
                $this->invalidateSession($sessionCode);
                return false;
            }

            if ($session['status'] !== 'active') {
                $this->authErrorMessage = "User account is not active";
                $this->invalidateSession($sessionCode);
                return false;
            }

            return $session;
        } catch (\Exception $e) {
            $this->authErrorMessage = "Authentication check error: " . $e->getMessage();
            return false;
        }
    }

    public function check(): bool
    {
        if ($this->user !== null && $this->validateSession() !== false) {
            return true;
        }

        $session = $this->validateSession();
        if ($session === false) {
            return false;
        }

        $this->user = $session;
        $this->authErrorMessage = null;
        return true;
    }

    public function getUser(): ?array
    {
        return $this->user;
    }

    public function isAuthenticated(): bool
    {
        return $this->check();
    }

    private function invalidateSession(string $sessionCode): void
    {
        $this->db->update(
            'user_sessions',
            ['status' => 'expired'],
            'session_code=%s',
            $sessionCode
        );

        setcookie("session", "", [
            'expires' => 1,
            'path' => '/'
        ]);
    }
}
