{extends file="layouts/base.tpl"}

{block name="body_class"}page-shop{/block}

{block name="body"}
{include file="partials/desktop_header.tpl"}
{if !empty($palette_css)}
<style>
.shop-page {
    --palette-primary: {$palette_css.primary};
    --palette-bar: {$palette_css.bar};
    --palette-bar-text: {$palette_css.bar_text};
    --palette-accent: {$palette_css.accent};
}
</style>
{/if}
<div class="shop-page" id="main-content" data-subdomain="{$shop.subdomain|escape}" data-total="{$total_products}" data-limit="{$products_limit}" data-currency="{$currency_symbol|escape}"{if $active_category} data-active-category="{$active_category.id}" data-active-slug="{$active_category.slug|escape}" data-active-parent="{$active_category.parent_id|default:0}"{/if}>
    <div class="container">
        {include file="partials/announcement_bar.tpl"}
        {include file="partials/shop_header.tpl"}

        {block name="shop_hero"}
        {include file="partials/hero_slider.tpl"}
        {/block}

        {if $total_products > 0 && $shop.show_search|default:1}
        {block name="shop_search"}
        <div class="shop-search" id="shopSearch">
            <i class="fa-solid fa-magnifying-glass shop-search-icon" style="font-size:18px"></i>
            <input type="text" class="shop-search-input" id="searchInput" placeholder="{block name='search_placeholder'}Search products...{/block}" autocomplete="off">
            <button type="button" class="shop-search-clear" id="searchClear" aria-label="Clear search">
                <i class="fa-solid fa-xmark" style="font-size:16px"></i>
            </button>
        </div>
        {/block}
        {/if}

        {if $shop.show_categories|default:1}
        {block name="shop_categories"}
        {include file="partials/shop_categories.tpl"}
        {/block}
        {/if}

        {if $total_products > 0 && $shop.show_sort_toolbar|default:1}
            <div class="product-toolbar" id="productToolbar">
                <div class="product-count" id="productCount">
                    {if $total_products > $products_limit}
                        Showing {$products|@count} of {$total_products} products
                    {else}
                        {$total_products} {if $total_products == 1}product{else}products{/if}
                    {/if}
                </div>
                <select class="product-sort" id="productSort" aria-label="Sort products">
                    <option value="default">Featured</option>
                    <option value="newest">Newest</option>
                    <option value="price_asc">Price: Low to High</option>
                    <option value="price_desc">Price: High to Low</option>
                    <option value="name_asc">Name: A &ndash; Z</option>
                </select>
            </div>
        {/if}

        {block name="pre_grid"}{/block}

        {block name="search_empty"}
        <div class="search-empty" id="searchEmpty" style="display:none">
            <i class="fa-solid fa-magnifying-glass" style="font-size:48px;color:var(--color-text-muted);opacity:0.3;margin-bottom:12px"></i>
            <p class="search-empty-title">No results found</p>
            <p class="search-empty-hint">Try a different search term</p>
        </div>
        {/block}

        <section class="product-grid {block name='grid_class'}{/block}" id="catalogue">
            {foreach $products as $product}
                {include file="partials/product_card.tpl" product=$product currency_symbol=$currency_symbol}
            {foreachelse}
                {block name="empty_state"}
                <div class="empty-state">
                    <i class="fa-solid fa-bag-shopping" style="font-size:64px;color:var(--color-text-muted);opacity:0.4;margin-bottom:16px"></i>
                    <p><strong>Coming soon</strong></p>
                    <p style="font-size:0.8125rem">This shop is getting ready — check back soon!</p>
                </div>
                {/block}
            {/foreach}
        </section>

        {if $total_products > $products_limit}
        <div class="load-more-wrap" id="loadMoreWrap">
            <button type="button" class="load-more-btn" id="loadMoreBtn">
                Show more products
                <span class="load-more-count" id="loadMoreCount">({$total_products - $products|@count} more)</span>
            </button>
        </div>
        {/if}

        {block name="shop_footer"}
        <footer class="shop-footer">
            &copy; {$shop.store_name|escape}
        </footer>
        {/block}
    </div>

    {include file="partials/share_sheet.tpl"}
    {include file="partials/cart_drawer.tpl"}
</div>
{include file="partials/desktop_footer.tpl"}

{block name="theme_scripts"}{/block}

{include file="partials/shop_jsonld.tpl"}
{/block}
