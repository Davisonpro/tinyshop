<?php

declare(strict_types=1);

namespace TinyShop\Services;

use TinyShop\Enums\UserRole;

final class Auth
{
    private const SESSION_TIMEOUT = 8 * 60 * 60; // 8 hours

    /**
     * Ensure a PHP session is active. Call before accessing $_SESSION.
     * Safe to call multiple times — only starts once.
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

    public function login(int $userId, string $userName, UserRole $role = UserRole::Seller): void
    {
        self::ensureSession();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        $_SESSION['user_id']   = $userId;
        $_SESSION['user_name'] = $userName;
        $_SESSION['user_role'] = $role->value;
        $_SESSION['ip']        = $_SERVER['REMOTE_ADDR'] ?? '';
        $_SESSION['created']   = time();
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public function check(): bool
    {
        if (empty($_SESSION['user_id'])) {
            return false;
        }

        if (($_SESSION['ip'] ?? '') !== ($_SERVER['REMOTE_ADDR'] ?? '')) {
            $this->logout();
            return false;
        }

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

    public function role(): UserRole
    {
        return UserRole::tryFrom($_SESSION['user_role'] ?? '') ?? UserRole::Seller;
    }

    public function isAdmin(): bool
    {
        return $this->check() && $this->role() === UserRole::Admin;
    }

    public function isSeller(): bool
    {
        return $this->check() && $this->role() === UserRole::Seller;
    }

    public function impersonate(int $userId, string $userName, UserRole $role = UserRole::Seller): void
    {
        $_SESSION['_impersonate_admin_id']   = $_SESSION['user_id'];
        $_SESSION['_impersonate_admin_name'] = $_SESSION['user_name'];
        $_SESSION['_impersonate_admin_role'] = $_SESSION['user_role'];

        $_SESSION['user_id']   = $userId;
        $_SESSION['user_name'] = $userName;
        $_SESSION['user_role'] = $role->value;
    }

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

    public function isImpersonating(): bool
    {
        return !empty($_SESSION['_impersonate_admin_id']);
    }

    public function realAdminId(): ?int
    {
        return isset($_SESSION['_impersonate_admin_id']) ? (int) $_SESSION['_impersonate_admin_id'] : null;
    }
}
