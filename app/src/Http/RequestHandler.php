<?php

namespace Pushbase\Http;

use League\Route\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Nyholm\Psr7\Response;
use Pushbase\Middleware\CorsMiddleware;
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
        $adapter = new RequestHandlerAdapter(function ($request) {
            return $this->handleRoute($request);
        });
        $response = $this->corsMiddleware->process($request, $adapter);

        return $response;
    }

    private function handleRoute(ServerRequestInterface $request): ResponseInterface
    {
        try {
            // Dispatch the request to the router
            $response = $this->router->dispatch($request);
            
            // If the response is a string (e.g., HTML content), wrap it in a Response object
            if (is_string($response)) {
                return new Response(200, ['Content-Type' => 'text/html'], $response);
            }
            
            return $response;
        } catch (\League\Route\Http\Exception\NotFoundException $e) {
            return new Response(404, [], json_encode(['error' => 'Not found']));
        } catch (\League\Route\Http\Exception\MethodNotAllowedException $e) {
            return new Response(405, [], json_encode(['error' => 'Method not allowed']));
        } catch (\Exception $e) {
            return new Response(500, [], json_encode(['error' => $e->getMessage()]));
        }
    }
}
