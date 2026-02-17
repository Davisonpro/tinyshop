<?php

declare(strict_types=1);

namespace TinyShop\Services\Importers;

final class ImportResult
{
    /**
     * @param string[] $images      Remote image URLs
     * @param string[] $categories  Breadcrumb order (parent → child)
     * @param array[]  $variations  Each: [name => string, price => float|null, attributes => array<string,string>]
     */
    public function __construct(
        public readonly string $title,
        public readonly string $description,
        public readonly float $price,
        public readonly ?float $comparePrice,
        public readonly array $images,
        public readonly array $categories,
        public readonly array $variations,
        public readonly string $currency = 'KES',
        public readonly string $sourcePlatform = 'unknown',
    ) {}

    public function toArray(): array
    {
        return [
            'title'           => $this->title,
            'description'     => $this->description,
            'price'           => $this->price,
            'compare_price'   => $this->comparePrice,
            'images'          => $this->images,
            'categories'      => $this->categories,
            'variations'      => $this->variations,
            'currency'        => $this->currency,
            'source_platform' => $this->sourcePlatform,
        ];
    }
}
