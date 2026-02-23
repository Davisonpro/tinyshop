<?php

declare(strict_types=1);

namespace TinyShop\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
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
        '/checkout/',
        '/webhook/',
        '/health',
    ];

    private const RESERVED_SUBDOMAINS = [
        'www', 'webmail', 'mail', 'smtp', 'imap', 'pop', 'pop3',
        'ftp', 'cpanel', 'whm', 'cpcalendars', 'cpcontacts',
        'phpmyadmin', 'autodiscover', 'autoconfig',
        'ns1', 'ns2', 'dns', 'mx',
    ];

    private static array $domainCache = [];
    private readonly string $baseDomain;

    public function __construct(
        private readonly User $userModel,
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
        $result = $this->resolveCustomDomain($host);

        if ($result === null) {
            return $handler->handle($request);
        }

        // Domain found but plan expired past grace period — show disconnected page
        if ($result['status'] === 'disconnected') {
            return $this->renderDisconnectedPage($result['store_name'], $result['subdomain']);
        }

        $newPath = '/~shop/' . $result['subdomain'];
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

    /**
     * @return array{subdomain: string, status: string, store_name: string}|null
     */
    private function resolveCustomDomain(string $host): ?array
    {
        $base = self::stripPort($this->baseDomain);

        // Subdomain: davis.tinyshop.com → "davis" (always allowed)
        $suffix = '.' . $base;
        if (str_ends_with($host, $suffix)) {
            $sub = substr($host, 0, -strlen($suffix));
            if ($sub !== '' && !in_array($sub, self::RESERVED_SUBDOMAINS, true)) {
                return ['subdomain' => $sub, 'status' => 'active', 'store_name' => ''];
            }
        }

        // Custom domain: myshop.com → DB lookup (with in-memory cache)
        if ($host !== $base && $host !== 'www.' . $base) {
            if (array_key_exists($host, self::$domainCache)) {
                return self::$domainCache[$host];
            }

            $shop = $this->userModel->findByCustomDomain($host);
            if ($shop === null) {
                self::$domainCache[$host] = null;
                return null;
            }

            $subdomain = $shop['subdomain'];
            $storeName = $shop['store_name'] ?? $subdomain;
            $status = $this->getDomainStatus($shop);

            $result = ['subdomain' => $subdomain, 'status' => $status, 'store_name' => $storeName];
            self::$domainCache[$host] = $result;

            return $result;
        }

        return null;
    }

    private function getDomainStatus(array $shop): string
    {
        $planId = $shop['plan_id'] ?? null;
        $expiresAt = $shop['plan_expires_at'] ?? null;

        // Active paid plan
        if ($planId !== null && $expiresAt !== null && strtotime($expiresAt) >= time()) {
            return 'active';
        }

        // Expired — 7-day grace period
        if ($expiresAt !== null) {
            $graceEnd = strtotime($expiresAt) + (7 * 86400);
            if (time() < $graceEnd) {
                return 'active';
            }
        }

        return 'disconnected';
    }

    private function renderDisconnectedPage(string $storeName, string $subdomain): ResponseInterface
    {
        $storeName = htmlspecialchars($storeName);
        $subdomainUrl = 'https://' . htmlspecialchars($subdomain) . '.' . htmlspecialchars($this->baseDomain);

        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>{$storeName}</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #F9FAFB; color: #1F2937; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 24px; }
                .wrap { text-align: center; max-width: 420px; width: 100%; }
                .icon { margin-bottom: 20px; color: #D97706; }
                h1 { font-size: 1.375rem; font-weight: 700; margin-bottom: 8px; }
                p { font-size: 0.9375rem; color: #6B7280; line-height: 1.6; margin-bottom: 24px; }
                .btn { display: inline-block; padding: 12px 28px; background: #111827; color: #fff; border-radius: 10px; text-decoration: none; font-size: 0.9375rem; font-weight: 600; }
                .btn:active { transform: scale(0.97); }
            </style>
        </head>
        <body>
            <div class="wrap">
                <div class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg></div>
                <h1>This domain is disconnected</h1>
                <p>The custom domain for <strong>{$storeName}</strong> is no longer active. The shop may still be available at its original address.</p>
                <a href="{$subdomainUrl}" class="btn">Visit shop</a>
            </div>
        </body>
        </html>
        HTML;

        $response = new Response(200);
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    private static function stripPort(string $host): string
    {
        $pos = strrpos($host, ':');

        return $pos !== false ? substr($host, 0, $pos) : $host;
    }
}
