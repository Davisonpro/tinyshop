{extends file="layouts/base.tpl"}

{block name="body_class"}page-shop page-collections{/block}

{block name="body"}

{include file="partials/palette_vars.tpl" palette_scope="page-collections"}

{include file="partials/announcement_bar.tpl"}
{include file="partials/desktop_header.tpl"}
{include file="partials/mobile_header.tpl"}

<main class="shop-content">

    {hook name="theme.collections.before"}

    <div class="collections-header">
        <nav class="collections-breadcrumb">
            <a href="/">Home</a>
            <svg width="6" height="10" viewBox="0 0 6 10" fill="currentColor"><path d="M1.4 10L0 8.6 3.6 5 0 1.4 1.4 0l5 5-5 5z"/></svg>
            <span>Collections</span>
        </nav>
        <h1 class="collections-title">All Collections</h1>
        <p class="collections-count">{$categories|@count} {if $categories|@count == 1}collection{else}collections{/if}</p>
    </div>

    {hook name="theme.collections.header.after"}

    {if !empty($categories)}
    <div class="collections-list">
        {foreach $categories as $cat}
        <a href="/collections/{$cat.slug|escape}" class="collections-row{if $cat.image_url} has-image{/if}">
            {if $cat.image_url}
            <div class="collections-row-img">
                <img src="{$cat.image_url|escape}" alt="{$cat.name|escape}" loading="lazy">
            </div>
            {/if}
            <div class="collections-row-main">
                <h2 class="collections-row-name">{$cat.name|escape}</h2>
                <span class="collections-row-count">{$cat.product_count} {if $cat.product_count == 1}product{else}products{/if}</span>
            </div>
            {if !empty($cat.children)}
            <div class="collections-row-subcats">
                {foreach $cat.children as $child}
                <span class="collections-row-subcat">{$child.name|escape}</span>
                {/foreach}
            </div>
            {/if}
            <svg class="collections-row-arrow" width="7" height="12" viewBox="0 0 7 12" fill="currentColor"><path d="M1.5 12L0 10.5 4.5 6 0 1.5 1.5 0l6 6-6 6z"/></svg>
        </a>
        {/foreach}
    </div>
    {else}
    <div class="collections-empty">
        <p>No collections yet.</p>
    </div>
    {/if}

    {hook name="theme.collections.after"}

</main>

{include file="partials/mobile_footer.tpl"}
{include file="partials/desktop_footer.tpl"}
{include file="partials/search_overlay.tpl"}
{include file="partials/cart_drawer.tpl"}

{/block}
