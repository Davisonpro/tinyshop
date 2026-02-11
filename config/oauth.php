<?php

declare(strict_types=1);

return [
    'google' => [
        'enabled'       => filter_var($_ENV['OAUTH_GOOGLE_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'client_id'     => $_ENV['OAUTH_GOOGLE_CLIENT_ID'] ?? '',
        'client_secret' => $_ENV['OAUTH_GOOGLE_CLIENT_SECRET'] ?? '',
        'redirect_uri'  => '/auth/callback/google',
    ],
    'instagram' => [
        'enabled'       => filter_var($_ENV['OAUTH_INSTAGRAM_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'client_id'     => $_ENV['OAUTH_INSTAGRAM_CLIENT_ID'] ?? '',
        'client_secret' => $_ENV['OAUTH_INSTAGRAM_CLIENT_SECRET'] ?? '',
        'redirect_uri'  => '/auth/callback/instagram',
    ],
    'tiktok' => [
        'enabled'       => filter_var($_ENV['OAUTH_TIKTOK_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'client_key'    => $_ENV['OAUTH_TIKTOK_CLIENT_KEY'] ?? '',
        'client_secret' => $_ENV['OAUTH_TIKTOK_CLIENT_SECRET'] ?? '',
        'redirect_uri'  => '/auth/callback/tiktok',
    ],
];
