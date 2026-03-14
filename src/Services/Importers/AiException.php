<?php

declare(strict_types=1);

namespace TinyShop\Services\Importers;

/**
 * Thrown when the AI API call fails due to external issues (rate limit, auth, server error).
 */
final class AiException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly bool $rateLimited = false,
    ) {
        parent::__construct($message);
    }
}
