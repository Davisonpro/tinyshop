{extends file="layouts/base.tpl"}

{block name="body_class"}page-auth{/block}

{block name="extra_css"}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="/public/css/marketing.css?v={$asset_v}">
{/block}

{block name="body"}
{include file="partials/marketing_nav.tpl"}
<div class="auth-wrapper">
    <div class="auth-card">
        {block name="content"}{/block}
    </div>
</div>
{/block}