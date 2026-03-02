<?php

declare(strict_types=1);

namespace TinyShop\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

/**
 * File-based rate limiter.
 *
 * @since 1.0.0
 */
final class RateLimit implements MiddlewareInterface
{
    private int $maxAttempts;
    private int $windowSeconds;
    private string $storageDir;

    /**
     * @since 1.0.0
     *
     * @param int    $maxAttempts   Max requests per window.
     * @param int    $windowSeconds Window length in seconds.
     * @param string $storageDir    State file directory.
     */
    public function __construct(int $maxAttempts = 10, int $windowSeconds = 60, string $storageDir = '')
    {
        $this->maxAttempts = $maxAttempts;
        $this->windowSeconds = $windowSeconds;
        $this->storageDir = $storageDir ?: sys_get_temp_dir() . '/tinyshop_ratelimit';
    }

    /**
     * Enforce the rate limit, returning 429 if exceeded.
     *
     * @since 1.0.0
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $ip = $this->resolveIp($request);
        $path = $request->getUri()->getPath();
        $key = hash('xxh128', $ip . '|' . $path);

        if (!is_dir($this->storageDir)) {
            @mkdir($this->storageDir, 0755, true);
        }

        $file = $this->storageDir . '/' . $key;
        $now = time();
        $attempts = $this->readAttempts($file, $now);

        if (count($attempts) >= $this->maxAttempts) {
            $retryAfter = ($attempts[0] + $this->windowSeconds) - $now;
            $response = new Response();
            $response->getBody()->write(json_encode([
                'error'   => true,
                'message' => 'Too many requests. Please try again later.',
            ]));
            return $response
                ->withStatus(429)
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Retry-After', (string) max(1, $retryAfter));
        }

        $attempts[] = $now;
        $this->writeAttempts($file, $attempts);

        // Lazy GC: ~1% of requests prune old files
        if (random_int(1, 100) === 1) {
            $this->gc();
        }

        return $handler->handle($request);
    }

    /** @return int[] Timestamps within the current window. */
    private function readAttempts(string $file, int $now): array
    {
        if (!is_file($file)) {
            return [];
        }

        $raw = @file_get_contents($file);
        if ($raw === false) {
            return [];
        }

        $cutoff = $now - $this->windowSeconds;
        $timestamps = array_filter(
            array_map('intval', explode("\n", trim($raw))),
            fn(int $ts) => $ts > $cutoff
        );

        return array_values($timestamps);
    }

    /** Write timestamps to the state file. */
    private function writeAttempts(string $file, array $timestamps): void
    {
        @file_put_contents($file, implode("\n", $timestamps), LOCK_EX);
    }

    /** Get the client IP. */
    private function resolveIp(ServerRequestInterface $request): string
    {
        $server = $request->getServerParams();
        return $server['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /** Clean up stale rate-limit files. */
    private function gc(): void
    {
        $cutoff = time() - ($this->windowSeconds * 2);
        $files = @scandir($this->storageDir);
        if ($files === false) {
            return;
        }

        foreach ($files as $f) {
            if ($f === '.' || $f === '..') {
                continue;
            }
            $path = $this->storageDir . '/' . $f;
            if (is_file($path) && filemtime($path) < $cutoff) {
                @unlink($path);
            }
        }
    }
}
