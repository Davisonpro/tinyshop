{extends file="layouts/dashboard.tpl"}

{block name="content"}
<link rel="stylesheet" href="/public/css/billing.css?v={$asset_v}">
<div class="dash-topbar">
    <span class="dash-topbar-title">Billing</span>
    <a href="/dashboard/shop" class="dash-topbar-avatar">{$user.store_name|default:$user.name|escape|substr:0:1|upper}</a>
</div>

{* --- Current Plan --- *}
<div class="billing-section">
    <div class="form-section">
        <div class="plan-current-header">
            <span class="plan-current-name">{$usage.plan.name|escape}</span>
            {if $usage.is_free}
                <span class="plan-badge plan-badge-free">Free</span>
            {else}
                <span class="plan-badge plan-badge-paid">Paid</span>
            {/if}
        </div>
        {if $usage.is_free}
            <div class="plan-validity">Free plan &mdash; no expiration</div>
        {else}
            {if $usage.expires_at}
                <div class="plan-validity">
                    Valid until {$usage.expires_at|date_format:"%b %e, %Y"}
                    {if $usage.days_left > 0}
                        &middot; {$usage.days_left} days remaining
                    {/if}
                </div>
            {else}
                <div class="plan-validity">Active subscription</div>
            {/if}
        {/if}

        {* Usage meters *}
        <div class="usage-meters">
            {* Products *}
            <div class="usage-meter">
                <div class="usage-meter-header">
                    <span class="usage-meter-label">Products</span>
                    {if $usage.products_unlimited}
                        <span class="usage-meter-value">Unlimited</span>
                    {else}
                        <span class="usage-meter-value">{$usage.product_count} of {$usage.max_products} used</span>
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

        {* Feature list *}
        <div class="usage-feature">
            <span class="usage-feature-label">Themes</span>
            {if $usage.all_themes}
                <span class="usage-feature-status available">
                    <i class="fa-solid fa-check" aria-hidden="true"></i> All themes
                </span>
            {else}
                <span class="usage-feature-status unavailable">Classic only</span>
            {/if}
        </div>
        <div class="usage-feature">
            <span class="usage-feature-label">Custom domain</span>
            {if $usage.custom_domain}
                <span class="usage-feature-status available">
                    <i class="fa-solid fa-check" aria-hidden="true"></i> Available
                </span>
            {else}
                <span class="usage-feature-status unavailable">Not included</span>
            {/if}
        </div>
        <div class="usage-feature">
            <span class="usage-feature-label">Coupons</span>
            {if $usage.coupons}
                <span class="usage-feature-status available">
                    <i class="fa-solid fa-check" aria-hidden="true"></i> Available
                </span>
            {else}
                <span class="usage-feature-status unavailable">Not included</span>
            {/if}
        </div>

        {* Action buttons *}
        {if $usage.is_free}
            <a href="#plans" class="btn-block btn-accent mt-md" id="scrollToPlans">
                Upgrade Your Plan
            </a>
        {else}
            {if $active_sub}
                <button type="button" class="btn-block btn-muted mt-sm" id="cancelSubBtn">
                    Cancel Subscription
                </button>
            {/if}
        {/if}
    </div>

    {* --- Available Plans --- *}
    {if $plans|@count > 0}
    <div class="form-section" id="plans">
        <div class="form-section-title">Choose a Plan</div>

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
                                <span class="plan-card-cycle plan-cycle-monthly"> / month</span>
                                <span class="plan-card-cycle plan-cycle-yearly" style="display:none"> / year</span>
                            {/if}
                        </div>
                    </div>
                    <ul class="plan-card-features">
                        {if $plan.max_products}
                            <li><i class="fa-solid fa-check"></i> Up to {$plan.max_products} products</li>
                        {else}
                            <li><i class="fa-solid fa-check"></i> Unlimited products</li>
                        {/if}
                        {if $plan.all_themes}
                            <li><i class="fa-solid fa-check"></i> All shop themes</li>
                        {else}
                            <li><i class="fa-solid fa-check"></i> Classic theme</li>
                        {/if}
                        {if $plan.custom_domain}
                            <li><i class="fa-solid fa-check"></i> Custom domain</li>
                        {/if}
                        {if $plan.coupons}
                            <li><i class="fa-solid fa-check"></i> Coupon codes</li>
                        {/if}
                    </ul>
                    {if $is_current}
                        <button type="button" class="btn-block btn-secondary btn-sm">Current Plan</button>
                    {elseif $is_free_plan}
                        <button type="button" class="btn-block btn-secondary btn-sm">Free &mdash; no payment needed</button>
                    {else}
                        <button type="button" class="btn-block btn-accent btn-sm plan-choose-btn" data-plan-id="{$plan.id}" data-plan-name="{$plan.name|escape}">Choose {$plan.name|escape}</button>
                    {/if}
                </div>
            {/foreach}
        </div>
    </div>
    {/if}

    {* --- Billing History --- *}
    <div class="form-section">
        <button type="button" class="history-toggle" id="historyToggle">
            <span class="history-toggle-label">Billing History</span>
            <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
        </button>
        <div class="history-list" id="historyList">
            {if $history|@count > 0}
                {foreach $history as $record}
                    <div class="history-item">
                        <div class="history-item-left">
                            <span class="history-item-plan">{$record.plan_name|escape} ({$record.billing_cycle|escape})</span>
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
            {else}
                <div class="history-empty">No billing history yet</div>
            {/if}
        </div>
    </div>
</div>
{/block}

{block name="extra_scripts"}
<script>
$(function() {ldelim}
    var currentCycle = 'monthly';

    // --- Monthly / Yearly toggle ---
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

    // --- Scroll to plans on "Upgrade" click ---
    $('#scrollToPlans').on('click', function(e) {ldelim}
        e.preventDefault();
        var target = document.getElementById('plans');
        if (target) {ldelim}
            target.scrollIntoView({ldelim} behavior: 'smooth', block: 'start' {rdelim});
        {rdelim}
    {rdelim});

    // --- Subscribe to a plan ---
    var gateways = {$gateways|json_encode};

    function startPayment(planId, planName, cycle, gateway) {ldelim}
        TinyShop.api('POST', '/api/billing/subscribe', {ldelim}
            plan_id: planId,
            cycle: cycle,
            gateway: gateway
        {rdelim}).done(function(res) {ldelim}
            if (res.redirect_url) {ldelim}
                window.location = res.redirect_url;
            {rdelim} else {ldelim}
                TinyShop.toast('Subscription started!');
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
            $(this).prop('disabled', true).text('Processing...');
            startPayment(planId, planName, cycle, gateways[0]);
            return;
        {rdelim}

        // Multiple gateways — show picker
        var html = '<div class="gateway-summary">Subscribe to <strong>' + planName + '</strong> (' + cycle + ')</div>'
            + '<div class="gateway-picker">';

        for (var i = 0; i < gateways.length; i++) {ldelim}
            var gw = gateways[i];
            var iconClass = gw === 'stripe' ? 'fa-brands fa-stripe' : 'fa-brands fa-paypal';
            var label = gw === 'stripe' ? 'Stripe' : 'PayPal';
            var desc = gw === 'stripe' ? 'Pay with card' : 'Pay with PayPal';
            html += '<div class="gateway-option' + (i === 0 ? ' selected' : '') + '" data-gw="' + gw + '">'
                + '<div class="gateway-option-icon ' + gw + '"><i class="' + iconClass + '"></i></div>'
                + '<div class="gateway-option-info"><div class="gateway-option-name">' + label + '</div><div class="gateway-option-desc">' + desc + '</div></div>'
                + '<div class="gateway-option-check"><i class="fa-solid fa-check"></i></div>'
                + '</div>';
        {rdelim}

        html += '</div>'
            + '<button type="button" class="btn-block btn-accent" id="confirmGatewayBtn">Continue to Payment</button>';

        TinyShop.openModal('Payment Method', html);

        $('.gateway-option').on('click', function() {ldelim}
            $('.gateway-option').removeClass('selected');
            $(this).addClass('selected');
        {rdelim});

        $('#confirmGatewayBtn').on('click', function() {ldelim}
            var selectedGw = $('.gateway-option.selected').data('gw');
            $(this).prop('disabled', true).text('Processing...');
            TinyShop.closeModal();
            startPayment(planId, planName, cycle, selectedGw);
        {rdelim});
    {rdelim});

    // --- Cancel subscription ---
    $('#cancelSubBtn').on('click', function() {ldelim}
        TinyShop.confirm(
            'Cancel Subscription?',
            'Your plan will remain active until the end of the current billing period. After that, you\'ll be switched to the free plan.',
            'Cancel Subscription',
            function() {ldelim}
                $('#confirmModalOk').prop('disabled', true).text('Cancelling...');
                TinyShop.api('POST', '/api/billing/cancel').done(function() {ldelim}
                    TinyShop.toast('Subscription cancelled');
                    setTimeout(function() {ldelim} location.reload(); {rdelim}, 600);
                {rdelim}).fail(function(xhr) {ldelim}
                    var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to cancel';
                    TinyShop.toast(msg, 'error');
                    TinyShop.closeModal();
                {rdelim});
            {rdelim},
            'danger'
        );
    {rdelim});

    // --- Billing history toggle ---
    $('#historyToggle').on('click', function() {ldelim}
        $(this).toggleClass('open');
        $('#historyList').toggleClass('open');
    {rdelim});
{rdelim});
</script>
{/block}
