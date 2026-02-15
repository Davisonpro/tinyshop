{extends file="layouts/admin.tpl"}

{block name="content"}
<div class="dash-topbar">
    <span class="dash-topbar-title">All Orders ({$total})</span>
</div>

<div class="admin-toolbar">
    <div class="admin-filter-tabs">
        <a href="/admin/orders" class="filter-tab{if !$status} active{/if}">All</a>
        <a href="/admin/orders?status=pending" class="filter-tab{if $status == 'pending'} active{/if}">Pending</a>
        <a href="/admin/orders?status=paid" class="filter-tab{if $status == 'paid'} active{/if}">Paid</a>
        <a href="/admin/orders?status=cancelled" class="filter-tab{if $status == 'cancelled'} active{/if}">Cancelled</a>
    </div>
</div>

<div class="admin-list-wrap">
    {if $orders|count == 0}
        <div class="empty-state">
            <p>{if $status}No {$status} orders{else}No orders yet.{/if}</p>
        </div>
    {else}
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Customer</th>
                        <th>Seller</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                {foreach $orders as $order}
                    <tr data-id="{$order.id}">
                        <td><small>#{$order.id}</small></td>
                        <td>
                            <div class="seller-cell">
                                <strong>{$order.customer_name|escape|default:'—'}</strong>
                                <small>{$order.customer_phone|escape|default:''}</small>
                            </div>
                        </td>
                        <td>
                            <a href="/admin/sellers/{$order.user_id}">{$order.store_name|escape|default:$order.seller_name|escape}</a>
                        </td>
                        <td>{$order.amount|number_format:2}</td>
                        <td>
                            <select class="status-select admin-order-status" data-id="{$order.id}">
                                <option value="pending"{if $order.status == 'pending'} selected{/if}>Pending</option>
                                <option value="paid"{if $order.status == 'paid'} selected{/if}>Paid</option>
                                <option value="cancelled"{if $order.status == 'cancelled'} selected{/if}>Cancelled</option>
                            </select>
                        </td>
                        <td><small>{$order.created_at|date_format:"%b %e, %Y"}</small></td>
                        <td>
                            <div class="admin-actions">
                                <a href="/admin/sellers/{$order.user_id}" class="btn-icon" title="View seller">
                                    <i class="fa-solid fa-eye icon-md"></i>
                                </a>
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
                <a href="/admin/orders?page={$current_page - 1}{if $status}&status={$status|escape:'url'}{/if}" class="btn btn-sm btn-outline">&larr; Prev</a>
            {/if}
            <span class="pagination-info">Page {$current_page} of {$total_pages}</span>
            {if $current_page < $total_pages}
                <a href="/admin/orders?page={$current_page + 1}{if $status}&status={$status|escape:'url'}{/if}" class="btn btn-sm btn-outline">Next &rarr;</a>
            {/if}
        </div>
        {/if}
    {/if}
</div>
{/block}

{block name="extra_scripts"}
<script>
document.querySelectorAll('.admin-order-status').forEach(function(sel) {
    sel.addEventListener('change', function() {
        var id = this.dataset.id;
        var newStatus = this.value;
        fetch('/api/admin/orders/' + id + '/status', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ status: newStatus })
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.success) TinyShop.toast('Order status updated', 'success');
        });
    });
});
</script>
{/block}
