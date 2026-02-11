{extends file="layouts/dashboard.tpl"}

{block name="content"}
<div class="dash-topbar">
    <span class="dash-topbar-title">Products</span>
    <a href="/dashboard/shop" class="dash-topbar-avatar">{if $user.shop_logo}<img src="{$user.shop_logo|escape}" alt="">{else}{$user.name|escape|substr:0:1|upper}{/if}</a>
</div>

<div class="product-search-bar" id="productSearchBar" style="display:none">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" id="productSearch" placeholder="Search products..." autocomplete="off" aria-label="Search products">
</div>

<div class="category-filter-bar" id="categoryFilterBar" style="display:none"></div>

<div class="product-list-summary" id="productListSummary" style="display:none">
    <span id="productCount"></span>
</div>

<div id="productGrid" class="product-grid-manage">
    <div class="skeleton-card"><div class="skeleton-img"></div><div class="skeleton-body"><div class="skeleton-line skeleton-line-title"></div><div class="skeleton-line skeleton-line-price"></div></div></div>
    <div class="skeleton-card"><div class="skeleton-img"></div><div class="skeleton-body"><div class="skeleton-line skeleton-line-title"></div><div class="skeleton-line skeleton-line-price"></div></div></div>
    <div class="skeleton-card"><div class="skeleton-img"></div><div class="skeleton-body"><div class="skeleton-line skeleton-line-title"></div><div class="skeleton-line skeleton-line-price"></div></div></div>
    <div class="skeleton-card"><div class="skeleton-img"></div><div class="skeleton-body"><div class="skeleton-line skeleton-line-title"></div><div class="skeleton-line skeleton-line-price"></div></div></div>
</div>

<div id="productLoadMore" class="product-load-more" style="display:none">
    <button type="button" id="loadMoreBtn" class="btn-load-more">Load more products</button>
</div>

{* FAB — Add Product *}
<a href="/dashboard/products/add" class="fab" title="Add Product" aria-label="Add new product">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
</a>
{/block}

{block name="extra_scripts"}
<script>
var _productListConfig = {
    currency: '{$user.currency|default:'KES'|escape:"javascript"}'
};
</script>
{/block}
