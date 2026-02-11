{extends file="layouts/dashboard.tpl"}

{block name="content"}
{* Greeting header *}
<div class="dash-greeting">
    <div class="dash-greeting-row">
        <div>
            <small>Welcome back</small>
            <h1>Hi, {$user.name|escape}!</h1>
        </div>
        <a href="/dashboard/shop" class="dash-avatar">{if $user.shop_logo}<img src="{$user.shop_logo|escape}" alt="">{else}{$user.name|escape|substr:0:1|upper}{/if}</a>
    </div>
</div>

{* Stats *}
<div class="dash-stats">
    <div class="stat-card">
        <div class="stat-number">{$view_stats.today|default:0}</div>
        <div class="stat-label">Views Today</div>
    </div>
    <div class="stat-card">
        <div class="stat-number">{$view_stats.week|default:0}</div>
        <div class="stat-label">This Week</div>
    </div>
    <div class="stat-card">
        <div class="stat-number">{$product_count|default:0}</div>
        <div class="stat-label">Products</div>
    </div>
</div>

{* Onboarding checklist (shows until dismissed) *}
<div class="dash-section" id="onboardingSection" style="display:none">
    <div class="onboarding-card">
        <div class="onboarding-title">Get Started</div>
        <div class="onboarding-progress"><div class="onboarding-progress-fill" id="onboardingFill"></div></div>
        <div class="onboarding-steps">
            <a href="/dashboard/shop" class="onboarding-step" id="onb-logo" data-check="shop_logo">
                <span class="onboarding-step-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                </span>
                <span class="onboarding-step-text">Add your shop logo</span>
                <svg class="onboarding-step-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 6 15 12 9 18"/></svg>
            </a>
            <a href="/dashboard/products/add" class="onboarding-step" id="onb-product" data-check="has_products">
                <span class="onboarding-step-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                </span>
                <span class="onboarding-step-text">Add your first product</span>
                <svg class="onboarding-step-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 6 15 12 9 18"/></svg>
            </a>
            <a href="/dashboard/shop" class="onboarding-step" id="onb-whatsapp" data-check="contact_whatsapp">
                <span class="onboarding-step-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 2C6.477 2 2 6.477 2 12c0 1.89.525 3.66 1.438 5.168L2 22l4.832-1.438A9.955 9.955 0 0 0 12 22c5.523 0 10-4.477 10-10S17.523 2 12 2z"/></svg>
                </span>
                <span class="onboarding-step-text">Add your WhatsApp number</span>
                <svg class="onboarding-step-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 6 15 12 9 18"/></svg>
            </a>
            <a href="javascript:void(0)" class="onboarding-step" id="onb-share" data-check="shared">
                <span class="onboarding-step-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                </span>
                <span class="onboarding-step-text">Share your shop link</span>
                <svg class="onboarding-step-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 6 15 12 9 18"/></svg>
            </a>
        </div>
        <button type="button" class="onboarding-dismiss" id="dismissOnboarding">Dismiss checklist</button>
    </div>
</div>

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
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
            </div>
            <strong>Products</strong>
        </a>
        <a href="/dashboard/shop" class="action-card">
            <div class="action-icon green">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            </div>
            <strong>Settings</strong>
        </a>
        <a href="/dashboard/categories" class="action-card">
            <div class="action-icon blue">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
            </div>
            <strong>Categories</strong>
        </a>
        <a href="/logout" class="action-card">
            <div class="action-icon orange">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            </div>
            <strong>Logout</strong>
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
        navigator.clipboard.writeText(link).then(function() {
            $btn.text('Copied!');
            TinyShop.toast('Link copied!');
            setTimeout(function() { $btn.text('Copy'); }, 2000);
        });
    });

    // --- Onboarding Checklist ---
    var ONBOARD_KEY = 'onboarding_dismissed';
    var dismissed = false;
    try { dismissed = localStorage.getItem(ONBOARD_KEY) === '1'; } catch(e) {}

    if (!dismissed) {
        var hasLogo = {if $user.shop_logo}true{else}false{/if};
        var hasProducts = {if $product_count > 0}true{else}false{/if};
        var hasWhatsApp = {if $user.contact_whatsapp}true{else}false{/if};
        var hasShared = false;
        try { hasShared = localStorage.getItem('has_shared') === '1'; } catch(e) {}

        var checks = { shop_logo: hasLogo, has_products: hasProducts, contact_whatsapp: hasWhatsApp, shared: hasShared };
        var totalSteps = 4;
        var completedSteps = 0;

        $('.onboarding-step').each(function() {
            var key = $(this).data('check');
            if (checks[key]) {
                $(this).addClass('completed');
                var iconEl = $(this).find('.onboarding-step-icon');
                iconEl.html('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>');
                $(this).find('.onboarding-step-chevron').hide();
                completedSteps++;
            }
        });

        // Show if not all completed
        if (completedSteps < totalSteps) {
            var pct = Math.round((completedSteps / totalSteps) * 100);
            $('#onboardingFill').css('width', pct + '%');
            $('#onboardingSection').show();
        }

        // Share step
        $('#onb-share').on('click', function() {
            if ($(this).hasClass('completed')) return;
            if (navigator.share && shopUrl) {
                navigator.share({ title: '{$user.store_name|escape:"javascript"}', url: shopUrl }).then(function() {
                    try { localStorage.setItem('has_shared', '1'); } catch(e) {}
                    TinyShop.toast('Thanks for sharing!');
                    $('#onb-share').addClass('completed');
                    $('#onb-share .onboarding-step-icon').html('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>');
                });
            } else if (shopUrl) {
                navigator.clipboard.writeText(shopUrl).then(function() {
                    try { localStorage.setItem('has_shared', '1'); } catch(e) {}
                    TinyShop.toast('Link copied! Share it anywhere');
                    $('#onb-share').addClass('completed');
                });
            }
        });

        // Dismiss
        $('#dismissOnboarding').on('click', function() {
            try { localStorage.setItem(ONBOARD_KEY, '1'); } catch(e) {}
            $('#onboardingSection').slideUp(200);
        });
    }

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
