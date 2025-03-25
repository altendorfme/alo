<?php

namespace alo\Controllers;

use League\Plates\Engine;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class BaseController
{
    protected $templates;
    protected $auth;

    public function __construct()
    {
        $this->templates = new Engine(__DIR__ . '/../../templates');

        $route = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $route = preg_replace('/\/\d+$/', '', $route);
        $this->templates->addData([
            'route' => $route,
        ]);

        $this->templates->registerFunction('asset', function ($path) {
            return '/assets/' . ltrim($path, '/');
        });

        $this->auth = \alo\Auth::getInstance();
    }

    protected function render(string $template, array $data = [], int $statusCode = 200): ResponseInterface
    {
        if ($this->auth->check()) {
            $get_user = $this->auth->getUser();
            $this->templates->addData(
                [
                    'user' => [
                        'email' => $get_user['email'],
                        'role' => $get_user['role']
                    ]
                ]
            );

            if (
                ($get_user['role'] == 'editor' && $template == 'main/user') ||
                ($get_user['role'] == 'editor' && $template == 'main/users')
            ) {
                return new Response(
                    302,
                    ['Location' => '/dashboard']
                );
            }
        }

        $this->templates->addData($data);

        $content = $this->templates->render($template);

        return new Response($statusCode, ['Content-Type' => 'text/html'], $content);
    }

    protected function redirect(string $route, int $statusCode = 302): ResponseInterface
    {
        return new Response($statusCode, ['Location' => $route]);
    }
}
