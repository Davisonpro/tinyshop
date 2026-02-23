<?php

declare(strict_types=1);

namespace TinyShop\Services\OAuth;

interface OAuthProviderInterface
{
    public function name(): string;

    public function getAuthUrl(string $state): string;

    public function handleCallback(string $code): ?OAuthUser;
}
