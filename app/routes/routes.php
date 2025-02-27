<?php

namespace Pushbase\Controllers;

use FastRoute\RouteCollector;
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
use Pushbase\Middleware\AuthMiddleware;
use Pushbase\Analytics\SubscribersAnalytics;

return function (RouteCollector $r) {
    // Public Routes (No Authentication Required)
    $r->addGroup('', function (RouteCollector $r) {
        $r->addRoute('GET', '/', [AuthController::class, 'index']);
        $r->addRoute(['GET', 'POST'], '/login', [AuthController::class, 'login']);

        // Password Reset Routes
        $r->addRoute(['GET', 'POST'], '/login/forgot_password', [AuthController::class, 'forgotPassword']);
        $r->addRoute(['GET', 'POST'], '/login/reset_password', [AuthController::class, 'resetPassword']);

        // Install
        $r->addRoute('GET', '/install', [InstallController::class, 'index']);
        $r->addRoute('POST', '/install', [InstallController::class, 'install']);
        $r->addRoute('POST', '/install/rabbitmq', [InstallController::class, 'testRabbitMQConnection']);
        $r->addRoute('POST', '/install/mysql', [InstallController::class, 'testMySQLConnection']);
        $r->addRoute('POST', '/install/smtp', [InstallController::class, 'testSMTPConnection']);
        
        // SDK Routes
        $r->addRoute('GET', '/clientSDK', [SDKController::class, 'clientSDK']);
        $r->addRoute('GET', '/serviceWorker', [SDKController::class, 'serviceWorker']);
        
        // Public API Routes
        $r->addGroup('/api', function (RouteCollector $r) {
            // Subscriber API Routes
            $r->addGroup('/subscriber', function (RouteCollector $r) {
                $r->addRoute('POST', '', [SubscriberController::class, 'subscribe']);
                $r->addRoute('DELETE', '/unsubscribe', [SubscriberController::class, 'unsubscribe']);
                $r->addRoute('POST', '/status', [SubscriberController::class, 'status']);
                $r->addRoute('POST', '/analytics', [SubscribersAnalytics::class, 'trackAnalytics']);
            });
        });
    });

    // Protected Routes (Requires Authentication)
    $r->addGroup('', function (RouteCollector $r) {
        // Authentication
        $r->addRoute('GET', '/logout', [AuthController::class, 'logout']);
        $r->addRoute('GET', '/dashboard', [AdminController::class, 'dashboard']);

        // Download Route for pushBaseSW (Only for user with ID 1)
        $r->addRoute('GET', '/download/pushBaseSW', [SDKController::class, 'downloadPushBaseSW']);

        // Campaign Management
        $r->addGroup('/campaign', function (RouteCollector $r) {
            $r->addRoute(['GET', 'POST'], '', [CampaignController::class, 'campaign']);
            $r->addRoute(['GET', 'POST'], '/edit/{id:\d+}', [CampaignController::class, 'campaign']);
            $r->addRoute('GET', '/delete/{id:\d+}', [CampaignController::class, 'deleteCampaign']);
            $r->addRoute('GET', '/cancel/{id:\d+}', [CampaignController::class, 'cancelCampaign']);
            
            // Updated analytics route to use new CampaignAnalyticsController
            $r->addRoute('GET', '/analytics/{id:\d+}', [CampaignAnalyticsController::class, 'campaignAnalytics']);
        });

        // Campaigns Listing and Export
        $r->addGroup('/campaigns', function (RouteCollector $r) {
            $r->addRoute('GET', '[/page/{page:\d+}]', [CampaignController::class, 'campaigns']);
            $r->addRoute('GET', '/export/{format:csv|xlsx}', [CampaignController::class, 'exportCampaigns']);
        });

        // User Management
        $r->addGroup('/user', function (RouteCollector $r) {
            // GET routes for viewing
            $r->addRoute('GET', '', [UserController::class, 'viewUserCreate']);
            $r->addRoute('GET', '/edit/{id:\d+}', [UserController::class, 'viewUserEdit']);
            
            // POST routes for actions
            $r->addRoute('POST', '', [UserController::class, 'createUser']);
            $r->addRoute('POST', '/edit/{id:\d+}', [UserController::class, 'updateUser']);
            $r->addRoute('POST', '/token/{id:\d+}', [UserController::class, 'generateApiKey']);
        });
        // Users Listing
        $r->addGroup('/users', function (RouteCollector $r) {
            $r->addRoute('GET', '[/page/{page:\d+}]', [UserController::class, 'users']);
        });

        // Segment Management
        $r->addGroup('/segment', function (RouteCollector $r) {
            $r->addRoute(['GET', 'POST'], '/edit/{id:\d+}', [SegmentController::class, 'segment']);
        });
        // Segments Listing
        $r->addGroup('/segments', function (RouteCollector $r) {
            $r->addRoute('GET', '[/page/{page:\d+}]', [SegmentController::class, 'segments']);
        });

        // Client Config
        $r->addRoute('GET', '/client', [ClientConfigController::class, 'index']);

        // API
        $r->addGroup('/api', function (RouteCollector $r) {
            $r->addRoute('POST', '/campaign/import/metadata', [CampaignController::class, 'importMetadata']);

            $r->addGroup('/segments', function (RouteCollector $r) {
                $r->addRoute('POST', '', [SegmentController::class, 'subscribersBySegments']);
                $r->addRoute('GET', '/values/{id:\d+}', [SegmentController::class, 'getSegments']);
            });
        });
    }, [AuthMiddleware::class, 'authenticate']); 
};
