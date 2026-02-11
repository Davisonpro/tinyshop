<?php

declare(strict_types=1);

namespace TinyShop\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use TinyShop\Services\Auth;

final class GuestOnly implements MiddlewareInterface
{
    public function __construct(private Auth $auth) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->auth->check()) {
            return (new Response())->withHeader('Location', '/dashboard')->withStatus(302);
        }

        return $handler->handle($request);
    }
}
