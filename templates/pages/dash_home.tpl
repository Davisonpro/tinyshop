{extends file="layouts/dashboard.tpl"}

{block name="content"}
{* Greeting header *}
<div class="dash-greeting">
    <div class="dash-greeting-row">
        <div>
            <small>Welcome back</small>
            <h1>Hi, {$user.store_name|escape}!</h1>
        </div>
        <a href="/dashboard/shop" class="dash-avatar">{$user.store_name|escape|substr:0:1|upper}</a>
    </div>
</div>

{* Upgrade banner *}
{if !empty($usage) && $usage.can_upgrade}
<a href="/dashboard/billing" class="upgrade-banner">
    <div class="upgrade-banner-content">
        <i class="fa-solid fa-crown icon-md" style="color:#F5A623"></i>
        <div>
            <strong>You're on the {$usage.plan.name|escape} plan</strong>
            <span>{if $usage.products_unlimited}Unlimited products{elseif $usage.product_count >= $usage.max_products}Product limit reached ({$usage.max_products}){else}{$usage.product_count} of {$usage.max_products} products used{/if}</span>
        </div>
    </div>
    <i class="fa-solid fa-chevron-right icon-sm text-muted"></i>
</a>
{/if}

{* Stats *}
<div class="stats-panel">
    <div class="stats-panel-grid">
        <div class="stats-panel-metric">
            <div class="stats-panel-number">{$view_stats.today|default:0|number_format:0:'.':','}</div>
            <div class="stats-panel-label">Views Today</div>
        </div>
        <div class="stats-panel-metric">
            <div class="stats-panel-number">{$product_count|default:0|number_format:0:'.':','}</div>
            <div class="stats-panel-label">Products</div>
        </div>
        <div class="stats-panel-metric">
            <div class="stats-panel-number">{$order_stats.total|default:0|number_format:0:'.':','}</div>
            <div class="stats-panel-label">Orders</div>
        </div>
        <div class="stats-panel-metric">
            <div class="stats-panel-number">{if $order_stats.revenue > 0}<span class="stats-panel-currency">{$currency}</span> {$order_stats.revenue|default:0|number_format:0:'.':','}{else}0{/if}</div>
            <div class="stats-panel-label">Revenue</div>
        </div>
    </div>
</div>

{* Low Stock Alert *}
{if !empty($low_stock_products)}
<div class="low-stock-card">
    <div class="low-stock-label"><i class="fa-solid fa-arrow-down"></i> Low Stock</div>
    {foreach $low_stock_products as $p}
    <a href="/dashboard/products/{$p.id}/edit" class="low-stock-item">
        <span class="low-stock-item-name">{$p.name|escape}</span>
        <span class="low-stock-badge">{$p.stock_quantity} left</span>
    </a>
    {/foreach}
</div>
{/if}

{* Onboarding checklist (shows until dismissed) *}
{if !empty($onboarding_steps)}
{assign var="completed_count" value=0}
{foreach $onboarding_steps as $step}
    {if $step.done}{assign var="completed_count" value=$completed_count+1}{/if}
{/foreach}
<div class="onboarding-card" id="onboardingCard" style="display:none">
    <div class="onboarding-title">Getting Started</div>
    <div style="font-size:0.8125rem;color:var(--color-text-muted);margin-bottom:4px" id="onboardingProgress">{$completed_count} of {$onboarding_steps|@count} complete</div>
    <div class="onboarding-progress">
        <div class="onboarding-progress-fill" id="onboardingBar" style="width:{$completed_count / $onboarding_steps|@count * 100}%"></div>
    </div>
    <div class="onboarding-steps">
        {foreach $onboarding_steps as $step}
        <a href="{$step.link}" class="onboarding-step{if $step.done} completed{/if}" data-key="{$step.key}">
            <div class="onboarding-step-icon">
                {if $step.done}
                    <i class="fa-solid fa-check"></i>
                {else}
                    <i class="fa-solid fa-circle" style="font-size:8px"></i>
                {/if}
            </div>
            <span class="onboarding-step-text">{$step.label}</span>
        </a>
        {/foreach}
    </div>
    <button type="button" class="onboarding-dismiss" onclick="this.closest('.onboarding-card').style.display='none';try{ldelim}localStorage.setItem('tinyshop_onboard_dismissed','1'){rdelim}catch(e){ldelim}{rdelim}">I'll do this later</button>
</div>
<script>
(function() {
    var card = document.getElementById('onboardingCard');
    if (!card) return;
    if (localStorage.getItem('tinyshop_onboard_dismissed') === '1') return;

    // Check homescreen step from localStorage
    var hsStep = card.querySelector('.onboarding-step[data-key="homescreen"]');
    var hsCompleted = localStorage.getItem('tinyshop_homescreen_added') === '1';
    if (hsStep && hsCompleted && !hsStep.classList.contains('completed')) {
        hsStep.classList.add('completed');
        hsStep.querySelector('.onboarding-step-icon').innerHTML = '<i class="fa-solid fa-check"></i>';
    }

    // Recalculate progress
    var steps = card.querySelectorAll('.onboarding-step');
    var done = card.querySelectorAll('.onboarding-step.completed').length;
    var total = steps.length;
    if (done >= total) return; // All done — hide onboarding
    card.querySelector('#onboardingProgress').textContent = done + ' of ' + total + ' complete';
    card.querySelector('#onboardingBar').style.width = (done / total * 100) + '%';
    card.style.display = '';

    // Homescreen step — open modal instead of navigating
    if (hsStep) {
        hsStep.addEventListener('click', function(e) {
            e.preventDefault();
            if (hsStep.classList.contains('completed')) return;

            var ua = navigator.userAgent || '';
            var isIOS = /iPad|iPhone|iPod/.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
            var isAndroid = /Android/.test(ua);
            var isSafari = /Safari/.test(ua) && !/CriOS|Chrome/.test(ua);

            var html = '<div style="padding:4px 0">';

            if (isIOS) {
                html += '<div class="a2hs-instruction">' +
                    '<div class="a2hs-step-num">1</div>' +
                    '<div class="a2hs-step-text">Tap the <strong>Share</strong> button <i class="fa-solid fa-arrow-up-from-bracket" style="color:var(--color-accent)"></i> at the bottom of Safari</div>' +
                '</div>' +
                '<div class="a2hs-instruction">' +
                    '<div class="a2hs-step-num">2</div>' +
                    '<div class="a2hs-step-text">Scroll down and tap <strong>Add to Home Screen</strong></div>' +
                '</div>' +
                '<div class="a2hs-instruction">' +
                    '<div class="a2hs-step-num">3</div>' +
                    '<div class="a2hs-step-text">Tap <strong>Add</strong> in the top-right corner</div>' +
                '</div>';
                if (!isSafari) {
                    html += '<div style="margin-top:12px;padding:10px 12px;background:var(--color-bg-alt);border-radius:var(--radius-md);font-size:0.8125rem;color:var(--color-text-muted)">' +
                        '<i class="fa-solid fa-circle-info" style="margin-right:4px"></i> Open this page in <strong>Safari</strong> to add to homescreen' +
                    '</div>';
                }
            } else if (isAndroid) {
                html += '<div class="a2hs-instruction">' +
                    '<div class="a2hs-step-num">1</div>' +
                    '<div class="a2hs-step-text">Tap the <strong>menu</strong> button <i class="fa-solid fa-ellipsis-vertical" style="color:var(--color-accent)"></i> in Chrome</div>' +
                '</div>' +
                '<div class="a2hs-instruction">' +
                    '<div class="a2hs-step-num">2</div>' +
                    '<div class="a2hs-step-text">Tap <strong>Add to Home screen</strong></div>' +
                '</div>' +
                '<div class="a2hs-instruction">' +
                    '<div class="a2hs-step-num">3</div>' +
                    '<div class="a2hs-step-text">Tap <strong>Add</strong> to confirm</div>' +
                '</div>';
            } else {
                html += '<div class="a2hs-instruction">' +
                    '<div class="a2hs-step-num">1</div>' +
                    '<div class="a2hs-step-text">Click the <strong>install icon</strong> <i class="fa-solid fa-download" style="color:var(--color-accent)"></i> in your browser\'s address bar</div>' +
                '</div>' +
                '<div class="a2hs-instruction">' +
                    '<div class="a2hs-step-num">2</div>' +
                    '<div class="a2hs-step-text">Click <strong>Install</strong> to confirm</div>' +
                '</div>';
            }

            html += '</div>' +
                '<button type="button" class="btn btn-primary btn-block" id="a2hsDoneBtn" style="margin-top:16px">Done, I\'ve added it</button>';

            TinyShop.openModal('Add to Homescreen', html);

            document.getElementById('a2hsDoneBtn').addEventListener('click', function() {
                localStorage.setItem('tinyshop_homescreen_added', '1');
                hsStep.classList.add('completed');
                hsStep.querySelector('.onboarding-step-icon').innerHTML = '<i class="fa-solid fa-check"></i>';
                var newDone = card.querySelectorAll('.onboarding-step.completed').length;
                card.querySelector('#onboardingProgress').textContent = newDone + ' of ' + total + ' complete';
                card.querySelector('#onboardingBar').style.width = (newDone / total * 100) + '%';
                TinyShop.closeModal();
                if (newDone >= total) card.style.display = 'none';
            });
        });
    }
})();
</script>
{/if}

{* Share shop link *}
{if $user.subdomain}
<div class="dash-section">
    <div class="dash-section-header">
        <h2>Your Shop</h2>
        <a href="{$scheme}://{$user.subdomain}.{$base_domain}" target="_blank">Preview</a>
    </div>
    <div class="share-card">
        <div class="share-card-label">Share this link with your customers</div>
        <div class="share-link-row">
            <input type="text" value="" id="shopLink" data-subdomain="{$user.subdomain|escape}" readonly>
            <button type="button" class="btn-copy" id="copyBtn">Copy</button>
        </div>
        <div class="share-hint">Post it on WhatsApp, Instagram, TikTok — anywhere!</div>
    </div>
</div>
{/if}

{* Quick Actions *}
<div class="dash-section">
    <div class="dash-section-header">
        <h2>Quick Actions</h2>
    </div>
    <div class="quick-actions">
        <a href="/dashboard/products" class="action-card">
            <div class="action-icon purple">
                <i class="fa-solid fa-box"></i>
            </div>
            <strong>Products</strong>
        </a>
        <a href="/dashboard/orders" class="action-card">
            <div class="action-icon orange">
                <i class="fa-solid fa-bag-shopping"></i>
            </div>
            <strong>Orders</strong>
        </a>
        <a href="/dashboard/design" class="action-card">
            <div class="action-icon teal">
                <i class="fa-solid fa-palette"></i>
            </div>
            <strong>Design</strong>
        </a>
        <a href="/dashboard/shop" class="action-card">
            <div class="action-icon green">
                <i class="fa-solid fa-gear"></i>
            </div>
            <strong>Settings</strong>
        </a>
        <a href="/dashboard/categories" class="action-card">
            <div class="action-icon blue">
                <i class="fa-solid fa-folder"></i>
            </div>
            <strong>Categories</strong>
        </a>
        <a href="/dashboard/coupons" class="action-card">
            <div class="action-icon pink">
                <i class="fa-solid fa-ticket"></i>
            </div>
            <strong>Coupons</strong>
        </a>
        <a href="/logout" class="action-card">
            <div class="action-icon red">
                <i class="fa-solid fa-right-from-bracket"></i>
            </div>
            <strong>Log Out</strong>
        </a>
    </div>
</div>
{/block}

{block name="extra_scripts"}
<script>
$(function() {
    // Fill share link with full URL
    var $linkInput = $('#shopLink');
    var shopUrl = '';
    if ($linkInput.length) {
        var sub = $linkInput.data('subdomain');
        var port = window.location.port ? ':' + window.location.port : '';
        shopUrl = window.location.protocol + '//' + sub + '.{$base_domain|escape:"javascript"}' + port;
        $linkInput.val(shopUrl);
    }

    $('#copyBtn').on('click', function() {
        var link = $('#shopLink').val();
        var $btn = $(this);
        if (navigator.clipboard) {
            navigator.clipboard.writeText(link).then(function() {
                $btn.text('Copied!');
                TinyShop.toast('Link copied!');
                setTimeout(function() { $btn.text('Copy'); }, 2000);
            });
        } else {
            var ta = document.createElement('textarea');
            ta.value = link;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            $btn.text('Copied!');
            TinyShop.toast('Link copied!');
            setTimeout(function() { $btn.text('Copy'); }, 2000);
        }
    });

    // --- PWA Install Prompt ---
    var _deferredPrompt = null;
    window.addEventListener('beforeinstallprompt', function(e) {
        e.preventDefault();
        _deferredPrompt = e;
        // Show install banner at the bottom of quick actions
        var $banner = $('<div class="dash-section" id="installBanner">' +
            '<div class="share-card" style="display:flex;align-items:center;gap:14px">' +
                '<div style="flex:1">' +
                    '<div style="font-weight:600;font-size:0.9375rem;margin-bottom:2px">Install {$app_name}</div>' +
                    '<div style="font-size:0.75rem;color:var(--color-text-muted)">Add to home screen for quick access</div>' +
                '</div>' +
                '<button type="button" id="installBtn" style="flex-shrink:0;padding:10px 20px;background:var(--color-accent);color:#fff;border:none;border-radius:10px;font-size:0.8125rem;font-weight:600;font-family:inherit;cursor:pointer">Install</button>' +
            '</div>' +
        '</div>');
        $('.dash-content').append($banner);

        $('#installBtn').on('click', function() {
            _deferredPrompt.prompt();
            _deferredPrompt.userChoice.then(function(choice) {
                if (choice.outcome === 'accepted') {
                    TinyShop.toast('{$app_name|escape:"javascript"} installed!');
                }
                _deferredPrompt = null;
                $('#installBanner').slideUp(200);
            });
        });
    });
});
</script>
{/block}
