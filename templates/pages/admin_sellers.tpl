{extends file="layouts/admin.tpl"}

{block name="content"}
<div class="dash-topbar">
    <span class="dash-topbar-title">Sellers ({$total})</span>
</div>

<div class="admin-toolbar">
    <form method="get" action="/admin/sellers" class="admin-search">
        <input type="text" name="q" value="{$search|escape}" placeholder="Search sellers..." class="form-control form-control-sm" autocomplete="off">
        {if $search}<a href="/admin/sellers" class="admin-search-clear" title="Clear">&times;</a>{/if}
    </form>
</div>

<div class="admin-list-wrap">
    {if $sellers|count == 0}
        <div class="empty-state">
            <div class="empty-icon">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#AEAEB2" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <h2>{if $search}No sellers matching "{$search|escape}"{else}No sellers have signed up yet{/if}</h2>
        </div>
    {else}
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Seller</th>
                        <th>Shop</th>
                        <th>Joined</th>
                        <th>Logins</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                {foreach $sellers as $seller}
                    <tr>
                        <td>
                            <a href="/admin/sellers/{$seller.id}" class="seller-cell">
                                <strong>{$seller.name|escape}</strong>
                                <small>{$seller.email|escape}</small>
                            </a>
                        </td>
                        <td>
                            {if $seller.subdomain}
                                <a href="{$scheme}://{$seller.subdomain|escape}.{$base_domain}" target="_blank">{$seller.store_name|escape:'html'|default:$seller.subdomain}</a>
                            {else}
                                <span class="text-muted">&mdash;</span>
                            {/if}
                        </td>
                        <td><small>{$seller.created_at|date_format:"%b %e, %Y"}</small></td>
                        <td>{$seller.login_count}</td>
                        <td>
                            <button
                                type="button"
                                class="status-toggle {if $seller.is_active}active{/if}"
                                data-id="{$seller.id}"
                                data-active="{$seller.is_active}"
                                title="{if $seller.is_active}Suspend{else}Activate{/if}"
                            >
                                {if $seller.is_active}Active{else}Suspended{/if}
                            </button>
                        </td>
                        <td>
                            <a href="/admin/sellers/{$seller.id}" class="btn-icon" title="View details">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                            </a>
                        </td>
                    </tr>
                {/foreach}
                </tbody>
            </table>
        </div>

        {if $total_pages > 1}
        <div class="pagination">
            {if $current_page > 1}
                <a href="/admin/sellers?page={$current_page - 1}{if $search}&q={$search|escape:'url'}{/if}" class="btn btn-sm btn-outline">&larr; Prev</a>
            {/if}
            <span class="pagination-info">Page {$current_page} of {$total_pages}</span>
            {if $current_page < $total_pages}
                <a href="/admin/sellers?page={$current_page + 1}{if $search}&q={$search|escape:'url'}{/if}" class="btn btn-sm btn-outline">Next &rarr;</a>
            {/if}
        </div>
        {/if}
    {/if}
</div>
{/block}

{block name="extra_scripts"}
<script>
(function() {ldelim}
    $('.status-toggle').on('click', function(e) {ldelim}
        e.stopPropagation();
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
{rdelim})();
</script>
{/block}
