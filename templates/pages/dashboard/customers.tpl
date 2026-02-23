{extends file="layouts/dashboard.tpl"}

{block name="content"}
<div class="dash-topbar">
    <span class="dash-topbar-title">Customers</span>
    <a href="/dashboard/shop" class="dash-topbar-avatar">{$user.store_name|escape|substr:0:1|upper}</a>
</div>

{* Stats overview *}
<div class="stats-panel" id="customerStats" style="display:none">
    <div class="stats-panel-grid stats-panel-3col">
        <div class="stats-panel-metric">
            <div class="stats-panel-number" id="statCustomers">0</div>
            <div class="stats-panel-label">Customers</div>
        </div>
        <div class="stats-panel-metric">
            <div class="stats-panel-number" id="statOrders">0</div>
            <div class="stats-panel-label">Orders</div>
        </div>
        <div class="stats-panel-metric">
            <div class="stats-panel-number" id="statSpent">0</div>
            <div class="stats-panel-label">Revenue</div>
        </div>
    </div>
</div>

{* Search bar *}
<div class="search-bar-row" id="customerSearchBar" style="display:none">
    <div class="product-search-bar">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="customerSearch" placeholder="Search customers..." autocomplete="off" aria-label="Search customers">
    </div>
</div>

{* Customer list *}
<div id="customerList" class="order-list" style="padding:8px 20px 100px">
    <div class="skeleton-order"><div class="skeleton-order-top"><div class="skeleton-line" style="width:55%;height:14px"></div><div class="skeleton-line" style="width:25%;height:14px"></div></div><div class="skeleton-order-bottom"><div class="skeleton-line" style="width:35%;height:10px"></div></div></div>
    <div class="skeleton-order"><div class="skeleton-order-top"><div class="skeleton-line" style="width:45%;height:14px"></div><div class="skeleton-line" style="width:30%;height:14px"></div></div><div class="skeleton-order-bottom"><div class="skeleton-line" style="width:30%;height:10px"></div></div></div>
    <div class="skeleton-order"><div class="skeleton-order-top"><div class="skeleton-line" style="width:60%;height:14px"></div><div class="skeleton-line" style="width:20%;height:14px"></div></div><div class="skeleton-order-bottom"><div class="skeleton-line" style="width:40%;height:10px"></div></div></div>
</div>
{/block}

{block name="extra_scripts"}
<script>
(function() {ldelim}
    var _currency = '{$currency|escape:"javascript"}';
    var _customers = [];
    var _search = '';

    function loadCustomers() {ldelim}
        var url = '/api/customers';
        if (_search) url += '?q=' + encodeURIComponent(_search);
        TinyShop.api('GET', url).done(function(res) {ldelim}
            _customers = res.customers || [];
            updateStats();
            render();
        {rdelim}).fail(function() {ldelim}
            $('#customerList').html('<div class="empty-state"><p>Failed to load customers.</p></div>');
        {rdelim});
    {rdelim}

    function updateStats() {ldelim}
        if (_customers.length === 0) return;
        var totalOrders = 0;
        var totalSpent = 0;
        _customers.forEach(function(c) {ldelim}
            totalOrders += parseInt(c.order_count) || 0;
            totalSpent += parseFloat(c.total_spent) || 0;
        {rdelim});
        $('#statCustomers').text(_customers.length.toLocaleString());
        $('#statOrders').text(totalOrders.toLocaleString());
        var revFormatted = TinyShop.formatPrice(totalSpent, _currency);
        var revParts = revFormatted.split(' ');
        if (revParts.length > 1) {ldelim}
            $('#statSpent').html('<span class="stats-panel-currency">' + revParts[0] + '</span> ' + revParts.slice(1).join(' '));
        {rdelim} else {ldelim}
            $('#statSpent').text(revFormatted);
        {rdelim}
        $('#customerStats').show();
    {rdelim}

    function render() {ldelim}
        if (_customers.length >= 3) {ldelim}
            $('#customerSearchBar').show();
        {rdelim}

        if (_customers.length === 0) {ldelim}
            var msg;
            if (_search) {ldelim}
                msg = '<div class="empty-state"><p>No customers matching "' + escapeHtml(_search) + '"</p></div>';
            {rdelim} else {ldelim}
                msg = '<div class="empty-state">' +
                    '<div class="empty-icon"><i class="fa-solid fa-users"></i></div>' +
                    '<h2>No customers yet</h2>' +
                    '<p>Customers appear here after they place orders</p>' +
                '</div>';
            {rdelim}
            $('#customerList').html(msg);
            return;
        {rdelim}

        var html = '';
        _customers.forEach(function(c) {ldelim}
            var name = escapeHtml(c.customer_name || c.customer_email);
            var orders = parseInt(c.order_count) || 0;
            var orderLabel = orders === 1 ? '1 order' : orders + ' orders';
            var spent = parseFloat(c.total_spent) || 0;
            var spentStr = spent > 0 ? TinyShop.formatPrice(spent, _currency) : '';
            var lastOrder = c.last_order_at ? new Date(c.last_order_at).toLocaleDateString('en-US', {ldelim} month: 'short', day: 'numeric' {rdelim}) : '';

            html += '<div class="order-card" data-email="' + escapeHtml(c.customer_email) + '" data-name="' + escapeHtml(c.customer_name || '') + '" data-phone="' + escapeHtml(c.customer_phone || '') + '">' +
                '<div class="order-card-top">' +
                    '<div class="order-card-customer">' +
                        '<div class="order-card-name">' + name + '</div>' +
                        '<div class="order-card-phone">' + escapeHtml(c.customer_email) + '</div>' +
                    '</div>' +
                    (spentStr ? '<div class="order-card-amount">' + spentStr + '</div>' : '') +
                '</div>' +
                '<div class="order-card-bottom">' +
                    '<span class="order-status order-status-paid">' + orderLabel + '</span>' +
                    (lastOrder ? '<span class="order-card-date">Last order &middot; ' + lastOrder + '</span>' : '') +
                '</div>' +
            '</div>';
        {rdelim});
        $('#customerList').html(html);
    {rdelim}

    // Search
    var _timer;
    $('#customerSearch').on('input', function() {ldelim}
        clearTimeout(_timer);
        var q = $(this).val().trim();
        _timer = setTimeout(function() {ldelim}
            _search = q;
            loadCustomers();
        {rdelim}, 300);
    {rdelim});

    // Customer detail modal
    $('#customerList').on('click', '.order-card', function() {ldelim}
        var email = $(this).data('email');
        var name = $(this).data('name') || email;
        var phone = $(this).data('phone');

        var html = '<div style="text-align:center;padding:8px 0 16px">' +
            '<div class="customer-avatar" style="width:56px;height:56px;font-size:1.375rem;margin:0 auto 10px">' + name.charAt(0).toUpperCase() + '</div>' +
            '<div style="font-weight:700;font-size:1.0625rem">' + escapeHtml(name) + '</div>' +
            '<div style="font-size:0.8125rem;color:var(--color-text-muted);margin-top:2px">' + escapeHtml(email) + '</div>' +
        '</div>';

        html += '<div style="display:flex;flex-direction:column;gap:8px">';
        html += '<a href="mailto:' + escapeHtml(email) + '" class="order-contact-chip order-contact-email" style="justify-content:center">' +
            '<i class="fa-solid fa-envelope icon-sm"></i> Send Email</a>';
        if (phone) {ldelim}
            var cleanPhone = phone.replace(/[^0-9+]/g, '');
            html += '<a href="https://wa.me/' + escapeHtml(cleanPhone) + '" target="_blank" rel="noopener" class="order-contact-chip order-contact-whatsapp" style="justify-content:center">' +
                '<i class="fa-brands fa-whatsapp icon-sm"></i> WhatsApp</a>';
            html += '<a href="tel:' + escapeHtml(cleanPhone) + '" class="order-contact-chip" style="justify-content:center;background:var(--color-bg-secondary)">' +
                '<i class="fa-solid fa-phone icon-sm"></i> Call</a>';
        {rdelim}
        html += '</div>';

        TinyShop.openModal('Customer', html);
    {rdelim});

    loadCustomers();
{rdelim})();
</script>
{/block}
