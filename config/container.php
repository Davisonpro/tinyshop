<?php

declare(strict_types=1);

use Psr\Log\LoggerInterface;
use TinyShop\Services\Config;
use TinyShop\Services\DB;
use TinyShop\Services\Logger;
use TinyShop\Services\View;
use TinyShop\Services\Auth;
use TinyShop\Services\Upload;
use TinyShop\Services\OAuth;
use TinyShop\Services\Mailer;
use TinyShop\Services\Validation;
use TinyShop\Models\Setting;

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

        View::class => function ($container) use ($config) {
            return new View($config, $container->get(Auth::class), $container->get(Setting::class));
        },

        Upload::class => function () use ($config) {
            return new Upload($config);
        },

        OAuth::class => function () use ($oauthConfig, $config) {
            return new OAuth($oauthConfig, $config->url());
        },

        Mailer::class => function ($container) use ($config) {
            return new Mailer($config, $container->get(Setting::class));
        },

        Validation::class => function () {
            return new Validation();
        },

        LoggerInterface::class => function () use ($config) {
            return new Logger($config->cacheDir() . '/logs');
        },
    ];
};
