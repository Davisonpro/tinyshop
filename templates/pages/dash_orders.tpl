{extends file="layouts/dashboard.tpl"}

{block name="content"}
<div class="dash-topbar">
    <span class="dash-topbar-title">Orders</span>
    <a href="/dashboard/shop" class="dash-topbar-avatar">{if $user.shop_logo}<img src="{$user.shop_logo|escape}" alt="">{else}{$user.name|escape|substr:0:1|upper}{/if}</a>
</div>

{* Stats overview *}
<div class="dash-stats" id="orderStats" style="display:none">
    <div class="stat-card">
        <div class="stat-number" id="statTotal">0</div>
        <div class="stat-label">Total</div>
    </div>
    <div class="stat-card">
        <div class="stat-number" id="statPending">0</div>
        <div class="stat-label">Pending</div>
    </div>
    <div class="stat-card">
        <div class="stat-number" id="statCompleted">0</div>
        <div class="stat-label">Completed</div>
    </div>
    <div class="stat-card">
        <div class="stat-number" id="statRevenue">0</div>
        <div class="stat-label">Revenue</div>
    </div>
</div>

{* Status filter tabs *}
<div class="category-filter-bar" id="orderFilterBar" style="display:none">
    <button class="category-tab active" data-filter="all">All</button>
    <button class="category-tab" data-filter="pending">Pending</button>
    <button class="category-tab" data-filter="paid">Completed</button>
    <button class="category-tab" data-filter="cancelled">Cancelled</button>
</div>

{* Order list *}
<div id="orderList" class="order-list" style="padding:8px 20px 100px">
    <div class="skeleton-order"><div class="skeleton-order-top"><div class="skeleton-line" style="width:55%;height:14px"></div><div class="skeleton-line" style="width:25%;height:14px"></div></div><div class="skeleton-order-bottom"><div class="skeleton-line" style="width:35%;height:10px"></div><div class="skeleton-line" style="width:20%;height:10px"></div></div></div>
    <div class="skeleton-order"><div class="skeleton-order-top"><div class="skeleton-line" style="width:45%;height:14px"></div><div class="skeleton-line" style="width:30%;height:14px"></div></div><div class="skeleton-order-bottom"><div class="skeleton-line" style="width:30%;height:10px"></div><div class="skeleton-line" style="width:22%;height:10px"></div></div></div>
    <div class="skeleton-order"><div class="skeleton-order-top"><div class="skeleton-line" style="width:60%;height:14px"></div><div class="skeleton-line" style="width:20%;height:14px"></div></div><div class="skeleton-order-bottom"><div class="skeleton-line" style="width:40%;height:10px"></div><div class="skeleton-line" style="width:18%;height:10px"></div></div></div>
</div>

{* FAB - Add Order *}
<a href="javascript:void(0)" class="fab" id="addOrderFab" title="Log Order" aria-label="Log a new order">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
    </svg>
</a>
{/block}

{block name="extra_scripts"}
<script>
var _orderConfig = {
    currency: '{$currency|escape:"javascript"}'
};
</script>
<script>
$(function() {
    var _currency = _orderConfig.currency || 'KES';
    var _orders = [];
    var _filter = 'all';

    function loadOrders() {
        TinyShop.api('GET', '/api/orders').done(function(res) {
            _orders = res.orders || [];
            updateStats(res.stats || {});
            renderOrders();
        }).fail(function() {
            $('#orderList').html('<div class="empty-state"><p>Failed to load orders.</p></div>');
        });
    }

    function updateStats(stats) {
        var $statsEl = $('#orderStats');
        if (parseInt(stats.total) > 0) {
            $('#statTotal').text(stats.total || 0);
            $('#statPending').text(stats.pending || 0);
            $('#statCompleted').text(stats.completed || 0);
            $('#statRevenue').text(TinyShop.formatPrice(stats.revenue || 0, _currency));
            $statsEl.show();
        }
    }

    function renderOrders() {
        var filtered = _orders;
        if (_filter !== 'all') {
            filtered = _orders.filter(function(o) { return o.status === _filter; });
        }

        if (_orders.length > 0) {
            $('#orderFilterBar').show();
        }

        if (filtered.length === 0) {
            var msg = _orders.length === 0
                ? '<div class="empty-state"><div class="empty-icon"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#AEAEB2" stroke-width="1.5"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg></div><h2>No orders yet</h2><p>Tap + to log your first order from WhatsApp</p></div>'
                : '<div class="empty-state"><p>No ' + _filter + ' orders</p></div>';
            $('#orderList').html(msg);
            return;
        }

        var html = '';
        filtered.forEach(function(o) {
            var statusClass = 'order-status-' + o.status;
            var statusLabel = o.status === 'paid' ? 'Completed' : o.status.charAt(0).toUpperCase() + o.status.slice(1);
            var date = new Date(o.created_at);
            var dateStr = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });

            html += '<div class="order-card" data-id="' + o.id + '">' +
                '<div class="order-card-top">' +
                    '<div class="order-card-customer">' +
                        '<div class="order-card-name">' + escapeHtml(o.customer_name || 'Customer') + '</div>' +
                        (o.customer_phone ? '<div class="order-card-phone">' + escapeHtml(o.customer_phone) + '</div>' : '') +
                    '</div>' +
                    '<div class="order-card-amount">' + TinyShop.formatPrice(o.amount, _currency) + '</div>' +
                '</div>' +
                '<div class="order-card-bottom">' +
                    '<span class="order-status ' + statusClass + '">' + statusLabel + '</span>' +
                    '<span class="order-card-date">' + dateStr + '</span>' +
                '</div>' +
            '</div>';
        });
        $('#orderList').html(html);
    }

    // Filter tabs
    $('#orderFilterBar').on('click', '.category-tab', function() {
        $('#orderFilterBar .category-tab').removeClass('active');
        $(this).addClass('active');
        _filter = $(this).data('filter');
        renderOrders();
    });

    // Click order card to show details
    $('#orderList').on('click', '.order-card', function() {
        var id = $(this).data('id');
        var order = _orders.find(function(o) { return parseInt(o.id) === parseInt(id); });
        if (!order) return;

        var statusOptions = ['pending', 'paid', 'cancelled', 'refunded'];
        var statusHtml = '';
        statusOptions.forEach(function(s) {
            var label = s === 'paid' ? 'Completed' : s.charAt(0).toUpperCase() + s.slice(1);
            var checked = order.status === s ? ' checked' : '';
            statusHtml += '<label class="order-status-option">' +
                '<input type="radio" name="orderStatus" value="' + s + '"' + checked + '>' +
                '<span class="order-status order-status-' + s + '">' + label + '</span>' +
            '</label>';
        });

        var html = '<div style="margin-bottom:16px">' +
            '<div style="font-weight:700;font-size:1.125rem;margin-bottom:2px">' + escapeHtml(order.customer_name || 'Customer') + '</div>' +
            (order.customer_phone ? '<div style="color:var(--color-text-muted);font-size:0.875rem">' + escapeHtml(order.customer_phone) + '</div>' : '') +
        '</div>' +
        '<div style="padding:14px;background:var(--color-bg-secondary);border-radius:12px;margin-bottom:16px">' +
            '<div style="display:flex;justify-content:space-between;align-items:center">' +
                '<span style="color:var(--color-text-muted);font-size:0.8125rem">Amount</span>' +
                '<span style="font-weight:800;font-size:1.125rem">' + TinyShop.formatPrice(order.amount, _currency) + '</span>' +
            '</div>' +
            (order.reference_id ? '<div style="margin-top:8px;font-size:0.8125rem;color:var(--color-text-muted)">Notes: ' + escapeHtml(order.reference_id) + '</div>' : '') +
        '</div>' +
        '<div class="form-group"><label style="font-size:0.8125rem;font-weight:600;margin-bottom:10px;display:block">Status</label>' +
            '<div class="order-status-options">' + statusHtml + '</div>' +
        '</div>' +
        '<div style="display:flex;gap:10px;margin-top:20px">' +
            '<button type="button" id="deleteOrderBtn" style="flex:0 0 auto;min-height:48px;padding:0 20px;font-size:0.875rem;font-weight:600;border-radius:12px;background:none;color:#FF3B30;border:1.5px solid #FF3B30;cursor:pointer;font-family:inherit">Delete</button>' +
            (order.customer_phone ? '<a href="https://wa.me/' + escapeHtml(order.customer_phone.replace(/[^0-9+]/g, '')) + '" target="_blank" rel="noopener" style="flex:1;display:flex;align-items:center;justify-content:center;min-height:48px;font-size:1rem;font-weight:600;border-radius:12px;background:#25D366;color:#fff;border:none;cursor:pointer;font-family:inherit;text-decoration:none;gap:6px"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 2C6.477 2 2 6.477 2 12c0 1.89.525 3.66 1.438 5.168L2 22l4.832-1.438A9.955 9.955 0 0 0 12 22c5.523 0 10-4.477 10-10S17.523 2 12 2z"/></svg> Chat</a>' : '') +
        '</div>';

        TinyShop.openModal('Order #' + order.id, html);

        // Status change
        $('#modalBody input[name="orderStatus"]').on('change', function() {
            var newStatus = $(this).val();
            TinyShop.api('PUT', '/api/orders/' + order.id + '/status', { status: newStatus }).done(function() {
                var idx = _orders.findIndex(function(o) { return parseInt(o.id) === parseInt(order.id); });
                if (idx !== -1) _orders[idx].status = newStatus;
                TinyShop.toast('Status updated');
                renderOrders();
                loadOrders();
            }).fail(function(xhr) {
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to update';
                TinyShop.toast(msg, 'error');
            });
        });

        // Delete order
        $('#deleteOrderBtn').on('click', function() {
            if (!confirm('Delete this order?')) return;
            TinyShop.api('DELETE', '/api/orders/' + order.id).done(function() {
                _orders = _orders.filter(function(o) { return parseInt(o.id) !== parseInt(order.id); });
                TinyShop.toast('Order deleted');
                TinyShop.closeModal();
                renderOrders();
                loadOrders();
            }).fail(function() {
                TinyShop.toast('Failed to delete', 'error');
            });
        });
    });

    // Add order FAB
    $('#addOrderFab').on('click', function() {
        var html = '<form id="addOrderForm" autocomplete="off">' +
            '<div class="form-group">' +
                '<label for="orderCustomerName">Customer Name</label>' +
                '<input type="text" class="form-control" id="orderCustomerName" placeholder="e.g. John" required autofocus autocomplete="off">' +
            '</div>' +
            '<div class="form-group">' +
                '<label for="orderCustomerPhone">Phone (optional)</label>' +
                '<input type="tel" class="form-control" id="orderCustomerPhone" placeholder="e.g. 254712345678" inputmode="numeric" autocomplete="off">' +
            '</div>' +
            '<div class="form-group">' +
                '<label for="orderAmount">Amount (' + escapeHtml(_currency) + ')</label>' +
                '<input type="text" class="form-control price-input" id="orderAmount" placeholder="0" inputmode="decimal" required autocomplete="off">' +
            '</div>' +
            '<div class="form-group">' +
                '<label for="orderNotes">Notes (optional)</label>' +
                '<input type="text" class="form-control" id="orderNotes" placeholder="e.g. Red size M" autocomplete="off">' +
            '</div>' +
            '<button type="submit" class="btn btn-primary" id="saveOrderBtn" style="width:100%;min-height:52px;font-size:1rem;font-weight:600;border-radius:14px;border:none;cursor:pointer;font-family:inherit;">Log Order</button>' +
        '</form>';
        TinyShop.openModal('Log New Order', html);

        TinyShop.initPriceInput($('#orderAmount'));

        $('#addOrderForm').on('submit', function(e) {
            e.preventDefault();
            var name = $('#orderCustomerName').val().trim();
            var phone = $('#orderCustomerPhone').val().trim();
            var amountRaw = $('#orderAmount').val().replace(/,/g, '');
            var notes = $('#orderNotes').val().trim();
            if (!name || !amountRaw) return;

            var $btn = $('#saveOrderBtn').prop('disabled', true).text('Saving...');
            TinyShop.api('POST', '/api/orders', {
                customer_name: name,
                customer_phone: phone,
                amount: parseFloat(amountRaw),
                notes: notes
            }).done(function(res) {
                _orders.unshift(res.order);
                TinyShop.toast('Order logged!');
                TinyShop.closeModal();
                renderOrders();
                loadOrders();
            }).fail(function(xhr) {
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to save';
                TinyShop.toast(msg, 'error');
                $btn.prop('disabled', false).text('Log Order');
            });
        });
    });

    loadOrders();
});
</script>
{/block}
