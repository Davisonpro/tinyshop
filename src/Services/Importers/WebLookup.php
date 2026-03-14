<?php

declare(strict_types=1);

namespace TinyShop\Services\Importers;

use DOMDocument;
use DOMXPath;
use Psr\Log\LoggerInterface;
use TinyShop\Models\ImportSource;
use TinyShop\Models\ProductCatalog;

/**
 * Searches whitelisted import sources for product data.
 *
 * Uses admin-configured CSS selectors to scrape product pages.
 * Results are cached in the Product Knowledge Base.
 *
 * @since 1.0.0
 */
final class WebLookup
{
    /** @var array<string, float> Last request timestamp per domain. */
    private array $lastRequestTime = [];

    public function __construct(
        private readonly HttpClient $httpClient,
        private readonly ImportSource $sourceModel,
        private readonly ProductCatalog $catalogModel,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Look up a product from whitelisted sources.
     *
     * @return array<string, mixed>|null Resolved product data or null.
     */
    public function lookup(string $brand, string $model): ?array
    {
        $sources = $this->sourceModel->findActive();

        if (empty($sources)) {
            return null;
        }

        $query = trim("{$brand} {$model}");

        foreach ($sources as $source) {
            $searchUrl = $source['search_url_template'] ?? '';
            if ($searchUrl === '') {
                continue;
            }

            try {
                $result = $this->searchSource($source, $query, $brand, $model);
                if ($result !== null) {
                    return $result;
                }
            } catch (\Throwable $e) {
                $this->logger->warning('weblookup.source_failed', [
                    'source' => $source['name'],
                    'query' => $query,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return null;
    }

    /**
     * Search a single source for product data.
     *
     * @param array<string, mixed> $source Import source config.
     * @return array<string, mixed>|null
     */
    private function searchSource(array $source, string $query, string $brand, string $model): ?array
    {
        $selectors = is_string($source['selectors'])
            ? json_decode($source['selectors'], true) ?? []
            : ($source['selectors'] ?? []);

        if (empty($selectors)) {
            return null;
        }

        // Build search URL
        $searchUrl = str_replace('{query}', urlencode($query), $source['search_url_template']);

        // Rate limit: 1s between requests to same domain
        $domain = parse_url($source['base_url'], PHP_URL_HOST) ?? '';
        $this->throttle($domain);

        // Fetch search results page
        $html = $this->httpClient->get($searchUrl);
        $this->lastRequestTime[$domain] = microtime(true);

        // Find product link in search results
        $productUrl = $this->extractProductLink($html, $selectors, $source['base_url']);

        if ($productUrl === null) {
            return null;
        }

        // Fetch the actual product page
        $this->throttle($domain);
        $productHtml = $this->httpClient->get($productUrl);
        $this->lastRequestTime[$domain] = microtime(true);

        // Extract product data
        $data = $this->extractProductData($productHtml, $selectors);

        if ($data === null || empty($data['name'])) {
            return null;
        }

        // Cache in PKB
        $catalogData = [
            'brand' => $brand,
            'model' => $model,
            'canonical_name' => $data['name'],
            'description' => $data['description'] ?? null,
            'full_description' => $data['full_description'] ?? null,
            'specs' => $data['specs'] ?? null,
            'images' => $data['images'] ?? null,
            'category_hint' => $data['category'] ?? null,
            'source_url' => $productUrl,
            'source_site' => $source['name'],
            'quality_score' => $this->calculateQuality($data),
        ];

        $this->catalogModel->upsert($catalogData);

        return $catalogData;
    }

    /**
     * Extract product link from search results HTML.
     */
    private function extractProductLink(string $html, array $selectors, string $baseUrl): ?string
    {
        $linkSelector = $selectors['search_result_link'] ?? '';
        if ($linkSelector === '') {
            return null;
        }

        $doc = $this->loadHtml($html);
        if ($doc === null) {
            return null;
        }

        $xpath = new DOMXPath($doc);
        $xpathQuery = $this->cssToXpath($linkSelector);
        $nodes = $xpath->query($xpathQuery);

        if ($nodes === false || $nodes->length === 0) {
            return null;
        }

        $node = $nodes->item(0);
        $href = $node instanceof \DOMElement ? $node->getAttribute('href') : '';

        if ($href === '') {
            return null;
        }

        // Make absolute URL
        if (str_starts_with($href, '/')) {
            $parsed = parse_url($baseUrl);
            $href = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '') . $href;
        } elseif (!str_starts_with($href, 'http')) {
            $href = rtrim($baseUrl, '/') . '/' . $href;
        }

        return $href;
    }

    /**
     * Extract product data from a product page.
     *
     * @return array<string, mixed>|null
     */
    private function extractProductData(string $html, array $selectors): ?array
    {
        $doc = $this->loadHtml($html);
        if ($doc === null) {
            return null;
        }

        $xpath = new DOMXPath($doc);
        $data = [];

        // Product name
        if (!empty($selectors['product_name'])) {
            $data['name'] = $this->queryText($xpath, $selectors['product_name']);
        }

        // Description
        if (!empty($selectors['description'])) {
            $data['description'] = $this->queryText($xpath, $selectors['description']);
        }

        // Full description (HTML)
        if (!empty($selectors['full_description'])) {
            $data['full_description'] = $this->queryHtml($xpath, $selectors['full_description'], $doc);
        }

        // Images
        if (!empty($selectors['images'])) {
            $data['images'] = $this->queryImages($xpath, $selectors['images']);
        }

        // Specs
        if (!empty($selectors['specs'])) {
            $data['specs'] = $this->querySpecs($xpath, $selectors['specs']);
        }

        // Category
        if (!empty($selectors['category'])) {
            $data['category'] = $this->queryText($xpath, $selectors['category']);
        }

        return $data;
    }

    /**
     * Query text content using a CSS selector.
     */
    private function queryText(DOMXPath $xpath, string $cssSelector): ?string
    {
        $xpathQuery = $this->cssToXpath($cssSelector);
        $nodes = $xpath->query($xpathQuery);

        if ($nodes === false || $nodes->length === 0) {
            return null;
        }

        $text = trim($nodes->item(0)->textContent ?? '');
        return $text !== '' ? $text : null;
    }

    /**
     * Query inner HTML using a CSS selector.
     */
    private function queryHtml(DOMXPath $xpath, string $cssSelector, DOMDocument $doc): ?string
    {
        $xpathQuery = $this->cssToXpath($cssSelector);
        $nodes = $xpath->query($xpathQuery);

        if ($nodes === false || $nodes->length === 0) {
            return null;
        }

        $node = $nodes->item(0);
        $html = '';
        foreach ($node->childNodes as $child) {
            $html .= $doc->saveHTML($child);
        }

        $html = trim($html);
        return $html !== '' ? $html : null;
    }

    /**
     * Query image URLs from img elements.
     *
     * @return string[]
     */
    private function queryImages(DOMXPath $xpath, string $cssSelector): array
    {
        $xpathQuery = $this->cssToXpath($cssSelector);
        $nodes = $xpath->query($xpathQuery);

        if ($nodes === false) {
            return [];
        }

        $images = [];
        foreach ($nodes as $node) {
            if (!$node instanceof \DOMElement) {
                continue;
            }
            $src = $node->getAttribute('src')
                ?: $node->getAttribute('data-src')
                ?: $node->getAttribute('data-lazy-src');

            if ($src !== '' && !str_contains($src, 'placeholder')) {
                $images[] = $src;
            }
        }

        return array_values(array_unique($images));
    }

    /**
     * Query specs as key-value pairs from a table or list.
     *
     * @return array<string, string>
     */
    private function querySpecs(DOMXPath $xpath, string $cssSelector): array
    {
        $xpathQuery = $this->cssToXpath($cssSelector);
        $nodes = $xpath->query($xpathQuery);

        if ($nodes === false || $nodes->length === 0) {
            return [];
        }

        $specs = [];
        $container = $nodes->item(0);

        // Try table rows first
        $rows = $xpath->query('.//tr', $container);
        if ($rows !== false && $rows->length > 0) {
            foreach ($rows as $row) {
                $cells = $xpath->query('.//td|.//th', $row);
                if ($cells !== false && $cells->length >= 2) {
                    $key = trim($cells->item(0)->textContent ?? '');
                    $val = trim($cells->item(1)->textContent ?? '');
                    if ($key !== '' && $val !== '') {
                        $specs[$key] = $val;
                    }
                }
            }
        }

        // Try dt/dd pairs
        if (empty($specs)) {
            $dts = $xpath->query('.//dt', $container);
            $dds = $xpath->query('.//dd', $container);
            if ($dts !== false && $dds !== false) {
                $count = min($dts->length, $dds->length);
                for ($i = 0; $i < $count; $i++) {
                    $key = trim($dts->item($i)->textContent ?? '');
                    $val = trim($dds->item($i)->textContent ?? '');
                    if ($key !== '' && $val !== '') {
                        $specs[$key] = $val;
                    }
                }
            }
        }

        return $specs;
    }

    /**
     * Load HTML into a DOMDocument.
     */
    private function loadHtml(string $html): ?DOMDocument
    {
        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $loaded = $doc->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        return $loaded ? $doc : null;
    }

    /**
     * Simple CSS selector to XPath conversion.
     *
     * Handles: tag, .class, #id, tag.class, tag[attr], and basic descendant combinators.
     */
    private function cssToXpath(string $css): string
    {
        $css = trim($css);

        // Split by space for descendant combinator
        $parts = preg_split('/\s+/', $css);
        $xpathParts = [];

        foreach ($parts as $part) {
            $xpathParts[] = $this->cssSingleToXpath($part);
        }

        return '//' . implode('//', $xpathParts);
    }

    /**
     * Convert a single CSS selector segment to XPath.
     */
    private function cssSingleToXpath(string $selector): string
    {
        // ID selector: #id
        if (preg_match('/^#([\w-]+)$/', $selector, $m)) {
            return "*[@id='{$m[1]}']";
        }

        // Class selector: .class
        if (preg_match('/^\.([\w-]+)$/', $selector, $m)) {
            return "*[contains(concat(' ',normalize-space(@class),' '),' {$m[1]} ')]";
        }

        // Tag with class: tag.class
        if (preg_match('/^(\w+)\.([\w-]+)$/', $selector, $m)) {
            return "{$m[1]}[contains(concat(' ',normalize-space(@class),' '),' {$m[2]} ')]";
        }

        // Tag with attribute: tag[attr=value]
        if (preg_match('/^(\w+)\[(\w+)=["\']?([^"\']+)["\']?\]$/', $selector, $m)) {
            return "{$m[1]}[@{$m[2]}='{$m[3]}']";
        }

        // Tag with ID: tag#id
        if (preg_match('/^(\w+)#([\w-]+)$/', $selector, $m)) {
            return "{$m[1]}[@id='{$m[2]}']";
        }

        // Plain tag
        if (preg_match('/^\w+$/', $selector)) {
            return $selector;
        }

        // Fallback — try as-is (might be an XPath already)
        return $selector;
    }

    /**
     * Rate-limit requests to the same domain (1s gap).
     */
    private function throttle(string $domain): void
    {
        if (isset($this->lastRequestTime[$domain])) {
            $elapsed = microtime(true) - $this->lastRequestTime[$domain];
            if ($elapsed < 1.0) {
                usleep((int) ((1.0 - $elapsed) * 1_000_000));
            }
        }
    }

    /**
     * Calculate a quality score (0-100) based on how much data we found.
     */
    private function calculateQuality(array $data): int
    {
        $score = 0;

        if (!empty($data['name'])) {
            $score += 20;
        }
        if (!empty($data['description'])) {
            $score += 20;
        }
        if (!empty($data['full_description'])) {
            $score += 15;
        }
        if (!empty($data['images']) && count($data['images']) > 0) {
            $score += 25;
        }
        if (!empty($data['specs']) && count($data['specs']) > 0) {
            $score += 15;
        }
        if (!empty($data['category'])) {
            $score += 5;
        }

        return min(100, $score);
    }
}
