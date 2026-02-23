{extends file="layouts/admin.tpl"}

{block name="content"}
<div class="dash-topbar">
    <a href="/admin/sellers" class="dash-topbar-back" aria-label="Back to sellers">
        <i class="fa-solid fa-chevron-left icon-lg"></i>
    </a>
    <span class="dash-topbar-title">{$seller.store_name|escape}</span>
    {if $seller.subdomain}
    <a href="{$scheme}://{$seller.subdomain|escape}.{$base_domain}" target="_blank" class="btn-icon" title="Visit shop">
        <i class="fa-solid fa-arrow-up-right-from-square icon-lg"></i>
    </a>
    {/if}
</div>

<div class="sd-wrap">
    {* Profile header *}
    <div class="sd-profile">
        <div class="sd-avatar{if !$seller.is_active} suspended{/if}">
            {$seller.store_name|escape|substr:0:1|upper}
        </div>
        <h2 class="sd-name">{$seller.store_name|escape}</h2>
        <p class="sd-email">{$seller.email|escape}</p>
        {if $seller.subdomain}
            <p class="sd-domain">{$seller.subdomain|escape}.{$base_domain}</p>
        {/if}
        <div class="sd-status-row">
            <button type="button" class="sd-status-btn{if $seller.is_active} active{/if}" id="sellerToggle" data-id="{$seller.id}" data-active="{$seller.is_active}">
                <span class="sd-status-dot"></span>
                {if $seller.is_active}Active{else}Suspended{/if}
            </button>
            <button type="button" class="sd-status-btn sd-showcase-btn{if $seller.is_showcased} showcased{/if}" id="showcaseToggle" data-id="{$seller.id}" data-showcased="{$seller.is_showcased}">
                <i class="fa-solid fa-star"></i>
                {if $seller.is_showcased}Featured{else}Feature{/if}
            </button>
        </div>
    </div>

    {* Stats *}
    <div class="sd-stats">
        <div class="sd-stat">
            <div class="sd-stat-num">{$seller.product_count}</div>
            <div class="sd-stat-label">Products</div>
        </div>
        <div class="sd-stat">
            <div class="sd-stat-num">{$seller.order_count}</div>
            <div class="sd-stat-label">Orders</div>
        </div>
        <div class="sd-stat">
            <div class="sd-stat-num">{$seller.view_count}</div>
            <div class="sd-stat-label">Views</div>
        </div>
    </div>

    {* Details card *}
    <div class="sd-details">
        <div class="sd-detail-row">
            <span class="sd-detail-label">Joined</span>
            <span class="sd-detail-value">{$seller.created_at|date_format:"%b %e, %Y"}</span>
        </div>
        <div class="sd-detail-row">
            <span class="sd-detail-label">Last login</span>
            <span class="sd-detail-value">{if $seller.last_login_at}{$seller.last_login_at|date_format:"%b %e, %Y %H:%M"}{else}Never{/if}</span>
        </div>
        <div class="sd-detail-row">
            <span class="sd-detail-label">Login count</span>
            <span class="sd-detail-value">{$seller.login_count}</span>
        </div>
        <div class="sd-detail-row">
            <span class="sd-detail-label">Currency</span>
            <span class="sd-detail-value">{$seller.currency|default:'KES'}</span>
        </div>
        <div class="sd-detail-row">
            <span class="sd-detail-label">Plan</span>
            <span class="sd-detail-value" id="sellerPlanValue">
                {assign var="plan_name" value="Free"}
                {foreach $plans as $p}
                    {if $p.id == $seller.plan_id}{assign var="plan_name" value=$p.name}{/if}
                {/foreach}
                {$plan_name|escape}
                {if $seller.plan_expires_at}
                    {if $plan_expired}
                        <span class="badge badge-orange" style="margin-left:6px">Expired</span>
                    {else}
                        <span style="color:var(--color-text-muted);margin-left:6px">until {$seller.plan_expires_at|date_format:"%b %e, %Y"}</span>
                    {/if}
                {/if}
            </span>
        </div>
    </div>

    {* Actions *}
    <div class="sd-actions">
        <button type="button" class="sd-action-btn" id="changePlanBtn" data-id="{$seller.id}">
            <div class="sd-action-icon" style="background:linear-gradient(135deg,#EEF2FF,#E0E7FF);color:#6366F1"><i class="fa-solid fa-arrow-up-right-dots"></i></div>
            <div class="sd-action-text">
                <span class="sd-action-title">Change plan</span>
                <span class="sd-action-desc">Update subscription plan</span>
            </div>
            <i class="fa-solid fa-chevron-right sd-action-chevron"></i>
        </button>
        <button type="button" class="sd-action-btn" id="impersonateBtn" data-id="{$seller.id}">
            <div class="sd-action-icon blue"><i class="fa-solid fa-user-secret"></i></div>
            <div class="sd-action-text">
                <span class="sd-action-title">Log in as seller</span>
                <span class="sd-action-desc">View their dashboard</span>
            </div>
            <i class="fa-solid fa-chevron-right sd-action-chevron"></i>
        </button>
        <button type="button" class="sd-action-btn danger" id="deleteSellerBtn" data-id="{$seller.id}">
            <div class="sd-action-icon red"><i class="fa-solid fa-trash-can"></i></div>
            <div class="sd-action-text">
                <span class="sd-action-title">Delete account</span>
                <span class="sd-action-desc">Permanently remove all data</span>
            </div>
            <i class="fa-solid fa-chevron-right sd-action-chevron"></i>
        </button>
    </div>

    {* Products *}
    {if $products|count > 0}
    <div class="sd-section">
        <div class="sd-section-header">
            <h3>Products</h3>
            <span class="sd-section-count">{$products|count}</span>
        </div>
        <div class="sd-product-list">
            {foreach $products as $product}
            <div class="sd-product">
                <div class="sd-product-info">
                    {if $seller.subdomain}
                        <a href="{$scheme}://{$seller.subdomain|escape}.{$base_domain}/{$product.slug|default:$product.id}" target="_blank" class="sd-product-name">{$product.name|escape|truncate:40}</a>
                    {else}
                        <span class="sd-product-name">{$product.name|escape|truncate:40}</span>
                    {/if}
                    <span class="sd-product-price">{$seller.currency|default:'KES'} {$product.price|number_format:2}</span>
                </div>
                <div class="sd-product-badges">
                    {if $product.is_active}<span class="badge badge-green">Active</span>{else}<span class="badge badge-muted">Hidden</span>{/if}
                    {if $product.is_sold}<span class="badge badge-orange">Sold</span>{/if}
                    {if $product.is_featured}<span class="badge badge-purple">Featured</span>{/if}
                </div>
            </div>
            {/foreach}
        </div>
    </div>
    {/if}
</div>
{/block}

{block name="extra_scripts"}
<script>
(function() {ldelim}
    // Status toggle
    $('#sellerToggle').on('click', function() {ldelim}
        var $el = $(this);
        var id = $el.data('id');
        var currentlyActive = String($el.data('active')) === '1';
        var newState = !currentlyActive;

        $el.prop('disabled', true);
        TinyShop.api('PUT', '/api/admin/sellers/' + id + '/toggle', {ldelim} is_active: newState {rdelim})
            .done(function(res) {ldelim}
                if (res.success) {ldelim}
                    $el.data('active', newState ? '1' : '0');
                    $el.toggleClass('active', newState);
                    $el.find('.sd-status-dot').length;
                    var label = newState ? 'Active' : 'Suspended';
                    $el.html('<span class="sd-status-dot"></span>' + label);
                    // Update avatar
                    $('.sd-avatar').toggleClass('suspended', !newState);
                    TinyShop.toast(newState ? 'Seller activated' : 'Seller suspended', 'success');
                {rdelim}
            {rdelim})
            .fail(function(xhr) {ldelim}
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to update status';
                TinyShop.toast(msg, 'error');
            {rdelim})
            .always(function() {ldelim}
                $el.prop('disabled', false);
            {rdelim});
    {rdelim});

    // Showcase toggle
    $('#showcaseToggle').on('click', function() {ldelim}
        var $el = $(this);
        var id = $el.data('id');
        var current = String($el.data('showcased')) === '1';
        var newState = !current;

        $el.prop('disabled', true);
        TinyShop.api('PUT', '/api/admin/sellers/' + id + '/toggle', {ldelim} is_showcased: newState ? 1 : 0 {rdelim})
            .done(function(res) {ldelim}
                if (res.success) {ldelim}
                    $el.data('showcased', newState ? '1' : '0');
                    $el.toggleClass('showcased', newState);
                    $el.html('<i class="fa-solid fa-star"></i> ' + (newState ? 'Featured' : 'Feature'));
                    TinyShop.toast(newState ? 'Shop featured on landing page' : 'Shop removed from landing page');
                {rdelim}
            {rdelim})
            .fail(function(xhr) {ldelim}
                TinyShop.toast('Failed to update', 'error');
            {rdelim})
            .always(function() {ldelim}
                $el.prop('disabled', false);
            {rdelim});
    {rdelim});

    // Change plan
    $('#changePlanBtn').on('click', function() {ldelim}
        var id = $(this).data('id');
        var currentPlanId = '{$seller.plan_id|default:0}';
        var currentExpiry = '{$seller.plan_expires_at|default:""}';
        if (currentExpiry) {ldelim}
            currentExpiry = currentExpiry.substring(0, 10);
        {rdelim}

        var html = '<div>'
            + '<div class="form-group">'
            + '<label for="planSelect">Plan</label>'
            + '<select id="planSelect" class="form-control">'
            + '<option value="0"' + (currentPlanId == '0' || !currentPlanId ? ' selected' : '') + '>Free (no plan)</option>'
            {foreach $plans as $p}
            + '<option value="{$p.id}"' + (currentPlanId == '{$p.id}' ? ' selected' : '') + '>{$p.name|escape} &mdash; {$p.currency|default:"KES"} {$p.price_monthly|number_format:0}/mo</option>'
            {/foreach}
            + '</select>'
            + '</div>'
            + '<div class="form-group" id="expiryField">'
            + '<label for="planExpiry">Expires on <span style="color:var(--color-text-muted);font-weight:400">(optional)</span></label>'
            + '<input type="date" id="planExpiry" class="form-control" value="' + currentExpiry + '">'
            + '</div>'
            + '<button type="button" id="savePlanBtn" class="btn-block btn-primary" style="margin-top:8px">Save</button>'
            + '</div>';

        TinyShop.openModal('Change Plan', html);

        function toggleExpiry() {ldelim}
            var val = $('#planSelect').val();
            if (val === '0') {ldelim}
                $('#expiryField').hide();
                $('#planExpiry').val('');
            {rdelim} else {ldelim}
                $('#expiryField').show();
            {rdelim}
        {rdelim}
        toggleExpiry();
        $('#planSelect').on('change', toggleExpiry);

        $('#savePlanBtn').on('click', function() {ldelim}
            var $btn = $(this);
            var planId = parseInt($('#planSelect').val(), 10);
            var expiry = $('#planExpiry').val() || '';

            $btn.prop('disabled', true).text('Saving...');
            TinyShop.api('PUT', '/api/admin/sellers/' + id + '/plan', {ldelim}
                plan_id: planId,
                plan_expires_at: expiry
            {rdelim})
            .done(function(res) {ldelim}
                if (res.success) {ldelim}
                    var label = res.plan_name || 'Free';
                    var expiryHtml = '';
                    if (expiry && planId > 0) {ldelim}
                        var d = new Date(expiry + 'T00:00:00');
                        var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                        expiryHtml = ' <span style="color:var(--color-text-muted);margin-left:6px">until ' + months[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear() + '</span>';
                    {rdelim}
                    $('#sellerPlanValue').html(label + expiryHtml);
                    TinyShop.toast('Plan updated', 'success');
                    TinyShop.closeModal();
                {rdelim}
            {rdelim})
            .fail(function(xhr) {ldelim}
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to update plan';
                TinyShop.toast(msg, 'error');
                $btn.prop('disabled', false).text('Save');
            {rdelim});
        {rdelim});
    {rdelim});

    // Impersonate
    $('#impersonateBtn').on('click', function() {ldelim}
        var id = $(this).data('id');
        TinyShop.confirm('Log in as this seller?', 'You will be viewing their dashboard. Click "Exit" in the banner to return to admin.', 'Continue', function() {ldelim}
            $('#confirmModalOk').prop('disabled', true).text('Switching...');
            TinyShop.api('POST', '/api/admin/sellers/' + id + '/impersonate')
                .done(function(res) {ldelim}
                    if (res.success) {ldelim}
                        window.location.href = res.redirect || '/dashboard';
                    {rdelim}
                {rdelim})
                .fail(function(xhr) {ldelim}
                    var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to impersonate';
                    TinyShop.toast(msg, 'error');
                    TinyShop.closeModal();
                {rdelim});
        {rdelim});
    {rdelim});

    // Delete
    $('#deleteSellerBtn').on('click', function() {ldelim}
        var id = $(this).data('id');
        TinyShop.confirm('Delete this account?', 'This will permanently delete this seller and ALL their data (products, orders, images). This cannot be undone.', 'Delete', function() {ldelim}
            $('#confirmModalOk').prop('disabled', true).text('Deleting...');
            TinyShop.api('DELETE', '/api/admin/sellers/' + id)
                .done(function(res) {ldelim}
                    if (res.success) {ldelim}
                        TinyShop.toast('Account deleted', 'success');
                        setTimeout(function() {ldelim} TinyShop.navigate('/admin/sellers'); {rdelim}, 800);
                    {rdelim}
                {rdelim})
                .fail(function(xhr) {ldelim}
                    var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to delete';
                    TinyShop.toast(msg, 'error');
                    TinyShop.closeModal();
                {rdelim});
        {rdelim}, 'danger');
    {rdelim});
{rdelim})();
</script>
{/block}
