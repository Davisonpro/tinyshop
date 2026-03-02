<?php

declare(strict_types=1);

namespace TinyShop\Services;

/**
 * Theme customizer registry.
 *
 * @since 1.0.0
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
     * Register a section.
     *
     * @since 1.0.0
     *
     * @param string $id   Section ID.
     * @param array  $args Section options.
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
     * Register a setting.
     *
     * @since 1.0.0
     *
     * @param string $id   Setting ID.
     * @param array  $args Setting options.
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
     * Register a control.
     *
     * @since 1.0.0
     *
     * @param string $id   Control ID (matches setting ID).
     * @param array  $args Control options.
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

    /** @return array<string, array> Sections sorted by priority. */
    public function getSections(): array
    {
        $sections = $this->sections;
        uasort($sections, static fn(array $a, array $b): int => $a['priority'] <=> $b['priority']);
        return $sections;
    }

    /** @return array<string, array> Controls for a section, sorted by priority. */
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
     * Get the full schema for the design dashboard.
     *
     * @since 1.0.0
     *
     * @return list<array> Sections with nested controls.
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

    /** @return array<string, mixed> Default values for all settings. */
    public function getDefaults(): array
    {
        $defaults = [];
        foreach ($this->settings as $id => $setting) {
            $defaults[$id] = $setting['default'];
        }
        return $defaults;
    }

    /** @return array<string, array> All registered settings. */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * Merge saved options with defaults.
     *
     * @since 1.0.0
     *
     * @param  array<string, string|null> $saved Raw values from DB.
     * @return array<string, mixed> Resolved options.
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
     * Sanitize a setting value before saving.
     *
     * @since 1.0.0
     *
     * @param  string $settingId Setting ID.
     * @param  mixed  $value     Raw value.
     * @return string|null Sanitized value, or null if setting not found.
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

    /** Check if a setting is registered. */
    public function hasSetting(string $id): bool
    {
        return isset($this->settings[$id]);
    }

    /** Check if a control requires a paid plan. */
    public function isProControl(string $id): bool
    {
        return !empty($this->controls[$id]['pro']);
    }

    /** Clear all registrations. */
    public function reset(): void
    {
        $this->sections = [];
        $this->settings = [];
        $this->controls = [];
    }
}
