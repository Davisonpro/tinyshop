<?php

declare(strict_types=1);

namespace TinyShop\Services\OAuth;

trait OAuthHttpTrait
{
    /** @return array<string, mixed>|null */
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

        $result = @file_get_contents($url, false, $ctx);
        if ($result === false) {
            return null;
        }

        return json_decode($result, true);
    }

    /** @return array<string, mixed>|null */
    private function httpGet(string $url, string $token): ?array
    {
        $ctx = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'header'  => "Authorization: Bearer {$token}\r\n",
                'timeout' => 10,
            ],
        ]);

        $result = @file_get_contents($url, false, $ctx);
        if ($result === false) {
            return null;
        }

        return json_decode($result, true);
    }
}
