{extends file="layouts/base.tpl"}

{block name="body_class"}page-shop page-collection{/block}

{block name="body"}

{include file="partials/palette_vars.tpl" palette_scope="page-collection"}

{include file="partials/announcement_bar.tpl"}
{include file="partials/desktop_header.tpl"}
{include file="partials/mobile_header.tpl"}

<main class="shop-content" data-subdomain="{$shop.subdomain|escape}" data-total="{$total_products}" data-limit="{$products_limit}" data-currency="{$currency_symbol|escape}" data-active-category="{$category.id}">

    {hook name="theme.collection.before"}

    <div class="collection-hero">
        <nav class="collections-breadcrumb">
            <a href="/">Home</a>
            <svg width="6" height="10" viewBox="0 0 6 10" fill="currentColor"><path d="M1.4 10L0 8.6 3.6 5 0 1.4 1.4 0l5 5-5 5z"/></svg>
            <a href="/collections">Collections</a>
            <svg width="6" height="10" viewBox="0 0 6 10" fill="currentColor"><path d="M1.4 10L0 8.6 3.6 5 0 1.4 1.4 0l5 5-5 5z"/></svg>
            <span>{$category.name|escape}</span>
        </nav>

        {if $category.image_url}
        <div class="collection-hero-banner">
            <img src="{$category.image_url|escape}" alt="{$category.name|escape}">
            <div class="collection-hero-overlay">
                <h1 class="collection-hero-title">{$category.name|escape}</h1>
            </div>
        </div>
        {else}
        <h1 class="collections-title">{$category.name|escape}</h1>
        {/if}

        <p class="collection-hero-count">{$total_products} {if $total_products == 1}product{else}products{/if}</p>
    </div>

    {hook name="theme.collection.hero.after"}

    {if !empty($subcategories)}
    <div class="collection-filters hide-scrollbar">
        <button class="collection-filter active" data-filter="all">All</button>
        {foreach $subcategories as $sub}
        <button class="collection-filter" data-filter="{$sub.id}">{$sub.name|escape}</button>
        {/foreach}
    </div>
    {/if}

    {hook name="theme.collection.grid.before"}

    <section class="products-section">
        <div class="product-grid" id="productGrid">
            {foreach $products as $product}
                {include file="partials/product_card.tpl"}
            {foreachelse}
                {include file="partials/empty_state.tpl" empty_title="No products yet" empty_subtitle="Check back soon for new items in this collection."}
            {/foreach}
        </div>
    </section>

    {hook name="theme.collection.grid.after"}

    {if $total_products > $products_limit}
    <div class="load-more-wrap">
        <button class="load-more-btn" id="loadMoreBtn">
            Load more <span class="load-more-count">({$total_products - $products_limit} more)</span>
        </button>
    </div>
    {/if}

    {hook name="theme.collection.after"}

</main>

{include file="partials/mobile_footer.tpl"}
{include file="partials/desktop_footer.tpl"}
{include file="partials/search_overlay.tpl"}
{include file="partials/cart_drawer.tpl"}

{/block}

{block name="page_scripts"}
<script>
(function() {ldelim}
    'use strict';

    var filters = document.querySelectorAll('.collection-filter');
    var grid = document.getElementById('productGrid');
    if (!filters.length || !grid) return;

    filters.forEach(function(btn) {ldelim}
        btn.addEventListener('click', function() {ldelim}
            filters.forEach(function(f) {ldelim} f.classList.remove('active'); {rdelim});
            btn.classList.add('active');

            var filter = btn.getAttribute('data-filter');
            var cards = grid.querySelectorAll('.product-card');

            cards.forEach(function(card) {ldelim}
                if (filter === 'all') {ldelim}
                    card.style.display = '';
                {rdelim} else {ldelim}
                    var catId = card.getAttribute('data-category');
                    card.style.display = (catId === filter) ? '' : 'none';
                {rdelim}
            {rdelim});
        {rdelim});
    {rdelim});
{rdelim})();
</script>
{/block}
