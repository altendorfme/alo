<?php

namespace alo\Controllers;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use Psr\Container\ContainerInterface;
use alo\Config\Config;
use League\Plates\Engine;
use Exception;

class EmailController extends BaseController
{
    private array $smtp;
    private PHPMailer $mailer;
    protected Config $config;
    private Engine $templateEngine;
    private array $translations;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct();
        $this->config = $container->get(Config::class);

        $this->smtp = $this->config->get('smtp');
        $this->mailer = new PHPMailer(true);
        $this->configureSMTP();

        $this->templateEngine = new Engine(__DIR__ . '/../../templates');
        
        $this->templateEngine->registerFunction('get', function($key, $default = null) {
            return $this->templateEngine->getData($key) ?? $default;
        });
    }

    function _e(string $key): string {
        static $translations = null;
        
        if ($translations === null) {
            $config = new \alo\Config\Config();
            $language = $config->get('app.language');
    
            $translationPath = __DIR__ . '/../../languages/' . $language . '.php';
            
            $translations = file_exists($translationPath)
                ? require $translationPath
                : require __DIR__ . '/../../languages/en.php';
        }
    
        return $translations[$key] ?? '<span style="background: red">'.$key.'</span>';
    }

    private function configureSMTP(): void
    {
        $this->mailer->isSMTP();
        $this->mailer->Host = $this->smtp['host'];
        $this->mailer->Port = intval($this->smtp['port']);
        $this->mailer->SMTPAuth = $this->smtp['auth'];
        $this->mailer->SMTPSecure = trim($this->smtp['security']);
        $this->mailer->Username = trim($this->smtp['user']);
        $this->mailer->Password = trim($this->smtp['pass']);
        $this->mailer->CharSet = 'UTF-8';

        $fromEmail = $this->smtp['from'];
        $fromName = $this->smtp['fromName'];
        
        $this->mailer->setFrom($fromEmail, $fromName);
        $this->mailer->isHTML(true);
    }

    public function sendPasswordResetEmail(string $email, string $resetToken): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email);
            $this->mailer->Subject = $this->_e('email_password_reset_subject');

            $resetLink = $this->config->get('app.url') . "/login/reset_password?token=" . urlencode($resetToken);

            $templateData = [
                'lang' => $this->config->get('app.lang'),
                'resetLink' => $resetLink,
                'title' => $this->_e('email_password_reset_title'),
                'line1' => $this->_e('email_password_reset_line1'),
                'line2' => $this->_e('email_password_reset_line2'),
                'line3' => $this->_e('email_password_reset_line3'),
                'button' => $this->_e('email_password_reset_button')
            ];

            $this->mailer->Body = $this->templateEngine->render('emails/password_reset', $templateData);
            $this->mailer->AltBody = strip_tags(str_replace(['<br>', '</p>'], ["\n", "\n\n"], $this->mailer->Body));

            return $this->mailer->send();
        } catch (PHPMailerException $e) {
            error_log("Failed to send password reset email to {$email}: " . $e->getMessage());
            throw new Exception("Failed to send password reset email: " . $e->getMessage());
        }
    }

    public function sendWelcomeEmail(string $email, string $password): bool
    {
        try {
            $this->mailer->addAddress($email);
            
            $this->mailer->Subject = $this->_e('email_welcome_subject');

            $loginLink = $this->config->get('app.url') . "/login";
            
            $templateData = [
                'lang' => $this->config->get('app.lang'),
                'email' => $email,
                'password' => $password,
                'loginLink' => $loginLink,
                'emailText' => $this->_e('email'),
                'passwordText' => $this->_e('password'),
                'title' => $this->_e('email_welcome_title'),
                'line1' => $this->_e('email_welcome_line1'),
                'button' => $this->_e('email_welcome_button')
            ];

            try {
                $this->mailer->Body = $this->templateEngine->render('emails/welcome', $templateData);
                $this->mailer->AltBody = strip_tags(str_replace(['<br>', '</p>'], ["\n", "\n\n"], $this->mailer->Body));
            } catch (Exception $e) {
                error_log("Failed to render welcome email template: " . $e->getMessage());
                throw new Exception("Failed to render welcome email template: " . $e->getMessage());
            }

            $result = $this->mailer->send();
            
            if (!$result) {
                error_log("Failed to send welcome email to {$email}: " . $this->mailer->ErrorInfo);
                throw new Exception("Failed to send welcome email: " . $this->mailer->ErrorInfo);
            }
            
            return $result;
        } catch (PHPMailerException $e) {
            error_log("PHPMailer exception when sending welcome email to {$email}: " . $e->getMessage());
            throw new Exception("Failed to send welcome email: " . $e->getMessage());
        } catch (Exception $e) {
            error_log("Exception when sending welcome email to {$email}: " . $e->getMessage());
            throw $e;
        }
    }
}
