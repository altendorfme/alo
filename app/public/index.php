<?php
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use alo\Container\ContainerFactory;
use alo\Http\RequestHandler;
use alo\Http\ResponseEmitter;
use alo\Middleware\CorsMiddleware;

require __DIR__ . '/../vendor/autoload.php';

// Initialize dependency injection container
$container = ContainerFactory::create();

// Get configuration from container
$config = $container->get('config');

function _e(string $key): string {
    static $translations = null;
    
    if ($translations === null) {
        $config = new \alo\Config\Config();
        $language = $config->get('app.language');

        $translationPath = __DIR__ . '/../languages/' . $language . '.php';
        
        $translations = file_exists($translationPath)
            ? require $translationPath
            : require __DIR__ . '/../languages/en.php';
    }

    return $translations[$key] ?? '<span style="background: red">'.$key.'</span>';
}

// Create PSR-17 factories
$psr17Factory = new Psr17Factory();

// Create PSR-7 ServerRequest
$creator = new ServerRequestCreator(
    $psr17Factory, // ServerRequestFactory
    $psr17Factory, // UriFactory
    $psr17Factory, // UploadedFileFactory
    $psr17Factory  // StreamFactory
);
$request = $creator->fromGlobals();

// Configure router with application routes
$routesFunction = require __DIR__ . '/../routes/routes.php';
$router = $routesFunction($container);

// Create CORS middleware
$corsMiddleware = new CorsMiddleware($config);

// Create request handler
$requestHandler = new RequestHandler($container, $router, $corsMiddleware);

// Handle the request
$response = $requestHandler->handle($request);

// Emit the response
$emitter = new ResponseEmitter();
$emitter->emit($response);
