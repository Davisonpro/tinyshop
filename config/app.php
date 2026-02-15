<?php

declare(strict_types=1);

return [
    'name'          => $_ENV['APP_NAME'] ?? 'TinyShop',
    'debug'         => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'url'           => $_ENV['APP_URL'],
    'base_domain'   => $_ENV['APP_BASE_DOMAIN'],
    'upload_dir'    => dirname(__DIR__) . '/public/uploads',
    'upload_url'    => '/public/uploads',
    'max_file_size' => 5 * 1024 * 1024,
    'allowed_types' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml'],
    'templates_dir' => dirname(__DIR__) . '/templates',
    'compile_dir'   => dirname(__DIR__) . '/var/compiled',
    'cache_dir'     => dirname(__DIR__) . '/var/cache',
];
