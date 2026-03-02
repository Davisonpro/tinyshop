<?php

declare(strict_types=1);

namespace TinyShop\Services\Importers;

/**
 * Product importer contract.
 *
 * @since 1.0.0
 */
interface ImporterInterface
{
    /**
     * Check if this importer supports the given URL.
     *
     * @since 1.0.0
     *
     * @param  string $url Product URL.
     * @return bool
     */
    public function supports(string $url): bool;

    /**
     * Fetch and parse product data from a URL.
     *
     * @since 1.0.0
     *
     * @param  string $url Product URL.
     * @return ImportResult
     *
     * @throws \RuntimeException On fetch or parse failure.
     */
    public function fetch(string $url): ImportResult;
}
