<?php

declare(strict_types=1);

namespace TinyShop\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use TinyShop\Services\View;
use TinyShop\Services\Auth;
use TinyShop\Services\OAuth;
use TinyShop\Enums\UserRole;
use TinyShop\Models\Plan;
use TinyShop\Models\Setting;
use TinyShop\Models\User;

final class PageController
{
    public function __construct(
        private readonly View $view,
        private readonly Auth $auth,
        private readonly OAuth $oauth,
        private readonly Plan $planModel,
        private readonly Setting $setting,
        private readonly User $user
    ) {}

    public function landing(Request $request, Response $response): Response
    {
        if ($this->auth->check()) {
            return $response->withHeader('Location', '/dashboard')->withStatus(302);
        }

        return $this->view->render($response, 'pages/landing.tpl', [
            'page_title' => 'Create Your Shop in Minutes',
        ]);
    }

    public function login(Request $request, Response $response): Response
    {
        return $this->view->render($response, 'pages/login.tpl', [
            'page_title' => 'Sign In',
        ]);
    }

    public function register(Request $request, Response $response): Response
    {
        if ($this->setting->get('allow_registration', '1') !== '1') {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        return $this->view->render($response, 'pages/register.tpl', [
            'page_title' => 'Create Account',
        ]);
    }

    public function oauthRedirect(Request $request, Response $response, array $args): Response
    {
        $provider = $args['provider'] ?? '';

        if (!$this->oauth->isEnabled($provider)) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        $url = $this->oauth->getAuthUrl($provider);
        return $response->withHeader('Location', $url)->withStatus(302);
    }

    public function oauthCallback(Request $request, Response $response, array $args): Response
    {
        $provider = $args['provider'] ?? '';
        $params = $request->getQueryParams();
        $code = $params['code'] ?? '';
        $state = $params['state'] ?? '';

        if (!$code || !$state || !$this->oauth->isEnabled($provider)) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        $oauthUser = $this->oauth->handleCallback($provider, $code, $state);
        if (!$oauthUser || empty($oauthUser['id'])) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        // 1. Try to find existing user by provider's unique ID
        $existing = $this->user->findByOAuth($provider, $oauthUser['id']);

        // 2. Fall back to email match ONLY if the provider verified the email
        if (!$existing && !empty($oauthUser['email']) && !empty($oauthUser['email_verified'])) {
            $existing = $this->user->findByEmail($oauthUser['email']);

            // Link this OAuth provider to the existing email-matched account
            if ($existing) {
                $this->user->updateOAuth((int) $existing['id'], $provider, $oauthUser['id']);
            }
        }

        if ($existing) {
            // 3. Check if account is suspended
            if (isset($existing['is_active']) && (int) $existing['is_active'] === 0) {
                return $response->withHeader('Location', '/login')->withStatus(302);
            }

            $role = UserRole::tryFrom($existing['role'] ?? '') ?? UserRole::Seller;
            $this->auth->login((int) $existing['id'], $existing['name'], $role);
            $this->user->recordLogin((int) $existing['id']);

            $redirect = $role === UserRole::Admin ? '/admin' : '/dashboard';
            return $response->withHeader('Location', $redirect)->withStatus(302);
        }

        // 4. Respect allow_registration setting for new accounts
        if ($this->setting->get('allow_registration', '1') !== '1') {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        // Create new user — generate subdomain from name
        $name = $oauthUser['name'] ?: 'User';
        $subdomain = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
        if (strlen($subdomain) < 3) {
            $subdomain .= bin2hex(random_bytes(2));
        }

        $base = $subdomain;
        $i = 1;
        while ($this->user->subdomainExists($subdomain)) {
            $subdomain = $base . $i;
            $i++;
        }

        // 5. Use provider's unique ID for oauth_id
        $userId = $this->user->create([
            'name' => $name,
            'email' => $oauthUser['email'] ?: $subdomain . '@oauth.local',
            'oauth_provider' => $provider,
            'oauth_id' => $oauthUser['id'],
            'store_name' => $name . "'s Shop",
            'subdomain' => $subdomain,
        ]);

        $this->auth->login($userId, $name, UserRole::Seller);
        $this->user->recordLogin($userId);

        return $response->withHeader('Location', '/dashboard')->withStatus(302);
    }

    public function pricing(Request $request, Response $response): Response
    {
        $plans = $this->planModel->findAll();

        return $this->view->render($response, 'pages/pricing.tpl', [
            'page_title' => 'Pricing',
            'plans'      => $plans,
            'logged_in'  => $this->auth->check(),
        ]);
    }

    public function forgotPassword(Request $request, Response $response): Response
    {
        return $this->view->render($response, 'pages/forgot_password.tpl', [
            'page_title' => 'Forgot Password',
        ]);
    }

    public function resetPassword(Request $request, Response $response): Response
    {
        $token = $request->getQueryParams()['token'] ?? '';

        return $this->view->render($response, 'pages/reset_password.tpl', [
            'page_title' => 'Reset Password',
            'token'      => $token,
        ]);
    }

    public function logout(Request $request, Response $response): Response
    {
        $this->auth->logout();
        return $response->withHeader('Location', '/login')->withStatus(302);
    }
}
