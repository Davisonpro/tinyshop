<?php

declare(strict_types=1);

namespace TinyShop\Services;

/**
 * Loads addons from the /addons directory.
 *
 * Each addon is a folder containing an init.php file:
 *   addons/
 *     my-addon/
 *       init.php   ← entry point, registers hooks
 *
 * Example addon init.php:
 *   <?php
 *   use TinyShop\Services\Hooks;
 *   Hooks::addFilter('product.price.display', function ($price) {
 *       return '$' . number_format($price, 2);
 *   });
 */
final class AddonLoader
{
    private string $addonsDir;
    private array $loaded = [];

    public function __construct(string $addonsDir)
    {
        $this->addonsDir = $addonsDir;
    }

    public function loadAll(): void
    {
        if (!is_dir($this->addonsDir)) {
            return;
        }

        $dirs = glob($this->addonsDir . '/*/init.php');
        foreach ($dirs as $initFile) {
            $addonName = basename(dirname($initFile));
            if (!isset($this->loaded[$addonName])) {
                require_once $initFile;
                $this->loaded[$addonName] = true;
            }
        }
    }

    public function getLoaded(): array
    {
        return array_keys($this->loaded);
    }
}
