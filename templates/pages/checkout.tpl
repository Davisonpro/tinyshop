{extends file="layouts/base.tpl"}

{block name="body_class"}page-shop page-checkout{/block}

{block name="extra_css"}
<link rel="stylesheet" href="/public/css/checkout{$min}.css?v={$asset_v}">
{/block}
{block name="body"}
<div class="checkout-page" id="checkoutPage">
    <a href="/" class="checkout-back">
        <i class="fa-solid fa-chevron-left" style="font-size:16px"></i>
        Back to shop
    </a>
    <h1 class="checkout-title">Checkout</h1>

    <div id="checkoutContent">
        <div class="checkout-empty" id="checkoutEmpty" style="display:none">
            <i class="fa-solid fa-cart-shopping" style="font-size:40px;color:var(--color-text-muted);opacity:0.4;margin-bottom:12px"></i>
            <p style="font-weight:600">Your cart is empty</p>
            <p style="font-size:0.8125rem;color:var(--color-text-muted)"><a href="/" style="color:var(--color-accent)">Continue shopping</a></p>
        </div>

        <div id="stockWarnings" style="display:none"></div>

        <div id="checkoutForm" style="display:none">
            {* Order summary *}
            <div class="checkout-section">
                <div class="checkout-section-title">Order Summary</div>
                <ul class="checkout-items" id="checkoutItems"></ul>
                <div id="checkoutDiscountRow" class="checkout-discount-row" style="display:none">
                    <span>Discount (<span id="discountCode"></span>)</span>
                    <span id="discountAmount"></span>
                </div>
                <div style="margin-top:8px">
                    <div class="checkout-summary-row checkout-summary-total">
                        <span>Total</span>
                        <span id="checkoutTotal"></span>
                    </div>
                </div>
            </div>

            {* Coupon code *}
            <div class="checkout-section">
                <button type="button" id="couponToggle" class="checkout-coupon-toggle">Have a coupon?</button>
                <div id="couponForm" style="display:none">
                    <div class="checkout-coupon-row">
                        <input type="text" id="couponInput" placeholder="Enter code" autocomplete="off">
                        <button type="button" id="couponApply" class="btn-sm">Apply</button>
                    </div>
                    <div id="couponResult" class="checkout-coupon-result"></div>
                </div>
            </div>

            {* Customer details *}
            <div class="checkout-section">
                <div class="checkout-section-title">Your Details</div>
                <p id="savedCustomerHint" class="checkout-saved-hint" style="display:none">
                    Using saved info &middot; <a id="clearSavedCustomer">Not you?</a>
                </p>
                <div class="checkout-field">
                    <label for="coName">Name</label>
                    <input type="text" id="coName" placeholder="Your full name" required autocomplete="name">
                </div>
                <div class="checkout-field">
                    <label for="coEmail">Email</label>
                    <input type="email" id="coEmail" placeholder="you@example.com" required autocomplete="email">
                </div>
                <div class="checkout-field">
                    <label for="coPhone">Phone <span class="field-optional">(optional)</span></label>
                    <input type="tel" id="coPhone" placeholder="Your phone number" autocomplete="tel">
                </div>
                <div class="checkout-field">
                    <label for="coNotes">Delivery note <span class="field-optional">(optional)</span></label>
                    <textarea id="coNotes" placeholder="Address, delivery instructions, or special requests..." rows="2"></textarea>
                </div>
            </div>

            {* Payment method *}
            <div class="checkout-section">
                <div class="checkout-section-title">Payment Method</div>
                {if $has_stripe}
                <label class="gateway-option" data-gateway="stripe">
                    <input type="radio" name="payment_method" value="stripe">
                    <div class="gateway-option-icon">
                        <i class="fa-brands fa-stripe" style="color:#635BFF"></i>
                    </div>
                    <span class="gateway-option-label">Credit / Debit Card</span>
                    <div class="gateway-option-check">
                        <i class="fa-solid fa-check" style="font-size:12px;color:#fff;display:none"></i>
                    </div>
                </label>
                {/if}
                {if $has_paypal}
                <label class="gateway-option" data-gateway="paypal">
                    <input type="radio" name="payment_method" value="paypal">
                    <div class="gateway-option-icon">
                        <i class="fa-brands fa-paypal" style="color:#003087"></i>
                    </div>
                    <span class="gateway-option-label">PayPal</span>
                    <div class="gateway-option-check">
                        <i class="fa-solid fa-check" style="font-size:12px;color:#fff;display:none"></i>
                    </div>
                </label>
                {/if}
                {if $has_cod}
                <label class="gateway-option" data-gateway="cod">
                    <input type="radio" name="payment_method" value="cod">
                    <div class="gateway-option-icon">
                        <i class="fa-solid fa-hand-holding-dollar" style="color:#059669"></i>
                    </div>
                    <span class="gateway-option-label">Pay on Delivery</span>
                    <div class="gateway-option-check">
                        <i class="fa-solid fa-check" style="font-size:12px;color:#fff;display:none"></i>
                    </div>
                </label>
                {/if}
                {if $has_mpesa}
                <label class="gateway-option" data-gateway="mpesa">
                    <input type="radio" name="payment_method" value="mpesa">
                    <div class="gateway-option-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="12" fill="#4CAF50"/><text x="12" y="16" text-anchor="middle" font-size="10" font-weight="bold" fill="white">M</text></svg>
                    </div>
                    <span class="gateway-option-label">M-Pesa</span>
                    <div class="gateway-option-check">
                        <i class="fa-solid fa-check" style="font-size:12px;color:#fff;display:none"></i>
                    </div>
                </label>
                {/if}
            </div>

            <div class="checkout-section" id="mpesaPhoneSection" style="display:none">
                <div class="checkout-field">
                    <label for="mpesaPhone">M-Pesa Phone Number</label>
                    <input type="tel" id="mpesaPhone" placeholder="e.g. 0712 345 678" inputmode="numeric" autocomplete="tel">
                    <p class="form-hint" style="margin-top:4px;font-size:0.75rem;color:var(--color-text-muted)">
                        The number registered with M-Pesa. You'll get a PIN prompt on this phone.
                    </p>
                </div>
            </div>

            <div class="checkout-pay-btn">
                <button type="button" class="btn btn-accent" id="payBtn" disabled>
                    Select payment method
                </button>
            </div>
        </div>
    </div>
</div>

<script>
window._shopId = {$shop.id|escape:'javascript'};
window._shopCurrency = '{$currency|escape:'javascript'}';
window._shopCurrencySymbol = '{$currency_symbol|escape:'javascript'}';
</script>
<script src="/public/js/cart{$min}.js?v={$asset_v}"></script>
{/block}

{block name="page_scripts"}
<script>
(function() {ldelim}
    TinyShop.Cart.init({$shop.id|escape:'javascript'});
    var items = TinyShop.Cart.getItems();

    if (items.length === 0) {ldelim}
        document.getElementById('checkoutEmpty').style.display = '';
        return;
    {rdelim}

    document.getElementById('checkoutForm').style.display = '';

    // ── Prefill saved customer details ──
    try {ldelim}
        var saved = JSON.parse(localStorage.getItem('tinyshop_customer') || '{}');
        if (saved.name) document.getElementById('coName').value = saved.name;
        if (saved.email) document.getElementById('coEmail').value = saved.email;
        if (saved.phone) document.getElementById('coPhone').value = saved.phone;
        if (saved.name || saved.email) {ldelim}
            document.getElementById('savedCustomerHint').style.display = '';
        {rdelim}
    {rdelim} catch(e) {ldelim}{rdelim}

    var clearBtn = document.getElementById('clearSavedCustomer');
    if (clearBtn) {ldelim}
        clearBtn.addEventListener('click', function() {ldelim}
            try {ldelim} localStorage.removeItem('tinyshop_customer'); {rdelim} catch(e) {ldelim}{rdelim}
            document.getElementById('coName').value = '';
            document.getElementById('coEmail').value = '';
            document.getElementById('coPhone').value = '';
            document.getElementById('savedCustomerHint').style.display = 'none';
            TinyShop.toast('Saved info cleared');
        {rdelim});
    {rdelim}

    // ── Stock validation on page load ──
    (function validateStock() {ldelim}
        var cartItems = items.map(function(i) {ldelim}
            return {ldelim} productId: i.productId, name: i.name, quantity: i.quantity, variation: i.variation {rdelim};
        {rdelim});
        $.ajax({ldelim}
            url: '/api/checkout/validate',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ldelim} shop_id: window._shopId, items: cartItems {rdelim}),
            timeout: 5000,
            error: function(xhr) {ldelim}
                if (xhr.status === 422 && xhr.responseJSON) {ldelim}
                    var msg = xhr.responseJSON.message || 'Some items are no longer available';
                    document.getElementById('stockWarnings').innerHTML =
                        '<div class="checkout-warning">' +
                        '<i class="fa-solid fa-triangle-exclamation" style="color:#F59E0B;font-size:18px;flex-shrink:0"></i>' +
                        '<div><strong>Cart updated</strong><span class="checkout-warning-text">' + esc(msg) + '</span></div></div>';
                    document.getElementById('stockWarnings').style.display = '';
                {rdelim}
            {rdelim}
        {rdelim});
    {rdelim})();

    var sym = window._shopCurrencySymbol || '';

    function fmt(n) {ldelim}
        return parseFloat(n).toFixed(2).replace(/\B(?=(\d{ldelim}3{rdelim})+(?!\d))/g, ',');
    {rdelim}

    function esc(s) {ldelim}
        if (!s) return '';
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(s));
        return d.innerHTML;
    {rdelim}

    // Render order summary
    var $items = document.getElementById('checkoutItems');
    var total = 0;
    items.forEach(function(item) {ldelim}
        var lineTotal = item.price * item.quantity;
        total += lineTotal;
        var li = document.createElement('li');
        li.className = 'checkout-item';
        var imgSrc = item.image || '/public/img/placeholder.svg';
        li.innerHTML =
            '<div class="checkout-item-img"><img src="' + esc(imgSrc) + '" alt="' + esc(item.name) + '"></div>' +
            '<div class="checkout-item-info">' +
                '<div class="checkout-item-name">' + esc(item.name) + '</div>' +
                '<div class="checkout-item-meta">' + (item.variation ? esc(item.variation) + ' · ' : '') + 'Qty: ' + item.quantity + '</div>' +
            '</div>' +
            '<div class="checkout-item-price">' + esc(sym) + fmt(lineTotal) + '</div>';
        $items.appendChild(li);
    {rdelim});
    var subtotal = total;
    var appliedCoupon = null;
    var discountAmt = 0;

    function updateTotals() {ldelim}
        var finalTotal = Math.max(0, subtotal - discountAmt);
        document.getElementById('checkoutTotal').textContent = sym + fmt(finalTotal);
        if (selectedGateway) updatePayBtnLabel();
    {rdelim}

    updateTotals();

    // ── Coupon handling ──
    var couponToggle = document.getElementById('couponToggle');
    var couponForm = document.getElementById('couponForm');
    if (couponToggle) {ldelim}
        couponToggle.addEventListener('click', function() {ldelim}
            var showing = couponForm.style.display !== 'none';
            couponForm.style.display = showing ? 'none' : '';
            couponToggle.textContent = showing ? 'Have a coupon?' : 'Hide coupon';
            if (!showing) document.getElementById('couponInput').focus();
        {rdelim});
    {rdelim}

    var couponApplyBtn = document.getElementById('couponApply');
    if (couponApplyBtn) {ldelim}
        couponApplyBtn.addEventListener('click', function() {ldelim}
            var code = document.getElementById('couponInput').value.trim().toUpperCase();
            if (!code) {ldelim} TinyShop.toast('Enter a coupon code', 'error'); return; {rdelim}

            couponApplyBtn.disabled = true;
            couponApplyBtn.textContent = '...';

            $.ajax({ldelim}
                url: '/api/checkout/apply-coupon',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ldelim} shop_id: window._shopId, code: code, subtotal: subtotal {rdelim}),
                success: function(resp) {ldelim}
                    appliedCoupon = resp.code;
                    discountAmt = parseFloat(resp.discount);
                    document.getElementById('couponResult').innerHTML =
                        '<span class="checkout-coupon-success"><i class="fa-solid fa-check-circle"></i> ' + esc(resp.message) + ' (-' + esc(sym) + fmt(discountAmt) + ')</span>';
                    document.getElementById('discountCode').textContent = resp.code;
                    document.getElementById('discountAmount').textContent = '-' + sym + fmt(discountAmt);
                    document.getElementById('checkoutDiscountRow').style.display = '';
                    updateTotals();
                    couponApplyBtn.disabled = false;
                    couponApplyBtn.textContent = 'Applied';
                    couponApplyBtn.style.background = '#059669';
                {rdelim},
                error: function(xhr) {ldelim}
                    var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Invalid coupon';
                    document.getElementById('couponResult').innerHTML =
                        '<span class="checkout-coupon-error">' + esc(msg) + '</span>';
                    appliedCoupon = null;
                    discountAmt = 0;
                    document.getElementById('checkoutDiscountRow').style.display = 'none';
                    updateTotals();
                    couponApplyBtn.disabled = false;
                    couponApplyBtn.textContent = 'Apply';
                    couponApplyBtn.style.background = '';
                {rdelim}
            {rdelim});
        {rdelim});
    {rdelim}

    // Gateway selection
    var $gateways = document.querySelectorAll('.gateway-option');
    var selectedGateway = '';
    function updatePayBtnLabel() {ldelim}
        var finalTotal = Math.max(0, subtotal - discountAmt);
        var label = selectedGateway === 'cod' ? 'Place Order'
            : selectedGateway === 'mpesa' ? 'Pay ' + sym + fmt(finalTotal) + ' via M-Pesa'
            : 'Pay ' + sym + fmt(finalTotal);
        document.getElementById('payBtn').innerHTML = label;
    {rdelim}
    $gateways.forEach(function(g) {ldelim}
        g.addEventListener('click', function() {ldelim}
            $gateways.forEach(function(o) {ldelim}
                o.classList.remove('selected');
                o.querySelector('i.fa-check').style.display = 'none';
            {rdelim});
            g.classList.add('selected');
            g.querySelector('input').checked = true;
            g.querySelector('i.fa-check').style.display = '';
            selectedGateway = g.dataset.gateway;
            document.getElementById('payBtn').disabled = false;
            var mpesaSection = document.getElementById('mpesaPhoneSection');
            if (mpesaSection) {ldelim}
                mpesaSection.style.display = selectedGateway === 'mpesa' ? '' : 'none';
            {rdelim}
            updatePayBtnLabel();
        {rdelim});
    {rdelim});

    // Auto-select first gateway if only one
    if ($gateways.length === 1) {ldelim}
        $gateways[0].click();
    {rdelim}

    // Pay button
    document.getElementById('payBtn').addEventListener('click', function() {ldelim}
        var btn = this;
        var name = document.getElementById('coName').value.trim();
        var email = document.getElementById('coEmail').value.trim();
        var phone = document.getElementById('coPhone').value.trim();
        var notes = document.getElementById('coNotes').value.trim();

        if (!name) {ldelim} TinyShop.toast('Please enter your name', 'error'); return; {rdelim}
        if (!email) {ldelim} TinyShop.toast('Please enter your email', 'error'); return; {rdelim}
        if (!selectedGateway) {ldelim} TinyShop.toast('Please select a payment method', 'error'); return; {rdelim}
        if (selectedGateway === 'mpesa') {ldelim}
            var mpesaPhone = document.getElementById('mpesaPhone').value.trim();
            if (!mpesaPhone) {ldelim}
                TinyShop.toast('Enter your M-Pesa phone number', 'error');
                return;
            {rdelim}
        {rdelim}

        btn.disabled = true;
        btn.innerHTML = '<span class="btn-spinner"></span> Processing...';

        var cartItems = TinyShop.Cart.getItems().map(function(i) {ldelim}
            return {ldelim} productId: i.productId, name: i.name, quantity: i.quantity, variation: i.variation {rdelim};
        {rdelim});

        $.ajax({ldelim}
            url: '/api/checkout/create',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ldelim}
                shop_id: window._shopId,
                items: cartItems,
                payment_method: selectedGateway,
                customer_name: name,
                customer_email: email,
                customer_phone: phone,
                notes: notes,
                coupon_code: appliedCoupon || '',
                mpesa_phone: selectedGateway === 'mpesa' ? document.getElementById('mpesaPhone').value.trim() : ''
            {rdelim}),
            success: function(resp) {ldelim}
                // Save customer details for next time
                try {ldelim}
                    localStorage.setItem('tinyshop_customer', JSON.stringify({ldelim} name: name, email: email, phone: phone {rdelim}));
                {rdelim} catch(e) {ldelim}{rdelim}
                TinyShop.Cart.clear();

                if (resp.gateway === 'mpesa' && resp.poll_url) {ldelim}
                    btn.innerHTML = '<span class="btn-spinner"></span> Enter your M-Pesa PIN...';
                    var pollCount = 0;
                    var maxPolls = 60;
                    var pollTimer = setInterval(function() {ldelim}
                        pollCount++;
                        if (pollCount >= maxPolls) {ldelim}
                            clearInterval(pollTimer);
                            btn.disabled = false;
                            btn.innerHTML = 'Try Again';
                            TinyShop.toast('Payment not received. Please try again.', 'error');
                            return;
                        {rdelim}
                        $.get(resp.poll_url, function(statusResp) {ldelim}
                            if (statusResp.status === 'paid') {ldelim}
                                clearInterval(pollTimer);
                                window.location.href = statusResp.order_url;
                            {rdelim}
                        {rdelim});
                    {rdelim}, 2000);
                    return;
                {rdelim}

                if (resp.redirect_url) {ldelim}
                    window.location.href = resp.redirect_url;
                {rdelim} else if (resp.order_url) {ldelim}
                    window.location.href = resp.order_url;
                {rdelim}
            {rdelim},
            error: function(xhr) {ldelim}
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Something went wrong';
                TinyShop.toast(msg, 'error');
                btn.disabled = false;
                updatePayBtnLabel();
            {rdelim}
        {rdelim});
    {rdelim});
{rdelim})();
</script>
{/block}
