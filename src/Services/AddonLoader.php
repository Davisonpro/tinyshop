<?php

declare(strict_types=1);

namespace TinyShop\Services;

/**
 * Addon loader.
 *
 * @since 1.0.0
 */
final class AddonLoader
{
    private string $addonsDir;
    private array $loaded = [];

    /**
     * @param string $addonsDir Path to the addons directory.
     */
    public function __construct(string $addonsDir)
    {
        $this->addonsDir = $addonsDir;
    }

    /**
     * Load all addons from the addons directory.
     *
     * @since 1.0.0
     */
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

    /**
     * Get loaded addon names.
     *
     * @since 1.0.0
     *
     * @return string[]
     */
    public function getLoaded(): array
    {
        return array_keys($this->loaded);
    }
}
