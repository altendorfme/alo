<?php

namespace Pushbase\Http;

use FastRoute\Dispatcher;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Nyholm\Psr7\Response;
use Pushbase\Middleware\CorsMiddleware;
use DI\Container;
use Pushbase\Auth;

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

                $response = new Response();

                if ($this->isProtectedRoute($request->getUri()->getPath())) {
                    $next = function (ServerRequestInterface $request, ResponseInterface $response) use ($handler, $vars) {
                        return $this->executeHandler($handler, $request, $response, $vars);
                    };

                    return $this->authenticate($request, $response, $next);
                }

                return $this->executeHandler($handler, $request, $response, $vars);
        }
    }
    
    private function authenticate(ServerRequestInterface $request, ResponseInterface $response, callable $next)
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

    private function isProtectedRoute(string $path): bool
    {
        $publicRoutes = [
            '/',
            '/clientSDK',
            '/serviceWorker'
        ];
        
        if (strpos($path, '/login') === 0) {
            return false;
        }

        if (strpos($path, '/install') === 0) {
            return false;
        }

        if (strpos($path, '/api/subscriber') === 0) {
            return false;
        }

        foreach ($publicRoutes as $publicRoute) {
            if ($path === $publicRoute) {
                return false;
            }
        }

        return true;
    }
    
    private function executeHandler($handler, ServerRequestInterface $request, ResponseInterface $response, array $vars): ResponseInterface
    {
        if (is_array($handler)) {
            $controller = $this->container->get($handler[0]);
            $method = $handler[1];
            $result = $controller->$method($request, $vars);
            
            if (is_array($result)) {
                return new Response(200, [], json_encode($result));
            }
            if (is_string($result)) {
                return new Response(200, ['Content-Type' => 'text/html'], $result);
            }
            if ($result instanceof ResponseInterface) {
                return $result;
            }
        }
        
        return new Response(500, [], json_encode(['error' => 'Invalid response from handler']));
    }
}
