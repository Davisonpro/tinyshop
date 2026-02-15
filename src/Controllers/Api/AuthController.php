<?php

declare(strict_types=1);

namespace TinyShop\Controllers\Api;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use TinyShop\Controllers\Traits\JsonResponder;
use TinyShop\Models\User;
use TinyShop\Enums\UserRole;
use TinyShop\Services\Auth;
use TinyShop\Services\Config;
use TinyShop\Services\Hooks;
use TinyShop\Services\Mailer;
use TinyShop\Services\Validation;
use TinyShop\Services\DB;
use TinyShop\Models\Setting;

final class AuthController
{
    use JsonResponder;

    private readonly \PDO $db;

    private const LOGIN_MAX_ATTEMPTS = 5;
    private const LOGIN_LOCKOUT_SECONDS = 900; // 15 minutes

    public function __construct(
        private readonly User $userModel,
        private readonly Auth $auth,
        private readonly Mailer $mailer,
        private readonly Config $config,
        private readonly Validation $validation,
        private readonly Setting $setting,
        private readonly LoggerInterface $logger,
        DB $database
    ) {
        $this->db = $database->pdo();
    }

    public function register(Request $request, Response $response): Response
    {
        if ($this->setting->get('allow_registration', '1') !== '1') {
            return $this->json($response, ['error' => true, 'message' => 'Registration is currently disabled'], 403);
        }

        $data = (array) $request->getParsedBody();

        $email     = trim($data['email'] ?? '');
        $password  = $data['password'] ?? '';
        $name      = trim($data['name'] ?? '');
        $storeName = trim($data['store_name'] ?? '');

        if ($email === '' || $password === '') {
            return $this->json($response, ['error' => true, 'message' => 'Email and password are required'], 422);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json($response, ['error' => true, 'message' => 'Invalid email address'], 422);
        }

        if ($err = $this->validation->maxLength($email, 'email')) {
            return $this->json($response, ['error' => true, 'message' => $err], 422);
        }

        $passwordError = $this->validation->password($password);
        if ($passwordError !== null) {
            return $this->json($response, ['error' => true, 'message' => $passwordError], 422);
        }

        if ($name !== '' && ($err = $this->validation->maxLength($name, 'name'))) {
            return $this->json($response, ['error' => true, 'message' => $err], 422);
        }

        if ($storeName !== '' && ($err = $this->validation->maxLength($storeName, 'store_name'))) {
            return $this->json($response, ['error' => true, 'message' => $err], 422);
        }

        if ($this->userModel->findByEmail($email)) {
            return $this->json($response, ['error' => true, 'message' => 'Email already registered'], 409);
        }

        $emailPrefix = strstr($email, '@', true);
        if ($name === '') {
            $name = ucfirst(preg_replace('/[^a-zA-Z0-9]/', ' ', $emailPrefix));
        }
        if ($storeName === '') {
            $storeName = $name . "'s Shop";
        }

        $subdomain = $this->validation->slug($storeName);
        [$subdomain, $subdomainError] = $this->validation->subdomain($subdomain);
        if ($subdomainError !== null) {
            $subdomain = $subdomain . '-' . bin2hex(random_bytes(2));
        }
        $baseSubdomain = $subdomain;
        $counter = 1;
        while ($this->userModel->subdomainExists($subdomain)) {
            $subdomain = $baseSubdomain . '-' . $counter++;
        }

        $userId = $this->userModel->create([
            'name'          => $name,
            'email'         => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'role'          => UserRole::Seller->value,
            'store_name'    => $storeName,
            'subdomain'     => $subdomain,
        ]);

        $this->auth->login($userId, $name, UserRole::Seller);

        $this->logger->info('auth.register', [
            'user_id' => $userId,
            'email'   => $email,
            'ip'      => $request->getServerParams()['REMOTE_ADDR'] ?? '',
        ]);

        Hooks::doAction('user.registered', $userId);

        return $this->json($response, [
            'success'  => true,
            'redirect' => '/dashboard',
        ], 201);
    }

    public function login(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();

        $email    = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $ip       = $request->getServerParams()['REMOTE_ADDR'] ?? '';

        if ($email === '' || $password === '') {
            return $this->json($response, ['error' => true, 'message' => 'Email and password are required'], 422);
        }

        // Per-email brute force check
        $lockRemaining = $this->checkLoginThrottle($email);
        if ($lockRemaining !== null) {
            $this->logger->warning('auth.login_blocked', ['email' => $email, 'ip' => $ip, 'locked_for' => $lockRemaining]);
            $minutes = (int) ceil($lockRemaining / 60);
            return $this->json($response, [
                'error'   => true,
                'message' => "Too many failed attempts. Try again in {$minutes} minute(s).",
            ], 429);
        }

        $user = $this->userModel->findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->recordFailedLogin($email);
            $this->logger->warning('auth.login_failed', ['email' => $email, 'ip' => $ip]);
            return $this->json($response, ['error' => true, 'message' => 'Invalid credentials'], 401);
        }

        if (isset($user['is_active']) && (int) $user['is_active'] === 0) {
            $this->logger->warning('auth.login_suspended', ['user_id' => $user['id'], 'ip' => $ip]);
            return $this->json($response, ['error' => true, 'message' => 'Account suspended'], 403);
        }

        // Clear throttle on successful login
        $this->clearLoginThrottle($email);

        $role = UserRole::tryFrom($user['role'] ?? '') ?? UserRole::Seller;
        $this->auth->login((int) $user['id'], $user['name'], $role);
        $this->userModel->recordLogin((int) $user['id']);

        $this->logger->info('auth.login_success', ['user_id' => $user['id'], 'ip' => $ip]);

        Hooks::doAction('user.logged_in', (int) $user['id']);

        $redirect = $role === UserRole::Admin ? '/admin' : '/dashboard';

        return $this->json($response, [
            'success'  => true,
            'redirect' => $redirect,
        ]);
    }

    public function logout(Request $request, Response $response): Response
    {
        $this->auth->logout();
        return $this->json($response, ['success' => true]);
    }

    public function forgotPassword(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $email = trim($data['email'] ?? '');

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json($response, ['error' => true, 'message' => 'Please enter a valid email address'], 422);
        }

        // Purge expired tokens on every request (lightweight cleanup)
        $this->db->prepare(
            'DELETE FROM password_resets WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR) AND used_at IS NULL'
        )->execute();

        // Always return success to avoid email enumeration
        $user = $this->userModel->findByEmail($email);
        if ($user) {
            $plainToken = bin2hex(random_bytes(32));
            $hashedToken = hash('sha256', $plainToken);

            $stmt = $this->db->prepare('DELETE FROM password_resets WHERE email = ?');
            $stmt->execute([$email]);

            $stmt = $this->db->prepare('INSERT INTO password_resets (email, token) VALUES (?, ?)');
            $stmt->execute([$email, $hashedToken]);

            $resetUrl = $this->config->url() . '/reset-password?token=' . $plainToken;

            $this->mailer->sendPasswordReset($email, $user['store_name'] ?? $user['name'], $resetUrl);

            $this->logger->info('auth.forgot_password', [
                'email' => $email,
                'ip'    => $request->getServerParams()['REMOTE_ADDR'] ?? '',
            ]);
        }

        return $this->json($response, ['success' => true, 'message' => 'If that email exists, we sent a reset link']);
    }

    public function resetPassword(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $plainToken = $data['token'] ?? '';
        $password = $data['password'] ?? '';
        $passwordConfirm = $data['password_confirm'] ?? '';

        if (!$plainToken) {
            return $this->json($response, ['error' => true, 'message' => 'Invalid or missing token'], 422);
        }

        $passwordError = $this->validation->password($password);
        if ($passwordError !== null) {
            return $this->json($response, ['error' => true, 'message' => $passwordError], 422);
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

        $user = $this->userModel->findByEmail($reset['email']);
        if (!$user) {
            return $this->json($response, ['error' => true, 'message' => 'Account not found'], 404);
        }

        $this->userModel->updatePassword((int) $user['id'], password_hash($password, PASSWORD_BCRYPT));

        $stmt = $this->db->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ?');
        $stmt->execute([$reset['id']]);

        $this->logger->info('auth.password_reset', [
            'user_id' => $user['id'],
            'email'   => $reset['email'],
            'ip'      => $request->getServerParams()['REMOTE_ADDR'] ?? '',
        ]);

        return $this->json($response, ['success' => true, 'message' => 'Password reset successfully']);
    }

    // ── Per-email login throttle (file-based) ──

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

        // Lock expired — clear
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
