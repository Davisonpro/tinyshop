<?php

declare(strict_types=1);

namespace TinyShop\Controllers\Api;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use TinyShop\Controllers\Traits\JsonResponder;
use TinyShop\Models\Category;
use TinyShop\Models\Product;
use TinyShop\Models\ProductImage;
use TinyShop\Services\Auth;
use TinyShop\Services\Importers\HttpClient;
use TinyShop\Services\Importers\ImporterFactory;
use TinyShop\Services\Importers\WooCommerceImporter;
use TinyShop\Services\Upload;
use TinyShop\Services\Validation;

final class ImportController
{
    use JsonResponder;

    public function __construct(
        private readonly Auth $auth,
        private readonly Product $productModel,
        private readonly ProductImage $productImageModel,
        private readonly Category $categoryModel,
        private readonly ImporterFactory $importerFactory,
        private readonly WooCommerceImporter $wooImporter,
        private readonly HttpClient $httpClient,
        private readonly Upload $upload,
        private readonly Validation $validation,
        private readonly LoggerInterface $logger,
    ) {}

    public function fetch(Request $request, Response $response): Response
    {
        $data     = (array) $request->getParsedBody();
        $url      = trim($data['url'] ?? '');
        $pasteHtml = trim($data['html'] ?? '');
        $userId   = $this->auth->userId();

        if ($pasteHtml !== '') {
            try {
                $result = $this->wooImporter->fetchFromHtml($pasteHtml);
                return $this->json($response, ['success' => true, 'product' => $result->toArray()]);
            } catch (\Throwable $e) {
                return $this->json($response, ['error' => true, 'message' => $e->getMessage()], 422);
            }
        }

        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            return $this->json($response, ['error' => true, 'message' => 'Please enter a valid product URL'], 422);
        }

        try {
            $importer = $this->importerFactory->resolve($url);
            $result   = $importer->fetch($url);
            $arr = $result->toArray();
            $arr['source_url'] = $url;

            $existing = $this->productModel->findBySourceUrl($userId, $url);
            if ($existing) {
                $arr['existing_product_id'] = (int) $existing['id'];
                $arr['existing_product_name'] = $existing['name'];
            }

            return $this->json($response, ['success' => true, 'product' => $arr]);
        } catch (\Throwable $e) {
            return $this->json($response, ['error' => true, 'message' => $e->getMessage()], 422);
        }
    }

    public function save(Request $request, Response $response): Response
    {
        $data   = (array) $request->getParsedBody();
        $userId = $this->auth->userId();
        $title  = trim($data['title'] ?? '');
        $price  = (float) ($data['price'] ?? 0);

        if ($title === '') {
            return $this->json($response, ['error' => true, 'message' => 'Product title is required'], 422);
        }

        // Resolve / create category hierarchy
        $categoryId = null;
        $categories = $data['categories'] ?? [];
        if (!empty($categories) && is_array($categories)) {
            $parentId = null;
            foreach ($categories as $catName) {
                $catName = trim($catName);
                if ($catName === '') {
                    continue;
                }

                $existing = $this->categoryModel->findByUserNameAndParent($userId, $catName, $parentId);
                if ($existing) {
                    $categoryId = (int) $existing['id'];
                } else {
                    $categoryId = $this->categoryModel->create([
                        'user_id'   => $userId,
                        'parent_id' => $parentId,
                        'name'      => $catName,
                    ]);
                }
                $parentId = $categoryId;
            }
        }

        // Download and store images
        $imageUrls = [];
        $failedImages = [];
        $remoteImages = $data['images'] ?? [];
        if (is_array($remoteImages)) {
            foreach ($remoteImages as $imgUrl) {
                $stored = $this->downloadAndStoreImage($imgUrl);
                if ($stored !== null) {
                    $imageUrls[] = $stored;
                } else {
                    $imageUrls[] = $imgUrl;
                    $failedImages[] = $imgUrl;
                }
            }
        }
        if (!empty($failedImages)) {
            $this->logger->warning('import.image_download_failed', [
                'failed' => $failedImages,
                'reason' => 'Likely Cloudflare challenge or 403',
            ]);
        }

        $comparePrice = isset($data['compare_price']) && $data['compare_price'] !== '' && $data['compare_price'] !== null
            ? (float) $data['compare_price'] : null;

        $variations = null;
        if (!empty($data['variations']) && is_array($data['variations'])) {
            $first = $data['variations'][0] ?? null;
            // Already grouped format: [{name: ..., options: [...]}]
            $isGrouped = is_array($first) && isset($first['name']) && isset($first['options']);
            $grouped = $isGrouped ? $data['variations'] : $this->convertToGroupedVariations($data['variations']);
            $variations = $this->validation->sanitizeVariations($grouped);
        }

        $slug = $this->validation->slug($title);
        $slug = $this->productModel->ensureUniqueSlug($userId, $slug);

        $productId = $this->productModel->create([
            'user_id'           => $userId,
            'category_id'       => $categoryId,
            'name'              => $title,
            'slug'              => $slug,
            'description'       => trim($data['description'] ?? ''),
            'full_description'  => trim($data['full_description'] ?? ''),
            'price'             => $price,
            'compare_price'     => $comparePrice,
            'image_url'         => $imageUrls[0] ?? null,
            'sort_order'        => 0,
            'is_sold'           => !empty($data['is_sold']) ? 1 : 0,
            'stock_quantity'    => null,
            'is_featured'       => !empty($data['is_featured']) ? 1 : 0,
            'variations'        => $variations,
            'meta_title'        => trim($data['meta_title'] ?? '') ?: null,
            'meta_description'  => trim($data['meta_description'] ?? '') ?: null,
            'source_url'        => trim($data['source_url'] ?? '') ?: null,
        ]);

        if (!empty($imageUrls)) {
            $this->productImageModel->sync($productId, $imageUrls);
        }

        $this->logger->info('seller.product_imported', [
            'user_id'    => $userId,
            'product_id' => $productId,
            'title'      => $title,
        ]);

        $msg = 'Product imported successfully';
        if (!empty($failedImages)) {
            $msg .= '. ' . count($failedImages) . ' image(s) could not be downloaded — using remote URLs instead.';
        }

        return $this->json($response, [
            'success'       => true,
            'product_id'    => $productId,
            'message'       => $msg,
            'failed_images' => $failedImages,
        ], 201);
    }

    public function categories(Request $request, Response $response): Response
    {
        $tree = $this->categoryModel->findByUserAsTree($this->auth->userId());
        return $this->json($response, ['categories' => $tree]);
    }

    public function saveCategory(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $name = trim($data['name'] ?? '');
        $userId = $this->auth->userId();

        if ($name === '') {
            return $this->json($response, ['error' => true, 'message' => 'Category name is required'], 422);
        }

        $existing = $this->categoryModel->findByUserNameAndParent($userId, $name, null);
        if ($existing) {
            return $this->json($response, ['category' => $existing]);
        }

        $id = $this->categoryModel->create([
            'user_id' => $userId,
            'name' => $name,
        ]);

        return $this->json($response, ['category' => ['id' => $id, 'name' => $name]]);
    }

    private function convertToGroupedVariations(array $flatVariations): array
    {
        $groups = [];
        $groupOrder = [];

        foreach ($flatVariations as $v) {
            $attributes = $v['attributes'] ?? [];
            $price = $v['price'] ?? null;

            foreach ($attributes as $groupName => $value) {
                if (!isset($groups[$groupName])) {
                    $groups[$groupName] = [];
                    $groupOrder[] = $groupName;
                }

                if (!isset($groups[$groupName][$value])) {
                    $groups[$groupName][$value] = $price;
                } elseif ($price !== null && ($groups[$groupName][$value] === null || $price < $groups[$groupName][$value])) {
                    $groups[$groupName][$value] = $price;
                }
            }
        }

        $result = [];
        foreach ($groupOrder as $name) {
            $options = [];
            foreach ($groups[$name] as $value => $price) {
                $opt = ['value' => (string) $value];
                if ($price !== null) {
                    $opt['price'] = $price;
                }
                $options[] = $opt;
            }
            $result[] = ['name' => $name, 'options' => $options];
        }

        return $result;
    }

    private function downloadAndStoreImage(string $url): ?string
    {
        try {
            $tmpPath  = $this->httpClient->download($url);
            $filename = basename(parse_url($url, PHP_URL_PATH) ?? 'image.jpg');
            return $this->upload->storeFromPath($tmpPath, $filename);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
