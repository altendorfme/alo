<?php

namespace Pushbase\Controllers;

use League\Route\Router;
use League\Route\RouteGroup;
use Pushbase\Analytics\CampaignsAnalytics;
use Pushbase\Controllers\AdminController;
use Pushbase\Controllers\CampaignController;
use Pushbase\Controllers\CampaignAnalyticsController;
use Pushbase\Controllers\UserController;
use Pushbase\Controllers\SubscriberController;
use Pushbase\Controllers\SegmentController;
use Pushbase\Controllers\AuthController;
use Pushbase\Controllers\InstallController;
use Pushbase\Controllers\SDKController;
use Pushbase\Controllers\ClientConfigController;
use Pushbase\Analytics\SubscribersAnalytics;
use Pushbase\Http\ContainerAwareStrategy;
use Pushbase\Middleware\AuthMiddleware;
use Pushbase\Middleware\ApiAuthMiddleware;
use DI\Container;

return function (Container $container): Router {
    $router = new Router();

    $strategy = new ContainerAwareStrategy($container);
    $router->setStrategy($strategy);
    $authMiddleware = new AuthMiddleware();
    
    // Public Routes (No Authentication Required)
    // Login
    $router->get('/', [AuthController::class, 'index']);
    $router->map(['GET', 'POST'], '/login', [AuthController::class, 'login']);
    //-- Forgot/Reset Routes
    $router->map(['GET', 'POST'], '/login/forgot_password', [AuthController::class, 'forgotPassword']);
    $router->map(['GET', 'POST'], '/login/reset_password', [AuthController::class, 'resetPassword']);
    
    // Install
    $router->get('/install', [InstallController::class, 'index']);
    $router->post('/install', [InstallController::class, 'install']);
    $router->post('/install/rabbitmq', [InstallController::class, 'testRabbitMQConnection']);
    $router->post('/install/mysql', [InstallController::class, 'testMySQLConnection']);
    $router->post('/install/smtp', [InstallController::class, 'testSMTPConnection']);
    
    // SDK Routes
    $router->get('/clientSDK', [SDKController::class, 'clientSDK']);
    $router->get('/serviceWorker', [SDKController::class, 'serviceWorker']);
    
    // API
    //-- Subscriber
    $router->post('/api/subscriber', [SubscriberController::class, 'subscribe']);
    $router->delete('/api/subscriber/unsubscribe', [SubscriberController::class, 'unsubscribe']);
    $router->post('/api/subscriber/status', [SubscriberController::class, 'status']);
    $router->post('/api/subscriber/analytics', [SubscribersAnalytics::class, 'trackAnalytics']);
    
    // Bearer Token Authentication
    //-- API
    $apiAuthMiddleware = new ApiAuthMiddleware();
    $router->post('/api/campaign/create', [CampaignController::class, 'apiCreateCampaign'])
        ->middleware($apiAuthMiddleware);
    
    // Protected Routes (Requires Authentication)
    $router->group('', function (RouteGroup $route) {
        $route->get('/logout', [AuthController::class, 'logout']);
        $route->get('/dashboard', [AdminController::class, 'dashboard']);
        
        // Campaign Management
        $route->get('/campaign', [CampaignController::class, 'viewCampaign']);
        $route->post('/campaign', [CampaignController::class, 'processCampaign']);
        $route->get('/campaign/edit/{id:\d+}', [CampaignController::class, 'viewEditCampaign']);
        $route->post('/campaign/edit/{id:\d+}', [CampaignController::class, 'processEditCampaign']);
        $route->get('/campaign/delete/{id:\d+}', [CampaignController::class, 'deleteCampaign']);
        $route->get('/campaign/cancel/{id:\d+}', [CampaignController::class, 'cancelCampaign']);
        $route->get('/campaign/analytics/{id:\d+}', [CampaignAnalyticsController::class, 'campaignAnalytics']);
        $route->get('/campaigns[/page/{page:\d+}]', [CampaignController::class, 'viewCampaigns']);
        $route->post('/campaigns', [CampaignController::class, 'processCampaigns']);
        $route->get('/campaigns/export/{format:csv|xlsx}', [CampaignController::class, 'exportCampaigns']);
        
        // User Management
        $route->get('/user', [UserController::class, 'viewUserCreate']);
        $route->get('/user/edit/{id:\d+}', [UserController::class, 'viewUserEdit']);
        $route->post('/user', [UserController::class, 'createUser']);
        $route->post('/user/edit/{id:\d+}', [UserController::class, 'updateUser']);
        $route->post('/user/token/{id:\d+}', [UserController::class, 'generateApiKey']);
        $route->get('/users[/page/{page:\d+}]', [UserController::class, 'viewUsers']);
        
        // Segment Management
        $route->get('/segment/edit/{id:\d+}', [SegmentController::class, 'viewSegment']);
        $route->post('/segment/edit/{id:\d+}', [SegmentController::class, 'updateSegment']);
        $route->get('/segments[/page/{page:\d+}]', [SegmentController::class, 'viewSegments']);
        
        // Client Config
        $route->get('/client', [ClientConfigController::class, 'index']);
        //-- Download pushBaseSW
        $route->get('/download/pushBaseSW', [SDKController::class, 'downloadPushBaseSW']);

        // API
        //-- Campaign
        $route->post('/api/campaign/import/metadata', [CampaignController::class, 'importMetadata']);
        //-- Segments
        $route->post('/api/segments', [SegmentController::class, 'subscribersBySegments']);
        $route->get('/api/segments/values/{id:\d+}', [SegmentController::class, 'getSegments']);
    })->middleware($authMiddleware);
    
    return $router;
};
