<?php

declare(strict_types=1);

namespace TinyShop\Controllers\Api;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use TinyShop\Controllers\Traits\JsonResponder;
use TinyShop\Models\AuditLog;
use TinyShop\Models\Product;
use TinyShop\Models\ProductImage;
use TinyShop\Services\Auth;
use TinyShop\Services\DB;
use TinyShop\Services\Hooks;
use TinyShop\Services\PlanGuard;
use TinyShop\Services\Upload;
use TinyShop\Services\Validation;

final class ProductController
{
    use JsonResponder;

    private readonly \PDO $db;

    public function __construct(
        private readonly Product $productModel,
        private readonly ProductImage $productImageModel,
        private readonly Auth $auth,
        private readonly Upload $upload,
        private readonly Validation $validation,
        private readonly PlanGuard $planGuard,
        private readonly AuditLog $auditLog,
        DB $database
    ) {
        $this->db = $database->pdo();
    }

    private const MAX_PAGE_SIZE = 100;

    public function list(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $requestedLimit = (int) ($params['limit'] ?? 50);
        $offset = max(0, (int) ($params['offset'] ?? 0));

        $userId = $this->auth->userId();

        // limit=0 means "return all products" (for client-side filtering)
        $limit = $requestedLimit === 0 ? 10000 : min($requestedLimit, self::MAX_PAGE_SIZE);
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
        // Plan limit check
        if (!$this->planGuard->canCreateProduct($this->auth->userId())) {
            return $this->json($response, ['error' => true, 'message' => "You've reached the product limit on your current plan. Upgrade to add more."], 403);
        }

        $data = (array) $request->getParsedBody();
        $name  = trim($data['name'] ?? '');
        $price = $data['price'] ?? null;

        if ($name === '' || $price === null) {
            return $this->json($response, ['error' => true, 'message' => 'Name and price are required'], 422);
        }

        if (!is_numeric($price) || (float) $price < 0) {
            return $this->json($response, ['error' => true, 'message' => 'Price must be a valid number (0 or greater)'], 422);
        }

        if ($err = $this->validation->maxLength($name, 'name')) {
            return $this->json($response, ['error' => true, 'message' => $err], 422);
        }

        $description = trim($data['description'] ?? '');
        $description = $this->validation->sanitizeHtml($description);
        if ($description !== '' && ($err = $this->validation->maxLength($description, 'description'))) {
            return $this->json($response, ['error' => true, 'message' => $err], 422);
        }

        $fullDescription = trim($data['full_description'] ?? '');
        $fullDescription = $this->validation->sanitizeHtml($fullDescription);

        if (!empty($data['meta_title'])) {
            $data['meta_title'] = strip_tags(trim($data['meta_title']));
            if ($err = $this->validation->maxLength($data['meta_title'], 'meta_title')) {
                return $this->json($response, ['error' => true, 'message' => $err], 422);
            }
        }

        if (!empty($data['meta_description'])) {
            $data['meta_description'] = strip_tags(trim($data['meta_description']));
            if ($err = $this->validation->maxLength($data['meta_description'], 'meta_description')) {
                return $this->json($response, ['error' => true, 'message' => $err], 422);
            }
        }

        $categoryId = !empty($data['category_id']) ? (int) $data['category_id'] : null;

        $comparePrice = isset($data['compare_price']) && $data['compare_price'] !== '' && $data['compare_price'] !== null
            ? (float) $data['compare_price'] : null;
        if ($comparePrice !== null && (!is_numeric($data['compare_price']) || $comparePrice < 0)) {
            return $this->json($response, ['error' => true, 'message' => 'Compare price must be a valid number'], 422);
        }

        $variations = !empty($data['variations']) ? $this->validation->sanitizeVariations($data['variations']) : null;

        $slug = $this->validation->slug($name);
        $slug = $this->productModel->ensureUniqueSlug($this->auth->userId(), $slug);

        $id = $this->productModel->create([
            'user_id'          => $this->auth->userId(),
            'category_id'      => $categoryId,
            'name'             => $name,
            'slug'             => $slug,
            'description'       => $description,
            'full_description'  => $fullDescription,
            'price'             => (float) $price,
            'compare_price'    => $comparePrice,
            'image_url'        => $data['image_url'] ?? null,
            'sort_order'       => (int) ($data['sort_order'] ?? 0),
            'is_sold'          => !empty($data['is_sold']) ? 1 : 0,
            'stock_quantity'   => array_key_exists('stock_quantity', $data) && $data['stock_quantity'] !== null && $data['stock_quantity'] !== '' ? max(0, (int) $data['stock_quantity']) : null,
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

        $this->auditLog->log('product.create', $this->auth->userId(), 'product', $id, ['name' => $name]);

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
            $desc = $this->validation->sanitizeHtml($desc);
            if ($desc !== '' && ($err = $this->validation->maxLength($desc, 'description'))) {
                return $this->json($response, ['error' => true, 'message' => $err], 422);
            }
            $updateData['description'] = $desc;
        }
        if (isset($data['full_description'])) {
            $fullDesc = trim($data['full_description']);
            $fullDesc = $this->validation->sanitizeHtml($fullDesc);
            $updateData['full_description'] = $fullDesc;
        }
        if (isset($data['price'])) {
            if (!is_numeric($data['price']) || (float) $data['price'] < 0) {
                return $this->json($response, ['error' => true, 'message' => 'Price must be a valid number (0 or greater)'], 422);
            }
            $updateData['price'] = (float) $data['price'];
        }
        if (array_key_exists('compare_price', $data)) {
            if ($data['compare_price'] !== '' && $data['compare_price'] !== null) {
                if (!is_numeric($data['compare_price']) || (float) $data['compare_price'] < 0) {
                    return $this->json($response, ['error' => true, 'message' => 'Compare price must be a valid number'], 422);
                }
                $updateData['compare_price'] = (float) $data['compare_price'];
            } else {
                $updateData['compare_price'] = null;
            }
        }
        if (isset($data['image_url']))   $updateData['image_url'] = $data['image_url'];
        if (isset($data['sort_order']))  $updateData['sort_order'] = (int) $data['sort_order'];
        if (isset($data['is_active']))   $updateData['is_active'] = (int) $data['is_active'];
        if (array_key_exists('is_sold', $data)) $updateData['is_sold'] = !empty($data['is_sold']) ? 1 : 0;
        if (array_key_exists('stock_quantity', $data)) {
            $updateData['stock_quantity'] = ($data['stock_quantity'] !== null && $data['stock_quantity'] !== '') ? max(0, (int) $data['stock_quantity']) : null;
        }
        if (array_key_exists('is_featured', $data)) $updateData['is_featured'] = !empty($data['is_featured']) ? 1 : 0;
        if (array_key_exists('variations', $data)) {
            $updateData['variations'] = !empty($data['variations']) ? $this->validation->sanitizeVariations($data['variations']) : null;
        }
        if (array_key_exists('category_id', $data)) {
            $updateData['category_id'] = !empty($data['category_id']) ? (int) $data['category_id'] : null;
        }
        if (array_key_exists('meta_title', $data)) {
            $title = !empty($data['meta_title']) ? strip_tags(trim($data['meta_title'])) : null;
            if ($title !== null && ($err = $this->validation->maxLength($title, 'meta_title'))) {
                return $this->json($response, ['error' => true, 'message' => $err], 422);
            }
            $updateData['meta_title'] = $title;
        }
        if (array_key_exists('meta_description', $data)) {
            $metaDesc = !empty($data['meta_description']) ? strip_tags(trim($data['meta_description'])) : null;
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

        $this->auditLog->log('product.update', $this->auth->userId(), 'product', $id, ['fields' => array_keys($updateData)]);

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

        $this->auditLog->log('product.delete', $this->auth->userId(), 'product', $id, ['name' => $product['name']]);

        return $this->json($response, ['success' => true]);
    }

    public function bulkArchive(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $ids = $data['ids'] ?? [];

        if (!is_array($ids) || empty($ids)) {
            return $this->json($response, ['error' => true, 'message' => 'No products selected'], 422);
        }

        $ids = array_map('intval', array_slice($ids, 0, 100));
        $userId = $this->auth->userId();
        $count = 0;

        foreach ($ids as $id) {
            $product = $this->productModel->findById($id);
            if ($product && (int) $product['user_id'] === $userId) {
                $this->productModel->update($id, ['is_active' => 0]);
                $count++;
            }
        }

        return $this->json($response, ['success' => true, 'archived' => $count]);
    }

    public function bulkDelete(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $ids = $data['ids'] ?? [];

        if (!is_array($ids) || empty($ids)) {
            return $this->json($response, ['error' => true, 'message' => 'No products selected'], 422);
        }

        $ids = array_map('intval', array_slice($ids, 0, 100));
        $userId = $this->auth->userId();
        $count = 0;

        $this->db->beginTransaction();
        try {
            foreach ($ids as $id) {
                $product = $this->productModel->findById($id);
                if (!$product || (int) $product['user_id'] !== $userId) {
                    continue;
                }

                $images = $this->productImageModel->findByProduct($id);
                $imageUrls = array_column($images, 'image_url');
                if (!empty($product['image_url'])) {
                    $imageUrls[] = $product['image_url'];
                }

                $this->productImageModel->deleteByProduct($id);
                $this->productModel->delete($id);

                foreach ($imageUrls as $url) {
                    $this->upload->deleteFile($url);
                }

                $count++;
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return $this->json($response, ['success' => true, 'deleted' => $count]);
    }

    public function duplicate(Request $request, Response $response, array $args): Response
    {
        // Plan limit check
        if (!$this->planGuard->canCreateProduct($this->auth->userId())) {
            return $this->json($response, ['error' => true, 'message' => "You've reached the product limit on your current plan. Upgrade to add more."], 403);
        }

        $id = (int) $args['id'];
        $product = $this->productModel->findById($id);

        if (!$product || (int) $product['user_id'] !== $this->auth->userId()) {
            return $this->json($response, ['error' => true, 'message' => 'Product not found'], 404);
        }

        $newName = mb_strlen($product['name']) > 93
            ? mb_substr($product['name'], 0, 93) . ' (Copy)'
            : $product['name'] . ' (Copy)';

        $slug = $this->validation->slug($newName);
        $slug = $this->productModel->ensureUniqueSlug($this->auth->userId(), $slug);

        $newId = $this->productModel->create([
            'user_id'        => $this->auth->userId(),
            'category_id'    => $product['category_id'],
            'name'           => $newName,
            'slug'           => $slug,
            'description'       => $product['description'],
            'full_description'  => $product['full_description'],
            'price'             => (float) $product['price'],
            'compare_price'  => $product['compare_price'] ? (float) $product['compare_price'] : null,
            'image_url'      => null,
            'sort_order'     => 0,
            'is_sold'        => 0,
            'stock_quantity'  => $product['stock_quantity'],
            'is_featured'    => 0,
            'variations'     => $product['variations'],
        ]);

        return $this->json($response, [
            'success'      => true,
            'redirect_url' => '/dashboard/products/' . $newId . '/edit',
        ], 201);
    }

}
