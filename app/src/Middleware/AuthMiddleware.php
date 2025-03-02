<?php

namespace Pushbase\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Nyholm\Psr7\Response;
use Pushbase\Auth;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
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
        
        return $handler->handle($request);
    }
}