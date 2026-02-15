{extends file="layouts/base.tpl"}

{block name="body_class"}page-shop{/block}

{block name="body"}
{include file="partials/desktop_header.tpl"}
<div class="shop-page" data-subdomain="{$shop.subdomain|escape}" data-total="{$total_products}" data-limit="{$products_limit}" data-currency="{$currency_symbol|escape}">
    <div class="container">
        {include file="partials/announcement_bar.tpl"}
        {include file="partials/shop_header.tpl"}

        {if $total_products > 0 && $shop.show_search|default:1}
        <div class="shop-search" id="shopSearch">
            <i class="fa-solid fa-magnifying-glass shop-search-icon" style="font-size:18px"></i>
            <input type="text" class="shop-search-input" id="searchInput" placeholder="Search the collection..." autocomplete="off">
            <button type="button" class="shop-search-clear" id="searchClear" aria-label="Clear search">
                <i class="fa-solid fa-xmark" style="font-size:16px"></i>
            </button>
        </div>
        {/if}

        {if $shop.show_categories|default:1}
        {include file="partials/shop_categories.tpl"}
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

        <div class="search-empty" id="searchEmpty" style="display:none">
            <i class="fa-solid fa-magnifying-glass" style="font-size:48px;color:var(--color-text-muted);opacity:0.3;margin-bottom:12px"></i>
            <p class="search-empty-title">Nothing matches</p>
            <p class="search-empty-hint">Try a different search</p>
        </div>

        {if $total_products > 0}
        <div class="monaco-collection-title">Collection</div>
        {/if}

        <section class="product-grid monaco-grid" id="catalogue">
            {foreach $products as $product}
                {include file="partials/product_card.tpl" product=$product currency_symbol=$currency_symbol}
            {foreachelse}
                <div class="empty-state">
                    <p class="empty-state-title"><strong>The collection is being curated</strong></p>
                    <p class="empty-state-text">Exquisite pieces are on their way.</p>
                </div>
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

        <footer class="shop-footer">
            &copy; {$shop.store_name|escape}
        </footer>
    </div>

    {include file="partials/share_sheet.tpl"}
    {include file="partials/cart_drawer.tpl"}
</div>
{include file="partials/desktop_footer.tpl"}

{* Monaco theme renderer — accent bar + collection label in card body *}
<script>
window.TinyShopTheme = {ldelim}{rdelim};
window.TinyShopTheme.renderProductCard = function(p, currencySymbol) {ldelim}
    var h = TinyShop.cardHelpers;
    var slug = p.slug || p.id;
    var soldClass = p.is_sold == 1 ? ' product-card-sold' : '';
    var badge = h.badge(p);
    var badgeHtml = '';
    if (badge) {ldelim}
        var label = badge.type === 'sold' ? 'Sold Out' : badge.pct + '% OFF';
        badgeHtml = '<span class="product-badge product-badge-' + badge.type + '">' + label + '</span>';
    {rdelim}
    var name = h.escapeName(p.name);
    var price = h.priceHtml(p, currencySymbol);

    return '<a href="/' + slug + '" class="product-card' + soldClass + '" data-category="' + (p.category_id || '') + '">'
        + '<div class="product-card-img">' + badgeHtml
        + '<img src="' + h.imgSrc(p) + '" alt="' + name + '" loading="lazy" onload="this.classList.add(\'loaded\')">'
        + '</div>'
        + '<div class="product-card-body">'
        + '<div class="monaco-card-accent"></div>'
        + '<span class="product-card-label">Collection</span>'
        + '<h3 class="product-title">' + name + '</h3>'
        + '<div class="product-price">' + price.full + '</div>'
        + '</div></a>';
{rdelim};
</script>

{include file="partials/shop_jsonld.tpl"}
{/block}
