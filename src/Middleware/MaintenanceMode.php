<?php

declare(strict_types=1);

namespace TinyShop\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use TinyShop\Models\Setting;
use TinyShop\Services\Auth;

final class MaintenanceMode implements MiddlewareInterface
{
    public function __construct(
        private readonly Setting $setting,
        private readonly Auth $auth
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->setting->get('maintenance_mode', '0') !== '1') {
            return $handler->handle($request);
        }

        // Admins can always access the site
        if ($this->auth->check() && $this->auth->isAdmin()) {
            return $handler->handle($request);
        }

        // Allow login/logout so admins can authenticate
        $path = $request->getUri()->getPath();
        if (in_array($path, ['/login', '/logout', '/api/auth/login', '/api/auth/logout'], true)) {
            return $handler->handle($request);
        }

        // Allow admin routes for authenticated admins (already checked above)
        // But block everything else

        if (str_starts_with($path, '/api/')) {
            $response = new Response();
            $response->getBody()->write(json_encode([
                'error'   => true,
                'message' => 'Site is under maintenance. Please try again later.',
            ]));
            return $response->withStatus(503)->withHeader('Content-Type', 'application/json');
        }

        $response = new Response();
        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>Maintenance</title>'
            . '<style>body{font-family:-apple-system,BlinkMacSystemFont,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#f8f9fa;color:#333}'
            . '.box{text-align:center;max-width:420px;padding:40px 24px}'
            . 'h1{font-size:1.5rem;margin:0 0 8px}p{color:#666;line-height:1.6;margin:0}</style></head>'
            . '<body><div class="box"><h1>We\'ll be back soon</h1><p>We\'re performing scheduled maintenance. Please check back in a little while.</p></div></body></html>';
        $response->getBody()->write($html);
        return $response->withStatus(503)->withHeader('Content-Type', 'text/html; charset=utf-8');
    }
}
