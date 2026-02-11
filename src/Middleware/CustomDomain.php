<?php

declare(strict_types=1);

namespace TinyShop\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TinyShop\Models\User;
use TinyShop\Services\Config;

/**
 * Rewrites subdomain / custom-domain requests into internal shop routes.
 *
 *   davis.tinyshop.com/           → /~shop/davis
 *   davis.tinyshop.com/blue-shirt → /~shop/davis/blue-shirt
 *   myshop.com/                   → /~shop/davis  (looked up by custom_domain)
 */
final class CustomDomain implements MiddlewareInterface
{
    private const APP_PREFIXES = [
        '/api/',
        '/dashboard',
        '/admin',
        '/login',
        '/register',
        '/auth/',
        '/logout',
        '/public/',
        '/install',
        '/~shop/',
    ];

    private string $baseDomain;

    public function __construct(
        private User $userModel,
        Config $config
    ) {
        $this->baseDomain = strtolower($config->baseDomain());
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();

        if ($this->isAppRoute($path)) {
            return $handler->handle($request);
        }

        $host = self::stripPort(strtolower($request->getUri()->getHost()));
        $subdomain = $this->resolveSubdomain($host);

        if ($subdomain === null) {
            return $handler->handle($request);
        }

        $newPath = '/~shop/' . $subdomain;
        if ($path !== '/' && $path !== '') {
            $newPath .= $path;
        }

        $request = $request->withUri($request->getUri()->withPath($newPath));

        return $handler->handle($request);
    }

    private function isAppRoute(string $path): bool
    {
        foreach (self::APP_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function resolveSubdomain(string $host): ?string
    {
        $base = self::stripPort($this->baseDomain);

        // Subdomain: davis.tinyshop.com → "davis"
        $suffix = '.' . $base;
        if (str_ends_with($host, $suffix)) {
            $sub = substr($host, 0, -strlen($suffix));
            if ($sub !== '' && $sub !== 'www') {
                return $sub;
            }
        }

        // Custom domain: myshop.com → DB lookup
        if ($host !== $base && $host !== 'www.' . $base) {
            $shop = $this->userModel->findByCustomDomain($host);
            if ($shop !== null) {
                return $shop['subdomain'];
            }
        }

        return null;
    }

    private static function stripPort(string $host): string
    {
        $pos = strrpos($host, ':');

        return $pos !== false ? substr($host, 0, $pos) : $host;
    }
}
