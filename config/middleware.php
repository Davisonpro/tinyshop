<?php

declare(strict_types=1);

use Slim\App;
use TinyShop\Middleware\MaintenanceMode;
use TinyShop\Middleware\SecureHeaders;
use TinyShop\Middleware\SpaResponse;
use TinyShop\Middleware\CustomDomain;

return function (App $app): void {
    // Slim 4 middleware is LIFO — last added = outermost = runs first on request.
    // We add inner → outer so routing sees the rewritten URI.

    $app->addBodyParsingMiddleware();
    $app->addRoutingMiddleware();

    // SPA fragment responses — converts HTML → JSON when X-SPA: 1 header is present
    $app->add(SpaResponse::class);

    // Custom domain detection — rewrites URI *before* routing resolves
    $app->add(CustomDomain::class);

    // Maintenance mode — blocks public access when enabled (admins bypass)
    $app->add(MaintenanceMode::class);

    // Security response headers (runs on every response)
    $app->add(SecureHeaders::class);
};
