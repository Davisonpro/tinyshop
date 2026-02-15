<?php

declare(strict_types=1);

namespace TinyShop\Controllers\Api;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use TinyShop\Controllers\Traits\JsonResponder;
use TinyShop\Models\Category;
use TinyShop\Services\Auth;
use TinyShop\Services\Validation;

final class CategoryController
{
    use JsonResponder;
    public function __construct(
        private readonly Category $categoryModel,
        private readonly Auth $auth,
        private readonly Validation $validation
    ) {}

    public function list(Request $request, Response $response): Response
    {
        $categories = $this->categoryModel->findByUser($this->auth->userId());
        $tree = $this->categoryModel->findByUserAsTree($this->auth->userId());
        return $this->json($response, ['categories' => $categories, 'tree' => $tree]);
    }

    public function create(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $name = trim($data['name'] ?? '');

        if ($name === '') {
            return $this->json($response, ['error' => true, 'message' => 'Name is required'], 422);
        }

        if ($err = $this->validation->maxLength($name, 'category_name')) {
            return $this->json($response, ['error' => true, 'message' => $err], 422);
        }

        $parentId = !empty($data['parent_id']) ? (int) $data['parent_id'] : null;

        // Validate parent belongs to this user
        if ($parentId) {
            $parent = $this->categoryModel->findById($parentId);
            if (!$parent || (int) $parent['user_id'] !== $this->auth->userId()) {
                return $this->json($response, ['error' => true, 'message' => 'Parent category not found'], 404);
            }
            // Don't allow nesting deeper than 1 level
            if ($parent['parent_id']) {
                return $this->json($response, ['error' => true, 'message' => 'Sub-categories cannot have their own sub-categories'], 422);
            }
        }

        $id = $this->categoryModel->create([
            'user_id'    => $this->auth->userId(),
            'parent_id'  => $parentId,
            'name'       => $name,
            'image_url'  => $data['image_url'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        $category = $this->categoryModel->findById($id);
        return $this->json($response, ['success' => true, 'category' => $category], 201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $category = $this->categoryModel->findById($id);

        if (!$category || (int) $category['user_id'] !== $this->auth->userId()) {
            return $this->json($response, ['error' => true, 'message' => 'Category not found'], 404);
        }

        $data = (array) $request->getParsedBody();
        $updateData = [];

        if (isset($data['name'])) {
            $name = trim($data['name']);
            if ($err = $this->validation->maxLength($name, 'category_name')) {
                return $this->json($response, ['error' => true, 'message' => $err], 422);
            }
            $updateData['name'] = $name;
        }
        if (array_key_exists('image_url', $data)) $updateData['image_url'] = $data['image_url'];
        if (isset($data['sort_order'])) $updateData['sort_order'] = (int) $data['sort_order'];
        if (array_key_exists('parent_id', $data)) {
            $newParentId = !empty($data['parent_id']) ? (int) $data['parent_id'] : null;
            if ($newParentId !== null) {
                // Cannot set parent to self
                if ($newParentId === $id) {
                    return $this->json($response, ['error' => true, 'message' => 'A category cannot be its own parent'], 422);
                }
                // Validate parent belongs to this user
                $parent = $this->categoryModel->findById($newParentId);
                if (!$parent || (int) $parent['user_id'] !== $this->auth->userId()) {
                    return $this->json($response, ['error' => true, 'message' => 'Parent category not found'], 404);
                }
                // Don't allow nesting deeper than 1 level
                if ($parent['parent_id']) {
                    return $this->json($response, ['error' => true, 'message' => 'Sub-categories cannot have their own sub-categories'], 422);
                }
            }
            $updateData['parent_id'] = $newParentId;
        }

        $this->categoryModel->update($id, $updateData);
        $category = $this->categoryModel->findById($id);

        return $this->json($response, ['success' => true, 'category' => $category]);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $category = $this->categoryModel->findById($id);

        if (!$category || (int) $category['user_id'] !== $this->auth->userId()) {
            return $this->json($response, ['error' => true, 'message' => 'Category not found'], 404);
        }

        $this->categoryModel->delete($id);
        return $this->json($response, ['success' => true]);
    }

}
