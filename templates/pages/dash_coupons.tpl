{extends file="layouts/dashboard.tpl"}

{block name="content"}
<div class="dash-topbar">
    <a href="/dashboard/orders" class="dash-topbar-back">
        <i class="fa-solid fa-chevron-left icon-md"></i>
    </a>
    <span class="dash-topbar-title">Coupons</span>
    <a href="/dashboard/shop" class="dash-topbar-avatar">{$user.store_name|default:$user.name|escape|substr:0:1|upper}</a>
</div>

{if !empty($usage) && !$usage.coupons}
<div class="empty-gate">
    <i class="fa-solid fa-tag empty-gate-icon"></i>
    <h2>Coupons are a paid feature</h2>
    <p>Upgrade your plan to create discount codes for your customers</p>
    <a href="/dashboard/billing" class="btn-block btn-accent">
        <i class="fa-solid fa-crown icon-sm"></i>
        Upgrade Plan
    </a>
</div>
{else}
{* Coupon list *}
<div id="couponList" style="padding:8px 20px 100px">
    <div class="skeleton-order"><div class="skeleton-order-top"><div class="skeleton-line" style="width:55%;height:14px"></div><div class="skeleton-line" style="width:25%;height:14px"></div></div><div class="skeleton-order-bottom"><div class="skeleton-line" style="width:35%;height:10px"></div><div class="skeleton-line" style="width:20%;height:10px"></div></div></div>
    <div class="skeleton-order"><div class="skeleton-order-top"><div class="skeleton-line" style="width:45%;height:14px"></div><div class="skeleton-line" style="width:30%;height:14px"></div></div><div class="skeleton-order-bottom"><div class="skeleton-line" style="width:30%;height:10px"></div><div class="skeleton-line" style="width:22%;height:10px"></div></div></div>
</div>

{* FAB - Create Coupon *}
<a href="javascript:void(0)" class="fab" id="addCouponFab" title="Create Coupon" aria-label="Create a new coupon">
    <i class="fa-solid fa-plus"></i>
</a>
{/if}
{/block}

{block name="extra_scripts"}
{if empty($usage) || $usage.coupons}
<script>
var _couponConfig = {ldelim}
    currency: '{$currency|escape:"javascript"}'
{rdelim};
</script>
<script>
$(function() {ldelim}
    var _currency = _couponConfig.currency || 'KES';
    var _coupons = [];

    function loadCoupons() {ldelim}
        TinyShop.api('GET', '/api/coupons').done(function(res) {ldelim}
            _coupons = res.coupons || [];
            renderCoupons();
        {rdelim}).fail(function() {ldelim}
            $('#couponList').html('<div class="empty-state"><p>Failed to load coupons.</p></div>');
        {rdelim});
    {rdelim}

    function renderCoupons() {ldelim}
        if (_coupons.length === 0) {ldelim}
            $('#couponList').html(
                '<div class="empty-state">' +
                    '<div class="empty-icon"><i class="fa-solid fa-ticket icon-2xl text-muted"></i></div>' +
                    '<h2>No coupons yet</h2>' +
                    '<p>Create a coupon to offer discounts to your customers</p>' +
                '</div>'
            );
            return;
        {rdelim}

        var html = '';
        _coupons.forEach(function(c) {ldelim}
            var valueDisplay = c.type === 'percent' ? parseFloat(c.value) + '% off' : _currency + ' ' + parseFloat(c.value).toFixed(2) + ' off';
            var isActive = parseInt(c.is_active);
            var isExpired = c.expires_at && new Date(c.expires_at) < new Date();
            var statusClass = isExpired ? 'coupon-status-expired' : (isActive ? 'coupon-status-active' : 'coupon-status-inactive');
            var statusLabel = isExpired ? 'Expired' : (isActive ? 'Active' : 'Inactive');

            var usageText = '';
            if (c.max_uses) {ldelim}
                usageText = (parseInt(c.used_count) || 0) + '/' + c.max_uses + ' used';
            {rdelim} else {ldelim}
                usageText = (parseInt(c.used_count) || 0) + ' used';
            {rdelim}

            html += '<div class="coupon-card" data-id="' + c.id + '">' +
                '<div class="coupon-card-top">' +
                    '<div class="coupon-card-code">' + escapeHtml(c.code) + '</div>' +
                    '<div class="coupon-card-value">' + valueDisplay + '</div>' +
                '</div>' +
                '<div class="coupon-card-bottom">' +
                    '<div style="display:flex;align-items:center;gap:6px">' +
                        '<span class="coupon-status ' + statusClass + '">' + statusLabel + '</span>' +
                        '<span class="coupon-card-usage">' + usageText + '</span>' +
                    '</div>' +
                    (c.min_order ? '<span class="coupon-card-min">Min: ' + _currency + ' ' + parseFloat(c.min_order).toFixed(2) + '</span>' : '') +
                '</div>' +
            '</div>';
        {rdelim});
        $('#couponList').html(html);
    {rdelim}

    // View coupon detail
    $('#couponList').on('click', '.coupon-card', function() {ldelim}
        var id = $(this).data('id');
        var coupon = _coupons.find(function(c) {ldelim} return parseInt(c.id) === parseInt(id); {rdelim});
        if (!coupon) return;
        showCouponDetail(coupon);
    {rdelim});

    function showCouponDetail(coupon) {ldelim}
        var isActive = parseInt(coupon.is_active);
        var valueDisplay = coupon.type === 'percent' ? parseFloat(coupon.value) + '%' : _currency + ' ' + parseFloat(coupon.value).toFixed(2);
        var typeLabel = coupon.type === 'percent' ? 'Percentage' : 'Fixed Amount';

        var html = '<div style="text-align:center;margin-bottom:16px">' +
            '<div style="font-size:2rem;font-weight:800;letter-spacing:-0.02em">' + valueDisplay + '</div>' +
            '<div style="font-size:0.8125rem;color:var(--color-text-muted)">' + typeLabel + ' discount</div>' +
        '</div>';

        html += '<div style="background:var(--color-bg-secondary);border-radius:var(--radius-md);padding:12px;margin-bottom:12px">';
        html += '<div class="coupon-detail-row"><span>Code</span><strong>' + escapeHtml(coupon.code) + '</strong></div>';
        html += '<div class="coupon-detail-row"><span>Used</span><strong>' + (parseInt(coupon.used_count) || 0) + (coupon.max_uses ? ' / ' + coupon.max_uses : '') + ' times</strong></div>';
        if (coupon.min_order) {ldelim}
            html += '<div class="coupon-detail-row"><span>Min order</span><strong>' + _currency + ' ' + parseFloat(coupon.min_order).toFixed(2) + '</strong></div>';
        {rdelim}
        if (coupon.expires_at) {ldelim}
            var expDate = new Date(coupon.expires_at);
            html += '<div class="coupon-detail-row"><span>Expires</span><strong>' + expDate.toLocaleDateString('en-US', {ldelim} month: 'short', day: 'numeric', year: 'numeric' {rdelim}) + '</strong></div>';
        {rdelim}
        html += '</div>';

        // Toggle active
        html += '<div style="display:flex;gap:8px;margin-bottom:8px">';
        html += '<button type="button" id="toggleCouponBtn" class="btn-outline" style="flex:1;min-height:48px">' +
            '<i class="fa-solid fa-' + (isActive ? 'pause' : 'play') + '"></i> ' + (isActive ? 'Deactivate' : 'Activate') +
        '</button>';
        html += '<button type="button" id="deleteCouponBtn" class="btn-outline" style="flex:0 0 48px;min-height:48px;color:#FF3B30;border-color:#FF3B30">' +
            '<i class="fa-solid fa-trash"></i>' +
        '</button>';
        html += '</div>';

        TinyShop.openModal(coupon.code, html);

        // Toggle
        $('#toggleCouponBtn').on('click', function() {ldelim}
            var $btn = $(this).prop('disabled', true);
            TinyShop.api('PUT', '/api/coupons/' + coupon.id, {ldelim} is_active: isActive ? 0 : 1 {rdelim}).done(function() {ldelim}
                TinyShop.toast(isActive ? 'Coupon deactivated' : 'Coupon activated');
                TinyShop.closeModal();
                loadCoupons();
            {rdelim}).fail(function(xhr) {ldelim}
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to update';
                TinyShop.toast(msg, 'error');
                $btn.prop('disabled', false);
            {rdelim});
        {rdelim});

        // Delete
        $('#deleteCouponBtn').on('click', function() {ldelim}
            TinyShop.confirm('Delete Coupon?', 'Delete coupon "' + coupon.code + '"? This cannot be undone.', 'Delete', function() {ldelim}
                TinyShop.closeModal();
                TinyShop.api('DELETE', '/api/coupons/' + coupon.id).done(function() {ldelim}
                    _coupons = _coupons.filter(function(c) {ldelim} return parseInt(c.id) !== parseInt(coupon.id); {rdelim});
                    TinyShop.toast('Coupon deleted');
                    renderCoupons();
                {rdelim}).fail(function() {ldelim}
                    TinyShop.toast('Failed to delete', 'error');
                {rdelim});
            {rdelim}, 'danger');
        {rdelim});
    {rdelim}

    // ── Create Coupon (FAB) ──
    $('#addCouponFab').on('click', function() {ldelim}
        showCreateCoupon();
    {rdelim});

    function generateCode() {ldelim}
        var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        var code = '';
        for (var i = 0; i < 8; i++) code += chars.charAt(Math.floor(Math.random() * chars.length));
        return code;
    {rdelim}

    function showCreateCoupon() {ldelim}
        var html = '<form id="createCouponForm" autocomplete="off">' +
            '<div class="form-group">' +
                '<label for="couponCode">Coupon Code</label>' +
                '<div style="display:flex;gap:8px">' +
                    '<input type="text" class="form-control" id="couponCode" placeholder="e.g. SUMMER20" required autocomplete="off" style="text-transform:uppercase;flex:1">' +
                    '<button type="button" id="generateCodeBtn" style="flex-shrink:0;padding:0 14px;border:1px solid var(--color-border);border-radius:var(--radius-md);background:var(--color-bg);color:var(--color-text);font-size:0.8125rem;font-weight:600;cursor:pointer;font-family:inherit">Generate</button>' +
                '</div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label>Discount Type</label>' +
                '<div style="display:flex;gap:8px">' +
                    '<button type="button" class="coupon-type-btn active" data-type="percent" style="flex:1">Percentage (%)</button>' +
                    '<button type="button" class="coupon-type-btn" data-type="fixed" style="flex:1">Fixed (' + escapeHtml(_currency) + ')</button>' +
                '</div>' +
                '<input type="hidden" id="couponType" value="percent">' +
            '</div>' +
            '<div class="form-group">' +
                '<label for="couponValue" id="couponValueLabel">Discount (%)</label>' +
                '<input type="text" class="form-control" id="couponValue" placeholder="e.g. 10" required inputmode="decimal" autocomplete="off">' +
            '</div>' +
            '<div class="form-group">' +
                '<label for="couponMinOrder">Minimum Order <span style="color:var(--color-text-muted);font-weight:400">(optional)</span></label>' +
                '<input type="text" class="form-control" id="couponMinOrder" placeholder="No minimum" inputmode="decimal" autocomplete="off">' +
            '</div>' +
            '<div class="form-group">' +
                '<label for="couponMaxUses">Max Uses <span style="color:var(--color-text-muted);font-weight:400">(optional)</span></label>' +
                '<input type="text" class="form-control" id="couponMaxUses" placeholder="Unlimited" inputmode="numeric" autocomplete="off">' +
            '</div>' +
            '<div class="form-group">' +
                '<label for="couponExpiry">Expiry Date <span style="color:var(--color-text-muted);font-weight:400">(optional)</span></label>' +
                '<input type="date" class="form-control" id="couponExpiry" autocomplete="off">' +
            '</div>' +
            '<button type="submit" class="btn-block btn-primary" id="saveCouponBtn">Create Coupon</button>' +
        '</form>';

        TinyShop.openModal('New Coupon', html);

        // Generate code
        $('#generateCodeBtn').on('click', function() {ldelim}
            $('#couponCode').val(generateCode());
        {rdelim});

        // Type toggle
        $('.coupon-type-btn').on('click', function() {ldelim}
            $('.coupon-type-btn').removeClass('active');
            $(this).addClass('active');
            var type = $(this).data('type');
            $('#couponType').val(type);
            $('#couponValueLabel').text(type === 'percent' ? 'Discount (%)' : 'Discount (' + _currency + ')');
            $('#couponValue').attr('placeholder', type === 'percent' ? 'e.g. 10' : 'e.g. 500');
        {rdelim});

        // Submit
        $('#createCouponForm').on('submit', function(e) {ldelim}
            e.preventDefault();
            var code = $('#couponCode').val().trim().toUpperCase();
            var type = $('#couponType').val();
            var value = parseFloat($('#couponValue').val());
            var minOrder = $('#couponMinOrder').val().trim();
            var maxUses = $('#couponMaxUses').val().trim();
            var expiry = $('#couponExpiry').val();

            if (!code) {ldelim} TinyShop.toast('Enter a coupon code', 'error'); return; {rdelim}
            if (!value || value <= 0) {ldelim} TinyShop.toast('Enter a discount value', 'error'); return; {rdelim}
            if (type === 'percent' && value > 100) {ldelim} TinyShop.toast('Percent cannot exceed 100', 'error'); return; {rdelim}

            var payload = {ldelim}
                code: code,
                type: type,
                value: value
            {rdelim};
            if (minOrder) payload.min_order = parseFloat(minOrder);
            if (maxUses) payload.max_uses = parseInt(maxUses);
            if (expiry) payload.expires_at = expiry + ' 23:59:59';

            var $btn = $('#saveCouponBtn').prop('disabled', true).text('Creating...');
            TinyShop.api('POST', '/api/coupons', payload).done(function(res) {ldelim}
                _coupons.unshift(res.coupon);
                TinyShop.toast('Coupon created!');
                TinyShop.closeModal();
                renderCoupons();
            {rdelim}).fail(function(xhr) {ldelim}
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to create';
                TinyShop.toast(msg, 'error');
                $btn.prop('disabled', false).text('Create Coupon');
            {rdelim});
        {rdelim});
    {rdelim}

    loadCoupons();
{rdelim});
</script>
{/if}
{/block}
