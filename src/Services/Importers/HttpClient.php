<?php

declare(strict_types=1);

namespace TinyShop\Services\Importers;

use RuntimeException;

/**
 * HTTP client for product imports.
 *
 * @since 1.0.0
 */
final class HttpClient
{
    private const TIMEOUT = 15;

    private const USER_AGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36';

    /**
     * Fetch a URL and return the response body.
     *
     * @since 1.0.0
     *
     * @param  string $url URL to fetch.
     * @return string Response body.
     *
     * @throws RuntimeException On failure.
     */
    public function get(string $url): string
    {
        $ch = curl_init();

        $opts = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT      => self::USER_AGENT,
            CURLOPT_HTTPHEADER     => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
                'Accept-Language: en-GB,en-US;q=0.9,en;q=0.8',
                'Cache-Control: no-cache',
                'Connection: keep-alive',
                'Pragma: no-cache',
                'Upgrade-Insecure-Requests: 1',
                'sec-ch-ua: "Google Chrome";v="145", "Chromium";v="145", "Not-A.Brand";v="24"',
                'sec-ch-ua-mobile: ?0',
                'sec-ch-ua-platform: "macOS"',
                'Sec-Fetch-Dest: document',
                'Sec-Fetch-Mode: navigate',
                'Sec-Fetch-Site: none',
                'Sec-Fetch-User: ?1',
            ],
            CURLOPT_COOKIEJAR      => sys_get_temp_dir() . '/tinyshop_import_cookies.txt',
            CURLOPT_COOKIEFILE     => sys_get_temp_dir() . '/tinyshop_import_cookies.txt',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_2_0,
        ];

        // Use system CA bundle if available (common on cPanel/shared hosts)
        $caBundle = self::findCaBundle();
        if ($caBundle !== null) {
            $opts[CURLOPT_CAINFO] = $caBundle;
        }

        curl_setopt_array($ch, $opts);

        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($body === false || $err !== '') {
            throw new RuntimeException('HTTP request failed: ' . $err);
        }

        if ($code >= 400) {
            throw new RuntimeException('HTTP ' . $code . ' returned for ' . $url);
        }

        return (string) $body;
    }

    /** Find a system CA bundle for SSL verification. */
    private static function findCaBundle(): ?string
    {
        $paths = [
            '/etc/ssl/certs/ca-certificates.crt',     // Debian/Ubuntu
            '/etc/pki/tls/certs/ca-bundle.crt',       // RHEL/CentOS
            '/etc/ssl/ca-bundle.pem',                  // openSUSE
            '/etc/ssl/cert.pem',                       // Alpine/macOS
            '/usr/local/share/certs/ca-root-nss.crt',  // FreeBSD
        ];

        foreach ($paths as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Download a file to a temporary path.
     *
     * @since 1.0.0
     *
     * @param  string $url Remote file URL.
     * @return string Path to the downloaded temp file.
     *
     * @throws RuntimeException On failure.
     */
    public function download(string $url): string
    {
        $ext  = pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION) ?: 'jpg';
        $tmp  = sys_get_temp_dir() . '/tinyshop_import_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $fp   = fopen($tmp, 'wb');

        if ($fp === false) {
            throw new RuntimeException('Cannot create temp file');
        }

        // Build a Referer from the image URL's origin (helps bypass Cloudflare)
        $origin = parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST);

        $ch = curl_init();
        $opts = [
            CURLOPT_URL            => $url,
            CURLOPT_FILE           => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT      => self::USER_AGENT,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_2_0,
            CURLOPT_HTTPHEADER     => [
                'Accept: image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8',
                'Accept-Language: en-GB,en-US;q=0.9,en;q=0.8',
                'Referer: ' . $origin . '/',
                'sec-ch-ua: "Google Chrome";v="145", "Chromium";v="145", "Not-A.Brand";v="24"',
                'sec-ch-ua-mobile: ?0',
                'sec-ch-ua-platform: "macOS"',
                'Sec-Fetch-Dest: image',
                'Sec-Fetch-Mode: no-cors',
                'Sec-Fetch-Site: same-origin',
            ],
        ];

        $caBundle = self::findCaBundle();
        if ($caBundle !== null) {
            $opts[CURLOPT_CAINFO] = $caBundle;
        }

        curl_setopt_array($ch, $opts);

        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);
        fclose($fp);

        if ($err !== '' || $code >= 400) {
            @unlink($tmp);
            throw new RuntimeException('Image download failed: ' . ($err ?: 'HTTP ' . $code));
        }

        return $tmp;
    }
}
