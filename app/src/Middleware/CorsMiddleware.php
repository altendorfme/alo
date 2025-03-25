<?php

namespace alo\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Nyholm\Psr7\Response;
use alo\Config\Config;

class CorsMiddleware implements MiddlewareInterface
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getMethod() === 'OPTIONS') {
            return $this->createPreflightResponse($request);
        }
        $response = $handler->handle($request);
        return $this->addCorsHeaders($response);
    }

    private function createPreflightResponse(ServerRequestInterface $request): ResponseInterface
    {
        $headers = $this->getCorsHeaders();

        $requestMethod = $request->getHeaderLine('Access-Control-Request-Method');
        if (!empty($requestMethod)) {
            $headers['Access-Control-Allow-Methods'] = $requestMethod;
        }
        
        return new Response(204, $headers);
    }

    private function addCorsHeaders(ResponseInterface $response): ResponseInterface
    {
        $headers = $this->getCorsHeaders();
        
        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }
        
        return $response;
    }

    private function getCorsHeaders(): array
    {
        return [
            'Access-Control-Allow-Origin' => $this->config->get('client.url'),
            'Access-Control-Allow-Headers' => 'X-Requested-With, Content-Type, Accept, Origin, Authorization, x-service-worker-version',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, PATCH, OPTIONS',
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Max-Age' => 86400,
        ];
    }
}
