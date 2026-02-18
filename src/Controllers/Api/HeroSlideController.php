<?php

declare(strict_types=1);

namespace TinyShop\Controllers\Api;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use TinyShop\Controllers\Traits\JsonResponder;
use TinyShop\Models\HeroSlide;
use TinyShop\Services\Auth;
use TinyShop\Services\Upload;

final class HeroSlideController
{
    use JsonResponder;

    private const MAX_SLIDES = 6;

    public function __construct(
        private readonly HeroSlide $heroSlideModel,
        private readonly Auth $auth,
        private readonly Upload $upload
    ) {}

    public function list(Request $request, Response $response): Response
    {
        $slides = $this->heroSlideModel->findByUser($this->auth->userId());
        return $this->json($response, ['slides' => $slides]);
    }

    public function create(Request $request, Response $response): Response
    {
        $userId = $this->auth->userId();
        $data = (array) $request->getParsedBody();

        $imageUrl = trim($data['image_url'] ?? '');
        if ($imageUrl === '') {
            return $this->json($response, ['error' => true, 'message' => 'Image is required'], 422);
        }

        $count = $this->heroSlideModel->countByUser($userId);
        if ($count >= self::MAX_SLIDES) {
            return $this->json($response, ['error' => true, 'message' => 'Maximum ' . self::MAX_SLIDES . ' slides allowed'], 422);
        }

        $heading = isset($data['heading']) ? mb_substr(trim($data['heading']), 0, 200) : null;
        $subheading = isset($data['subheading']) ? mb_substr(trim($data['subheading']), 0, 500) : null;
        $linkUrl = isset($data['link_url']) ? trim($data['link_url']) : null;
        $linkText = isset($data['link_text']) ? mb_substr(trim($data['link_text']), 0, 100) : null;

        if ($linkUrl !== null && $linkUrl !== '' && !filter_var($linkUrl, FILTER_VALIDATE_URL) && !str_starts_with($linkUrl, '/')) {
            return $this->json($response, ['error' => true, 'message' => 'Link must be a valid URL or path'], 422);
        }

        if ($heading === '') $heading = null;
        if ($subheading === '') $subheading = null;
        if ($linkUrl === '') $linkUrl = null;
        if ($linkText === '') $linkText = null;

        $id = $this->heroSlideModel->create([
            'user_id' => $userId,
            'image_url' => $imageUrl,
            'heading' => $heading,
            'subheading' => $subheading,
            'link_url' => $linkUrl,
            'link_text' => $linkText,
            'position' => $count,
        ]);

        $slide = $this->heroSlideModel->findById($id);
        return $this->json($response, ['success' => true, 'slide' => $slide], 201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $userId = $this->auth->userId();
        $slideId = (int) ($args['id'] ?? 0);
        $slide = $this->heroSlideModel->findById($slideId);

        if (!$slide || (int) $slide['user_id'] !== $userId) {
            return $this->json($response, ['error' => true, 'message' => 'Slide not found'], 404);
        }

        $data = (array) $request->getParsedBody();
        $update = [];

        if (isset($data['image_url'])) {
            $url = trim($data['image_url']);
            if ($url === '') {
                return $this->json($response, ['error' => true, 'message' => 'Image is required'], 422);
            }
            $update['image_url'] = $url;
        }

        if (isset($data['heading'])) {
            $val = mb_substr(trim($data['heading']), 0, 200);
            $update['heading'] = $val === '' ? null : $val;
        }

        if (isset($data['subheading'])) {
            $val = mb_substr(trim($data['subheading']), 0, 500);
            $update['subheading'] = $val === '' ? null : $val;
        }

        if (isset($data['link_url'])) {
            $val = trim($data['link_url']);
            if ($val !== '' && !filter_var($val, FILTER_VALIDATE_URL) && !str_starts_with($val, '/')) {
                return $this->json($response, ['error' => true, 'message' => 'Link must be a valid URL or path'], 422);
            }
            $update['link_url'] = $val === '' ? null : $val;
        }

        if (isset($data['link_text'])) {
            $val = mb_substr(trim($data['link_text']), 0, 100);
            $update['link_text'] = $val === '' ? null : $val;
        }

        if (isset($data['is_active'])) {
            $update['is_active'] = $data['is_active'] ? 1 : 0;
        }

        if (empty($update)) {
            return $this->json($response, ['error' => true, 'message' => 'Nothing to update'], 422);
        }

        $this->heroSlideModel->update($slideId, $update);
        $updated = $this->heroSlideModel->findById($slideId);
        return $this->json($response, ['success' => true, 'slide' => $updated]);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $userId = $this->auth->userId();
        $slideId = (int) ($args['id'] ?? 0);
        $slide = $this->heroSlideModel->findById($slideId);

        if (!$slide || (int) $slide['user_id'] !== $userId) {
            return $this->json($response, ['error' => true, 'message' => 'Slide not found'], 404);
        }

        // Delete uploaded image
        if (!empty($slide['image_url'])) {
            $this->upload->deleteFile($slide['image_url']);
        }

        $this->heroSlideModel->delete($slideId);
        return $this->json($response, ['success' => true, 'message' => 'Slide deleted']);
    }

    public function reorder(Request $request, Response $response): Response
    {
        $userId = $this->auth->userId();
        $data = (array) $request->getParsedBody();
        $ids = $data['ids'] ?? [];

        if (!is_array($ids) || empty($ids)) {
            return $this->json($response, ['error' => true, 'message' => 'Invalid slide order'], 422);
        }

        $ids = array_map('intval', $ids);
        $this->heroSlideModel->reorder($userId, $ids);
        return $this->json($response, ['success' => true]);
    }
}
