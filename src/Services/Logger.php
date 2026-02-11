<?php

declare(strict_types=1);

namespace TinyShop\Services;

use Psr\Log\AbstractLogger;
use Stringable;

/**
 * Minimal PSR-3 logger that writes to a file.
 * No Monolog dependency — just structured, timestamped log lines.
 */
final class Logger extends AbstractLogger
{
    private string $logFile;

    public function __construct(string $logDir)
    {
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $this->logFile = $logDir . '/app-' . date('Y-m-d') . '.log';
    }

    public function log($level, string|Stringable $message, array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $line = "[{$timestamp}] [{$level}] {$message}";

        if (isset($context['exception']) && $context['exception'] instanceof \Throwable) {
            $e = $context['exception'];
            $line .= ' | ' . get_class($e) . ': ' . $e->getMessage()
                . ' in ' . $e->getFile() . ':' . $e->getLine();
        } elseif ($context !== []) {
            $line .= ' | ' . json_encode($context, JSON_UNESCAPED_SLASHES);
        }

        @file_put_contents($this->logFile, $line . "\n", FILE_APPEND | LOCK_EX);
    }
}
