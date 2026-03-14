<?php

declare(strict_types=1);

namespace TinyShop\Services;

use Psr\Http\Message\ResponseInterface;
use Smarty\Smarty;
use TinyShop\Models\Setting;

/**
 * View service.
 *
 * @since 1.0.0
 */
final class View
{
    /** Cache-busting version appended to CSS/JS URLs. */
    public const ASSET_VERSION = '1.0.183';

    private readonly Smarty $smarty;
    private readonly string $baseTemplatesDir;
    private readonly bool $minifyHtml;

    /**
     * @param array<string, array{enabled: bool}> $oauthConfig OAuth provider flags.
     */
    public function __construct(Config $config, Auth $auth, Setting $setting, array $oauthConfig = [])
    {
        $this->smarty = new Smarty();
        $this->baseTemplatesDir = $config->templatesDir();
        $this->minifyHtml = !$config->isDebug();
        $this->smarty->setTemplateDir($this->baseTemplatesDir);
        $this->smarty->setCompileDir($config->compileDir());
        $this->smarty->setCacheDir($config->cacheDir());
        $this->smarty->setCaching(Smarty::CACHING_OFF);
        $this->smarty->setCompileCheck(Smarty::COMPILECHECK_ON);

        $this->smarty->assign('asset_v', self::ASSET_VERSION);

        // Minified asset suffix: ".min" in production, "" in debug
        $this->smarty->assign('min', $config->isDebug() ? '' : '.min');

        // Custom modifier: format prices with commas (e.g. 1,500.00)
        $this->smarty->registerPlugin('modifier', 'format_price', function ($number, $decimals = 2) {
            return number_format((float) $number, $decimals, '.', ',');
        });

        // Custom modifier: check if a date is within N days (default 14)
        $this->smarty->registerPlugin('modifier', 'is_recent', function ($dateStr, int $days = 14): bool {
            if (!$dateStr) {
                return false;
            }
            return strtotime((string) $dateStr) > strtotime("-{$days} days");
        });

        // {hook name="..."} — execute theme/addon action hooks and return output
        $this->smarty->registerPlugin('function', 'hook', function (array $params): string {
            $name = $params['name'] ?? '';
            if ($name === '') {
                return '';
            }
            ob_start();
            Hooks::doAction($name);
            return ob_get_clean() ?: '';
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

        // OAuth providers — flags injected via container from config/oauth.php
        $this->smarty->assign('oauth_google', $oauthConfig['google']['enabled'] ?? false);
        $this->smarty->assign('oauth_instagram', $oauthConfig['instagram']['enabled'] ?? false);
        $this->smarty->assign('oauth_tiktok', $oauthConfig['tiktok']['enabled'] ?? false);

        // SEO & Analytics
        $this->smarty->assign('google_verification', $setting->get('google_verification', ''));
        $this->smarty->assign('bing_verification', $setting->get('bing_verification', ''));
        $this->smarty->assign('google_analytics_id', $setting->get('google_analytics_id', ''));
        $this->smarty->assign('facebook_pixel_id', $setting->get('facebook_pixel_id', ''));

        // Canonical URL — strip query string, use current request path
        // Shop pages (/~shop/) set their own canonical via subdomain in ShopController
        $requestUri = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
        if (!str_starts_with($requestUri, '/~shop/')) {
            $this->smarty->assign('canonical_url', $appUrl . rtrim($requestUri, '/'));
        }
    }

    /**
     * Set the theme template directory.
     *
     * @since 1.0.0
     *
     * @param string $dir       Absolute path to theme templates.
     * @param string $compileId Compile ID to isolate cached templates per theme.
     */
    public function setThemeDir(string $dir, string $compileId = ''): void
    {
        if (is_dir($dir)) {
            $this->smarty->setTemplateDir([
                'theme'   => $dir,
                'default' => $this->baseTemplatesDir,
            ]);
            if ($compileId !== '') {
                $this->smarty->setCompileId($compileId);
            }
        }
    }

    /**
     * Assign theme asset URLs to templates.
     *
     * @since 1.0.0
     *
     * @param string[]    $styleUrls  Theme CSS URLs.
     * @param string[]    $scriptUrls Theme JS URLs.
     * @param string|null $fontLink   External font link tag.
     */
    public function assignThemeVars(array $styleUrls, array $scriptUrls, ?string $fontLink): void
    {
        $this->smarty->assign('theme_styles', $styleUrls);
        $this->smarty->assign('theme_scripts', $scriptUrls);
        $this->smarty->assign('theme_font_link', $fontLink);
    }

    /** Assign a global template variable. */
    public function assign(string $key, mixed $value): void
    {
        $this->smarty->assign($key, $value);
    }

    /**
     * Render a template into the response.
     *
     * @since 1.0.0
     *
     * @param ResponseInterface    $response PSR-7 response.
     * @param string               $template Template path.
     * @param array<string, mixed> $data     Template variables.
     * @return ResponseInterface
     */
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

        return $response
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    /**
     * Render a template to a string.
     *
     * @since 1.0.0
     *
     * @param string               $template Template path.
     * @param array<string, mixed> $data     Template variables.
     * @return string Rendered HTML.
     */
    public function renderFragment(string $template, array $data = []): string
    {
        foreach ($data as $key => $value) {
            $this->smarty->assign($key, $value);
        }

        return $this->smarty->fetch($template);
    }

    /** Minify HTML while preserving pre/textarea/script/style blocks. */
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
