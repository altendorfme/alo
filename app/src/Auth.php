<?php

namespace Pushbase;

use MeekroDB;
use Pushbase\Database\Database;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class Auth
{
    private static ?Auth $instance = null;
    private $db;
    private ?array $user = null;
    private ?string $sessionCode = null;
    private ?int $cookieExpiration = null;
    private ?string $authErrorMessage = null;
    private const DEFAULT_SESSION_DURATION = 7;
    private const EXTENDED_SESSION_DURATION = 30;
    private const SESSION_COOKIE_NAME = 'session';

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

            if (!$user) {
                $this->authErrorMessage = "Invalid credentials - User not found";
                return false;
            }

            $passwordVerified = password_verify($password, $user['password']);
            
            if (!$passwordVerified) {
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

            try {
                $this->db->insert('user_sessions', [
                    'user_id' => $user['id'],
                    'session_code' => $this->sessionCode,
                    'created_at' => $this->db->sqleval('NOW()'),
                    'expires_at' => $expiresAt,
                    'ip_address' => $this->getClientIpAddress(),
                    'status' => 'active'
                ]);
            } catch (\Exception $e) {
                throw $e;
            }

            try {
                $this->db->update(
                    'users',
                    ['last_login' => $this->db->sqleval('NOW()')],
                    'id=%i',
                    $user['id']
                );
            } catch (\Exception $e) {
                // Not throwing here as it's not critical
            }

            $this->cookieExpiration = strtotime($expiresAt);

            $this->user = $user;
            $this->authErrorMessage = null;
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    public function setSessionCookie(): void
    {        
        if (!$this->sessionCode || !$this->cookieExpiration) {
            return;
        }

        $result = setcookie(
            self::SESSION_COOKIE_NAME,
            $this->sessionCode,
            [
                'expires' => $this->cookieExpiration,
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Strict',
                'secure' => $this->isSecureConnection()
            ]
        );
        
    }
    private function validateSession(?string $sessionCode = null): array|false
    {
        $sessionCode = $sessionCode ?? $_COOKIE[self::SESSION_COOKIE_NAME] ?? null;

        if (!$sessionCode) {
            $this->authErrorMessage = "No session cookie found";
            return false;
        }

        try {
            $session = $this->db->queryFirstRow(
                "SELECT u.*, s.status as session_status, s.expires_at, s.id as session_id
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

    public function getErrorMessage(): ?string
    {
        return $this->authErrorMessage;
    }

    public function logout(): bool
    {
        $sessionCode = $_COOKIE[self::SESSION_COOKIE_NAME] ?? null;
        
        if ($sessionCode) {
            $this->invalidateSession($sessionCode);
        }
        
        $this->user = null;
        $this->sessionCode = null;
        $this->cookieExpiration = null;
        
        return true;
    }

    private function invalidateSession(string $sessionCode): void
    {
        try {
            $this->db->update(
                'user_sessions',
                ['status' => 'expired'],
                'session_code=%s',
                $sessionCode
            );
        } catch (\Exception $e) {
            // Error invalidating session in database
        }

        $result = setcookie(self::SESSION_COOKIE_NAME, "", [
            'expires' => 1,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Strict',
            'secure' => $this->isSecureConnection()
        ]);
    }

    private function getClientIpAddress(): string
    {
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
            return '127.0.0.1';
        }

        if (!$ip || !filter_var($ip, FILTER_VALIDATE_IP)) {
            return '127.0.0.1';
        }

        return $ip;
    }

    private function isSecureConnection(): bool
    {
        $https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $port443 = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443;
        
        return $https || $port443;
    }
}
