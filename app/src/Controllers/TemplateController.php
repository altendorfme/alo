<?php

namespace Pushbase\Controllers;

use League\Plates\Engine;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class TemplateController
{
    private $templateEngine;
    private $auth;

    public function __construct()
    {
        $this->templateEngine = new Engine(__DIR__ . '/../../templates');

        $this->templateEngine->registerFunction('asset', function ($path) {
            return '/assets/' . ltrim($path, '/');
        });

        $this->auth = \Pushbase\Auth::getInstance();
    }

    public function render(string $template, array $data = [], int $statusCode = 200): ResponseInterface
    {
        if ($this->auth->check()) {
            $data['user'] = $this->auth->getUser();
        }

        $content = $this->templateEngine->render($template, $data);

        return new Response($statusCode, ['Content-Type' => 'text/html'], $content);
    }

    public function redirect(string $route, int $statusCode = 302): ResponseInterface
    {
        return new Response($statusCode, ['Location' => $route]);
    }
}
