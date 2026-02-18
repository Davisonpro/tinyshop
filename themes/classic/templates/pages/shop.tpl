{extends file="layouts/base.tpl"}

{block name="body_class"}page-shop{/block}

{block name="body"}

{include file="partials/palette_vars.tpl" palette_scope="page-shop"}

<div class="shop-page{if empty($hero_slides)} shop-page--no-hero{/if}" id="main-content" data-subdomain="{$shop.subdomain|escape}" data-total="{$total_products}" data-limit="{$products_limit}" data-currency="{$currency_symbol|escape}"{if $active_category} data-active-category="{$active_category.id}" data-active-slug="{$active_category.slug|escape}" data-active-parent="{$active_category.parent_id|default:0}"{/if}>

    {hook name="theme.header.before"}

    {include file="partials/announcement_bar.tpl"}

    {include file="partials/desktop_header.tpl"}

    {include file="partials/mobile_header.tpl" show_contact_links=true show_social_links=true}

    {hook name="theme.header.after"}

    {include file="partials/search_overlay.tpl"}

    <main class="shop-content">

        {hook name="theme.content.before"}

        {include file="partials/hero_slider.tpl"}

        {hook name="theme.hero.after"}

        {if !empty($hero_slides)}
            {include file="partials/trust_badges.tpl"}
            {hook name="theme.trust_badges.after"}
        {/if}

        {if !empty($sale_products)}
            {include file="partials/product_slider.tpl" slider_products=$sale_products slider_title="Hot Deals"}
        {/if}

        {hook name="theme.deals.after"}

        {include file="partials/collection_banners.tpl"}

        {hook name="theme.banners.after"}

        {if !empty($featured_products)}
            {include file="partials/product_slider.tpl" slider_products=$featured_products slider_title="Best Sellers"}
        {/if}

        {hook name="theme.featured.after"}

        {include file="partials/category_band.tpl"}

        {hook name="theme.categories.after"}

        {hook name="theme.grid.before"}

        <section class="products-section">
            <div class="section-header">
                <h2 class="section-title">All Products</h2>
                <span class="section-count">{$total_products} {if $total_products == 1}product{else}products{/if}</span>
            </div>

            <div class="product-grid" id="catalogue">
                {foreach $products as $product}
                    {include file="partials/product_card.tpl" product=$product currency_symbol=$currency_symbol}
                {foreachelse}
                    {include file="partials/empty_state.tpl"}
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

    {include file="partials/mobile_footer.tpl"}
    {include file="partials/desktop_footer.tpl"}

    {hook name="theme.footer.after"}

    {include file="partials/share_sheet.tpl"}
    {include file="partials/cart_drawer.tpl"}

</div>

{include file="partials/shop_jsonld.tpl"}
{/block}

{block name="page_scripts"}
<script>
(function() {ldelim}
    var overlay = document.getElementById('searchOverlay');
    if (!overlay) return;
    var input   = document.getElementById('searchOverlayInput');
    var closeBtn = document.getElementById('searchOverlayClose');

    document.querySelectorAll('.search-toggle').forEach(function(btn) {ldelim}
        btn.addEventListener('click', function() {ldelim}
            overlay.classList.add('active');
            setTimeout(function() {ldelim} input.focus(); {rdelim}, 100);
        {rdelim});
    {rdelim});

    closeBtn.addEventListener('click', function() {ldelim}
        overlay.classList.remove('active');
        input.value = '';
    {rdelim});

    overlay.addEventListener('click', function(e) {ldelim}
        if (e.target === overlay) {ldelim}
            overlay.classList.remove('active');
            input.value = '';
        {rdelim}
    {rdelim});

    document.addEventListener('keydown', function(e) {ldelim}
        if (e.key === 'Escape' && overlay.classList.contains('active')) {ldelim}
            overlay.classList.remove('active');
            input.value = '';
        {rdelim}
    {rdelim});
{rdelim})();
</script>
{/block}
