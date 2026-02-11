<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="color-scheme" content="light dark">
<meta name="description" content="{$meta_description|default:'Create your mobile shop in minutes. Share anywhere.'}">
<title>{$page_title|default:'TinyShop'} — {$app_name}</title>
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#111111" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#1C1C1E" media="(prefers-color-scheme: dark)">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="TinyShop">
<meta name="csrf-token" content="{$csrf_token|escape}">
<link rel="apple-touch-icon" href="/public/img/icon-192.png">
<link rel="icon" type="image/png" sizes="192x192" href="/public/img/icon-192.png">
<link rel="icon" type="image/svg+xml" href="/public/img/icon.svg">
{if !empty($og_title)}
<meta property="og:title" content="{$og_title|escape}">
<meta property="og:description" content="{$og_description|escape}">
<meta property="og:type" content="{$og_type|default:'website'}">
<meta property="og:url" content="{$base_url}{$og_url|escape}">
{if $og_image}<meta property="og:image" content="{$og_image|escape}">{/if}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{$og_title|escape}">
<meta name="twitter:description" content="{$og_description|escape}">
{if $og_image}<meta name="twitter:image" content="{$og_image|escape}">{/if}
{/if}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/public/css/app.css">
{if !empty($shop_theme) && $shop_theme !== 'classic'}
<link rel="stylesheet" href="/public/css/themes/{$shop_theme}.css">
{/if}
{block name="extra_css"}{/block}
