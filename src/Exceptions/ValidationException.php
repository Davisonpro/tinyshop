<?php

declare(strict_types=1);

namespace TinyShop\Exceptions;

final class ValidationException extends \RuntimeException
{
    /** @var array<string, string> */
    private array $errors;

    /**
     * @param array<string, string> $errors  field => message
     */
    public function __construct(array $errors)
    {
        $this->errors = $errors;
        parent::__construct('Validation failed: ' . implode(', ', $errors));
    }

    /** @return array<string, string> */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
