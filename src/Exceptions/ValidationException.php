<?php

declare(strict_types=1);

namespace TinyShop\Exceptions;

/**
 * Input validation failure with per-field errors.
 *
 * @since 1.0.0
 */
final class ValidationException extends \RuntimeException
{
    /** @var array<string, string> */
    private array $errors;

    /**
     * @since 1.0.0
     *
     * @param array<string, string> $errors Field-to-message map.
     */
    public function __construct(array $errors)
    {
        $this->errors = $errors;
        parent::__construct('Validation failed: ' . implode(', ', $errors));
    }

    /**
     * Get the validation errors.
     *
     * @since 1.0.0
     *
     * @return array<string, string> Field-to-message map.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
