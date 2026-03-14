<?php

/**
 * CLI migration runner.
 *
 * Usage:
 *   php migrate.php              — Run pending migrations
 *   php migrate.php status       — Show pending/applied counts
 *   php migrate.php mark-all     — Mark all migrations as applied (init existing DB)
 */

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

require __DIR__ . '/vendor/autoload.php';

use TinyShop\Services\Migrator;

// Load environment variables (same as index.php)
$envFile = __DIR__ . '/config/env.php';
if (file_exists($envFile)) {
    $env = require $envFile;
    foreach ($env as $key => $value) {
        $_ENV[$key] = (string) $value;
    }
}

// Load database config
$dbConfig = require __DIR__ . '/config/database.php';

$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
    $dbConfig['host'],
    $dbConfig['port'],
    $dbConfig['dbname'],
    $dbConfig['charset']
);

$pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
]);

$migrator = new Migrator($pdo, __DIR__ . '/migrations');
$command = $argv[1] ?? 'migrate';

match ($command) {
    'status' => (function () use ($migrator) {
        $applied = $migrator->getApplied();
        $pending = $migrator->getPending();
        echo "Applied: " . count($applied) . "\n";
        echo "Pending: " . count($pending) . "\n";
        if ($pending) {
            echo "\nPending migrations:\n";
            foreach ($pending as $file) {
                echo "  - {$file}\n";
            }
        } else {
            echo "\nAll migrations are up to date.\n";
        }
    })(),

    'mark-all' => (function () use ($migrator) {
        $marked = $migrator->markAllAsApplied();
        echo "Marked " . count($marked) . " migrations as applied.\n";
    })(),

    'migrate' => (function () use ($migrator) {
        $pending = $migrator->getPending();
        if (empty($pending)) {
            echo "No pending migrations.\n";
            return;
        }

        echo "Running " . count($pending) . " migration(s)...\n";
        $result = $migrator->migrate();

        foreach ($result['applied'] as $file) {
            echo "  ✓ {$file}\n";
        }

        foreach ($result['errors'] as $file => $error) {
            echo "  ✗ {$file}: {$error}\n";
        }

        if ($result['errors']) {
            echo "\nMigration stopped due to error.\n";
            exit(1);
        }

        echo "\nDone. Applied " . count($result['applied']) . " migration(s).\n";
    })(),

    default => (function () {
        echo "Unknown command. Usage: php migrate.php [status|migrate|mark-all]\n";
        exit(1);
    })(),
};
