<?php

namespace alo\Config;

use Dotenv\Dotenv;
use Exception;

class Config
{
    private array $config;

    public function __construct()
    {
        $this->config = [
            'app' => [
                'url' => '',
                'language' => 'en'
            ],
            'client' => [
                'url' => '',
                'icon' => '',
                'badge' => '',
            ],
            'smtp' => [
                'host' => '',
                'port' => '',
                'user' => '',
                'pass' => '',
                'from' => '',
                'fromName' => '',
                'security' => '',
                'auth' => false,
            ],
            'firebase' => [
                'apiKey' => '',
                'authDomain' => '',
                'projectId' => '',
                'storageBucket' => '',
                'messagingSenderId' => '',
                'appId' => '',
                'measurementId' => '',
                'vapid' => [
                    'public' => '',
                    'private' => '',
                ]
            ],
            'rabbitmq' => [
                'host' => '',
                'port' => '',
                'user' => '',
                'password' => '',
                'vhost' => ''
            ]
        ];

        try {
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../../config');
            $dotenv->load();

            \DB::$host = $_ENV['DB_HOST'];
            \DB::$dbName = $_ENV['DB_NAME'];
            \DB::$user = $_ENV['DB_USER'];
            \DB::$password = $_ENV['DB_PASS'];
            \DB::$encoding = $_ENV['DB_ENCODING'];

            $this->config = [
                'app' => [
                    'url' => $_ENV['APP_URL'],
                    'language' => $_ENV['APP_LANGUAGE']
                ],
				'client' => [
					'url' => $_ENV['CLIENT_URL'],
                    'icon' => $_ENV['CLIENT_ICON_URL'],
                    'badge' => $_ENV['CLIENT_BADGE_URL'],
				],
                'smtp' => [
                    'host' => $_ENV['SMTP_HOST'],
                    'port' => $_ENV['SMTP_PORT'],
                    'user' => $_ENV['SMTP_USER'],
                    'pass' => $_ENV['SMTP_PASS'],
                    'from' => $_ENV['SMTP_FROM'],
                    'fromName' => $_ENV['SMTP_FROM_NAME'],
                    'security' => $_ENV['SMTP_SECURITY'],
                    'auth' => filter_var($_ENV['SMTP_AUTH'], FILTER_VALIDATE_BOOLEAN),
                ],
                'firebase' => [
                    'apiKey' => $_ENV['FIREBASE_APIKEY'],
                    'authDomain' => $_ENV['FIREBASE_AUTHDOMAIN'],
                    'projectId' => $_ENV['FIREBASE_PROJECTID'],
                    'storageBucket' => $_ENV['FIREBASE_STORAGEBUCKET'],
                    'messagingSenderId' => $_ENV['FIREBASE_MESSAGINGSENDERID'],
                    'appId' => $_ENV['FIREBASE_APPID'],
                    'measurementId' => $_ENV['FIREBASE_MEASUREMENTID'],
                    'vapid' => [
                        'public' => $_ENV['FIREBASE_VAPID_PUBLIC'],
                        'private' => $_ENV['FIREBASE_VAPID_PRIVATE'],
                    ]
                ],
                'rabbitmq' => [
                    'host' => $_ENV['RABBITMQ_HOST'],
                    'port' => $_ENV['RABBITMQ_PORT'],
                    'user' => $_ENV['RABBITMQ_USER'],
                    'password' => $_ENV['RABBITMQ_PASS'],
                    'vhost' => $_ENV['RABBITMQ_VHOST']
                ]
            ];
        } catch (\Exception $e) {
            $currentPath = $_SERVER['REQUEST_URI'] ?? '';

            if (strpos($currentPath, '/install') !== 0) {
                header('Location: /install');
                exit();
            }
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $data = $this->config;

        foreach ($segments as $segment) {
            if (!isset($data[$segment])) {
                return $default;
            }
            $data = $data[$segment];
        }

        return $data;
    }

    public function all(): array
    {
        return $this->config;
    }
}
