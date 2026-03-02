<?php

declare(strict_types=1);

namespace TinyShop\Services;

use TinyShop\Models\ThemeOption;

/**
 * Theme service.
 *
 * @since 1.0.0
 */
final class Theme
{
    private string $themesDir;
    private ?array $activeManifest = null;
    private ?string $activeTheme = null;

    /** @var array<string, array> Cache of loaded manifests */
    private array $manifestCache = [];

    public function __construct(
        private readonly Config $config,
        private readonly ThemeCustomizer $customizer
    ) {
        $this->themesDir = dirname(__DIR__, 2) . '/themes';
    }

    /**
     * Activate a theme for the current request.
     *
     * @since 1.0.0
     *
     * @param string $themeSlug Theme slug.
     * @param View   $view      View service to register templates with.
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
        $this->customizer->reset();

        $themeTemplatesDir = $this->themesDir . '/' . $themeSlug . '/templates';
        $view->setThemeDir($themeTemplatesDir, $themeSlug);

        // Make $customizer available to the theme's functions.php
        $customizer = $this->customizer;
        $functionsFile = $this->themesDir . '/' . $themeSlug . '/functions.php';
        if (file_exists($functionsFile)) {
            require_once $functionsFile;
        }

        Hooks::doAction('theme.activated', $themeSlug, $manifest);
    }

    /**
     * Load and validate a theme.json manifest.
     *
     * @since 1.0.0
     *
     * @param  string $themeSlug Theme slug.
     * @return array|null Parsed manifest, or null if invalid.
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

    /** Get the active theme slug. */
    public function activeSlug(): ?string
    {
        return $this->activeTheme;
    }

    /** Get the active theme's manifest. */
    public function activeManifest(): ?array
    {
        return $this->activeManifest;
    }

    /** @return string[] CSS URLs for the active theme. */
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

    /** @return string[] JS URLs for the active theme. */
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

    /** Get the Google Fonts link URL, or null. */
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

    /** Check if the active theme supports a feature. */
    public function supports(string $feature): bool
    {
        return (bool) ($this->activeManifest['supports'][$feature] ?? false);
    }

    /** Get a setting from the active theme manifest. */
    public function setting(string $key, mixed $default = null): mixed
    {
        return $this->activeManifest['settings'][$key] ?? $default;
    }

    /**
     * List all available themes.
     *
     * @since 1.0.0
     *
     * @return array[] Theme manifests.
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

    /** Get the theme customizer. */
    public function getCustomizer(): ThemeCustomizer
    {
        return $this->customizer;
    }

    /**
     * Resolve theme options for a seller, merging saved values with defaults.
     *
     * @since 1.0.0
     *
     * @param  int         $userId     Seller ID.
     * @param  ThemeOption $themeOption Theme option model.
     * @return array<string, mixed> Resolved options.
     */
    public function resolveOptions(int $userId, ThemeOption $themeOption): array
    {
        $themeSlug = $this->activeTheme ?? 'classic';
        $saved = $themeOption->getAll($userId, $themeSlug);
        return $this->customizer->resolveOptions($saved);
    }

    /** Get the themes directory path. */
    public function themesDir(): string
    {
        return $this->themesDir;
    }
}
