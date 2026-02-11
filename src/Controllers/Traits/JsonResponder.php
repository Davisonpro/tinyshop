<?php

declare(strict_types=1);

namespace TinyShop\Controllers\Traits;

use Psr\Http\Message\ResponseInterface as Response;

trait JsonResponder
{
    protected function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_THROW_ON_ERROR));

        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
    }
}
