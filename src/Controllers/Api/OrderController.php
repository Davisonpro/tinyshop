<?php

declare(strict_types=1);

namespace TinyShop\Controllers\Api;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use TinyShop\Controllers\Traits\JsonResponder;
use TinyShop\Enums\OrderStatus;
use TinyShop\Models\AuditLog;
use TinyShop\Models\Coupon;
use TinyShop\Models\Order;
use TinyShop\Models\OrderItem;
use TinyShop\Models\Product;
use TinyShop\Models\ProductImage;
use TinyShop\Services\Auth;
use TinyShop\Services\Validation;

final class OrderController
{
    use JsonResponder;
    public function __construct(
        private readonly Order $orderModel,
        private readonly OrderItem $orderItemModel,
        private readonly Product $productModel,
        private readonly ProductImage $productImageModel,
        private readonly Coupon $couponModel,
        private readonly Auth $auth,
        private readonly Validation $validation,
        private readonly AuditLog $auditLog
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

        // Attach order items to each order
        $orderIds = array_map(fn($o) => (int) $o['id'], $orders);
        $allItems = $this->orderItemModel->findByOrderIds($orderIds);
        $itemsByOrder = [];
        foreach ($allItems as $item) {
            $itemsByOrder[(int) $item['order_id']][] = $item;
        }
        foreach ($orders as &$order) {
            $order['items'] = $itemsByOrder[(int) $order['id']] ?? [];
        }
        unset($order);

        return $this->json($response, ['orders' => $orders, 'stats' => $stats]);
    }

    public function create(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $userId = $this->auth->userId();

        $customerName = trim($data['customer_name'] ?? '');
        $rawAmount = $data['amount'] ?? 0;

        if ($customerName === '') {
            return $this->json($response, ['error' => true, 'message' => 'Customer name is required'], 422);
        }

        if ($err = $this->validation->maxLength($customerName, 'customer_name')) {
            return $this->json($response, ['error' => true, 'message' => $err], 422);
        }

        if (!is_numeric($rawAmount) || (float) $rawAmount <= 0) {
            return $this->json($response, ['error' => true, 'message' => 'Amount must be a valid number greater than 0'], 422);
        }
        $amount = (float) $rawAmount;

        // Validate product belongs to this seller if provided
        $productId = !empty($data['product_id']) ? (int) $data['product_id'] : null;
        if ($productId !== null) {
            $product = $this->productModel->findById($productId);
            if (!$product || (int) $product['user_id'] !== $userId) {
                return $this->json($response, ['error' => true, 'message' => 'Product not found'], 404);
            }
        }

        // Sanitize optional text fields
        $customerPhone = trim($data['customer_phone'] ?? '');
        if ($customerPhone !== '') {
            $customerPhone = preg_replace('/[^0-9+\s\-]/', '', $customerPhone);
        }
        $notes = trim($data['notes'] ?? '');
        if ($notes !== '') {
            $notes = strip_tags($notes);
        }

        // Validate status is a valid enum value
        $status = $data['status'] ?? OrderStatus::Pending->value;
        if (OrderStatus::tryFrom($status) === null) {
            $status = OrderStatus::Pending->value;
        }

        $orderId = $this->orderModel->create([
            'user_id'        => $userId,
            'order_number'   => Order::generateOrderNumber(),
            'product_id'     => $productId,
            'customer_name'  => $customerName,
            'customer_phone' => $customerPhone,
            'amount'         => $amount,
            'status'         => $status,
            'payment_method' => $data['payment_method'] ?? 'whatsapp',
            'reference_id'   => $notes ?: null,
        ]);

        // Create order items if provided
        $items = $data['items'] ?? [];
        if (!empty($items) && is_array($items)) {
            $orderItems = [];
            foreach ($items as $item) {
                $itemProductId = (int) ($item['product_id'] ?? 0);
                if ($itemProductId <= 0) {
                    continue;
                }
                $itemProduct = $this->productModel->findById($itemProductId);
                if (!$itemProduct || (int) $itemProduct['user_id'] !== $userId) {
                    continue;
                }
                $qty = max(1, (int) ($item['quantity'] ?? 1));
                $unitPrice = (float) $itemProduct['price'];
                $image = $itemProduct['image_url'] ?? null;
                if ($image === null) {
                    $images = $this->productImageModel->findByProduct($itemProductId);
                    $image = !empty($images) ? $images[0]['image_url'] : null;
                }
                $orderItems[] = [
                    'product_id'    => $itemProductId,
                    'product_name'  => $itemProduct['name'],
                    'product_image' => $image,
                    'variation'     => null,
                    'quantity'      => $qty,
                    'unit_price'    => $unitPrice,
                    'total'         => $unitPrice * $qty,
                ];
            }
            if (!empty($orderItems)) {
                $this->orderItemModel->createBatch($orderId, $orderItems);
            }
        }

        $order = $this->orderModel->findById($orderId);
        $order['items'] = $this->orderItemModel->findByOrder($orderId);

        $this->auditLog->log('order.create', $userId, 'order', $orderId, [
            'customer_name' => $customerName,
            'amount' => $amount,
        ]);

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

        $oldStatus = $order['status'];
        $this->orderModel->updateStatus($orderId, $newStatus);

        // Decrement coupon usage when order is cancelled/refunded
        $cancelStatuses = [OrderStatus::Cancelled->value, OrderStatus::Refunded->value];
        if (in_array($newStatus, $cancelStatuses, true) && !in_array($oldStatus, $cancelStatuses, true)) {
            $couponCode = $order['coupon_code'] ?? '';
            if ($couponCode !== '') {
                $coupon = $this->couponModel->findByUserAndCode($userId, $couponCode);
                if ($coupon) {
                    $this->couponModel->decrementUsage((int) $coupon['id']);
                }
            }
        }

        $order = $this->orderModel->findById($orderId);

        $this->auditLog->log('order.status_change', $userId, 'order', $orderId, [
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ]);

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

        $this->auditLog->log('order.delete', $userId, 'order', $orderId, [
            'customer_name' => $order['customer_name'] ?? null,
        ]);

        return $this->json($response, ['success' => true]);
    }
}
