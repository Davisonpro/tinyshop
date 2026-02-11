<?php

declare(strict_types=1);

return [
    'host'     => $_ENV['DB_HOST'],
    'port'     => (int) ($_ENV['DB_PORT'] ?? 3306),
    'dbname'   => $_ENV['DB_NAME'],
    'username' => $_ENV['DB_USERNAME'],
    'password' => $_ENV['DB_PASSWORD'] ?? '',
    'charset'  => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
];
