{extends file="layouts/base.tpl"}

{block name="body_class"}page-shop page-search{/block}

{block name="body"}

{include file="partials/palette_vars.tpl" palette_scope="page-search"}

{include file="partials/announcement_bar.tpl"}
{include file="partials/desktop_header.tpl"}
{include file="partials/mobile_header.tpl"}

<main class="shop-content" data-subdomain="{$shop.subdomain|escape}" data-total="{$total_products}" data-limit="{$products_limit}" data-currency="{$currency_symbol|escape}">

    <div class="search-page-header">
        <nav class="collections-breadcrumb">
            <a href="/">Home</a>
            <svg width="6" height="10" viewBox="0 0 6 10" fill="currentColor"><path d="M1.4 10L0 8.6 3.6 5 0 1.4 1.4 0l5 5-5 5z"/></svg>
            <span>Search</span>
        </nav>

        <form class="search-page-form" action="/search" method="get">
            <div class="search-page-bar">
                <i class="fa-solid fa-magnifying-glass search-page-icon"></i>
                <input type="search" name="q" class="search-page-input" value="{$search_query|escape}" placeholder="Search products..." autocomplete="off" autofocus>
                {if $search_query}
                <a href="/search" class="search-page-clear" aria-label="Clear search">
                    <i class="fa-solid fa-xmark"></i>
                </a>
                {/if}
            </div>
        </form>

        {if $search_query}
        <p class="search-page-count">{$total_products} {if $total_products == 1}result{else}results{/if} for "{$search_query|escape}"</p>
        {/if}
    </div>

    <section class="products-section">
        <div class="product-grid" id="productGrid">
            {if $search_query}
                {foreach $products as $product}
                    {include file="partials/product_card.tpl"}
                {foreachelse}
                    {include file="partials/empty_state.tpl" empty_title="No results found" empty_subtitle="Try a different search term."}
                {/foreach}
            {else}
                {include file="partials/empty_state.tpl" empty_title="Search products" empty_subtitle="Type a keyword to find what you're looking for."}
            {/if}
        </div>
    </section>

    {if $total_products > $products_limit}
    <div class="load-more-wrap">
        <button class="load-more-btn" id="loadMoreBtn">
            Load more <span class="load-more-count">({$total_products - $products_limit} more)</span>
        </button>
    </div>
    {/if}

</main>

{include file="partials/mobile_footer.tpl"}
{include file="partials/desktop_footer.tpl"}
{include file="partials/search_overlay.tpl"}
{include file="partials/cart_drawer.tpl"}

{/block}
