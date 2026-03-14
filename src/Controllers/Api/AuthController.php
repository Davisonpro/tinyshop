<?php

declare(strict_types=1);

namespace TinyShop\Controllers\Api;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use TinyShop\Controllers\Traits\JsonResponder;
use TinyShop\Models\AuditLog;
use TinyShop\Models\User;
use TinyShop\Enums\UserRole;
use TinyShop\Services\Auth;
use TinyShop\Services\Config;
use TinyShop\Services\Hooks;
use TinyShop\Services\Mailer;
use TinyShop\Services\Validation;
use TinyShop\Services\DB;
use TinyShop\Models\Setting;

/**
 * Authentication API controller.
 *
 * Handles login, registration, password reset, and session checks.
 * Supports both session-based (web) and bearer-token (mobile) auth.
 *
 * @since 1.0.0
 */
final class AuthController
{
    use JsonResponder;

    private readonly \PDO $db;

    private const LOGIN_MAX_ATTEMPTS    = 5;
    private const LOGIN_LOCKOUT_SECONDS = 900; // 15 minutes

    public function __construct(
        private readonly User $userModel,
        private readonly Auth $auth,
        private readonly Mailer $mailer,
        private readonly Config $config,
        private readonly Validation $validation,
        private readonly Setting $setting,
        private readonly LoggerInterface $logger,
        private readonly AuditLog $auditLog,
        DB $database,
    ) {
        $this->db = $database->pdo();
    }

    // ── Registration ─────────────────────────────────────────

    public function register(Request $request, Response $response): Response
    {
        if ($this->setting->get('allow_registration', '1') !== '1') {
            return $this->json($response, ['error' => true, 'message' => 'Registration is currently disabled'], 403);
        }

        $data      = (array) $request->getParsedBody();
        $email     = trim($data['email'] ?? '');
        $password  = $data['password'] ?? '';
        $storeName = trim($data['store_name'] ?? '');
        $ip        = $this->ip($request);

        // ── Validation ──
        if ($email === '' || $password === '') {
            return $this->json($response, ['error' => true, 'message' => 'Email and password are required'], 422);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json($response, ['error' => true, 'message' => 'Invalid email address'], 422);
        }
        if ($err = $this->validation->maxLength($email, 'email')) {
            return $this->json($response, ['error' => true, 'message' => $err], 422);
        }
        if ($err = $this->validation->password($password)) {
            return $this->json($response, ['error' => true, 'message' => $err], 422);
        }
        if ($storeName !== '' && ($err = $this->validation->maxLength($storeName, 'store_name'))) {
            return $this->json($response, ['error' => true, 'message' => $err], 422);
        }
        if (User::findBy('email', $email)) {
            return $this->json($response, ['error' => true, 'message' => 'Email already registered'], 409);
        }

        // ── Defaults ──
        if ($storeName === '') {
            $prefix    = strstr($email, '@', true);
            $storeName = ucfirst(preg_replace('/[^a-zA-Z0-9]/', ' ', $prefix)) . "'s Shop";
        }

        $subdomain = $this->resolveUniqueSubdomain($storeName);

        // ── Create user ──
        $userId = $this->userModel->create([
            'email'         => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'role'          => UserRole::Seller->value,
            'store_name'    => $storeName,
            'subdomain'     => $subdomain,
        ]);

        $this->auth->login($userId, $storeName, UserRole::Seller);

        $this->logger->info('auth.register', ['user_id' => $userId, 'email' => $email, 'ip' => $ip]);
        $this->auditLog->log('user.register', $userId, 'user', $userId, ['email' => $email]);
        Hooks::doAction('user.registered', $userId);

        $user = [
            'id'         => $userId,
            'email'      => $email,
            'role'       => UserRole::Seller->value,
            'store_name' => $storeName,
            'subdomain'  => $subdomain,
        ];

        return $this->json($response, [
            'success'  => true,
            'redirect' => '/dashboard',
            'token'    => $this->userModel->generateApiToken($userId),
            'user'     => $user,
        ], 201);
    }

    // ── Login ────────────────────────────────────────────────

    public function login(Request $request, Response $response): Response
    {
        $data     = (array) $request->getParsedBody();
        $email    = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $ip       = $this->ip($request);

        // ── Validation ──
        if ($email === '' || $password === '') {
            return $this->json($response, ['error' => true, 'message' => 'Email and password are required'], 422);
        }
        if (mb_strlen($email) > 255 || mb_strlen($password) > 72) {
            return $this->json($response, ['error' => true, 'message' => 'Invalid credentials'], 401);
        }

        // ── Brute-force throttle ──
        $lockRemaining = $this->checkLoginThrottle($email);
        if ($lockRemaining !== null) {
            $this->logger->warning('auth.login_blocked', ['email' => $email, 'ip' => $ip, 'locked_for' => $lockRemaining]);
            $minutes = (int) ceil($lockRemaining / 60);
            return $this->json($response, [
                'error'   => true,
                'message' => "Too many failed attempts. Try again in {$minutes} minute(s).",
            ], 429);
        }

        // ── Credential check ──
        $user = User::findBy('email', $email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->recordFailedLogin($email);
            $this->logger->warning('auth.login_failed', ['email' => $email, 'ip' => $ip]);
            return $this->json($response, ['error' => true, 'message' => 'Invalid credentials'], 401);
        }

        if (isset($user['is_active']) && (int) $user['is_active'] === 0) {
            $this->logger->warning('auth.login_suspended', ['user_id' => $user['id'], 'ip' => $ip]);
            return $this->json($response, ['error' => true, 'message' => 'Account suspended'], 403);
        }

        // ── Success ──
        $this->clearLoginThrottle($email);

        $role   = UserRole::tryFrom($user['role'] ?? '') ?? UserRole::Seller;
        $userId = (int) $user['id'];

        $this->auth->login($userId, $user['store_name'] ?? '', $role);
        $this->userModel->recordLogin($userId);

        $this->logger->info('auth.login_success', ['user_id' => $userId, 'ip' => $ip]);
        $this->auditLog->log('user.login', $userId, 'user', $userId, ['email' => $email]);
        Hooks::doAction('user.logged_in', $userId);

        return $this->json($response, [
            'success'  => true,
            'redirect' => $role === UserRole::Admin ? '/admin' : '/dashboard',
            'token'    => $this->userModel->generateApiToken($userId),
            'user'     => [
                'id'         => $userId,
                'email'      => $user['email'],
                'role'       => $role->value,
                'store_name' => $user['store_name'] ?? '',
                'subdomain'  => $user['subdomain'] ?? '',
            ],
        ]);
    }

    // ── Logout ───────────────────────────────────────────────

    public function logout(Request $request, Response $response): Response
    {
        $userId = $this->auth->userId();

        if ($userId !== null) {
            $this->userModel->clearApiToken($userId);
            $this->auditLog->log('user.logout', $userId, 'user', $userId);
        }

        $this->auth->logout();

        return $this->json($response, ['success' => true]);
    }

    // ── Auth check ───────────────────────────────────────────

    public function check(Request $request, Response $response): Response
    {
        Auth::ensureSession();

        // Resolve identity: bearer token first, then session
        $user     = $this->resolveBearerUser($request);
        $loggedIn = $user !== null || $this->auth->check();

        if (!isset($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        $result = [
            'logged_in' => $loggedIn,
            'csrf'      => $_SESSION['_csrf_token'],
        ];

        // Attach user data when authenticated via bearer
        if ($user !== null) {
            $result['user'] = $this->serializeUser($user);
        }

        return $this->json($response, $result);
    }

    // ── Forgot password ──────────────────────────────────────

    public function forgotPassword(Request $request, Response $response): Response
    {
        $data  = (array) $request->getParsedBody();
        $email = trim($data['email'] ?? '');

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) {
            return $this->json($response, ['error' => true, 'message' => 'Please enter a valid email address'], 422);
        }

        // Purge expired tokens
        $this->db->prepare(
            'DELETE FROM password_resets WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR) AND used_at IS NULL'
        )->execute();

        // Always return success to prevent email enumeration
        $user = User::findBy('email', $email);
        if ($user) {
            $plainToken  = bin2hex(random_bytes(32));
            $hashedToken = hash('sha256', $plainToken);

            $this->db->prepare('DELETE FROM password_resets WHERE email = ?')->execute([$email]);
            $this->db->prepare('INSERT INTO password_resets (email, token) VALUES (?, ?)')->execute([$email, $hashedToken]);

            $resetUrl = $this->config->url() . '/reset-password?token=' . $plainToken;
            $this->mailer->sendPasswordReset($email, $user['store_name'] ?? '', $resetUrl);

            $this->logger->info('auth.forgot_password', ['email' => $email, 'ip' => $this->ip($request)]);
        }

        return $this->json($response, ['success' => true, 'message' => 'If that email exists, we sent a reset link']);
    }

    // ── Reset password ───────────────────────────────────────

    public function resetPassword(Request $request, Response $response): Response
    {
        $data            = (array) $request->getParsedBody();
        $plainToken      = $data['token'] ?? '';
        $password        = $data['password'] ?? '';
        $passwordConfirm = $data['password_confirm'] ?? '';

        if (!$plainToken || mb_strlen($plainToken) > 128) {
            return $this->json($response, ['error' => true, 'message' => 'Invalid or missing token'], 422);
        }
        if ($err = $this->validation->password($password)) {
            return $this->json($response, ['error' => true, 'message' => $err], 422);
        }
        if ($password !== $passwordConfirm) {
            return $this->json($response, ['error' => true, 'message' => 'Passwords do not match'], 422);
        }

        $hashedToken = hash('sha256', $plainToken);

        $stmt = $this->db->prepare(
            'SELECT * FROM password_resets WHERE token = ? AND used_at IS NULL AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR) ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$hashedToken]);
        $reset = $stmt->fetch();

        if (!$reset) {
            return $this->json($response, ['error' => true, 'message' => 'This reset link has expired or is invalid'], 422);
        }

        $user = User::findBy('email', $reset['email']);
        if (!$user) {
            return $this->json($response, ['error' => true, 'message' => 'Account not found'], 404);
        }

        $userId = (int) $user['id'];
        $this->userModel->updatePassword($userId, password_hash($password, PASSWORD_BCRYPT));

        $this->db->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ?')->execute([$reset['id']]);

        $this->logger->info('auth.password_reset', ['user_id' => $userId, 'email' => $reset['email'], 'ip' => $this->ip($request)]);
        $this->auditLog->log('user.password_reset', $userId, 'user', $userId, ['email' => $reset['email']]);

        return $this->json($response, ['success' => true, 'message' => 'Password reset successfully']);
    }

    // ── Private helpers ──────────────────────────────────────

    private function ip(Request $request): string
    {
        return $request->getServerParams()['REMOTE_ADDR'] ?? '';
    }

    private function resolveBearerUser(Request $request): ?array
    {
        $header = $request->getHeaderLine('Authorization');
        if (!str_starts_with($header, 'Bearer ')) {
            return null;
        }

        $token = substr($header, 7);
        if ($token === '' || strlen($token) !== 64) {
            return null;
        }

        return $this->userModel->findByApiToken($token);
    }

    /**
     * Serialize a user row into a safe public shape.
     *
     * @param array<string, mixed> $user Raw DB row.
     * @return array<string, mixed>
     */
    private function serializeUser(array $user): array
    {
        return [
            'id'         => (int) $user['id'],
            'email'      => $user['email'],
            'role'       => $user['role'] ?? UserRole::Seller->value,
            'store_name' => $user['store_name'] ?? '',
            'subdomain'  => $user['subdomain'] ?? '',
        ];
    }

    private function resolveUniqueSubdomain(string $storeName): string
    {
        $subdomain = $this->validation->slug($storeName);
        [$subdomain, $error] = $this->validation->subdomain($subdomain);
        if ($error !== null) {
            $subdomain .= '-' . bin2hex(random_bytes(2));
        }

        $base    = $subdomain;
        $counter = 1;
        while (User::exists('subdomain', $subdomain)) {
            $subdomain = $base . '-' . $counter++;
        }

        return $subdomain;
    }

    // ── Per-email login throttle (file-based) ────────────────

    private function throttleDir(): string
    {
        return sys_get_temp_dir() . '/tinyshop_login_throttle';
    }

    private function checkLoginThrottle(string $email): ?int
    {
        $file = $this->throttleDir() . '/' . hash('xxh128', strtolower($email));
        if (!is_file($file)) {
            return null;
        }

        $data = @json_decode((string) @file_get_contents($file), true);
        if (!$data) {
            return null;
        }

        $lockUntil = $data['locked_until'] ?? 0;
        if ($lockUntil > time()) {
            return $lockUntil - time();
        }

        if (($data['attempts'] ?? 0) >= self::LOGIN_MAX_ATTEMPTS) {
            @unlink($file);
        }

        return null;
    }

    private function recordFailedLogin(string $email): void
    {
        $dir = $this->throttleDir();
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $file = $dir . '/' . hash('xxh128', strtolower($email));

        $data = ['attempts' => 0, 'locked_until' => 0];
        if (is_file($file)) {
            $data = @json_decode((string) @file_get_contents($file), true) ?: $data;
        }

        $data['attempts']++;
        if ($data['attempts'] >= self::LOGIN_MAX_ATTEMPTS) {
            $data['locked_until'] = time() + self::LOGIN_LOCKOUT_SECONDS;
        }

        @file_put_contents($file, json_encode($data), LOCK_EX);
    }

    private function clearLoginThrottle(string $email): void
    {
        @unlink($this->throttleDir() . '/' . hash('xxh128', strtolower($email)));
    }
}
