<?php

declare(strict_types=1);

namespace TinyShop\Controllers\Api;

use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use TinyShop\Controllers\Traits\JsonResponder;
use TinyShop\Models\Customer;
use TinyShop\Models\Order;
use TinyShop\Models\OrderItem;
use TinyShop\Models\User;
use TinyShop\Services\Auth;
use TinyShop\Services\Config;
use TinyShop\Services\CustomerAuth;
use TinyShop\Services\DB;
use TinyShop\Services\Hooks;
use TinyShop\Services\Mailer;
use TinyShop\Services\Validation;

/**
 * Customer authentication API controller.
 *
 * @since 1.0.0
 */
final class CustomerAuthController
{
    use JsonResponder;

    private const LOGIN_MAX_ATTEMPTS = 5;
    private const LOGIN_LOCKOUT_SECONDS = 900; // 15 minutes

    private readonly PDO $db;

    public function __construct(
        private readonly Customer $customerModel,
        private readonly Order $orderModel,
        private readonly OrderItem $orderItemModel,
        private readonly CustomerAuth $customerAuth,
        private readonly Validation $validation,
        private readonly User $userModel,
        private readonly Mailer $mailer,
        private readonly Config $config,
        DB $database
    ) {
        $this->db = $database->pdo();
    }

    /**
     * Register a new customer.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function register(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();

        $shopId   = (int) ($data['shop_id'] ?? 0);
        $email    = trim($data['email'] ?? '');
        $phone    = trim($data['phone'] ?? '');
        $password = $data['password'] ?? '';

        if ($shopId === 0) {
            return $this->json($response, ['error' => true, 'message' => 'Invalid shop'], 422);
        }

        if ($email === '' || $password === '') {
            return $this->json($response, ['error' => true, 'message' => 'Email and password are required'], 422);
        }

        // Derive display name from the email local part
        $name = ucfirst(explode('@', $email)[0]);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) {
            return $this->json($response, ['error' => true, 'message' => 'Invalid email address'], 422);
        }

        $passwordError = $this->validation->password($password);
        if ($passwordError !== null) {
            return $this->json($response, ['error' => true, 'message' => $passwordError], 422);
        }

        if ($this->customerModel->emailExists($shopId, $email)) {
            return $this->json($response, ['error' => true, 'message' => 'An account with this email already exists'], 409);
        }

        $customerId = $this->customerModel->create([
            'user_id'       => $shopId,
            'name'          => $name,
            'email'         => $email,
            'phone'         => $phone !== '' ? $phone : null,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        // Backfill existing guest orders matching this email
        $this->orderModel->linkToCustomer($shopId, $email, $customerId);

        $this->customerAuth->login($customerId, $shopId, $name);

        Hooks::doAction('customer.registered', $customerId, $shopId);

        return $this->json($response, ['success' => true], 201);
    }

    /**
     * Log in a customer.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function login(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();

        $shopId   = (int) ($data['shop_id'] ?? 0);
        $email    = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if ($shopId === 0 || $email === '' || $password === '') {
            return $this->json($response, ['error' => true, 'message' => 'Email and password are required'], 422);
        }

        // Cap input lengths: 255 for email (VARCHAR limit), 72 for bcrypt max
        if (mb_strlen($email) > 255 || mb_strlen($password) > 72) {
            return $this->json($response, ['error' => true, 'message' => 'Invalid email or password'], 401);
        }

        $lockRemaining = $this->checkLoginThrottle($shopId, $email);
        if ($lockRemaining !== null) {
            $minutes = (int) ceil($lockRemaining / 60);
            return $this->json($response, [
                'error'   => true,
                'message' => "Too many failed attempts. Try again in {$minutes} minute(s).",
            ], 429);
        }

        $customer = $this->customerModel->findByShopAndEmail($shopId, $email);

        if (!$customer || !password_verify($password, $customer['password_hash'])) {
            $this->recordFailedLogin($shopId, $email);
            return $this->json($response, ['error' => true, 'message' => 'Invalid email or password'], 401);
        }

        if ((int) ($customer['is_active'] ?? 1) === 0) {
            return $this->json($response, ['error' => true, 'message' => 'Account suspended'], 403);
        }

        $this->clearLoginThrottle($shopId, $email);
        $this->customerAuth->login((int) $customer['id'], $shopId, $customer['name']);
        $this->customerModel->recordLogin((int) $customer['id']);

        Hooks::doAction('customer.logged_in', (int) $customer['id'], $shopId);

        return $this->json($response, ['success' => true]);
    }

    /**
     * Log out a customer.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function logout(Request $request, Response $response): Response
    {
        $this->customerAuth->logout();
        return $this->json($response, ['success' => true]);
    }

    /**
     * List the customer's orders.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function orders(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $shopId = (int) ($params['shop_id'] ?? 0);

        if (!$this->customerAuth->check($shopId)) {
            return $this->json($response, ['error' => true, 'message' => 'Not logged in'], 401);
        }

        $customerId = $this->customerAuth->customerId();
        $orders = $this->orderModel->findByCustomer($customerId, 50, 0);

        // Attach items to each order
        $orderIds = array_column($orders, 'id');
        $allItems = [];
        if (!empty($orderIds)) {
            $allItems = $this->orderItemModel->findByOrderIds($orderIds);
        }

        $itemsByOrder = [];
        foreach ($allItems as $item) {
            $itemsByOrder[(int) $item['order_id']][] = $item;
        }

        foreach ($orders as &$order) {
            $order['items'] = $itemsByOrder[(int) $order['id']] ?? [];
        }
        unset($order);

        return $this->json($response, ['success' => true, 'orders' => $orders]);
    }

    /**
     * Update the customer's profile.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function updateProfile(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $shopId = (int) ($data['shop_id'] ?? 0);

        if (!$this->customerAuth->check($shopId)) {
            return $this->json($response, ['error' => true, 'message' => 'Not logged in'], 401);
        }

        $customerId = $this->customerAuth->customerId();
        $name  = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $phone = trim($data['phone'] ?? '');

        if ($name === '' || $email === '') {
            return $this->json($response, ['error' => true, 'message' => 'Name and email are required'], 422);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json($response, ['error' => true, 'message' => 'Invalid email address'], 422);
        }

        if ($this->customerModel->emailExists($shopId, $email, $customerId)) {
            return $this->json($response, ['error' => true, 'message' => 'Email already in use'], 409);
        }

        $this->customerModel->update($customerId, [
            'name'  => $name,
            'email' => $email,
            'phone' => $phone !== '' ? $phone : null,
        ]);

        // Update session name
        $_SESSION['customer_name'] = $name;

        return $this->json($response, ['success' => true]);
    }

    /**
     * Change the customer's password.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function changePassword(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $shopId = (int) ($data['shop_id'] ?? 0);

        if (!$this->customerAuth->check($shopId)) {
            return $this->json($response, ['error' => true, 'message' => 'Not logged in'], 401);
        }

        $customerId     = $this->customerAuth->customerId();
        $currentPassword = $data['current_password'] ?? '';
        $newPassword     = $data['new_password'] ?? '';

        if ($currentPassword === '' || $newPassword === '') {
            return $this->json($response, ['error' => true, 'message' => 'Current and new password are required'], 422);
        }

        $customer = Customer::find($customerId);
        if (!$customer || !password_verify($currentPassword, $customer['password_hash'])) {
            return $this->json($response, ['error' => true, 'message' => 'Current password is incorrect'], 401);
        }

        $passwordError = $this->validation->password($newPassword);
        if ($passwordError !== null) {
            return $this->json($response, ['error' => true, 'message' => $passwordError], 422);
        }

        $this->customerModel->updatePassword($customerId, password_hash($newPassword, PASSWORD_BCRYPT));

        return $this->json($response, ['success' => true]);
    }

    /**
     * Send a password-reset email.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function forgotPassword(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $shopId = (int) ($data['shop_id'] ?? 0);
        $email  = trim($data['email'] ?? '');

        if ($shopId === 0 || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json($response, ['error' => true, 'message' => 'Please enter a valid email address'], 422);
        }

        // Purge expired tokens (1-hour window)
        $this->db->prepare(
            'DELETE FROM customer_password_resets WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR) AND used_at IS NULL'
        )->execute();

        // Always return success to avoid email enumeration
        $customer = $this->customerModel->findByShopAndEmail($shopId, $email);
        if ($customer) {
            $plainToken  = bin2hex(random_bytes(32));
            $hashedToken = hash('sha256', $plainToken);

            // Remove previous tokens for this customer
            $stmt = $this->db->prepare('DELETE FROM customer_password_resets WHERE customer_id = ?');
            $stmt->execute([(int) $customer['id']]);

            $stmt = $this->db->prepare('INSERT INTO customer_password_resets (customer_id, token) VALUES (?, ?)');
            $stmt->execute([(int) $customer['id'], $hashedToken]);

            // Build reset URL pointing to the shop's account page
            $shop = User::find($shopId);
            $shopSlug = $shop['shop_slug'] ?? '';
            $resetUrl = $this->config->url() . '/s/' . $shopSlug . '/account?reset_token=' . $plainToken;

            $this->mailer->sendPasswordReset($email, $customer['name'], $resetUrl);
        }

        return $this->json($response, ['success' => true, 'message' => 'If that email exists, we\'ll send a reset link']);
    }

    /**
     * Reset password using a token.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function resetPassword(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $plainToken  = $data['token'] ?? '';
        $password    = $data['password'] ?? '';

        if ($plainToken === '') {
            return $this->json($response, ['error' => true, 'message' => 'Invalid or missing token'], 422);
        }

        $passwordError = $this->validation->password($password);
        if ($passwordError !== null) {
            return $this->json($response, ['error' => true, 'message' => $passwordError], 422);
        }

        $hashedToken = hash('sha256', $plainToken);

        $stmt = $this->db->prepare(
            'SELECT * FROM customer_password_resets WHERE token = ? AND used_at IS NULL AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR) ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$hashedToken]);
        $reset = $stmt->fetch();

        if (!$reset) {
            return $this->json($response, ['error' => true, 'message' => 'This reset link has expired or is invalid'], 422);
        }

        $customer = Customer::find((int) $reset['customer_id']);
        if (!$customer) {
            return $this->json($response, ['error' => true, 'message' => 'Account not found'], 404);
        }

        $this->customerModel->updatePassword(
            (int) $customer['id'],
            password_hash($password, PASSWORD_BCRYPT)
        );

        // Mark token as used
        $stmt = $this->db->prepare('UPDATE customer_password_resets SET used_at = NOW() WHERE id = ?');
        $stmt->execute([(int) $reset['id']]);

        return $this->json($response, ['success' => true, 'message' => 'Password has been reset. You can now sign in.']);
    }

    // ── Per-email login throttle (file-based, scoped to shop) ──

    private function throttleDir(): string
    {
        return sys_get_temp_dir() . '/tinyshop_customer_throttle';
    }

    private function throttleKey(int $shopId, string $email): string
    {
        return hash('xxh128', $shopId . ':' . strtolower($email));
    }

    private function checkLoginThrottle(int $shopId, string $email): ?int
    {
        $file = $this->throttleDir() . '/' . $this->throttleKey($shopId, $email);
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

    private function recordFailedLogin(int $shopId, string $email): void
    {
        $dir = $this->throttleDir();
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $file = $dir . '/' . $this->throttleKey($shopId, $email);
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

    private function clearLoginThrottle(int $shopId, string $email): void
    {
        @unlink($this->throttleDir() . '/' . $this->throttleKey($shopId, $email));
    }
}
