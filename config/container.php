<?php

declare(strict_types=1);

use Psr\Log\LoggerInterface;
use TinyShop\Services\Config;
use TinyShop\Services\DB;
use TinyShop\Services\Logger;
use TinyShop\Services\View;
use TinyShop\Services\Auth;
use TinyShop\Services\CustomerAuth;
use TinyShop\Services\Upload;
use TinyShop\Services\OAuth\OAuthProviderFactory;
use TinyShop\Services\Mailer;
use TinyShop\Services\Gateways\GatewayFactory;
use TinyShop\Services\Validation;
use TinyShop\Services\PlanGuard;
use TinyShop\Services\Theme;
use TinyShop\Services\HestiaCP;
use TinyShop\Services\ThemeCustomizer;
use TinyShop\Models\Setting;
use TinyShop\Models\Plan;
use TinyShop\Models\Product;
use TinyShop\Models\User;

return function (Config $config, array $dbConfig): array {
    $oauthConfig = require __DIR__ . '/oauth.php';

    return [
        Config::class => $config,

        DB::class => function () use ($dbConfig) {
            return new DB($dbConfig);
        },

        Auth::class => function () {
            return new Auth();
        },

        CustomerAuth::class => function () {
            return new CustomerAuth();
        },

        View::class => function ($container) use ($config, $oauthConfig) {
            return new View($config, $container->get(Auth::class), $container->get(Setting::class), $oauthConfig);
        },

        Upload::class => function ($container) use ($config) {
            return new Upload($config, $container->get(Setting::class));
        },

        OAuthProviderFactory::class => function () use ($oauthConfig, $config) {
            return new OAuthProviderFactory($oauthConfig, $config->url());
        },

        Mailer::class => function ($container) use ($config) {
            return new Mailer($config, $container->get(Setting::class));
        },

        Validation::class => function () {
            return new Validation();
        },

        GatewayFactory::class => function () {
            return new GatewayFactory();
        },

        ThemeCustomizer::class => function () {
            return new ThemeCustomizer();
        },

        Theme::class => function ($container) use ($config) {
            return new Theme($config, $container->get(ThemeCustomizer::class));
        },

        PlanGuard::class => function ($container) {
            return new PlanGuard(
                $container->get(Plan::class),
                $container->get(Product::class),
                $container->get(User::class)
            );
        },

        LoggerInterface::class => function () use ($config) {
            return new Logger($config->cacheDir() . '/logs');
        },

        HestiaCP::class => function ($container) {
            $hestia = [
                'api_url'      => $_ENV['HESTIA_API_URL'] ?? '',
                'api_user'     => $_ENV['HESTIA_API_USER'] ?? '',
                'api_password' => $_ENV['HESTIA_API_PASSWORD'] ?? '',
                'domain'       => $_ENV['HESTIA_DOMAIN'] ?? '',
                'user'         => $_ENV['HESTIA_USER'] ?? '',
                'server_ip'    => $_ENV['HESTIA_SERVER_IP'] ?? '',
            ];
            return new HestiaCP($hestia, $container->get(LoggerInterface::class));
        },
    ];
};
