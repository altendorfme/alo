<?php

namespace alo\Controllers;

use Dotenv\Dotenv;
use Nyholm\Psr7\Response;
use PDO;
use Exception;
use Psr\Container\ContainerInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use alo\Utilities\PasswordService;
use alo\Config\Config;

class InstallController extends BaseController
{
    private $projectRootPath;
    private $config;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct();
        try {
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../../config');
            $dotenv->load();

            header('Location: /dashboard');
            exit();
        } catch (\Exception $e) {
            $this->projectRootPath = __DIR__ . '/../../';
            $this->config = $container->get(Config::class);
        }
    }

    public function index()
    {
        $formData = [
            'app_url' => $this->getDefaultAppUrl(),
            'app_language' => 'en',
            'client_url' => 'https://alo.org',
            'db_host' => 'mariadb',
            'db_user' => 'alo',
            'db_pass' => '',
            'db_encoding' => 'utf8mb4',
            'db_name' => 'alo',
            'rabbitmq_host' => 'rabbitmq',
            'rabbitmq_user' => 'alo',
            'rabbitmq_pass' => '',
            'rabbitmq_port' => 5672,
            'rabbitmq_vhost' => 'alo',
            'smtp_host' => 'smtp.resend.com',
            'smtp_user' => 'resend',
            'smtp_pass' => '',
            'smtp_port' => 587,
            'smtp_security' => 'tls',
            'smtp_auth' => 'true',
            'smtp_from' => 'alo@' . parse_url($this->getDefaultAppUrl(), PHP_URL_HOST),
            'smtp_from_name' => 'alo',
            'firebase_apikey' => '',
            'firebase_authdomain' => '',
            'firebase_projectid' => '',
            'firebase_storagebucket' => '',
            'firebase_messagingsenderid' => '',
            'firebase_appid' => '',
            'firebase_measurementid' => '',
            'firebase_vapid_public' => '',
            'firebase_vapid_private' => '',
            'user_email' => '',
            'user_password' => PasswordService::generateSecurePassword()
        ];

        return $this->render('install/install', [
            'formData' => $formData
        ]);
    }

    private function getDefaultAppUrl()
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        return "https://" . $host;
    }

    public function install()
    {
        try {
            $formData = $this->validateInstallationData($_POST);

            $dbConfig = $this->configureDatabaseConnection($formData);
            $this->createDatabase($dbConfig);
            $pdo = $this->installDatabaseSchema($dbConfig);

            $this->createAdminUser($formData, $pdo);

            $envConfig = $this->configureEnvironment($formData);
            $this->writeEnvFile($envConfig);

            return new Response(
                302,
                ['Location' => '/login']
            );
        } catch (Exception $e) {
            return $this->render('install/install', [
                'formData' => $_POST,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function validateInstallationData(array $data): array
    {
        $requiredFields = [
            'app_url',
            'app_language',
            'client_url',
            'db_host',
            'db_user',
            'db_encoding',
            'db_name',
            'rabbitmq_host',
            'rabbitmq_user',
            'rabbitmq_pass',
            'rabbitmq_port',
            'rabbitmq_vhost',
            'smtp_host',
            'smtp_user',
            'smtp_pass',
            'smtp_port',
            'smtp_security',
            'smtp_auth',
            'smtp_from',
            'smtp_from_name',
            'firebase_apikey',
            'firebase_authdomain',
            'firebase_projectid',
            'firebase_storagebucket',
            'firebase_messagingsenderid',
            'firebase_appid',
            'firebase_vapid_public',
            'firebase_vapid_private',
            'user_email',
            'user_password'
        ];

        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new Exception("The {$field} field is required.");
            }
        }

        if (!filter_var($data['user_email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid user email.");
        }

        return $data;
    }

    private function configureDatabaseConnection(array $formData): array
    {
        return [
            'host' => $formData['db_host'],
            'user' => $formData['db_user'],
            'pass' => $formData['db_pass'],
            'name' => $formData['db_name'],
            'encoding' => $formData['db_encoding']
        ];
    }

    private function createDatabase(array $dbConfig)
    {
        try {
            $dsn = "mysql:host={$dbConfig['host']}";
            $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare("CREATE DATABASE IF NOT EXISTS `{$dbConfig['name']}` CHARACTER SET {$dbConfig['encoding']}");
            $stmt->execute();
        } catch (Exception $e) {
            throw new Exception("Database creation failed: " . $e->getMessage());
        }
    }

    private function installDatabaseSchema(array $dbConfig): PDO
    {
        try {
            $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']}";
            $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $schemaPath = $this->projectRootPath . '/migration/1-init.sql';

            if (!file_exists($schemaPath)) {
                throw new Exception("Schema file not found in {$schemaPath}");
            }

            $schema = file_get_contents($schemaPath);

            if (empty(trim($schema))) {
                throw new Exception("Schema file is empty");
            }

            $pdo->exec($schema);

            return $pdo;
        } catch (Exception $e) {
            throw new Exception("Schema installation failed: " . $e->getMessage());
        }
    }

    private function configureEnvironment(array $formData): array
    {
        $config = [
            'APP_URL' => $formData['app_url'],
            'APP_LANGUAGE' => $formData['app_language'],
            'CLIENT_URL' => rtrim($formData['client_url'], '/'),
            'CLIENT_ICON_URL' => $formData['client_icon_url'],
            'CLIENT_BADGE_URL' => $formData['client_badge_url'],
            'DB_HOST' => $formData['db_host'],
            'DB_PORT' => $formData['db_port'] ?? 3306,
            'DB_USER' => $formData['db_user'],
            'DB_PASS' => $formData['db_pass'],
            'DB_ENCODING' => $formData['db_encoding'],
            'DB_NAME' => $formData['db_name'],
            'SMTP_HOST' => $formData['smtp_host'],
            'SMTP_PORT' => $formData['smtp_port'],
            'SMTP_USER' => $formData['smtp_user'],
            'SMTP_PASS' => $formData['smtp_pass'],
            'SMTP_FROM' => $formData['smtp_from'],
            'SMTP_FROM_NAME' => $formData['smtp_from_name'],
            'SMTP_SECURITY' => $formData['smtp_security'],
            'SMTP_AUTH' => $formData['smtp_auth'],
            'RABBITMQ_HOST' => $formData['rabbitmq_host'],
            'RABBITMQ_PORT' => $formData['rabbitmq_port'],
            'RABBITMQ_USER' => $formData['rabbitmq_user'],
            'RABBITMQ_PASS' => $formData['rabbitmq_pass'],
            'RABBITMQ_VHOST' => $formData['rabbitmq_vhost'],
            'FIREBASE_VAPID_PUBLIC' => $formData['firebase_vapid_public'],
            'FIREBASE_VAPID_PRIVATE' => $formData['firebase_vapid_private'],
            'FIREBASE_APIKEY' => $formData['firebase_apikey'],
            'FIREBASE_AUTHDOMAIN' => $formData['firebase_authdomain'],
            'FIREBASE_PROJECTID' => $formData['firebase_projectid'],
            'FIREBASE_STORAGEBUCKET' => $formData['firebase_storagebucket'],
            'FIREBASE_MESSAGINGSENDERID' => $formData['firebase_messagingsenderid'],
            'FIREBASE_APPID' => $formData['firebase_appid'],
            'FIREBASE_MEASUREMENTID' => $formData['firebase_measurementid']
        ];

        return $config;
    }

    private function writeEnvFile(array $config)
    {
        $envFilePath = $this->projectRootPath . '/config/.env';

        $envContent = "# Server Configuration\n";
        $envContent .= "APP_URL={$config['APP_URL']}\n";
        $envContent .= "APP_LANGUAGE={$config['APP_LANGUAGE']}\n\n";

        $envContent .= "# Client\n";
        $envContent .= "CLIENT_URL={$config['CLIENT_URL']}\n";
        $envContent .= "CLIENT_ICON_URL={$config['CLIENT_ICON_URL']}\n";
        $envContent .= "CLIENT_BADGE_URL={$config['CLIENT_BADGE_URL']}\n\n";

        $envContent .= "# Database Configuration\n";
        $envContent .= "DB_HOST={$config['DB_HOST']}\n";
        $envContent .= "DB_NAME={$config['DB_NAME']}\n";
        $envContent .= "DB_USER={$config['DB_USER']}\n";
        $envContent .= "DB_PASS={$config['DB_PASS']}\n";
        $envContent .= "DB_ENCODING={$config['DB_ENCODING']}\n\n";

        $envContent .= "# Firebase Configuration\n";
        $envContent .= "FIREBASE_VAPID_PUBLIC={$config['FIREBASE_VAPID_PUBLIC']}\n";
        $envContent .= "FIREBASE_VAPID_PRIVATE={$config['FIREBASE_VAPID_PRIVATE']}\n";
        $envContent .= "FIREBASE_APIKEY={$config['FIREBASE_APIKEY']}\n";
        $envContent .= "FIREBASE_AUTHDOMAIN={$config['FIREBASE_AUTHDOMAIN']}\n";
        $envContent .= "FIREBASE_PROJECTID={$config['FIREBASE_PROJECTID']}\n";
        $envContent .= "FIREBASE_STORAGEBUCKET={$config['FIREBASE_STORAGEBUCKET']}\n";
        $envContent .= "FIREBASE_MESSAGINGSENDERID={$config['FIREBASE_MESSAGINGSENDERID']}\n";
        $envContent .= "FIREBASE_APPID={$config['FIREBASE_APPID']}\n";
        $envContent .= "FIREBASE_MEASUREMENTID={$config['FIREBASE_MEASUREMENTID']}\n\n";

        $envContent .= "# SMTP Configuration\n";
        $envContent .= "SMTP_HOST={$config['SMTP_HOST']}\n";
        $envContent .= "SMTP_PORT={$config['SMTP_PORT']}\n";
        $envContent .= "SMTP_USER={$config['SMTP_USER']}\n";
        $envContent .= "SMTP_PASS={$config['SMTP_PASS']}\n";
        $envContent .= "SMTP_FROM={$config['SMTP_FROM']}\n";
        $envContent .= "SMTP_FROM_NAME={$config['SMTP_FROM_NAME']}\n";
        $envContent .= "SMTP_SECURITY={$config['SMTP_SECURITY']}\n";
        $envContent .= "SMTP_AUTH={$config['SMTP_AUTH']}\n\n";

        $envContent .= "# RabbitMQ\n";
        $envContent .= "RABBITMQ_HOST={$config['RABBITMQ_HOST']}\n";
        $envContent .= "RABBITMQ_PORT={$config['RABBITMQ_PORT']}\n";
        $envContent .= "RABBITMQ_USER={$config['RABBITMQ_USER']}\n";
        $envContent .= "RABBITMQ_PASS={$config['RABBITMQ_PASS']}\n";
        $envContent .= "RABBITMQ_VHOST={$config['RABBITMQ_VHOST']}\n";

        file_put_contents($envFilePath, $envContent);
    }

    private function createAdminUser(array $formData, PDO $pdo)
    {
        $hashedPassword = password_hash($formData['user_password'], PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (email, password, role) VALUES (:email, :password, 'admin')");
        $stmt->bindParam(':email', $formData['user_email']);
        $stmt->bindParam(':password', $hashedPassword);

        if (!$stmt->execute()) {
            throw new Exception("Failed to create user: " . implode(', ', $stmt->errorInfo()));
        }
    }

    public function testRabbitMQConnection()
    {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);

        if (
            !isset($data['host']) || !isset($data['port']) ||
            !isset($data['user']) || !isset($data['pass']) ||
            !isset($data['vhost'])
        ) {
            return new Response(
                400,
                ['Content-Type' => 'application/json'],
                json_encode(['success' => false, 'message' => 'Missing connection parameters'])
            );
        }

        try {
            $connection = new AMQPStreamConnection(
                $data['host'],
                $data['port'],
                $data['user'],
                $data['pass'],
                $data['vhost']
            );

            $channel = $connection->channel();

            $channel->close();
            $connection->close();

            return new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode(['success' => true])
            );
        } catch (AMQPRuntimeException $e) {
            return new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ])
            );
        } catch (Exception $e) {
            return new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'success' => false,
                    'message' => 'Unexpected error: ' . $e->getMessage()
                ])
            );
        }
    }

    public function testMySQLConnection()
    {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);

        if (
            !isset($data['host']) || !isset($data['user']) ||
            !isset($data['name'])
        ) {
            return new Response(
                400,
                ['Content-Type' => 'application/json'],
                json_encode(['success' => false, 'message' => 'Missing connection parameters'])
            );
        }

        try {
            $dsn = "mysql:host={$data['host']}";
            $pdo = new PDO($dsn, $data['user'], $data['pass'] ?? '');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->query("SHOW DATABASES LIKE '{$data['name']}'");

            return new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode(['success' => true])
            );
        } catch (Exception $e) {
            return new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ])
            );
        }
    }

    public function testSMTPConnection()
    {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);

        if (!isset($data['host']) || !isset($data['port'])) {
            return new Response(
                400,
                ['Content-Type' => 'application/json'],
                json_encode(['success' => false, 'message' => 'Missing SMTP host or port'])
            );
        }

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $data['host'];
            $mail->Port = $data['port'];
            $mail->SMTPAuth = $data['auth'] === 'true';

            if ($mail->SMTPAuth) {
                if (!isset($data['user']) || !isset($data['pass'])) {
                    return new Response(
                        400,
                        ['Content-Type' => 'application/json'],
                        json_encode(['success' => false, 'message' => 'SMTP authentication requires username and password'])
                    );
                }
                $mail->Username = $data['user'];
                $mail->Password = $data['pass'];
            }

            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
            $mail->SMTPDebug = SMTP::DEBUG_OFF;
            $mail->Timeout = 10;

            if (!$mail->smtpConnect()) {
                return new Response(
                    200,
                    ['Content-Type' => 'application/json'],
                    json_encode([
                        'success' => false,
                        'message' => 'Could not connect to SMTP server'
                    ])
                );
            }

            $mail->smtpClose();

            return new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode(['success' => true])
            );
        } catch (Exception $e) {
            return new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ])
            );
        }
    }
}
