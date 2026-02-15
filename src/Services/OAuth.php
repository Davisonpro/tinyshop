<?php

declare(strict_types=1);

namespace TinyShop\Services;

use RuntimeException;

/**
 * Lightweight OAuth 2.0 service for Google, Instagram, and TikTok.
 * No external dependencies — uses PHP's native HTTP functions.
 */
final class OAuth
{
    private array $config;
    private string $baseUrl;

    public function __construct(array $oauthConfig, string $baseUrl)
    {
        $this->config = $oauthConfig;
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function isEnabled(string $provider): bool
    {
        return !empty($this->config[$provider]['enabled']);
    }

    /**
     * Get the authorization URL for the given provider.
     */
    public function getAuthUrl(string $provider): string
    {
        $cfg = $this->config[$provider] ?? null;
        if (!$cfg) {
            throw new RuntimeException("Unknown OAuth provider: {$provider}");
        }

        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_state'] = $state;
        $_SESSION['oauth_provider'] = $provider;

        return match ($provider) {
            'google' => $this->googleAuthUrl($cfg, $state),
            'instagram' => $this->instagramAuthUrl($cfg, $state),
            'tiktok' => $this->tiktokAuthUrl($cfg, $state),
            default => throw new RuntimeException("Unsupported provider: {$provider}"),
        };
    }

    /**
     * Exchange the authorization code for user info.
     * Returns ['email' => ..., 'name' => ..., 'provider' => ...] or null on failure.
     */
    public function handleCallback(string $provider, string $code, string $state): ?array
    {
        // Verify state
        $expectedState = $_SESSION['oauth_state'] ?? '';
        unset($_SESSION['oauth_state'], $_SESSION['oauth_provider']);

        if (!hash_equals($expectedState, $state)) {
            return null;
        }

        return match ($provider) {
            'google' => $this->googleCallback($code),
            'instagram' => $this->instagramCallback($code),
            'tiktok' => $this->tiktokCallback($code),
            default => null,
        };
    }

    // ── Google ──

    private function googleAuthUrl(array $cfg, string $state): string
    {
        $params = http_build_query([
            'client_id'     => $cfg['client_id'],
            'redirect_uri'  => $this->baseUrl . $cfg['redirect_uri'],
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'state'         => $state,
            'access_type'   => 'offline',
            'prompt'        => 'select_account',
        ]);
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . $params;
    }

    private function googleCallback(string $code): ?array
    {
        $cfg = $this->config['google'];

        // Exchange code for token
        $tokenData = $this->httpPost('https://oauth2.googleapis.com/token', [
            'code'          => $code,
            'client_id'     => $cfg['client_id'],
            'client_secret' => $cfg['client_secret'],
            'redirect_uri'  => $this->baseUrl . $cfg['redirect_uri'],
            'grant_type'    => 'authorization_code',
        ]);

        if (!$tokenData || empty($tokenData['access_token'])) {
            return null;
        }

        // Get user info
        $userInfo = $this->httpGet(
            'https://www.googleapis.com/oauth2/v2/userinfo',
            $tokenData['access_token']
        );

        if (!$userInfo || empty($userInfo['email'])) {
            return null;
        }

        return [
            'id'             => (string) ($userInfo['id'] ?? ''),
            'email'          => $userInfo['email'],
            'name'           => $userInfo['name'] ?? '',
            'avatar'         => $userInfo['picture'] ?? '',
            'provider'       => 'google',
            'email_verified' => !empty($userInfo['verified_email']),
        ];
    }

    // ── Instagram ──

    private function instagramAuthUrl(array $cfg, string $state): string
    {
        $params = http_build_query([
            'client_id'     => $cfg['client_id'],
            'redirect_uri'  => $this->baseUrl . $cfg['redirect_uri'],
            'scope'         => 'instagram_business_basic',
            'response_type' => 'code',
            'state'         => $state,
        ]);
        return 'https://www.instagram.com/oauth/authorize?' . $params;
    }

    private function instagramCallback(string $code): ?array
    {
        $cfg = $this->config['instagram'];

        $tokenData = $this->httpPost('https://api.instagram.com/oauth/access_token', [
            'client_id'     => $cfg['client_id'],
            'client_secret' => $cfg['client_secret'],
            'grant_type'    => 'authorization_code',
            'redirect_uri'  => $this->baseUrl . $cfg['redirect_uri'],
            'code'          => $code,
        ]);

        if (!$tokenData || empty($tokenData['access_token'])) {
            return null;
        }

        $userId = $tokenData['user_id'] ?? '';
        $userInfo = $this->httpGet(
            "https://graph.instagram.com/v21.0/{$userId}?fields=id,username",
            $tokenData['access_token']
        );

        if (!$userInfo || empty($userInfo['username'])) {
            return null;
        }

        return [
            'id'             => (string) ($userInfo['id'] ?? $userId),
            'email'          => '',
            'name'           => $userInfo['username'],
            'avatar'         => '',
            'provider'       => 'instagram',
            'email_verified' => false,
        ];
    }

    // ── TikTok ──

    private function tiktokAuthUrl(array $cfg, string $state): string
    {
        $params = http_build_query([
            'client_key'    => $cfg['client_key'],
            'redirect_uri'  => $this->baseUrl . $cfg['redirect_uri'],
            'scope'         => 'user.info.basic',
            'response_type' => 'code',
            'state'         => $state,
        ]);
        return 'https://www.tiktok.com/v2/auth/authorize/?' . $params;
    }

    private function tiktokCallback(string $code): ?array
    {
        $cfg = $this->config['tiktok'];

        $tokenData = $this->httpPost('https://open.tiktokapis.com/v2/oauth/token/', [
            'client_key'    => $cfg['client_key'],
            'client_secret' => $cfg['client_secret'],
            'code'          => $code,
            'grant_type'    => 'authorization_code',
            'redirect_uri'  => $this->baseUrl . $cfg['redirect_uri'],
        ]);

        if (!$tokenData || empty($tokenData['access_token'])) {
            return null;
        }

        $userInfo = $this->httpGet(
            'https://open.tiktokapis.com/v2/user/info/?fields=open_id,display_name,avatar_url',
            $tokenData['access_token']
        );

        $data = $userInfo['data']['user'] ?? null;
        if (!$data) {
            return null;
        }

        return [
            'id'             => (string) ($data['open_id'] ?? ''),
            'email'          => '',
            'name'           => $data['display_name'] ?? '',
            'avatar'         => $data['avatar_url'] ?? '',
            'provider'       => 'tiktok',
            'email_verified' => false,
        ];
    }

    // ── HTTP helpers ──

    private function httpPost(string $url, array $data): ?array
    {
        $ctx = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($data),
                'timeout' => 10,
            ],
        ]);

        $result = @file_get_contents($url, false, $ctx);
        if ($result === false) {
            return null;
        }

        return json_decode($result, true);
    }

    private function httpGet(string $url, string $token): ?array
    {
        $ctx = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'header'  => "Authorization: Bearer {$token}\r\n",
                'timeout' => 10,
            ],
        ]);

        $result = @file_get_contents($url, false, $ctx);
        if ($result === false) {
            return null;
        }

        return json_decode($result, true);
    }
}
