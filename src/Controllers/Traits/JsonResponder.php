<?php

declare(strict_types=1);

namespace TinyShop\Controllers\Traits;

use Psr\Http\Message\ResponseInterface as Response;

/**
 * JSON response helper trait.
 *
 * @since 1.0.0
 */
trait JsonResponder
{
    /**
     * Write a JSON response.
     *
     * @since 1.0.0
     *
     * @param Response $response PSR-7 response.
     * @param array    $data     Response payload.
     * @param int      $status   HTTP status code.
     * @return Response
     */
    protected function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_THROW_ON_ERROR));

        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate');
    }
}
