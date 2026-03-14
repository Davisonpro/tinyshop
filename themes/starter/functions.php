<?php

declare(strict_types=1);

/**
 * Starter Theme — Customizer registration & hooks.
 *
 * This file is auto-loaded when the Starter theme is activated.
 * The $customizer variable (ThemeCustomizer) is available for registering
 * sections, settings, and controls.
 */

use TinyShop\Services\Hooks;
use TinyShop\Services\ThemeCustomizer;

/** @var ThemeCustomizer $customizer */

// ── Hero Slider ──────────────────────────────────────────────

$customizer->addSection('hero_slider', [
    'title'       => 'Hero Slider',
    'description' => 'Add banner slides to the top of your shop page',
    'icon'        => 'fa-solid fa-images',
    'priority'    => 10,
]);

$customizer->addSetting('hero_slides_enabled', [
    'default' => false,
    'type'    => 'boolean',
]);
$customizer->addControl('hero_slides_enabled', [
    'section'     => 'hero_slider',
    'label'       => 'Show hero slider',
    'description' => 'Display banner slides at the top of your shop',
    'type'        => 'toggle',
    'priority'    => 10,
]);

$customizer->addSetting('hero_slides_items', [
    'default' => '[]',
    'type'    => 'json',
]);
$customizer->addControl('hero_slides_items', [
    'section'  => 'hero_slider',
    'label'    => 'Slides',
    'type'     => 'repeater',
    'max'      => 6,
    'fields'   => [
        'image'    => ['label' => 'Banner image', 'type' => 'image'],
        'title'    => ['label' => 'Heading', 'type' => 'text', 'placeholder' => 'New Arrivals'],
        'subtitle' => ['label' => 'Subheading', 'type' => 'text', 'placeholder' => 'Check out our latest products'],
        'link_url' => ['label' => 'Link URL', 'type' => 'text', 'placeholder' => '/collections/new-arrivals'],
        'link_text' => ['label' => 'Button text', 'type' => 'text', 'placeholder' => 'Shop Now'],
    ],
    'priority' => 20,
]);

// ── Trust Badges ──────────────────────────────────────────────

$customizer->addSection('trust_badges', [
    'title'       => 'Trust Badges',
    'description' => 'Add badges below the hero slider to build customer trust',
    'icon'        => 'fa-solid fa-shield-halved',
    'priority'    => 20,
]);

$customizer->addSetting('trust_badges_enabled', [
    'default' => false,
    'type'    => 'boolean',
]);
$customizer->addControl('trust_badges_enabled', [
    'section'     => 'trust_badges',
    'label'       => 'Show trust badges',
    'description' => 'Display trust badges on your shop homepage',
    'type'        => 'toggle',
    'priority'    => 10,
]);

$customizer->addSetting('trust_badges_items', [
    'default' => '[]',
    'type'    => 'json',
]);
$customizer->addControl('trust_badges_items', [
    'section' => 'trust_badges',
    'label'   => 'Badges',
    'type'    => 'repeater',
    'max'     => 4,
    'fields'  => [
        'icon'        => ['label' => 'Icon', 'type' => 'icon'],
        'title'       => ['label' => 'Title', 'type' => 'text', 'placeholder' => 'Free Shipping'],
        'description' => ['label' => 'Description', 'type' => 'text', 'placeholder' => 'On orders over $50'],
    ],
    'priority' => 20,
]);

// ── Collection Banners ───────────────────────────────────────

$customizer->addSection('collection_banners', [
    'title'       => 'Collection Banners',
    'description' => 'Add promotional banners to your shop homepage',
    'icon'        => 'fa-solid fa-rectangle-ad',
    'priority'    => 25,
]);

$customizer->addSetting('collection_banners_enabled', [
    'default' => false,
    'type'    => 'boolean',
]);
$customizer->addControl('collection_banners_enabled', [
    'section'     => 'collection_banners',
    'label'       => 'Show collection banners',
    'description' => 'Display promotional banners on your shop homepage',
    'type'        => 'toggle',
    'priority'    => 10,
]);

$customizer->addSetting('collection_banners_layout', [
    'default' => '2-col',
    'type'    => 'string',
]);
$customizer->addControl('collection_banners_layout', [
    'section' => 'collection_banners',
    'label'   => 'Layout',
    'type'    => 'radio',
    'choices' => [
        '2-col'      => '2 per row',
        '3-col'      => '3 per row',
        '4-col'      => '4 per row',
        'full-width' => 'Full width',
    ],
    'priority' => 15,
]);

$customizer->addSetting('collection_banners_size', [
    'default' => 'medium',
    'type'    => 'string',
]);
$customizer->addControl('collection_banners_size', [
    'section' => 'collection_banners',
    'label'   => 'Banner size',
    'type'    => 'radio',
    'choices' => [
        'small'  => 'Short',
        'medium' => 'Medium',
        'tall'   => 'Tall',
    ],
    'priority' => 16,
]);

$customizer->addSetting('collection_banners_text_position', [
    'default' => 'bottom-left',
    'type'    => 'string',
]);
$customizer->addControl('collection_banners_text_position', [
    'section' => 'collection_banners',
    'label'   => 'Text position',
    'type'    => 'radio',
    'choices' => [
        'bottom-left'   => 'Bottom left',
        'bottom-center' => 'Bottom center',
        'center'        => 'Center',
        'top-left'      => 'Top left',
    ],
    'priority' => 17,
]);

$customizer->addSetting('collection_banners_items', [
    'default' => '[]',
    'type'    => 'json',
]);
$customizer->addControl('collection_banners_items', [
    'section'  => 'collection_banners',
    'label'    => 'Banners',
    'type'     => 'repeater',
    'max'      => 8,
    'fields'   => [
        'image'       => ['label' => 'Banner image', 'type' => 'image'],
        'title'       => ['label' => 'Title', 'type' => 'text', 'placeholder' => 'New Collection'],
        'description' => ['label' => 'Description', 'type' => 'text', 'placeholder' => 'Explore our latest arrivals'],
        'link_url'    => ['label' => 'Link', 'type' => 'text', 'placeholder' => '/collections/new-arrivals'],
        'link_text'   => ['label' => 'Button text', 'type' => 'text', 'placeholder' => 'Shop Collection'],
    ],
    'priority' => 20,
]);

// ── Footer ────────────────────────────────────────────────────

$customizer->addSection('footer_settings', [
    'title'       => 'Footer',
    'description' => 'Customize what appears in your shop footer',
    'icon'        => 'fa-solid fa-table-columns',
    'priority'    => 30,
]);

$customizer->addSetting('show_powered_by', [
    'default' => true,
    'type'    => 'boolean',
]);
$customizer->addControl('show_powered_by', [
    'section'     => 'footer_settings',
    'label'       => 'Show "Powered by TinyShop"',
    'description' => 'Display the TinyShop branding in your footer',
    'type'        => 'toggle',
    'priority'    => 10,
    'pro'         => true,
]);
