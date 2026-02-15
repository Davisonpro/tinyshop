<?php
/**
 * TinyShop — Platform Setup
 *
 * One-time setup for the TinyShop SaaS platform.
 * Self-contained OOP installer — no framework dependencies.
 * Delete this file after setup for security.
 */

declare(strict_types=1);

// ─── Installer Class ─────────────────────────────────────────────────────────

final class Installer
{
    private string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;
    }

    // ── Guard ──

    public function isInstalled(): bool
    {
        return file_exists($this->basePath . '/config/.installed');
    }

    // ── Actions ──

    public function checkRequirements(): array
    {
        $checks = [];

        $checks[] = $this->check('PHP 8.1+', 'Running ' . PHP_VERSION, version_compare(PHP_VERSION, '8.1.0', '>='));

        foreach (['pdo', 'pdo_mysql', 'json', 'mbstring', 'fileinfo'] as $ext) {
            $loaded = extension_loaded($ext);
            $checks[] = $this->check("ext-{$ext}", $loaded ? 'Loaded' : 'Missing', $loaded);
        }

        $dirs = [
            'var/compiled'   => $this->basePath . '/var/compiled',
            'var/cache'      => $this->basePath . '/var/cache',
            'public/uploads' => $this->basePath . '/public/uploads',
            'config/'        => $this->basePath . '/config',
        ];

        foreach ($dirs as $label => $path) {
            $writable = is_dir($path) && is_writable($path);
            $checks[] = $this->check($label, $writable ? 'Writable' : 'Not writable', $writable);
        }

        $composerOk = file_exists($this->basePath . '/vendor/autoload.php');
        $checks[] = $this->check('Composer dependencies', $composerOk ? 'Installed' : 'Run composer install', $composerOk);

        $allPass = !in_array(false, array_column($checks, 'pass'), true);

        return ['ok' => true, 'checks' => $checks, 'allPass' => $allPass];
    }

    public function testDatabase(array $data): array
    {
        $creds = $this->parseDbCredentials($data);

        if ($creds['name'] === '') {
            return $this->fail('Database name is required');
        }

        if (strlen($creds['name']) > 64) {
            return $this->fail('Database name too long');
        }

        try {
            $pdo = $this->connectWithoutDb($creds);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$creds['name']}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$creds['name']}`");

            return ['ok' => true, 'message' => 'Connection successful'];
        } catch (PDOException $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function setupDatabase(array $data): array
    {
        $test = $this->testDatabase($data);
        if (!$test['ok']) {
            return $test;
        }

        $configResult = $this->writeDatabaseConfig($data);
        if (!$configResult['ok']) {
            return $configResult;
        }

        return $this->createTables($data);
    }

    public function createAdmin(array $data): array
    {
        $email    = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if ($email === '' || $password === '') {
            return $this->fail('Email and password are required');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->fail('Invalid email address');
        }

        if (strlen($password) < 6) {
            return $this->fail('Password must be at least 6 characters');
        }

        $dbConfig = require $this->basePath . '/config/database.php';

        try {
            $pdo = $this->connectToDb($dbConfig);

            $prefix = strstr($email, '@', true);
            $name   = ucfirst(preg_replace('/[^a-zA-Z0-9]/', ' ', $prefix));

            $stmt = $pdo->prepare(
                'INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_BCRYPT), 'admin']);

            // Write environment config
            $this->writeEnvConfig($dbConfig);

            // Write lock file
            file_put_contents($this->basePath . '/config/.installed', date('Y-m-d H:i:s'));

            return ['ok' => true, 'message' => 'Admin created'];
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                return $this->fail('That email is already registered');
            }
            return $this->fail($e->getMessage());
        }
    }

    // ── Routing ──

    public function handleRequest(): void
    {
        if ($this->isInstalled()) {
            header('Location: /');
            exit;
        }

        $this->sendSecurityHeaders();

        $action = $_POST['action'] ?? null;

        if ($action && $_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            echo json_encode($this->dispatch($action));
            exit;
        }

        $this->renderPage();
    }

    // ── Private helpers ──

    private function dispatch(string $action): array
    {
        return match ($action) {
            'check'        => $this->checkRequirements(),
            'test_db'      => $this->testDatabase($_POST),
            'setup_db'     => $this->setupDatabase($_POST),
            'create_admin' => $this->createAdmin($_POST),
            default        => $this->fail('Unknown action'),
        };
    }

    private function sendSecurityHeaders(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: no-referrer');
    }

    private function check(string $label, string $detail, bool $pass): array
    {
        return compact('label', 'detail', 'pass');
    }

    private function fail(string $message): array
    {
        return ['ok' => false, 'message' => $message];
    }

    private function sanitizeIdentifier(string $value): string
    {
        return preg_replace('/[^a-zA-Z0-9_\-]/', '', $value);
    }

    private function parseDbCredentials(array $data): array
    {
        return [
            'host' => trim($data['db_host'] ?? 'localhost'),
            'port' => (int) ($data['db_port'] ?? 3306),
            'name' => $this->sanitizeIdentifier(trim($data['db_name'] ?? '')),
            'user' => trim($data['db_user'] ?? ''),
            'pass' => $data['db_pass'] ?? '',
        ];
    }

    private function connectWithoutDb(array $creds): PDO
    {
        $dsn = "mysql:host={$creds['host']};port={$creds['port']};charset=utf8mb4";
        return new PDO($dsn, $creds['user'], $creds['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]);
    }

    private function connectToDb(array $config): PDO
    {
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}";
        return new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }

    private function writeDatabaseConfig(array $data): array
    {
        $creds = $this->parseDbCredentials($data);

        $config = "<?php\n\ndeclare(strict_types=1);\n\nreturn [\n"
            . "    'host'     => " . var_export($creds['host'], true) . ",\n"
            . "    'port'     => {$creds['port']},\n"
            . "    'dbname'   => " . var_export($creds['name'], true) . ",\n"
            . "    'username' => " . var_export($creds['user'], true) . ",\n"
            . "    'password' => " . var_export($creds['pass'], true) . ",\n"
            . "    'charset'  => 'utf8mb4',\n"
            . "];\n";

        $ok = file_put_contents($this->basePath . '/config/database.php', $config);

        return $ok !== false
            ? ['ok' => true]
            : $this->fail('Could not write config/database.php — check permissions.');
    }

    private function writeEnvConfig(array $dbConfig): void
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $appUrl = $scheme . '://' . $host;
        // Base domain: strip port if present
        $baseDomain = explode(':', $host)[0];

        $config = "<?php\n\ndeclare(strict_types=1);\n\nreturn [\n"
            . "    'APP_NAME'        => 'TinyShop',\n"
            . "    'APP_URL'         => " . var_export($appUrl, true) . ",\n"
            . "    'APP_BASE_DOMAIN' => " . var_export($baseDomain, true) . ",\n"
            . "    'APP_DEBUG'       => false,\n"
            . "\n"
            . "    'DB_HOST'    => " . var_export($dbConfig['host'], true) . ",\n"
            . "    'DB_PORT'    => " . var_export((int) $dbConfig['port'], true) . ",\n"
            . "    'DB_NAME'    => " . var_export($dbConfig['dbname'], true) . ",\n"
            . "    'DB_USERNAME'=> " . var_export($dbConfig['username'], true) . ",\n"
            . "    'DB_PASSWORD'=> " . var_export($dbConfig['password'], true) . ",\n"
            . "    'DB_CHARSET' => 'utf8mb4',\n"
            . "\n"
            . "    'OAUTH_GOOGLE_ENABLED'       => false,\n"
            . "    'OAUTH_GOOGLE_CLIENT_ID'     => '',\n"
            . "    'OAUTH_GOOGLE_CLIENT_SECRET' => '',\n"
            . "\n"
            . "    'OAUTH_INSTAGRAM_ENABLED'       => false,\n"
            . "    'OAUTH_INSTAGRAM_CLIENT_ID'     => '',\n"
            . "    'OAUTH_INSTAGRAM_CLIENT_SECRET' => '',\n"
            . "\n"
            . "    'OAUTH_TIKTOK_ENABLED'       => false,\n"
            . "    'OAUTH_TIKTOK_CLIENT_KEY'    => '',\n"
            . "    'OAUTH_TIKTOK_CLIENT_SECRET' => '',\n"
            . "];\n";

        @file_put_contents($this->basePath . '/config/env.php', $config);
    }

    private function createTables(array $data): array
    {
        $creds = $this->parseDbCredentials($data);

        try {
            $dsn = "mysql:host={$creds['host']};port={$creds['port']};dbname={$creds['name']};charset=utf8mb4";
            $pdo = new PDO($dsn, $creds['user'], $creds['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            $schema = file_get_contents($this->basePath . '/schema.sql');
            $schema = preg_replace('/CREATE DATABASE.*?;/is', '', $schema);
            $schema = preg_replace('/USE\s+`[^`]+`;/i', '', $schema);

            $statements = array_filter(array_map('trim', explode(';', $schema)), fn($s) => $s !== '');

            foreach ($statements as $sql) {
                $pdo->exec($sql);
            }

            return ['ok' => true, 'message' => 'Tables created'];
        } catch (PDOException $e) {
            return $this->fail($e->getMessage());
        }
    }

    // ── Render ──

    private function renderPage(): void
    {
        require __DIR__ . '/templates/install.html';
    }
}

// ─── Boot ────────────────────────────────────────────────────────────────────

(new Installer(__DIR__))->handleRequest();
