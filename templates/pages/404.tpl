{extends file="layouts/base.tpl"}

{block name="body_class"}page-not-found{/block}

{block name="body"}

{include file="partials/marketing_nav.tpl"}

<div class="not-found-wrap">
    <div class="not-found-icon">
        <i class="fa-solid fa-compass"></i>
    </div>
    <h1 class="not-found-title">Page not found</h1>
    <p class="not-found-text">The page you're looking for doesn't exist or has been moved.</p>
    <a href="/" class="not-found-btn">Go to homepage</a>
</div>

{include file="partials/marketing_footer.tpl"}

{/block}
