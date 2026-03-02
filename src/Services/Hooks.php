<?php

declare(strict_types=1);

namespace TinyShop\Services;

/**
 * WordPress-style hooks system.
 *
 * @since 1.0.0
 */
final class Hooks
{
    /** @var array<string, array<int, callable[]>> Action callbacks keyed by hook name, then priority */
    private static array $actions = [];

    /** @var array<string, array<int, callable[]>> Filter callbacks keyed by hook name, then priority */
    private static array $filters = [];

    /**
     * Register an action callback.
     *
     * @since 1.0.0
     *
     * @param string   $hook     Hook name.
     * @param callable $callback Callback to run.
     * @param int      $priority Lower runs first.
     */
    public static function addAction(string $hook, callable $callback, int $priority = 10): void
    {
        self::$actions[$hook][$priority][] = $callback;
    }

    /**
     * Fire an action hook.
     *
     * @since 1.0.0
     *
     * @param string $hook Hook name.
     * @param mixed  ...$args Arguments passed to callbacks.
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
     * Register a filter callback.
     *
     * @since 1.0.0
     *
     * @param string   $hook     Hook name.
     * @param callable $callback Callback to run.
     * @param int      $priority Lower runs first.
     */
    public static function addFilter(string $hook, callable $callback, int $priority = 10): void
    {
        self::$filters[$hook][$priority][] = $callback;
    }

    /**
     * Apply filters to a value and return the result.
     *
     * @since 1.0.0
     *
     * @param string $hook    Hook name.
     * @param mixed  $value   Value to filter.
     * @param mixed  ...$args Extra arguments passed to callbacks.
     * @return mixed Filtered value.
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

    /** Check if an action hook has callbacks. */
    public static function hasAction(string $hook): bool
    {
        return !empty(self::$actions[$hook]);
    }

    /** Check if a filter hook has callbacks. */
    public static function hasFilter(string $hook): bool
    {
        return !empty(self::$filters[$hook]);
    }

    /** Remove all callbacks for a hook. */
    public static function removeAll(string $hook): void
    {
        unset(self::$actions[$hook], self::$filters[$hook]);
    }
}
