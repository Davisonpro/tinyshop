<?php

declare(strict_types=1);

namespace TinyShop\Services\Importers;

/**
 * Imported product data.
 *
 * @since 1.0.0
 */
final class ImportResult
{
    /**
     * @param string   $title            Product title.
     * @param string   $description      Full description (may contain HTML).
     * @param string   $shortDescription Short summary.
     * @param float    $price            Selling price.
     * @param ?float   $comparePrice     Compare-at price.
     * @param string[] $images           Image URLs.
     * @param string[] $categories       Category names.
     * @param array[]  $variations       Variant data.
     * @param string   $currency         ISO 4217 currency code.
     * @param string   $sourcePlatform   Source platform identifier.
     */
    public function __construct(
        public readonly string $title,
        public readonly string $description,
        public readonly string $shortDescription,
        public readonly float $price,
        public readonly ?float $comparePrice,
        public readonly array $images,
        public readonly array $categories,
        public readonly array $variations,
        public readonly string $currency = 'KES',
        public readonly string $sourcePlatform = 'unknown',
        public readonly string $sourceUrl = '',
        public readonly bool $isSold = false,
        public readonly bool $isFeatured = false,
        public readonly string $metaTitle = '',
        public readonly string $metaDescription = '',
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'title'             => $this->title,
            'description'       => $this->shortDescription,
            'full_description'  => $this->description,
            'price'             => $this->price,
            'compare_price'     => $this->comparePrice,
            'images'            => $this->images,
            'categories'        => $this->categories,
            'variations'        => $this->variations,
            'currency'          => $this->currency,
            'source_platform'   => $this->sourcePlatform,
            'source_url'        => $this->sourceUrl,
            'is_sold'           => $this->isSold,
            'is_featured'       => $this->isFeatured,
            'meta_title'        => $this->metaTitle,
            'meta_description'  => $this->metaDescription,
        ];
    }
}
