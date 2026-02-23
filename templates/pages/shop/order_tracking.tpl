{extends file="layouts/base.tpl"}

{block name="body_class"}page-shop page-tracking{/block}

{block name="extra_css"}
<link rel="stylesheet" href="/public/css/tracking{$min}.css?v={$asset_v}">
{/block}
{block name="body"}

{include file="partials/shop/announcement_bar.tpl"}
{include file="partials/shop/desktop_header.tpl"}
{include file="partials/shop/mobile_header.tpl"}

<div class="tracking-page">
    <div class="tracking-header">
        <h1 class="tracking-title">Track Your Order</h1>
        <p class="tracking-subtitle">Enter your details to check order status</p>
    </div>

    <form id="trackingForm" class="tracking-form">
        <div class="tracking-field">
            <label for="trackEmail">Email Address</label>
            <input type="email" id="trackEmail" name="email" placeholder="you@example.com" required autocomplete="email">
        </div>
        <div class="tracking-field">
            <label for="trackOrder">Order Number</label>
            <input type="text" id="trackOrder" name="order_number" placeholder="TS-12345678" required autocomplete="off">
        </div>
        <div id="trackingError" class="tracking-error"></div>
        <button type="submit" class="tracking-submit" id="trackSubmit">Track Order</button>
    </form>

    <div id="trackingResult" class="tracking-result"></div>
</div>

{include file="partials/shop/desktop_footer.tpl"}
{include file="partials/shop/cart_drawer.tpl"}
{include file="partials/shop/contact_sheet.tpl"}
{include file="partials/shop/bottom_nav.tpl"}
{/block}

{block name="page_scripts"}
<script>
(function() {ldelim}
    var form = document.getElementById('trackingForm');
    var errorDiv = document.getElementById('trackingError');
    var resultDiv = document.getElementById('trackingResult');
    var submitBtn = document.getElementById('trackSubmit');

    form.addEventListener('submit', function(e) {ldelim}
        e.preventDefault();
        var email = document.getElementById('trackEmail').value.trim();
        var orderNum = document.getElementById('trackOrder').value.trim();
        if (!email || !orderNum) return;

        errorDiv.classList.remove('active');
        resultDiv.classList.remove('active');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Searching...';

        fetch('/orders/lookup', {ldelim}
            method: 'POST',
            headers: {ldelim} 'Content-Type': 'application/json' {rdelim},
            body: JSON.stringify({ldelim} email: email, order_number: orderNum {rdelim})
        {rdelim})
        .then(function(r) {ldelim} return r.json(); {rdelim})
        .then(function(data) {ldelim}
            submitBtn.disabled = false;
            submitBtn.textContent = 'Track Order';
            if (data.success && data.order) {ldelim}
                renderOrder(data.order);
            {rdelim} else {ldelim}
                errorDiv.textContent = data.message || 'Order not found';
                errorDiv.classList.add('active');
            {rdelim}
        {rdelim})
        .catch(function() {ldelim}
            submitBtn.disabled = false;
            submitBtn.textContent = 'Track Order';
            errorDiv.textContent = 'Something went wrong. Please try again.';
            errorDiv.classList.add('active');
        {rdelim});
    {rdelim});

    function esc(s) {ldelim}
        if (!s) return '';
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(s));
        return d.innerHTML;
    {rdelim}

    function renderOrder(order) {ldelim}
        var statusMap = {ldelim}
            pending: {ldelim} label: 'Processing', icon: 'fa-clock', cls: 's-pending' {rdelim},
            paid: {ldelim} label: 'Completed', icon: 'fa-check-circle', cls: 's-paid' {rdelim},
            cancelled: {ldelim} label: 'Cancelled', icon: 'fa-times-circle', cls: 's-cancelled' {rdelim},
            refunded: {ldelim} label: 'Refunded', icon: 'fa-undo', cls: 's-cancelled' {rdelim}
        {rdelim};
        var s = statusMap[order.status] || statusMap.pending;
        var dt = new Date(order.created_at);
        var dateStr = dt.toLocaleDateString('en-US', {ldelim} month: 'long', day: 'numeric', year: 'numeric' {rdelim});

        var h = '<div class="order-status-display">' +
            '<div class="order-status-icon ' + s.cls + '"><i class="fa-solid ' + s.icon + '"></i></div>' +
            '<div class="order-status-label">' + s.label + '</div>' +
            '<div class="order-status-date">Placed on ' + dateStr + '</div></div>';

        h += '<div class="tracking-detail-row"><span class="tracking-detail-label">Order</span><span class="tracking-detail-value">#' + esc(order.order_number) + '</span></div>';
        h += '<div class="tracking-detail-row"><span class="tracking-detail-label">Name</span><span class="tracking-detail-value">' + esc(order.customer_name) + '</span></div>';
        if (order.customer_phone) {ldelim}
            h += '<div class="tracking-detail-row"><span class="tracking-detail-label">Phone</span><span class="tracking-detail-value">' + esc(order.customer_phone) + '</span></div>';
        {rdelim}
        h += '<div class="tracking-detail-row"><span class="tracking-detail-label">Total</span><span class="tracking-detail-value">' + esc('{$currency_symbol|escape:"javascript"}') + parseFloat(order.amount).toFixed(2).replace(/\B(?=(\d{ldelim}3{rdelim})+(?!\d))/g, ',') + '</span></div>';

        if (order.items && order.items.length) {ldelim}
            h += '<div class="tracking-items"><div class="tracking-items-title">Items</div>';
            order.items.forEach(function(item) {ldelim}
                var img = item.product_image || '/public/img/placeholder.svg';
                h += '<div class="tracking-item">' +
                    '<div class="tracking-item-img"><img src="' + esc(img) + '" alt=""></div>' +
                    '<div class="tracking-item-info">' +
                    '<div class="tracking-item-name">' + esc(item.product_name) + '</div>' +
                    '<div class="tracking-item-meta">' + (item.variation ? esc(item.variation) + ' &middot; ' : '') + 'Qty: ' + item.quantity + '</div>' +
                    '</div></div>';
            {rdelim});
            h += '</div>';
        {rdelim}

        resultDiv.innerHTML = h;
        resultDiv.classList.add('active');
        resultDiv.scrollIntoView({ldelim} behavior: 'smooth', block: 'start' {rdelim});
    {rdelim}
{rdelim})();
</script>
{/block}
