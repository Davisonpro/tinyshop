<?php

declare(strict_types=1);

namespace TinyShop;

use DI\ContainerBuilder;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory;
use TinyShop\Services\AddonLoader;
use TinyShop\Services\Config;
use TinyShop\Services\Hooks;
use TinyShop\Services\Logger;

final class App
{
    public static function create(array $appConfig, array $dbConfig): \Slim\App
    {
        $config = new Config($appConfig);

        $containerBuilder = new ContainerBuilder();

        $definitions = (require dirname(__DIR__) . '/config/container.php')($config, $dbConfig);
        $containerBuilder->addDefinitions($definitions);

        $container = $containerBuilder->build();

        AppFactory::setContainer($container);
        $app = AppFactory::create();

        // Load addons (hook into system before routes/middleware)
        $addonLoader = new AddonLoader(dirname(__DIR__) . '/addons');
        $addonLoader->loadAll();

        Hooks::doAction('tinyshop.boot', $app, $container);

        // Register middleware
        (require dirname(__DIR__) . '/config/middleware.php')($app);

        // Register routes
        (require dirname(__DIR__) . '/config/routes.php')($app);

        Hooks::doAction('tinyshop.routes.registered', $app);

        // Error handling with file logging
        $logger = $container->get(LoggerInterface::class);
        $errorMiddleware = $app->addErrorMiddleware($config->isDebug(), true, true, $logger);

        return $app;
    }
}
