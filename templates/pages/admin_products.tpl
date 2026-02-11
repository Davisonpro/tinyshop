{extends file="layouts/admin.tpl"}

{block name="content"}
<div class="dash-topbar">
    <span class="dash-topbar-title">All Products ({$total})</span>
</div>

<div class="admin-toolbar">
    <form method="get" action="/admin/products" class="admin-search">
        <input type="text" name="q" value="{$search|escape}" placeholder="Search products or sellers..." class="form-control form-control-sm" autocomplete="off">
        {if $search}<a href="/admin/products" class="admin-search-clear" title="Clear">&times;</a>{/if}
    </form>
</div>

<div class="admin-list-wrap">
    {if $products|count == 0}
        <div class="empty-state">
            <p>{if $search}No products matching "{$search|escape}"{else}No products yet.{/if}</p>
        </div>
    {else}
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Seller</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                {foreach $products as $product}
                    <tr>
                        <td>
                            <div class="seller-cell">
                                <strong>{$product.name|escape|truncate:40}</strong>
                                {if $product.category_name}<small>{$product.category_name|escape}</small>{/if}
                            </div>
                        </td>
                        <td>
                            <a href="/admin/sellers/{$product.user_id}">{$product.store_name|escape|default:$product.seller_name|escape}</a>
                        </td>
                        <td>{$product.price|number_format:2}</td>
                        <td>
                            {if $product.is_active}
                                <span class="badge badge-green">Active</span>
                            {else}
                                <span class="badge badge-muted">Hidden</span>
                            {/if}
                            {if $product.is_sold}<span class="badge badge-orange">Sold</span>{/if}
                        </td>
                        <td><small>{$product.created_at|date_format:"%b %e, %Y"}</small></td>
                        <td>
                            <div class="admin-actions">
                                {if $product.subdomain}
                                <a href="{$scheme}://{$product.subdomain|escape}.{$base_domain}/{$product.slug|default:$product.id}" target="_blank" class="btn-icon" title="View in shop">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                </a>
                                {/if}
                                <button type="button" class="btn-icon btn-icon-danger admin-delete-product" data-id="{$product.id}" title="Delete product">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                {/foreach}
                </tbody>
            </table>
        </div>

        {if $total_pages > 1}
        <div class="pagination">
            {if $current_page > 1}
                <a href="/admin/products?page={$current_page - 1}{if $search}&q={$search|escape:'url'}{/if}" class="btn btn-sm btn-outline">&larr; Prev</a>
            {/if}
            <span class="pagination-info">Page {$current_page} of {$total_pages}</span>
            {if $current_page < $total_pages}
                <a href="/admin/products?page={$current_page + 1}{if $search}&q={$search|escape:'url'}{/if}" class="btn btn-sm btn-outline">Next &rarr;</a>
            {/if}
        </div>
        {/if}
    {/if}
</div>
{/block}

{block name="extra_scripts"}
<script>
document.querySelectorAll('.admin-delete-product').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var id = this.dataset.id;
        if (!confirm('Delete this product? This cannot be undone.')) return;
        var row = this.closest('tr');
        fetch('/api/admin/products/' + id, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' }
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.success) {
                row.remove();
                TinyShop.toast('Product deleted', 'success');
            }
        });
    });
});
</script>
{/block}
