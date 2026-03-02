<?php

declare(strict_types=1);

namespace TinyShop\Services\OAuth;

/**
 * HTTP helpers for OAuth flows.
 *
 * @since 1.0.0
 */
trait OAuthHttpTrait
{
    /** POST form data and decode the JSON response. */
    private function httpPost(string $url, array $data): ?array
    {
        $ctx = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($data),
                'timeout' => 10,
            ],
        ]);

        // Suppress warning — network failures are handled by the false check below.
        $result = @file_get_contents($url, false, $ctx);
        if ($result === false) {
            return null;
        }

        return json_decode($result, true);
    }

    /** GET with a Bearer token and decode the JSON response. */
    private function httpGet(string $url, string $token): ?array
    {
        $ctx = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'header'  => "Authorization: Bearer {$token}\r\n",
                'timeout' => 10,
            ],
        ]);

        // Suppress warning — network failures are handled by the false check below.
        $result = @file_get_contents($url, false, $ctx);
        if ($result === false) {
            return null;
        }

        return json_decode($result, true);
    }
}
