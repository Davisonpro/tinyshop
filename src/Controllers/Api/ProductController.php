<?php

declare(strict_types=1);

namespace TinyShop\Controllers\Api;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use TinyShop\Controllers\Traits\JsonResponder;
use TinyShop\Models\Product;
use TinyShop\Models\ProductImage;
use TinyShop\Services\Auth;
use TinyShop\Services\DB;
use TinyShop\Services\Hooks;
use TinyShop\Services\Upload;
use TinyShop\Services\Validation;

final class ProductController
{
    use JsonResponder;

    private \PDO $db;

    public function __construct(
        private Product $productModel,
        private ProductImage $productImageModel,
        private Auth $auth,
        private Upload $upload,
        private Validation $validation,
        DB $database
    ) {
        $this->db = $database->pdo();
    }

    private const MAX_PAGE_SIZE = 100;

    public function list(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $limit  = min((int) ($params['limit'] ?? 50), self::MAX_PAGE_SIZE);
        $offset = max(0, (int) ($params['offset'] ?? 0));

        $userId = $this->auth->userId();
        $products = $this->productModel->findByUser($userId, $limit, $offset);
        $total = $this->productModel->countByUser($userId);

        $productIds = array_map(fn($p) => (int) $p['id'], $products);
        $imagesByProduct = $this->productImageModel->findByProducts($productIds);

        foreach ($products as &$product) {
            $product['images'] = $imagesByProduct[(int) $product['id']] ?? [];
        }
        unset($product);

        $products = Hooks::applyFilter('api.products.list', $products);
        return $this->json($response, ['products' => $products, 'total' => $total]);
    }

    public function create(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $name  = trim($data['name'] ?? '');
        $price = $data['price'] ?? null;

        if ($name === '' || $price === null) {
            return $this->json($response, ['error' => true, 'message' => 'Name and price are required'], 422);
        }

        if ($err = $this->validation->maxLength($name, 'name')) {
            return $this->json($response, ['error' => true, 'message' => $err], 422);
        }

        $description = trim($data['description'] ?? '');
        if ($description !== '' && ($err = $this->validation->maxLength($description, 'description'))) {
            return $this->json($response, ['error' => true, 'message' => $err], 422);
        }

        if (!empty($data['meta_title']) && ($err = $this->validation->maxLength(trim($data['meta_title']), 'meta_title'))) {
            return $this->json($response, ['error' => true, 'message' => $err], 422);
        }

        if (!empty($data['meta_description']) && ($err = $this->validation->maxLength(trim($data['meta_description']), 'meta_description'))) {
            return $this->json($response, ['error' => true, 'message' => $err], 422);
        }

        $categoryId = !empty($data['category_id']) ? (int) $data['category_id'] : null;

        $comparePrice = isset($data['compare_price']) && $data['compare_price'] !== '' && $data['compare_price'] !== null
            ? (float) $data['compare_price'] : null;

        $variations = !empty($data['variations']) ? json_encode($data['variations']) : null;

        $slug = $this->validation->slug($name);
        $slug = $this->productModel->ensureUniqueSlug($this->auth->userId(), $slug);

        $id = $this->productModel->create([
            'user_id'          => $this->auth->userId(),
            'category_id'      => $categoryId,
            'name'             => $name,
            'slug'             => $slug,
            'description'      => trim($data['description'] ?? ''),
            'price'            => (float) $price,
            'compare_price'    => $comparePrice,
            'image_url'        => $data['image_url'] ?? null,
            'sort_order'       => (int) ($data['sort_order'] ?? 0),
            'is_sold'          => !empty($data['is_sold']) ? 1 : 0,
            'is_featured'      => !empty($data['is_featured']) ? 1 : 0,
            'variations'       => $variations,
            'meta_title'       => !empty($data['meta_title']) ? trim($data['meta_title']) : null,
            'meta_description' => !empty($data['meta_description']) ? trim($data['meta_description']) : null,
        ]);

        // Sync product images
        $images = $data['images'] ?? [];
        if (!empty($images)) {
            $this->productImageModel->sync($id, $images);
        }

        $product = $this->productModel->findById($id);
        $product['images'] = $this->productImageModel->findByProduct($id);
        Hooks::doAction('product.created', $product);

        return $this->json($response, ['success' => true, 'product' => $product], 201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $product = $this->productModel->findById($id);

        if (!$product || (int) $product['user_id'] !== $this->auth->userId()) {
            return $this->json($response, ['error' => true, 'message' => 'Product not found'], 404);
        }

        $data = (array) $request->getParsedBody();
        $updateData = [];

        if (isset($data['name'])) {
            $name = trim($data['name']);
            if ($err = $this->validation->maxLength($name, 'name')) {
                return $this->json($response, ['error' => true, 'message' => $err], 422);
            }
            $updateData['name'] = $name;
            $newSlug = $this->validation->slug($name);
            $updateData['slug'] = $this->productModel->ensureUniqueSlug($this->auth->userId(), $newSlug, $id);
        }
        if (isset($data['description'])) {
            $desc = trim($data['description']);
            if ($desc !== '' && ($err = $this->validation->maxLength($desc, 'description'))) {
                return $this->json($response, ['error' => true, 'message' => $err], 422);
            }
            $updateData['description'] = $desc;
        }
        if (isset($data['price']))       $updateData['price'] = (float) $data['price'];
        if (array_key_exists('compare_price', $data)) {
            $updateData['compare_price'] = ($data['compare_price'] !== '' && $data['compare_price'] !== null)
                ? (float) $data['compare_price'] : null;
        }
        if (isset($data['image_url']))   $updateData['image_url'] = $data['image_url'];
        if (isset($data['sort_order']))  $updateData['sort_order'] = (int) $data['sort_order'];
        if (isset($data['is_active']))   $updateData['is_active'] = (int) $data['is_active'];
        if (array_key_exists('is_sold', $data)) $updateData['is_sold'] = !empty($data['is_sold']) ? 1 : 0;
        if (array_key_exists('is_featured', $data)) $updateData['is_featured'] = !empty($data['is_featured']) ? 1 : 0;
        if (array_key_exists('variations', $data)) {
            $updateData['variations'] = !empty($data['variations']) ? json_encode($data['variations']) : null;
        }
        if (array_key_exists('category_id', $data)) {
            $updateData['category_id'] = !empty($data['category_id']) ? (int) $data['category_id'] : null;
        }
        if (array_key_exists('meta_title', $data)) {
            $title = !empty($data['meta_title']) ? trim($data['meta_title']) : null;
            if ($title !== null && ($err = $this->validation->maxLength($title, 'meta_title'))) {
                return $this->json($response, ['error' => true, 'message' => $err], 422);
            }
            $updateData['meta_title'] = $title;
        }
        if (array_key_exists('meta_description', $data)) {
            $metaDesc = !empty($data['meta_description']) ? trim($data['meta_description']) : null;
            if ($metaDesc !== null && ($err = $this->validation->maxLength($metaDesc, 'meta_description'))) {
                return $this->json($response, ['error' => true, 'message' => $err], 422);
            }
            $updateData['meta_description'] = $metaDesc;
        }

        $this->productModel->update($id, $updateData);

        // Sync product images if provided, delete removed files from disk
        if (isset($data['images'])) {
            $oldImages = $this->productImageModel->findByProduct($id);
            $oldUrls = array_column($oldImages, 'image_url');
            $newUrls = array_filter($data['images']);

            // Delete files that were removed
            $removedUrls = array_diff($oldUrls, $newUrls);
            foreach ($removedUrls as $url) {
                $this->upload->deleteFile($url);
            }

            $this->productImageModel->sync($id, $data['images']);
        }

        $product = $this->productModel->findById($id);
        $product['images'] = $this->productImageModel->findByProduct($id);

        Hooks::doAction('product.updated', $product);

        return $this->json($response, ['success' => true, 'product' => $product]);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $product = $this->productModel->findById($id);

        if (!$product || (int) $product['user_id'] !== $this->auth->userId()) {
            return $this->json($response, ['error' => true, 'message' => 'Product not found'], 404);
        }

        // Collect image URLs before deleting DB rows
        $images = $this->productImageModel->findByProduct($id);
        $imageUrls = array_column($images, 'image_url');
        if (!empty($product['image_url'])) {
            $imageUrls[] = $product['image_url'];
        }

        // Delete DB rows in a transaction, then clean up files
        $this->db->beginTransaction();
        try {
            $this->productImageModel->deleteByProduct($id);
            $this->productModel->delete($id);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        foreach ($imageUrls as $url) {
            $this->upload->deleteFile($url);
        }

        Hooks::doAction('product.deleted', $id);

        return $this->json($response, ['success' => true]);
    }

}
