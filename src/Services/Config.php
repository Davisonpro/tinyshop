<?php

declare(strict_types=1);

namespace TinyShop\Services;

use RuntimeException;

/**
 * Immutable application configuration with boot-time validation.
 *
 * Every required key is validated once at construction. No fallback
 * defaults — if config is wrong, the app fails fast with a clear message.
 */
final class Config
{
    private const REQUIRED_KEYS = [
        'name',
        'url',
        'base_domain',
        'upload_dir',
        'upload_url',
        'max_file_size',
        'allowed_types',
        'templates_dir',
        'compile_dir',
        'cache_dir',
    ];

    private array $data;

    public function __construct(array $data)
    {
        $missing = array_diff(self::REQUIRED_KEYS, array_keys($data));
        if ($missing !== []) {
            throw new RuntimeException(
                'Missing required config keys: ' . implode(', ', $missing)
            );
        }

        if (!str_starts_with($data['url'], 'http://') && !str_starts_with($data['url'], 'https://')) {
            throw new RuntimeException("Config 'url' must start with http:// or https://");
        }

        $this->data = $data;
    }

    public function get(string $key): mixed
    {
        if (!array_key_exists($key, $this->data)) {
            throw new RuntimeException("Config key '{$key}' does not exist");
        }

        return $this->data[$key];
    }

    public function name(): string        { return $this->data['name']; }
    public function url(): string         { return rtrim($this->data['url'], '/'); }
    public function baseDomain(): string  { return $this->data['base_domain']; }
    public function isDebug(): bool       { return !empty($this->data['debug']); }
    public function templatesDir(): string { return $this->data['templates_dir']; }
    public function compileDir(): string  { return $this->data['compile_dir']; }
    public function cacheDir(): string    { return $this->data['cache_dir']; }
    public function uploadDir(): string   { return $this->data['upload_dir']; }
    public function uploadUrl(): string   { return $this->data['upload_url']; }
    public function maxFileSize(): int    { return (int) $this->data['max_file_size']; }

    /** @return string[] */
    public function allowedTypes(): array { return $this->data['allowed_types']; }

    public function toArray(): array { return $this->data; }
}
