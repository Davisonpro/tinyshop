<?php

declare(strict_types=1);

namespace TinyShop\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Stream;

/**
 * Converts full HTML responses into compact JSON fragments for SPA navigation.
 *
 * When the client sends X-SPA: 1, this middleware intercepts the HTML response,
 * extracts body content / title / styles / scripts, and returns a JSON envelope.
 * This cuts response size 30-50% and eliminates client-side DOMParser overhead.
 */
final class SpaResponse implements MiddlewareInterface
{
    /** Core assets loaded on every page — no need to send in SPA responses. */
    private const CORE_STYLES = [
        '/public/css/app.css',
        '/public/css/marketing.css',
        '/public/css/fontawesome.min.css',
        'fonts.googleapis.com',
    ];

    private const CORE_SCRIPTS = [
        '/public/js/jquery.min.js',
        '/public/js/app.js',
    ];

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if ($request->getHeaderLine('X-SPA') !== '1') {
            return $response;
        }

        // For redirects, return JSON with redirect URL so SPA can follow it
        $status = $response->getStatusCode();
        if ($status >= 300 && $status < 400) {
            $location = $response->getHeaderLine('Location');
            if ($location !== '') {
                $payload = json_encode(['redirect' => $location], JSON_UNESCAPED_SLASHES);
                $stream = new Stream(fopen('php://temp', 'r+'));
                $stream->write($payload);
                return $response
                    ->withBody($stream)
                    ->withStatus(200)
                    ->withHeader('Content-Type', 'application/json; charset=utf-8')
                    ->withHeader('X-SPA-Fragment', '1');
            }
        }

        if ($status < 200 || $status >= 300) {
            return $response;
        }

        $contentType = $response->getHeaderLine('Content-Type');
        if (!str_contains($contentType, 'text/html')) {
            return $response;
        }

        $html = (string) $response->getBody();

        // Extract title
        $title = '';
        if (preg_match('/<title>([^<]*)<\/title>/', $html, $m)) {
            $title = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
        }

        // Extract body class
        $bodyClass = '';
        if (preg_match('/<body[^>]*class="([^"]*)"/', $html, $m)) {
            $bodyClass = $m[1];
        }

        // Extract body inner HTML
        $bodyContent = '';
        if (preg_match('/<body[^>]*>(.*)<\/body>/s', $html, $m)) {
            $bodyContent = $m[1];
        }

        // Extract stylesheet hrefs and inline styles from <head>
        $styles = [];
        $inlineStyles = [];
        if (preg_match('/<head[^>]*>(.*?)<\/head>/s', $html, $headMatch)) {
            $head = $headMatch[1];
            // Match both regular stylesheets and preloaded ones that swap to stylesheet
            if (preg_match_all('/<link[^>]+href="([^"]*)"[^>]*>/i', $head, $linkMatches)) {
                foreach ($linkMatches[0] as $i => $tag) {
                    if (str_contains($tag, 'stylesheet') || str_contains($tag, 'as="style"')) {
                        $styles[] = $linkMatches[1][$i];
                    }
                }
            }
            // Extract inline <style> blocks
            if (preg_match_all('/<style[^>]*>([\s\S]*?)<\/style>/i', $head, $styleMatches)) {
                foreach ($styleMatches[1] as $css) {
                    $css = trim($css);
                    if ($css !== '') {
                        $inlineStyles[] = $css;
                    }
                }
            }
        }

        // Extract scripts from body (external + inline)
        $scripts = [];
        $inlineScripts = [];
        preg_match_all('/<script([^>]*)>([\s\S]*?)<\/script>/i', $bodyContent, $scriptMatches, PREG_SET_ORDER);

        foreach ($scriptMatches as $sm) {
            $attrs = $sm[1];
            $content = trim($sm[2]);

            // Skip non-JS scripts (JSON-LD, importmap, etc.)
            if (preg_match('/type="([^"]*)"/', $attrs, $typeMatch)) {
                $type = strtolower($typeMatch[1]);
                if ($type !== '' && $type !== 'text/javascript' && $type !== 'module') {
                    continue;
                }
            }

            if (preg_match('/src="([^"]*)"/', $attrs, $srcMatch)) {
                $scripts[] = $srcMatch[1];
            } elseif ($content !== '') {
                // Skip service worker registration
                if (str_contains($content, 'serviceWorker') && str_contains($content, 'register')) {
                    continue;
                }
                $inlineScripts[] = $content;
            }
        }

        // Strip all script tags from body content
        $bodyContent = preg_replace('/<script[\s\S]*?<\/script>/i', '', $bodyContent);

        // Strip core assets — the client already has them permanently loaded
        $styles = array_values(array_filter($styles, static function (string $href): bool {
            foreach (self::CORE_STYLES as $core) {
                if (str_contains($href, $core)) {
                    return false;
                }
            }
            return true;
        }));

        $scripts = array_values(array_filter($scripts, static function (string $src): bool {
            foreach (self::CORE_SCRIPTS as $core) {
                if (str_contains($src, $core)) {
                    return false;
                }
            }
            return true;
        }));

        $payload = json_encode([
            'title'         => $title,
            'bodyClass'     => $bodyClass,
            'csrf'          => $_SESSION['_csrf_token'] ?? '',
            'styles'        => $styles,
            'inlineStyles'  => $inlineStyles,
            'scripts'       => $scripts,
            'inlineScripts' => $inlineScripts,
            'body'          => trim($bodyContent),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $stream = new Stream(fopen('php://temp', 'r+'));
        $stream->write($payload);

        return $response
            ->withBody($stream)
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withHeader('X-SPA-Fragment', '1');
    }
}
