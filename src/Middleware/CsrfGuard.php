<?php

declare(strict_types=1);

namespace TinyShop\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

/**
 * CSRF protection middleware.
 *
 * Enforces CSRF tokens on state-changing requests from browser sessions.
 * Skips validation for:
 *  - GET/HEAD/OPTIONS (safe methods)
 *  - Requests with a valid Bearer token (stateless API clients)
 *  - Requests with no Referer/Origin (non-browser clients like mobile apps)
 *
 * @since 1.0.0
 */
final class CsrfGuard implements MiddlewareInterface
{
    private const STATE_CHANGING_METHODS = ['POST', 'PUT', 'DELETE', 'PATCH'];

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!in_array($request->getMethod(), self::STATE_CHANGING_METHODS, true)) {
            return $handler->handle($request);
        }

        // Bearer-authenticated requests are stateless — immune to CSRF
        if ($this->hasBearer($request)) {
            return $handler->handle($request);
        }

        // Non-browser clients (no Origin or Referer) are not susceptible to CSRF.
        // Browsers always send at least one of these on cross-origin POSTs.
        if (!$this->isBrowserRequest($request)) {
            return $handler->handle($request);
        }

        // Browser request — enforce CSRF
        \TinyShop\Services\Auth::ensureSession();

        $expected = $_SESSION['_csrf_token'] ?? '';
        $supplied = $this->extractCsrfToken($request);

        if ($expected === '' || $supplied === '' || !hash_equals($expected, $supplied)) {
            return $this->reject();
        }

        return $handler->handle($request);
    }

    private function hasBearer(ServerRequestInterface $request): bool
    {
        $header = $request->getHeaderLine('Authorization');
        return str_starts_with($header, 'Bearer ') && strlen($header) > 7;
    }

    /**
     * Detect browser-originated requests.
     *
     * Browsers always send Origin on CORS/POST requests and Referer on
     * same-origin requests. A request with neither is from a non-browser
     * client (mobile app, curl, server-to-server).
     */
    private function isBrowserRequest(ServerRequestInterface $request): bool
    {
        return $request->getHeaderLine('Origin') !== ''
            || $request->getHeaderLine('Referer') !== '';
    }

    private function extractCsrfToken(ServerRequestInterface $request): string
    {
        $token = $request->getHeaderLine('X-CSRF-Token');
        if ($token !== '') {
            return $token;
        }

        $body = (array) $request->getParsedBody();
        return (string) ($body['_csrf_token'] ?? '');
    }

    private function reject(): ResponseInterface
    {
        $response = new Response();
        $response->getBody()->write(json_encode([
            'error'   => true,
            'message' => 'Invalid or missing CSRF token',
        ]));
        return $response
            ->withStatus(403)
            ->withHeader('Content-Type', 'application/json');
    }
}
