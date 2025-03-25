<?php

namespace alo\Container;

use DI\Container;
use DI\ContainerBuilder;
use alo\Config\Config;
use alo\Auth;
use alo\Controllers\{
    AdminController,
    AuthController,
    BaseController,
    CampaignController,
    EmailController,
    InstallController,
    SegmentController,
    SubscriberController,
    TemplateController,
    UserController,
    ClientConfigController
};
use alo\Middleware\{
    CorsMiddleware
};
use alo\Commands\Campaign\{
    SendCommand,
    QueueCommand
};
use alo\Commands\GeoIP\UpdateCommand;
use alo\Analytics\{
    SubscribersAnalytics,
    CampaignsAnalytics
};

class ContainerFactory
{
    public static function create(): Container
    {
        $builder = new ContainerBuilder();

        $builder->useAutowiring(true);
        $builder->useAttributes(true);

        $builder->addDefinitions([
            // Create a Config instance. The constructor will now load the configuration.
            Config::class => \DI\autowire(Config::class),

            // Create an alias 'config' for easy access to the Config instance.
            'config' => \DI\get(Config::class),

            // Add Auth service to the container
            Auth::class => \DI\factory(function () {
                return Auth::getInstance();
            }),
            'auth' => \DI\get(Auth::class),

            // Controller
            AdminController::class => \DI\autowire(AdminController::class),
            AuthController::class => \DI\autowire(AuthController::class),
            BaseController::class => \DI\autowire(BaseController::class),
            CampaignController::class => \DI\autowire(CampaignController::class),
            EmailController::class => \DI\autowire(EmailController::class),
            InstallController::class => \DI\autowire(InstallController::class),
            SegmentController::class => \DI\autowire(SegmentController::class),
            SubscriberController::class => \DI\autowire(SubscriberController::class),
            TemplateController::class => \DI\autowire(TemplateController::class),
            UserController::class => \DI\autowire(UserController::class),

            // Middleware
            CorsMiddleware::class => \DI\autowire(CorsMiddleware::class),

            // Analytics
            CampaignsAnalytics::class => \DI\autowire(CampaignsAnalytics::class),
            SubscribersAnalytics::class => \DI\autowire(SubscribersAnalytics::class),

            // Commands
            UpdateCommand::class => \DI\autowire(UpdateCommand::class),
            SendCommand::class => \DI\autowire(SendCommand::class),
            QueueCommand::class => \DI\autowire(QueueCommand::class),
        ]);

        return $builder->build();
    }
}
