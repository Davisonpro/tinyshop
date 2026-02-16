<?php

declare(strict_types=1);

namespace TinyShop\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use TinyShop\Models\Order;
use TinyShop\Models\OrderItem;
use TinyShop\Models\User;
use TinyShop\Services\View;

final class CheckoutController
{
    private const CURRENCY_SYMBOLS = [
        'KES' => 'KES ', 'USD' => '$', 'NGN' => "\u{20A6}", 'TZS' => 'TZS ',
        'UGX' => 'UGX ', 'ZAR' => 'R', 'GHS' => "GH\u{20B5}", 'GBP' => "\u{00A3}",
        'EUR' => "\u{20AC}", 'RWF' => 'RWF ', 'ETB' => 'ETB ', 'XOF' => 'CFA ',
    ];

    public function __construct(
        private readonly View $view,
        private readonly User $userModel,
        private readonly Order $orderModel,
        private readonly OrderItem $orderItemModel
    ) {}

    /**
     * GET /checkout — Checkout page (rendered client-side from cart data)
     */
    public function showCheckout(Request $request, Response $response, array $args): Response
    {
        $subdomain = $args['subdomain'] ?? '';
        $shop = $this->userModel->findBySubdomain($subdomain);

        if (!$shop) {
            return $this->view->render(
                $response->withStatus(404),
                'pages/shop_404.tpl',
                ['page_title' => 'Shop Not Found']
            );
        }

        $hasStripe = !empty($shop['stripe_enabled']) && !empty($shop['stripe_public_key']) && !empty($shop['stripe_secret_key']);
        $hasPaypal = !empty($shop['paypal_enabled']) && !empty($shop['paypal_client_id']) && !empty($shop['paypal_secret']);
        $hasCod = !empty($shop['cod_enabled']);
        $hasMpesa = !empty($shop['mpesa_enabled']) && !empty($shop['mpesa_shortcode'])
            && !empty($shop['mpesa_consumer_key']) && !empty($shop['mpesa_consumer_secret'])
            && !empty($shop['mpesa_passkey']);

        if (!$hasStripe && !$hasPaypal && !$hasCod && !$hasMpesa) {
            return $response->withHeader('Location', '/')->withStatus(302);
        }

        $currency = $shop['currency'] ?? 'KES';
        $currencySymbol = self::CURRENCY_SYMBOLS[$currency] ?? $currency . ' ';

        $this->view->setTheme($shop['shop_theme'] ?? 'classic');

        return $this->view->render($response, 'pages/checkout.tpl', [
            'page_title' => 'Checkout — ' . ($shop['store_name'] ?? ''),
            'shop' => $shop,
            'currency' => $currency,
            'currency_symbol' => $currencySymbol,
            'has_stripe' => $hasStripe,
            'has_paypal' => $hasPaypal,
            'has_cod' => $hasCod,
            'has_mpesa' => $hasMpesa,
            'shop_theme' => $shop['shop_theme'] ?? 'classic',
        ]);
    }

    /**
     * GET /order/{orderNumber} — Order confirmation page
     */
    public function showConfirmation(Request $request, Response $response, array $args): Response
    {
        $subdomain = $args['subdomain'] ?? '';
        $orderNumber = $args['orderNumber'] ?? '';

        $shop = $this->userModel->findBySubdomain($subdomain);
        if (!$shop) {
            return $this->view->render(
                $response->withStatus(404),
                'pages/shop_404.tpl',
                ['page_title' => 'Shop Not Found']
            );
        }

        $order = $this->orderModel->findByOrderNumber($orderNumber);
        if (!$order || (int) $order['user_id'] !== (int) $shop['id']) {
            return $this->view->render(
                $response->withStatus(404),
                'pages/shop_404.tpl',
                ['page_title' => 'Order Not Found']
            );
        }

        $items = $this->orderItemModel->findByOrder((int) $order['id']);
        $currency = $shop['currency'] ?? 'KES';
        $currencySymbol = self::CURRENCY_SYMBOLS[$currency] ?? $currency . ' ';

        $this->view->setTheme($shop['shop_theme'] ?? 'classic');

        return $this->view->render($response, 'pages/order_confirmation.tpl', [
            'page_title' => 'Order Confirmed — ' . $order['order_number'],
            'shop' => $shop,
            'order' => $order,
            'order_items' => $items,
            'currency' => $currency,
            'currency_symbol' => $currencySymbol,
            'shop_theme' => $shop['shop_theme'] ?? 'classic',
        ]);
    }
}
