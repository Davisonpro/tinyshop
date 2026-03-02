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
 * @since 1.0.0
 */
final class CsrfGuard implements MiddlewareInterface
{
    /**
     * Validate the CSRF token on state-changing requests.
     *
     * @since 1.0.0
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = $request->getMethod();

        if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
            \TinyShop\Services\Auth::ensureSession();
            $expected = $_SESSION['_csrf_token'] ?? '';
            $token = $request->getHeaderLine('X-CSRF-Token');

            if ($token === '') {
                $body = (array) $request->getParsedBody();
                $token = $body['_csrf_token'] ?? '';
            }

            if ($expected === '' || !hash_equals($expected, $token)) {
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

        return $handler->handle($request);
    }
}
