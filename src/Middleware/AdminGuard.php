<?php

declare(strict_types=1);

namespace TinyShop\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use TinyShop\Services\Auth;

final class AdminGuard implements MiddlewareInterface
{
    public function __construct(private readonly Auth $auth) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        Auth::ensureSession();

        if (!$this->auth->check()) {
            return (new Response())->withHeader('Location', '/login')->withStatus(302);
        }

        if (!$this->auth->isAdmin()) {
            $path = $request->getUri()->getPath();

            if (str_starts_with($path, '/api/')) {
                $response = new Response();
                $response->getBody()->write(json_encode([
                    'error'   => true,
                    'message' => 'Forbidden',
                ]));
                return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
            }

            return (new Response())->withHeader('Location', '/dashboard')->withStatus(302);
        }

        return $handler->handle($request);
    }
}
