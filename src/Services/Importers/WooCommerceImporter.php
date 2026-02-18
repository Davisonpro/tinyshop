<?php

declare(strict_types=1);

namespace TinyShop\Services\Importers;

use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;

final class WooCommerceImporter implements ImporterInterface
{
    public function __construct(private readonly HttpClient $http)
    {
    }

    private ?string $lastError = null;

    public function supports(string $url): bool
    {
        $this->lastError = null;

        // Fetch the page and look for WooCommerce markers
        try {
            $html = $this->http->get($url);
            $this->lastHtml = $html;
            return str_contains($html, 'woocommerce')
                || str_contains($html, 'WooCommerce');
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /** Cache HTML from supports() so fetch() doesn't re-download */
    private ?string $lastHtml = null;

    public function fetch(string $url): ImportResult
    {
        // Reuse HTML from supports() if available, otherwise fetch fresh
        $html = $this->lastHtml ?? $this->http->get($url);
        $this->lastHtml = null;

        return $this->parseHtml($html);
    }

    /** Parse pre-supplied HTML (for paste-mode when server can't fetch the URL). */
    public function fetchFromHtml(string $html): ImportResult
    {
        return $this->parseHtml($html);
    }

    private function parseHtml(string $html): ImportResult
    {
        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();

        $xpath = new DOMXPath($doc);

        // Parse JSON-LD structured data (reliable source for categories, currency, etc.)
        $ld = $this->extractJsonLd($xpath);

        $title       = $this->extractTitle($xpath, $ld);
        $description = $this->extractDescription($xpath, $ld);
        [$price, $comparePrice] = $this->extractPrices($xpath, $ld);
        $images      = $this->extractImages($xpath, $ld);
        $categories  = $this->extractCategories($xpath, $ld);
        $variations  = $this->extractVariations($xpath);
        $currency    = $this->extractCurrency($xpath, $ld);

        return new ImportResult(
            title:          $title,
            description:    $description,
            price:          $price,
            comparePrice:   $comparePrice,
            images:         $images,
            categories:     $categories,
            variations:     $variations,
            currency:       $currency,
            sourcePlatform: 'woocommerce',
        );
    }

    /**
     * Extract Product JSON-LD from <script type="application/ld+json"> tags.
     * Handles both top-level Product and Yoast/RankMath @graph arrays.
     *
     * @return array Product structured data, or empty array if not found
     */
    private function extractJsonLd(DOMXPath $xpath): array
    {
        $scripts = $xpath->query('//script[@type="application/ld+json"]');
        if ($scripts === false) {
            return [];
        }

        foreach ($scripts as $script) {
            $json = json_decode(trim($script->textContent), true);
            if (!is_array($json)) {
                continue;
            }

            // Top-level Product
            if (($json['@type'] ?? '') === 'Product') {
                return $json;
            }

            // Yoast/RankMath style: @graph array containing a Product item
            foreach (($json['@graph'] ?? []) as $item) {
                if (is_array($item) && ($item['@type'] ?? '') === 'Product') {
                    return $item;
                }
            }
        }

        return [];
    }

    private function extractTitle(DOMXPath $xpath, array $ld): string
    {
        $nodes = $xpath->query('//h1[contains(@class,"product_title")]');
        if ($nodes !== false && $nodes->length > 0) {
            return trim($nodes->item(0)->textContent);
        }

        if (isset($ld['name']) && $ld['name'] !== '') {
            return trim($ld['name']);
        }

        throw new RuntimeException('Could not find product title');
    }

    private function extractDescription(DOMXPath $xpath, array $ld): string
    {
        $nodes = $xpath->query('//*[contains(@class,"woocommerce-product-details__short-description")]');
        if ($nodes === false || $nodes->length === 0) {
            // Fallback: JSON-LD description (plain text, wrapped in <p>)
            $ldDesc = $ld['description'] ?? '';
            return $ldDesc !== '' ? '<p>' . htmlspecialchars(strip_tags($ldDesc)) . '</p>' : '';
        }

        $node = $nodes->item(0);
        $inner = '';
        foreach ($node->childNodes as $child) {
            $inner .= $node->ownerDocument->saveHTML($child);
        }

        // Keep safe HTML tags for the rich editor, strip everything else
        $clean = strip_tags($inner, '<p><br><b><strong><i><em><ul><ol><li><h2><h3><a>');

        // Strip inline style/class/id attributes from remaining tags
        $clean = preg_replace('/\s+(style|class|id)\s*=\s*"[^"]*"/i', '', $clean);
        $clean = preg_replace('/\s+(style|class|id)\s*=\s*\'[^\']*\'/i', '', $clean);

        // Remove empty list items and empty lists
        $clean = preg_replace('/<li>\s*<\/li>/', '', $clean);
        $clean = preg_replace('/<(ul|ol)>\s*<\/(ul|ol)>/', '', $clean);

        return trim($clean);
    }

    /** @return array{float, float|null} [price, comparePrice] */
    private function extractPrices(DOMXPath $xpath, array $ld): array
    {
        // Try scoped inside .summary first, then unscoped p.price as fallback
        $scopes = [
            '//div[contains(@class,"summary")]//p[contains(@class,"price")]',
            '//p[contains(@class,"price")]',
        ];

        foreach ($scopes as $scope) {
            // Sale price: ins = current, del = original
            $ins = $xpath->query($scope . '//ins//*[contains(@class,"woocommerce-Price-amount")]');
            $del = $xpath->query($scope . '//del//*[contains(@class,"woocommerce-Price-amount")]');

            if ($ins !== false && $ins->length > 0) {
                $price = $this->parsePrice($ins->item(0)->textContent);
                $comparePrice = ($del !== false && $del->length > 0)
                    ? $this->parsePrice($del->item(0)->textContent)
                    : null;
                return [$price, $comparePrice];
            }

            // Simple price (no sale)
            $nodes = $xpath->query($scope . '//*[contains(@class,"woocommerce-Price-amount")]');
            if ($nodes !== false && $nodes->length > 0) {
                return [$this->parsePrice($nodes->item(0)->textContent), null];
            }
        }

        // Fallback: JSON-LD offers
        $offers = $ld['offers'] ?? null;
        if (is_array($offers)) {
            // Single offer or first from array
            $offer = isset($offers['@type']) ? $offers : ($offers[0] ?? null);
            if (is_array($offer) && isset($offer['price'])) {
                return [(float) $offer['price'], null];
            }
        }

        throw new RuntimeException('Could not find product price');
    }

    private function parsePrice(string $text): float
    {
        // Remove currency symbols, commas, whitespace — keep digits and dot
        $clean = preg_replace('/[^0-9.]/', '', str_replace(',', '', $text));
        return (float) $clean;
    }

    private function extractCurrency(DOMXPath $xpath, array $ld): string
    {
        // Try WooCommerce currency code from <span class="woocommerce-Price-currencySymbol">
        $nodes = $xpath->query('//*[contains(@class,"woocommerce-Price-currencySymbol")]');
        if ($nodes !== false && $nodes->length > 0) {
            $symbol = trim($nodes->item(0)->textContent);
            $code = self::currencySymbolToCode($symbol);
            if ($code !== null) {
                return $code;
            }
        }

        // Fallback: JSON-LD offers.priceCurrency
        $offers = $ld['offers'] ?? null;
        if (is_array($offers)) {
            $offer = isset($offers['@type']) ? $offers : ($offers[0] ?? null);
            if (is_array($offer) && isset($offer['priceCurrency'])) {
                return strtoupper($offer['priceCurrency']);
            }
        }

        // Fallback: meta tag
        $meta = $xpath->query('//meta[@property="product:price:currency"]/@content');
        if ($meta !== false && $meta->length > 0) {
            return strtoupper(trim($meta->item(0)->nodeValue));
        }

        return 'USD';
    }

    private static function currencySymbolToCode(string $symbol): ?string
    {
        return match ($symbol) {
            '$', 'US$'     => 'USD',
            '€'            => 'EUR',
            '£'            => 'GBP',
            '¥', 'JP¥'    => 'JPY',
            'KSh', 'KES'  => 'KES',
            '₹'            => 'INR',
            'R'            => 'ZAR',
            '₦'            => 'NGN',
            'UGX'          => 'UGX',
            'TSh', 'TZS'  => 'TZS',
            'A$', 'AU$'   => 'AUD',
            'C$', 'CA$'   => 'CAD',
            'Fr', 'CHF'   => 'CHF',
            '₱', 'PHP'    => 'PHP',
            'RM', 'MYR'   => 'MYR',
            'R$', 'BRL'   => 'BRL',
            'AED'          => 'AED',
            'SAR'          => 'SAR',
            default        => null,
        };
    }

    /** @return string[] */
    private function extractImages(DOMXPath $xpath, array $ld): array
    {
        $images = [];

        // WooCommerce product gallery — multiple gallery patterns (order matters):
        // 1. Standard: .woocommerce-product-gallery__image > a[href]
        // 2. CommerceGurus Swiper: .cg-main-swiper a.swiper-slide-imglink[href] (finds ALL slides)
        // 3. Reversed (CommerceGurus): a > .woocommerce-product-gallery__image (fallback, may find only 1)
        $galleryQueries = [
            '//*[contains(@class,"woocommerce-product-gallery__image")]//a/@href',
            '//*[contains(@class,"cg-main-swiper")]//a[contains(@class,"swiper-slide-imglink")]/@href',
            '//a[.//*[contains(@class,"woocommerce-product-gallery__image")]]/@href',
        ];
        foreach ($galleryQueries as $query) {
            $nodes = $xpath->query($query);
            if ($nodes !== false) {
                foreach ($nodes as $attr) {
                    $href = trim($attr->nodeValue);
                    if ($href !== '' && $this->looksLikeImageUrl($href) && !in_array($href, $images, true)) {
                        $images[] = $href;
                    }
                }
            }
            if (!empty($images)) {
                break;
            }
        }

        // Fallback: JSON-LD images
        if (empty($images) && isset($ld['image'])) {
            $ldImages = is_array($ld['image']) ? $ld['image'] : [$ld['image']];
            foreach ($ldImages as $img) {
                $url = is_array($img) ? ($img['url'] ?? $img['contentUrl'] ?? '') : (string) $img;
                if ($url !== '' && !in_array($url, $images, true)) {
                    $images[] = $url;
                }
            }
        }

        // Fallback: og:image
        if (empty($images)) {
            $meta = $xpath->query('//meta[@property="og:image"]/@content');
            if ($meta !== false && $meta->length > 0) {
                $images[] = trim($meta->item(0)->nodeValue);
            }
        }

        return $images;
    }

    /** Names that WooCommerce assigns by default — never useful as imported categories. */
    private const JUNK_CATEGORIES = ['default category', 'uncategorized'];

    /** @return string[] */
    private function extractCategories(DOMXPath $xpath, array $ld): array
    {
        // 1. Breadcrumb containers — scoped per-container so nav/sidebar can't pollute
        $categories = $this->extractCategoriesFromBreadcrumbs($xpath);
        if (!empty($categories)) {
            return $categories;
        }

        // 2. JSON-LD Product category field
        $categories = $this->extractCategoriesFromLdProduct($ld);
        if (!empty($categories)) {
            return $categories;
        }

        // 3. JSON-LD BreadcrumbList (Yoast/RankMath)
        $categories = $this->extractCategoriesFromLdBreadcrumb($xpath);
        if (!empty($categories)) {
            return $categories;
        }

        // 4. Inline JS tracking data (WebEngage, GTM dataLayer)
        $categories = $this->extractCategoriesFromTrackingJs($xpath);
        if (!empty($categories)) {
            return $categories;
        }

        // 5. posted_in links (any scope, filter junk)
        $categories = $this->extractCategoriesFromPostedIn($xpath);
        if (!empty($categories)) {
            return $categories;
        }

        return [];
    }

    /**
     * Extract categories from breadcrumb containers.
     * Checks each container individually and picks the one with 1–8 category links
     * (breadcrumbs are small; nav/sidebar widgets have 10+).
     */
    private function extractCategoriesFromBreadcrumbs(DOMXPath $xpath): array
    {
        $selectors = [
            '//*[contains(@class,"woocommerce-breadcrumb")]',
            '//*[contains(@class,"breadcrumbs-container")]',
            '//*[contains(@class,"breadcrumb-trail")]',
            '//*[contains(@class,"breadcrumbs")]',
        ];

        foreach ($selectors as $selector) {
            $containers = $xpath->query($selector);
            if ($containers === false) {
                continue;
            }

            foreach ($containers as $container) {
                // Scoped search: .//a limits to THIS container's descendants
                $links = $xpath->query('.//a[contains(@href,"/product-category/")]', $container);
                if ($links === false || $links->length === 0) {
                    continue;
                }

                $cats = [];
                foreach ($links as $link) {
                    $name = trim($link->textContent);
                    if ($name !== '' && !$this->isJunkCategory($name) && !in_array($name, $cats, true)) {
                        $cats[] = $name;
                    }
                }

                // Breadcrumbs have 1–8 category links; skip containers with more (likely nav)
                if (!empty($cats) && count($cats) <= 8) {
                    return $cats;
                }
            }
        }

        return [];
    }

    /** Extract category from JSON-LD Product data. */
    private function extractCategoriesFromLdProduct(array $ld): array
    {
        if (!isset($ld['category'])) {
            return [];
        }

        $ldCat = is_string($ld['category']) ? $ld['category'] : ($ld['category']['name'] ?? '');
        if ($ldCat === '') {
            return [];
        }

        $categories = [];
        $parts = preg_split('/\s*[>,]\s*/', $ldCat);
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part !== '' && !$this->isJunkCategory($part) && !in_array($part, $categories, true)) {
                $categories[] = $part;
            }
        }

        return $categories;
    }

    /** Extract category from inline JS tracking (WebEngage, GTM dataLayer). */
    private function extractCategoriesFromTrackingJs(DOMXPath $xpath): array
    {
        $scripts = $xpath->query('//script[not(@src)]');
        if ($scripts === false) {
            return [];
        }

        foreach ($scripts as $script) {
            $js = $script->textContent;

            // WebEngage: "Category": "MacBooks"
            if (preg_match('/"Category"\s*:\s*"([^"]+)"/', $js, $m)) {
                $cat = trim($m[1]);
                if ($cat !== '' && !$this->isJunkCategory($cat)) {
                    return [$cat];
                }
            }
        }

        return [];
    }

    /** Extract categories from .posted_in links, filtering out junk. */
    private function extractCategoriesFromPostedIn(DOMXPath $xpath): array
    {
        // Try scoped to .summary first, then unscoped
        $queries = [
            '//div[contains(@class,"summary")]//*[contains(@class,"posted_in")]//a',
            '//*[contains(@class,"posted_in")]//a',
        ];

        foreach ($queries as $query) {
            $nodes = $xpath->query($query);
            if ($nodes === false || $nodes->length === 0) {
                continue;
            }

            $cats = [];
            foreach ($nodes as $node) {
                $name = trim($node->textContent);
                if ($name !== '' && !$this->isJunkCategory($name) && !in_array($name, $cats, true)) {
                    $cats[] = $name;
                }
            }

            if (!empty($cats)) {
                return $cats;
            }
        }

        return [];
    }

    private function looksLikeImageUrl(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        return (bool) preg_match('/\.(jpe?g|png|webp|gif|avif|bmp|svg)/i', $path);
    }

    private function isJunkCategory(string $name): bool
    {
        return in_array(strtolower($name), self::JUNK_CATEGORIES, true);
    }

    /** Extract categories from JSON-LD BreadcrumbList (Yoast/RankMath). */
    private function extractCategoriesFromLdBreadcrumb(DOMXPath $xpath): array
    {
        $scripts = $xpath->query('//script[@type="application/ld+json"]');
        if ($scripts === false) {
            return [];
        }

        $items = null;
        foreach ($scripts as $script) {
            $json = json_decode(trim($script->textContent), true);
            if (!is_array($json)) {
                continue;
            }

            // Top-level BreadcrumbList
            if (($json['@type'] ?? '') === 'BreadcrumbList') {
                $items = $json['itemListElement'] ?? [];
                break;
            }

            // Inside @graph
            foreach (($json['@graph'] ?? []) as $item) {
                if (is_array($item) && ($item['@type'] ?? '') === 'BreadcrumbList') {
                    $items = $item['itemListElement'] ?? [];
                    break 2;
                }
            }
        }

        if (empty($items)) {
            return [];
        }

        // Sort by position, skip first (Home) and last (product name — no URL)
        usort($items, fn($a, $b) => ($a['position'] ?? 0) <=> ($b['position'] ?? 0));

        $categories = [];
        foreach ($items as $item) {
            // Only include items with a /product-category/ URL
            $itemData = $item['item'] ?? null;
            $url = is_array($itemData) ? ($itemData['@id'] ?? '') : ((string) ($itemData ?? ''));
            if (!str_contains($url, '/product-category/')) {
                continue;
            }

            // Name can be at ListItem level or inside the item object (Yoast format)
            $name = trim($item['name'] ?? '');
            if ($name === '' && is_array($itemData)) {
                $name = trim($itemData['name'] ?? '');
            }
            if ($name !== '' && !in_array($name, $categories, true)) {
                $categories[] = $name;
            }
        }

        return $categories;
    }

    /** @return array[] */
    private function extractVariations(DOMXPath $xpath): array
    {
        $nodes = $xpath->query('//form[contains(@class,"variations_form")]/@data-product_variations');
        if ($nodes === false || $nodes->length === 0) {
            return [];
        }

        $json = $nodes->item(0)->nodeValue;
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return [];
        }

        // Map attribute_pa_X → proper group label from <label> elements (e.g. "SIM Card Slots")
        $groupLabels = $this->buildAttributeGroupLabels($xpath);

        // Map attribute_pa_X → [slug => display text] from <select> options (e.g. "esim-only" → "eSIM only")
        $valueLabels = $this->buildAttributeValueLabels($xpath);

        $variations = [];
        foreach ($data as $v) {
            $attributes = [];
            foreach (($v['attributes'] ?? []) as $key => $value) {
                // Use proper group label from <label for="pa_X"> if available
                $label = $groupLabels[$key] ?? null;
                if ($label === null) {
                    $label = str_replace('attribute_pa_', '', $key);
                    $label = str_replace('attribute_', '', $label);
                    $label = ucfirst(str_replace('-', ' ', $label));
                }

                // Use display text from <select> <option> if available
                $readableValue = $valueLabels[$key][$value] ?? ucwords(str_replace('-', ' ', $value));
                $attributes[$label] = $readableValue;
            }

            // Extract price with fallbacks
            $price = $this->extractVariationPrice($v);
            $regularPrice = $this->extractVariationRegularPrice($v);
            $comparePrice = ($regularPrice !== null && $regularPrice !== $price) ? $regularPrice : null;

            // Build readable name from attributes
            $name = implode(' / ', array_filter(array_values($attributes)));

            $variations[] = [
                'name'          => $name,
                'price'         => $price,
                'compare_price' => $comparePrice,
                'attributes'    => $attributes,
            ];
        }

        return $variations;
    }

    private function extractVariationPrice(array $v): ?float
    {
        // Try display_price first, then price, then parse from price_html
        if (isset($v['display_price']) && $v['display_price'] !== '') {
            return (float) $v['display_price'];
        }
        if (isset($v['price']) && $v['price'] !== '') {
            return (float) $v['price'];
        }
        if (isset($v['price_html']) && $v['price_html'] !== '') {
            return $this->parsePriceFromHtml($v['price_html']);
        }
        return null;
    }

    private function extractVariationRegularPrice(array $v): ?float
    {
        if (isset($v['display_regular_price']) && $v['display_regular_price'] !== '') {
            return (float) $v['display_regular_price'];
        }
        if (isset($v['regular_price']) && $v['regular_price'] !== '') {
            return (float) $v['regular_price'];
        }
        return null;
    }

    private function parsePriceFromHtml(string $html): ?float
    {
        // Extract price from WooCommerce price_html like "<span class="woocommerce-Price-amount">KES 150,000</span>"
        if (preg_match('/[\d,]+(?:\.\d+)?/', strip_tags($html), $m)) {
            return (float) str_replace(',', '', $m[0]);
        }
        return null;
    }

    /**
     * Build a map of attribute key → proper group label from <label> elements.
     * e.g. ['attribute_pa_sim-card-slots' => 'SIM Card Slots']
     *
     * @return array<string, string>
     */
    private function buildAttributeGroupLabels(DOMXPath $xpath): array
    {
        $map = [];
        $labels = $xpath->query('//table[contains(@class,"variations")]//th[contains(@class,"label")]//label');
        if ($labels === false) {
            return $map;
        }

        foreach ($labels as $label) {
            if (!$label instanceof DOMElement) {
                continue;
            }
            $for = $label->getAttribute('for');
            if ($for === '') {
                continue;
            }
            $text = trim($label->textContent);
            if ($text !== '') {
                // "pa_sim-card-slots" → "attribute_pa_sim-card-slots"
                $map['attribute_' . $for] = $text;
            }
        }

        return $map;
    }

    /**
     * Build a map of attribute slug → display label from variation <select> options.
     * e.g. ['attribute_pa_sim-card-slots' => ['esim-only' => 'eSIM only']]
     *
     * @return array<string, array<string, string>>
     */
    private function buildAttributeValueLabels(DOMXPath $xpath): array
    {
        $map = [];
        $selects = $xpath->query('//table[contains(@class,"variations")]//select');
        if ($selects === false) {
            return $map;
        }

        foreach ($selects as $select) {
            if (!$select instanceof DOMElement) {
                continue;
            }
            $name = $select->getAttribute('name');
            if ($name === '') {
                continue;
            }
            $options = $xpath->query('.//option', $select);
            if ($options === false) {
                continue;
            }
            foreach ($options as $option) {
                if (!$option instanceof DOMElement) {
                    continue;
                }
                $val = $option->getAttribute('value');
                $text = trim($option->textContent);
                if ($val !== '' && $text !== '') {
                    $map[$name][$val] = $text;
                }
            }
        }

        return $map;
    }
}
