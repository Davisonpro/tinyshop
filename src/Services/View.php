<?php

declare(strict_types=1);

namespace TinyShop\Services;

use Psr\Http\Message\ResponseInterface;
use Smarty\Smarty;
use TinyShop\Models\Setting;

final class View
{
    private readonly Smarty $smarty;
    private readonly string $baseTemplatesDir;

    public function __construct(Config $config, Auth $auth, Setting $setting)
    {
        $this->smarty = new Smarty();
        $this->baseTemplatesDir = $config->templatesDir();
        $this->smarty->setTemplateDir($this->baseTemplatesDir);
        $this->smarty->setCompileDir($config->compileDir());
        $this->smarty->setCacheDir($config->cacheDir());
        $this->smarty->setCaching(Smarty::CACHING_OFF);
        if (!$config->isDebug()) {
            $this->smarty->setCompileCheck(\Smarty\Smarty::COMPILECHECK_OFF);
        }

        // Asset versioning for cache-busting
        $cssPath = dirname($config->uploadDir()) . '/css/app.css';
        $assetVersion = substr(md5((string) @filemtime($cssPath)), 0, 8);
        $this->smarty->assign('asset_v', $assetVersion);

        // Custom modifier: format prices with commas (e.g. 1,500.00)
        $this->smarty->registerPlugin('modifier', 'format_price', function ($number, $decimals = 2) {
            return number_format((float) $number, $decimals, '.', ',');
        });

        $appUrl = $config->url();
        $scheme = str_starts_with($appUrl, 'https') ? 'https' : 'http';

        $this->smarty->assign('app_name', $config->name());
        $this->smarty->assign('logged_in', $auth->check());
        $this->smarty->assign('user_role', $auth->role()->value);
        $this->smarty->assign('base_domain', $config->baseDomain());
        $this->smarty->assign('base_url', $appUrl);
        $this->smarty->assign('scheme', $scheme);
        $this->smarty->assign('is_impersonating', $auth->isImpersonating());
        $this->smarty->assign('allow_registration', $setting->get('allow_registration', '1') === '1');
        $this->smarty->assign('csrf_token', $_SESSION['_csrf_token'] ?? '');

        // Site branding
        $this->smarty->assign('site_logo', $setting->get('site_logo', ''));
        $this->smarty->assign('site_favicon', $setting->get('site_favicon', ''));

        // SEO & Analytics
        $this->smarty->assign('google_verification', $setting->get('google_verification', ''));
        $this->smarty->assign('bing_verification', $setting->get('bing_verification', ''));
        $this->smarty->assign('google_analytics_id', $setting->get('google_analytics_id', ''));
        $this->smarty->assign('facebook_pixel_id', $setting->get('facebook_pixel_id', ''));
    }

    /**
     * Set the active theme — prepends theme-specific template directory.
     * Smarty checks theme dir first, falls back to base templates dir.
     */
    public function setTheme(string $theme): void
    {
        if ($theme && $theme !== 'classic') {
            $themeDir = $this->baseTemplatesDir . '/themes/' . $theme;
            if (is_dir($themeDir)) {
                $this->smarty->setTemplateDir([
                    'theme'   => $themeDir,
                    'default' => $this->baseTemplatesDir,
                ]);
            }
        }
    }

    public function render(ResponseInterface $response, string $template, array $data = []): ResponseInterface
    {
        foreach ($data as $key => $value) {
            $this->smarty->assign($key, $value);
        }

        $html = $this->smarty->fetch($template);
        $response->getBody()->write($html);

        return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
    }
}
