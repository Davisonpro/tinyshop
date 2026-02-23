<?php

declare(strict_types=1);

namespace TinyShop\Services\Importers;

use RuntimeException;

final class ShopifyImporter implements ImporterInterface
{
    /** Known Shopify-powered domains (extend as needed). */
    private const KNOWN_DOMAINS = [
        'myshopify.com',
        'minimog.co',
    ];

    public function __construct(private readonly HttpClient $http)
    {
    }

    public function supports(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST) ?? '';
        foreach (self::KNOWN_DOMAINS as $domain) {
            if (str_ends_with($host, $domain)) {
                return true;
            }
        }
        return false;
    }

    public function fetch(string $url): ImportResult
    {
        $handle  = $this->extractHandle($url);
        $baseUrl = $this->baseUrl($url);
        $json    = $this->http->get($baseUrl . '/products/' . $handle . '.json');
        $data    = json_decode($json, true);

        if (!isset($data['product'])) {
            throw new RuntimeException('Invalid Shopify product response');
        }

        $product = $data['product'];

        $title    = $product['title'] ?? '';
        $bodyHtml = $product['body_html'] ?? '';

        // Full description: preserve safe HTML tags
        $description = strip_tags($bodyHtml, '<p><br><b><strong><i><em><ul><ol><li><h2><h3><a>');
        $description = preg_replace('/\s+(style|class|id)\s*=\s*"[^"]*"/i', '', $description);
        $description = preg_replace('/\s+(style|class|id)\s*=\s*\'[^\']*\'/i', '', $description);
        $description = trim($description);

        // Short description: first ~300 chars as plain text
        $shortDescription = '';
        if ($bodyHtml !== '') {
            $plain = trim(preg_replace('/\s+/', ' ', strip_tags($bodyHtml)));
            $shortDescription = mb_strlen($plain) > 300 ? mb_substr($plain, 0, 297) . '...' : $plain;
        }

        $images      = array_map(fn($img) => $img['src'], $product['images'] ?? []);
        $categories  = array_filter([$product['product_type'] ?? '']);

        // Price from first variant
        $firstVariant = $product['variants'][0] ?? [];
        $price        = (float) ($firstVariant['price'] ?? 0);
        $comparePrice = !empty($firstVariant['compare_at_price'])
            ? (float) $firstVariant['compare_at_price']
            : null;

        // Build variations (skip if single default variant)
        $variations = [];
        $variants   = $product['variants'] ?? [];
        $isDefault  = count($variants) === 1
            && ($variants[0]['title'] ?? '') === 'Default Title';

        if (!$isDefault) {
            foreach ($variants as $v) {
                $attributes = [];
                foreach (['option1', 'option2', 'option3'] as $i => $key) {
                    if (!empty($v[$key])) {
                        $optionName = $product['options'][$i]['name'] ?? ('Option ' . ($i + 1));
                        $attributes[$optionName] = $v[$key];
                    }
                }

                $vCompare = !empty($v['compare_at_price'])
                    ? (float) $v['compare_at_price']
                    : null;

                $variations[] = [
                    'name'          => $v['title'] ?? '',
                    'price'         => (float) ($v['price'] ?? 0),
                    'compare_price' => $vCompare,
                    'attributes'    => $attributes,
                ];
            }
        }

        return new ImportResult(
            title:            $title,
            description:      $description,
            shortDescription: $shortDescription,
            price:            $price,
            comparePrice:     $comparePrice,
            images:           $images,
            categories:       $categories,
            variations:       $variations,
            currency:         'USD',
            sourcePlatform:   'shopify',
        );
    }

    private function extractHandle(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        // /products/some-handle or /collections/xxx/products/some-handle
        if (preg_match('#/products/([^/?]+)#', $path, $m)) {
            return $m[1];
        }
        throw new RuntimeException('Cannot extract product handle from URL');
    }

    private function baseUrl(string $url): string
    {
        $scheme = parse_url($url, PHP_URL_SCHEME) ?? 'https';
        $host   = parse_url($url, PHP_URL_HOST) ?? '';
        return $scheme . '://' . $host;
    }
}
