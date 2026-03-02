<?php

declare(strict_types=1);

namespace TinyShop\Services\OAuth;

/**
 * Normalized OAuth user profile.
 *
 * @since 1.0.0
 */
final class OAuthUser
{
    /**
     * @param string $id            Provider user ID.
     * @param string $email         Email (empty if unavailable).
     * @param string $name          Display name.
     * @param string $avatar        Profile picture URL.
     * @param string $provider      Provider name.
     * @param bool   $emailVerified Whether email is verified.
     */
    public function __construct(
        public readonly string $id,
        public readonly string $email,
        public readonly string $name,
        public readonly string $avatar,
        public readonly string $provider,
        public readonly bool $emailVerified,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id'             => $this->id,
            'email'          => $this->email,
            'name'           => $this->name,
            'avatar'         => $this->avatar,
            'provider'       => $this->provider,
            'email_verified' => $this->emailVerified,
        ];
    }
}
