<?php

declare(strict_types=1);

namespace TinyShop\Services;

final class Hooks
{
    private static array $actions = [];
    private static array $filters = [];

    /**
     * Register an action callback for a hook name.
     */
    public static function addAction(string $hook, callable $callback, int $priority = 10): void
    {
        self::$actions[$hook][$priority][] = $callback;
    }

    /**
     * Execute all callbacks registered for an action hook.
     */
    public static function doAction(string $hook, mixed ...$args): void
    {
        if (!isset(self::$actions[$hook])) {
            return;
        }

        ksort(self::$actions[$hook]);
        foreach (self::$actions[$hook] as $callbacks) {
            foreach ($callbacks as $callback) {
                $callback(...$args);
            }
        }
    }

    /**
     * Register a filter callback for a hook name.
     */
    public static function addFilter(string $hook, callable $callback, int $priority = 10): void
    {
        self::$filters[$hook][$priority][] = $callback;
    }

    /**
     * Apply all filter callbacks and return the modified value.
     */
    public static function applyFilter(string $hook, mixed $value, mixed ...$args): mixed
    {
        if (!isset(self::$filters[$hook])) {
            return $value;
        }

        ksort(self::$filters[$hook]);
        foreach (self::$filters[$hook] as $callbacks) {
            foreach ($callbacks as $callback) {
                $value = $callback($value, ...$args);
            }
        }

        return $value;
    }

    /**
     * Check if an action hook has registered callbacks.
     */
    public static function hasAction(string $hook): bool
    {
        return !empty(self::$actions[$hook]);
    }

    /**
     * Check if a filter hook has registered callbacks.
     */
    public static function hasFilter(string $hook): bool
    {
        return !empty(self::$filters[$hook]);
    }

    /**
     * Remove all callbacks for a given hook.
     */
    public static function removeAll(string $hook): void
    {
        unset(self::$actions[$hook], self::$filters[$hook]);
    }
}
