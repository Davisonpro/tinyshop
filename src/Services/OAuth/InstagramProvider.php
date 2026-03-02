<?php

declare(strict_types=1);

namespace TinyShop\Services\OAuth;

/**
 * Instagram OAuth provider.
 *
 * @since 1.0.0
 */
final class InstagramProvider implements OAuthProviderInterface
{
    use OAuthHttpTrait;

    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $redirectUri,
    ) {}

    public function name(): string
    {
        return 'instagram';
    }

    /** {@inheritDoc} */
    public function getAuthUrl(string $state): string
    {
        $params = http_build_query([
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->redirectUri,
            'scope'         => 'instagram_business_basic',
            'response_type' => 'code',
            'state'         => $state,
        ]);

        return 'https://www.instagram.com/oauth/authorize?' . $params;
    }

    /** {@inheritDoc} */
    public function handleCallback(string $code): ?OAuthUser
    {
        $tokenData = $this->httpPost('https://api.instagram.com/oauth/access_token', [
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type'    => 'authorization_code',
            'redirect_uri'  => $this->redirectUri,
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

        return new OAuthUser(
            id: (string) ($userInfo['id'] ?? $userId),
            email: '',
            name: $userInfo['username'],
            avatar: '',
            provider: 'instagram',
            emailVerified: false,
        );
    }
}
