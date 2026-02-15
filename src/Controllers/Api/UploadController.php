<?php

declare(strict_types=1);

namespace TinyShop\Controllers\Api;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use TinyShop\Controllers\Traits\JsonResponder;
use TinyShop\Services\Upload;
use RuntimeException;

final class UploadController
{
    use JsonResponder;

    public function __construct(
        private readonly Upload $upload
    ) {}

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
