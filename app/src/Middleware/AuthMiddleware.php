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
            return $this->createRedirectResponse('Session expired. Please login again.');
        }

        $user = $auth->getUser();

        if ($user['status'] !== 'active') {
            return $this->createRedirectResponse('Inactive account.');
        }

        $request = $request->withAttribute('user', $user);
        
        return $handler->handle($request);
    }
    private function createRedirectResponse(string $errorMessage): ResponseInterface
    {
        return new Response(
            302,
            [
                'Location' => '/login',
                'X-Error-Message' => $errorMessage
            ]
        );
    }
}