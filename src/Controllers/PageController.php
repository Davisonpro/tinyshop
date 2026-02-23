<?php

declare(strict_types=1);

namespace TinyShop\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;
use TinyShop\Services\View;
use TinyShop\Services\Auth;
use TinyShop\Services\OAuth\OAuthProviderFactory;
use TinyShop\Enums\UserRole;
use TinyShop\Models\Page;
use TinyShop\Models\PageView;
use TinyShop\Models\Plan;
use TinyShop\Models\Setting;
use TinyShop\Models\ShopView;
use TinyShop\Models\User;

final class PageController
{
    private const VISITOR_COOKIE_MAX_AGE = 86400 * 365;

    public function __construct(
        private readonly View $view,
        private readonly Auth $auth,
        private readonly OAuthProviderFactory $oauthFactory,
        private readonly Plan $planModel,
        private readonly Setting $setting,
        private readonly User $user,
        private readonly Page $pageModel,
        private readonly PageView $pageViewModel
    ) {}

    public function landing(Request $request, Response $response): Response
    {
        if ($this->auth->check()) {
            return $response->withHeader('Location', '/dashboard')->withStatus(302);
        }

        $response = $this->trackPageView($request, $response, '/');

        return $this->view->render($response, 'pages/public/landing.tpl', [
            'page_title'      => 'Create Your Shop in Minutes',
            'showcased_shops' => $this->user->findShowcased(),
        ]);
    }

    public function login(Request $request, Response $response): Response
    {
        return $this->view->render($response, 'pages/auth/login.tpl', [
            'page_title' => 'Sign In',
            'current_page' => 'login',
        ]);
    }

    public function register(Request $request, Response $response): Response
    {
        if ($this->setting->get('allow_registration', '1') !== '1') {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        return $this->view->render($response, 'pages/auth/register.tpl', [
            'page_title' => 'Create Account',
            'current_page' => 'register',
        ]);
    }

    public function oauthRedirect(Request $request, Response $response, array $args): Response
    {
        $provider = $args['provider'] ?? '';

        if (!$this->oauthFactory->isEnabled($provider)) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_state'] = $state;
        $_SESSION['oauth_provider'] = $provider;

        $oauthProvider = $this->oauthFactory->create($provider);
        $url = $oauthProvider->getAuthUrl($state);
        return $response->withHeader('Location', $url)->withStatus(302);
    }

    public function oauthCallback(Request $request, Response $response, array $args): Response
    {
        $provider = $args['provider'] ?? '';
        $params = $request->getQueryParams();
        $code = $params['code'] ?? '';
        $state = $params['state'] ?? '';

        if (!$code || !$state || !$this->oauthFactory->isEnabled($provider)) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        // Verify state
        $expectedState = $_SESSION['oauth_state'] ?? '';
        unset($_SESSION['oauth_state'], $_SESSION['oauth_provider']);

        if (!hash_equals($expectedState, $state)) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        $oauthProvider = $this->oauthFactory->create($provider);
        $oauthUser = $oauthProvider->handleCallback($code);
        if (!$oauthUser || $oauthUser->id === '') {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        // 1. Try to find existing user by provider's unique ID
        $existing = $this->user->findByOAuth($provider, $oauthUser->id);

        // 2. Fall back to email match ONLY if the provider verified the email
        if (!$existing && $oauthUser->email !== '' && $oauthUser->emailVerified) {
            $existing = User::findBy('email', $oauthUser->email);

            // Link this OAuth provider to the existing email-matched account
            if ($existing) {
                $this->user->updateOAuth((int) $existing['id'], $provider, $oauthUser->id);
            }
        }

        if ($existing) {
            // 3. Check if account is suspended
            if (isset($existing['is_active']) && (int) $existing['is_active'] === 0) {
                return $response->withHeader('Location', '/login')->withStatus(302);
            }

            $role = UserRole::tryFrom($existing['role'] ?? '') ?? UserRole::Seller;
            $this->auth->login((int) $existing['id'], $existing['store_name'] ?? '', $role);
            $this->user->recordLogin((int) $existing['id']);

            $redirect = $role === UserRole::Admin ? '/admin' : '/dashboard';
            return $response->withHeader('Location', $redirect)->withStatus(302);
        }

        // 4. Respect allow_registration setting for new accounts
        if ($this->setting->get('allow_registration', '1') !== '1') {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        // Create new user — generate subdomain from OAuth name or email
        $oauthName = $oauthUser->name ?: 'User';
        $subdomain = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $oauthName));
        if (strlen($subdomain) < 3) {
            $subdomain .= bin2hex(random_bytes(2));
        }

        $base = $subdomain;
        $i = 1;
        while (User::exists('subdomain', $subdomain)) {
            $subdomain = $base . $i;
            $i++;
        }

        $storeName = $oauthName . "'s Shop";

        // 5. Use provider's unique ID for oauth_id
        $userId = $this->user->create([
            'email' => $oauthUser->email ?: $subdomain . '@oauth.local',
            'oauth_provider' => $provider,
            'oauth_id' => $oauthUser->id,
            'store_name' => $storeName,
            'subdomain' => $subdomain,
        ]);

        $this->auth->login($userId, $storeName, UserRole::Seller);
        $this->user->recordLogin($userId);

        return $response->withHeader('Location', '/dashboard')->withStatus(302);
    }

    public function pricing(Request $request, Response $response): Response
    {
        $response = $this->trackPageView($request, $response, '/pricing');

        $plans = $this->planModel->findAll();

        foreach ($plans as &$plan) {
            $plan['feature_list'] = $plan['features'] ? json_decode($plan['features'], true) : [];
        }
        unset($plan);

        return $this->view->render($response, 'pages/public/pricing.tpl', [
            'page_title' => 'Pricing',
            'plans'      => $plans,
            'logged_in'  => $this->auth->check(),
        ]);
    }

    public function forgotPassword(Request $request, Response $response): Response
    {
        return $this->view->render($response, 'pages/auth/forgot_password.tpl', [
            'page_title' => 'Forgot Password',
            'current_page' => 'forgot_password',
        ]);
    }

    public function resetPassword(Request $request, Response $response): Response
    {
        $token = $request->getQueryParams()['token'] ?? '';

        return $this->view->render($response, 'pages/auth/reset_password.tpl', [
            'page_title' => 'Reset Password',
            'current_page' => 'reset_password',
            'token'      => $token,
        ]);
    }

    public function logout(Request $request, Response $response): Response
    {
        $this->auth->logout();
        return $response->withHeader('Location', '/login')->withStatus(302);
    }

    public function showPage(Request $request, Response $response, array $args): Response
    {
        $slug = $args['slug'] ?? '';

        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            throw new HttpNotFoundException($request);
        }

        $page = $this->pageModel->findBySlug($slug);
        if ($page === null) {
            throw new HttpNotFoundException($request);
        }

        $response = $this->trackPageView($request, $response, '/' . $slug);

        return $this->view->render($response, 'pages/public/page.tpl', [
            'page_title'       => $page['title'],
            'meta_description' => $page['meta_description'] ?? '',
            'page_data'        => $page,
        ]);
    }

    public function manifest(Request $request, Response $response): Response
    {
        $appName = $this->setting->get('app_name') ?: 'TinyShop';

        $manifest = [
            'name'             => $appName,
            'short_name'       => $appName,
            'description'      => 'Create your mobile shop in minutes. Share anywhere.',
            'id'               => '/dashboard',
            'start_url'        => '/dashboard',
            'display'          => 'standalone',
            'background_color' => '#F5F5F7',
            'theme_color'      => '#111111',
            'orientation'      => 'portrait',
            'icons'            => [
                ['src' => '/public/img/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/public/img/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/public/img/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'maskable'],
                ['src' => '/public/img/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
        ];

        $response->getBody()->write(json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        return $response
            ->withHeader('Content-Type', 'application/manifest+json')
            ->withHeader('Cache-Control', 'public, max-age=3600');
    }

    private function trackPageView(Request $request, Response $response, string $pagePath): Response
    {
        $serverParams = $request->getServerParams();
        $ip = $serverParams['REMOTE_ADDR'] ?? '0.0.0.0';
        $ua = $serverParams['HTTP_USER_AGENT'] ?? '';

        $refererDomain = ShopView::extractRefererDomain($serverParams['HTTP_REFERER'] ?? '');
        $requestHost = strtolower($serverParams['HTTP_HOST'] ?? '');
        if ($requestHost !== '' && str_starts_with($requestHost, 'www.')) {
            $requestHost = substr($requestHost, 4);
        }
        if ($refererDomain !== null && $refererDomain === $requestHost) {
            $refererDomain = null;
        }

        $queryParams = $request->getQueryParams();
        $utmSource = null;
        if (!empty($queryParams['utm_source'])) {
            $raw = strtolower(trim($queryParams['utm_source']));
            $raw = preg_replace('/[^a-z0-9_\-]/', '', $raw);
            $utmSource = $raw !== '' ? mb_substr($raw, 0, 50) : null;
        }

        $cookies = $request->getCookieParams();
        [$visitorToken, $isNewToken] = ShopView::resolveVisitorToken($cookies[ShopView::COOKIE_NAME] ?? '');

        $this->pageViewModel->log($pagePath, $visitorToken, $ip, $ua, $refererDomain, $utmSource);

        if ($isNewToken) {
            $response = $response->withHeader(
                'Set-Cookie',
                ShopView::COOKIE_NAME . '=' . $visitorToken
                . '; Path=/; Max-Age=' . self::VISITOR_COOKIE_MAX_AGE . '; HttpOnly; SameSite=Lax'
            );
        }

        return $response;
    }
}
