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

        // Custom default error handler (500, etc.)
        $defaultHandler = $errorMiddleware->getDefaultErrorHandler();
        $errorMiddleware->setDefaultErrorHandler(
            function (
                ServerRequestInterface $request,
                \Throwable $exception,
                bool $displayErrorDetails,
                bool $logErrors,
                bool $logErrorDetails
            ) use ($app, $container, $logger): ResponseInterface {
                // Log the error
                $logger->error('app.error', [
                    'message' => $exception->getMessage(),
                    'file'    => $exception->getFile(),
                    'line'    => $exception->getLine(),
                    'url'     => (string) $request->getUri(),
                ]);

                $code = 500;
                if ($exception instanceof \Slim\Exception\HttpException) {
                    $code = $exception->getCode();
                }

                $response = $app->getResponseFactory()->createResponse($code);

                // AJAX / JSON requests
                if ($request->getHeaderLine('Accept') === 'application/json'
                    || $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest'
                    || str_starts_with($request->getUri()->getPath(), '/api/')
                ) {
                    $payload = ['error' => true, 'message' => 'Something went wrong'];
                    if ($displayErrorDetails) {
                        $payload['detail'] = $exception->getMessage();
                    }
                    $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_SLASHES));
                    return $response->withHeader('Content-Type', 'application/json');
                }

                // Plain HTML error page (no Smarty — it might be the cause of the error)
                $html = self::renderErrorPage($displayErrorDetails ? $exception : null);
                $response->getBody()->write($html);
                return $response->withHeader('Content-Type', 'text/html');
            }
        );

        return $app;
    }

    private static function renderErrorPage(?\Throwable $exception = null): string
    {
        $detail = '';
        if ($exception !== null) {
            $detail = '<div style="margin-top:24px;padding:16px;background:#FEF2F2;border-radius:10px;font-size:0.8125rem;color:#991B1B;word-break:break-word">'
                . '<strong>' . htmlspecialchars($exception::class) . '</strong><br>'
                . htmlspecialchars($exception->getMessage()) . '<br>'
                . '<span style="color:#999">' . htmlspecialchars($exception->getFile()) . ':' . $exception->getLine() . '</span>'
                . '</div>';
        }

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Something went wrong</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #F9FAFB; color: #1F2937; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 24px; }
                .error-wrap { text-align: center; max-width: 420px; width: 100%; }
                .error-icon { font-size: 3rem; margin-bottom: 16px; }
                .error-title { font-size: 1.375rem; font-weight: 700; margin-bottom: 8px; }
                .error-text { font-size: 0.9375rem; color: #6B7280; line-height: 1.6; margin-bottom: 24px; }
                .error-btn { display: inline-block; padding: 12px 28px; background: #111827; color: #fff; border-radius: 10px; text-decoration: none; font-size: 0.9375rem; font-weight: 600; }
                .error-btn:active { transform: scale(0.97); }
            </style>
        </head>
        <body>
            <div class="error-wrap">
                <div class="error-icon">&#9888;&#65039;</div>
                <h1 class="error-title">Something went wrong</h1>
                <p class="error-text">We hit an unexpected error. Please try again or go back to the homepage.</p>
                <a href="/" class="error-btn">Go to homepage</a>
                {$detail}
            </div>
        </body>
        </html>
        HTML;
    }
}
