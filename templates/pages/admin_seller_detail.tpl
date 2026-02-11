{extends file="layouts/admin.tpl"}

{block name="content"}
<div class="dash-topbar">
    <a href="/admin/sellers" class="dash-topbar-back" aria-label="Back to sellers">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
    </a>
    <span class="dash-topbar-title">{$seller.store_name|escape|default:$seller.name|escape}</span>
    <div class="admin-actions">
        {if $seller.subdomain}
        <a href="{$scheme}://{$seller.subdomain|escape}.{$base_domain}" target="_blank" class="btn-icon" title="Visit shop">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
        </a>
        {/if}
    </div>
</div>

<div class="seller-profile-wrap">
    <div class="seller-info-card">
        <div class="seller-info-row">
            <div class="seller-avatar-lg">
                {$seller.name|escape|substr:0:1|upper}
            </div>
            <div>
                <h2 class="seller-info-name">{$seller.name|escape}</h2>
                <p class="seller-info-email">{$seller.email|escape}</p>
                {if $seller.subdomain}
                    <p class="seller-info-sub">{$seller.subdomain|escape}.{$base_domain}</p>
                {/if}
            </div>
        </div>

        <div class="seller-meta">
            <div class="seller-meta-item">
                <span class="seller-meta-label">Joined</span>
                <span>{$seller.created_at|date_format:"%b %e, %Y"}</span>
            </div>
            <div class="seller-meta-item">
                <span class="seller-meta-label">Last login</span>
                <span>{if $seller.last_login_at}{$seller.last_login_at|date_format:"%b %e, %Y %H:%M"}{else}Never{/if}</span>
            </div>
            <div class="seller-meta-item">
                <span class="seller-meta-label">Logins</span>
                <span>{$seller.login_count}</span>
            </div>
            <div class="seller-meta-item">
                <span class="seller-meta-label">Currency</span>
                <span>{$seller.currency|default:'KES'}</span>
            </div>
            <div class="seller-meta-item">
                <span class="seller-meta-label">Status</span>
                <span>
                    <button type="button" class="status-toggle{if $seller.is_active} active{/if}" id="sellerToggle" data-id="{$seller.id}" data-active="{$seller.is_active}">
                        {if $seller.is_active}Active{else}Suspended{/if}
                    </button>
                </span>
            </div>
        </div>
    </div>

    <div class="dash-stats admin-stats-3 seller-detail-stats">
        <div class="stat-card">
            <div class="stat-number">{$seller.product_count}</div>
            <div class="stat-label">Products</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{$seller.order_count}</div>
            <div class="stat-label">Orders</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{$seller.view_count}</div>
            <div class="stat-label">Views</div>
        </div>
    </div>

    <div class="seller-action-buttons">
        <a href="/admin/impersonate/{$seller.id}" class="btn btn-sm btn-outline" id="impersonateBtn">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="btn-inline-icon"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Impersonate
        </a>
        <button type="button" class="btn btn-sm btn-danger" id="deleteSellerBtn" data-id="{$seller.id}">Delete Account</button>
    </div>

    {if $products|count > 0}
    <div class="dash-section seller-products-section">
        <div class="dash-section-header">
            <h2>Products ({$products|count})</h2>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                {foreach $products as $product}
                    <tr>
                        <td>
                            {if $seller.subdomain}
                                <a href="{$scheme}://{$seller.subdomain|escape}.{$base_domain}/{$product.slug|default:$product.id}" target="_blank">{$product.name|escape|truncate:40}</a>
                            {else}
                                {$product.name|escape|truncate:40}
                            {/if}
                        </td>
                        <td>{$product.price|number_format:2}</td>
                        <td>
                            {if $product.is_active}<span class="badge badge-green">Active</span>{else}<span class="badge badge-muted">Hidden</span>{/if}
                            {if $product.is_sold}<span class="badge badge-orange">Sold</span>{/if}
                            {if $product.is_featured}<span class="badge badge-purple">Featured</span>{/if}
                        </td>
                        <td><small>{$product.created_at|date_format:"%b %e, %Y"}</small></td>
                    </tr>
                {/foreach}
                </tbody>
            </table>
        </div>
    </div>
    {/if}
</div>
{/block}

{block name="extra_scripts"}
<script>
(function() {ldelim}
    $('#sellerToggle').on('click', function() {ldelim}
        var $el = $(this);
        var id = $el.data('id');
        var currentlyActive = String($el.data('active')) === '1';
        var newState = !currentlyActive;

        TinyShop.api('PUT', '/api/admin/sellers/' + id + '/toggle', {ldelim} is_active: newState {rdelim})
            .done(function(res) {ldelim}
                if (res.success) {ldelim}
                    $el.data('active', newState ? '1' : '0');
                    $el.text(newState ? 'Active' : 'Suspended');
                    $el.toggleClass('active', newState);
                    TinyShop.toast(newState ? 'Seller activated' : 'Seller suspended', 'success');
                {rdelim}
            {rdelim})
            .fail(function() {ldelim}
                TinyShop.toast('Failed to update status', 'error');
            {rdelim});
    {rdelim});

    $('#deleteSellerBtn').on('click', function() {ldelim}
        var id = $(this).data('id');

        var html = '<p class="modal-confirm-text">This will permanently delete this seller account and ALL their data (products, orders, etc.). This cannot be undone.</p>' +
            '<div class="modal-confirm-actions">' +
                '<button type="button" class="btn btn-sm btn-outline" id="confirmDeleteCancel">Cancel</button>' +
                '<button type="button" class="btn btn-sm btn-danger" id="confirmDeleteYes">Delete</button>' +
            '</div>';
        TinyShop.openModal('Delete Account?', html);

        $('#confirmDeleteCancel').on('click', function() {ldelim}
            TinyShop.closeModal();
        {rdelim});

        $('#confirmDeleteYes').on('click', function() {ldelim}
            $(this).prop('disabled', true).text('Deleting...');
            TinyShop.api('DELETE', '/api/admin/sellers/' + id)
                .done(function(res) {ldelim}
                    if (res.success) {ldelim}
                        TinyShop.toast('Account deleted', 'success');
                        setTimeout(function() {ldelim} window.location.href = '/admin/sellers'; {rdelim}, 800);
                    {rdelim}
                {rdelim})
                .fail(function(xhr) {ldelim}
                    var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to delete';
                    TinyShop.toast(msg, 'error');
                    TinyShop.closeModal();
                {rdelim});
        {rdelim});
    {rdelim});
{rdelim})();
</script>
{/block}
