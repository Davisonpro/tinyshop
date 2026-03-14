<?php

declare(strict_types=1);

namespace TinyShop\Services\Importers;

use Psr\Log\LoggerInterface;
use TinyShop\Models\ProductCatalog;

/**
 * Resolves identified products into complete, import-ready data.
 *
 * Resolution cascade:
 * 1. PKB cache (instant, 0 tokens)
 * 2. Web lookup from whitelisted sources (0 tokens)
 * 3. AI enrichment (tokens, cached for future)
 *
 * @since 1.0.0
 */
final class ProductResolver
{
    public function __construct(
        private readonly ProductCatalog $catalogModel,
        private readonly WebLookup $webLookup,
        private readonly AiEnricher $aiEnricher,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Resolve multiple products through the cascade.
     *
     * @param array<int, array{brand: string, model: string, canonical_name: string, price: float}> $items
     * @param string $currency Target currency code.
     * @param array<int, array{name: string}> $sellerCategories Seller's existing categories.
     * @return array<int, array{name: string, description: string, full_description: string, images: array, specs: array, category_hint: ?string, price: float, source: string, source_url: ?string, confidence: string}>
     */
    public function resolveAll(array $items, string $currency = 'USD', array $sellerCategories = [], array $shopContext = []): array
    {
        if (empty($items)) {
            return [];
        }

        $results = [];
        $misses = [];

        // Layer 1: Bulk PKB lookup
        $cached = $this->catalogModel->findMany($items);

        foreach ($items as $i => $item) {
            $brand = mb_strtoupper(trim($item['brand']));
            $model = mb_strtoupper(trim($item['model']));
            $key = "{$brand}|{$model}";

            if (isset($cached[$key])) {
                $entry = $cached[$key];
                $entryPrice = (float) ($entry['price'] ?? 0);
                $inputPrice = (float) $item['price'];

                // PKB has full data including price — use it directly
                if ($this->isComplete($entry)) {
                    $this->catalogModel->incrementLookup((int) $entry['id']);
                    $results[$i] = $this->buildResult($entry, $inputPrice, 'pkb', 'high');
                    continue;
                }

                // PKB has name+desc but no price, and user provided one — use PKB data + user price
                $name = trim((string) ($entry['canonical_name'] ?? $entry['name'] ?? ''));
                $desc = trim((string) ($entry['description'] ?? ''));
                if ($name !== '' && $desc !== '' && $inputPrice > 0) {
                    $entry['price'] = $inputPrice;
                    $this->catalogModel->incrementLookup((int) $entry['id']);
                    $results[$i] = $this->buildResult($entry, $inputPrice, 'pkb', 'high');
                    continue;
                }
            }

            $misses[$i] = $item;
        }

        if (empty($misses)) {
            ksort($results);
            return $results;
        }

        // Layer 2: Web lookup for misses
        $aiCandidates = [];
        foreach ($misses as $i => $item) {
            try {
                $webResult = $this->webLookup->lookup(
                    mb_strtoupper(trim($item['brand'])),
                    trim($item['model'])
                );
                if ($webResult !== null && $this->isComplete($webResult)) {
                    $results[$i] = $this->buildResult($webResult, $item['price'], 'web', 'high');
                    continue;
                }
            } catch (\Throwable $e) {
                $this->logger->warning('resolver.web_failed', [
                    'product' => $item['canonical_name'],
                    'error' => $e->getMessage(),
                ]);
            }

            $aiCandidates[$i] = $item;
        }

        // Layer 3: AI enrichment for remaining misses
        if (!empty($aiCandidates) && $this->aiEnricher->isEnabled()) {
            $aiItems = array_values($aiCandidates);
            $aiIndices = array_keys($aiCandidates);

            // Process in batches of 5
            $batches = array_chunk($aiItems, 5);
            $indexBatches = array_chunk($aiIndices, 5);

            foreach ($batches as $batchIdx => $batch) {
                try {
                    $aiResults = $this->aiEnricher->enrichProducts($batch, $currency, $sellerCategories, $shopContext);
                    foreach ($aiResults as $j => $aiResult) {
                        $idx = $indexBatches[$batchIdx][$j];
                        if ($aiResult !== null && $this->isComplete($aiResult)) {
                            $results[$idx] = $this->buildResult(
                                $aiResult,
                                $aiCandidates[$idx]['price'],
                                'ai',
                                'medium'
                            );
                        }
                    }
                } catch (\Throwable $e) {
                    $this->logger->warning('resolver.ai_batch_failed', ['error' => $e->getMessage()]);
                }
            }
        }

        ksort($results);
        return $results;
    }

    /**
     * Check if a product has enough data to import.
     *
     * Requires: name, description, and a positive price.
     */
    private function isComplete(array $data): bool
    {
        $name = trim((string) ($data['canonical_name'] ?? $data['name'] ?? ''));
        $desc = trim((string) ($data['description'] ?? ''));
        $price = (float) ($data['price'] ?? 0);

        return $name !== '' && $desc !== '' && $price > 0;
    }

    /**
     * Build a standardized result from resolved data.
     */
    private function buildResult(array $data, float $inputPrice, string $source, string $confidence): array
    {
        // Parse images — could be JSON string from DB or array from AI
        $images = $data['images'] ?? [];
        if (is_string($images)) {
            $decoded = json_decode($images, true);
            $images = is_array($decoded) ? $decoded : [];
        }

        // Parse specs
        $specs = $data['specs'] ?? [];
        if (is_string($specs)) {
            $decoded = json_decode($specs, true);
            $specs = is_array($decoded) ? $decoded : [];
        }

        // Price: user input takes priority, then AI/resolved price
        $resolvedPrice = (float) ($data['price'] ?? 0);
        $price = $inputPrice > 0 ? $inputPrice : $resolvedPrice;

        // Parse variations
        $variations = $data['variations'] ?? [];
        if (is_string($variations)) {
            $decoded = json_decode($variations, true);
            $variations = is_array($decoded) ? $decoded : [];
        }

        // Compare price
        $comparePrice = (float) ($data['compare_price'] ?? 0);
        if ($comparePrice > 0 && $comparePrice < $price) {
            $comparePrice = 0;
        }

        return [
            'name' => trim((string) ($data['canonical_name'] ?? $data['name'] ?? '')),
            'description' => trim((string) ($data['description'] ?? '')),
            'full_description' => trim((string) ($data['full_description'] ?? '')),
            'images' => $images,
            'specs' => $specs,
            'category_hint' => $data['category_hint'] ?? $data['category'] ?? null,
            'price' => $price,
            'compare_price' => $comparePrice,
            'variations' => $variations,
            'source' => $source,
            'source_url' => $data['source_url'] ?? null,
            'confidence' => $confidence,
        ];
    }
}
