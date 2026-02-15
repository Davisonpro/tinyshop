<?php

declare(strict_types=1);

/**
 * Theme Registry — defines all available storefront themes.
 *
 * Each theme has:
 *   slug        — Internal ID (used in DB, CSS class, URL)
 *   name        — Display name shown in dashboard
 *   description — One-line marketing description
 *   features    — Theme-specific feature flags
 *     category_style  — Visual style of category navigation
 *     dark_native     — true if theme is inherently dark (no dark-mode override needed)
 */

return [
    'classic' => [
        'name'        => 'Classic',
        'description' => 'Clean and versatile. Works for any shop.',
        'features'    => [
            'category_style' => 'image-cards',
            'dark_native'    => false,
        ],
    ],
    'bloom' => [
        'name'        => 'Bloom',
        'description' => 'Warm and refined. Clean golden accents.',
        'features'    => [
            'category_style' => 'circle-thumbnails',
            'dark_native'    => false,
        ],
    ],
    'ember' => [
        'name'        => 'Ember',
        'description' => 'Editorial lookbook. Serif typography, curated feel.',
        'features'    => [
            'category_style' => 'underline-serif',
            'dark_native'    => false,
        ],
    ],
    'ivory' => [
        'name'        => 'Ivory',
        'description' => 'Ultra-minimal gallery. Lets your products breathe.',
        'features'    => [
            'category_style' => 'outlined-pills',
            'dark_native'    => false,
        ],
    ],
    'monaco' => [
        'name'        => 'Monaco',
        'description' => 'Luxury gold and serif. Elegant sophistication.',
        'features'    => [
            'category_style' => 'gold-circles',
            'dark_native'    => false,
        ],
    ],
    'obsidian' => [
        'name'        => 'Obsidian',
        'description' => 'Bold streetwear hype. Makes a statement.',
        'features'    => [
            'category_style' => 'filled-squares',
            'dark_native'    => false,
        ],
    ],
    'volt' => [
        'name'        => 'Volt',
        'description' => 'Neon glow on dark. Cyberpunk energy.',
        'features'    => [
            'category_style' => 'outlined-mono',
            'dark_native'    => true,
        ],
    ],
    'halloween' => [
        'name'        => 'Halloween',
        'description' => 'Playful dark. Lime and purple dual accents.',
        'features'    => [
            'category_style' => 'rounded-colorful',
            'dark_native'    => true,
        ],
    ],
];
