{extends file="layouts/base.tpl"}

{block name="body_class"}page-help{/block}

{block name="extra_css"}
<link rel="stylesheet" href="/public/css/help{$min}.css?v={$asset_v}">
{/block}

{block name="body"}

{include file="partials/public/nav.tpl" current_page="help"}

{* ── Breadcrumb ── *}
<nav class="help-breadcrumb">
    <a href="/help">Help Center</a>
    <span class="help-breadcrumb-sep">&rsaquo;</span>
    <a href="/help#section-{$category_slug}">{$category.name|escape}</a>
    <span class="help-breadcrumb-sep">&rsaquo;</span>
    <span class="help-breadcrumb-current">{$article.title|escape}</span>
</nav>

{* ── Article header ── *}
<header class="help-article-header">
    <span class="help-article-cat">
        <i class="fa-solid {$category.icon}"></i>
        {$category.name|escape}
    </span>
    <h1 class="help-article-title">{$article.title|escape}</h1>
    {if $article.summary}
        <p class="help-article-summary">{$article.summary|escape}</p>
    {/if}
</header>

{* ── Article content ── *}
<div class="help-article-content">
    {$article.content}
</div>

{* ── Prev / Next ── *}
{if $prev_article || $next_article}
<nav class="help-nav">
    {if $prev_article}
    <a href="/help/{$prev_article.slug}" class="help-nav-link help-nav-link--prev">
        <span class="help-nav-label">&larr; Previous</span>
        <span class="help-nav-title">{$prev_article.title|escape}</span>
    </a>
    {/if}
    {if $next_article}
    <a href="/help/{$next_article.slug}" class="help-nav-link help-nav-link--next">
        <span class="help-nav-label">Next &rarr;</span>
        <span class="help-nav-title">{$next_article.title|escape}</span>
    </a>
    {/if}
</nav>
{/if}

{* ── Related articles (title-only) ── *}
{if $related|@count > 0}
<div class="help-related">
    <h2 class="help-related-title">Related articles</h2>
    <div class="help-related-list">
        {foreach $related as $rel}
        <a href="/help/{$rel.slug}" class="help-article-link">
            <div class="help-article-link-body">
                <p class="help-article-link-title">{$rel.title|escape}</p>
            </div>
            <i class="fa-solid fa-chevron-right help-article-link-arrow"></i>
        </a>
        {/foreach}
    </div>
</div>
{/if}

{* ── Bottom CTA ── *}
<div class="help-article-bottom">
    <h2>Still need help?</h2>
    <p>Can't find what you're looking for? Reach out and we'll get back to you.</p>
    <a href="mailto:{if $support_email}{$support_email|escape}{else}hello@{$base_domain|default:'tinyshop.com'}{/if}" class="help-bottom-btn">
        <i class="fa-solid fa-envelope"></i>
        Contact support
    </a>
</div>

{include file="partials/public/footer.tpl"}

{* ── Google Schema: Article ── *}
<script type="application/ld+json">
{ldelim}
    "@context": "https://schema.org",
    "@type": "Article",
    "headline": "{$article.title|escape:'javascript'}",
    {if $article.summary}"description": "{$article.summary|escape:'javascript'}",{/if}
    "articleSection": "{$category.name|escape:'javascript'}",
    "datePublished": "{$article.created_at}",
    "dateModified": "{$article.updated_at}",
    "publisher": {ldelim}
        "@type": "Organization",
        "name": "{$app_name|escape:'javascript'}"
    {rdelim},
    "mainEntityOfPage": {ldelim}
        "@type": "WebPage",
        "@id": "{$canonical_url|default:''}"
    {rdelim}
{rdelim}
</script>

{* ── Google Schema: BreadcrumbList ── *}
<script type="application/ld+json">
{ldelim}
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
        {ldelim}
            "@type": "ListItem",
            "position": 1,
            "name": "Help Center",
            "item": "{$base_url|default:''}/help"
        {rdelim},
        {ldelim}
            "@type": "ListItem",
            "position": 2,
            "name": "{$category.name|escape:'javascript'}",
            "item": "{$base_url|default:''}/help#section-{$category_slug}"
        {rdelim},
        {ldelim}
            "@type": "ListItem",
            "position": 3,
            "name": "{$article.title|escape:'javascript'}"
        {rdelim}
    ]
{rdelim}
</script>

{/block}
