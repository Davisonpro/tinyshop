<?php

declare(strict_types=1);

namespace TinyShop\Services\OAuth;

/**
 * Google OAuth provider.
 *
 * @since 1.0.0
 */
final class GoogleProvider implements OAuthProviderInterface
{
    use OAuthHttpTrait;

    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $redirectUri,
    ) {}

    public function name(): string
    {
        return 'google';
    }

    /** {@inheritDoc} */
    public function getAuthUrl(string $state): string
    {
        $params = http_build_query([
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->redirectUri,
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'state'         => $state,
            'access_type'   => 'offline',
            'prompt'        => 'select_account',
        ]);

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . $params;
    }

    /** {@inheritDoc} */
    public function handleCallback(string $code): ?OAuthUser
    {
        $tokenData = $this->httpPost('https://oauth2.googleapis.com/token', [
            'code'          => $code,
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri'  => $this->redirectUri,
            'grant_type'    => 'authorization_code',
        ]);

        if (!$tokenData || empty($tokenData['access_token'])) {
            return null;
        }

        $userInfo = $this->httpGet(
            'https://www.googleapis.com/oauth2/v2/userinfo',
            $tokenData['access_token']
        );

        if (!$userInfo || empty($userInfo['email'])) {
            return null;
        }

        return new OAuthUser(
            id: (string) ($userInfo['id'] ?? ''),
            email: $userInfo['email'],
            name: $userInfo['name'] ?? '',
            avatar: $userInfo['picture'] ?? '',
            provider: 'google',
            emailVerified: !empty($userInfo['verified_email']),
        );
    }
}
