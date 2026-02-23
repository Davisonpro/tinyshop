{extends file="layouts/dashboard.tpl"}

{block name="content"}
<div class="dash-topbar">
    <span class="dash-topbar-title">Settings</span>
    <a href="/dashboard/shop" class="dash-topbar-avatar">{$user.store_name|escape|substr:0:1|upper}</a>
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
    </div>

    {* --- Design page link --- *}
    <a href="/dashboard/design" class="design-link-card">
        <i class="fa-solid fa-palette design-link-icon"></i>
        <div>
            <div class="design-link-title">Logo, colors & layout</div>
            <div class="design-link-desc">Customize your logo, colors, banners, and more</div>
        </div>
        <i class="fa-solid fa-chevron-right account-row-chevron"></i>
    </a>

    {* --- Your Shop Link (read-only display, edit via modal) --- *}
    <input type="hidden" id="subdomain" name="subdomain" value="{$user.subdomain|escape}">
    <input type="hidden" id="customDomain" name="custom_domain" value="{$user.custom_domain|escape}">

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

    {* --- Accept Payments (hidden fields synced from modals) --- *}
    <input type="hidden" id="stripePublicKey" name="stripe_public_key" value="{$user.stripe_public_key|escape}">
    <input type="hidden" id="stripeSecretKey" name="stripe_secret_key" value="{$user.stripe_secret_key|escape}">
    <input type="hidden" id="stripeMode" name="stripe_mode" value="{$user.stripe_mode|default:'test'}">
    <input type="hidden" id="stripeEnabled" name="stripe_enabled" value="{$user.stripe_enabled|default:1}">
    <input type="hidden" id="paypalClientId" name="paypal_client_id" value="{$user.paypal_client_id|escape}">
    <input type="hidden" id="paypalSecret" name="paypal_secret" value="{$user.paypal_secret|escape}">
    <input type="hidden" id="paypalMode" name="paypal_mode" value="{$user.paypal_mode|default:'test'}">
    <input type="hidden" id="paypalEnabled" name="paypal_enabled" value="{$user.paypal_enabled|default:1}">
    <input type="hidden" id="mpesaShortcode" name="mpesa_shortcode" value="{$user.mpesa_shortcode|escape}">
    <input type="hidden" id="mpesaConsumerKey" name="mpesa_consumer_key" value="{$user.mpesa_consumer_key|escape}">
    <input type="hidden" id="mpesaConsumerSecret" name="mpesa_consumer_secret" value="{$user.mpesa_consumer_secret|escape}">
    <input type="hidden" id="mpesaPasskey" name="mpesa_passkey" value="{$user.mpesa_passkey|escape}">
    <input type="hidden" id="mpesaMode" name="mpesa_mode" value="{$user.mpesa_mode|default:'test'}">
    <input type="hidden" id="mpesaEnabled" name="mpesa_enabled" value="{$user.mpesa_enabled|default:0}">
    <input type="hidden" id="pesapalConsumerKey" name="pesapal_consumer_key" value="{$user.pesapal_consumer_key|escape}">
    <input type="hidden" id="pesapalConsumerSecret" name="pesapal_consumer_secret" value="{$user.pesapal_consumer_secret|escape}">
    <input type="hidden" id="pesapalMode" name="pesapal_mode" value="{$user.pesapal_mode|default:'test'}">
    <input type="hidden" id="pesapalEnabled" name="pesapal_enabled" value="{$user.pesapal_enabled|default:0}">

    {* --- Contact Info --- *}
    <div class="form-section">
        <div class="form-section-title">How Customers Reach You</div>
        <div class="form-group">
            <label for="contactWhatsapp"><i class="fa-brands fa-whatsapp label-icon icon-whatsapp"></i>WhatsApp</label>
            <input type="tel" class="form-control" id="contactWhatsapp" name="contact_whatsapp" value="{$user.contact_whatsapp|escape}" placeholder="e.g. 254712345678" inputmode="numeric">
            <p class="form-hint">Start with country code (254 for Kenya)</p>
        </div>
        <div class="form-group">
            <label for="contactPhone"><i class="fa-solid fa-phone label-icon-sm icon-muted"></i>Phone</label>
            <input type="tel" class="form-control" id="contactPhone" name="contact_phone" value="{$user.contact_phone|escape}" placeholder="e.g. 0712 345 678" inputmode="numeric">
        </div>
        <div class="form-group">
            <label for="contactEmail"><i class="fa-solid fa-envelope label-icon-sm icon-muted"></i>Email</label>
            <input type="email" class="form-control" id="contactEmail" name="contact_email" value="{$user.contact_email|escape}" placeholder="e.g. mary@gmail.com">
        </div>
        <div class="form-group">
            <label for="mapLink"><i class="fa-solid fa-location-dot label-icon-sm icon-maps"></i>Google Maps</label>
            <input type="url" class="form-control" id="mapLink" name="map_link" value="{$user.map_link|escape}" placeholder="Paste your Google Maps link">
            <p class="form-hint">Optional — helps customers find your shop</p>
        </div>
    </div>

    {* --- Social Media --- *}
    <div class="form-section">
        <div class="form-section-title">Social Media</div>
        <p class="form-section-hint">Shown as icons on your shop page</p>
        <div class="form-group">
            <label for="socialInstagram"><i class="fa-brands fa-instagram label-icon icon-instagram"></i>Instagram</label>
            <input type="text" class="form-control" id="socialInstagram" name="social_instagram" value="{$user.social_instagram|escape}" placeholder="your username (without @)">
        </div>
        <div class="form-group">
            <label for="socialTiktok"><i class="fa-brands fa-tiktok label-icon icon-tiktok"></i>TikTok</label>
            <input type="text" class="form-control" id="socialTiktok" name="social_tiktok" value="{$user.social_tiktok|escape}" placeholder="your username (without @)">
        </div>
        <div class="form-group">
            <label for="socialFacebook"><i class="fa-brands fa-facebook label-icon icon-facebook"></i>Facebook</label>
            <input type="text" class="form-control" id="socialFacebook" name="social_facebook" value="{$user.social_facebook|escape}" placeholder="your page name or username">
        </div>
    </div>

    <button type="submit" class="btn-primary" id="saveShopBtn">Save Settings</button>
</form>


{* --- Shop Link & Domain (tappable rows → modals) --- *}
<div class="dash-form dash-form-flush">
    <div class="form-section">
        <div class="form-section-title">Your Shop Link</div>
        <div class="account-row" id="changeSubdomainBtn">
            <div class="account-row-left">
                <i class="fa-solid fa-link icon-lg"></i>
                <div>
                    <div class="account-row-label">Shop URL</div>
                    <div class="account-row-value" id="subdomainDisplay">{if $user.subdomain}{$user.subdomain|escape}.{$base_domain}{else}Not set{/if}</div>
                </div>
            </div>
            <i class="fa-solid fa-chevron-right account-row-chevron"></i>
        </div>
        <div class="account-row" id="changeDomainBtn"{if !$usage.custom_domain && !$user.custom_domain} data-locked="1"{/if}>
            <div class="account-row-left">
                <i class="fa-solid fa-globe icon-lg"></i>
                <div>
                    <div class="account-row-label">Custom Domain</div>
                    <div class="account-row-value" id="domainDisplay">{if $user.custom_domain}{$user.custom_domain|escape}{elseif !$usage.custom_domain}Upgrade to unlock{else}Not connected{/if}</div>
                </div>
            </div>
            {if $user.custom_domain}
            <span class="connected-badge">
                <i class="fa-solid fa-check icon-xs"></i>
                Active
            </span>
            {elseif !$usage.custom_domain}
            <span class="plan-locked-badge"><i class="fa-solid fa-lock icon-xs"></i> Pro</span>
            {else}
            <i class="fa-solid fa-chevron-right account-row-chevron"></i>
            {/if}
        </div>
    </div>
</div>

{* --- Search & Sitemap (shown only for custom domain shops) --- *}
{if !empty($user.custom_domain)}
<div class="dash-form dash-form-flush">
    <div class="form-section">
        <div class="form-section-title">Search Engines</div>
        <div class="form-group">
            <div class="settings-copy-row">
                <input type="text" class="form-control" id="shopSitemapUrl" value="https://{$user.custom_domain|escape}/sitemap.xml" readonly>
                <button type="button" class="btn-copy" id="copyShopSitemapBtn" title="Copy URL">
                    <i class="fa-solid fa-copy"></i>
                </button>
            </div>
            <p class="form-hint">Submit this URL to <a href="https://search.google.com/search-console" target="_blank" rel="noopener">Google Search Console</a> or <a href="https://www.bing.com/webmasters" target="_blank" rel="noopener">Bing Webmaster Tools</a></p>
        </div>
        <div class="account-row" id="verificationCodesBtn">
            <div class="account-row-left">
                <i class="fa-solid fa-shield-halved icon-lg"></i>
                <div>
                    <div class="account-row-label">Verification Codes</div>
                    <div class="account-row-value">{if $user.google_verification || $user.bing_verification}Connected{else}Verify your domain{/if}</div>
                </div>
            </div>
            <i class="fa-solid fa-chevron-right account-row-chevron"></i>
        </div>
    </div>
</div>
{/if}

{* --- Accept Payments (tappable rows → modals) --- *}
<div class="dash-form dash-form-flush">
    <div class="form-section">
        <div class="form-section-title">Accept Payments</div>
        <div class="account-row" id="setupStripeBtn">
            <div class="account-row-left">
                <i class="fa-brands fa-stripe icon-lg icon-stripe"></i>
                <div>
                    <div class="account-row-label">Stripe</div>
                    <div class="account-row-value" id="stripeStatusDisplay">{if $user.stripe_secret_key}Connected{else}Not connected{/if}</div>
                </div>
            </div>
            {if $user.stripe_secret_key}
            <label class="toggle-switch" onclick="event.stopPropagation()">
                <input type="checkbox" id="stripeEnabledToggle" {if $user.stripe_enabled|default:1}checked{/if}>
                <span class="toggle-slider"></span>
            </label>
            {else}
            <i class="fa-solid fa-chevron-right account-row-chevron"></i>
            {/if}
        </div>
        <div class="account-row" id="setupPaypalBtn">
            <div class="account-row-left">
                <i class="fa-brands fa-paypal icon-lg icon-paypal"></i>
                <div>
                    <div class="account-row-label">PayPal</div>
                    <div class="account-row-value" id="paypalStatusDisplay">{if $user.paypal_client_id}Connected{else}Not connected{/if}</div>
                </div>
            </div>
            {if $user.paypal_client_id}
            <label class="toggle-switch" onclick="event.stopPropagation()">
                <input type="checkbox" id="paypalEnabledToggle" {if $user.paypal_enabled|default:1}checked{/if}>
                <span class="toggle-slider"></span>
            </label>
            {else}
            <i class="fa-solid fa-chevron-right account-row-chevron"></i>
            {/if}
        </div>
        <div class="account-row account-row-static">
            <div class="account-row-left">
                <i class="fa-solid fa-hand-holding-dollar icon-lg icon-cod"></i>
                <div>
                    <div class="account-row-label">Pay on Delivery</div>
                    <div class="account-row-value">Collect payment when order is delivered</div>
                </div>
            </div>
            <label class="toggle-switch" onclick="event.stopPropagation()">
                <input type="checkbox" id="codEnabledToggle" {if $user.cod_enabled}checked{/if}>
                <span class="toggle-slider"></span>
            </label>
        </div>
        <div class="account-row" id="setupMpesaBtn">
            <div class="account-row-left">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" style="flex-shrink:0"><circle cx="12" cy="12" r="12" fill="#4CAF50"/><text x="12" y="16" text-anchor="middle" font-size="10" font-weight="bold" fill="white">M</text></svg>
                <div>
                    <div class="account-row-label">M-Pesa</div>
                    <div class="account-row-value" id="mpesaStatusDisplay">{if $user.mpesa_shortcode}Connected{else}Not connected{/if}</div>
                </div>
            </div>
            {if $user.mpesa_shortcode}
            <label class="toggle-switch" onclick="event.stopPropagation()">
                <input type="checkbox" id="mpesaEnabledToggle" {if $user.mpesa_enabled}checked{/if}>
                <span class="toggle-slider"></span>
            </label>
            {else}
            <i class="fa-solid fa-chevron-right account-row-chevron"></i>
            {/if}
        </div>
        <div class="account-row" id="setupPesapalBtn">
            <div class="account-row-left">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" style="flex-shrink:0"><circle cx="12" cy="12" r="12" fill="#0A7BC4"/><text x="12" y="16" text-anchor="middle" font-size="9" font-weight="bold" fill="white">PP</text></svg>
                <div>
                    <div class="account-row-label">Pesapal</div>
                    <div class="account-row-value" id="pesapalStatusDisplay">{if $user.pesapal_consumer_key}Connected{else}Not connected{/if}</div>
                </div>
            </div>
            {if $user.pesapal_consumer_key}
            <label class="toggle-switch" onclick="event.stopPropagation()">
                <input type="checkbox" id="pesapalEnabledToggle" {if $user.pesapal_enabled}checked{/if}>
                <span class="toggle-slider"></span>
            </label>
            {else}
            <i class="fa-solid fa-chevron-right account-row-chevron"></i>
            {/if}
        </div>
    </div>
</div>

{* --- Preferences --- *}
<div class="dash-form dash-form-flush">
    <div class="form-section">
        <div class="form-section-title">Preferences</div>
        <div class="form-toggle-row">
            <div>
                <div class="form-toggle-label">Dark mode</div>
                <p class="form-hint">Use a darker look for your dashboard</p>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" id="darkModeToggle">
                <span class="toggle-slider"></span>
            </label>
        </div>
        <div class="account-row" id="addToHomescreenBtn">
            <div class="account-row-left">
                <i class="fa-solid fa-mobile-screen icon-lg icon-accent"></i>
                <div>
                    <div class="account-row-label">Add to Homescreen</div>
                    <div class="account-row-value">Use your dashboard like an app</div>
                </div>
            </div>
            <i class="fa-solid fa-chevron-right account-row-chevron"></i>
        </div>
    </div>
</div>

{* --- Account (tappable rows → modals) --- *}
<div class="dash-form dash-form-flush">
    <div class="form-section">
        <div class="form-section-title">Account</div>
        <div class="account-row" id="changeEmailBtn">
            <div class="account-row-left">
                <i class="fa-solid fa-envelope icon-lg"></i>
                <div>
                    <div class="account-row-label">Login Email</div>
                    <div class="account-row-value" id="currentEmailDisplay">{$user.email|escape}</div>
                </div>
            </div>
            <i class="fa-solid fa-chevron-right account-row-chevron"></i>
        </div>
        <div class="account-row" id="changePasswordBtn">
            <div class="account-row-left">
                <i class="fa-solid fa-lock icon-lg"></i>
                <div>
                    <div class="account-row-label">Password</div>
                    <div class="account-row-value">Change your password</div>
                </div>
            </div>
            <i class="fa-solid fa-chevron-right account-row-chevron"></i>
        </div>
        <a href="/logout" class="account-row account-row-link">
            <div class="account-row-left">
                <i class="fa-solid fa-right-from-bracket icon-lg"></i>
                <div>
                    <div class="account-row-label">Log Out</div>
                    <div class="account-row-value">Sign out of your account</div>
                </div>
            </div>
            <i class="fa-solid fa-chevron-right account-row-chevron"></i>
        </a>
    </div>
</div>

{* --- Danger Zone --- *}
<div class="dash-form dash-form-flush">
    <div class="form-section">
        <div class="form-section-title account-row-danger">Danger Zone</div>
        <div class="account-row" id="deleteShopBtn">
            <div class="account-row-left">
                <i class="fa-solid fa-trash icon-lg icon-danger"></i>
                <div>
                    <div class="account-row-label account-row-danger">Delete Shop</div>
                    <div class="account-row-value">Permanently remove your shop and all data</div>
                </div>
            </div>
            <i class="fa-solid fa-chevron-right account-row-chevron"></i>
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
    // --- Dark mode toggle ---
    var $darkToggle = $('#darkModeToggle');
    $darkToggle.prop('checked', document.documentElement.getAttribute('data-theme') === 'dark');
    $darkToggle.on('change', function() {ldelim}
        var theme = this.checked ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
    {rdelim});

    // --- Copy shop sitemap URL ---
    $('#copyShopSitemapBtn').on('click', function() {ldelim}
        var url = $('#shopSitemapUrl').val();
        if (navigator.clipboard) {ldelim}
            navigator.clipboard.writeText(url).then(function() {ldelim}
                TinyShop.toast('Sitemap URL copied');
            {rdelim});
        {rdelim} else {ldelim}
            $('#shopSitemapUrl').select();
            document.execCommand('copy');
            TinyShop.toast('Sitemap URL copied');
        {rdelim}
    {rdelim});

    // --- Verification codes modal ---
    $('#verificationCodesBtn').on('click', function() {ldelim}
        var html = '<form id="verificationForm" autocomplete="off">' +
            '<div class="form-group">' +
                '<label for="googleVerCode">Google</label>' +
                '<input type="text" class="form-control" id="googleVerCode" placeholder="Paste verification code" value="{$user.google_verification|escape:"javascript"}" autocomplete="off">' +
                '<p class="form-hint">From <a href="https://search.google.com/search-console" target="_blank" rel="noopener">Google Search Console</a> &rarr; Settings &rarr; Ownership verification &rarr; HTML tag</p>' +
            '</div>' +
            '<div class="form-group">' +
                '<label for="bingVerCode">Bing</label>' +
                '<input type="text" class="form-control" id="bingVerCode" placeholder="Paste verification code" value="{$user.bing_verification|escape:"javascript"}" autocomplete="off">' +
                '<p class="form-hint">From <a href="https://www.bing.com/webmasters" target="_blank" rel="noopener">Bing Webmaster Tools</a> &rarr; Add site &rarr; HTML meta tag</p>' +
            '</div>' +
            '<button type="submit" class="btn btn-block btn-primary" id="saveVerBtn">Save</button>' +
        '</form>';
        TinyShop.openModal('Verification Codes', html);

        $('#verificationForm').on('submit', function(e) {ldelim}
            e.preventDefault();
            var $btn = $('#saveVerBtn').prop('disabled', true).text('Saving...');
            TinyShop.api('PUT', '/api/shop', {ldelim}
                google_verification: $('#googleVerCode').val().trim(),
                bing_verification: $('#bingVerCode').val().trim()
            {rdelim}).done(function() {ldelim}
                TinyShop.toast('Verification codes saved');
                TinyShop.closeModal();
                // Update the row status text
                var hasAny = $('#googleVerCode').val().trim() || $('#bingVerCode').val().trim();
                $('#verificationCodesBtn .account-row-value').text(hasAny ? 'Connected' : 'Verify your domain');
            {rdelim}).fail(function(xhr) {ldelim}
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to save';
                TinyShop.toast(msg, 'error');
                $btn.prop('disabled', false).text('Save');
            {rdelim});
        {rdelim});
    {rdelim});

    // --- Gateway enable/disable toggles ---
    $('#stripeEnabledToggle').on('change', function() {
        var enabled = this.checked ? 1 : 0;
        $('#stripeEnabled').val(enabled);
        TinyShop.api('PUT', '/api/shop', { stripe_enabled: enabled }).done(function() {
            TinyShop.toast(enabled ? 'Stripe enabled' : 'Stripe disabled');
        }).fail(function() {
            TinyShop.toast('Failed to update', 'error');
        });
    });
    $('#paypalEnabledToggle').on('change', function() {
        var enabled = this.checked ? 1 : 0;
        $('#paypalEnabled').val(enabled);
        TinyShop.api('PUT', '/api/shop', { paypal_enabled: enabled }).done(function() {
            TinyShop.toast(enabled ? 'PayPal enabled' : 'PayPal disabled');
        }).fail(function() {
            TinyShop.toast('Failed to update', 'error');
        });
    });
    $('#codEnabledToggle').on('change', function() {
        var enabled = this.checked ? 1 : 0;
        TinyShop.api('PUT', '/api/shop', { cod_enabled: enabled }).done(function() {
            TinyShop.toast(enabled ? 'Pay on Delivery enabled' : 'Pay on Delivery disabled');
        }).fail(function() {
            TinyShop.toast('Failed to update', 'error');
        });
    });
    $('#mpesaEnabledToggle').on('change', function() {
        var enabled = this.checked ? 1 : 0;
        $('#mpesaEnabled').val(enabled);
        TinyShop.api('PUT', '/api/shop', { mpesa_enabled: enabled }).done(function() {
            TinyShop.toast(enabled ? 'M-Pesa enabled' : 'M-Pesa disabled');
        }).fail(function() {
            TinyShop.toast('Failed to update', 'error');
        });
    });
    $('#pesapalEnabledToggle').on('change', function() {
        var enabled = this.checked ? 1 : 0;
        $('#pesapalEnabled').val(enabled);
        TinyShop.api('PUT', '/api/shop', { pesapal_enabled: enabled }).done(function() {
            TinyShop.toast(enabled ? 'Pesapal enabled' : 'Pesapal disabled');
        }).fail(function() {
            TinyShop.toast('Failed to update', 'error');
        });
    });

    // --- Setup Stripe (modal) ---
    $('#setupStripeBtn').on('click', function() {
        var currentPk = $('#stripePublicKey').val();
        var currentSk = $('#stripeSecretKey').val();
        var currentMode = $('#stripeMode').val();
        var isConnected = currentSk !== '';

        var html = '<form id="stripeSetupForm" autocomplete="off">' +
            '<div style="display:flex;align-items:center;gap:10px;margin-bottom:20px">' +
                '<i class="fa-brands fa-stripe icon-lg" style="color:#635BFF"></i>' +
                '<span style="font-weight:700;font-size:1rem">Stripe</span>' +
                (isConnected ? '<span class="connected-badge-sm"><i class="fa-solid fa-check"></i> Connected</span>' : '') +
            '</div>' +
            '<div class="form-group">' +
                '<label for="modalStripePk">Publishable Key</label>' +
                '<input type="text" class="form-control" id="modalStripePk" value="' + escapeHtml(currentPk) + '" placeholder="pk_test_..." autocomplete="off">' +
            '</div>' +
            '<div class="form-group">' +
                '<label for="modalStripeSk">Secret Key</label>' +
                '<div class="password-field"><input type="password" class="form-control" id="modalStripeSk" value="' + escapeHtml(currentSk) + '" placeholder="sk_test_..." autocomplete="off">' +
                '<button type="button" class="password-toggle" onclick="togglePw(this)" aria-label="Show password"><i class="fa-solid fa-eye eye-open"></i><i class="fa-solid fa-eye-slash eye-closed" style="display:none"></i></button></div>' +
            '</div>' +
            '<div class="form-toggle-row" style="margin-bottom:20px">' +
                '<div>' +
                    '<div class="form-toggle-label">Live Mode</div>' +
                    '<p class="form-hint" style="margin-top:2px">Use live keys for real payments</p>' +
                '</div>' +
                '<label class="toggle-switch">' +
                    '<input type="checkbox" id="modalStripeMode"' + (currentMode === 'live' ? ' checked' : '') + '>' +
                    '<span class="toggle-slider"></span>' +
                '</label>' +
            '</div>' +
            '<button type="submit" class="btn-block btn-primary" id="saveStripeBtn">Save Stripe Settings</button>' +
            (isConnected ? '<button type="button" class="btn-block btn-link mt-sm" id="disconnectStripeBtn">Disconnect Stripe</button>' : '') +
        '</form>';
        TinyShop.openModal('Stripe Setup', html);

        $('#stripeSetupForm').on('submit', function(e) {
            e.preventDefault();
            var pk = $('#modalStripePk').val().trim();
            var sk = $('#modalStripeSk').val().trim();
            var mode = $('#modalStripeMode').is(':checked') ? 'live' : 'test';
            if (!pk || !sk) {
                TinyShop.toast('Both keys are required', 'error');
                return;
            }
            var $btn = $('#saveStripeBtn').prop('disabled', true).text('Saving...');
            TinyShop.api('PUT', '/api/shop', {
                stripe_public_key: pk,
                stripe_secret_key: sk,
                stripe_mode: mode
            }).done(function() {
                $('#stripePublicKey').val(pk);
                $('#stripeSecretKey').val(sk);
                $('#stripeMode').val(mode);
                $('#stripeStatusDisplay').text('Connected');
                TinyShop.toast('Stripe connected!');
                TinyShop.closeModal();
                setTimeout(function() { location.reload(); }, 400);
            }).fail(function(xhr) {
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to save';
                TinyShop.toast(msg, 'error');
                $btn.prop('disabled', false).text('Save Stripe Settings');
            });
        });

        $('#disconnectStripeBtn').on('click', function() {
            TinyShop.confirm('Disconnect Stripe?', 'Customers won\'t be able to pay with cards.', 'Disconnect', function() {
                $('#confirmModalOk').prop('disabled', true).text('Disconnecting...');
                TinyShop.api('PUT', '/api/shop', {
                    stripe_public_key: '',
                    stripe_secret_key: '',
                    stripe_mode: 'test'
                }).done(function() {
                    $('#stripePublicKey').val('');
                    $('#stripeSecretKey').val('');
                    $('#stripeMode').val('test');
                    $('#stripeStatusDisplay').text('Not connected');
                    TinyShop.toast('Stripe disconnected');
                    TinyShop.closeModal();
                    setTimeout(function() { location.reload(); }, 400);
                }).fail(function() {
                    TinyShop.toast('Failed to disconnect', 'error');
                    TinyShop.closeModal();
                });
            }, 'danger');
        });
    });

    // --- Setup PayPal (modal) ---
    $('#setupPaypalBtn').on('click', function() {
        var currentCid = $('#paypalClientId').val();
        var currentSecret = $('#paypalSecret').val();
        var currentMode = $('#paypalMode').val();
        var isConnected = currentCid !== '';

        var html = '<form id="paypalSetupForm" autocomplete="off">' +
            '<div style="display:flex;align-items:center;gap:10px;margin-bottom:20px">' +
                '<i class="fa-brands fa-paypal icon-lg" style="color:#003087"></i>' +
                '<span style="font-weight:700;font-size:1rem">PayPal</span>' +
                (isConnected ? '<span class="connected-badge-sm"><i class="fa-solid fa-check"></i> Connected</span>' : '') +
            '</div>' +
            '<div class="form-group">' +
                '<label for="modalPaypalCid">Client ID</label>' +
                '<input type="text" class="form-control" id="modalPaypalCid" value="' + escapeHtml(currentCid) + '" placeholder="Your PayPal Client ID" autocomplete="off">' +
            '</div>' +
            '<div class="form-group">' +
                '<label for="modalPaypalSecret">Secret</label>' +
                '<div class="password-field"><input type="password" class="form-control" id="modalPaypalSecret" value="' + escapeHtml(currentSecret) + '" placeholder="Your PayPal Secret" autocomplete="off">' +
                '<button type="button" class="password-toggle" onclick="togglePw(this)" aria-label="Show password"><i class="fa-solid fa-eye eye-open"></i><i class="fa-solid fa-eye-slash eye-closed" style="display:none"></i></button></div>' +
            '</div>' +
            '<div class="form-toggle-row" style="margin-bottom:20px">' +
                '<div>' +
                    '<div class="form-toggle-label">Live Mode</div>' +
                    '<p class="form-hint" style="margin-top:2px">Use live keys for real payments</p>' +
                '</div>' +
                '<label class="toggle-switch">' +
                    '<input type="checkbox" id="modalPaypalMode"' + (currentMode === 'live' ? ' checked' : '') + '>' +
                    '<span class="toggle-slider"></span>' +
                '</label>' +
            '</div>' +
            '<button type="submit" class="btn-block btn-primary" id="savePaypalBtn">Save PayPal Settings</button>' +
            (isConnected ? '<button type="button" class="btn-block btn-link mt-sm" id="disconnectPaypalBtn">Disconnect PayPal</button>' : '') +
        '</form>';
        TinyShop.openModal('PayPal Setup', html);

        $('#paypalSetupForm').on('submit', function(e) {
            e.preventDefault();
            var cid = $('#modalPaypalCid').val().trim();
            var secret = $('#modalPaypalSecret').val().trim();
            var mode = $('#modalPaypalMode').is(':checked') ? 'live' : 'test';
            if (!cid || !secret) {
                TinyShop.toast('Both fields are required', 'error');
                return;
            }
            var $btn = $('#savePaypalBtn').prop('disabled', true).text('Saving...');
            TinyShop.api('PUT', '/api/shop', {
                paypal_client_id: cid,
                paypal_secret: secret,
                paypal_mode: mode
            }).done(function() {
                $('#paypalClientId').val(cid);
                $('#paypalSecret').val(secret);
                $('#paypalMode').val(mode);
                $('#paypalStatusDisplay').text('Connected');
                TinyShop.toast('PayPal connected!');
                TinyShop.closeModal();
                setTimeout(function() { location.reload(); }, 400);
            }).fail(function(xhr) {
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to save';
                TinyShop.toast(msg, 'error');
                $btn.prop('disabled', false).text('Save PayPal Settings');
            });
        });

        $('#disconnectPaypalBtn').on('click', function() {
            TinyShop.confirm('Disconnect PayPal?', 'Customers won\'t be able to pay with PayPal.', 'Disconnect', function() {
                $('#confirmModalOk').prop('disabled', true).text('Disconnecting...');
                TinyShop.api('PUT', '/api/shop', {
                    paypal_client_id: '',
                    paypal_secret: '',
                    paypal_mode: 'test'
                }).done(function() {
                    $('#paypalClientId').val('');
                    $('#paypalSecret').val('');
                    $('#paypalMode').val('test');
                    $('#paypalStatusDisplay').text('Not connected');
                    TinyShop.toast('PayPal disconnected');
                    TinyShop.closeModal();
                    setTimeout(function() { location.reload(); }, 400);
                }).fail(function() {
                    TinyShop.toast('Failed to disconnect', 'error');
                    TinyShop.closeModal();
                });
            }, 'danger');
        });
    });

    // --- Setup M-Pesa (modal) ---
    $('#setupMpesaBtn').on('click', function() {
        var currentShortcode = $('#mpesaShortcode').val();
        var currentKey = $('#mpesaConsumerKey').val();
        var currentSecret = $('#mpesaConsumerSecret').val();
        var currentPasskey = $('#mpesaPasskey').val();
        var currentMode = $('#mpesaMode').val();
        var isConnected = currentShortcode !== '';

        var html = '<form id="mpesaSetupForm" autocomplete="off">' +
            '<div style="display:flex;align-items:center;gap:10px;margin-bottom:20px">' +
                '<svg width="28" height="28" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="12" fill="#4CAF50"/><text x="12" y="16" text-anchor="middle" font-size="10" font-weight="bold" fill="white">M</text></svg>' +
                '<span style="font-weight:700;font-size:1rem">M-Pesa</span>' +
                (isConnected ? '<span class="connected-badge-sm"><i class="fa-solid fa-check"></i> Connected</span>' : '') +
            '</div>' +
            '<div class="form-group">' +
                '<label for="modalMpesaShortcode">Shortcode (Till / Paybill)</label>' +
                '<input type="text" class="form-control" id="modalMpesaShortcode" value="' + escapeHtml(currentShortcode) + '" placeholder="e.g. 174379" autocomplete="off" inputmode="numeric">' +
            '</div>' +
            '<div class="form-group">' +
                '<label for="modalMpesaKey">Consumer Key</label>' +
                '<input type="text" class="form-control" id="modalMpesaKey" value="' + escapeHtml(currentKey) + '" placeholder="From Daraja portal" autocomplete="off">' +
            '</div>' +
            '<div class="form-group">' +
                '<label for="modalMpesaSecret">Consumer Secret</label>' +
                '<div class="password-field"><input type="password" class="form-control" id="modalMpesaSecret" value="' + escapeHtml(currentSecret) + '" placeholder="From Daraja portal" autocomplete="off">' +
                '<button type="button" class="password-toggle" onclick="togglePw(this)" aria-label="Show password"><i class="fa-solid fa-eye eye-open"></i><i class="fa-solid fa-eye-slash eye-closed" style="display:none"></i></button></div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label for="modalMpesaPasskey">Passkey</label>' +
                '<div class="password-field"><input type="password" class="form-control" id="modalMpesaPasskey" value="' + escapeHtml(currentPasskey) + '" placeholder="STK Push passkey" autocomplete="off">' +
                '<button type="button" class="password-toggle" onclick="togglePw(this)" aria-label="Show password"><i class="fa-solid fa-eye eye-open"></i><i class="fa-solid fa-eye-slash eye-closed" style="display:none"></i></button></div>' +
            '</div>' +
            '<div class="form-toggle-row" style="margin-bottom:20px">' +
                '<div>' +
                    '<div class="form-toggle-label">Live Mode</div>' +
                    '<p class="form-hint" style="margin-top:2px">Use live credentials for real payments</p>' +
                '</div>' +
                '<label class="toggle-switch">' +
                    '<input type="checkbox" id="modalMpesaMode"' + (currentMode === 'live' ? ' checked' : '') + '>' +
                    '<span class="toggle-slider"></span>' +
                '</label>' +
            '</div>' +
            '<button type="submit" class="btn-block btn-primary" id="saveMpesaBtn">Save M-Pesa Settings</button>' +
            (isConnected ? '<button type="button" class="btn-block btn-link mt-sm" id="disconnectMpesaBtn">Disconnect M-Pesa</button>' : '') +
        '</form>';
        TinyShop.openModal('M-Pesa Setup', html);

        $('#mpesaSetupForm').on('submit', function(e) {
            e.preventDefault();
            var shortcode = $('#modalMpesaShortcode').val().trim();
            var key = $('#modalMpesaKey').val().trim();
            var secret = $('#modalMpesaSecret').val().trim();
            var passkey = $('#modalMpesaPasskey').val().trim();
            var mode = $('#modalMpesaMode').is(':checked') ? 'live' : 'test';
            if (!shortcode || !key || !secret || !passkey) {
                TinyShop.toast('All fields are required', 'error');
                return;
            }
            var $btn = $('#saveMpesaBtn').prop('disabled', true).text('Saving...');
            TinyShop.api('PUT', '/api/shop', {
                mpesa_shortcode: shortcode,
                mpesa_consumer_key: key,
                mpesa_consumer_secret: secret,
                mpesa_passkey: passkey,
                mpesa_mode: mode
            }).done(function() {
                $('#mpesaShortcode').val(shortcode);
                $('#mpesaConsumerKey').val(key);
                $('#mpesaConsumerSecret').val(secret);
                $('#mpesaPasskey').val(passkey);
                $('#mpesaMode').val(mode);
                $('#mpesaStatusDisplay').text('Connected');
                TinyShop.toast('M-Pesa connected!');
                TinyShop.closeModal();
                setTimeout(function() { location.reload(); }, 400);
            }).fail(function(xhr) {
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to save';
                TinyShop.toast(msg, 'error');
                $btn.prop('disabled', false).text('Save M-Pesa Settings');
            });
        });

        $('#disconnectMpesaBtn').on('click', function() {
            TinyShop.confirm('Disconnect M-Pesa?', 'Customers won\'t be able to pay with M-Pesa.', 'Disconnect', function() {
                $('#confirmModalOk').prop('disabled', true).text('Disconnecting...');
                TinyShop.api('PUT', '/api/shop', {
                    mpesa_shortcode: '',
                    mpesa_consumer_key: '',
                    mpesa_consumer_secret: '',
                    mpesa_passkey: '',
                    mpesa_mode: 'test'
                }).done(function() {
                    $('#mpesaShortcode').val('');
                    $('#mpesaConsumerKey').val('');
                    $('#mpesaConsumerSecret').val('');
                    $('#mpesaPasskey').val('');
                    $('#mpesaMode').val('test');
                    $('#mpesaStatusDisplay').text('Not connected');
                    TinyShop.toast('M-Pesa disconnected');
                    TinyShop.closeModal();
                    setTimeout(function() { location.reload(); }, 400);
                }).fail(function() {
                    TinyShop.toast('Failed to disconnect', 'error');
                    TinyShop.closeModal();
                });
            }, 'danger');
        });
    });

    // --- Setup Pesapal (modal) ---
    $('#setupPesapalBtn').on('click', function() {
        var currentKey = $('#pesapalConsumerKey').val();
        var currentSecret = $('#pesapalConsumerSecret').val();
        var currentMode = $('#pesapalMode').val();
        var isConnected = currentKey !== '';

        var html = '<form id="pesapalSetupForm" autocomplete="off">' +
            '<div style="display:flex;align-items:center;gap:10px;margin-bottom:20px">' +
                '<svg width="28" height="28" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="12" fill="#0A7BC4"/><text x="12" y="16" text-anchor="middle" font-size="9" font-weight="bold" fill="white">PP</text></svg>' +
                '<span style="font-weight:700;font-size:1rem">Pesapal</span>' +
                (isConnected ? '<span class="connected-badge-sm"><i class="fa-solid fa-check"></i> Connected</span>' : '') +
            '</div>' +
            '<div class="form-group">' +
                '<label for="modalPesapalKey">Consumer Key</label>' +
                '<input type="text" class="form-control" id="modalPesapalKey" value="' + escapeHtml(currentKey) + '" placeholder="From Pesapal merchant portal" autocomplete="off">' +
            '</div>' +
            '<div class="form-group">' +
                '<label for="modalPesapalSecret">Consumer Secret</label>' +
                '<div class="password-field"><input type="password" class="form-control" id="modalPesapalSecret" value="' + escapeHtml(currentSecret) + '" placeholder="From Pesapal merchant portal" autocomplete="off">' +
                '<button type="button" class="password-toggle" onclick="togglePw(this)" aria-label="Show password"><i class="fa-solid fa-eye eye-open"></i><i class="fa-solid fa-eye-slash eye-closed" style="display:none"></i></button></div>' +
            '</div>' +
            '<div class="form-toggle-row" style="margin-bottom:20px">' +
                '<div>' +
                    '<div class="form-toggle-label">Live Mode</div>' +
                    '<p class="form-hint" style="margin-top:2px">Use live credentials for real payments</p>' +
                '</div>' +
                '<label class="toggle-switch">' +
                    '<input type="checkbox" id="modalPesapalMode"' + (currentMode === 'live' ? ' checked' : '') + '>' +
                    '<span class="toggle-slider"></span>' +
                '</label>' +
            '</div>' +
            '<button type="submit" class="btn-block btn-primary" id="savePesapalBtn">Save Pesapal Settings</button>' +
            (isConnected ? '<button type="button" class="btn-block btn-link mt-sm" id="disconnectPesapalBtn">Disconnect Pesapal</button>' : '') +
        '</form>';
        TinyShop.openModal('Pesapal Setup', html);

        $('#pesapalSetupForm').on('submit', function(e) {
            e.preventDefault();
            var key = $('#modalPesapalKey').val().trim();
            var secret = $('#modalPesapalSecret').val().trim();
            var mode = $('#modalPesapalMode').is(':checked') ? 'live' : 'test';
            if (!key || !secret) {
                TinyShop.toast('All fields are required', 'error');
                return;
            }
            var $btn = $('#savePesapalBtn').prop('disabled', true).text('Saving...');
            TinyShop.api('PUT', '/api/shop', {
                pesapal_consumer_key: key,
                pesapal_consumer_secret: secret,
                pesapal_mode: mode
            }).done(function() {
                $('#pesapalConsumerKey').val(key);
                $('#pesapalConsumerSecret').val(secret);
                $('#pesapalMode').val(mode);
                $('#pesapalStatusDisplay').text('Connected');
                TinyShop.toast('Pesapal connected!');
                TinyShop.closeModal();
                setTimeout(function() { location.reload(); }, 400);
            }).fail(function(xhr) {
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to save';
                TinyShop.toast(msg, 'error');
                $btn.prop('disabled', false).text('Save Pesapal Settings');
            });
        });

        $('#disconnectPesapalBtn').on('click', function() {
            TinyShop.confirm('Disconnect Pesapal?', 'Customers won\'t be able to pay with Pesapal.', 'Disconnect', function() {
                $('#confirmModalOk').prop('disabled', true).text('Disconnecting...');
                TinyShop.api('PUT', '/api/shop', {
                    pesapal_consumer_key: '',
                    pesapal_consumer_secret: '',
                    pesapal_mode: 'test'
                }).done(function() {
                    $('#pesapalConsumerKey').val('');
                    $('#pesapalConsumerSecret').val('');
                    $('#pesapalMode').val('test');
                    $('#pesapalStatusDisplay').text('Not connected');
                    TinyShop.toast('Pesapal disconnected');
                    TinyShop.closeModal();
                    setTimeout(function() { location.reload(); }, 400);
                }).fail(function() {
                    TinyShop.toast('Failed to disconnect', 'error');
                    TinyShop.closeModal();
                });
            }, 'danger');
        });
    });

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
            '<button type="submit" class="btn-block btn-primary" id="saveSubdomainBtn" disabled>Save URL</button>' +
        '</form>';
        TinyShop.openModal('Change Shop URL', html);

        $('#newSubdomain').on('input', function() {
            var val = $(this).val().toLowerCase().replace(/[^a-z0-9\-]/g, '');
            $(this).val(val);
            $('#subdomainPreviewModal').text(shopUrl(val || '...'));
            $('#saveSubdomainBtn').prop('disabled', val === currentVal || val === '');
        });

        $('#subdomainChangeForm').on('submit', function(e) {
            e.preventDefault();
            var newVal = $('#newSubdomain').val().trim();
            if (!newVal) return;
            if (newVal === currentVal) { TinyShop.closeModal(); return; }

            // Confirmation prompt
            var confirmHtml = '<p class="form-hint" style="margin-bottom:16px">Your shop URL will change to:</p>' +
                '<div class="url-preview">' + escapeHtml(shopUrl(newVal)) + '</div>' +
                '<p class="form-hint" style="margin-bottom:20px">Anyone using your old link won\'t be able to find your shop. Make sure to update your links everywhere.</p>' +
                '<div class="confirm-actions">' +
                    '<button type="button" class="btn-block btn-sm" id="urlConfirmCancel">Cancel</button>' +
                    '<button type="button" class="btn-block btn-sm btn-primary" id="urlConfirmSave">Confirm</button>' +
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

    // --- Add to Homescreen ---
    $('#addToHomescreenBtn').on('click', function() {
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
        html += '</div>';

        TinyShop.openModal('Add to Homescreen', html);
    });

    // --- Change Custom Domain (modal) ---
    $('#changeDomainBtn').on('click', function() {
        if ($(this).data('locked')) {
            TinyShop.confirm('Upgrade to unlock', 'Custom domains are available on a higher plan. Upgrade to connect your own domain.', 'Upgrade', function() {
                TinyShop.navigate('/dashboard/billing');
            });
            return;
        }
        var currentDomain = $('#customDomain').val();
        var html = '';

        if (currentDomain) {
            // Connected state
            html = '<div class="domain-connected-card" style="margin-bottom:16px">' +
                '<div class="domain-connected-icon">' +
                    '<i class="fa-solid fa-check" style="font-size:18px"></i>' +
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
                '<button type="submit" class="btn-block btn-primary mt-sm" id="saveDomainBtn" disabled>Connect Domain</button>' +
            '</form>';
        }

        TinyShop.openModal('Custom Domain', html);

        // Enable button only when domain is entered
        $('#newDomain').on('input', function() {
            $('#saveDomainBtn').prop('disabled', $(this).val().trim() === '');
        });

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
                    '<button type="button" class="btn-block btn-sm" id="domainConfirmCancel" style="flex:1;background:var(--color-bg-secondary);color:var(--color-text)">Cancel</button>' +
                    '<button type="button" class="btn-block btn-sm btn-primary" id="domainConfirmSave" style="flex:1">Connect</button>' +
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
                    '<button type="button" class="btn-block btn-sm" id="domainRemoveCancel" style="flex:1;background:var(--color-bg-secondary);color:var(--color-text)">Cancel</button>' +
                    '<button type="button" class="btn-block btn-sm" id="domainRemoveConfirm" style="flex:1;background:#FF3B30;color:#fff">Remove</button>' +
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

    // Save form
    $('#shopForm').on('submit', function(e) {
        e.preventDefault();
        var $btn = $('#saveShopBtn').prop('disabled', true).text('Saving...');
        var data = {};
        // Payment fields are excluded — managed in their own modals
        var paymentFields = ['stripe_public_key', 'stripe_secret_key', 'stripe_mode', 'paypal_client_id', 'paypal_secret', 'paypal_mode', 'mpesa_shortcode', 'mpesa_consumer_key', 'mpesa_consumer_secret', 'mpesa_passkey', 'mpesa_mode', 'pesapal_consumer_key', 'pesapal_consumer_secret', 'pesapal_mode'];
        $(this).serializeArray().forEach(function(item) {
            if (paymentFields.indexOf(item.name) === -1) {
                data[item.name] = item.value;
            }
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
                '<button type="button" class="password-toggle" onclick="togglePw(this)" aria-label="Show password"><i class="fa-solid fa-eye eye-open"></i><i class="fa-solid fa-eye-slash eye-closed" style="display:none"></i></button></div>' +
            '</div>' +
            '<button type="submit" class="btn-block" id="deleteShopConfirmBtn" style="background:#FF3B30;color:#fff;opacity:0.4;pointer-events:none">Delete My Shop Forever</button>' +
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
                '<button type="button" class="password-toggle" onclick="togglePw(this)" aria-label="Show password"><i class="fa-solid fa-eye eye-open"></i><i class="fa-solid fa-eye-slash eye-closed" style="display:none"></i></button></div>' +
            '</div>' +
            '<button type="submit" class="btn-block btn-primary" id="saveEmailBtn">Update Email</button>' +
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
                '<button type="button" class="password-toggle" onclick="togglePw(this)" aria-label="Show password"><i class="fa-solid fa-eye eye-open"></i><i class="fa-solid fa-eye-slash eye-closed" style="display:none"></i></button></div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label for="newPassword">New Password</label>' +
                '<div class="password-field"><input type="password" class="form-control" id="newPassword" placeholder="At least 6 characters" autocomplete="off" required>' +
                '<button type="button" class="password-toggle" onclick="togglePw(this)" aria-label="Show password"><i class="fa-solid fa-eye eye-open"></i><i class="fa-solid fa-eye-slash eye-closed" style="display:none"></i></button></div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label for="confirmPassword">Confirm New Password</label>' +
                '<div class="password-field"><input type="password" class="form-control" id="confirmPassword" placeholder="Re-enter new password" autocomplete="off" required>' +
                '<button type="button" class="password-toggle" onclick="togglePw(this)" aria-label="Show password"><i class="fa-solid fa-eye eye-open"></i><i class="fa-solid fa-eye-slash eye-closed" style="display:none"></i></button></div>' +
            '</div>' +
            '<button type="submit" class="btn-block btn-primary" id="savePassBtn">Update Password</button>' +
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
