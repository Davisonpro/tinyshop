<!DOCTYPE html>
<html lang="en">
<head>
    {block name="head"}
        {include file="partials/head.tpl"}
    {/block}
</head>
<body class="{block name='body_class'}{/block}{if !empty($shop_theme) && $shop_theme !== 'classic'} theme-{$shop_theme}{/if}">

    {block name="body"}{/block}

    {include file="partials/shop_cta.tpl"}
    {include file="partials/toast.tpl"}
    {include file="partials/scripts.tpl"}
    {block name="page_scripts"}{/block}
    {literal}<script>$(document).trigger('page:init');</script>{/literal}
</body>
</html>
