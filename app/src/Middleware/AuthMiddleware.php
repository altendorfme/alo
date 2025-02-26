<?php

namespace Pushbase\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Pushbase\Auth;
use Nyholm\Psr7\Response;
use Pushbase\Config\Config;

class AuthMiddleware
{
    public static function authenticate(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $auth = Auth::getInstance();

        if (!$auth->check()) {
            return new Response(
                302,
                [
                    'Location' => '/login',
                    'X-Error-Message' => 'Sessão expirada. Por favor, faça login novamente.'
                ]
            );
        }

        $user = $auth->getUser();

        if ($user['status'] !== 'active') {
            return new Response(
                302,
                [
                    'Location' => '/login',
                    'X-Error-Message' => 'Conta inativa. Entre em contato com o administrador.'
                ]
            );
        }

        return $next($request, $response);
    }
}
