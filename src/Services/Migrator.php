<?php

declare(strict_types=1);

namespace TinyShop\Services;

use PDO;

/**
 * Runs SQL migration files and tracks which have been applied.
 */
final class Migrator
{
    private PDO $pdo;
    private string $migrationsDir;

    public function __construct(PDO $pdo, string $migrationsDir)
    {
        $this->pdo = $pdo;
        $this->migrationsDir = rtrim($migrationsDir, '/');
        $this->ensureTable();
    }

    /**
     * Create the migrations tracking table if it doesn't exist.
     */
    private function ensureTable(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS migrations (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                filename VARCHAR(255) NOT NULL UNIQUE,
                applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }

    /**
     * Get list of already-applied migration filenames.
     *
     * @return string[]
     */
    public function getApplied(): array
    {
        $stmt = $this->pdo->query('SELECT filename FROM migrations ORDER BY filename');
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get list of pending migration files (not yet applied).
     *
     * @return string[]
     */
    public function getPending(): array
    {
        $applied = array_flip($this->getApplied());
        $files = glob($this->migrationsDir . '/*.sql');

        if ($files === false) {
            return [];
        }

        $pending = [];
        foreach ($files as $file) {
            $filename = basename($file);
            if (!isset($applied[$filename])) {
                $pending[] = $filename;
            }
        }

        sort($pending);
        return $pending;
    }

    /**
     * Run all pending migrations.
     *
     * @return array{applied: string[], errors: array<string, string>}
     */
    public function migrate(): array
    {
        $pending = $this->getPending();
        $applied = [];
        $errors = [];

        foreach ($pending as $filename) {
            $filepath = $this->migrationsDir . '/' . $filename;
            $sql = file_get_contents($filepath);

            if ($sql === false) {
                $errors[$filename] = 'Could not read file';
                continue;
            }

            $sql = trim($sql);
            if ($sql === '') {
                $errors[$filename] = 'Empty migration file';
                continue;
            }

            try {
                $this->pdo->exec($sql);
                $this->recordMigration($filename);
                $applied[] = $filename;
            } catch (\PDOException $e) {
                $errors[$filename] = $e->getMessage();
                // Stop on first error to prevent running migrations out of order
                break;
            }
        }

        return ['applied' => $applied, 'errors' => $errors];
    }

    /**
     * Mark a single migration as applied (for seeding existing databases).
     */
    public function markAsApplied(string $filename): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT IGNORE INTO migrations (filename) VALUES (?)'
        );
        $stmt->execute([$filename]);
    }

    /**
     * Mark all existing migration files as applied (for initializing on existing databases).
     *
     * @return string[]
     */
    public function markAllAsApplied(): array
    {
        $files = glob($this->migrationsDir . '/*.sql');

        if ($files === false) {
            return [];
        }

        $marked = [];
        foreach ($files as $file) {
            $filename = basename($file);
            $this->markAsApplied($filename);
            $marked[] = $filename;
        }

        sort($marked);
        return $marked;
    }

    private function recordMigration(string $filename): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO migrations (filename) VALUES (?)'
        );
        $stmt->execute([$filename]);
    }
}
