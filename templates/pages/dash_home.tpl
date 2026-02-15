{extends file="layouts/dashboard.tpl"}

{block name="content"}
{* Greeting header *}
<div class="dash-greeting">
    <div class="dash-greeting-row">
        <div>
            <small>Welcome back</small>
            <h1>Hi, {$user.store_name|default:$user.name|escape}!</h1>
        </div>
        <a href="/dashboard/shop" class="dash-avatar">{$user.store_name|default:$user.name|escape|substr:0:1|upper}</a>
    </div>
</div>

{* Upgrade banner for free plan *}
{if !empty($usage) && $usage.is_free}
<a href="/dashboard/billing" class="upgrade-banner">
    <div class="upgrade-banner-content">
        <i class="fa-solid fa-crown icon-md" style="color:#F5A623"></i>
        <div>
            <strong>You're on the Free plan</strong>
            <span>{$usage.product_count} of {$usage.max_products} products used</span>
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
    <div class="low-stock-header">
        <div class="low-stock-icon">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <div>
            <div class="low-stock-title">Low Stock</div>
            <div class="low-stock-subtitle">{$low_stock_products|@count} {if $low_stock_products|@count == 1}product needs{else}products need{/if} restocking</div>
        </div>
    </div>
    <div class="low-stock-items">
        {foreach $low_stock_products as $p}
        <a href="/dashboard/products/{$p.id}/edit" class="low-stock-item">
            <span class="low-stock-item-name">{$p.name|escape}</span>
            <span class="low-stock-badge">{$p.stock_quantity} left</span>
        </a>
        {/foreach}
    </div>
</div>
{/if}

{* Onboarding checklist (shows until dismissed) *}
{if !empty($onboarding_steps)}
{assign var="completed_count" value=0}
{foreach $onboarding_steps as $step}
    {if $step.done}{assign var="completed_count" value=$completed_count+1}{/if}
{/foreach}
{if $completed_count < $onboarding_steps|@count}
<div class="onboarding-card" id="onboardingCard">
    <div class="onboarding-title">Getting Started</div>
    <div style="font-size:0.8125rem;color:var(--color-text-muted);margin-bottom:4px">{$completed_count} of {$onboarding_steps|@count} complete</div>
    <div class="onboarding-progress">
        <div class="onboarding-progress-fill" style="width:{$completed_count / $onboarding_steps|@count * 100}%"></div>
    </div>
    <div class="onboarding-steps">
        {foreach $onboarding_steps as $step}
        <a href="{$step.link}" class="onboarding-step{if $step.done} completed{/if}">
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
if (localStorage.getItem('tinyshop_onboard_dismissed') === '1') {ldelim}
    var card = document.getElementById('onboardingCard');
    if (card) card.style.display = 'none';
{rdelim}
</script>
{/if}
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
            <div class="action-icon" style="background:var(--color-text-muted)">
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
                    '<div style="font-weight:600;font-size:0.9375rem;margin-bottom:2px">Install TinyShop</div>' +
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
                    TinyShop.toast('TinyShop installed!');
                }
                _deferredPrompt = null;
                $('#installBanner').slideUp(200);
            });
        });
    });
});
</script>
{/block}
