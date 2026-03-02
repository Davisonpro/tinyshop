<?php

declare(strict_types=1);

namespace TinyShop\Services;

use TinyShop\Enums\UserRole;

/**
 * Authentication service.
 *
 * @since 1.0.0
 */
final class Auth
{
    private const SESSION_TIMEOUT = 8 * 60 * 60; // 8 hours

    /**
     * Ensure a PHP session is active.
     *
     * @since 1.0.0
     */
    public static function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
            if (empty($_SESSION['_csrf_token'])) {
                $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
            }
        }
    }

    /**
     * Log a user in and store identity in the session.
     *
     * @since 1.0.0
     *
     * @param int      $userId   User ID.
     * @param string   $userName Display name.
     * @param UserRole $role     User role.
     */
    public function login(int $userId, string $userName, UserRole $role = UserRole::Seller): void
    {
        self::ensureSession();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        // Rotate CSRF token on login to prevent login CSRF attacks
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));

        $_SESSION['user_id']   = $userId;
        $_SESSION['user_name'] = $userName;
        $_SESSION['user_role'] = $role->value;
        $_SESSION['ip']        = $_SERVER['REMOTE_ADDR'] ?? '';
        $_SESSION['created']   = time();
    }

    /**
     * Log the user out.
     *
     * @since 1.0.0
     */
    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    /**
     * Check if a user is logged in with a valid session.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function check(): bool
    {
        if (empty($_SESSION['user_id'])) {
            return false;
        }

        // Note: Strict IP check removed — mobile users switch between
        // WiFi and cellular, causing IP changes that would destroy sessions.

        if (time() - ($_SESSION['created'] ?? 0) > self::SESSION_TIMEOUT) {
            $this->logout();
            return false;
        }

        return true;
    }

    public function userId(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public function userName(): ?string
    {
        return $_SESSION['user_name'] ?? null;
    }

    /**
     * Get the current user's role.
     *
     * @since 1.0.0
     *
     * @return UserRole Defaults to Seller if missing.
     */
    public function role(): UserRole
    {
        return UserRole::tryFrom($_SESSION['user_role'] ?? '') ?? UserRole::Seller;
    }

    /** Whether the current user is an admin. */
    public function isAdmin(): bool
    {
        return $this->check() && $this->role() === UserRole::Admin;
    }

    /** Whether the current user is a seller. */
    public function isSeller(): bool
    {
        return $this->check() && $this->role() === UserRole::Seller;
    }

    /**
     * Impersonate another user (admin only).
     *
     * @since 1.0.0
     *
     * @param int      $userId   User to impersonate.
     * @param string   $userName Display name.
     * @param UserRole $role     User role.
     */
    public function impersonate(int $userId, string $userName, UserRole $role = UserRole::Seller): void
    {
        $_SESSION['_impersonate_admin_id']   = $_SESSION['user_id'];
        $_SESSION['_impersonate_admin_name'] = $_SESSION['user_name'];
        $_SESSION['_impersonate_admin_role'] = $_SESSION['user_role'];

        $_SESSION['user_id']   = $userId;
        $_SESSION['user_name'] = $userName;
        $_SESSION['user_role'] = $role->value;
    }

    /**
     * Stop impersonating and restore the admin session.
     *
     * @since 1.0.0
     */
    public function stopImpersonating(): void
    {
        if (!$this->isImpersonating()) {
            return;
        }

        $_SESSION['user_id']   = $_SESSION['_impersonate_admin_id'];
        $_SESSION['user_name'] = $_SESSION['_impersonate_admin_name'];
        $_SESSION['user_role'] = $_SESSION['_impersonate_admin_role'];

        unset(
            $_SESSION['_impersonate_admin_id'],
            $_SESSION['_impersonate_admin_name'],
            $_SESSION['_impersonate_admin_role']
        );
    }

    /** Whether the admin is currently impersonating another user. */
    public function isImpersonating(): bool
    {
        return !empty($_SESSION['_impersonate_admin_id']);
    }

    /** Get the real admin's user ID during impersonation. */
    public function realAdminId(): ?int
    {
        return isset($_SESSION['_impersonate_admin_id']) ? (int) $_SESSION['_impersonate_admin_id'] : null;
    }
}
