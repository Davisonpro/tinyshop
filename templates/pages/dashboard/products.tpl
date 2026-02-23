{extends file="layouts/dashboard.tpl"}

{block name="content"}
<div class="dash-topbar">
    <span class="dash-topbar-title">Products</span>
    <a href="/dashboard/shop" class="dash-topbar-avatar">{$user.store_name|escape|substr:0:1|upper}</a>
</div>

{if !empty($usage) && !$usage.products_unlimited}
<div class="plan-limit-bar" id="planLimitBar">
    <span>{if $usage.product_count >= $usage.max_products}Product limit reached ({$usage.max_products}){else}{$usage.product_count} of {$usage.max_products} products{/if}</span>
    <div class="plan-limit-bar-fill{if $usage.product_count >= $usage.max_products} at-limit{elseif $usage.product_count >= $usage.max_products * 0.8} near-limit{/if}">
        <span style="width:{$usage.product_percent}%"></span>
    </div>
    {if $usage.can_upgrade}<a href="/dashboard/billing" style="font-size:0.75rem;font-weight:600;color:var(--color-accent)">Upgrade</a>{/if}
</div>
{/if}

<div class="product-search-bar" id="productSearchBar" style="display:none">
    <i class="fa-solid fa-magnifying-glass"></i>
    <input type="text" id="productSearch" placeholder="Search products..." autocomplete="off" aria-label="Search products">
    <button type="button" class="bulk-select-toggle" id="bulkSelectToggle" style="display:none" title="Select products">
        <i class="fa-regular fa-square-check"></i>
    </button>
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
<a href="/dashboard/products/add" class="fab" id="addProductFab" title="Add Product" aria-label="Add new product">
    <i class="fa-solid fa-plus" aria-hidden="true"></i>
</a>

{* Bulk action bar — shown in select mode *}
<div class="bulk-action-bar" id="bulkActionBar" style="display:none">
    <span class="bulk-count" id="bulkCount">0 selected</span>
    <div class="bulk-actions">
        <button type="button" class="bulk-btn bulk-btn-archive" id="bulkArchiveBtn">
            <i class="fa-solid fa-eye-slash"></i> Hide
        </button>
        <button type="button" class="bulk-btn bulk-btn-delete" id="bulkDeleteBtn">
            <i class="fa-solid fa-trash"></i> Delete
        </button>
    </div>
</div>
{/block}

{block name="extra_scripts"}
<script>
var _productListConfig = {
    currency: '{$user.currency|default:'KES'|escape:"javascript"}',
    subdomain: '{$user.subdomain|escape:"javascript"}',
    baseDomain: '{$base_domain|escape:"javascript"}'
};
</script>
{/block}
