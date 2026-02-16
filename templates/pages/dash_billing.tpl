{extends file="layouts/dashboard.tpl"}

{block name="extra_css"}
<link rel="stylesheet" href="/public/css/billing{$min}.css?v={$asset_v}">
{/block}

{block name="content"}
<div class="dash-topbar">
    <span class="dash-topbar-title">Billing</span>
    <a href="/dashboard/shop" class="dash-topbar-avatar">{$user.store_name|escape|substr:0:1|upper}</a>
</div>

{* --- Current Plan --- *}
<div class="billing-section">
    <div class="form-section">
        <div class="plan-current-header">
            <span class="plan-current-name">{$usage.plan.name|escape}</span>
            {if $usage.is_free}
                <span class="plan-badge plan-badge-free">Free</span>
            {else}
                <span class="plan-badge plan-badge-paid">Active</span>
            {/if}
        </div>
        {if $usage.is_free}
            <div class="plan-validity">No time limit on the free plan</div>
        {else}
            {if $usage.expires_at}
                <div class="plan-validity">
                    {if $usage.days_left > 0}
                        {$usage.days_left} days left &middot; renews {$usage.expires_at|date_format:"%b %e, %Y"}
                    {else}
                        Valid until {$usage.expires_at|date_format:"%b %e, %Y"}
                    {/if}
                </div>
            {else}
                <div class="plan-validity">Your plan renews automatically</div>
            {/if}
        {/if}

        {* Products usage *}
        <div class="usage-meters">
            <div class="usage-meter">
                <div class="usage-meter-header">
                    <span class="usage-meter-label">Products</span>
                    {if $usage.products_unlimited}
                        <span class="usage-meter-value">Unlimited</span>
                    {else}
                        <span class="usage-meter-value">{$usage.product_count} / {$usage.max_products}</span>
                    {/if}
                </div>
                {if !$usage.products_unlimited && $usage.max_products > 0}
                    {assign var="pct" value=($usage.product_count / $usage.max_products * 100)}
                    <div class="usage-bar">
                        <div class="usage-bar-fill{if $pct >= 100} full{elseif $pct >= 80} warning{/if}" style="width:{if $pct > 100}100{else}{$pct|string_format:'%.0f'}{/if}%"></div>
                    </div>
                {/if}
            </div>
        </div>

        {* Action buttons *}
        {if $usage.can_upgrade}
            <a href="#plans" class="btn-block btn-accent mt-md" id="scrollToPlans">
                Upgrade
            </a>
        {/if}
        {if !$usage.is_free && $active_sub}
            <button type="button" class="btn-block btn-muted mt-sm" id="cancelSubBtn">
                Cancel Plan
            </button>
        {/if}
    </div>

    {* --- Available Plans --- *}
    {if $plans|@count > 0}
    <div class="form-section" id="plans">
        <div class="form-section-title">Plans</div>

        <div class="cycle-toggle-wrap">
            <div class="cycle-toggle">
                <button type="button" class="cycle-toggle-btn active" data-cycle="monthly">Monthly</button>
                <button type="button" class="cycle-toggle-btn" data-cycle="yearly">Yearly</button>
            </div>
        </div>

        <div class="plan-cards">
            {foreach $plans as $plan}
                {assign var="is_current" value=($usage.plan.id == $plan.id)}
                {assign var="is_free_plan" value=($plan.price_monthly == 0 && $plan.price_yearly == 0)}
                <div class="plan-card{if $is_current} current{/if}" data-plan-id="{$plan.id}">
                    <div class="plan-card-header">
                        <span class="plan-card-name">{$plan.name|escape}</span>
                        <div class="plan-card-price">
                            {if $is_free_plan}
                                <span class="plan-card-amount">Free</span>
                            {else}
                                <span class="plan-card-amount plan-price-monthly">{$plan.currency|escape} {$plan.price_monthly|number_format:0:".":","}</span>
                                <span class="plan-card-amount plan-price-yearly" style="display:none">{$plan.currency|escape} {$plan.price_yearly|number_format:0:".":","}</span>
                                <span class="plan-card-cycle plan-cycle-monthly"> / mo</span>
                                <span class="plan-card-cycle plan-cycle-yearly" style="display:none"> / yr</span>
                            {/if}
                        </div>
                    </div>
                    <ul class="plan-card-features">
                        {if $plan.max_products}
                            <li><i class="fa-solid fa-check"></i> {$plan.max_products} products</li>
                        {else}
                            <li><i class="fa-solid fa-check"></i> Unlimited products</li>
                        {/if}
                        {if $plan.all_themes}
                            <li><i class="fa-solid fa-check"></i> All themes</li>
                        {/if}
                        {if $plan.custom_domain}
                            <li><i class="fa-solid fa-check"></i> Custom domain</li>
                        {/if}
                        {if $plan.coupons}
                            <li><i class="fa-solid fa-check"></i> Coupons</li>
                        {/if}
                    </ul>
                    {if $is_current}
                        <span class="plan-current-label">Your plan</span>
                    {elseif !$is_free_plan}
                        <button type="button" class="btn-block btn-accent btn-sm plan-choose-btn" data-plan-id="{$plan.id}" data-plan-name="{$plan.name|escape}">Get {$plan.name|escape}</button>
                    {/if}
                </div>
            {/foreach}
        </div>
    </div>
    {/if}

    {* --- Billing History (only show if there are records) --- *}
    {if $history|@count > 0}
    <div class="form-section">
        <button type="button" class="history-toggle" id="historyToggle">
            <span class="history-toggle-label">Payment History</span>
            <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
        </button>
        <div class="history-list" id="historyList">
            {foreach $history as $record}
                <div class="history-item">
                    <div class="history-item-left">
                        <span class="history-item-plan">{$record.plan_name|escape}</span>
                        <span class="history-item-date">
                            {$record.starts_at|date_format:"%b %e, %Y"}
                            {if $record.expires_at} &ndash; {$record.expires_at|date_format:"%b %e, %Y"}{/if}
                        </span>
                    </div>
                    <div class="history-item-right">
                        <span class="history-item-amount">{$usage.plan.currency|default:'KES'|escape} {$record.amount_paid|number_format:0:".":","}</span>
                        {if $record.status == 'active'}
                            <span class="history-status history-status-active">Active</span>
                        {elseif $record.status == 'cancelled'}
                            <span class="history-status history-status-cancelled">Cancelled</span>
                        {else}
                            <span class="history-status history-status-expired">Expired</span>
                        {/if}
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
$(function() {ldelim}
    var currentCycle = 'monthly';

    // Monthly / Yearly toggle
    $('.cycle-toggle-btn').on('click', function() {ldelim}
        var cycle = $(this).data('cycle');
        if (cycle === currentCycle) return;
        currentCycle = cycle;

        $('.cycle-toggle-btn').removeClass('active');
        $(this).addClass('active');

        if (cycle === 'yearly') {ldelim}
            $('.plan-price-monthly').hide();
            $('.plan-price-yearly').show();
            $('.plan-cycle-monthly').hide();
            $('.plan-cycle-yearly').show();
        {rdelim} else {ldelim}
            $('.plan-price-monthly').show();
            $('.plan-price-yearly').hide();
            $('.plan-cycle-monthly').show();
            $('.plan-cycle-yearly').hide();
        {rdelim}
    {rdelim});

    // Upgrade — if only one paid plan, go straight to checkout
    $('#scrollToPlans').on('click', function(e) {ldelim}
        e.preventDefault();
        var chooseBtns = $('.plan-choose-btn');
        if (chooseBtns.length === 1) {ldelim}
            chooseBtns.trigger('click');
        {rdelim} else {ldelim}
            var target = document.getElementById('plans');
            if (target) {ldelim}
                target.scrollIntoView({ldelim} behavior: 'smooth', block: 'start' {rdelim});
            {rdelim}
        {rdelim}
    {rdelim});

    // Subscribe to a plan
    var gateways = {$gateways|json_encode};

    function startPayment(planId, planName, cycle, gateway) {ldelim}
        var payload = {ldelim}
            plan_id: planId,
            cycle: cycle,
            gateway: gateway
        {rdelim};
        if (gateway === 'mpesa') {ldelim}
            payload.mpesa_phone = $('#billingMpesaPhoneInput').val() ? $('#billingMpesaPhoneInput').val().trim() : '';
            if (!payload.mpesa_phone) {ldelim}
                TinyShop.toast('Enter your M-Pesa phone number', 'error');
                return;
            {rdelim}
        {rdelim}

        TinyShop.api('POST', '/api/billing/subscribe', payload).done(function(res) {ldelim}
            if (res.gateway === 'mpesa' && res.poll_url) {ldelim}
                var waitHtml = '<div style="text-align:center;padding:20px 0">' +
                    '<div class="btn-spinner" style="width:32px;height:32px;margin:0 auto 16px"></div>' +
                    '<div style="font-weight:600;font-size:1rem;margin-bottom:8px">Check your phone</div>' +
                    '<div style="color:var(--color-text-muted);font-size:0.875rem;line-height:1.5">Enter your M-Pesa PIN when prompted.<br>This page will update automatically.</div>' +
                    '</div>';
                TinyShop.openModal('Waiting for payment', waitHtml);

                var pollCount = 0;
                var maxPolls = 60;
                var pollTimer = setInterval(function() {ldelim}
                    pollCount++;
                    if (pollCount >= maxPolls) {ldelim}
                        clearInterval(pollTimer);
                        TinyShop.closeModal();
                        TinyShop.toast('Payment not received. Please try again.', 'error');
                        return;
                    {rdelim}
                    $.get(res.poll_url, function(statusRes) {ldelim}
                        if (statusRes.status === 'paid') {ldelim}
                            clearInterval(pollTimer);
                            TinyShop.closeModal();
                            TinyShop.toast('Plan activated!');
                            setTimeout(function() {ldelim} location.reload(); {rdelim}, 600);
                        {rdelim}
                    {rdelim});
                {rdelim}, 2000);
                return;
            {rdelim}
            if (res.redirect_url) {ldelim}
                window.location = res.redirect_url;
            {rdelim} else {ldelim}
                TinyShop.toast('Plan activated!');
                setTimeout(function() {ldelim} location.reload(); {rdelim}, 600);
            {rdelim}
        {rdelim}).fail(function(xhr) {ldelim}
            var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Something went wrong';
            TinyShop.toast(msg, 'error');
        {rdelim});
    {rdelim}

    $('.plan-choose-btn').on('click', function() {ldelim}
        var planId = $(this).data('plan-id');
        var planName = $(this).data('plan-name');
        var cycle = currentCycle;

        if (gateways.length === 0) {ldelim}
            TinyShop.openModal('Payment', '<div class="no-gateways-msg"><i class="fa-solid fa-credit-card"></i>No payment methods available.<br>Please contact support.</div>');
            return;
        {rdelim}

        if (gateways.length === 1) {ldelim}
            if (gateways[0] === 'mpesa') {ldelim}
                // Need phone input first — show it in a quick modal
                var phoneHtml = '<div class="gateway-summary">Subscribe to <strong>' + planName + '</strong> (' + cycle + ')</div>'
                    + '<div style="margin:12px 0">'
                    + '<label style="font-size:0.8125rem;font-weight:600;margin-bottom:4px;display:block">M-Pesa Phone Number</label>'
                    + '<input type="tel" id="billingMpesaPhoneInput" class="form-control" placeholder="e.g. 0712 345 678" inputmode="numeric">'
                    + '</div>'
                    + '<button type="button" class="btn-block btn-accent" id="confirmMpesaPhoneBtn">Pay via M-Pesa</button>';
                TinyShop.openModal('Payment Method', phoneHtml);
                $('#confirmMpesaPhoneBtn').on('click', function() {ldelim}
                    $(this).prop('disabled', true).text('Processing...');
                    TinyShop.closeModal();
                    startPayment(planId, planName, cycle, 'mpesa');
                {rdelim});
                return;
            {rdelim}
            $(this).prop('disabled', true).text('Processing...');
            startPayment(planId, planName, cycle, gateways[0]);
            return;
        {rdelim}

        // Multiple gateways — show picker
        var html = '<div class="gateway-summary">Subscribe to <strong>' + planName + '</strong> (' + cycle + ')</div>'
            + '<div class="gateway-picker">';

        for (var i = 0; i < gateways.length; i++) {ldelim}
            var gw = gateways[i];
            var iconHtml, label, desc;
            if (gw === 'stripe') {ldelim}
                iconHtml = '<i class="fa-brands fa-stripe"></i>';
                label = 'Stripe'; desc = 'Pay with card';
            {rdelim} else if (gw === 'paypal') {ldelim}
                iconHtml = '<i class="fa-brands fa-paypal"></i>';
                label = 'PayPal'; desc = 'Pay with PayPal';
            {rdelim} else if (gw === 'mpesa') {ldelim}
                iconHtml = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="12" fill="#4CAF50"/><text x="12" y="16" text-anchor="middle" font-size="10" font-weight="bold" fill="white">M</text></svg>';
                label = 'M-Pesa'; desc = 'Pay with M-Pesa';
            {rdelim} else {ldelim}
                iconHtml = '<i class="fa-solid fa-credit-card"></i>';
                label = gw; desc = '';
            {rdelim}
            html += '<div class="gateway-option' + (i === 0 ? ' selected' : '') + '" data-gw="' + gw + '">'
                + '<div class="gateway-option-icon ' + gw + '">' + iconHtml + '</div>'
                + '<div class="gateway-option-info"><div class="gateway-option-name">' + label + '</div><div class="gateway-option-desc">' + desc + '</div></div>'
                + '<div class="gateway-option-check"><i class="fa-solid fa-check"></i></div>'
                + '</div>';
        {rdelim}

        html += '</div>'
            + '<div id="billingMpesaPhone" style="display:none;margin-top:12px">'
            + '<label style="font-size:0.8125rem;font-weight:600;margin-bottom:4px;display:block">M-Pesa Phone Number</label>'
            + '<input type="tel" id="billingMpesaPhoneInput" class="form-control" placeholder="e.g. 0712 345 678" inputmode="numeric">'
            + '</div>'
            + '<button type="button" class="btn-block btn-accent" id="confirmGatewayBtn" style="margin-top:16px">Continue</button>';

        TinyShop.openModal('Payment Method', html);

        $('.gateway-option').on('click', function() {ldelim}
            $('.gateway-option').removeClass('selected');
            $(this).addClass('selected');
            var gw = $(this).data('gw');
            $('#billingMpesaPhone').toggle(gw === 'mpesa');
        {rdelim});

        $('#confirmGatewayBtn').on('click', function() {ldelim}
            var selectedGw = $('.gateway-option.selected').data('gw');
            $(this).prop('disabled', true).text('Processing...');
            TinyShop.closeModal();
            startPayment(planId, planName, cycle, selectedGw);
        {rdelim});
    {rdelim});

    // Cancel plan
    $('#cancelSubBtn').on('click', function() {ldelim}
        TinyShop.confirm(
            'Cancel your plan?',
            'You\'ll keep your current plan until the end of this billing period. After that, you\'ll switch to the free plan.',
            'Yes, cancel',
            function() {ldelim}
                $('#confirmModalOk').prop('disabled', true).text('Cancelling...');
                TinyShop.api('POST', '/api/billing/cancel').done(function() {ldelim}
                    TinyShop.toast('Plan cancelled');
                    setTimeout(function() {ldelim} location.reload(); {rdelim}, 600);
                {rdelim}).fail(function(xhr) {ldelim}
                    var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Something went wrong';
                    TinyShop.toast(msg, 'error');
                    TinyShop.closeModal();
                {rdelim});
            {rdelim},
            'danger'
        );
    {rdelim});

    // Billing history toggle
    $('#historyToggle').on('click', function() {ldelim}
        $(this).toggleClass('open');
        $('#historyList').toggleClass('open');
    {rdelim});
{rdelim});
</script>
{/block}
