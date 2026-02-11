<?php

declare(strict_types=1);

/**
 * Theme Registry — defines all available storefront themes.
 *
 * Each theme has:
 *   slug        — Internal ID (used in DB, CSS class, URL)
 *   name        — Display name shown in dashboard
 *   description — One-line marketing description
 *   premium     — Whether this is a premium theme
 *   features    — Theme-specific feature flags
 *     category_filter — 'images' | 'pills' — which category filter UI to show
 *     dark_native     — true if theme is inherently dark (no dark-mode override needed)
 */

return [
    'classic' => [
        'name'        => 'Classic',
        'description' => 'Clean and versatile. Works for any shop.',
        'premium'     => false,
        'features'    => [
            'category_filter' => 'images',
            'dark_native'     => false,
        ],
    ],
    'ivory' => [
        'name'        => 'Ivory',
        'description' => 'Editorial and airy. Lets your products breathe.',
        'premium'     => false,
        'features'    => [
            'category_filter' => 'pills',
            'dark_native'     => false,
        ],
    ],
    'obsidian' => [
        'name'        => 'Obsidian',
        'description' => 'Bold and high-impact. Makes a statement.',
        'premium'     => false,
        'features'    => [
            'category_filter' => 'pills',
            'dark_native'     => false,
        ],
    ],
    'bloom' => [
        'name'        => 'Bloom',
        'description' => 'Vibrant and playful. Full of personality.',
        'premium'     => false,
        'features'    => [
            'category_filter' => 'images',
            'dark_native'     => false,
        ],
    ],
    'ember' => [
        'name'        => 'Ember',
        'description' => 'Warm and artisanal. Feels handcrafted.',
        'premium'     => false,
        'features'    => [
            'category_filter' => 'images',
            'dark_native'     => false,
        ],
    ],
    'monaco' => [
        'name'        => 'Monaco',
        'description' => 'Luxury gold and serif. Premium elegance.',
        'premium'     => true,
        'features'    => [
            'category_filter' => 'pills',
            'dark_native'     => false,
        ],
    ],
    'volt' => [
        'name'        => 'Volt',
        'description' => 'Neon glow on dark. Cyberpunk energy.',
        'premium'     => true,
        'features'    => [
            'category_filter' => 'pills',
            'dark_native'     => true,
        ],
    ],
];
