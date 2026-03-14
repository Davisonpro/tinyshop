<?php

declare(strict_types=1);

namespace TinyShop\Services\Importers;

use Psr\Log\LoggerInterface;
use TinyShop\Models\ProductCatalog;
use TinyShop\Models\Setting;

/**
 * AI-powered product parsing and enrichment using Google Gemini API.
 *
 * Two main capabilities:
 * 1. Parse free-form text into identified products (brand, model, price).
 * 2. Enrich identified products with full data (description, images, specs, category).
 *
 * All results are cached in the Product Knowledge Base so the same product
 * never costs tokens twice.
 *
 * @since 1.0.0
 */
final class AiEnricher
{
    private const API_BASE = 'https://generativelanguage.googleapis.com/v1beta/models/';
    private const MODEL = 'gemini-2.5-flash-lite';
    private const TIMEOUT = 60;

    public function __construct(
        private readonly Setting $settingModel,
        private readonly ProductCatalog $catalogModel,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Check if AI is available.
     */
    public function isEnabled(): bool
    {
        return $this->settingModel->get('ai_enabled', '0') === '1'
            && $this->settingModel->get('ai_api_key', '') !== '';
    }

    /**
     * Parse free-form text into structured product items using AI.
     *
     * @param string $text Raw user input (any format).
     * @return array<int, array{brand: string, model: string, canonical_name: string, price: float}>
     */
    public function parseProductList(string $text): array
    {
        if (!$this->isEnabled()) {
            return [];
        }

        $text = trim($text);
        if ($text === '') {
            return [];
        }

        $prompt = <<<PROMPT
Extract every product from this text. Return exactly ONE entry per product line — NEVER split a single product into multiple variants or versions.

Rules:
- brand: uppercase manufacturer name
- model: product name without the brand
- canonical_name: full name as a store would list it (e.g. "Apple AirPods Pro 2")
- price: number (0 if absent, parse 5,500 as 5500)
- If a line is vague (e.g. "Pro 2"), identify the most likely product and return ONE result
- NEVER invent versions, variants, or sub-models that aren't explicitly in the text

{$text}
PROMPT;

        $parseSchema = [
            'type' => 'ARRAY',
            'items' => [
                'type' => 'OBJECT',
                'properties' => [
                    'brand'          => ['type' => 'STRING'],
                    'model'          => ['type' => 'STRING'],
                    'canonical_name' => ['type' => 'STRING'],
                    'price'          => ['type' => 'NUMBER'],
                ],
                'required' => ['brand', 'model', 'canonical_name', 'price'],
            ],
        ];

        $response = $this->callApi($prompt, $parseSchema);

        if ($response === null) {
            return [];
        }

        $json = $this->extractJson($response);

        if (!is_array($json)) {
            $this->logger->warning('ai_enricher.parse_failed', ['response' => substr($response, 0, 500)]);
            return [];
        }

        $items = [];
        foreach ($json as $item) {
            if (!is_array($item)) {
                continue;
            }

            $brand = mb_strtoupper(trim((string) ($item['brand'] ?? '')));
            $model = trim((string) ($item['model'] ?? ''));
            $canonical = trim((string) ($item['canonical_name'] ?? ''));
            $price = (float) ($item['price'] ?? 0);

            if ($canonical === '' && $model === '') {
                continue;
            }

            if ($canonical === '') {
                $canonical = $brand !== '' ? "{$brand} {$model}" : $model;
            }

            $items[] = [
                'brand' => $brand,
                'model' => $model,
                'canonical_name' => $canonical,
                'price' => $price,
            ];
        }

        return $items;
    }

    /**
     * Enrich products with complete data using AI.
     *
     * @param array<int, array{brand: string, model: string, canonical_name: string, price: float}> $items Max 5.
     * @param string $currency Target currency code (e.g. "KES", "USD").
     * @param array<int, array{name: string}> $sellerCategories Seller's existing categories for matching.
     * @return array<int, array<string, mixed>|null>
     */
    public function enrichProducts(array $items, string $currency = 'USD', array $sellerCategories = [], array $shopContext = []): array
    {
        if (!$this->isEnabled() || empty($items)) {
            return array_fill(0, count($items), null);
        }

        $items = array_slice($items, 0, 5);

        // Build product list with price hints
        $productList = [];
        foreach ($items as $i => $item) {
            $parts = ($i + 1) . '. ' . $item['canonical_name'];
            if ($item['price'] > 0) {
                $parts .= " (seller listed at {$currency} {$item['price']})";
            }
            $productList[] = $parts;
        }
        $list = implode("\n", $productList);

        // Build compact context lines
        $ctx = [];
        $storeName = trim($shopContext['store_name'] ?? '');
        $tagline = trim($shopContext['tagline'] ?? '');
        $country = trim($shopContext['country'] ?? '');
        $existingProducts = $shopContext['existing_products'] ?? [];

        if ($storeName !== '') {
            $ctx[] = "Store: {$storeName}";
        }
        if ($tagline !== '') {
            $ctx[] = "Tagline: {$tagline}";
        }
        if ($country !== '') {
            $ctx[] = "Market: {$country}";
        }
        if (!empty($existingProducts)) {
            $ctx[] = 'Existing products: ' . implode(', ', array_slice($existingProducts, 0, 8));
        }
        if (!empty($sellerCategories)) {
            $catNames = array_map(fn(array $c) => $c['name'], array_slice($sellerCategories, 0, 20));
            $ctx[] = 'Categories: ' . implode(', ', $catNames);
        }

        $contextBlock = !empty($ctx) ? implode("\n", $ctx) . "\n" : '';

        $prompt = <<<PROMPT
Generate product data for an online store. Currency: {$currency}. {$country}
{$contextBlock}
Products:
{$list}

Return JSON array, one object per product (null if unknown). Fields:
- name: SHORT product name exactly as the manufacturer markets it. NEVER append category words like "Earbuds", "Wireless", "Headphones", "Speaker", "Smartphone" etc. Examples: "JBL Wave Flex" NOT "JBL Wave Flex True Wireless Earbuds". "Samsung Galaxy A15" NOT "Samsung Galaxy A15 Smartphone". "Sony WH-1000XM5" NOT "Sony WH-1000XM5 Wireless Noise Cancelling Headphones"
- description: 1-2 sentence sales pitch with keywords buyers search for. Never empty
- full_description: 2-3 paragraph HTML (<p>,<strong>,<ul><li>) covering features, benefits, use cases. Include searchable keywords naturally. Never empty
- category: pick from store's categories if listed above, else suggest one
- specs: 5-10 key:value pairs
- price: retail price in {$currency}. MUST be positive. Use seller's price if given, else estimate market price. NEVER 0
- compare_price: MSRP if higher than price, else same as price
- variations: [{name:"Color",options:[{value:"Black"}]}] — include Color/Size/Storage where applicable. [] only if truly no options
- source_url: product page URL from official site or major retailer
PROMPT;

        $response = $this->callApi($prompt, $this->buildEnrichSchema());

        if ($response === null) {
            $this->logger->warning('ai_enricher.enrich_null_response');
            return array_fill(0, count($items), null);
        }

        return $this->processEnrichResponse($response, $items);
    }

    /**
     * Build JSON schema for the Gemini response to enforce required fields.
     *
     * @return array Gemini-compatible response schema.
     */
    private function buildEnrichSchema(): array
    {
        $productSchema = [
            'type' => 'OBJECT',
            'properties' => [
                'name'             => ['type' => 'STRING'],
                'description'      => ['type' => 'STRING'],
                'full_description' => ['type' => 'STRING'],
                'category'         => ['type' => 'STRING'],
                'specs'            => ['type' => 'OBJECT'],
                'price'            => ['type' => 'NUMBER'],
                'compare_price'    => ['type' => 'NUMBER'],
                'variations'       => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'name'    => ['type' => 'STRING'],
                            'options' => [
                                'type' => 'ARRAY',
                                'items' => [
                                    'type' => 'OBJECT',
                                    'properties' => [
                                        'value' => ['type' => 'STRING'],
                                    ],
                                    'required' => ['value'],
                                ],
                            ],
                        ],
                        'required' => ['name', 'options'],
                    ],
                ],
                'source_url' => ['type' => 'STRING'],
            ],
            'required' => ['name', 'description', 'full_description', 'category', 'price', 'variations'],
        ];

        return [
            'type' => 'ARRAY',
            'items' => $productSchema,
        ];
    }

    /**
     * Call the Google Gemini API via cURL with retry on transient errors.
     *
     * @param string $prompt The prompt text.
     * @param array|null $responseSchema Optional JSON schema to enforce response structure.
     */
    private function callApi(string $prompt, ?array $responseSchema = null): ?string
    {
        $apiKey = $this->settingModel->get('ai_api_key', '');

        if ($apiKey === '') {
            return null;
        }

        $url = self::API_BASE . self::MODEL . ':generateContent?key=' . urlencode($apiKey);

        $generationConfig = [
            'responseMimeType' => 'application/json',
            'temperature' => 0.3,
        ];

        if ($responseSchema !== null) {
            $generationConfig['responseSchema'] = $responseSchema;
        }

        $body = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => $generationConfig,
        ];

        $payload = json_encode($body);

        $maxRetries = 2;

        for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => self::TIMEOUT,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                ],
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            $body = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);

            if ($body === false || $err !== '') {
                $this->logger->error('ai_enricher.api_failed', ['error' => $err, 'attempt' => $attempt]);
                if ($attempt < $maxRetries) {
                    usleep(500_000 * ($attempt + 1));
                    continue;
                }
                throw new AiException('Could not connect to AI service. Please try again.');
            }

            // Retry on 429 (rate limit) or 5xx (server error)
            if ($code === 429 || $code >= 500) {
                $this->logger->warning('ai_enricher.api_retrying', ['code' => $code, 'attempt' => $attempt]);
                if ($attempt < $maxRetries) {
                    usleep(1_000_000 * ($attempt + 1));
                    continue;
                }
                $this->logger->warning('ai_enricher.api_error', ['code' => $code, 'body' => substr((string) $body, 0, 500)]);
                throw new AiException(
                    $code === 429
                        ? 'AI service is busy. Please wait a moment and try again.'
                        : 'AI service is temporarily unavailable. Please try again later.',
                    rateLimited: $code === 429,
                );
            }

            if ($code !== 200) {
                $this->logger->warning('ai_enricher.api_error', ['code' => $code, 'body' => substr((string) $body, 0, 500)]);
                throw new AiException('AI request failed. Please check your API key in Settings.');
            }

            $data = json_decode((string) $body, true);

            // Gemini response: candidates[0].content.parts[0].text
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if ($text === null) {
                $this->logger->warning('ai_enricher.empty_response', ['body' => substr((string) $body, 0, 500)]);
            }

            return $text;
        }

        return null;
    }

    /**
     * Process the enrichment response and cache results in PKB.
     *
     * @return array<int, array<string, mixed>|null>
     */
    private function processEnrichResponse(string $response, array $items): array
    {
        $json = $this->extractJson($response);

        if (!is_array($json)) {
            $this->logger->warning('ai_enricher.enrich_parse_failed', ['response' => substr($response, 0, 500)]);
            return array_fill(0, count($items), null);
        }

        $results = [];
        foreach ($items as $i => $item) {
            $product = $json[$i] ?? null;

            if (!is_array($product) || empty($product['name'])) {
                $this->logger->info('ai_enricher.product_null', [
                    'index' => $i,
                    'canonical' => $item['canonical_name'],
                ]);
                $results[] = null;
                continue;
            }

            // Normalize images — only keep valid HTTP(S) URLs
            $images = $product['images'] ?? [];
            if (!is_array($images)) {
                $images = [];
            }
            $images = array_values(array_filter($images, function ($url) {
                return is_string($url)
                    && $url !== ''
                    && (bool) filter_var($url, FILTER_VALIDATE_URL)
                    && preg_match('#^https?://#i', $url);
            }));

            // Normalize specs
            $specs = $product['specs'] ?? [];
            if (!is_array($specs)) {
                $specs = [];
            }

            // Normalize variations — filter out malformed groups
            $variations = $product['variations'] ?? [];
            if (!is_array($variations)) {
                $variations = [];
            }
            $variations = array_values(array_filter($variations, function ($group) {
                return is_array($group)
                    && !empty($group['name'])
                    && !empty($group['options'])
                    && is_array($group['options']);
            }));

            // Price resolution: AI price → user-provided price → flag as needing input
            $aiPrice = (float) ($product['price'] ?? 0);
            $userPrice = (float) $item['price'];
            $finalPrice = $aiPrice > 0 ? $aiPrice : ($userPrice > 0 ? $userPrice : 0);

            // If user provided a price and AI didn't, use the user's
            // If both exist, prefer user's price (they know their market)
            if ($userPrice > 0) {
                $finalPrice = $userPrice;
            }

            // Compare price must be >= selling price
            $comparePrice = (float) ($product['compare_price'] ?? 0);
            if ($comparePrice > 0 && $comparePrice < $finalPrice) {
                $comparePrice = 0;
            }

            $this->logger->info('ai_enricher.product_resolved', [
                'name' => $product['name'],
                'ai_price' => $aiPrice,
                'user_price' => $userPrice,
                'final_price' => $finalPrice,
                'images' => count($images),
                'variations' => count($variations),
                'category' => $product['category'] ?? null,
            ]);

            $catalogData = [
                'brand' => $item['brand'],
                'model' => $item['model'],
                'canonical_name' => $product['name'],
                'description' => trim((string) ($product['description'] ?? '')),
                'full_description' => trim((string) ($product['full_description'] ?? '')),
                'specs' => $specs,
                'images' => $images,
                'category_hint' => $product['category'] ?? null,
                'source_url' => $product['source_url'] ?? null,
                'source_site' => 'ai',
                'quality_score' => $this->calculateQuality($product),
                'variations' => $variations,
                'compare_price' => $comparePrice,
                'price' => $finalPrice,
            ];

            // Cache in PKB
            try {
                $this->catalogModel->upsert($catalogData);
            } catch (\Throwable $e) {
                $this->logger->warning('ai_enricher.cache_failed', ['error' => $e->getMessage()]);
            }

            $results[] = $catalogData;
        }

        return $results;
    }

    /**
     * Calculate a quality score (0-100) for enriched data.
     */
    private function calculateQuality(array $data): int
    {
        $score = 0;

        if (!empty($data['name'])) {
            $score += 15;
        }
        if (!empty($data['description'])) {
            $score += 15;
        }
        if (!empty($data['full_description']) && mb_strlen($data['full_description']) > 50) {
            $score += 15;
        }
        if (!empty($data['images']) && is_array($data['images']) && count($data['images']) > 0) {
            $score += 25;
            if (count($data['images']) >= 3) {
                $score += 5;
            }
        }
        if (!empty($data['specs']) && is_array($data['specs']) && count($data['specs']) >= 3) {
            $score += 15;
        }
        if (!empty($data['category'])) {
            $score += 5;
        }
        if (!empty($data['source_url'])) {
            $score += 5;
        }

        return min(100, $score);
    }

    /**
     * Extract a JSON array from a response that may contain markdown code fences.
     */
    private function extractJson(string $text): mixed
    {
        $text = trim($text);

        // Strip markdown code fences
        if (preg_match('/```(?:json)?\s*\n?(.*?)\n?\s*```/s', $text, $m)) {
            $text = trim($m[1]);
        }

        $decoded = json_decode($text, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        // Try to find a JSON array in the text
        if (preg_match('/\[.*\]/s', $text, $m)) {
            $decoded = json_decode($m[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return null;
    }
}
