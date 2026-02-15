{extends file="layouts/dashboard.tpl"}

{block name="content"}
<div class="dash-topbar">
    <span class="dash-topbar-title">Orders</span>
    <a href="/dashboard/shop" class="dash-topbar-avatar">{$user.store_name|default:$user.name|escape|substr:0:1|upper}</a>
</div>

{* Stats overview *}
<div class="stats-panel" id="orderStats" style="display:none">
    <div class="stats-panel-grid stats-panel-3col">
        <div class="stats-panel-metric">
            <div class="stats-panel-number" id="statTotal">0</div>
            <div class="stats-panel-label">Total</div>
        </div>
        <div class="stats-panel-metric">
            <div class="stats-panel-number" id="statPending">0</div>
            <div class="stats-panel-label">Pending</div>
        </div>
        <div class="stats-panel-metric">
            <div class="stats-panel-number" id="statRevenue">0</div>
            <div class="stats-panel-label">Revenue</div>
        </div>
    </div>
</div>

{* Search bar *}
<div class="product-search-bar" id="orderSearchBar" style="display:none">
    <i class="fa-solid fa-magnifying-glass"></i>
    <input type="text" id="orderSearch" placeholder="Search by name, phone, or order #..." autocomplete="off" aria-label="Search orders">
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
    <i class="fa-solid fa-plus"></i>
</a>
{/block}

{block name="extra_scripts"}
<script>
var _orderConfig = {ldelim}
    currency: '{$currency|escape:"javascript"}'
{rdelim};
</script>
<script>
$(function() {ldelim}
    var _currency = _orderConfig.currency || 'KES';
    var _orders = [];
    var _filter = 'all';
    var _search = '';
    var _products = null;

    function loadOrders() {ldelim}
        TinyShop.api('GET', '/api/orders').done(function(res) {ldelim}
            _orders = res.orders || [];
            updateStats(res.stats || {ldelim}{rdelim});
            renderOrders();
        {rdelim}).fail(function() {ldelim}
            $('#orderList').html('<div class="empty-state"><p>Failed to load orders.</p></div>');
        {rdelim});
    {rdelim}

    function updateStats(stats) {ldelim}
        var $statsEl = $('#orderStats');
        if (parseInt(stats.total) > 0) {ldelim}
            $('#statTotal').text(Number(stats.total || 0).toLocaleString());
            $('#statPending').text(Number(stats.pending || 0).toLocaleString());
            var revFormatted = TinyShop.formatPrice(stats.revenue || 0, _currency);
            var revParts = revFormatted.split(' ');
            if (revParts.length > 1) {ldelim}
                $('#statRevenue').html('<span class="stats-panel-currency">' + revParts[0] + '</span> ' + revParts.slice(1).join(' '));
            {rdelim} else {ldelim}
                $('#statRevenue').text(revFormatted);
            {rdelim}
            $statsEl.show();
        {rdelim}
    {rdelim}

    function matchesSearch(o) {ldelim}
        if (!_search) return true;
        var q = _search.toLowerCase();
        var name = (o.customer_name || '').toLowerCase();
        var phone = (o.customer_phone || '').toLowerCase();
        var email = (o.customer_email || '').toLowerCase();
        var orderNum = (o.order_number || '').toLowerCase();
        return name.indexOf(q) !== -1 || phone.indexOf(q) !== -1 ||
               email.indexOf(q) !== -1 || orderNum.indexOf(q) !== -1;
    {rdelim}

    function renderOrders() {ldelim}
        var filtered = _orders;
        if (_filter !== 'all') {ldelim}
            filtered = filtered.filter(function(o) {ldelim} return o.status === _filter; {rdelim});
        {rdelim}
        if (_search) {ldelim}
            filtered = filtered.filter(matchesSearch);
        {rdelim}

        if (_orders.length > 0) {ldelim}
            $('#orderFilterBar').show();
            $('#orderSearchBar').show();
        {rdelim}

        if (filtered.length === 0) {ldelim}
            var msg;
            if (_orders.length === 0) {ldelim}
                msg = '<div class="empty-state">' +
                    '<div class="empty-icon"><i class="fa-solid fa-bag-shopping icon-2xl text-muted"></i></div>' +
                    '<h2>You\'re all set up</h2>' +
                    '<p>Orders will show here when customers start buying</p>' +
                '</div>';
            {rdelim} else if (_search) {ldelim}
                msg = '<div class="empty-state"><p>No orders matching "' + escapeHtml(_search) + '"</p></div>';
            {rdelim} else {ldelim}
                msg = '<div class="empty-state"><p>No ' + _filter + ' orders right now</p></div>';
            {rdelim}
            $('#orderList').html(msg);
            return;
        {rdelim}

        var html = '';
        filtered.forEach(function(o) {ldelim}
            var statusClass = 'order-status-' + o.status;
            var statusLabel = o.status === 'paid' ? 'Completed' : o.status.charAt(0).toUpperCase() + o.status.slice(1);
            var date = new Date(o.created_at);
            var dateStr = date.toLocaleDateString('en-US', {ldelim} month: 'short', day: 'numeric' {rdelim});

            var itemSummary = '';
            var items = o.items || [];
            var itemCount = parseInt(o.item_count) || items.length;
            if (items.length > 0) {ldelim}
                itemSummary = escapeHtml(items[0].product_name);
                if (itemCount > 1) itemSummary += ' + ' + (itemCount - 1) + ' more';
            {rdelim}

            var gatewayBadge = '';
            var gw = o.payment_gateway || o.payment_method || '';
            if (gw === 'stripe') gatewayBadge = '<span class="order-gateway-badge order-gateway-stripe">Stripe</span>';
            else if (gw === 'paypal') gatewayBadge = '<span class="order-gateway-badge order-gateway-paypal">PayPal</span>';
            else if (gw && gw !== 'whatsapp' && gw !== 'manual') gatewayBadge = '<span class="order-gateway-badge">' + escapeHtml(gw) + '</span>';

            html += '<div class="order-card" data-id="' + o.id + '">' +
                '<div class="order-card-top">' +
                    '<div class="order-card-customer">' +
                        '<div class="order-card-name">' + escapeHtml(o.customer_name || 'Customer') + '</div>' +
                        (itemSummary ? '<div class="order-card-phone">' + itemSummary + '</div>' :
                        (o.customer_phone ? '<div class="order-card-phone">' + escapeHtml(o.customer_phone) + '</div>' : '')) +
                    '</div>' +
                    '<div class="order-card-amount">' + TinyShop.formatPrice(o.amount, _currency) + '</div>' +
                '</div>' +
                '<div class="order-card-bottom">' +
                    '<div style="display:flex;align-items:center;gap:6px">' +
                        '<span class="order-status ' + statusClass + '">' + statusLabel + '</span>' +
                        gatewayBadge +
                    '</div>' +
                    '<span class="order-card-date">' + (o.order_number ? '#' + escapeHtml(o.order_number) + ' &middot; ' : '') + dateStr + '</span>' +
                '</div>' +
            '</div>';
        {rdelim});
        $('#orderList').html(html);
    {rdelim}

    // Search
    var _searchTimer;
    $('#orderSearch').on('input', function() {ldelim}
        var q = $(this).val().trim();
        clearTimeout(_searchTimer);
        _searchTimer = setTimeout(function() {ldelim}
            _search = q;
            renderOrders();
        {rdelim}, 200);
    {rdelim});

    // Filter tabs
    $('#orderFilterBar').on('click', '.category-tab', function() {ldelim}
        $('#orderFilterBar .category-tab').removeClass('active');
        $(this).addClass('active');
        _filter = $(this).data('filter');
        renderOrders();
    {rdelim});

    // ── Order Detail Modal ──
    $('#orderList').on('click', '.order-card', function() {ldelim}
        var id = $(this).data('id');
        var order = _orders.find(function(o) {ldelim} return parseInt(o.id) === parseInt(id); {rdelim});
        if (!order) return;
        showOrderDetail(order);
    {rdelim});

    function showOrderDetail(order) {ldelim}
        var statusOptions = ['pending', 'paid', 'cancelled', 'refunded'];
        var statusHtml = '';
        statusOptions.forEach(function(s) {ldelim}
            var label = s === 'paid' ? 'Completed' : s.charAt(0).toUpperCase() + s.slice(1);
            var checked = order.status === s ? ' checked' : '';
            statusHtml += '<label class="order-status-option">' +
                '<input type="radio" name="orderStatus" value="' + s + '"' + checked + '>' +
                '<span class="order-status order-status-' + s + '">' + label + '</span>' +
            '</label>';
        {rdelim});

        var date = new Date(order.created_at);
        var dateStr = date.toLocaleDateString('en-US', {ldelim} month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' {rdelim});

        // Customer info
        var html = '<div style="margin-bottom:16px">' +
            '<div style="font-weight:700;font-size:1.125rem;margin-bottom:2px">' + escapeHtml(order.customer_name || 'Customer') + '</div>' +
            '<div style="font-size:0.75rem;color:var(--color-text-muted)">' + escapeHtml(dateStr) + '</div>' +
        '</div>';

        // Contact chips
        var hasContact = order.customer_email || order.customer_phone;
        if (hasContact) {ldelim}
            html += '<div class="order-contact-row">';
            if (order.customer_phone) {ldelim}
                var cleanPhone = order.customer_phone.replace(/[^0-9+]/g, '');
                html += '<a href="https://wa.me/' + escapeHtml(cleanPhone) + '" target="_blank" rel="noopener" class="order-contact-chip order-contact-whatsapp">' +
                    '<i class="fa-brands fa-whatsapp icon-sm"></i>' +
                    escapeHtml(order.customer_phone) +
                '</a>';
            {rdelim}
            if (order.customer_email) {ldelim}
                html += '<a href="mailto:' + escapeHtml(order.customer_email) + '" class="order-contact-chip order-contact-email">' +
                    '<i class="fa-solid fa-envelope icon-sm"></i>' +
                    escapeHtml(order.customer_email) +
                '</a>';
            {rdelim}
            html += '</div>';
        {rdelim}

        // Order items
        var orderItems = order.items || [];
        if (orderItems.length > 0) {ldelim}
            html += '<div class="order-detail-items">';
            orderItems.forEach(function(item, idx) {ldelim}
                if (idx > 0) html += '<div class="order-detail-item-divider"></div>';
                var imgSrc = item.product_image || '/public/img/placeholder.svg';
                html += '<div class="order-detail-item">' +
                    '<img src="' + escapeHtml(imgSrc) + '" alt="" class="order-detail-item-img">' +
                    '<div class="order-detail-item-info">' +
                        '<div class="order-detail-item-name">' + escapeHtml(item.product_name) + '</div>' +
                        '<div class="order-detail-item-meta">' +
                            (item.variation ? escapeHtml(item.variation) + ' &middot; ' : '') +
                            'Qty: ' + item.quantity +
                        '</div>' +
                    '</div>' +
                    '<div class="order-detail-item-price">' + TinyShop.formatPrice(item.total, _currency) + '</div>' +
                '</div>';
            {rdelim});
            html += '</div>';
        {rdelim}

        // Total
        html += '<div class="order-detail-total">' +
            '<div style="display:flex;justify-content:space-between;align-items:center">' +
                '<span>Total</span>' +
                '<span class="order-detail-total-amount">' + TinyShop.formatPrice(order.amount, _currency) + '</span>' +
            '</div>';
        var gw = order.payment_gateway || order.payment_method || '';
        if (gw && gw !== 'manual' && gw !== 'whatsapp') {ldelim}
            html += '<div style="margin-top:6px;font-size:0.75rem;color:var(--color-text-muted)">Paid via ' + escapeHtml(gw.charAt(0).toUpperCase() + gw.slice(1)) + '</div>';
        {rdelim}
        if (order.notes || order.reference_id) {ldelim}
            html += '<div style="margin-top:6px;font-size:0.75rem;color:var(--color-text-muted)">Notes: ' + escapeHtml(order.notes || order.reference_id) + '</div>';
        {rdelim}
        html += '</div>';

        // Status
        html += '<div class="form-group" style="margin-top:16px"><label style="font-size:0.8125rem;font-weight:600;margin-bottom:10px;display:block">Status</label>' +
            '<div class="order-status-options">' + statusHtml + '</div>' +
            '<button type="button" id="updateStatusBtn" class="btn btn-block btn-primary mt-sm" style="display:none">Update Status</button>' +
        '</div>';

        // Delete
        html += '<button type="button" id="deleteOrderBtn" class="order-delete-btn">' +
            '<i class="fa-solid fa-trash icon-md"></i>' +
            'Delete Order' +
        '</button>';

        var modalTitle = order.order_number ? 'Order #' + escapeHtml(order.order_number) : 'Order #' + order.id;
        TinyShop.openModal(modalTitle, html);

        // Status change — show confirm button when selection differs from current
        var _origStatus = order.status;
        $('#modalBody input[name="orderStatus"]').on('change', function() {ldelim}
            var selected = $(this).val();
            if (selected !== _origStatus) {ldelim}
                var label = selected === 'paid' ? 'Completed' : selected.charAt(0).toUpperCase() + selected.slice(1);
                $('#updateStatusBtn').text('Mark as ' + label).show();
            {rdelim} else {ldelim}
                $('#updateStatusBtn').hide();
            {rdelim}
        {rdelim});

        // Confirm status update
        $('#updateStatusBtn').on('click', function() {ldelim}
            var newStatus = $('#modalBody input[name="orderStatus"]:checked').val();
            if (!newStatus || newStatus === _origStatus) return;
            var $btn = $(this).prop('disabled', true).text('Updating...');
            TinyShop.api('PUT', '/api/orders/' + order.id + '/status', {ldelim} status: newStatus {rdelim}).done(function() {ldelim}
                var idx = _orders.findIndex(function(o) {ldelim} return parseInt(o.id) === parseInt(order.id); {rdelim});
                if (idx !== -1) _orders[idx].status = newStatus;
                _origStatus = newStatus;
                TinyShop.toast('Status updated');
                $btn.hide().prop('disabled', false);
                renderOrders();
                loadOrders();
            {rdelim}).fail(function(xhr) {ldelim}
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to update';
                TinyShop.toast(msg, 'error');
                $btn.prop('disabled', false).text('Update Status');
            {rdelim});
        {rdelim});

        // Delete — proper confirmation modal
        $('#deleteOrderBtn').on('click', function() {ldelim}
            var confirmHtml = '<p style="margin-bottom:20px;color:var(--color-text-muted);font-size:0.9rem;line-height:1.5">' +
                'Delete this order for <strong>' + escapeHtml(order.customer_name || 'Customer') + '</strong>?' +
                (order.order_number ? ' (#' + escapeHtml(order.order_number) + ')' : '') +
                ' This can\'t be undone.' +
            '</p>' +
            '<div style="display:flex;gap:10px">' +
                '<button type="button" id="deleteCancel" style="flex:1;min-height:48px;font-size:1rem;font-weight:600;border-radius:12px;background:var(--color-bg-secondary);color:var(--color-text);border:none;cursor:pointer;font-family:inherit">Cancel</button>' +
                '<button type="button" id="deleteConfirm" style="flex:1;min-height:48px;font-size:1rem;font-weight:600;border-radius:12px;background:#FF3B30;color:#fff;border:none;cursor:pointer;font-family:inherit">Delete</button>' +
            '</div>';
            TinyShop.openModal('Delete Order?', confirmHtml);

            $('#deleteCancel').on('click', function() {ldelim} TinyShop.closeModal(); {rdelim});
            $('#deleteConfirm').on('click', function() {ldelim}
                var $btn = $(this).prop('disabled', true).text('Deleting...');
                TinyShop.api('DELETE', '/api/orders/' + order.id).done(function() {ldelim}
                    _orders = _orders.filter(function(o) {ldelim} return parseInt(o.id) !== parseInt(order.id); {rdelim});
                    TinyShop.toast('Order deleted');
                    TinyShop.closeModal();
                    renderOrders();
                    loadOrders();
                {rdelim}).fail(function() {ldelim}
                    TinyShop.toast('Failed to delete', 'error');
                    $btn.prop('disabled', false).text('Delete');
                {rdelim});
            {rdelim});
        {rdelim});
    {rdelim}

    // ── Log Order (FAB) ──
    function loadProducts(callback) {ldelim}
        if (_products !== null) {ldelim} callback(_products); return; {rdelim}
        TinyShop.api('GET', '/api/products').done(function(res) {ldelim}
            _products = (res.products || []).filter(function(p) {ldelim} return parseInt(p.is_active); {rdelim});
            callback(_products);
        {rdelim}).fail(function() {ldelim}
            _products = [];
            callback(_products);
        {rdelim});
    {rdelim}

    var _orderItems = [];

    $('#addOrderFab').on('click', function() {ldelim}
        _orderItems = [];
        loadProducts(function(products) {ldelim}
            showAddOrderModal(products);
        {rdelim});
    {rdelim});

    function showAddOrderModal(products) {ldelim}
        var html = '<form id="addOrderForm" autocomplete="off">' +
            '<div class="form-group">' +
                '<label for="orderCustomerName">Customer Name</label>' +
                '<input type="text" class="form-control" id="orderCustomerName" placeholder="e.g. John" required autofocus autocomplete="off">' +
            '</div>' +
            '<div class="form-group">' +
                '<label for="orderCustomerPhone">Phone (optional)</label>' +
                '<input type="tel" class="form-control" id="orderCustomerPhone" placeholder="e.g. 254712345678" inputmode="numeric" autocomplete="off">' +
            '</div>';

        if (products.length > 0) {ldelim}
            html += '<div class="form-group">' +
                '<label>Items</label>' +
                '<div id="orderItemsList"></div>' +
                '<button type="button" id="addProductBtn" class="order-add-product-btn">' +
                    '<i class="fa-solid fa-plus icon-md"></i>' +
                    'Add Product' +
                '</button>' +
            '</div>' +
            '<div id="orderTotalRow" style="display:none">' +
                '<div class="order-detail-total" style="margin-bottom:16px">' +
                    '<div style="display:flex;justify-content:space-between;align-items:center">' +
                        '<span>Total</span>' +
                        '<span class="order-detail-total-amount" id="orderCalcTotal">0</span>' +
                    '</div>' +
                '</div>' +
            '</div>';
        {rdelim}

        html += '<div class="form-group" id="manualAmountGroup"' + (products.length > 0 ? ' style="display:none"' : '') + '>' +
                '<label for="orderAmount">Amount (' + escapeHtml(_currency) + ')</label>' +
                '<input type="text" class="form-control price-input" id="orderAmount" placeholder="0" inputmode="decimal" ' + (products.length > 0 ? '' : 'required') + ' autocomplete="off">' +
            '</div>' +
            '<div class="form-group">' +
                '<label for="orderNotes">Notes (optional)</label>' +
                '<input type="text" class="form-control" id="orderNotes" placeholder="e.g. Red size M, deliver Tuesday" autocomplete="off">' +
            '</div>' +
            '<button type="submit" class="btn btn-block btn-primary" id="saveOrderBtn">Log Order</button>' +
        '</form>';

        TinyShop.openModal('Log New Order', html);
        TinyShop.initPriceInput($('#orderAmount'));

        function calcTotal() {ldelim}
            var total = 0;
            _orderItems.forEach(function(item) {ldelim} total += item.price * item.qty; {rdelim});
            $('#orderCalcTotal').text(TinyShop.formatPrice(total, _currency));
            $('#orderTotalRow').toggle(_orderItems.length > 0);
            if (products.length > 0) {ldelim}
                var showManual = _orderItems.length === 0;
                $('#manualAmountGroup').toggle(showManual);
                if (!showManual) $('#orderAmount').removeAttr('required');
                else $('#orderAmount').attr('required', 'required');
            {rdelim}
        {rdelim}

        function renderItems() {ldelim}
            var $list = $('#orderItemsList');
            if (_orderItems.length === 0) {ldelim}
                $list.empty();
                calcTotal();
                return;
            {rdelim}
            var h = '';
            _orderItems.forEach(function(item, idx) {ldelim}
                h += '<div class="order-line-item" data-idx="' + idx + '">' +
                    '<div class="order-line-item-info">' +
                        '<div class="order-line-item-name">' + escapeHtml(item.name) + '</div>' +
                        '<div class="order-line-item-price">' + TinyShop.formatPrice(item.price, _currency) + ' each</div>' +
                    '</div>' +
                    '<div class="order-line-item-controls">' +
                        '<div class="cart-qty-controls">' +
                            '<button type="button" class="cart-qty-btn order-item-minus" data-idx="' + idx + '">-</button>' +
                            '<span class="cart-qty-value">' + item.qty + '</span>' +
                            '<button type="button" class="cart-qty-btn order-item-plus" data-idx="' + idx + '">+</button>' +
                        '</div>' +
                        '<button type="button" class="order-item-remove" data-idx="' + idx + '" aria-label="Remove">' +
                            '<i class="fa-solid fa-xmark icon-md"></i>' +
                        '</button>' +
                    '</div>' +
                '</div>';
            {rdelim});
            $list.html(h);
            calcTotal();
        {rdelim}

        $(document).off('click.orderItems');
        $(document).on('click.orderItems', '.order-item-minus', function() {ldelim}
            var idx = parseInt($(this).data('idx'));
            if (_orderItems[idx] && _orderItems[idx].qty > 1) {ldelim}
                _orderItems[idx].qty--;
                renderItems();
            {rdelim}
        {rdelim});
        $(document).on('click.orderItems', '.order-item-plus', function() {ldelim}
            var idx = parseInt($(this).data('idx'));
            if (_orderItems[idx]) {ldelim}
                _orderItems[idx].qty++;
                renderItems();
            {rdelim}
        {rdelim});
        $(document).on('click.orderItems', '.order-item-remove', function() {ldelim}
            var idx = parseInt($(this).data('idx'));
            _orderItems.splice(idx, 1);
            renderItems();
        {rdelim});

        // Add Product → picker modal
        $('#addProductBtn').on('click', function() {ldelim}
            var pickerHtml = '<div style="margin-bottom:12px">' +
                '<input type="text" class="form-control" id="productPickerSearch" placeholder="Search products..." autocomplete="off" autofocus>' +
            '</div>' +
            '<div class="product-picker-list" id="productPickerList">';

            products.forEach(function(p) {ldelim}
                var imgSrc = (p.images && p.images.length > 0) ? p.images[0].image_url : (p.image_url || '/public/img/placeholder.svg');
                var isSold = parseInt(p.is_sold);
                var stockInfo = '';
                if (isSold) stockInfo = '<span style="color:#FF3B30;font-size:0.6875rem;font-weight:600">Sold out</span>';
                else if (p.stock_quantity !== null && parseInt(p.stock_quantity) <= 5 && parseInt(p.stock_quantity) > 0)
                    stockInfo = '<span style="color:#F59E0B;font-size:0.6875rem;font-weight:600">' + p.stock_quantity + ' left</span>';

                pickerHtml += '<div class="product-picker-item' + (isSold ? ' sold' : '') + '" data-id="' + p.id + '" data-name="' + escapeHtml(p.name) + '" data-price="' + p.price + '" data-image="' + escapeHtml(imgSrc) + '">' +
                    '<img src="' + escapeHtml(imgSrc) + '" alt="" class="product-picker-img">' +
                    '<div class="product-picker-info">' +
                        '<div class="product-picker-name">' + escapeHtml(p.name) + '</div>' +
                        '<div class="product-picker-price">' + TinyShop.formatPrice(p.price, _currency) + ' ' + stockInfo + '</div>' +
                    '</div>' +
                '</div>';
            {rdelim});
            pickerHtml += '</div>';
            TinyShop.openModal('Select Product', pickerHtml);

            $('#productPickerSearch').on('input', function() {ldelim}
                var q = $(this).val().trim().toLowerCase();
                $('#productPickerList .product-picker-item').each(function() {ldelim}
                    var name = ($(this).data('name') + '').toLowerCase();
                    $(this).toggle(!q || name.indexOf(q) !== -1);
                {rdelim});
            {rdelim});

            $('#productPickerList').on('click', '.product-picker-item:not(.sold)', function() {ldelim}
                var pid = parseInt($(this).data('id'));
                var name = $(this).data('name');
                var price = parseFloat($(this).data('price'));
                var image = $(this).data('image');

                var existing = -1;
                for (var i = 0; i < _orderItems.length; i++) {ldelim}
                    if (_orderItems[i].productId === pid) {ldelim} existing = i; break; {rdelim}
                {rdelim}
                if (existing !== -1) {ldelim}
                    _orderItems[existing].qty++;
                {rdelim} else {ldelim}
                    _orderItems.push({ldelim} productId: pid, name: name, price: price, image: image, qty: 1 {rdelim});
                {rdelim}
                TinyShop.closeModal();
                showAddOrderModal(products);
            {rdelim});
        {rdelim});

        renderItems();

        // Submit
        $('#addOrderForm').on('submit', function(e) {ldelim}
            e.preventDefault();
            var name = $('#orderCustomerName').val().trim();
            var phone = $('#orderCustomerPhone').val().trim();
            var notes = $('#orderNotes').val().trim();
            if (!name) return;

            var amount;
            if (_orderItems.length > 0) {ldelim}
                amount = 0;
                _orderItems.forEach(function(item) {ldelim} amount += item.price * item.qty; {rdelim});
            {rdelim} else {ldelim}
                var amountRaw = $('#orderAmount').val().replace(/,/g, '');
                amount = parseFloat(amountRaw);
                if (!amount || amount <= 0) {ldelim} TinyShop.toast('Enter an amount', 'error'); return; {rdelim}
            {rdelim}

            var $btn = $('#saveOrderBtn').prop('disabled', true).text('Saving...');
            var payload = {ldelim}
                customer_name: name,
                customer_phone: phone,
                amount: amount,
                notes: notes
            {rdelim};
            if (_orderItems.length > 0 && _orderItems[0].productId) {ldelim}
                payload.product_id = _orderItems[0].productId;
            {rdelim}

            TinyShop.api('POST', '/api/orders', payload).done(function(res) {ldelim}
                _orders.unshift(res.order);
                TinyShop.toast('Order logged!');
                TinyShop.closeModal();
                renderOrders();
                loadOrders();
            {rdelim}).fail(function(xhr) {ldelim}
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to save';
                TinyShop.toast(msg, 'error');
                $btn.prop('disabled', false).text('Log Order');
            {rdelim});
        {rdelim});
    {rdelim}

    loadOrders();
{rdelim});
</script>
{/block}
