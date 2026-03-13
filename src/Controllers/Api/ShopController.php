<?php

declare(strict_types=1);

namespace TinyShop\Controllers\Api;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use TinyShop\Controllers\Traits\JsonResponder;
use TinyShop\Models\AuditLog;
use TinyShop\Models\ThemeOption;
use TinyShop\Models\User;
use TinyShop\Services\Auth;
use TinyShop\Services\HestiaCP;
use TinyShop\Services\Hooks;
use TinyShop\Services\PlanGuard;
use TinyShop\Services\Theme;
use TinyShop\Services\Upload;
use TinyShop\Services\Validation;
use TinyShop\Services\View;

/**
 * Shop settings API controller.
 *
 * @since 1.0.0
 */
final class ShopController
{
    use JsonResponder;
    public function __construct(
        private readonly User $userModel,
        private readonly Auth $auth,
        private readonly Validation $validation,
        private readonly Upload $upload,
        private readonly PlanGuard $planGuard,
        private readonly Theme $themeService,
        private readonly ThemeOption $themeOptionModel,
        private readonly View $view,
        private readonly LoggerInterface $logger,
        private readonly AuditLog $auditLog,
        private readonly HestiaCP $hestiaCP
    ) {}

    /**
     * Get the seller's shop profile.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function get(Request $request, Response $response): Response
    {
        $user = User::find($this->auth->userId());
        unset($user['password_hash']);
        return $this->json($response, ['shop' => $user]);
    }

    /**
     * Update shop settings.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function update(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $userId = $this->auth->userId();

        // Input length checks
        foreach (['store_name' => 'store_name', 'shop_tagline' => 'shop_tagline', 'name' => 'name'] as $field => $rule) {
            if (isset($data[$field]) && ($err = $this->validation->maxLength(trim($data[$field]), $rule))) {
                return $this->json($response, ['error' => true, 'message' => $err], 422);
            }
        }

        // Validate currency if being changed
        $validCurrencies = ['KES', 'USD', 'NGN', 'TZS', 'UGX', 'ZAR', 'GHS', 'GBP', 'EUR', 'RWF', 'ETB', 'XOF'];
        if (isset($data['currency']) && !in_array($data['currency'], $validCurrencies, true)) {
            return $this->json($response, ['error' => true, 'message' => 'Invalid currency'], 422);
        }

        // Validate theme if being changed
        if (isset($data['shop_theme'])) {
            if ($this->themeService->loadManifest($data['shop_theme']) === null) {
                return $this->json($response, ['error' => true, 'message' => 'Invalid theme'], 422);
            }
            if (!$this->planGuard->canUseTheme($userId, $data['shop_theme'])) {
                return $this->json($response, ['error' => true, 'message' => 'Your plan doesn\'t include this theme. Upgrade to unlock it.'], 403);
            }
        }

        // Validate color palette
        $validPalettes = ['default', 'ocean', 'forest', 'sunset', 'lavender', 'cherry', 'sage', 'midnight', 'mocha', 'blush'];
        if (isset($data['color_palette']) && !in_array($data['color_palette'], $validPalettes, true)) {
            return $this->json($response, ['error' => true, 'message' => 'Invalid color palette'], 422);
        }

        // Validate logo alignment
        if (isset($data['logo_alignment']) && !in_array($data['logo_alignment'], ['left', 'centered'], true)) {
            return $this->json($response, ['error' => true, 'message' => 'Invalid logo alignment'], 422);
        }

        // Validate product image fit
        if (isset($data['product_image_fit']) && !in_array($data['product_image_fit'], ['cover', 'contain'], true)) {
            return $this->json($response, ['error' => true, 'message' => 'Invalid image fit value'], 422);
        }

        // Validate payment modes (per-gateway)
        foreach (['payment_mode', 'stripe_mode', 'paypal_mode', 'mpesa_mode', 'pesapal_mode'] as $modeField) {
            if (isset($data[$modeField]) && !in_array($data[$modeField], ['test', 'live'], true)) {
                $data[$modeField] = 'test';
            }
        }

        // Sanitize gateway enabled flags
        foreach (['stripe_enabled', 'paypal_enabled', 'cod_enabled', 'mpesa_enabled', 'pesapal_enabled'] as $enabledField) {
            if (isset($data[$enabledField])) {
                $data[$enabledField] = $data[$enabledField] ? 1 : 0;
            }
        }

        // Sanitize design toggle flags
        foreach (['show_logo', 'show_store_name', 'show_tagline', 'show_search', 'show_categories', 'show_sort_toolbar', 'show_desktop_footer'] as $toggleField) {
            if (isset($data[$toggleField])) {
                $data[$toggleField] = $data[$toggleField] ? 1 : 0;
            }
        }

        // Sanitize announcement text
        if (isset($data['announcement_text'])) {
            $text = trim($data['announcement_text']);
            $data['announcement_text'] = $text === '' ? null : mb_substr($text, 0, 500);
        }

        // Sanitize verification codes (alphanumeric + hyphens/underscores only)
        foreach (['google_verification', 'bing_verification'] as $verField) {
            if (isset($data[$verField])) {
                $code = trim($data[$verField]);
                $data[$verField] = $code === '' ? null : mb_substr(preg_replace('/[^a-zA-Z0-9_\-]/', '', $code), 0, 100);
            }
        }

        // Trim payment credential fields
        foreach (['stripe_public_key', 'stripe_secret_key', 'paypal_client_id', 'paypal_secret',
                  'mpesa_shortcode', 'mpesa_consumer_key', 'mpesa_consumer_secret', 'mpesa_passkey',
                  'pesapal_consumer_key', 'pesapal_consumer_secret'] as $payField) {
            if (isset($data[$payField])) {
                $data[$payField] = trim($data[$payField]) ?: null;
            }
        }

        // Sanitize phone numbers
        foreach (['contact_whatsapp', 'contact_phone'] as $phoneField) {
            if (isset($data[$phoneField]) && $data[$phoneField] !== '') {
                [$clean, $phoneError] = $this->validation->phone($data[$phoneField]);
                if ($phoneError !== null) {
                    return $this->json($response, ['error' => true, 'message' => $phoneError], 422);
                }
                $data[$phoneField] = $clean;
            }
        }

        // Validate email format
        if (!empty($data['contact_email'])) {
            if (!filter_var($data['contact_email'], FILTER_VALIDATE_EMAIL)) {
                return $this->json($response, ['error' => true, 'message' => 'Please enter a valid email address'], 422);
            }
            if ($err = $this->validation->maxLength($data['contact_email'], 'email')) {
                return $this->json($response, ['error' => true, 'message' => $err], 422);
            }
        }

        // Validate subdomain if being changed
        if (!empty($data['subdomain'])) {
            [$subdomain, $subdomainError] = $this->validation->subdomain($data['subdomain']);
            if ($subdomainError !== null) {
                return $this->json($response, ['error' => true, 'message' => $subdomainError], 422);
            }
            $data['subdomain'] = $subdomain;

            if (User::exists('subdomain', $subdomain, $userId)) {
                return $this->json($response, ['error' => true, 'message' => 'This shop URL is already taken'], 409);
            }
        }

        // Validate custom domain if being changed
        $domainAdded = null;
        $domainRemoved = null;

        if (array_key_exists('custom_domain', $data)) {
            $domain = trim($data['custom_domain'] ?? '');
            $currentUser = User::find($userId);
            $currentDomain = $currentUser['custom_domain'] ?? null;

            if ($domain === '') {
                $data['custom_domain'] = null;
                if ($currentDomain !== null) {
                    $domainRemoved = $currentDomain;
                }
            } else {
                // Normalize first so all comparisons use the canonical form
                $domain = strtolower($domain);
                $domain = preg_replace('#^https?://#', '', $domain);
                $domain = rtrim($domain, '/');

                if (!preg_match('/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?(\.[a-z]{2,})+$/', $domain)) {
                    return $this->json($response, ['error' => true, 'message' => 'Please enter a valid domain (e.g. myshop.com)'], 422);
                }
                if ($domain !== $currentDomain && !$this->planGuard->canUseCustomDomain($userId)) {
                    return $this->json($response, ['error' => true, 'message' => 'Custom domains are available on paid plans.'], 403);
                }
                if (User::exists('custom_domain', $domain, $userId)) {
                    return $this->json($response, ['error' => true, 'message' => 'This domain is already in use'], 409);
                }

                $data['custom_domain'] = $domain;
                if ($domain !== $currentDomain) {
                    $domainAdded = $domain;
                    if ($currentDomain !== null) {
                        $domainRemoved = $currentDomain;
                    }
                }
            }
        }

        $this->userModel->update($userId, $data);

        // Provision/deprovision domain aliases on the web server
        if ($domainRemoved !== null) {
            $this->hestiaCP->removeDomainAlias($domainRemoved);
        }
        if ($domainAdded !== null) {
            $this->hestiaCP->addDomainAlias($domainAdded);
        }

        Hooks::doAction('shop.updated', $userId, $data);

        $this->auditLog->log('shop.update', $userId, 'user', $userId, ['fields' => array_keys($data)]);

        $user = User::find($userId);
        unset($user['password_hash']);

        return $this->json($response, ['success' => true, 'shop' => $user]);
    }

    /**
     * Change the seller's password.
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
        $userId = $this->auth->userId();

        $currentPassword = $data['current_password'] ?? '';
        $newPassword = $data['new_password'] ?? '';

        if ($currentPassword === '' || $newPassword === '') {
            return $this->json($response, ['error' => true, 'message' => 'All fields are required'], 422);
        }

        $passwordError = $this->validation->password($newPassword);
        if ($passwordError !== null) {
            return $this->json($response, ['error' => true, 'message' => $passwordError], 422);
        }

        $hash = $this->userModel->getPasswordHash($userId);
        if (!$hash || !password_verify($currentPassword, $hash)) {
            return $this->json($response, ['error' => true, 'message' => 'Current password is incorrect'], 403);
        }

        $this->userModel->updatePassword($userId, password_hash($newPassword, PASSWORD_BCRYPT));

        $this->logger->info('auth.password_changed', [
            'user_id' => $userId,
            'ip'      => $request->getServerParams()['REMOTE_ADDR'] ?? '',
        ]);

        return $this->json($response, ['success' => true, 'message' => 'Password updated']);
    }

    /**
     * Change the seller's email.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function changeEmail(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $userId = $this->auth->userId();

        $newEmail = trim($data['new_email'] ?? '');
        $currentPassword = $data['current_password'] ?? '';

        if ($newEmail === '' || $currentPassword === '') {
            return $this->json($response, ['error' => true, 'message' => 'All fields are required'], 422);
        }

        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            return $this->json($response, ['error' => true, 'message' => 'Invalid email address'], 422);
        }

        if ($err = $this->validation->maxLength($newEmail, 'email')) {
            return $this->json($response, ['error' => true, 'message' => $err], 422);
        }

        $hash = $this->userModel->getPasswordHash($userId);
        if (!$hash || !password_verify($currentPassword, $hash)) {
            return $this->json($response, ['error' => true, 'message' => 'Current password is incorrect'], 403);
        }

        if (User::exists('email', $newEmail, $userId)) {
            return $this->json($response, ['error' => true, 'message' => 'This email is already in use'], 409);
        }

        $oldUser = User::find($userId);
        $this->userModel->updateEmail($userId, $newEmail);

        $this->logger->info('auth.email_changed', [
            'user_id'   => $userId,
            'old_email' => $oldUser['email'] ?? '',
            'new_email' => $newEmail,
            'ip'        => $request->getServerParams()['REMOTE_ADDR'] ?? '',
        ]);

        return $this->json($response, ['success' => true, 'message' => 'Email updated']);
    }

    /**
     * Get theme customizer options.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function getThemeOptions(Request $request, Response $response): Response
    {
        $userId = $this->auth->userId();
        $user = User::find($userId);
        $themeSlug = $user['shop_theme'] ?? 'classic';

        // Activate theme to trigger functions.php registration
        $this->themeService->activate($themeSlug, $this->view);

        $customizer = $this->themeService->getCustomizer();
        $schema = $customizer->getSchema();
        $saved = $this->themeOptionModel->getAll($userId, $themeSlug);
        $resolved = $customizer->resolveOptions($saved);

        return $this->json($response, [
            'success' => true,
            'schema'  => $schema,
            'values'  => $resolved,
        ]);
    }

    /**
     * Save theme customizer options.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function saveThemeOptions(Request $request, Response $response): Response
    {
        $userId = $this->auth->userId();
        $user = User::find($userId);
        $themeSlug = $user['shop_theme'] ?? 'classic';

        // Activate theme to trigger functions.php registration
        $this->themeService->activate($themeSlug, $this->view);

        $customizer = $this->themeService->getCustomizer();
        $data = (array) $request->getParsedBody();
        $updates = [];

        // Check if user is on a free plan (for pro-gated controls)
        $plan = $this->planGuard->getUserPlan($userId);
        $isFree = ((float) ($plan['price_monthly'] ?? 0)) === 0.0;

        $proBlocked = 0;

        foreach ($data as $key => $value) {
            if (!$customizer->hasSetting($key)) {
                continue;
            }

            // Prevent free users from changing pro-only settings
            if ($isFree && $customizer->isProControl($key)) {
                $proBlocked++;
                continue;
            }

            $sanitized = $customizer->sanitizeValue($key, $value);
            if ($sanitized !== null) {
                $updates[$key] = $sanitized;
            }
        }

        if ($updates === []) {
            if ($proBlocked > 0) {
                return $this->json($response, [
                    'error'   => true,
                    'message' => 'This setting requires a paid plan. Upgrade in Billing to unlock it.',
                ], 403);
            }
            return $this->json($response, [
                'error'   => true,
                'message' => 'Nothing changed. Try updating a setting before saving.',
            ], 422);
        }

        $this->themeOptionModel->setMany($userId, $themeSlug, $updates);

        Hooks::doAction('theme_options.saved', $userId, $themeSlug, $updates);

        return $this->json($response, ['success' => true]);
    }

    /**
     * Delete the seller's account.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function deleteAccount(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $userId = $this->auth->userId();

        $confirmation = trim($data['confirmation'] ?? '');
        $password = $data['password'] ?? '';

        if ($confirmation === '' || $password === '') {
            return $this->json($response, ['error' => true, 'message' => 'All fields are required'], 422);
        }

        $user = User::find($userId);
        if (!$user) {
            return $this->json($response, ['error' => true, 'message' => 'Account not found'], 404);
        }

        // Verify shop name matches
        if (strtolower($confirmation) !== strtolower($user['store_name'] ?? '')) {
            return $this->json($response, ['error' => true, 'message' => 'Shop name does not match'], 422);
        }

        // Verify password
        $hash = $this->userModel->getPasswordHash($userId);
        if (!$hash || !password_verify($password, $hash)) {
            return $this->json($response, ['error' => true, 'message' => 'Password is incorrect'], 403);
        }

        // Remove custom domain alias before account deletion
        if (!empty($user['custom_domain'])) {
            $this->hestiaCP->removeDomainAlias($user['custom_domain']);
        }

        $result = $this->userModel->deleteAccount($userId);
        if ($result === false) {
            return $this->json($response, ['error' => true, 'message' => 'Failed to delete account'], 500);
        }

        // Clean up uploaded files (best-effort, outside transaction)
        foreach ($result as $url) {
            $this->upload->deleteFile($url);
        }

        $this->logger->info('account.deleted', [
            'user_id' => $userId,
            'email'   => $user['email'],
            'ip'      => $request->getServerParams()['REMOTE_ADDR'] ?? '',
        ]);

        $this->auditLog->log('account.delete', $userId, 'user', $userId, [
            'email' => $user['email'],
            'store_name' => $user['store_name'] ?? null,
        ]);

        Hooks::doAction('shop.deleted', $userId);

        // Destroy session
        $this->auth->logout();

        return $this->json($response, ['success' => true, 'message' => 'Account deleted']);
    }
}
