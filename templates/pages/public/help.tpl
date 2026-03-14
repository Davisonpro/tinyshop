{extends file="layouts/base.tpl"}

{block name="body_class"}page-help{/block}

{block name="extra_css"}
<link rel="stylesheet" href="/public/css/help{$min}.css?v={$asset_v}">
{/block}

{block name="body"}

{include file="partials/public/nav.tpl" current_page="help"}

{* ── Header + Search ── *}
<header class="help-header">
    <h1>How can we help?</h1>
    <p>Search our guides or browse by topic below.</p>
    <div class="help-search-wrap" id="helpSearchWrap">
        <i class="fa-solid fa-magnifying-glass help-search-icon"></i>
        <input type="text" class="help-search-input" id="helpSearchInput" placeholder="Search for answers..." autocomplete="off">
        <button type="button" class="help-search-clear" id="helpSearchClear" aria-label="Clear search">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
</header>

{* ── Search results (hidden until search) ── *}
<div class="help-search-results" id="helpSearchResults">
    <div class="help-search-results-title" id="helpResultsTitle"></div>
    <div class="help-search-empty" id="helpSearchEmpty" style="display:none">
        <i class="fa-solid fa-magnifying-glass"></i>
        <p>No results found</p>
        <p>Try a different search term or browse the topics below.</p>
    </div>
    <div class="help-article-list" id="helpResultsList"></div>
</div>

{* ── Main content (hidden during search) ── *}
<div id="helpMain">

{* ── Popular questions ── *}
<section class="help-popular">
    <h2 class="help-popular-title">Common questions</h2>
    <div class="help-popular-grid">
        {foreach $articles_all as $article}
        {if $article.slug == 'what-is-myduka'
            || $article.slug == 'quick-start-guide'
            || $article.slug == 'connecting-a-custom-domain'
            || $article.slug == 'site-not-secure'
            || $article.slug == 'setting-up-payment-methods'
            || $article.slug == 'is-myduka-free'}
        <a href="/help/{$article.slug}" class="help-popular-card">
            <span class="help-popular-card-cat">{$article.category_name|escape}</span>
            <span class="help-popular-card-title">{$article.title|escape}</span>
            <span class="help-popular-card-summary">{$article.summary|escape}</span>
        </a>
        {/if}
        {/foreach}
    </div>
</section>

{* ── Browse by topic (accordion) ── *}
<section class="help-topics">
    <h2 class="help-topics-title">Browse by topic</h2>
    <div class="help-topic-list">
        {foreach $categories as $slug => $cat}
        {if isset($articles_grouped[$slug])}
        <div class="help-topic" id="topic-{$slug}">
            <button type="button" class="help-topic-header" aria-expanded="false" aria-controls="topic-body-{$slug}">
                <span class="help-topic-icon"><i class="fa-solid {$cat.icon}"></i></span>
                <span class="help-topic-name">{$cat.name|escape}</span>
                <span class="help-topic-count">{$cat.article_count}</span>
                <i class="fa-solid fa-chevron-down help-topic-arrow"></i>
            </button>
            <div class="help-topic-body" id="topic-body-{$slug}">
                {foreach $articles_grouped[$slug] as $article}
                <a href="/help/{$article.slug}" class="help-article-link">
                    <div class="help-article-link-body">
                        <p class="help-article-link-title">{$article.title|escape}</p>
                        {if $article.summary}<p class="help-article-link-summary">{$article.summary|escape}</p>{/if}
                    </div>
                    <i class="fa-solid fa-chevron-right help-article-link-arrow"></i>
                </a>
                {/foreach}
            </div>
        </div>
        {/if}
        {/foreach}
    </div>
</section>

</div>

{* ── Bottom CTA ── *}
<div class="help-bottom" id="helpBottom">
    <div class="help-bottom-inner">
        <h2>Still need help?</h2>
        <p>Can't find what you're looking for? Reach out and we'll get back to you.</p>
        <a href="mailto:{if $support_email}{$support_email|escape}{else}support@{$base_domain|default:'tinyshop.com'}{/if}" class="help-bottom-btn">
            <i class="fa-solid fa-envelope"></i>
            Contact support
        </a>

        {* URL pill CTA *}
        <div class="help-bottom-cta">
            <h3>Ready to start selling?</h3>
            <p>Create your own shop in minutes. Free plan included.</p>
            <div class="help-bottom-form">
                <span class="help-bottom-url"><i class="fa-solid fa-link"></i> yourshop.{$base_domain|default:'tinyshop.com'}</span>
                <a href="/register" class="help-bottom-shop-btn">Claim your shop</a>
            </div>
        </div>
    </div>
</div>

{include file="partials/public/footer.tpl"}

{* Search data — embedded JSON for client-side search *}
<script type="application/json" id="helpArticleData">
[
{foreach $articles_all as $article}
    {ldelim}"slug":"{$article.slug|escape:'javascript'}","title":"{$article.title|escape:'javascript'}","summary":"{$article.summary|escape:'javascript'}","keywords":"{$article.keywords|escape:'javascript'}","category_name":"{$article.category_name|escape:'javascript'}"{rdelim}{if !$article@last},{/if}
{/foreach}
]
</script>

{* ── Google Schema: FAQPage ── *}
<script type="application/ld+json">
{ldelim}
    "@context": "https://schema.org",
    "@type": "FAQPage",
    "name": "Help Center",
    "description": "Search our guides or browse by topic.",
    "mainEntity": [
        {foreach $articles_all as $article}
        {ldelim}
            "@type": "Question",
            "name": "{$article.title|escape:'javascript'}",
            "acceptedAnswer": {ldelim}
                "@type": "Answer",
                "text": "{$article.summary|escape:'javascript'}"
            {rdelim}
        {rdelim}{if !$article@last},{/if}
        {/foreach}
    ]
{rdelim}
</script>

{/block}

{block name="page_scripts"}
<script src="/public/js/help{$min}.js?v={$asset_v}"></script>
{/block}
