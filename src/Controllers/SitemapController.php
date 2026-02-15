<?php

declare(strict_types=1);

namespace TinyShop\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use TinyShop\Models\Product;
use TinyShop\Models\Setting;
use TinyShop\Models\User;
use TinyShop\Services\Config;

final class SitemapController
{
    public function __construct(
        private readonly Config $config,
        private readonly User $userModel,
        private readonly Product $productModel,
        private readonly Setting $settingModel
    ) {}

    /**
     * GET /sitemap.xml — Sitemap index pointing to sub-sitemaps.
     */
    public function index(Request $request, Response $response): Response
    {
        $baseUrl = $this->config->url();

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $xml .= '<sitemap><loc>' . $this->esc($baseUrl . '/sitemap-pages.xml') . '</loc></sitemap>';
        $xml .= '<sitemap><loc>' . $this->esc($baseUrl . '/sitemap-shops.xml') . '</loc></sitemap>';
        $xml .= '</sitemapindex>';

        $response->getBody()->write($xml);
        return $response->withHeader('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * GET /sitemap-pages.xml — Static platform pages.
     */
    public function pages(Request $request, Response $response): Response
    {
        $baseUrl = $this->config->url();

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $xml .= $this->urlEntry($baseUrl . '/', null, 'daily', '1.0');
        $xml .= $this->urlEntry($baseUrl . '/login', null, 'monthly', '0.3');
        $xml .= $this->urlEntry($baseUrl . '/register', null, 'monthly', '0.3');
        $xml .= '</urlset>';

        $response->getBody()->write($xml);
        return $response->withHeader('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * GET /sitemap-shops.xml — All active shops and their products.
     */
    public function shops(Request $request, Response $response): Response
    {
        $baseUrl = $this->config->url();

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $sellers = $this->userModel->findSellers(10000, 0, '');

        foreach ($sellers as $seller) {
            $subdomain = $seller['subdomain'] ?? '';
            if ($subdomain === '') {
                continue;
            }

            // Skip shops with custom domains — they manage their own sitemaps
            if (!empty($seller['custom_domain'])) {
                continue;
            }

            $shopUrl = $baseUrl . '/~shop/' . rawurlencode($subdomain);
            $xml .= $this->urlEntry($shopUrl, $seller['updated_at'] ?? null, 'daily', '0.8');

            $products = $this->productModel->findActiveByUser((int) $seller['id']);
            foreach ($products as $product) {
                $slug = $product['slug'] ?? (string) $product['id'];
                $productUrl = $shopUrl . '/' . rawurlencode($slug);
                $xml .= $this->urlEntry($productUrl, $product['updated_at'] ?? null, 'weekly', '0.6');
            }
        }

        $xml .= '</urlset>';

        $response->getBody()->write($xml);
        return $response->withHeader('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * GET /~shop/{subdomain}/sitemap.xml — Per-shop sitemap for custom domain shops.
     */
    public function shopSitemap(Request $request, Response $response, array $args): Response
    {
        $subdomain = $args['subdomain'] ?? '';
        $seller = $this->userModel->findBySubdomain($subdomain);

        if (!$seller) {
            return $response->withStatus(404);
        }

        $customDomain = $seller['custom_domain'] ?? '';
        if ($customDomain !== '') {
            $shopBaseUrl = 'https://' . $customDomain;
        } else {
            $shopBaseUrl = $this->config->url() . '/~shop/' . rawurlencode($subdomain);
        }

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $xml .= $this->urlEntry($shopBaseUrl, $seller['updated_at'] ?? null, 'daily', '1.0');

        $products = $this->productModel->findActiveByUser((int) $seller['id']);
        foreach ($products as $product) {
            $slug = $product['slug'] ?? (string) $product['id'];
            $xml .= $this->urlEntry($shopBaseUrl . '/' . rawurlencode($slug), $product['updated_at'] ?? null, 'weekly', '0.8');
        }

        $xml .= '</urlset>';

        $response->getBody()->write($xml);
        return $response->withHeader('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * GET /robots.txt — Dynamic robots file with sitemap reference.
     */
    public function robots(Request $request, Response $response): Response
    {
        $baseUrl = $this->config->url();
        $extra   = trim($this->settingModel->get('robots_extra', '') ?? '');

        $txt  = "User-agent: *\n";
        $txt .= "Allow: /\n";
        $txt .= "Disallow: /dashboard\n";
        $txt .= "Disallow: /admin\n";
        $txt .= "Disallow: /api/\n";
        $txt .= "Disallow: /checkout\n";

        if ($extra !== '') {
            $txt .= "\n" . $extra . "\n";
        }

        $txt .= "\nSitemap: " . $baseUrl . "/sitemap.xml\n";

        $response->getBody()->write($txt);
        return $response->withHeader('Content-Type', 'text/plain; charset=UTF-8');
    }

    private function urlEntry(string $loc, ?string $lastmod, string $changefreq, string $priority): string
    {
        $xml = '<url><loc>' . $this->esc($loc) . '</loc>';
        if ($lastmod) {
            $xml .= '<lastmod>' . date('Y-m-d', strtotime($lastmod)) . '</lastmod>';
        }
        $xml .= '<changefreq>' . $changefreq . '</changefreq>';
        $xml .= '<priority>' . $priority . '</priority>';
        $xml .= '</url>';
        return $xml;
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1, 'UTF-8');
    }
}
