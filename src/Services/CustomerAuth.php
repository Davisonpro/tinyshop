<?php

declare(strict_types=1);

namespace TinyShop\Services;

final class CustomerAuth
{
    private const SESSION_TIMEOUT = 30 * 24 * 60 * 60; // 30 days

    public function login(int $customerId, int $shopId, string $customerName): void
    {
        Auth::ensureSession();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        $_SESSION['customer_id']      = $customerId;
        $_SESSION['customer_shop_id'] = $shopId;
        $_SESSION['customer_name']    = $customerName;
        $_SESSION['customer_created'] = time();
    }

    public function logout(): void
    {
        unset(
            $_SESSION['customer_id'],
            $_SESSION['customer_shop_id'],
            $_SESSION['customer_name'],
            $_SESSION['customer_created']
        );
    }

    public function check(int $shopId): bool
    {
        if (empty($_SESSION['customer_id'])) {
            return false;
        }
        if (($_SESSION['customer_shop_id'] ?? 0) !== $shopId) {
            return false;
        }
        if (time() - ($_SESSION['customer_created'] ?? 0) > self::SESSION_TIMEOUT) {
            $this->logout();
            return false;
        }
        return true;
    }

    public function customerId(): ?int
    {
        return isset($_SESSION['customer_id']) ? (int) $_SESSION['customer_id'] : null;
    }

    public function customerShopId(): ?int
    {
        return isset($_SESSION['customer_shop_id']) ? (int) $_SESSION['customer_shop_id'] : null;
    }

    public function customerName(): ?string
    {
        return $_SESSION['customer_name'] ?? null;
    }
}
