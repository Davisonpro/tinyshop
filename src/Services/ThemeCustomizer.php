<?php

declare(strict_types=1);

namespace TinyShop\Services;

/**
 * WordPress Customizer API equivalent.
 *
 * Themes register sections, settings, and controls in their functions.php.
 * The platform reads the schema to render UI and validate saves.
 */
final class ThemeCustomizer
{
    /** @var array<string, array> */
    private array $sections = [];

    /** @var array<string, array> */
    private array $settings = [];

    /** @var array<string, array> */
    private array $controls = [];

    /**
     * Register a customizer section.
     *
     * @param array{title: string, description?: string, icon?: string, priority?: int} $args
     */
    public function addSection(string $id, array $args): void
    {
        $this->sections[$id] = array_merge([
            'title'       => '',
            'description' => '',
            'icon'        => 'fa-solid fa-palette',
            'priority'    => 100,
        ], $args, ['id' => $id]);
    }

    /**
     * Register a customizer setting.
     *
     * @param array{default?: mixed, type?: string, sanitize_callback?: callable|null} $args
     */
    public function addSetting(string $id, array $args): void
    {
        $this->settings[$id] = array_merge([
            'default'           => '',
            'type'              => 'string', // string, boolean, number, json
            'sanitize_callback' => null,
        ], $args, ['id' => $id]);
    }

    /**
     * Register a customizer control.
     *
     * @param array{section: string, label: string, description?: string, type?: string, choices?: array, fields?: array, max?: int, input_attrs?: array, priority?: int} $args
     */
    public function addControl(string $id, array $args): void
    {
        $this->controls[$id] = array_merge([
            'section'     => '',
            'label'       => '',
            'description' => '',
            'type'        => 'text',
            'choices'     => [],
            'fields'      => [],
            'max'         => 0,
            'input_attrs' => [],
            'priority'    => 100,
        ], $args, ['id' => $id, 'setting' => $id]);
    }

    /**
     * Get all sections sorted by priority.
     *
     * @return array<string, array>
     */
    public function getSections(): array
    {
        $sections = $this->sections;
        uasort($sections, static fn(array $a, array $b): int => $a['priority'] <=> $b['priority']);
        return $sections;
    }

    /**
     * Get controls for a specific section, sorted by priority.
     *
     * @return array<string, array>
     */
    public function getControlsForSection(string $sectionId): array
    {
        $controls = array_filter(
            $this->controls,
            static fn(array $c): bool => $c['section'] === $sectionId
        );
        uasort($controls, static fn(array $a, array $b): int => $a['priority'] <=> $b['priority']);
        return $controls;
    }

    /**
     * Get the full schema: sections with nested controls and defaults.
     * Used by the design dashboard to dynamically render the UI.
     *
     * @return list<array>
     */
    public function getSchema(): array
    {
        $schema = [];
        foreach ($this->getSections() as $section) {
            $sectionData = $section;
            $sectionData['controls'] = [];

            foreach ($this->getControlsForSection($section['id']) as $control) {
                $setting = $this->settings[$control['setting']] ?? null;
                $control['default'] = $setting['default'] ?? '';
                $control['value_type'] = $setting['type'] ?? 'string';
                $sectionData['controls'][] = $control;
            }

            $schema[] = $sectionData;
        }
        return $schema;
    }

    /**
     * Get default values for all registered settings.
     *
     * @return array<string, mixed>
     */
    public function getDefaults(): array
    {
        $defaults = [];
        foreach ($this->settings as $id => $setting) {
            $defaults[$id] = $setting['default'];
        }
        return $defaults;
    }

    /**
     * Get all registered settings.
     *
     * @return array<string, array>
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * Merge saved options with defaults and decode JSON types.
     *
     * @param array<string, string|null> $saved Raw values from theme_options table
     * @return array<string, mixed>
     */
    public function resolveOptions(array $saved): array
    {
        $defaults = $this->getDefaults();
        $merged = array_merge($defaults, $saved);

        foreach ($this->settings as $id => $setting) {
            if (!array_key_exists($id, $merged)) {
                continue;
            }

            $value = $merged[$id];

            match ($setting['type']) {
                'boolean' => $merged[$id] = filter_var($value, FILTER_VALIDATE_BOOLEAN),
                'number'  => $merged[$id] = is_numeric($value) ? (float) $value : ($setting['default'] ?? 0),
                'json'    => $merged[$id] = is_string($value) ? (json_decode($value, true) ?: []) : (is_array($value) ? $value : []),
                default   => null,
            };
        }

        return $merged;
    }

    /**
     * Sanitize a value for a registered setting before saving.
     */
    public function sanitizeValue(string $settingId, mixed $value): ?string
    {
        $setting = $this->settings[$settingId] ?? null;
        if ($setting === null) {
            return null;
        }

        if ($setting['sanitize_callback'] !== null && is_callable($setting['sanitize_callback'])) {
            $value = ($setting['sanitize_callback'])($value);
        }

        return match ($setting['type']) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
            'number'  => is_numeric($value) ? (string) $value : (string) ($setting['default'] ?? '0'),
            'json'    => is_string($value) ? $value : json_encode($value, JSON_THROW_ON_ERROR),
            default   => is_string($value) ? $value : (string) $value,
        };
    }

    /**
     * Check if a setting ID is registered.
     */
    public function hasSetting(string $id): bool
    {
        return isset($this->settings[$id]);
    }

    /**
     * Check if a control is marked as pro-only (requires paid plan).
     */
    public function isProControl(string $id): bool
    {
        return !empty($this->controls[$id]['pro']);
    }

    /**
     * Clear all registrations. Used for per-request isolation.
     */
    public function reset(): void
    {
        $this->sections = [];
        $this->settings = [];
        $this->controls = [];
    }
}
