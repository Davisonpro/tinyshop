<?php

declare(strict_types=1);

namespace TinyShop\Services;

use Psr\Log\LoggerInterface;
use TinyShop\Models\Setting;
use TinyShop\Models\User;

/**
 * IndexNow service — notify search engines of URL changes in real time.
 *
 * Submits URLs to the IndexNow API (Bing, Yandex, etc.) when content
 * changes on the platform (product created/updated, shop updated).
 *
 * @since 1.0.0
 */
final class IndexNow
{
    private const ENDPOINT = 'https://api.indexnow.org/indexnow';
    private const TIMEOUT = 5;

    public function __construct(
        private readonly Setting $setting,
        private readonly Config $config,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Submit one or more URLs to IndexNow.
     *
     * @param string[] $urls Fully qualified URLs to submit.
     */
    public function submit(array $urls): void
    {
        $apiKey = trim($this->setting->get('indexnow_api_key', ''));
        if ($apiKey === '' || $urls === []) {
            return;
        }

        $host = $this->config->baseDomain();

        $payload = json_encode([
            'host'    => $host,
            'key'     => $apiKey,
            'urlList' => array_values(array_unique($urls)),
        ], JSON_UNESCAPED_SLASHES);

        $ch = curl_init(self::ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json; charset=utf-8',
            ],
            CURLOPT_USERAGENT => 'TinyShop/1.0',
        ]);

        $result = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($code >= 200 && $code < 300) {
            $this->logger->info('indexnow.submitted', [
                'urls'  => $urls,
                'code'  => $code,
            ]);
        } else {
            $this->logger->warning('indexnow.failed', [
                'urls'   => $urls,
                'code'   => $code,
                'error'  => $error ?: $result,
            ]);
        }
    }

    /**
     * Notify IndexNow about a product change.
     *
     * Builds the shop URL for the given product and submits it.
     */
    public function notifyProductChange(array $product): void
    {
        $userId = (int) ($product['user_id'] ?? 0);
        if ($userId === 0) {
            return;
        }

        $user = User::find($userId);
        if (!$user) {
            return;
        }

        $shop = $user->toArray();
        $baseUrl = $this->resolveShopBaseUrl($shop);
        $slug = $product['slug'] ?? $product['id'];

        $urls = [
            $baseUrl . '/' . $slug,  // Product page
            $baseUrl,                  // Shop homepage (product list changed)
        ];

        $this->submit($urls);
    }

    /**
     * Notify IndexNow about a shop profile change.
     */
    public function notifyShopChange(int $userId): void
    {
        $user = User::find($userId);
        if (!$user) {
            return;
        }

        $shop = $user->toArray();
        $baseUrl = $this->resolveShopBaseUrl($shop);
        $this->submit([$baseUrl]);
    }

    /**
     * Build the public base URL for a shop.
     */
    private function resolveShopBaseUrl(array $shop): string
    {
        if (!empty($shop['custom_domain'])) {
            return 'https://' . $shop['custom_domain'];
        }

        return 'https://' . ($shop['subdomain'] ?? '') . '.' . $this->config->baseDomain();
    }

    /**
     * Ping Google and Bing with the sitemap URL.
     *
     * @return array<string, bool> Results per engine.
     */
    public function pingSitemap(): array
    {
        $baseDomain = $this->config->baseDomain();
        $sitemapUrl = 'https://' . $baseDomain . '/sitemap.xml';

        return [
            'google' => $this->httpPing('https://www.google.com/ping?sitemap=' . urlencode($sitemapUrl)),
            'bing'   => $this->httpPing('https://www.bing.com/ping?sitemap=' . urlencode($sitemapUrl)),
        ];
    }

    private function httpPing(string $url): bool
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT      => 'TinyShop/1.0',
        ]);
        curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $code >= 200 && $code < 400;
    }
}
