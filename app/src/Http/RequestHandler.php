<?php

namespace alo\Http;

use League\Route\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Nyholm\Psr7\Response;
use alo\Middleware\CorsMiddleware;
use DI\Container;

class RequestHandler
{
    private Container $container;
    private Router $router;
    private CorsMiddleware $corsMiddleware;

    public function __construct(Container $container, Router $router, CorsMiddleware $corsMiddleware)
    {
        $this->container = $container;
        $this->router = $router;
        $this->corsMiddleware = $corsMiddleware;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $adapter = new RequestHandlerAdapter(function (ServerRequestInterface $request): ResponseInterface {
            return $this->handleRoute($request);
        });
        
        return $this->corsMiddleware->process($request, $adapter);
    }

    private function handleRoute(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $response = $this->router->dispatch($request);

            if (is_string($response)) {
                return new Response(200, ['Content-Type' => 'text/html'], $response);
            }
            
            return $response;
        } catch (\League\Route\Http\Exception\NotFoundException $e) {
            return new Response(
                404,
                ['Content-Type' => 'application/json'],
                json_encode(['error' => 'Resource not found', 'path' => $request->getUri()->getPath()])
            );
        } catch (\League\Route\Http\Exception\MethodNotAllowedException $e) {
            return new Response(
                405,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'error' => 'Method not allowed',
                    'method' => $request->getMethod(),
                    'allowed_methods' => $e->getAllowedMethods()
                ])
            );
        } catch (\Exception $e) {
            error_log('Error handling request: ' . $e->getMessage());
            
            return new Response(
                500,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'error' => 'Internal server error',
                    'message' => $e->getMessage()
                ])
            );
        }
    }
}
