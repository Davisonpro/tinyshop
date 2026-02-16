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
    private readonly bool $minifyHtml;

    public function __construct(Config $config, Auth $auth, Setting $setting)
    {
        $this->smarty = new Smarty();
        $this->baseTemplatesDir = $config->templatesDir();
        $this->minifyHtml = !$config->isDebug();
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

        // Minified asset suffix: ".min" in production, "" in debug
        $this->smarty->assign('min', $config->isDebug() ? '' : '.min');

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

        // Ensure session exists for CSRF token (lazy start for non-page routes)
        Auth::ensureSession();
        $this->smarty->assign('csrf_token', $_SESSION['_csrf_token'] ?? '');

        // Site branding
        $this->smarty->assign('site_logo', $setting->get('site_logo', ''));
        $this->smarty->assign('site_favicon', $setting->get('site_favicon', ''));
        $this->smarty->assign('support_email', $setting->get('support_email', ''));

        // OAuth providers
        $this->smarty->assign('oauth_google', filter_var($_ENV['OAUTH_GOOGLE_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN));
        $this->smarty->assign('oauth_instagram', filter_var($_ENV['OAUTH_INSTAGRAM_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN));
        $this->smarty->assign('oauth_tiktok', filter_var($_ENV['OAUTH_TIKTOK_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN));

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

        if ($this->minifyHtml) {
            $html = $this->minifyHtml($html);
        }

        $response->getBody()->write($html);

        return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    private function minifyHtml(string $html): string
    {
        // Preserve <pre>, <textarea>, <script>, <style> content
        $preserved = [];
        $html = preg_replace_callback(
            '#(<(?:pre|textarea|script|style)\b[^>]*>)(.*?)(</(?:pre|textarea|script|style)>)#si',
            function ($m) use (&$preserved) {
                $key = '<!--PRESERVED_' . count($preserved) . '-->';
                $preserved[$key] = $m[0];
                return $key;
            },
            $html
        );

        // Remove HTML comments (except preserved placeholders and IE conditionals)
        $html = preg_replace('/<!--(?!PRESERVED_|\\[if).*?-->/s', '', $html);
        // Collapse whitespace
        $html = preg_replace('/\s+/', ' ', $html);
        // Remove spaces around tags
        $html = preg_replace('/>\s+</', '><', $html);
        // Trim
        $html = trim($html);

        // Restore preserved blocks
        $html = strtr($html, $preserved);

        return $html;
    }
}
