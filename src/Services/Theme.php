<?php

declare(strict_types=1);

namespace TinyShop\Services;

final class Theme
{
    private string $themesDir;
    private ?array $activeManifest = null;
    private ?string $activeTheme = null;

    /** @var array<string, array> Cache of loaded manifests */
    private array $manifestCache = [];

    public function __construct(private readonly Config $config)
    {
        $this->themesDir = dirname(__DIR__, 2) . '/themes';
    }

    /**
     * Activate a theme for the current request.
     * Loads manifest, registers template dir, runs functions.php, fires hook.
     */
    public function activate(string $themeSlug, View $view): void
    {
        $manifest = $this->loadManifest($themeSlug);

        if ($manifest === null) {
            if ($themeSlug !== 'classic') {
                $this->activate('classic', $view);
                return;
            }
            return;
        }

        $this->activeTheme = $themeSlug;
        $this->activeManifest = $manifest;

        $themeTemplatesDir = $this->themesDir . '/' . $themeSlug . '/templates';
        $view->setThemeDir($themeTemplatesDir, $themeSlug);

        $functionsFile = $this->themesDir . '/' . $themeSlug . '/functions.php';
        if (file_exists($functionsFile)) {
            require_once $functionsFile;
        }

        Hooks::doAction('theme.activated', $themeSlug, $manifest);
    }

    /**
     * Load and validate a theme.json manifest.
     */
    public function loadManifest(string $themeSlug): ?array
    {
        if (!preg_match('/^[a-z0-9-]+$/', $themeSlug)) {
            return null;
        }

        if (isset($this->manifestCache[$themeSlug])) {
            return $this->manifestCache[$themeSlug];
        }

        $manifestPath = $this->themesDir . '/' . $themeSlug . '/theme.json';
        if (!file_exists($manifestPath)) {
            return null;
        }

        $json = file_get_contents($manifestPath);
        $manifest = json_decode($json, true);
        if (!is_array($manifest) || empty($manifest['name']) || empty($manifest['slug'])) {
            return null;
        }

        $this->manifestCache[$themeSlug] = $manifest;
        return $manifest;
    }

    public function activeSlug(): ?string
    {
        return $this->activeTheme;
    }

    public function activeManifest(): ?array
    {
        return $this->activeManifest;
    }

    /**
     * Get CSS URLs for the active theme.
     * @return string[]
     */
    public function getStyleUrls(): array
    {
        if (!$this->activeManifest || empty($this->activeManifest['styles'])) {
            return [];
        }

        $urls = [];
        foreach ($this->activeManifest['styles'] as $path) {
            $urls[] = '/themes/' . $this->activeTheme . '/' . $path;
        }
        return $urls;
    }

    /**
     * Get JS URLs for the active theme.
     * @return string[]
     */
    public function getScriptUrls(): array
    {
        if (!$this->activeManifest || empty($this->activeManifest['scripts'])) {
            return [];
        }

        $urls = [];
        foreach ($this->activeManifest['scripts'] as $path) {
            $urls[] = '/themes/' . $this->activeTheme . '/' . $path;
        }
        return $urls;
    }

    /**
     * Get Google Fonts <link> URL from theme manifest.
     */
    public function getFontLink(): ?string
    {
        if (!$this->activeManifest || empty($this->activeManifest['fonts'])) {
            return null;
        }

        $families = [];
        foreach ($this->activeManifest['fonts'] as $font) {
            if (($font['provider'] ?? 'google') !== 'google') {
                continue;
            }
            $family = str_replace(' ', '+', $font['family']);
            $weights = implode(';', $font['weights'] ?? [400]);
            $families[] = "family={$family}:wght@{$weights}";
        }

        return $families
            ? 'https://fonts.googleapis.com/css2?' . implode('&', $families) . '&display=swap'
            : null;
    }

    /**
     * Check if the active theme supports a feature.
     */
    public function supports(string $feature): bool
    {
        return (bool) ($this->activeManifest['supports'][$feature] ?? false);
    }

    /**
     * Get a theme setting value.
     */
    public function setting(string $key, mixed $default = null): mixed
    {
        return $this->activeManifest['settings'][$key] ?? $default;
    }

    /**
     * List all available themes (scans themes directory).
     * @return array[]
     */
    public function listAvailable(): array
    {
        $themes = [];
        if (!is_dir($this->themesDir)) {
            return $themes;
        }

        $dirs = glob($this->themesDir . '/*/theme.json');
        foreach ($dirs as $manifestPath) {
            $slug = basename(dirname($manifestPath));
            $manifest = $this->loadManifest($slug);
            if ($manifest) {
                $themes[] = $manifest;
            }
        }
        return $themes;
    }

    public function themesDir(): string
    {
        return $this->themesDir;
    }
}
