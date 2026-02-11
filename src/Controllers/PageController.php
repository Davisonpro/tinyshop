<?php

declare(strict_types=1);

namespace TinyShop\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use TinyShop\Services\View;
use TinyShop\Services\Auth;
use TinyShop\Services\OAuth;
use TinyShop\Models\Setting;
use TinyShop\Models\User;

final class PageController
{
    public function __construct(
        private View $view,
        private Auth $auth,
        private OAuth $oauth,
        private Setting $setting,
        private User $user
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
        if (!$oauthUser) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        // Try to find existing user by email first (if email provided), then by OAuth ID
        $existing = null;
        if (!empty($oauthUser['email'])) {
            $existing = $this->user->findByEmail($oauthUser['email']);
        }
        if (!$existing) {
            $existing = $this->user->findByOAuth($provider, $oauthUser['email'] ?: $oauthUser['name']);
        }

        if ($existing) {
            $this->auth->login($existing['id']);
            $this->user->recordLogin($existing['id']);
            return $response->withHeader('Location', '/dashboard')->withStatus(302);
        }

        // Create new user — generate subdomain from name
        $name = $oauthUser['name'] ?: 'User';
        $subdomain = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
        if (strlen($subdomain) < 3) {
            $subdomain .= bin2hex(random_bytes(2));
        }

        // Ensure unique subdomain
        $base = $subdomain;
        $i = 1;
        while ($this->user->subdomainExists($subdomain)) {
            $subdomain = $base . $i;
            $i++;
        }

        $userId = $this->user->create([
            'name' => $name,
            'email' => $oauthUser['email'] ?: $subdomain . '@oauth.local',
            'oauth_provider' => $provider,
            'oauth_id' => $oauthUser['email'] ?: $oauthUser['name'],
            'store_name' => $name . "'s Shop",
            'subdomain' => $subdomain,
        ]);

        $this->auth->login($userId);
        $this->user->recordLogin($userId);

        return $response->withHeader('Location', '/dashboard')->withStatus(302);
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
