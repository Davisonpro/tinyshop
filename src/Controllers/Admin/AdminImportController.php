<?php

declare(strict_types=1);

namespace TinyShop\Controllers\Admin;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use TinyShop\Controllers\Traits\JsonResponder;
use TinyShop\Models\Category;
use TinyShop\Models\Product;
use TinyShop\Models\ProductImage;
use TinyShop\Models\User;
use TinyShop\Services\Auth;
use TinyShop\Services\Importers\HttpClient;
use TinyShop\Services\Importers\ImporterFactory;
use TinyShop\Services\Importers\WooCommerceImporter;
use TinyShop\Services\Upload;
use TinyShop\Services\Validation;
use TinyShop\Services\View;

final class AdminImportController
{
    use JsonResponder;

    public function __construct(
        private readonly View $view,
        private readonly Auth $auth,
        private readonly User $userModel,
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

    public function import(Request $request, Response $response): Response
    {
        $sellers = $this->userModel->findSellers(500, 0);

        return $this->view->render($response, 'pages/admin/import.tpl', [
            'page_title'  => 'Import Product',
            'active_page' => 'import',
            'sellers'     => $sellers,
        ]);
    }

    public function importCategories(Request $request, Response $response, array $args): Response
    {
        $sellerId = (int) $args['seller_id'];
        $tree = $this->categoryModel->findByUserAsTree($sellerId);
        return $this->json($response, ['categories' => $tree]);
    }

    public function importSaveCategory(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $sellerId = (int) ($data['seller_id'] ?? 0);
        $name = trim($data['name'] ?? '');

        if ($sellerId <= 0 || $name === '') {
            return $this->json($response, ['error' => true, 'message' => 'Seller and category name are required'], 422);
        }

        $existing = $this->categoryModel->findByUserNameAndParent($sellerId, $name, null);
        if ($existing) {
            return $this->json($response, ['category' => $existing]);
        }

        $id = $this->categoryModel->create([
            'user_id' => $sellerId,
            'name' => $name,
        ]);

        return $this->json($response, ['category' => ['id' => $id, 'name' => $name]]);
    }

    public function fetchImport(Request $request, Response $response): Response
    {
        $data     = (array) $request->getParsedBody();
        $url      = trim($data['url'] ?? '');
        $pasteHtml = trim($data['html'] ?? '');

        // Paste-HTML mode: admin pasted page source (Cloudflare fallback)
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

            // Check for existing import (re-import detection)
            $sellerId = (int) ($data['seller_id'] ?? 0);
            if ($sellerId > 0) {
                $existing = $this->productModel->findBySourceUrl($sellerId, $url);
                if ($existing) {
                    $arr['existing_product_id'] = (int) $existing['id'];
                    $arr['existing_product_name'] = $existing['name'];
                }
            }

            return $this->json($response, ['success' => true, 'product' => $arr]);
        } catch (\Throwable $e) {
            return $this->json($response, ['error' => true, 'message' => $e->getMessage()], 422);
        }
    }

    public function saveImport(Request $request, Response $response): Response
    {
        $data     = (array) $request->getParsedBody();
        $sellerId = (int) ($data['seller_id'] ?? 0);
        $title    = trim($data['title'] ?? '');
        $price    = (float) ($data['price'] ?? 0);

        if ($sellerId === 0) {
            return $this->json($response, ['error' => true, 'message' => 'Please select a seller'], 422);
        }

        $seller = User::find($sellerId);
        if (!$seller) {
            return $this->json($response, ['error' => true, 'message' => 'Seller not found'], 404);
        }

        if ($title === '') {
            return $this->json($response, ['error' => true, 'message' => 'Product title is required'], 422);
        }

        // Resolve / create category hierarchy — match existing before creating
        $categoryId = null;
        $categories = $data['categories'] ?? [];
        if (!empty($categories) && is_array($categories)) {
            $parentId = null;
            foreach ($categories as $catName) {
                $catName = trim($catName);
                if ($catName === '') {
                    continue;
                }

                $existing = $this->categoryModel->findByUserNameAndParent($sellerId, $catName, $parentId);
                if ($existing) {
                    $categoryId = (int) $existing['id'];
                } else {
                    $categoryId = $this->categoryModel->create([
                        'user_id'   => $sellerId,
                        'parent_id' => $parentId,
                        'name'      => $catName,
                    ]);
                }
                $parentId = $categoryId;
            }
        }

        // Download and store images (fall back to remote URL if download fails e.g. Cloudflare)
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

        // Convert flat WooCommerce variations to TinyShop grouped format
        $variations = null;
        if (!empty($data['variations']) && is_array($data['variations'])) {
            $grouped = $this->convertToGroupedVariations($data['variations']);
            $variations = $this->validation->sanitizeVariations($grouped);
        }

        $slug = $this->validation->slug($title);
        $slug = $this->productModel->ensureUniqueSlug($sellerId, $slug);

        $productId = $this->productModel->create([
            'user_id'           => $sellerId,
            'category_id'       => $categoryId,
            'name'              => $title,
            'slug'              => $slug,
            'description'       => trim($data['description'] ?? ''),
            'full_description'  => trim($data['full_description'] ?? ''),
            'price'             => $price,
            'compare_price'     => $comparePrice,
            'image_url'         => $imageUrls[0] ?? null,
            'sort_order'        => 0,
            'is_sold'           => 0,
            'stock_quantity'    => null,
            'is_featured'       => 0,
            'variations'        => $variations,
            'source_url'        => trim($data['source_url'] ?? '') ?: null,
        ]);

        // Sync product images
        if (!empty($imageUrls)) {
            $this->productImageModel->sync($productId, $imageUrls);
        }

        $this->logger->info('admin.product_imported', [
            'admin_id'   => $this->auth->userId(),
            'seller_id'  => $sellerId,
            'product_id' => $productId,
            'title'      => $title,
        ]);

        $msg = 'Product imported successfully';
        if (!empty($failedImages)) {
            $msg .= '. ' . count($failedImages) . ' image(s) could not be downloaded (Cloudflare blocked) — using remote URLs instead.';
        }

        return $this->json($response, [
            'success'       => true,
            'product_id'    => $productId,
            'message'       => $msg,
            'failed_images' => $failedImages,
        ], 201);
    }

    /**
     * Convert flat WooCommerce-style variations into TinyShop grouped format.
     *
     * @return array[]
     */
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
