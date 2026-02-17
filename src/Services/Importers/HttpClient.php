<?php

declare(strict_types=1);

namespace TinyShop\Services\Importers;

use RuntimeException;

final class HttpClient
{
    private const TIMEOUT = 15;

    private const USER_AGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

    /** Fetch URL body as string. */
    public function get(string $url): string
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT      => self::USER_AGENT,
            CURLOPT_HTTPHEADER     => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,application/json;q=0.8,*/*;q=0.7',
                'Accept-Language: en-US,en;q=0.9',
            ],
            CURLOPT_COOKIEJAR      => sys_get_temp_dir() . '/tinyshop_import_cookies.txt',
            CURLOPT_COOKIEFILE     => sys_get_temp_dir() . '/tinyshop_import_cookies.txt',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_ENCODING       => '',
        ]);

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

    /** Download a file to a temporary path. Returns the temp file path. */
    public function download(string $url): string
    {
        $ext  = pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION) ?: 'jpg';
        $tmp  = sys_get_temp_dir() . '/tinyshop_import_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $fp   = fopen($tmp, 'wb');

        if ($fp === false) {
            throw new RuntimeException('Cannot create temp file');
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_FILE           => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT      => self::USER_AGENT,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

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
