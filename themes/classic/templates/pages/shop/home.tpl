{extends file="layouts/base.tpl"}

{block name="body_class"}page-shop{/block}

{block name="body"}

{include file="partials/shop/palette_vars.tpl" palette_scope="page-shop"}

<div class="shop-page{if empty($theme_options.hero_slides_enabled)} shop-page--no-hero{/if}" id="main-content" data-subdomain="{$shop.subdomain|escape}" data-total="{$total_products}" data-limit="{$products_limit}" data-currency="{$currency_symbol|escape}"{if $active_category} data-active-category="{$active_category.id}" data-active-slug="{$active_category.slug|escape}" data-active-parent="{$active_category.parent_id|default:0}"{/if}>

    {hook name="theme.header.before"}

    {include file="partials/shop/announcement_bar.tpl"}

    {include file="partials/shop/desktop_header.tpl"}

    {include file="partials/shop/mobile_header.tpl"}

    {hook name="theme.header.after"}

    <main class="shop-content">

        {hook name="theme.content.before"}

        {include file="partials/shop/hero_slider.tpl"}

        {hook name="theme.hero.after"}

        {include file="partials/shop/trust_badges.tpl"}
        {hook name="theme.trust_badges.after"}

        {if !empty($sale_products)}
            {include file="partials/shop/product_slider.tpl" slider_products=$sale_products slider_title="Hot Deals"}
        {/if}

        {hook name="theme.deals.after"}

        {include file="partials/shop/collection_banners.tpl"}

        {hook name="theme.banners.after"}

        {if !empty($featured_products)}
            {include file="partials/shop/product_slider.tpl" slider_products=$featured_products slider_title="Best Sellers"}
        {/if}

        {hook name="theme.featured.after"}

        {include file="partials/shop/category_band.tpl"}

        {hook name="theme.categories.after"}

        {hook name="theme.grid.before"}

        <section class="products-section">
            <div class="section-header">
                <h2 class="section-title">All Products</h2>
                <span class="section-count">{$total_products} {if $total_products == 1}product{else}products{/if}</span>
            </div>

            <div class="product-grid" id="catalogue">
                {foreach $products as $product}
                    {include file="partials/shop/product_card.tpl" product=$product currency_symbol=$currency_symbol}
                {foreachelse}
                    {include file="partials/shop/empty_state.tpl"}
                {/foreach}
            </div>
        </section>

        {hook name="theme.grid.after"}

        {if $total_products > $products_limit}
        <div class="load-more-wrap" id="loadMoreWrap">
            <button type="button" class="load-more-btn" id="loadMoreBtn">
                Load more <span class="load-more-count" id="loadMoreCount">({$total_products - $products|@count} more)</span>
            </button>
        </div>
        {/if}

        {hook name="theme.content.after"}

    </main>

    {hook name="theme.footer.before"}

    {include file="partials/shop/desktop_footer.tpl"}

    {hook name="theme.footer.after"}

    {include file="partials/shop/share_sheet.tpl"}
    {include file="partials/shop/cart_drawer.tpl"}
    {include file="partials/shop/contact_sheet.tpl"}
    {include file="partials/shop/bottom_nav.tpl"}

</div>

{include file="partials/shop/jsonld.tpl"}
{/block}

