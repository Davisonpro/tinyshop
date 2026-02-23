<?php

declare(strict_types=1);

namespace TinyShop\Services\OAuth;

final class TiktokProvider implements OAuthProviderInterface
{
    use OAuthHttpTrait;

    public function __construct(
        private readonly string $clientKey,
        private readonly string $clientSecret,
        private readonly string $redirectUri,
    ) {}

    public function name(): string
    {
        return 'tiktok';
    }

    public function getAuthUrl(string $state): string
    {
        $params = http_build_query([
            'client_key'    => $this->clientKey,
            'redirect_uri'  => $this->redirectUri,
            'scope'         => 'user.info.basic',
            'response_type' => 'code',
            'state'         => $state,
        ]);

        return 'https://www.tiktok.com/v2/auth/authorize/?' . $params;
    }

    public function handleCallback(string $code): ?OAuthUser
    {
        $tokenData = $this->httpPost('https://open.tiktokapis.com/v2/oauth/token/', [
            'client_key'    => $this->clientKey,
            'client_secret' => $this->clientSecret,
            'code'          => $code,
            'grant_type'    => 'authorization_code',
            'redirect_uri'  => $this->redirectUri,
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

        return new OAuthUser(
            id: (string) ($data['open_id'] ?? ''),
            email: '',
            name: $data['display_name'] ?? '',
            avatar: $data['avatar_url'] ?? '',
            provider: 'tiktok',
            emailVerified: false,
        );
    }
}
