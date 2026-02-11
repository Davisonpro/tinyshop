<!DOCTYPE html>
<html lang="en">
<head>
    {block name="head"}
        {include file="partials/head.tpl"}
    {/block}
</head>
<body class="{block name='body_class'}{/block}{if !empty($shop_theme) && $shop_theme !== 'classic'} theme-{$shop_theme}{/if}">

    {block name="body"}{/block}

    {include file="partials/toast.tpl"}
    {include file="partials/scripts.tpl"}
    {block name="page_scripts"}{/block}
</body>
</html>
