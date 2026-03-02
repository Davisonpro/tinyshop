<?php

declare(strict_types=1);

namespace TinyShop\Services;

use RuntimeException;

/**
 * Application configuration.
 *
 * @since 1.0.0
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

    /**
     * @param array<string, mixed> $data Raw config array from config/app.php.
     *
     * @throws RuntimeException If required keys are missing.
     */
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

    /**
     * Get a config value by key.
     *
     * @since 1.0.0
     *
     * @param  string $key Config key.
     * @return mixed
     *
     * @throws RuntimeException If key does not exist.
     */
    public function get(string $key): mixed
    {
        if (!array_key_exists($key, $this->data)) {
            throw new RuntimeException("Config key '{$key}' does not exist");
        }

        return $this->data[$key];
    }

    /** Platform display name. */
    public function name(): string        { return $this->data['name']; }

    /** Base URL without trailing slash. */
    public function url(): string         { return rtrim($this->data['url'], '/'); }

    /** Root domain for subdomain routing. */
    public function baseDomain(): string  { return $this->data['base_domain']; }

    public function isDebug(): bool       { return !empty($this->data['debug']); }
    public function templatesDir(): string { return $this->data['templates_dir']; }
    public function compileDir(): string  { return $this->data['compile_dir']; }
    public function cacheDir(): string    { return $this->data['cache_dir']; }
    public function uploadDir(): string   { return $this->data['upload_dir']; }
    public function uploadUrl(): string   { return $this->data['upload_url']; }

    /** @return int Max upload size in bytes. */
    public function maxFileSize(): int    { return (int) $this->data['max_file_size']; }

    /** @return string[] Allowed MIME types. */
    public function allowedTypes(): array { return $this->data['allowed_types']; }

    /** @return array<string, mixed> */
    public function toArray(): array { return $this->data; }
}
