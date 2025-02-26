<?php

namespace Pushbase\Controllers;

use PHPMailer\PHPMailer\PHPMailer;
use Psr\Container\ContainerInterface;
use Pushbase\Config\Config;
use League\Plates\Engine;

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

        $this->translations = $this->loadTranslations();
    }

    private function loadTranslations(): array
    {
        static $translations = null;

        if ($translations === null) {
            $language = $this->config->get('app.language');

            $translationPath = __DIR__ . '/../../languages/' . $language . '.php';

            $translations = file_exists($translationPath)
                ? require $translationPath
                : require __DIR__ . '/../../languages/en.php';
        }

        return $translations;
    }

    private function _e(string $key): string
    {
        return $this->translations[$key] ?? '<span style="background: red">' . $key . '</span>';
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
        $this->mailer->setFrom($this->smtp['from'], $this->smtp['fromName']);
        $this->mailer->isHTML(true);
    }

    public function sendPasswordResetEmail(string $email, string $resetToken): void
    {
        $this->mailer->clearAddresses();
        $this->mailer->addAddress($email);
        $this->mailer->Subject = $this->_e('email_password_reset_subject');

        $resetLink = $this->config->get('app.url') . "/login/reset_password?token=" . urlencode($resetToken);

        $templateData = [
            'lang' => $this->config->get('app.language'),
            'resetLink' => $resetLink,
            'title' => $this->_e('email_password_reset_title'),
            'line1' => $this->_e('email_password_reset_line1'),
            'line2' => $this->_e('email_password_reset_line2'),
            'line3' => $this->_e('email_password_reset_line3'),
            'button' => $this->_e('email_password_reset_button')
        ];

        $this->mailer->Body = $this->templateEngine->render('emails/password_reset', $templateData);

        $this->mailer->send();
    }

    public function sendWelcomeEmail(string $email, string $password): void
    {
        $this->mailer->clearAddresses();
        $this->mailer->addAddress($email);
        $this->mailer->Subject = $this->_e('email_welcome_subject');

        $loginLink = $this->config->get('app.url') . "/login";

        $templateData = [
            'lang' => $this->config->get('app.language'),
            'email' => $email,
            'password' => $password,
            'loginLink' => $loginLink,
            'emailText' => $this->_e('email'),
            'passwordText' => $this->_e('password'),
            'title' => $this->_e('email_welcome_title'),
            'line1' => $this->_e('email_welcome_line1'),
            'button' => $this->_e('email_welcome_button')
        ];

        $this->mailer->Body = $this->templateEngine->render('emails/welcome', $templateData);

        $this->mailer->send();
    }
}
