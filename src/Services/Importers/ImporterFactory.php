<?php

declare(strict_types=1);

namespace TinyShop\Services\Importers;

use RuntimeException;

final class ImporterFactory
{
    /** @var ImporterInterface[] */
    private array $importers;

    public function __construct(
        ShopifyImporter $shopify,
        WooCommerceImporter $wooCommerce,
    ) {
        // Shopify checked first (uses JSON endpoint, cheaper than HTML scan)
        $this->importers = [
            $shopify,
            $wooCommerce,
        ];
    }

    /** Resolve the correct importer for a given URL. */
    public function resolve(string $url): ImporterInterface
    {
        foreach ($this->importers as $importer) {
            if ($importer->supports($url)) {
                return $importer;
            }
        }

        throw new RuntimeException(
            'No importer available for this URL. Supported: WooCommerce, Shopify stores.'
        );
    }
}
