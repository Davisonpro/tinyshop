{extends file="layouts/base.tpl"}

{block name="body_class"}page-shop{/block}

{block name="body"}
{include file="partials/desktop_header.tpl"}
<div class="shop-page" data-subdomain="{$shop.subdomain|escape}" data-total="{$total_products}" data-limit="{$products_limit}" data-currency="{$currency_symbol|escape}">
    <div class="container">
        {include file="partials/announcement_bar.tpl"}
        {include file="partials/shop_header.tpl"}

        {* --- Purple banner with scattered Halloween motifs --- *}
        <div class="halloween-banner">
            <span class="halloween-sparkle halloween-sparkle--sm"><svg viewBox="0 0 24 24" fill="#FFFFFF"><path d="M12 0L14 10L24 12L14 14L12 24L10 14L0 12L10 10Z"/></svg></span>
            {* Bat icon *}
            <span class="halloween-bat"><svg width="28" height="18" viewBox="0 0 56 36" fill="#CCE156"><path d="M28 8C28 8 22 2 14 2C8 2 2 6 0 12C4 10 8 10 12 12C8 14 6 18 4 22C10 18 16 16 20 18C18 20 16 24 16 28C20 24 24 20 28 18C32 20 36 24 40 28C40 24 38 20 36 18C40 16 46 18 52 22C50 18 48 14 44 12C48 10 52 10 56 12C54 6 48 2 42 2C34 2 28 8 28 8Z"/><circle cx="20" cy="10" r="3" fill="#000"/><circle cx="20" cy="10" r="1.5" fill="#CCE156"/></svg></span>
            <span class="halloween-banner-text">Shop the Collection</span>
            {* Skull icon *}
            <span class="halloween-bat"><svg width="20" height="22" viewBox="0 0 40 44" fill="#E8B4F8"><path d="M20 2C10 2 2 10 2 20C2 28 8 34 14 36L14 42C14 43 15 44 16 44L24 44C25 44 26 43 26 42L26 36C32 34 38 28 38 20C38 10 30 2 20 2Z"/><ellipse cx="14" cy="20" rx="5" ry="6" fill="#000"/><ellipse cx="26" cy="20" rx="5" ry="6" fill="#000"/></svg></span>
            <span class="halloween-sparkle halloween-sparkle--md"><svg viewBox="0 0 24 24" fill="#FFFFFF"><path d="M12 0L14 10L24 12L14 14L12 24L10 14L0 12L10 10Z"/></svg></span>
        </div>

        {if $total_products > 0 && $shop.show_search|default:1}
        <div class="shop-search" id="shopSearch">
            <i class="fa-solid fa-magnifying-glass shop-search-icon" style="font-size:18px"></i>
            <input type="text" class="shop-search-input" id="searchInput" placeholder="Search for treats..." autocomplete="off">
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
            <i class="fa-solid fa-ghost" style="font-size:48px;color:var(--color-text-muted);opacity:0.3;margin-bottom:12px"></i>
            <p class="search-empty-title">No tricks or treats found</p>
            <p class="search-empty-hint">Try something else</p>
        </div>

        <section class="product-grid" id="catalogue">
            {foreach $products as $product}
                {include file="partials/product_card.tpl" product=$product currency_symbol=$currency_symbol}
            {foreachelse}
                <div class="empty-state">
                    <i class="fa-solid fa-ghost" style="font-size:64px;color:var(--color-text-muted);opacity:0.4;margin-bottom:16px"></i>
                    <p><strong>Nothing here... yet</strong></p>
                    <p style="font-size:0.8125rem; color:var(--color-text-muted)">Spooky things are brewing &mdash; check back soon!</p>
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

        {* --- Footer with bat decoration --- *}
        <footer class="shop-footer">
            <div class="halloween-footer-deco">
                <span class="halloween-sparkle halloween-sparkle--sm"><svg viewBox="0 0 24 24" fill="#AE7FF7"><path d="M12 0L14 10L24 12L14 14L12 24L10 14L0 12L10 10Z"/></svg></span>
                <svg width="24" height="16" viewBox="0 0 56 36" fill="#666"><path d="M28 8C28 8 22 2 14 2C8 2 2 6 0 12C4 10 8 10 12 12C8 14 6 18 4 22C10 18 16 16 20 18C18 20 16 24 16 28C20 24 24 20 28 18C32 20 36 24 40 28C40 24 38 20 36 18C40 16 46 18 52 22C50 18 48 14 44 12C48 10 52 10 56 12C54 6 48 2 42 2C34 2 28 8 28 8Z"/></svg>
                <span class="halloween-sparkle halloween-sparkle--sm"><svg viewBox="0 0 24 24" fill="#CCE156"><path d="M12 0L14 10L24 12L14 14L12 24L10 14L0 12L10 10Z"/></svg></span>
            </div>
            &copy; {$shop.store_name|escape}
        </footer>
    </div>

    {include file="partials/share_sheet.tpl"}
    {include file="partials/cart_drawer.tpl"}
</div>
{include file="partials/desktop_footer.tpl"}
{include file="partials/shop_jsonld.tpl"}
{/block}
