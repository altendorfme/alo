<?php

namespace Pushbase\Http;

use FastRoute\Dispatcher;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Nyholm\Psr7\Response;
use Pushbase\Middleware\CorsMiddleware;
use DI\Container;

class RequestHandler
{
    private Container $container;
    private Dispatcher $dispatcher;
    private CorsMiddleware $corsMiddleware;

    public function __construct(Container $container, Dispatcher $dispatcher, CorsMiddleware $corsMiddleware)
    {
        $this->container = $container;
        $this->dispatcher = $dispatcher;
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
        $routeInfo = $this->dispatcher->dispatch(
            $request->getMethod(),
            $request->getUri()->getPath()
        );

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                return new Response(404, [], json_encode(['error' => 'Not found']));
            case Dispatcher::METHOD_NOT_ALLOWED:
                return new Response(405, [], json_encode(['error' => 'Method not allowed']));
            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];

                if (is_array($handler)) {
                    $controller = $this->container->get($handler[0]);
                    $method = $handler[1];
                    $response = $controller->$method($request, $vars);

                    if (is_array($response)) {
                        return new Response(200, [], json_encode($response));
                    }
                    if (is_string($response)) {
                        return new Response(200, ['Content-Type' => 'text/html'], $response);
                    }
                    if ($response instanceof ResponseInterface) {
                        return $response;
                    }
                }

                return new Response(500, [], json_encode(['error' => 'Invalid response from handler']));
        }
    }
}
