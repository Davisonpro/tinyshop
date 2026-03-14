<?php

declare(strict_types=1);

namespace TinyShop\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use TinyShop\App;
use TinyShop\Models\Customer;
use TinyShop\Models\Order;
use TinyShop\Models\OrderItem;
use TinyShop\Models\ThemeOption;
use TinyShop\Models\User;
use TinyShop\Services\CustomerAuth;
use TinyShop\Services\PlanGuard;
use TinyShop\Services\View;
use TinyShop\Services\Theme;

/**
 * Storefront checkout controller.
 *
 * @since 1.0.0
 */
final class CheckoutController
{
    private const CURRENCY_SYMBOLS = [
        'KES' => 'KES ', 'USD' => '$', 'NGN' => "\u{20A6}", 'TZS' => 'TZS ',
        'UGX' => 'UGX ', 'ZAR' => 'R', 'GHS' => "GH\u{20B5}", 'GBP' => "\u{00A3}",
        'EUR' => "\u{20AC}", 'RWF' => 'RWF ', 'ETB' => 'ETB ', 'XOF' => 'CFA ',
    ];

    private const PALETTES = [
        'default'    => ['primary' => '#222222', 'bar' => '#222222', 'bar_text' => '#ffffff', 'accent' => '#222222'],
        'ocean'      => ['primary' => '#0F2B46', 'bar' => '#0F2B46', 'bar_text' => '#ffffff', 'accent' => '#E07A5F'],
        'forest'     => ['primary' => '#1B4332', 'bar' => '#1B4332', 'bar_text' => '#ffffff', 'accent' => '#D4A373'],
        'sunset'     => ['primary' => '#5C1A0A', 'bar' => '#5C1A0A', 'bar_text' => '#ffffff', 'accent' => '#457B9D'],
        'lavender'   => ['primary' => '#2D2457', 'bar' => '#2D2457', 'bar_text' => '#ffffff', 'accent' => '#F2CC8F'],
        'cherry'     => ['primary' => '#7B1E34', 'bar' => '#7B1E34', 'bar_text' => '#ffffff', 'accent' => '#C9534A'],
        'sage'       => ['primary' => '#5A7247', 'bar' => '#5A7247', 'bar_text' => '#ffffff', 'accent' => '#8B6F4E'],
        'midnight'   => ['primary' => '#151B2B', 'bar' => '#151B2B', 'bar_text' => '#ffffff', 'accent' => '#4A7CFF'],
        'mocha'      => ['primary' => '#3E2723', 'bar' => '#3E2723', 'bar_text' => '#ffffff', 'accent' => '#A1887F'],
        'blush'      => ['primary' => '#4A3040', 'bar' => '#4A3040', 'bar_text' => '#ffffff', 'accent' => '#D4889E'],
    ];

    public function __construct(
        private readonly View $view,
        private readonly Theme $theme,
        private readonly User $userModel,
        private readonly Order $orderModel,
        private readonly OrderItem $orderItemModel,
        private readonly ThemeOption $themeOptionModel,
        private readonly PlanGuard $planGuard,
        private readonly CustomerAuth $customerAuth,
        private readonly Customer $customerModel
    ) {}

    /**
     * Render the checkout page.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @param array    $args     Route arguments.
     * @return Response
     */
    public function showCheckout(Request $request, Response $response, array $args): Response
    {
        $subdomain = $args['subdomain'] ?? '';
        $shop = $this->userModel->findBySubdomain($subdomain);

        if (!$shop) {
            return $this->view->render(
                $response->withStatus(404),
                'pages/shop/404.tpl',
                ['page_title' => 'Shop Not Found']
            );
        }

        $hasStripe = !empty($shop['stripe_enabled']) && !empty($shop['stripe_public_key']) && !empty($shop['stripe_secret_key']);
        $hasPaypal = !empty($shop['paypal_enabled']) && !empty($shop['paypal_client_id']) && !empty($shop['paypal_secret']);
        $hasCod = !empty($shop['cod_enabled']);
        $hasMpesa = !empty($shop['mpesa_enabled']) && !empty($shop['mpesa_shortcode'])
            && !empty($shop['mpesa_consumer_key']) && !empty($shop['mpesa_consumer_secret'])
            && !empty($shop['mpesa_passkey']);
        $hasPesapal = !empty($shop['pesapal_enabled']) && !empty($shop['pesapal_consumer_key'])
            && !empty($shop['pesapal_consumer_secret']);

        if (!$hasStripe && !$hasPaypal && !$hasCod && !$hasMpesa && !$hasPesapal) {
            return $response->withHeader('Location', '/')->withStatus(302);
        }

        $currency = $shop['currency'] ?? App::DEFAULT_CURRENCY;
        $currencySymbol = self::CURRENCY_SYMBOLS[$currency] ?? $currency . ' ';

        $this->activateTheme($shop);

        $paletteKey = $shop['color_palette'] ?? 'default';
        $paletteCss = self::PALETTES[$paletteKey] ?? self::PALETTES['default'];

        // Pre-fill from logged-in customer account
        $shopId = (int) $shop['id'];
        $customerLoggedIn = $this->customerAuth->check($shopId);
        $checkoutCustomer = null;
        if ($customerLoggedIn) {
            $checkoutCustomer = Customer::find($this->customerAuth->customerId());
        }

        return $this->view->render($response, 'pages/shop/checkout.tpl', [
            'page_title' => 'Checkout — ' . ($shop['store_name'] ?? ''),
            'shop' => $shop,
            'currency' => $currency,
            'currency_symbol' => $currencySymbol,
            'has_stripe' => $hasStripe,
            'has_paypal' => $hasPaypal,
            'has_cod' => $hasCod,
            'has_mpesa' => $hasMpesa,
            'has_pesapal' => $hasPesapal,
            'has_payments' => true,
            'shop_theme' => $shop['shop_theme'] ?? 'classic',
            'palette_css' => $paletteCss,
            'checkout_customer' => $checkoutCustomer,
            'customer_logged_in' => $customerLoggedIn,
        ]);
    }

    /**
     * Render the order confirmation page.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @param array    $args     Route arguments.
     * @return Response
     */
    public function showConfirmation(Request $request, Response $response, array $args): Response
    {
        $subdomain = $args['subdomain'] ?? '';
        $orderNumber = $args['orderNumber'] ?? '';

        $shop = $this->userModel->findBySubdomain($subdomain);
        if (!$shop) {
            return $this->view->render(
                $response->withStatus(404),
                'pages/shop/404.tpl',
                ['page_title' => 'Shop Not Found']
            );
        }

        $order = $this->orderModel->findByOrderNumber($orderNumber);
        if (!$order || (int) $order['user_id'] !== (int) $shop['id']) {
            return $this->view->render(
                $response->withStatus(404),
                'pages/shop/404.tpl',
                ['page_title' => 'Order Not Found']
            );
        }

        $items = $this->orderItemModel->findByOrder((int) $order['id']);
        $currency = $shop['currency'] ?? App::DEFAULT_CURRENCY;
        $currencySymbol = self::CURRENCY_SYMBOLS[$currency] ?? $currency . ' ';

        $this->activateTheme($shop);

        $shopId = (int) $shop['id'];
        $hasPayments = !empty($shop['stripe_enabled']) || !empty($shop['paypal_enabled'])
            || !empty($shop['cod_enabled']) || !empty($shop['mpesa_enabled']) || !empty($shop['pesapal_enabled']);

        return $this->view->render($response, 'pages/shop/order_confirmation.tpl', [
            'page_title' => 'Order Confirmed — ' . $order['order_number'],
            'shop' => $shop,
            'order' => $order,
            'order_items' => $items,
            'currency' => $currency,
            'currency_symbol' => $currencySymbol,
            'shop_theme' => $shop['shop_theme'] ?? 'classic',
            'has_payments' => $hasPayments,
            'customer_logged_in' => $this->customerAuth->check($shopId),
        ]);
    }

    private function activateTheme(array $shop): void
    {
        $themeSlug = $shop['shop_theme'] ?? 'classic';
        $this->theme->activate($themeSlug, $this->view);
        $this->view->assignThemeVars(
            $this->theme->getStyleUrls(),
            $this->theme->getScriptUrls(),
            $this->theme->getFontLink()
        );

        $shopId = (int) $shop['id'];
        $themeOptions = $this->theme->resolveOptions($shopId, $this->themeOptionModel);

        // Free plan users always show "Powered by TinyShop"
        $plan = $this->planGuard->getUserPlan($shopId);
        if (((float) ($plan['price_monthly'] ?? 0)) === 0.0) {
            $themeOptions['show_powered_by'] = true;
        }

        $this->view->assign('theme_options', $themeOptions);
    }
}
