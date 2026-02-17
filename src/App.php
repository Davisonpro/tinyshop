<?php

declare(strict_types=1);

namespace TinyShop;

use DI\ContainerBuilder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Factory\AppFactory;
use TinyShop\Services\AddonLoader;
use TinyShop\Services\Config;
use TinyShop\Services\Hooks;
use TinyShop\Services\Logger;
use TinyShop\Services\View;

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

        // Custom 404 handler
        $errorMiddleware->setErrorHandler(
            HttpNotFoundException::class,
            function (
                ServerRequestInterface $request,
                \Throwable $exception,
                bool $displayErrorDetails,
                bool $logErrors,
                bool $logErrorDetails
            ) use ($app, $container): ResponseInterface {
                $response = $app->getResponseFactory()->createResponse(404);

                // AJAX / JSON requests get a JSON response
                if ($request->getHeaderLine('Accept') === 'application/json'
                    || $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest'
                    || str_starts_with($request->getUri()->getPath(), '/api/')
                ) {
                    $response->getBody()->write(json_encode([
                        'error'   => true,
                        'message' => 'Not found',
                    ], JSON_UNESCAPED_SLASHES));
                    return $response->withHeader('Content-Type', 'application/json');
                }

                // HTML requests get the 404 template
                $view = $container->get(View::class);
                return $view->render($response, 'pages/404.tpl', [
                    'page_title' => 'Page Not Found',
                ]);
            }
        );

        return $app;
    }
}
