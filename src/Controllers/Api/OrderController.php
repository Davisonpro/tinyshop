<?php

declare(strict_types=1);

namespace TinyShop\Controllers\Api;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use TinyShop\Controllers\Traits\JsonResponder;
use TinyShop\Enums\OrderStatus;
use TinyShop\Models\Order;
use TinyShop\Models\Product;
use TinyShop\Services\Auth;
use TinyShop\Services\Validation;

final class OrderController
{
    use JsonResponder;
    public function __construct(
        private Order $orderModel,
        private Product $productModel,
        private Auth $auth,
        private Validation $validation
    ) {}

    private const MAX_PAGE_SIZE = 100;

    public function list(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $limit  = min((int) ($params['limit'] ?? 50), self::MAX_PAGE_SIZE);
        $offset = max(0, (int) ($params['offset'] ?? 0));

        $userId = $this->auth->userId();
        $orders = $this->orderModel->findByUser($userId, $limit, $offset);
        $stats = $this->orderModel->getStats($userId);
        return $this->json($response, ['orders' => $orders, 'stats' => $stats]);
    }

    public function create(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $userId = $this->auth->userId();

        $customerName = trim($data['customer_name'] ?? '');
        $amount = (float) ($data['amount'] ?? 0);

        if ($customerName === '') {
            return $this->json($response, ['error' => true, 'message' => 'Customer name is required'], 422);
        }

        if ($err = $this->validation->maxLength($customerName, 'customer_name')) {
            return $this->json($response, ['error' => true, 'message' => $err], 422);
        }

        if ($amount <= 0) {
            return $this->json($response, ['error' => true, 'message' => 'Amount must be greater than 0'], 422);
        }

        // Validate product belongs to this seller if provided
        $productId = !empty($data['product_id']) ? (int) $data['product_id'] : null;
        if ($productId !== null) {
            $product = $this->productModel->findById($productId);
            if (!$product || (int) $product['user_id'] !== $userId) {
                return $this->json($response, ['error' => true, 'message' => 'Product not found'], 404);
            }
        }

        $orderId = $this->orderModel->create([
            'user_id'        => $userId,
            'product_id'     => $productId,
            'customer_name'  => $customerName,
            'customer_phone' => trim($data['customer_phone'] ?? ''),
            'amount'         => $amount,
            'status'         => $data['status'] ?? OrderStatus::Pending->value,
            'payment_method' => $data['payment_method'] ?? 'whatsapp',
            'reference_id'   => trim($data['notes'] ?? '') ?: null,
        ]);

        $order = $this->orderModel->findById($orderId);

        return $this->json($response, ['success' => true, 'order' => $order], 201);
    }

    public function updateStatus(Request $request, Response $response, array $args): Response
    {
        $data = (array) $request->getParsedBody();
        $userId = $this->auth->userId();
        $orderId = (int) $args['id'];

        $order = $this->orderModel->findById($orderId);
        if (!$order || (int) $order['user_id'] !== $userId) {
            return $this->json($response, ['error' => true, 'message' => 'Order not found'], 404);
        }

        $newStatus = $data['status'] ?? '';
        if (OrderStatus::tryFrom($newStatus) === null) {
            return $this->json($response, ['error' => true, 'message' => 'Invalid status'], 422);
        }

        $this->orderModel->updateStatus($orderId, $newStatus);
        $order = $this->orderModel->findById($orderId);

        return $this->json($response, ['success' => true, 'order' => $order]);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $userId = $this->auth->userId();
        $orderId = (int) $args['id'];

        $order = $this->orderModel->findById($orderId);
        if (!$order || (int) $order['user_id'] !== $userId) {
            return $this->json($response, ['error' => true, 'message' => 'Order not found'], 404);
        }

        $this->orderModel->delete($orderId);

        return $this->json($response, ['success' => true]);
    }
}
