<?php

declare(strict_types=1);

namespace TinyShop\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Default JSON headers for API responses.
 *
 * @since 1.0.0
 */
final class JsonResponse implements MiddlewareInterface
{
    /**
     * Set JSON content type and disable caching.
     *
     * @since 1.0.0
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if (!$response->hasHeader('Content-Type')) {
            $response = $response->withHeader('Content-Type', 'application/json');
        }

        // Prevent browsers and proxies from caching API responses
        return $response->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate');
    }
}
