<?php

declare(strict_types=1);

namespace TinyShop\Controllers\Api;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use TinyShop\App;
use TinyShop\Controllers\Traits\JsonResponder;
use TinyShop\Models\Category;
use TinyShop\Models\Product;
use TinyShop\Models\ProductImage;
use TinyShop\Models\User;
use TinyShop\Services\Auth;
use TinyShop\Services\DB;
use TinyShop\Services\Importers\AiEnricher;
use TinyShop\Services\Importers\AiException;
use TinyShop\Services\Importers\BulkTextParser;
use TinyShop\Services\Importers\HttpClient;
use TinyShop\Services\Importers\ProductResolver;
use TinyShop\Services\Upload;
use TinyShop\Services\Validation;

/**
 * AI-powered smart product import controller.
 *
 * Flow: parse (AI identifies products) → resolve (cascade fills data) → save.
 *
 * @since 1.0.0
 */
final class SmartImportController
{
    use JsonResponder;

    private const MAX_IMAGE_SIZE = 10 * 1024 * 1024; // 10 MB

    public function __construct(
        private readonly Auth $auth,
        private readonly DB $db,
        private readonly BulkTextParser $parser,
        private readonly ProductResolver $resolver,
        private readonly AiEnricher $aiEnricher,
        private readonly Product $productModel,
        private readonly ProductImage $productImageModel,
        private readonly Category $categoryModel,
        private readonly HttpClient $httpClient,
        private readonly Upload $upload,
        private readonly Validation $validation,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Parse free-form text into identified products using AI.
     *
     * POST /api/import/smart-parse
     */
    public function parse(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $text = trim($data['text'] ?? '');

        if ($text === '') {
            return $this->json($response, ['error' => true, 'message' => 'Please enter some product text'], 422);
        }

        if (!$this->aiEnricher->isEnabled()) {
            return $this->json($response, ['error' => true, 'message' => 'AI is not configured. Ask your admin to set up the AI API key in Settings.'], 422);
        }

        // Clean text (remove junk lines, normalize whitespace)
        $cleanText = $this->parser->clean($text);

        if ($cleanText === '') {
            return $this->json($response, ['error' => true, 'message' => 'Could not find any product text to process'], 422);
        }

        // AI parses the text into structured products
        try {
            $items = $this->aiEnricher->parseProductList($cleanText);
        } catch (AiException $e) {
            return $this->json($response, ['error' => true, 'message' => $e->getMessage()], 503);
        }

        if (empty($items)) {
            return $this->json($response, ['error' => true, 'message' => 'Could not identify any products. Try including product names and optionally prices.'], 422);
        }

        return $this->json($response, [
            'success' => true,
            'items' => array_values($items),
            'count' => count($items),
        ]);
    }

    /**
     * Resolve identified products with complete data through the cascade.
     *
     * POST /api/import/smart-resolve
     */
    public function resolve(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $items = $data['items'] ?? [];

        if (!is_array($items) || empty($items)) {
            return $this->json($response, ['error' => true, 'message' => 'No items to resolve'], 422);
        }

        // Limit batch size
        $items = array_slice($items, 0, 10);

        // Validate each item
        $validated = [];
        foreach ($items as $item) {
            $canonical = trim($item['canonical_name'] ?? '');
            if ($canonical === '' && empty($item['brand']) && empty($item['model'])) {
                continue;
            }
            $validated[] = [
                'brand' => trim($item['brand'] ?? ''),
                'model' => trim($item['model'] ?? ''),
                'canonical_name' => $canonical,
                'price' => (float) ($item['price'] ?? 0),
            ];
        }

        if (empty($validated)) {
            return $this->json($response, ['error' => true, 'message' => 'No valid items to resolve'], 422);
        }

        $userId = $this->auth->userId();
        $user = User::find($userId);
        $currency = $user['currency'] ?? App::DEFAULT_CURRENCY;

        // Load seller's categories so the AI can match them
        $userCategories = $this->categoryModel->findByUser($userId);

        // Build shop context for AI — helps write relevant descriptions and pricing
        $shopContext = [
            'store_name' => $user['store_name'] ?? '',
            'tagline' => $user['shop_tagline'] ?? '',
            'currency' => $currency,
            'country' => $this->currencyToCountry($currency),
        ];

        // Include existing product names so AI understands the catalog style
        $existingProducts = $this->productModel->findByUser($userId, 10);
        if (!empty($existingProducts)) {
            $shopContext['existing_products'] = array_map(
                fn(array $p) => $p['name'],
                $existingProducts
            );
        }

        try {
            $results = $this->resolver->resolveAll($validated, $currency, $userCategories, $shopContext);
        } catch (AiException $e) {
            return $this->json($response, ['error' => true, 'message' => $e->getMessage()], 503);
        }

        // Only return products with complete data
        $complete = [];
        $incomplete = 0;
        foreach ($results as $result) {
            if (!empty($result['name']) && !empty($result['description'])) {
                $complete[] = $result;
            } else {
                $incomplete++;
            }
        }

        $message = null;
        if ($incomplete > 0 && empty($complete)) {
            $message = "Could not find complete product data for any of the {$incomplete} product(s). Try more specific product names.";
        } elseif ($incomplete > 0) {
            $message = "{$incomplete} product(s) skipped — not enough data found.";
        }

        // Fetch product images from local e-commerce shops via Bing
        $country = $shopContext['country'] ?? '';
        foreach ($complete as &$product) {
            if (!empty($product['name'])) {
                $product['images'] = $this->searchProductImages($product['name'], $country);
            } else {
                $product['images'] = [];
            }
        }
        unset($product);

        // Match AI category hints to existing user categories (fuzzy)
        foreach ($complete as &$product) {
            $hint = mb_strtolower(trim($product['category_hint'] ?? ''));
            if ($hint === '') {
                continue;
            }

            $bestMatch = null;
            $bestScore = 0;

            foreach ($userCategories as $cat) {
                $catName = mb_strtolower($cat['name']);

                // Exact match — best possible
                if ($catName === $hint) {
                    $bestMatch = $cat;
                    break;
                }

                // One contains the other (e.g. "Headphones" matches "Audio & Headphones")
                $score = 0;
                if (str_contains($catName, $hint)) {
                    $score = 80;
                } elseif (str_contains($hint, $catName)) {
                    $score = 70;
                } else {
                    // Check word overlap (e.g. "Running Shoes" vs "Shoes")
                    $hintWords = preg_split('/[\s&,]+/', $hint);
                    $catWords = preg_split('/[\s&,]+/', $catName);
                    $common = array_intersect($hintWords, $catWords);
                    if (!empty($common)) {
                        $score = (int) (60 * count($common) / max(count($hintWords), count($catWords)));
                    }
                }

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestMatch = $cat;
                }
            }

            if ($bestMatch !== null && $bestScore >= 50) {
                $product['matched_category_id'] = (int) $bestMatch['id'];
                $product['matched_category_name'] = $bestMatch['name'];
            }
        }
        unset($product);

        return $this->json($response, [
            'success' => true,
            'results' => array_values($complete),
            'count' => count($complete),
            'skipped' => $incomplete,
            'message' => $message,
            'categories' => array_map(fn($c) => ['id' => (int) $c['id'], 'name' => $c['name']], $userCategories),
        ]);
    }

    /**
     * Save resolved products to the seller's catalog.
     *
     * POST /api/import/smart-save
     */
    public function save(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $products = $data['products'] ?? [];
        $userId = $this->auth->userId();

        if (!is_array($products) || empty($products)) {
            return $this->json($response, ['error' => true, 'message' => 'No products to save'], 422);
        }

        // Limit batch size
        $products = array_slice($products, 0, 10);

        $saved = [];
        $errors = [];
        $duplicates = [];

        // Pre-check for duplicates by slug
        $existingSlugs = [];
        foreach ($products as $product) {
            $name = trim($product['name'] ?? '');
            if ($name === '') {
                continue;
            }
            $slug = $this->validation->slug($name);
            $existing = $this->productModel->findBySlug($userId, $slug);
            if ($existing) {
                $existingSlugs[$slug] = $existing['name'];
            }
        }

        foreach ($products as $i => $product) {
            $name = trim($product['name'] ?? '');
            $price = (float) ($product['price'] ?? 0);

            if ($name === '') {
                $errors[] = ['index' => $i, 'message' => 'Product name is required'];
                continue;
            }

            if ($price <= 0) {
                $errors[] = ['index' => $i, 'name' => $name, 'message' => 'Price must be greater than 0'];
                continue;
            }

            // Duplicate warning (still saves with unique slug)
            $slug = $this->validation->slug($name);
            if (isset($existingSlugs[$slug])) {
                $duplicates[] = ['index' => $i, 'name' => $name, 'existing' => $existingSlugs[$slug]];
            }

            try {
                $pdo = $this->db->pdo();
                $pdo->beginTransaction();

                $productId = $this->createProduct($userId, $product);

                $pdo->commit();

                $saved[] = [
                    'index' => $i,
                    'product_id' => $productId,
                    'name' => $name,
                ];
            } catch (\Throwable $e) {
                if ($this->db->pdo()->inTransaction()) {
                    $this->db->pdo()->rollBack();
                }
                $this->logger->error('smart_import.save_failed', [
                    'name' => $name,
                    'error' => $e->getMessage(),
                ]);
                $errors[] = ['index' => $i, 'name' => $name, 'message' => 'Failed to save product'];
            }
        }

        return $this->json($response, [
            'success' => true,
            'saved' => $saved,
            'errors' => $errors,
            'duplicates' => $duplicates,
            'saved_count' => count($saved),
        ], count($saved) > 0 ? 201 : 422);
    }

    /**
     * Create a single product from resolved data.
     */
    private function createProduct(int $userId, array $data): int
    {
        $name = trim($data['name']);
        $price = (float) $data['price'];

        // Resolve category
        $categoryId = null;
        $categoryHint = trim($data['category_hint'] ?? $data['category'] ?? '');
        if ($categoryHint !== '') {
            $existing = $this->categoryModel->findByUserNameAndParent($userId, $categoryHint, null);
            if ($existing) {
                $categoryId = (int) $existing['id'];
            } else {
                $categoryId = $this->categoryModel->create([
                    'user_id' => $userId,
                    'name' => $categoryHint,
                ]);
            }
        }

        // Download and store images (with size limit)
        $imageUrls = [];
        $remoteImages = $data['images'] ?? [];
        if (is_array($remoteImages)) {
            foreach (array_slice($remoteImages, 0, 5) as $imgUrl) {
                $stored = $this->downloadAndStoreImage($imgUrl);
                if ($stored !== null) {
                    $imageUrls[] = $stored;
                }
            }
        }

        // Generate unique slug
        $slug = $this->validation->slug($name);
        $slug = $this->productModel->ensureUniqueSlug($userId, $slug);

        // Sanitize descriptions (AI-generated HTML can contain unsafe tags)
        $shortDesc = $this->validation->sanitizeHtml(trim($data['description'] ?? ''));
        $fullDesc = $this->validation->sanitizeHtml(trim($data['full_description'] ?? ''));

        // Append specs table to full description
        $specs = $data['specs'] ?? [];
        if (is_array($specs) && !empty($specs)) {
            $specRows = '';
            foreach ($specs as $key => $value) {
                $key = htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8');
                $value = htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
                $specRows .= "<tr><td><strong>{$key}</strong></td><td>{$value}</td></tr>";
            }
            $specsHtml = "<h3>Specifications</h3><table class=\"specs-table\">{$specRows}</table>";
            $fullDesc = $fullDesc !== '' ? "{$fullDesc}\n{$specsHtml}" : $specsHtml;
        }

        // Process variations
        $variations = null;
        if (!empty($data['variations']) && is_array($data['variations'])) {
            $variations = $this->validation->sanitizeVariations($data['variations']);
        }

        // Compare price (must be greater than selling price)
        $comparePrice = isset($data['compare_price']) && $data['compare_price'] !== '' && $data['compare_price'] !== null
            ? (float) $data['compare_price'] : null;
        if ($comparePrice !== null && $comparePrice <= $price) {
            $comparePrice = null;
        }

        // Generate meta description from short description (plain text)
        $metaDesc = $shortDesc !== '' ? mb_substr(strip_tags($shortDesc), 0, 160) : null;

        $productId = $this->productModel->create([
            'user_id' => $userId,
            'category_id' => $categoryId,
            'name' => $name,
            'slug' => $slug,
            'description' => $shortDesc,
            'full_description' => $fullDesc,
            'price' => $price,
            'compare_price' => $comparePrice,
            'image_url' => $imageUrls[0] ?? null,
            'sort_order' => 0,
            'is_sold' => !empty($data['is_sold']) ? 1 : 0,
            'stock_quantity' => null,
            'is_featured' => !empty($data['is_featured']) ? 1 : 0,
            'variations' => $variations,
            'meta_title' => null,
            'meta_description' => $metaDesc,
            'source_url' => trim($data['source_url'] ?? '') ?: null,
        ]);

        if (!empty($imageUrls)) {
            $this->productImageModel->sync($productId, $imageUrls);
        }

        $this->logger->info('smart_import.product_created', [
            'user_id' => $userId,
            'product_id' => $productId,
            'name' => $name,
            'image_count' => count($imageUrls),
            'has_variations' => $variations !== null,
            'source' => $data['source'] ?? 'unknown',
        ]);

        return $productId;
    }

    /**
     * Check if a remote image URL actually exists via HEAD request.
     */
    private function imageExists(string $url): bool
    {
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_NOBODY         => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 3,
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_USERAGENT      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            curl_close($ch);

            // Must be 2xx and an image content type
            return $code >= 200 && $code < 300
                && ($contentType === null || str_starts_with((string) $contentType, 'image/'));
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Download a remote image and store it locally.
     */
    private function downloadAndStoreImage(string $url): ?string
    {
        try {
            $tmpPath = $this->httpClient->download($url);

            // Reject oversized files
            $size = filesize($tmpPath);
            if ($size === false || $size > self::MAX_IMAGE_SIZE) {
                @unlink($tmpPath);
                return null;
            }

            // Verify it's actually an image
            $mime = mime_content_type($tmpPath);
            if ($mime === false || !str_starts_with($mime, 'image/')) {
                @unlink($tmpPath);
                return null;
            }

            $filename = basename(parse_url($url, PHP_URL_PATH) ?? 'image.jpg');
            $stored = $this->upload->storeFromPath($tmpPath, $filename);
            @unlink($tmpPath);

            return $stored;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Search for product images via Bing, prioritizing local e-commerce shops.
     *
     * Adding the country to the query naturally surfaces local retailers
     * (e.g. "JBL Wave Beam Kenya" → phoneplacekenya.com, jumia.co.ke).
     *
     * @return array<string> Up to 3 valid image URLs.
     */
    private function searchProductImages(string $productName, string $country = ''): array
    {
        try {
            // Quoted name forces exact match; "price" biases toward e-commerce listings
            $query = '"' . $productName . '" price';
            if ($country !== '') {
                $query .= ' ' . $country;
            }

            // aspect-square = e-commerce product shots (shops use square images)
            $url = 'https://www.bing.com/images/search?q=' . urlencode($query)
                . '&qft=+filterui:aspect-square'
                . '&form=HDRSC2&first=1';

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 2,
                CURLOPT_USERAGENT      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                CURLOPT_HTTPHEADER     => ['Accept-Language: en-US,en;q=0.9'],
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            $html = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($html === false || $code !== 200) {
                return [];
            }

            // Bing encodes full-res image URLs as: murl&quot;:&quot;https://...
            $images = [];
            $seen = [];
            if (preg_match_all('/murl&quot;:&quot;(https?:\/\/[^&]+)/', (string) $html, $matches)) {
                foreach ($matches[1] as $imgUrl) {
                    if (strlen($imgUrl) > 600
                        || preg_match('/icon|logo|sprite|favicon|badge|banner|pixel/i', $imgUrl)
                    ) {
                        continue;
                    }

                    $key = parse_url($imgUrl, PHP_URL_HOST) . parse_url($imgUrl, PHP_URL_PATH);
                    if (isset($seen[$key])) {
                        continue;
                    }
                    $seen[$key] = true;

                    if ($this->imageExists($imgUrl)) {
                        $images[] = $imgUrl;
                        if (count($images) >= 3) {
                            break;
                        }
                    }
                }
            }

            return $images;
        } catch (\Throwable $e) {
            $this->logger->warning('smart_import.image_search_failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Map common currency codes to market/country for AI pricing context.
     */
    private function currencyToCountry(string $currency): string
    {
        return match ($currency) {
            'KES' => 'Kenya',
            'USD' => 'United States',
            'EUR' => 'Europe',
            'GBP' => 'United Kingdom',
            'NGN' => 'Nigeria',
            'ZAR' => 'South Africa',
            'UGX' => 'Uganda',
            'TZS' => 'Tanzania',
            'GHS' => 'Ghana',
            'INR' => 'India',
            'AED' => 'UAE',
            'SAR' => 'Saudi Arabia',
            'CAD' => 'Canada',
            'AUD' => 'Australia',
            'JPY' => 'Japan',
            'CNY' => 'China',
            'BRL' => 'Brazil',
            'MXN' => 'Mexico',
            default => '',
        };
    }
}
