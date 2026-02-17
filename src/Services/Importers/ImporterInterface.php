<?php

declare(strict_types=1);

namespace TinyShop\Services\Importers;

interface ImporterInterface
{
    /** Whether this importer can handle the given URL. */
    public function supports(string $url): bool;

    /** Fetch product data from the URL. */
    public function fetch(string $url): ImportResult;
}
