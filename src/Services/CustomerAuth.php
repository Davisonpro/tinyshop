<?php

declare(strict_types=1);

namespace TinyShop\Services;

/**
 * Customer authentication service.
 *
 * @since 1.0.0
 */
final class CustomerAuth
{
    /** @var int Session lifetime in seconds (30 days) */
    private const SESSION_TIMEOUT = 30 * 24 * 60 * 60;

    /**
     * Log a customer in.
     *
     * @since 1.0.0
     *
     * @param int    $customerId   Customer ID.
     * @param int    $shopId       Shop ID.
     * @param string $customerName Display name.
     */
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

    /**
     * Log the customer out.
     *
     * @since 1.0.0
     */
    public function logout(): void
    {
        unset(
            $_SESSION['customer_id'],
            $_SESSION['customer_shop_id'],
            $_SESSION['customer_name'],
            $_SESSION['customer_created']
        );
    }

    /**
     * Check if the customer is logged in for a given shop.
     *
     * @since 1.0.0
     *
     * @param  int  $shopId Shop to check against.
     * @return bool
     */
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

    /** Get the customer ID, or null. */
    public function customerId(): ?int
    {
        return isset($_SESSION['customer_id']) ? (int) $_SESSION['customer_id'] : null;
    }

    /** Get the shop ID the customer is logged into, or null. */
    public function customerShopId(): ?int
    {
        return isset($_SESSION['customer_shop_id']) ? (int) $_SESSION['customer_shop_id'] : null;
    }

    /** Get the customer's display name, or null. */
    public function customerName(): ?string
    {
        return $_SESSION['customer_name'] ?? null;
    }
}
