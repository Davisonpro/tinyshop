<?php

declare(strict_types=1);

namespace TinyShop\Controllers\Api;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use TinyShop\Controllers\Traits\JsonResponder;
use TinyShop\Services\Upload;
use RuntimeException;

/**
 * File upload API controller.
 *
 * @since 1.0.0
 */
final class UploadController
{
    use JsonResponder;

    public function __construct(
        private readonly Upload $upload
    ) {}

    /**
     * Store an uploaded file.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function store(Request $request, Response $response): Response
    {
        $files = $request->getUploadedFiles();
        $file = $files['file'] ?? null;

        if (!$file || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return $this->json($response, ['error' => true, 'message' => 'No file uploaded'], 422);
        }

        try {
            $url = $this->upload->store($file);
            return $this->json($response, ['success' => true, 'url' => $url], 201);
        } catch (RuntimeException $e) {
            return $this->json($response, ['error' => true, 'message' => $e->getMessage()], 422);
        }
    }
}
