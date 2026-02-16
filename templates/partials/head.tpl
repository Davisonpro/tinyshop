<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="color-scheme" content="light dark">
<meta name="description" content="{$meta_description|default:$app_name}">
<title>{$page_title|default:$app_name}</title>
<link rel="manifest" href="/manifest.json">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
<link rel="dns-prefetch" href="https://www.googletagmanager.com">
<link rel="dns-prefetch" href="https://connect.facebook.net">
<link rel="dns-prefetch" href="https://www.google-analytics.com">
<meta name="theme-color" content="#111111" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#1C1C1E" media="(prefers-color-scheme: dark)">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="{$app_name|escape}">
<meta name="csrf-token" content="{$csrf_token|escape}">
{if !empty($google_verification)}<meta name="google-site-verification" content="{$google_verification|escape}">{/if}
{if !empty($bing_verification)}<meta name="msvalidate.01" content="{$bing_verification|escape}">{/if}
{if !empty($shop.shop_favicon)}
<link rel="apple-touch-icon" href="{$shop.shop_favicon|escape}">
<link rel="icon" href="{$shop.shop_favicon|escape}">
{elseif !empty($site_favicon)}
<link rel="apple-touch-icon" href="{$site_favicon|escape}">
<link rel="icon" href="{$site_favicon|escape}">
{else}
<link rel="apple-touch-icon" href="/public/img/icon-192.png">
<link rel="icon" type="image/png" sizes="192x192" href="/public/img/icon-192.png">
<link rel="icon" type="image/svg+xml" href="/public/img/icon.svg">
{/if}
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
<link rel="preload" href="/public/css/fontawesome.min.css?v={$asset_v}" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="/public/css/fontawesome.min.css?v={$asset_v}"></noscript>
<link rel="stylesheet" href="/public/css/app{$min}.css?v={$asset_v}">
{if !empty($shop_theme) && $shop_theme !== 'classic'}
<link rel="stylesheet" href="/public/css/themes/{$shop_theme}{$min}.css?v={$asset_v}">
{/if}
{block name="extra_css"}{/block}
{if !empty($google_analytics_id)}
<script async src="https://www.googletagmanager.com/gtag/js?id={$google_analytics_id|escape}"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){ldelim}dataLayer.push(arguments){rdelim}gtag('js',new Date());gtag('config','{$google_analytics_id|escape:"javascript"}');</script>
{/if}
{if !empty($facebook_pixel_id)}
<script>!function(f,b,e,v,n,t,s){ldelim}if(f.fbq)return;n=f.fbq=function(){ldelim}n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments){rdelim};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s){rdelim}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','{$facebook_pixel_id|escape:"javascript"}');fbq('track','PageView');</script>
<noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id={$facebook_pixel_id|escape:'url'}&ev=PageView&noscript=1"></noscript>
{/if}
