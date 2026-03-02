<?php

declare(strict_types=1);

namespace TinyShop\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Security headers middleware.
 *
 * @since 1.0.0
 */
final class SecureHeaders implements MiddlewareInterface
{
    /**
     * Add security headers to the response.
     *
     * @since 1.0.0
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        $response = $response
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-Frame-Options', 'SAMEORIGIN')
            ->withHeader('X-XSS-Protection', '1; mode=block')
            ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->withHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
            ->withHeader('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' https://js.stripe.com https://www.paypal.com https://www.sandbox.paypal.com https://www.googletagmanager.com https://connect.facebook.net",
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
                "font-src 'self' https://fonts.gstatic.com",
                "img-src 'self' data: blob: https:",
                "connect-src 'self' https://api.stripe.com https://api-m.paypal.com https://api-m.sandbox.paypal.com https://www.google-analytics.com",
                "frame-src https://js.stripe.com https://www.paypal.com https://www.sandbox.paypal.com",
                "frame-ancestors 'none'",
                "base-uri 'self'",
                "form-action 'self' https://checkout.stripe.com https://www.paypal.com https://www.sandbox.paypal.com",
            ]));

        // HSTS — only sent over HTTPS (browsers ignore it over HTTP)
        $isHttps = ($request->getUri()->getScheme() === 'https')
            || (($request->getServerParams()['HTTPS'] ?? '') === 'on');

        if ($isHttps) {
            $response = $response->withHeader(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        return $response;
    }
}
