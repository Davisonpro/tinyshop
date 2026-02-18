<!DOCTYPE html>
<html lang="en">
<head>
    {block name="head"}
        {include file="partials/head.tpl"}
    {/block}
</head>
<body class="{block name='body_class'}{/block}">

    <a href="#main-content" class="skip-to-content">Skip to content</a>

    {block name="body"}{/block}

    {include file="partials/shop_cta.tpl"}
    {include file="partials/toast.tpl"}
    {include file="partials/scripts.tpl"}
    {if !empty($theme_scripts)}
    {foreach $theme_scripts as $tjs}
    <script src="{$tjs|escape}?v={$asset_v}" defer></script>
    {/foreach}
    {/if}
    {block name="page_scripts"}{/block}
</body>
</html>
