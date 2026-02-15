<?php

declare(strict_types=1);

namespace TinyShop\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use TinyShop\Services\Auth;

final class AuthGuard implements MiddlewareInterface
{
    public function __construct(private readonly Auth $auth) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        Auth::ensureSession();

        if (!$this->auth->check()) {
            $path = $request->getUri()->getPath();

            if (str_starts_with($path, '/api/')) {
                $response = new Response();
                $response->getBody()->write(json_encode([
                    'error'   => true,
                    'message' => 'Authentication required',
                ]));
                return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
            }

            return (new Response())->withHeader('Location', '/login')->withStatus(302);
        }

        return $handler->handle($request);
    }
}
