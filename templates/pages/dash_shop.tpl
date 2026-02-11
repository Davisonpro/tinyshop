{extends file="layouts/dashboard.tpl"}

{block name="content"}
<div class="dash-topbar">
    <span class="dash-topbar-title">Settings</span>
    <a href="/dashboard/shop" class="dash-topbar-avatar">{if $user.shop_logo}<img src="{$user.shop_logo|escape}" alt="">{else}{$user.name|escape|substr:0:1|upper}{/if}</a>
</div>

<form id="shopForm" class="dash-form" autocomplete="off">
    {* --- Your Shop --- *}
    <div class="form-section">
        <div class="form-section-title">Your Shop</div>
        <div class="form-group">
            <label for="storeName">Shop Name</label>
            <input type="text" class="form-control" id="storeName" name="store_name" value="{$user.store_name|escape}" placeholder="e.g. Mary's Kitchen">
        </div>
        <div class="form-group">
            <label for="shopTagline">Short Description</label>
            <textarea class="form-control autosize" id="shopTagline" name="shop_tagline" placeholder="e.g. Homemade cakes & pastries" rows="1">{$user.shop_tagline|escape}</textarea>
            <p class="form-hint">Shown below your shop name</p>
        </div>
        <div class="form-group">
            <label>Shop Logo</label>
            <input type="file" id="logoInput" accept="image/*" style="display:none">
            <div class="logo-upload" id="logoZone">
                <div class="logo-upload-preview" id="logoPreview" {if !$user.shop_logo}style="display:none"{/if}>
                    <img src="{$user.shop_logo|escape}" alt="Logo" id="logoImg">
                    <div class="logo-upload-overlay">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                        <span>Change</span>
                    </div>
                </div>
                <div class="logo-upload-empty" id="logoPlaceholder" {if $user.shop_logo}style="display:none"{/if}>
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                    <span>Tap to add your logo</span>
                </div>
            </div>
            <input type="hidden" id="shopLogo" name="shop_logo" value="{$user.shop_logo|escape}">
        </div>
    </div>

    {* --- Your Shop Link (read-only display, edit via modal) --- *}
    <input type="hidden" id="subdomain" name="subdomain" value="{$user.subdomain|escape}">
    <input type="hidden" id="customDomain" name="custom_domain" value="{$user.custom_domain|escape}">

    {* --- Shop Theme --- *}
    <div class="form-section">
        <div class="form-section-title">Shop Theme</div>
        <p class="form-hint" style="margin-bottom:12px">Choose how your storefront looks to customers</p>
        <div class="theme-picker" id="themePicker">
            {assign var="currentTheme" value=$user.shop_theme|default:'classic'}
            <label class="theme-card{if $currentTheme == 'classic'} active{/if}" data-theme="classic">
                <input type="radio" name="shop_theme" value="classic" {if $currentTheme == 'classic'}checked{/if}>
                <div class="theme-card-preview theme-preview-classic">
                    <div class="tp-header"><div class="tp-logo"></div><div class="tp-lines"><div></div><div></div></div></div>
                    <div class="tp-tabs"><span></span><span class="filled"></span><span></span></div>
                    <div class="tp-grid"><div class="tp-item"><div class="tp-img"></div><div class="tp-bar"></div></div><div class="tp-item"><div class="tp-img"></div><div class="tp-bar"></div></div></div>
                </div>
                <div class="theme-card-name">Classic</div>
                <div class="theme-card-check"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg></div>
            </label>
            <label class="theme-card{if $currentTheme == 'ivory'} active{/if}" data-theme="ivory">
                <input type="radio" name="shop_theme" value="ivory" {if $currentTheme == 'ivory'}checked{/if}>
                <div class="theme-card-preview theme-preview-ivory">
                    <div class="tp-header left"><div class="tp-logo"></div><div class="tp-lines"><div></div><div></div></div></div>
                    <div class="tp-tabs underline"><span></span><span class="filled"></span><span></span></div>
                    <div class="tp-grid"><div class="tp-item flat"><div class="tp-img"></div><div class="tp-bar"></div></div><div class="tp-item flat"><div class="tp-img"></div><div class="tp-bar"></div></div></div>
                </div>
                <div class="theme-card-name">Ivory</div>
                <div class="theme-card-check"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg></div>
            </label>
            <label class="theme-card{if $currentTheme == 'obsidian'} active{/if}" data-theme="obsidian">
                <input type="radio" name="shop_theme" value="obsidian" {if $currentTheme == 'obsidian'}checked{/if}>
                <div class="theme-card-preview theme-preview-obsidian">
                    <div class="tp-header dark"><div class="tp-logo light"></div><div class="tp-lines light"><div></div><div></div></div></div>
                    <div class="tp-tabs sharp"><span></span><span class="filled"></span><span></span></div>
                    <div class="tp-grid"><div class="tp-item sharp"><div class="tp-img"></div><div class="tp-bar"></div></div><div class="tp-item sharp"><div class="tp-img"></div><div class="tp-bar"></div></div></div>
                </div>
                <div class="theme-card-name">Obsidian</div>
                <div class="theme-card-check"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg></div>
            </label>
            <label class="theme-card{if $currentTheme == 'bloom'} active{/if}" data-theme="bloom">
                <input type="radio" name="shop_theme" value="bloom" {if $currentTheme == 'bloom'}checked{/if}>
                <div class="theme-card-preview theme-preview-bloom">
                    <div class="tp-header"><div class="tp-logo accent"></div><div class="tp-lines"><div></div><div></div></div></div>
                    <div class="tp-tabs pill"><span></span><span class="filled"></span><span></span></div>
                    <div class="tp-grid"><div class="tp-item round"><div class="tp-img"></div><div class="tp-bar"></div></div><div class="tp-item round"><div class="tp-img"></div><div class="tp-bar"></div></div></div>
                </div>
                <div class="theme-card-name">Bloom</div>
                <div class="theme-card-check"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg></div>
            </label>
            <label class="theme-card{if $currentTheme == 'ember'} active{/if}" data-theme="ember">
                <input type="radio" name="shop_theme" value="ember" {if $currentTheme == 'ember'}checked{/if}>
                <div class="theme-card-preview theme-preview-ember">
                    <div class="tp-header"><div class="tp-logo ember"></div><div class="tp-lines"><div></div><div></div></div></div>
                    <div class="tp-tabs ember"><span></span><span class="filled"></span><span></span></div>
                    <div class="tp-grid"><div class="tp-item"><div class="tp-img ember"></div><div class="tp-bar"></div></div><div class="tp-item"><div class="tp-img ember"></div><div class="tp-bar"></div></div></div>
                </div>
                <div class="theme-card-name">Ember</div>
                <div class="theme-card-check"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg></div>
            </label>
            <label class="theme-card{if $currentTheme == 'monaco'} active{/if}" data-theme="monaco">
                <input type="radio" name="shop_theme" value="monaco" {if $currentTheme == 'monaco'}checked{/if}>
                <div class="theme-card-preview theme-preview-monaco">
                    <span class="theme-card-badge">Premium</span>
                    <div class="tp-header"><div class="tp-logo monaco"></div><div class="tp-lines"><div></div><div></div></div></div>
                    <div class="tp-tabs monaco-tabs"><span></span><span class="filled"></span><span></span></div>
                    <div class="tp-grid"><div class="tp-item monaco-item"><div class="tp-img"></div><div class="tp-bar"></div></div><div class="tp-item monaco-item"><div class="tp-img"></div><div class="tp-bar"></div></div></div>
                </div>
                <div class="theme-card-name">Monaco</div>
                <div class="theme-card-check"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg></div>
            </label>
            <label class="theme-card{if $currentTheme == 'volt'} active{/if}" data-theme="volt">
                <input type="radio" name="shop_theme" value="volt" {if $currentTheme == 'volt'}checked{/if}>
                <div class="theme-card-preview theme-preview-volt">
                    <span class="theme-card-badge">Premium</span>
                    <div class="tp-header"><div class="tp-logo volt"></div><div class="tp-lines"><div></div><div></div></div></div>
                    <div class="tp-tabs volt-tabs"><span></span><span class="filled"></span><span></span></div>
                    <div class="tp-grid"><div class="tp-item volt-item"><div class="tp-img"></div><div class="tp-bar"></div></div><div class="tp-item volt-item"><div class="tp-img"></div><div class="tp-bar"></div></div></div>
                </div>
                <div class="theme-card-name">Volt</div>
                <div class="theme-card-check"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg></div>
            </label>
        </div>
    </div>

    {* --- Pricing --- *}
    <div class="form-section">
        <div class="form-section-title">Pricing</div>
        <div class="form-group">
            <label for="currency">Currency for your prices</label>
            <select class="form-control" id="currency" name="currency">
                {assign var="cur" value=$user.currency|default:'KES'}
                <option value="KES" {if $cur == 'KES'}selected{/if}>KES — Kenyan Shilling</option>
                <option value="USD" {if $cur == 'USD'}selected{/if}>USD — US Dollar</option>
                <option value="NGN" {if $cur == 'NGN'}selected{/if}>NGN — Nigerian Naira</option>
                <option value="TZS" {if $cur == 'TZS'}selected{/if}>TZS — Tanzanian Shilling</option>
                <option value="UGX" {if $cur == 'UGX'}selected{/if}>UGX — Ugandan Shilling</option>
                <option value="ZAR" {if $cur == 'ZAR'}selected{/if}>ZAR — South African Rand</option>
                <option value="GHS" {if $cur == 'GHS'}selected{/if}>GHS — Ghanaian Cedi</option>
                <option value="RWF" {if $cur == 'RWF'}selected{/if}>RWF — Rwandan Franc</option>
                <option value="ETB" {if $cur == 'ETB'}selected{/if}>ETB — Ethiopian Birr</option>
                <option value="XOF" {if $cur == 'XOF'}selected{/if}>XOF — West African CFA</option>
                <option value="GBP" {if $cur == 'GBP'}selected{/if}>GBP — British Pound</option>
                <option value="EUR" {if $cur == 'EUR'}selected{/if}>EUR — Euro</option>
            </select>
        </div>
    </div>

    {* --- Contact Info --- *}
    <div class="form-section">
        <div class="form-section-title">How Customers Reach You</div>
        <div class="form-group">
            <label for="contactWhatsapp">WhatsApp Number</label>
            <input type="tel" class="form-control" id="contactWhatsapp" name="contact_whatsapp" value="{$user.contact_whatsapp|escape}" placeholder="e.g. 254712345678" inputmode="numeric">
            <p class="form-hint">Start with country code (254 for Kenya)</p>
        </div>
        <div class="form-group">
            <label for="contactPhone">Phone Number</label>
            <input type="tel" class="form-control" id="contactPhone" name="contact_phone" value="{$user.contact_phone|escape}" placeholder="e.g. 0712 345 678" inputmode="numeric">
        </div>
        <div class="form-group">
            <label for="contactEmail">Email</label>
            <input type="email" class="form-control" id="contactEmail" name="contact_email" value="{$user.contact_email|escape}" placeholder="e.g. mary@gmail.com">
        </div>
        <div class="form-group">
            <label for="mapLink">Google Maps Location</label>
            <input type="url" class="form-control" id="mapLink" name="map_link" value="{$user.map_link|escape}" placeholder="Paste your Google Maps link">
            <p class="form-hint">Optional — helps customers find your shop</p>
        </div>
    </div>

    {* --- Social Media --- *}
    <div class="form-section">
        <div class="form-section-title">Social Media</div>
        <div class="form-group">
            <label for="socialInstagram">Instagram</label>
            <input type="text" class="form-control" id="socialInstagram" name="social_instagram" value="{$user.social_instagram|escape}" placeholder="your username (without @)">
        </div>
        <div class="form-group">
            <label for="socialTiktok">TikTok</label>
            <input type="text" class="form-control" id="socialTiktok" name="social_tiktok" value="{$user.social_tiktok|escape}" placeholder="your username (without @)">
        </div>
        <div class="form-group">
            <label for="socialFacebook">Facebook</label>
            <input type="text" class="form-control" id="socialFacebook" name="social_facebook" value="{$user.social_facebook|escape}" placeholder="your page name or username">
            <p class="form-hint">Shown as icons on your shop page</p>
        </div>
    </div>

    <button type="submit" class="btn-primary" id="saveShopBtn">Save Settings</button>
</form>

{* --- Shop Link & Domain (tappable rows → modals) --- *}
<div class="dash-form" style="padding-top:0">
    <div class="form-section">
        <div class="form-section-title">Your Shop Link</div>
        <div class="account-row" id="changeSubdomainBtn">
            <div class="account-row-left">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                <div>
                    <div class="account-row-label">Shop URL</div>
                    <div class="account-row-value" id="subdomainDisplay">{if $user.subdomain}{$user.subdomain|escape}.{$base_domain}{else}Not set{/if}</div>
                </div>
            </div>
            <svg class="account-row-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="9 6 15 12 9 18"/></svg>
        </div>
        <div class="account-row" id="changeDomainBtn">
            <div class="account-row-left">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                <div>
                    <div class="account-row-label">Custom Domain</div>
                    <div class="account-row-value" id="domainDisplay">{if $user.custom_domain}{$user.custom_domain|escape}{else}Not connected{/if}</div>
                </div>
            </div>
            {if $user.custom_domain}
            <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 8px;background:#ECFDF5;color:#059669;border-radius:6px;font-size:0.6875rem;font-weight:600">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                Active
            </span>
            {else}
            <svg class="account-row-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="9 6 15 12 9 18"/></svg>
            {/if}
        </div>
    </div>
</div>

{* --- Account (tappable rows → modals) --- *}
<div class="dash-form" style="padding-top:0">
    <div class="form-section">
        <div class="form-section-title">Account</div>
        <div class="account-row" id="deleteShopBtn">
            <div class="account-row-left">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#FF3B30" stroke-width="2" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                <div>
                    <div class="account-row-label" style="color:#FF3B30">Delete Shop</div>
                    <div class="account-row-value">Permanently delete your account</div>
                </div>
            </div>
            <svg class="account-row-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="9 6 15 12 9 18"/></svg>
        </div>
        <div class="account-row" id="changeEmailBtn">
            <div class="account-row-left">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                <div>
                    <div class="account-row-label">Login Email</div>
                    <div class="account-row-value" id="currentEmailDisplay">{$user.email|escape}</div>
                </div>
            </div>
            <svg class="account-row-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="9 6 15 12 9 18"/></svg>
        </div>
        <div class="account-row" id="changePasswordBtn">
            <div class="account-row-left">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <div>
                    <div class="account-row-label">Password</div>
                    <div class="account-row-value">Change your password</div>
                </div>
            </div>
            <svg class="account-row-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="9 6 15 12 9 18"/></svg>
        </div>
    </div>
</div>
{/block}

{block name="extra_scripts"}
<script>
function togglePw(btn) {
    var input = btn.parentElement.querySelector('input');
    var isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    btn.querySelector('.eye-open').style.display = isPassword ? 'none' : '';
    btn.querySelector('.eye-closed').style.display = isPassword ? '' : 'none';
    btn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
}
var _baseDomain = '{$base_domain|escape:"javascript"}';
function shopUrl(sub) {
    var port = window.location.port ? ':' + window.location.port : '';
    return window.location.protocol + '//' + sub + '.' + _baseDomain + port;
}
$(function() {
    // --- Change Shop URL (modal) ---
    $('#changeSubdomainBtn').on('click', function() {
        var currentVal = $('#subdomain').val();
        var html = '<form id="subdomainChangeForm" autocomplete="off">' +
            '<div class="form-group">' +
                '<label for="newSubdomain">Shop link name</label>' +
                '<input type="text" class="form-control" id="newSubdomain" value="' + escapeHtml(currentVal) + '" placeholder="e.g. marys-kitchen" autocomplete="off" required autofocus>' +
                '<p class="form-hint" style="margin-top:6px">Customers visit: <strong id="subdomainPreviewModal">' + escapeHtml(shopUrl(currentVal || '...')) + '</strong></p>' +
                '<p class="form-hint">Use lowercase letters, numbers, and dashes only</p>' +
            '</div>' +
            '<button type="submit" class="btn btn-primary" id="saveSubdomainBtn" style="width:100%;min-height:52px;font-size:1rem;font-weight:600;border-radius:14px;border:none;cursor:pointer;font-family:inherit;">Save URL</button>' +
        '</form>';
        TinyShop.openModal('Change Shop URL', html);

        $('#newSubdomain').on('input', function() {
            var val = $(this).val().toLowerCase().replace(/[^a-z0-9\-]/g, '');
            $(this).val(val);
            $('#subdomainPreviewModal').text(shopUrl(val || '...'));
        });

        $('#subdomainChangeForm').on('submit', function(e) {
            e.preventDefault();
            var newVal = $('#newSubdomain').val().trim();
            if (!newVal) return;
            if (newVal === currentVal) { TinyShop.closeModal(); return; }

            // Confirmation prompt
            var confirmHtml = '<p style="margin-bottom:16px;color:var(--color-text-muted);font-size:0.9rem;line-height:1.5">Your shop URL will change to:</p>' +
                '<div style="padding:12px 14px;background:#F5F5F7;border-radius:10px;font-size:0.9375rem;font-weight:600;word-break:break-all;margin-bottom:20px">' + escapeHtml(shopUrl(newVal)) + '</div>' +
                '<p style="margin-bottom:20px;color:var(--color-text-muted);font-size:0.8125rem;line-height:1.4">Anyone using your old link won\'t be able to find your shop. Make sure to update your links everywhere.</p>' +
                '<div style="display:flex;gap:10px">' +
                    '<button type="button" id="urlConfirmCancel" style="flex:1;min-height:48px;font-size:1rem;font-weight:600;border-radius:12px;background:var(--color-bg-secondary);color:var(--color-text);border:none;cursor:pointer;font-family:inherit">Cancel</button>' +
                    '<button type="button" id="urlConfirmSave" class="btn btn-primary" style="flex:1;min-height:48px;font-size:1rem;font-weight:600;border-radius:12px;border:none;cursor:pointer;font-family:inherit">Confirm</button>' +
                '</div>';
            TinyShop.openModal('Change Shop URL?', confirmHtml);

            $('#urlConfirmCancel').on('click', function() { TinyShop.closeModal(); });
            $('#urlConfirmSave').on('click', function() {
                var $btn = $(this).prop('disabled', true).text('Saving...');
                TinyShop.api('PUT', '/api/shop', { subdomain: newVal }).done(function() {
                    $('#subdomain').val(newVal);
                    $('#subdomainDisplay').text(newVal + '.{$base_domain|escape:"javascript"}');
                    TinyShop.toast('Shop URL updated!');
                    TinyShop.closeModal();
                }).fail(function(xhr) {
                    var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to update';
                    TinyShop.toast(msg, 'error');
                    $btn.prop('disabled', false).text('Confirm');
                });
            });
        });
    });

    // --- Change Custom Domain (modal) ---
    $('#changeDomainBtn').on('click', function() {
        var currentDomain = $('#customDomain').val();
        var html = '';

        if (currentDomain) {
            // Connected state
            html = '<div class="domain-connected-card" style="margin-bottom:16px">' +
                '<div class="domain-connected-icon">' +
                    '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>' +
                '</div>' +
                '<div class="domain-connected-info">' +
                    '<div class="domain-connected-label">Connected</div>' +
                    '<div class="domain-connected-url">' + escapeHtml(currentDomain) + '</div>' +
                '</div>' +
            '</div>' +
            '<button type="button" class="domain-remove-btn" id="modalRemoveDomain">Remove custom domain</button>';
        } else {
            // Setup state
            html = '<form id="domainSetupForm" autocomplete="off">' +
                '<div class="form-group">' +
                    '<label for="newDomain">Your domain</label>' +
                    '<input type="text" class="form-control" id="newDomain" placeholder="e.g. shop.yourbrand.com" autocomplete="off" required autofocus>' +
                '</div>' +
                '<ol class="domain-setup-steps">' +
                    '<li class="domain-setup-step">' +
                        '<span class="domain-step-number">1</span>' +
                        '<span class="domain-step-text">Go to your domain provider (e.g. Namecheap, GoDaddy)</span>' +
                    '</li>' +
                    '<li class="domain-setup-step">' +
                        '<span class="domain-step-number">2</span>' +
                        '<span class="domain-step-text">Add a <code>CNAME</code> record pointing to <code>' + escapeHtml(window.location.host) + '</code></span>' +
                    '</li>' +
                    '<li class="domain-setup-step">' +
                        '<span class="domain-step-number">3</span>' +
                        '<span class="domain-step-text">Enter your domain above and tap Connect</span>' +
                    '</li>' +
                '</ol>' +
                '<button type="submit" class="btn btn-primary" id="saveDomainBtn" style="width:100%;min-height:52px;font-size:1rem;font-weight:600;border-radius:14px;border:none;cursor:pointer;font-family:inherit;margin-top:8px">Connect Domain</button>' +
            '</form>';
        }

        TinyShop.openModal('Custom Domain', html);

        // Connect domain
        $('#domainSetupForm').on('submit', function(e) {
            e.preventDefault();
            var domain = $('#newDomain').val().trim().toLowerCase();
            if (!domain) return;

            // Confirmation prompt
            var confirmHtml = '<p style="margin-bottom:16px;color:var(--color-text-muted);font-size:0.9rem;line-height:1.5">You are connecting this domain to your shop:</p>' +
                '<div style="padding:12px 14px;background:#EEF2FF;border-radius:10px;font-size:0.9375rem;font-weight:600;word-break:break-all;margin-bottom:20px;color:var(--color-accent)">' + escapeHtml(domain) + '</div>' +
                '<p style="margin-bottom:20px;color:var(--color-text-muted);font-size:0.8125rem;line-height:1.4">Make sure you\'ve added the CNAME record at your domain provider before confirming.</p>' +
                '<div style="display:flex;gap:10px">' +
                    '<button type="button" id="domainConfirmCancel" style="flex:1;min-height:48px;font-size:1rem;font-weight:600;border-radius:12px;background:var(--color-bg-secondary);color:var(--color-text);border:none;cursor:pointer;font-family:inherit">Cancel</button>' +
                    '<button type="button" id="domainConfirmSave" class="btn btn-primary" style="flex:1;min-height:48px;font-size:1rem;font-weight:600;border-radius:12px;border:none;cursor:pointer;font-family:inherit">Connect</button>' +
                '</div>';
            TinyShop.openModal('Connect Domain?', confirmHtml);

            $('#domainConfirmCancel').on('click', function() { TinyShop.closeModal(); });
            $('#domainConfirmSave').on('click', function() {
                var $btn = $(this).prop('disabled', true).text('Connecting...');
                TinyShop.api('PUT', '/api/shop', { custom_domain: domain }).done(function() {
                    $('#customDomain').val(domain);
                    $('#domainDisplay').text(domain);
                    TinyShop.toast('Domain connected!');
                    TinyShop.closeModal();
                    setTimeout(function() { location.reload(); }, 600);
                }).fail(function(xhr) {
                    var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to connect domain';
                    TinyShop.toast(msg, 'error');
                    $btn.prop('disabled', false).text('Connect');
                });
            });
        });

        // Remove domain — with confirmation
        $('#modalRemoveDomain').on('click', function() {
            var confirmHtml = '<p style="margin-bottom:20px;color:var(--color-text-muted);font-size:0.9rem;line-height:1.5">Remove <strong>' + escapeHtml(currentDomain) + '</strong> from your shop? Visitors using this domain won\'t reach your shop anymore.</p>' +
                '<div style="display:flex;gap:10px">' +
                    '<button type="button" id="domainRemoveCancel" style="flex:1;min-height:48px;font-size:1rem;font-weight:600;border-radius:12px;background:var(--color-bg-secondary);color:var(--color-text);border:none;cursor:pointer;font-family:inherit">Cancel</button>' +
                    '<button type="button" id="domainRemoveConfirm" style="flex:1;min-height:48px;font-size:1rem;font-weight:600;border-radius:12px;background:#FF3B30;color:#fff;border:none;cursor:pointer;font-family:inherit">Remove</button>' +
                '</div>';
            TinyShop.openModal('Remove Domain?', confirmHtml);

            $('#domainRemoveCancel').on('click', function() { TinyShop.closeModal(); });
            $('#domainRemoveConfirm').on('click', function() {
                var $btn = $(this).prop('disabled', true).text('Removing...');
                TinyShop.api('PUT', '/api/shop', { custom_domain: '' }).done(function() {
                    $('#customDomain').val('');
                    $('#domainDisplay').text('Not connected');
                    TinyShop.toast('Domain removed');
                    TinyShop.closeModal();
                    setTimeout(function() { location.reload(); }, 600);
                }).fail(function(xhr) {
                    var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to remove';
                    TinyShop.toast(msg, 'error');
                    $btn.prop('disabled', false).text('Remove');
                });
            });
        });
    });

    // --- Theme Picker ---
    $('#themePicker').on('change', 'input[name="shop_theme"]', function() {
        var theme = $(this).val();
        var $cards = $('#themePicker .theme-card');
        $cards.removeClass('active');
        $(this).closest('.theme-card').addClass('active');
        TinyShop.api('PUT', '/api/shop', { shop_theme: theme }).done(function() {
            TinyShop.toast('Theme updated!');
        }).fail(function(xhr) {
            var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to update theme';
            TinyShop.toast(msg, 'error');
        });
    });

    // Phone fields: strip non-numeric chars on input (allow + at start)
    $('#contactWhatsapp, #contactPhone').on('input', function() {
        var v = $(this).val().replace(/[^0-9+\s\-]/g, '');
        // Only allow + at the very start
        if (v.indexOf('+') > 0) v = v.charAt(0) + v.slice(1).replace(/\+/g, '');
        $(this).val(v);
    });

    // Social media: strip @ from start
    $('#socialInstagram, #socialTiktok').on('input', function() {
        var v = $(this).val().replace(/^@/, '');
        $(this).val(v);
    });

    // Logo upload
    $('#logoZone').on('click', function() { $('#logoInput').click(); });
    $('#logoInput').on('change', function() {
        var file = this.files[0];
        if (!file) return;
        TinyShop.uploadFile(file, function(url) {
            $('#shopLogo').val(url);
            $('#logoPreview img').attr('src', url);
            $('#logoPreview').show();
            $('#logoPlaceholder').hide();
            TinyShop.toast('Logo uploaded!');
        });
    });

    // Save form
    $('#shopForm').on('submit', function(e) {
        e.preventDefault();
        var $btn = $('#saveShopBtn').prop('disabled', true).text('Saving...');
        var data = {};
        $(this).serializeArray().forEach(function(item) {
            data[item.name] = item.value;
        });
        $.ajax({
            url: '/api/shop',
            method: 'PUT',
            contentType: 'application/json',
            data: JSON.stringify(data),
            success: function() {
                TinyShop.toast('Settings saved!');
                $btn.prop('disabled', false).text('Save Settings');
            },
            error: function(xhr) {
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to save';
                TinyShop.toast(msg, 'error');
                $btn.prop('disabled', false).text('Save Settings');
            }
        });
    });

    // --- Delete Shop (modal) ---
    $('#deleteShopBtn').on('click', function() {
        var shopName = $('#storeName').val() || '';
        var html = '<form id="deleteShopForm" autocomplete="off">' +
            '<p style="margin-bottom:16px;color:var(--color-text-muted);font-size:0.9rem;line-height:1.5">This will <strong style="color:#FF3B30">permanently delete</strong> your shop, all products, categories, orders, and analytics. This action cannot be undone.</p>' +
            '<div class="form-group">' +
                '<label for="deleteConfirmName">Type your shop name to confirm</label>' +
                '<input type="text" class="form-control" id="deleteConfirmName" placeholder="' + escapeHtml(shopName) + '" autocomplete="one-time-code" required autofocus>' +
                '<p class="form-hint">Enter <strong>' + escapeHtml(shopName) + '</strong> exactly</p>' +
            '</div>' +
            '<div class="form-group">' +
                '<label for="deleteConfirmPassword">Your password</label>' +
                '<div class="password-field"><input type="password" class="form-control" id="deleteConfirmPassword" placeholder="Enter your password" autocomplete="new-password" required>' +
                '<button type="button" class="password-toggle" onclick="togglePw(this)" aria-label="Show password"><svg class="eye-open" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg><svg class="eye-closed" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg></button></div>' +
            '</div>' +
            '<button type="submit" class="btn" id="deleteShopConfirmBtn" style="width:100%;min-height:52px;font-size:1rem;font-weight:600;border-radius:14px;background:#FF3B30;color:#fff;border:none;cursor:pointer;font-family:inherit;opacity:0.4;pointer-events:none">Delete My Shop Forever</button>' +
        '</form>';
        TinyShop.openModal('Delete Shop', html);

        // Only enable button when shop name matches exactly
        $('#deleteConfirmName').on('input', function() {
            var matches = $(this).val().trim().toLowerCase() === shopName.trim().toLowerCase();
            var hasPassword = $('#deleteConfirmPassword').val().length > 0;
            var $btn = $('#deleteShopConfirmBtn');
            if (matches && hasPassword) {
                $btn.css({ opacity: 1, 'pointer-events': 'auto' });
            } else {
                $btn.css({ opacity: 0.4, 'pointer-events': 'none' });
            }
        });
        $('#deleteConfirmPassword').on('input', function() {
            $('#deleteConfirmName').trigger('input');
        });

        $('#deleteShopForm').on('submit', function(e) {
            e.preventDefault();
            var confirmation = $('#deleteConfirmName').val().trim();
            var password = $('#deleteConfirmPassword').val();
            if (!confirmation || !password) return;
            var $btn = $('#deleteShopConfirmBtn').prop('disabled', true).text('Deleting...');
            TinyShop.api('DELETE', '/api/shop', { confirmation: confirmation, password: password }).done(function() {
                TinyShop.toast('Account deleted');
                setTimeout(function() { window.location.href = '/'; }, 800);
            }).fail(function(xhr) {
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to delete';
                TinyShop.toast(msg, 'error');
                $btn.prop('disabled', false).text('Delete My Shop Forever').css({ opacity: 1, 'pointer-events': 'auto' });
            });
        });
    });

    // --- Change Email (modal) ---
    $('#changeEmailBtn').on('click', function() {
        var html = '<form id="emailChangeForm" autocomplete="off">' +
            '<div class="form-group">' +
                '<label for="newEmail">New Email</label>' +
                '<input type="email" class="form-control" id="newEmail" placeholder="Enter new email address" autocomplete="off" required autofocus>' +
            '</div>' +
            '<div class="form-group">' +
                '<label for="emailPassword">Current Password</label>' +
                '<div class="password-field"><input type="password" class="form-control" id="emailPassword" placeholder="Confirm your password" autocomplete="off" required>' +
                '<button type="button" class="password-toggle" onclick="togglePw(this)" aria-label="Show password"><svg class="eye-open" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg><svg class="eye-closed" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg></button></div>' +
            '</div>' +
            '<button type="submit" class="btn btn-primary" id="saveEmailBtn" style="width:100%;min-height:52px;font-size:1rem;font-weight:600;border-radius:14px;border:none;cursor:pointer;font-family:inherit;">Update Email</button>' +
        '</form>';
        TinyShop.openModal('Change Login Email', html);

        $('#emailChangeForm').on('submit', function(e) {
            e.preventDefault();
            var newEmail = $('#newEmail').val().trim();
            var password = $('#emailPassword').val();
            if (!newEmail || !password) return;
            var $btn = $('#saveEmailBtn').prop('disabled', true).text('Updating...');
            TinyShop.api('PUT', '/api/shop/email', { new_email: newEmail, current_password: password }).done(function() {
                TinyShop.toast('Email updated!');
                $('#currentEmailDisplay').text(newEmail);
                TinyShop.closeModal();
            }).fail(function(xhr) {
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to update';
                TinyShop.toast(msg, 'error');
                $btn.prop('disabled', false).text('Update Email');
            });
        });
    });

    // --- Change Password (modal) ---
    $('#changePasswordBtn').on('click', function() {
        var html = '<form id="passwordChangeForm" autocomplete="off">' +
            '<div class="form-group">' +
                '<label for="currentPassword">Current Password</label>' +
                '<div class="password-field"><input type="password" class="form-control" id="currentPassword" placeholder="Enter current password" autocomplete="off" required autofocus>' +
                '<button type="button" class="password-toggle" onclick="togglePw(this)" aria-label="Show password"><svg class="eye-open" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg><svg class="eye-closed" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg></button></div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label for="newPassword">New Password</label>' +
                '<div class="password-field"><input type="password" class="form-control" id="newPassword" placeholder="At least 6 characters" autocomplete="off" required>' +
                '<button type="button" class="password-toggle" onclick="togglePw(this)" aria-label="Show password"><svg class="eye-open" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg><svg class="eye-closed" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg></button></div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label for="confirmPassword">Confirm New Password</label>' +
                '<div class="password-field"><input type="password" class="form-control" id="confirmPassword" placeholder="Re-enter new password" autocomplete="off" required>' +
                '<button type="button" class="password-toggle" onclick="togglePw(this)" aria-label="Show password"><svg class="eye-open" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg><svg class="eye-closed" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg></button></div>' +
            '</div>' +
            '<button type="submit" class="btn btn-primary" id="savePassBtn" style="width:100%;min-height:52px;font-size:1rem;font-weight:600;border-radius:14px;border:none;cursor:pointer;font-family:inherit;">Update Password</button>' +
        '</form>';
        TinyShop.openModal('Change Password', html);

        $('#passwordChangeForm').on('submit', function(e) {
            e.preventDefault();
            var current = $('#currentPassword').val();
            var newPass = $('#newPassword').val();
            var confirm = $('#confirmPassword').val();
            if (!current || !newPass || !confirm) return;
            if (newPass !== confirm) { TinyShop.toast('Passwords don\'t match', 'error'); return; }
            if (newPass.length < 6) { TinyShop.toast('Must be at least 6 characters', 'error'); return; }
            var $btn = $('#savePassBtn').prop('disabled', true).text('Updating...');
            TinyShop.api('PUT', '/api/shop/password', { current_password: current, new_password: newPass }).done(function() {
                TinyShop.toast('Password updated!');
                TinyShop.closeModal();
            }).fail(function(xhr) {
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to update';
                TinyShop.toast(msg, 'error');
                $btn.prop('disabled', false).text('Update Password');
            });
        });
    });
});
</script>
{/block}
