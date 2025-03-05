<?php

namespace Pushbase\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Nyholm\Psr7\Response;
use Pushbase\Database\Database;

class ApiAuthMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $db = Database::getInstance();

        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader) || !preg_match('/^Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $this->createErrorResponse('Authorization header with Bearer token is required', 401);
        }

        $token = $matches[1];

        $user = $db->queryFirstRow(
            "SELECT * FROM users WHERE api_key = %s AND status = 'active'",
            $token
        );
        
        if (!$user) {
            return $this->createErrorResponse('Invalid or expired token', 401);
        }

        $request = $request->withAttribute('user', $user);
        
        return $handler->handle($request);
    }
    
    private function createErrorResponse(string $errorMessage, int $statusCode = 400): ResponseInterface
    {
        return new Response(
            $statusCode,
            ['Content-Type' => 'application/json'],
            json_encode(['error' => $errorMessage])
        );
    }
}