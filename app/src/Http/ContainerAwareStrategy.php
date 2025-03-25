<?php

namespace alo\Http;

use League\Route\Route;
use League\Route\Strategy\ApplicationStrategy;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ContainerAwareStrategy extends ApplicationStrategy
{
    protected ?ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function invokeRouteCallable(Route $route, ServerRequestInterface $request): ResponseInterface
    {
        $handler = $route->getCallable($this->container);
        
        $vars = $route->getVars();
        
        if (is_array($handler) && is_string($handler[0])) {
            $handler[0] = $this->container->get($handler[0]);
        }
        
        return $handler($request, $vars);
    }
}