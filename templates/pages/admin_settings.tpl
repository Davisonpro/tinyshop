{extends file="layouts/admin.tpl"}

{block name="content"}
<div class="dash-topbar">
    <span class="dash-topbar-title">Settings</span>
</div>

<form id="settingsForm" class="dash-form" autocomplete="off">

    {* --- Platform --- *}
    <div class="form-section">
        <div class="form-section-title">
            <i class="fa-solid fa-globe icon-sm"></i>
            Platform
        </div>
        <div class="form-group">
            <label for="siteName">Platform Name</label>
            <input type="text" class="form-control" id="siteName" value="{$settings.site_name|escape}" data-key="site_name" autocomplete="off">
        </div>
        <div class="form-group">
            <label>Site Logo & Favicon</label>
            <div class="brand-uploads">
                <div class="brand-upload-item">
                    <input type="file" id="siteLogoInput" accept="image/*" class="d-none">
                    <div class="logo-upload" id="siteLogoZone">
                        <div class="logo-upload-preview" id="siteLogoPreview" {if !$settings.site_logo}style="display:none"{/if}>
                            <img src="{$settings.site_logo|escape}" alt="Site logo" id="siteLogoImg">
                            <div class="logo-upload-overlay">
                                <i class="fa-solid fa-camera"></i>
                                <span>Change</span>
                            </div>
                        </div>
                        <div class="logo-upload-empty" id="siteLogoPlaceholder" {if $settings.site_logo}style="display:none"{/if}>
                            <i class="fa-solid fa-image icon-xl"></i>
                            <span>Add logo</span>
                        </div>
                    </div>
                    <span class="brand-upload-label">Logo</span>
                </div>
                <div class="brand-upload-item">
                    <input type="file" id="siteFaviconInput" accept="image/*" class="d-none">
                    <div class="favicon-upload" id="siteFaviconZone">
                        <div class="favicon-upload-preview" id="siteFaviconPreview" {if !$settings.site_favicon}style="display:none"{/if}>
                            <img src="{$settings.site_favicon|escape}" alt="Favicon" id="siteFaviconImg">
                            <div class="logo-upload-overlay">
                                <i class="fa-solid fa-camera"></i>
                                <span>Change</span>
                            </div>
                        </div>
                        <div class="favicon-upload-empty" id="siteFaviconPlaceholder" {if $settings.site_favicon}style="display:none"{/if}>
                            <i class="fa-solid fa-globe icon-lg"></i>
                            <span>Add</span>
                        </div>
                    </div>
                    <span class="brand-upload-label">Favicon</span>
                </div>
            </div>
            <p class="form-hint">Your logo shows on the landing page. The favicon is the small icon in browser tabs.</p>
        </div>
        <div class="form-group">
            <label for="baseDomain">Base Domain</label>
            <input type="text" class="form-control" id="baseDomain" value="{$settings.base_domain|escape}" data-key="base_domain" autocomplete="off">
            <p class="form-hint">Seller shops use subdomains (e.g. shop.{$settings.base_domain|escape})</p>
        </div>
        <div class="form-group">
            <label for="supportEmail">Support Email</label>
            <input type="email" class="form-control" id="supportEmail" value="{$settings.support_email|escape}" data-key="support_email" placeholder="support@example.com" autocomplete="off">
        </div>
        <div class="form-group">
            <label for="defaultCurrency">Default Currency</label>
            <select class="form-control" id="defaultCurrency" data-key="default_currency">
                {foreach ['KES','NGN','TZS','UGX','RWF','ETB','GHS','ZAR','USD','EUR','GBP','XOF'] as $cur}
                    <option value="{$cur}" {if $settings.default_currency == $cur}selected{/if}>{$cur}</option>
                {/foreach}
            </select>
        </div>
    </div>

    {* --- Email / SMTP --- *}
    <div class="form-section">
        <div class="form-section-title">
            <i class="fa-solid fa-envelope icon-sm"></i>
            Email (SMTP)
        </div>
        <div class="form-group">
            <label for="mailFromName">From Name</label>
            <input type="text" class="form-control" id="mailFromName" value="{$settings.mail_from_name|escape}" data-key="mail_from_name" placeholder="{$app_name|escape}" autocomplete="off">
        </div>
        <div class="form-group">
            <label for="mailFromEmail">From Email</label>
            <input type="email" class="form-control" id="mailFromEmail" value="{$settings.mail_from_email|escape}" data-key="mail_from_email" placeholder="noreply@example.com" autocomplete="off">
        </div>
        <div class="form-group">
            <label for="smtpHost">SMTP Host</label>
            <input type="text" class="form-control" id="smtpHost" value="{$settings.smtp_host|escape}" data-key="smtp_host" placeholder="smtp.gmail.com" autocomplete="off">
        </div>
        <div class="form-group settings-inline-group">
            <div class="settings-inline-field">
                <label for="smtpPort">Port</label>
                <input type="number" class="form-control" id="smtpPort" value="{$settings.smtp_port|escape}" data-key="smtp_port" placeholder="587" autocomplete="off">
            </div>
            <div class="settings-inline-field">
                <label for="smtpEncryption">Encryption</label>
                <select class="form-control" id="smtpEncryption" data-key="smtp_encryption">
                    <option value="tls" {if $settings.smtp_encryption == 'tls'}selected{/if}>TLS</option>
                    <option value="ssl" {if $settings.smtp_encryption == 'ssl'}selected{/if}>SSL</option>
                    <option value="" {if !$settings.smtp_encryption}selected{/if}>None</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label for="smtpUsername">SMTP Username</label>
            <input type="text" class="form-control" id="smtpUsername" value="{$settings.smtp_username|escape}" data-key="smtp_username" placeholder="your@email.com" autocomplete="off">
        </div>
        <div class="form-group">
            <label for="smtpPassword">SMTP Password</label>
            <div class="password-field">
                <input type="password" class="form-control" id="smtpPassword" value="{$settings.smtp_password|escape}" data-key="smtp_password" placeholder="App password or SMTP key" autocomplete="new-password">
                <button type="button" class="password-toggle" onclick="togglePw(this)" aria-label="Show password">
                    <i class="fa-solid fa-eye eye-open"></i>
                    <i class="fa-solid fa-eye-slash eye-closed d-none"></i>
                </button>
            </div>
            <p class="form-hint">For Gmail, use an App Password (not your regular password)</p>
        </div>
        <div class="form-group">
            <button type="button" class="btn-test-email" id="testEmailBtn">
                <i class="fa-solid fa-paper-plane icon-sm"></i>
                Send Test Email
            </button>
            <p class="form-hint">Sends a test email to your support email address</p>
        </div>
    </div>

    {* --- Limits --- *}
    <div class="form-section">
        <div class="form-section-title">
            <i class="fa-solid fa-chart-bar icon-sm"></i>
            Limits
        </div>
        <div class="form-group">
            <label for="maxProducts">Max Products Per Seller</label>
            <input type="number" class="form-control" id="maxProducts" value="{$settings.max_products_per_seller|escape}" data-key="max_products_per_seller" min="1" max="10000" autocomplete="off">
            <p class="form-hint">How many products each seller can create</p>
        </div>
    </div>

    {* --- Access Control --- *}
    <div class="form-section">
        <div class="form-section-title">
            <i class="fa-solid fa-lock icon-sm"></i>
            Access Control
        </div>

        <div class="form-group">
            <div class="settings-toggle-row">
                <div class="settings-toggle-info">
                    <span class="settings-toggle-label">Allow Registration</span>
                    <span class="settings-toggle-desc">New sellers can sign up for accounts</span>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" id="allowRegistration" data-key="allow_registration" {if $settings.allow_registration == '1'}checked{/if}>
                    <span class="toggle-slider"></span>
                </label>
            </div>
        </div>

        <div class="form-group">
            <div class="settings-toggle-row">
                <div class="settings-toggle-info">
                    <span class="settings-toggle-label">Maintenance Mode</span>
                    <span class="settings-toggle-desc">Show maintenance page to non-admin users</span>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" id="maintenanceMode" data-key="maintenance_mode" {if $settings.maintenance_mode == '1'}checked{/if}>
                    <span class="toggle-slider"></span>
                </label>
            </div>
        </div>
    </div>

    {* --- SEO & Marketing --- *}
    <div class="form-section">
        <div class="form-section-title">
            <i class="fa-solid fa-magnifying-glass icon-sm"></i>
            SEO &amp; Marketing
        </div>

        <div class="form-group">
            <label>Sitemap URL</label>
            <div class="settings-copy-row">
                <input type="text" class="form-control" id="sitemapUrl" value="{$base_url}/sitemap.xml" readonly>
                <button type="button" class="btn-copy" id="copySitemapBtn" title="Copy URL">
                    <i class="fa-solid fa-copy"></i>
                </button>
            </div>
            <p class="form-hint">Add this in <a href="https://search.google.com/search-console" target="_blank" rel="noopener">Google Search Console</a> and <a href="https://www.bing.com/webmasters" target="_blank" rel="noopener">Bing Webmaster Tools</a></p>
        </div>

        <div class="form-group">
            <button type="button" class="btn-test-email" id="pingSitemapBtn">
                <i class="fa-solid fa-paper-plane icon-sm"></i>
                Notify Search Engines
            </button>
            <p class="form-hint">Ping Google and Bing to crawl your updated sitemap</p>
        </div>

        <div class="form-group">
            <label for="googleVerification">Google Verification Code</label>
            <input type="text" class="form-control" id="googleVerification" value="{$settings.google_verification|escape}" data-key="google_verification" placeholder="e.g. abc123xyz" autocomplete="off">
            <p class="form-hint">The content value from your Google Search Console meta tag</p>
        </div>

        <div class="form-group">
            <label for="bingVerification">Bing Verification Code</label>
            <input type="text" class="form-control" id="bingVerification" value="{$settings.bing_verification|escape}" data-key="bing_verification" placeholder="e.g. 1234ABCD" autocomplete="off">
            <p class="form-hint">The content value from your Bing Webmaster meta tag</p>
        </div>

        <div class="form-group">
            <label for="robotsExtra">Custom Robots.txt Rules</label>
            <textarea class="form-control" id="robotsExtra" data-key="robots_extra" rows="3" placeholder="e.g. Disallow: /private/">{$settings.robots_extra|escape}</textarea>
            <p class="form-hint">Extra rules appended to <a href="/robots.txt" target="_blank">robots.txt</a></p>
        </div>
    </div>

    {* --- Analytics --- *}
    <div class="form-section">
        <div class="form-section-title">
            <i class="fa-solid fa-chart-bar icon-sm"></i>
            Analytics
        </div>

        <div class="form-group">
            <label for="googleAnalyticsId">Google Analytics ID</label>
            <input type="text" class="form-control" id="googleAnalyticsId" value="{$settings.google_analytics_id|escape}" data-key="google_analytics_id" placeholder="G-XXXXXXXXXX" autocomplete="off">
            <p class="form-hint">GA4 Measurement ID (starts with G-)</p>
        </div>

        <div class="form-group">
            <label for="facebookPixelId">Facebook Pixel ID</label>
            <input type="text" class="form-control" id="facebookPixelId" value="{$settings.facebook_pixel_id|escape}" data-key="facebook_pixel_id" placeholder="e.g. 123456789" autocomplete="off">
        </div>
    </div>

    <button type="submit" class="btn-primary" id="saveSettingsBtn">Save Changes</button>
</form>

{* --- Platform Billing (tappable rows → modals, outside main form) --- *}
<input type="hidden" id="platformStripePublicKey" value="{$settings.platform_stripe_public_key|escape}">
<input type="hidden" id="platformStripeSecretKey" value="{$settings.platform_stripe_secret_key|escape}">
<input type="hidden" id="platformStripeMode" value="{$settings.platform_stripe_mode|default:'test'}">
<input type="hidden" id="platformPaypalClientId" value="{$settings.platform_paypal_client_id|escape}">
<input type="hidden" id="platformPaypalSecret" value="{$settings.platform_paypal_secret|escape}">
<input type="hidden" id="platformPaypalMode" value="{$settings.platform_paypal_mode|default:'test'}">
<input type="hidden" id="platformMpesaShortcode" value="{$settings.platform_mpesa_shortcode|escape}">
<input type="hidden" id="platformMpesaConsumerKey" value="{$settings.platform_mpesa_consumer_key|escape}">
<input type="hidden" id="platformMpesaConsumerSecret" value="{$settings.platform_mpesa_consumer_secret|escape}">
<input type="hidden" id="platformMpesaPasskey" value="{$settings.platform_mpesa_passkey|escape}">
<input type="hidden" id="platformMpesaMode" value="{$settings.platform_mpesa_mode|default:'test'}">

<div class="dash-form" style="padding-top:0">
    <div class="form-section">
        <div class="form-section-title">
            <i class="fa-solid fa-credit-card icon-sm"></i>
            Platform Billing
        </div>
        <p class="form-hint mb-md">Credentials for collecting subscription payments from sellers</p>
        <div class="account-row" id="setupPlatformStripeBtn">
            <div class="account-row-left">
                <i class="fa-brands fa-stripe icon-lg" style="color:#635BFF"></i>
                <div>
                    <div class="account-row-label">Stripe</div>
                    <div class="account-row-value" id="platformStripeStatusDisplay">{if $settings.platform_stripe_secret_key}Connected{else}Not connected{/if}</div>
                </div>
            </div>
            {if $settings.platform_stripe_secret_key}
            <span class="gateway-status-badge">
                <i class="fa-solid fa-check icon-xs"></i>
                {if $settings.platform_stripe_mode == 'live'}Live{else}Test{/if}
            </span>
            {else}
            <i class="fa-solid fa-chevron-right account-row-chevron"></i>
            {/if}
        </div>
        <div class="account-row" id="setupPlatformPaypalBtn">
            <div class="account-row-left">
                <i class="fa-brands fa-paypal icon-lg" style="color:#003087"></i>
                <div>
                    <div class="account-row-label">PayPal</div>
                    <div class="account-row-value" id="platformPaypalStatusDisplay">{if $settings.platform_paypal_client_id}Connected{else}Not connected{/if}</div>
                </div>
            </div>
            {if $settings.platform_paypal_client_id}
            <span class="gateway-status-badge">
                <i class="fa-solid fa-check icon-xs"></i>
                {if $settings.platform_paypal_mode == 'live'}Live{else}Test{/if}
            </span>
            {else}
            <i class="fa-solid fa-chevron-right account-row-chevron"></i>
            {/if}
        </div>
        <div class="account-row" id="setupPlatformMpesaBtn">
            <div class="account-row-left">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" style="flex-shrink:0"><circle cx="12" cy="12" r="12" fill="#4CAF50"/><text x="12" y="16" text-anchor="middle" font-size="10" font-weight="bold" fill="white">M</text></svg>
                <div>
                    <div class="account-row-label">M-Pesa</div>
                    <div class="account-row-value" id="platformMpesaStatusDisplay">{if $settings.platform_mpesa_shortcode}Connected{else}Not connected{/if}</div>
                </div>
            </div>
            {if $settings.platform_mpesa_shortcode}
            <span class="gateway-status-badge">
                <i class="fa-solid fa-check icon-xs"></i>
                {if $settings.platform_mpesa_mode == 'live'}Live{else}Test{/if}
            </span>
            {else}
            <i class="fa-solid fa-chevron-right account-row-chevron"></i>
            {/if}
        </div>
    </div>
</div>

{* --- File Storage (S3) --- *}
<input type="hidden" id="s3Bucket" value="{$settings.s3_bucket|escape}">
<input type="hidden" id="s3Region" value="{$settings.s3_region|default:'us-east-1'|escape}">
<input type="hidden" id="s3AccessKey" value="{$settings.s3_access_key|escape}">
<input type="hidden" id="s3SecretKey" value="{$settings.s3_secret_key|escape}">
<input type="hidden" id="s3Endpoint" value="{$settings.s3_endpoint|escape}">
<input type="hidden" id="s3CdnUrl" value="{$settings.s3_cdn_url|escape}">

<div class="dash-form" style="padding-top:0">
    <div class="form-section">
        <div class="form-section-title">
            <i class="fa-solid fa-cloud-arrow-up icon-sm"></i>
            File Storage
        </div>
        <p class="form-hint mb-md">Where uploaded images are stored. Leave empty to keep files on this server.</p>
        <div class="account-row" id="setupS3Btn">
            <div class="account-row-left">
                <i class="fa-brands fa-aws icon-lg" style="color:#FF9900"></i>
                <div>
                    <div class="account-row-label">Amazon S3</div>
                    <div class="account-row-value" id="s3StatusDisplay">{if $settings.s3_bucket}Connected &mdash; {$settings.s3_bucket|escape}{else}Not connected (using local storage){/if}</div>
                </div>
            </div>
            {if $settings.s3_bucket}
            <span class="gateway-status-badge">
                <i class="fa-solid fa-check icon-xs"></i>
                Active
            </span>
            {else}
            <i class="fa-solid fa-chevron-right account-row-chevron"></i>
            {/if}
        </div>
    </div>
</div>

{* --- Preferences --- *}
<div class="dash-form" style="padding-top:0">
    <div class="form-section">
        <div class="form-section-title">Preferences</div>
        <div class="form-toggle-row">
            <div>
                <div class="form-toggle-label">Dark mode</div>
                <p class="form-hint" style="margin-top:2px">Use a darker look for the admin panel</p>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" id="darkModeToggle">
                <span class="toggle-slider"></span>
            </label>
        </div>
    </div>
</div>
{/block}

{block name="extra_scripts"}
<script>
function togglePw(btn) {ldelim}
    var input = btn.parentElement.querySelector('input');
    var isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    btn.querySelector('.eye-open').style.display = isPassword ? 'none' : '';
    btn.querySelector('.eye-closed').style.display = isPassword ? '' : 'none';
    btn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
{rdelim}

(function() {ldelim}
    // --- Dark mode toggle ---
    var $darkToggle = $('#darkModeToggle');
    $darkToggle.prop('checked', document.documentElement.getAttribute('data-theme') === 'dark');
    $darkToggle.on('change', function() {ldelim}
        var theme = this.checked ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
    {rdelim});

    var $form = $('#settingsForm');

    $form.on('submit', function(e) {ldelim}
        e.preventDefault();
        var $btn = $('#saveSettingsBtn').prop('disabled', true).text('Saving...');

        var data = {ldelim}{rdelim};
        $form.find('[data-key]').each(function() {ldelim}
            var key = $(this).data('key');
            if ($(this).is(':checkbox')) {ldelim}
                data[key] = $(this).is(':checked') ? '1' : '0';
            {rdelim} else {ldelim}
                data[key] = $(this).val();
            {rdelim}
        {rdelim});

        TinyShop.api('PUT', '/api/admin/settings', data).done(function() {ldelim}
            TinyShop.toast('Settings saved');
            $btn.prop('disabled', false).text('Save Changes');
        {rdelim}).fail(function(xhr) {ldelim}
            var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to save';
            TinyShop.toast(msg, 'error');
            $btn.prop('disabled', false).text('Save Changes');
        {rdelim});
    {rdelim});

    // Test email
    $('#testEmailBtn').on('click', function() {ldelim}
        var $btn = $(this).prop('disabled', true);
        var originalHtml = $btn.html();
        $btn.html('<span class="btn-spinner"></span> Sending...');

        TinyShop.api('POST', '/api/admin/test-email', {ldelim}{rdelim}).done(function(res) {ldelim}
            TinyShop.toast(res.message || 'Test email sent!', 'success');
        {rdelim}).fail(function(xhr) {ldelim}
            var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to send test email';
            TinyShop.toast(msg, 'error');
        {rdelim}).always(function() {ldelim}
            $btn.prop('disabled', false).html(originalHtml);
        {rdelim});
    {rdelim});

    // Copy sitemap URL
    $('#copySitemapBtn').on('click', function() {ldelim}
        var url = $('#sitemapUrl').val();
        if (navigator.clipboard) {ldelim}
            navigator.clipboard.writeText(url).then(function() {ldelim}
                TinyShop.toast('Sitemap URL copied');
            {rdelim});
        {rdelim} else {ldelim}
            $('#sitemapUrl').select();
            document.execCommand('copy');
            TinyShop.toast('Sitemap URL copied');
        {rdelim}
    {rdelim});

    // Ping search engines
    $('#pingSitemapBtn').on('click', function() {ldelim}
        var $btn = $(this).prop('disabled', true);
        var originalHtml = $btn.html();
        $btn.html('<span class="btn-spinner"></span> Pinging...');

        TinyShop.api('POST', '/api/admin/ping-sitemap', {ldelim}{rdelim}).done(function(res) {ldelim}
            TinyShop.toast(res.message || 'Search engines notified', 'success');
        {rdelim}).fail(function(xhr) {ldelim}
            var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Ping failed';
            TinyShop.toast(msg, 'error');
        {rdelim}).always(function() {ldelim}
            $btn.prop('disabled', false).html(originalHtml);
        {rdelim});
    {rdelim});

    // Site logo upload
    $('#siteLogoZone').on('click', function() {ldelim} $('#siteLogoInput').trigger('click'); {rdelim});
    $('#siteLogoInput').on('change', function() {ldelim}
        var file = this.files[0];
        if (!file) return;
        TinyShop.uploadFile(file, function(url) {ldelim}
            $('#siteLogoImg').attr('src', url);
            $('#siteLogoPreview').show();
            $('#siteLogoPlaceholder').hide();
            TinyShop.api('PUT', '/api/admin/settings', {ldelim} site_logo: url {rdelim}).done(function() {ldelim}
                TinyShop.toast('Logo saved');
            {rdelim});
        {rdelim});
        this.value = '';
    {rdelim});

    // Site favicon upload
    $('#siteFaviconZone').on('click', function() {ldelim} $('#siteFaviconInput').trigger('click'); {rdelim});
    $('#siteFaviconInput').on('change', function() {ldelim}
        var file = this.files[0];
        if (!file) return;
        TinyShop.uploadFile(file, function(url) {ldelim}
            $('#siteFaviconImg').attr('src', url);
            $('#siteFaviconPreview').show();
            $('#siteFaviconPlaceholder').hide();
            TinyShop.api('PUT', '/api/admin/settings', {ldelim} site_favicon: url {rdelim}).done(function() {ldelim}
                TinyShop.toast('Favicon saved');
            {rdelim});
        {rdelim});
        this.value = '';
    {rdelim});
    // --- Platform Stripe setup (modal) ---
    $('#setupPlatformStripeBtn').on('click', function() {ldelim}
        var currentPk = $('#platformStripePublicKey').val();
        var currentSk = $('#platformStripeSecretKey').val();
        var currentMode = $('#platformStripeMode').val();
        var isConnected = currentSk !== '';

        var html = '<form id="platformStripeForm" autocomplete="off">' +
            '<div class="gateway-modal-header">' +
                '<i class="fa-brands fa-stripe icon-lg" style="color:#635BFF"></i>' +
                '<span class="gateway-brand-name">Stripe</span>' +
                (isConnected ? '<span class="gateway-status-badge"><i class="fa-solid fa-check icon-xs"></i> Connected</span>' : '') +
            '</div>' +
            '<div class="form-group">' +
                '<label for="modalPlatformStripePk">Publishable Key</label>' +
                '<input type="text" class="form-control" id="modalPlatformStripePk" value="' + escapeHtml(currentPk) + '" placeholder="pk_test_..." autocomplete="off">' +
            '</div>' +
            '<div class="form-group">' +
                '<label for="modalPlatformStripeSk">Secret Key</label>' +
                '<div class="password-field"><input type="password" class="form-control" id="modalPlatformStripeSk" value="' + escapeHtml(currentSk) + '" placeholder="sk_test_..." autocomplete="off">' +
                '<button type="button" class="password-toggle" onclick="togglePw(this)" aria-label="Show password"><i class="fa-solid fa-eye eye-open"></i><i class="fa-solid fa-eye-slash eye-closed d-none"></i></button></div>' +
            '</div>' +
            '<div class="form-toggle-row mb-md">' +
                '<div>' +
                    '<div class="form-toggle-label">Live Mode</div>' +
                    '<p class="form-hint mt-xs">Use live keys for real payments</p>' +
                '</div>' +
                '<label class="toggle-switch">' +
                    '<input type="checkbox" id="modalPlatformStripeMode"' + (currentMode === 'live' ? ' checked' : '') + '>' +
                    '<span class="toggle-slider"></span>' +
                '</label>' +
            '</div>' +
            '<button type="submit" class="btn-block btn-primary" id="savePlatformStripeBtn">Save Stripe Settings</button>' +
            (isConnected ? '<button type="button" class="btn-block btn-link mt-sm" id="disconnectPlatformStripeBtn">Disconnect Stripe</button>' : '') +
        '</form>';
        TinyShop.openModal('Platform Stripe', html);

        $('#platformStripeForm').on('submit', function(e) {ldelim}
            e.preventDefault();
            var pk = $('#modalPlatformStripePk').val().trim();
            var sk = $('#modalPlatformStripeSk').val().trim();
            var mode = $('#modalPlatformStripeMode').is(':checked') ? 'live' : 'test';
            if (!pk || !sk) {ldelim}
                TinyShop.toast('Both keys are required', 'error');
                return;
            {rdelim}
            var $btn = $('#savePlatformStripeBtn').prop('disabled', true).text('Saving...');
            TinyShop.api('PUT', '/api/admin/settings', {ldelim}
                platform_stripe_public_key: pk,
                platform_stripe_secret_key: sk,
                platform_stripe_mode: mode
            {rdelim}).done(function() {ldelim}
                $('#platformStripePublicKey').val(pk);
                $('#platformStripeSecretKey').val(sk);
                $('#platformStripeMode').val(mode);
                $('#platformStripeStatusDisplay').text('Connected');
                TinyShop.toast('Stripe connected!');
                TinyShop.closeModal();
                setTimeout(function() {ldelim} location.reload(); {rdelim}, 400);
            {rdelim}).fail(function(xhr) {ldelim}
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to save';
                TinyShop.toast(msg, 'error');
                $btn.prop('disabled', false).text('Save Stripe Settings');
            {rdelim});
        {rdelim});

        $('#disconnectPlatformStripeBtn').on('click', function() {ldelim}
            TinyShop.confirm('Disconnect Stripe?', 'Sellers won\'t be able to pay for subscriptions with Stripe.', 'Disconnect', function() {ldelim}
                $('#confirmModalOk').prop('disabled', true).text('Disconnecting...');
                TinyShop.api('PUT', '/api/admin/settings', {ldelim}
                    platform_stripe_public_key: '',
                    platform_stripe_secret_key: '',
                    platform_stripe_mode: 'test'
                {rdelim}).done(function() {ldelim}
                    $('#platformStripePublicKey').val('');
                    $('#platformStripeSecretKey').val('');
                    $('#platformStripeMode').val('test');
                    $('#platformStripeStatusDisplay').text('Not connected');
                    TinyShop.toast('Stripe disconnected');
                    TinyShop.closeModal();
                    setTimeout(function() {ldelim} location.reload(); {rdelim}, 400);
                {rdelim}).fail(function() {ldelim}
                    TinyShop.toast('Failed to disconnect', 'error');
                    TinyShop.closeModal();
                {rdelim});
            {rdelim}, 'danger');
        {rdelim});
    {rdelim});

    // --- Platform PayPal setup (modal) ---
    $('#setupPlatformPaypalBtn').on('click', function() {ldelim}
        var currentCid = $('#platformPaypalClientId').val();
        var currentSecret = $('#platformPaypalSecret').val();
        var currentMode = $('#platformPaypalMode').val();
        var isConnected = currentCid !== '';

        var html = '<form id="platformPaypalForm" autocomplete="off">' +
            '<div class="gateway-modal-header">' +
                '<i class="fa-brands fa-paypal icon-lg" style="color:#003087"></i>' +
                '<span class="gateway-brand-name">PayPal</span>' +
                (isConnected ? '<span class="gateway-status-badge"><i class="fa-solid fa-check icon-xs"></i> Connected</span>' : '') +
            '</div>' +
            '<div class="form-group">' +
                '<label for="modalPlatformPaypalCid">Client ID</label>' +
                '<input type="text" class="form-control" id="modalPlatformPaypalCid" value="' + escapeHtml(currentCid) + '" placeholder="Your PayPal Client ID" autocomplete="off">' +
            '</div>' +
            '<div class="form-group">' +
                '<label for="modalPlatformPaypalSecret">Secret</label>' +
                '<div class="password-field"><input type="password" class="form-control" id="modalPlatformPaypalSecret" value="' + escapeHtml(currentSecret) + '" placeholder="Your PayPal Secret" autocomplete="off">' +
                '<button type="button" class="password-toggle" onclick="togglePw(this)" aria-label="Show password"><i class="fa-solid fa-eye eye-open"></i><i class="fa-solid fa-eye-slash eye-closed d-none"></i></button></div>' +
            '</div>' +
            '<div class="form-toggle-row mb-md">' +
                '<div>' +
                    '<div class="form-toggle-label">Live Mode</div>' +
                    '<p class="form-hint mt-xs">Use live keys for real payments</p>' +
                '</div>' +
                '<label class="toggle-switch">' +
                    '<input type="checkbox" id="modalPlatformPaypalMode"' + (currentMode === 'live' ? ' checked' : '') + '>' +
                    '<span class="toggle-slider"></span>' +
                '</label>' +
            '</div>' +
            '<button type="submit" class="btn-block btn-primary" id="savePlatformPaypalBtn">Save PayPal Settings</button>' +
            (isConnected ? '<button type="button" class="btn-block btn-link mt-sm" id="disconnectPlatformPaypalBtn">Disconnect PayPal</button>' : '') +
        '</form>';
        TinyShop.openModal('Platform PayPal', html);

        $('#platformPaypalForm').on('submit', function(e) {ldelim}
            e.preventDefault();
            var cid = $('#modalPlatformPaypalCid').val().trim();
            var secret = $('#modalPlatformPaypalSecret').val().trim();
            var mode = $('#modalPlatformPaypalMode').is(':checked') ? 'live' : 'test';
            if (!cid || !secret) {ldelim}
                TinyShop.toast('Both fields are required', 'error');
                return;
            {rdelim}
            var $btn = $('#savePlatformPaypalBtn').prop('disabled', true).text('Saving...');
            TinyShop.api('PUT', '/api/admin/settings', {ldelim}
                platform_paypal_client_id: cid,
                platform_paypal_secret: secret,
                platform_paypal_mode: mode
            {rdelim}).done(function() {ldelim}
                $('#platformPaypalClientId').val(cid);
                $('#platformPaypalSecret').val(secret);
                $('#platformPaypalMode').val(mode);
                $('#platformPaypalStatusDisplay').text('Connected');
                TinyShop.toast('PayPal connected!');
                TinyShop.closeModal();
                setTimeout(function() {ldelim} location.reload(); {rdelim}, 400);
            {rdelim}).fail(function(xhr) {ldelim}
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to save';
                TinyShop.toast(msg, 'error');
                $btn.prop('disabled', false).text('Save PayPal Settings');
            {rdelim});
        {rdelim});

        $('#disconnectPlatformPaypalBtn').on('click', function() {ldelim}
            TinyShop.confirm('Disconnect PayPal?', 'Sellers won\'t be able to pay for subscriptions with PayPal.', 'Disconnect', function() {ldelim}
                $('#confirmModalOk').prop('disabled', true).text('Disconnecting...');
                TinyShop.api('PUT', '/api/admin/settings', {ldelim}
                    platform_paypal_client_id: '',
                    platform_paypal_secret: '',
                    platform_paypal_mode: 'test'
                {rdelim}).done(function() {ldelim}
                    $('#platformPaypalClientId').val('');
                    $('#platformPaypalSecret').val('');
                    $('#platformPaypalMode').val('test');
                    $('#platformPaypalStatusDisplay').text('Not connected');
                    TinyShop.toast('PayPal disconnected');
                    TinyShop.closeModal();
                    setTimeout(function() {ldelim} location.reload(); {rdelim}, 400);
                {rdelim}).fail(function() {ldelim}
                    TinyShop.toast('Failed to disconnect', 'error');
                    TinyShop.closeModal();
                {rdelim});
            {rdelim}, 'danger');
        {rdelim});
    {rdelim});

    // --- Platform M-Pesa setup (modal) ---
    $('#setupPlatformMpesaBtn').on('click', function() {ldelim}
        var currentShortcode = $('#platformMpesaShortcode').val();
        var currentKey = $('#platformMpesaConsumerKey').val();
        var currentSecret = $('#platformMpesaConsumerSecret').val();
        var currentPasskey = $('#platformMpesaPasskey').val();
        var currentMode = $('#platformMpesaMode').val();
        var isConnected = currentShortcode !== '';

        var html = '<form id="platformMpesaForm" autocomplete="off">' +
            '<div class="gateway-modal-header">' +
                '<svg width="28" height="28" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="12" fill="#4CAF50"/><text x="12" y="16" text-anchor="middle" font-size="10" font-weight="bold" fill="white">M</text></svg>' +
                '<span class="gateway-brand-name">M-Pesa</span>' +
                (isConnected ? '<span class="gateway-status-badge"><i class="fa-solid fa-check icon-xs"></i> Connected</span>' : '') +
            '</div>' +
            '<div class="form-group">' +
                '<label for="modalPlatformMpesaShortcode">Shortcode (Till / Paybill)</label>' +
                '<input type="text" class="form-control" id="modalPlatformMpesaShortcode" value="' + escapeHtml(currentShortcode) + '" placeholder="e.g. 174379" autocomplete="off" inputmode="numeric">' +
            '</div>' +
            '<div class="form-group">' +
                '<label for="modalPlatformMpesaKey">Consumer Key</label>' +
                '<input type="text" class="form-control" id="modalPlatformMpesaKey" value="' + escapeHtml(currentKey) + '" placeholder="From Daraja portal" autocomplete="off">' +
            '</div>' +
            '<div class="form-group">' +
                '<label for="modalPlatformMpesaSecret">Consumer Secret</label>' +
                '<div class="password-field"><input type="password" class="form-control" id="modalPlatformMpesaSecret" value="' + escapeHtml(currentSecret) + '" placeholder="From Daraja portal" autocomplete="off">' +
                '<button type="button" class="password-toggle" onclick="togglePw(this)" aria-label="Show password"><i class="fa-solid fa-eye eye-open"></i><i class="fa-solid fa-eye-slash eye-closed d-none"></i></button></div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label for="modalPlatformMpesaPasskey">Passkey</label>' +
                '<div class="password-field"><input type="password" class="form-control" id="modalPlatformMpesaPasskey" value="' + escapeHtml(currentPasskey) + '" placeholder="STK Push passkey" autocomplete="off">' +
                '<button type="button" class="password-toggle" onclick="togglePw(this)" aria-label="Show password"><i class="fa-solid fa-eye eye-open"></i><i class="fa-solid fa-eye-slash eye-closed d-none"></i></button></div>' +
            '</div>' +
            '<div class="form-toggle-row mb-md">' +
                '<div>' +
                    '<div class="form-toggle-label">Live Mode</div>' +
                    '<p class="form-hint mt-xs">Use live credentials for real payments</p>' +
                '</div>' +
                '<label class="toggle-switch">' +
                    '<input type="checkbox" id="modalPlatformMpesaMode"' + (currentMode === 'live' ? ' checked' : '') + '>' +
                    '<span class="toggle-slider"></span>' +
                '</label>' +
            '</div>' +
            '<button type="submit" class="btn-block btn-primary" id="savePlatformMpesaBtn">Save M-Pesa Settings</button>' +
            (isConnected ? '<button type="button" class="btn-block btn-link mt-sm" id="disconnectPlatformMpesaBtn">Disconnect M-Pesa</button>' : '') +
        '</form>';
        TinyShop.openModal('Platform M-Pesa', html);

        $('#platformMpesaForm').on('submit', function(e) {ldelim}
            e.preventDefault();
            var shortcode = $('#modalPlatformMpesaShortcode').val().trim();
            var key = $('#modalPlatformMpesaKey').val().trim();
            var secret = $('#modalPlatformMpesaSecret').val().trim();
            var passkey = $('#modalPlatformMpesaPasskey').val().trim();
            var mode = $('#modalPlatformMpesaMode').is(':checked') ? 'live' : 'test';
            if (!shortcode || !key || !secret || !passkey) {ldelim}
                TinyShop.toast('All fields are required', 'error');
                return;
            {rdelim}
            var $btn = $('#savePlatformMpesaBtn').prop('disabled', true).text('Saving...');
            TinyShop.api('PUT', '/api/admin/settings', {ldelim}
                platform_mpesa_shortcode: shortcode,
                platform_mpesa_consumer_key: key,
                platform_mpesa_consumer_secret: secret,
                platform_mpesa_passkey: passkey,
                platform_mpesa_mode: mode
            {rdelim}).done(function() {ldelim}
                $('#platformMpesaShortcode').val(shortcode);
                $('#platformMpesaConsumerKey').val(key);
                $('#platformMpesaConsumerSecret').val(secret);
                $('#platformMpesaPasskey').val(passkey);
                $('#platformMpesaMode').val(mode);
                $('#platformMpesaStatusDisplay').text('Connected');
                TinyShop.toast('M-Pesa connected!');
                TinyShop.closeModal();
                setTimeout(function() {ldelim} location.reload(); {rdelim}, 400);
            {rdelim}).fail(function(xhr) {ldelim}
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to save';
                TinyShop.toast(msg, 'error');
                $btn.prop('disabled', false).text('Save M-Pesa Settings');
            {rdelim});
        {rdelim});

        $('#disconnectPlatformMpesaBtn').on('click', function() {ldelim}
            TinyShop.confirm('Disconnect M-Pesa?', 'Sellers won\'t be able to pay for subscriptions with M-Pesa.', 'Disconnect', function() {ldelim}
                $('#confirmModalOk').prop('disabled', true).text('Disconnecting...');
                TinyShop.api('PUT', '/api/admin/settings', {ldelim}
                    platform_mpesa_shortcode: '',
                    platform_mpesa_consumer_key: '',
                    platform_mpesa_consumer_secret: '',
                    platform_mpesa_passkey: '',
                    platform_mpesa_mode: 'test'
                {rdelim}).done(function() {ldelim}
                    $('#platformMpesaShortcode').val('');
                    $('#platformMpesaConsumerKey').val('');
                    $('#platformMpesaConsumerSecret').val('');
                    $('#platformMpesaPasskey').val('');
                    $('#platformMpesaMode').val('test');
                    $('#platformMpesaStatusDisplay').text('Not connected');
                    TinyShop.toast('M-Pesa disconnected');
                    TinyShop.closeModal();
                    setTimeout(function() {ldelim} location.reload(); {rdelim}, 400);
                {rdelim}).fail(function() {ldelim}
                    TinyShop.toast('Failed to disconnect', 'error');
                    TinyShop.closeModal();
                {rdelim});
            {rdelim}, 'danger');
        {rdelim});
    {rdelim});

    // --- S3 File Storage setup (modal) ---
    $('#setupS3Btn').on('click', function() {ldelim}
        var currentBucket = $('#s3Bucket').val();
        var currentRegion = $('#s3Region').val() || 'us-east-1';
        var currentAccessKey = $('#s3AccessKey').val();
        var currentSecretKey = $('#s3SecretKey').val();
        var currentEndpoint = $('#s3Endpoint').val();
        var currentCdnUrl = $('#s3CdnUrl').val();
        var isConnected = currentBucket !== '';

        var regions = [
            ['us-east-1', 'US East (N. Virginia)'],
            ['us-east-2', 'US East (Ohio)'],
            ['us-west-1', 'US West (N. California)'],
            ['us-west-2', 'US West (Oregon)'],
            ['af-south-1', 'Africa (Cape Town)'],
            ['ap-south-1', 'Asia Pacific (Mumbai)'],
            ['ap-southeast-1', 'Asia Pacific (Singapore)'],
            ['ap-northeast-1', 'Asia Pacific (Tokyo)'],
            ['eu-west-1', 'Europe (Ireland)'],
            ['eu-west-2', 'Europe (London)'],
            ['eu-central-1', 'Europe (Frankfurt)'],
            ['me-south-1', 'Middle East (Bahrain)'],
            ['sa-east-1', 'South America (S\u00e3o Paulo)']
        ];
        var regionOpts = '';
        for (var i = 0; i < regions.length; i++) {ldelim}
            regionOpts += '<option value="' + regions[i][0] + '"' + (currentRegion === regions[i][0] ? ' selected' : '') + '>' + regions[i][1] + '</option>';
        {rdelim}

        var html = '<form id="s3Form" autocomplete="off">' +
            '<div class="gateway-modal-header">' +
                '<i class="fa-brands fa-aws icon-lg" style="color:#FF9900"></i>' +
                '<span class="gateway-brand-name">Amazon S3</span>' +
                (isConnected ? '<span class="gateway-status-badge"><i class="fa-solid fa-check icon-xs"></i> Connected</span>' : '') +
            '</div>' +
            '<div class="form-group">' +
                '<label for="modalS3Bucket">Bucket Name</label>' +
                '<input type="text" class="form-control" id="modalS3Bucket" value="' + escapeHtml(currentBucket) + '" placeholder="e.g. my-shop-uploads" autocomplete="off">' +
            '</div>' +
            '<div class="form-group">' +
                '<label for="modalS3Region">Region</label>' +
                '<select class="form-control" id="modalS3Region">' + regionOpts + '</select>' +
            '</div>' +
            '<div class="form-group">' +
                '<label for="modalS3AccessKey">Access Key</label>' +
                '<input type="text" class="form-control" id="modalS3AccessKey" value="' + escapeHtml(currentAccessKey) + '" placeholder="AKIA..." autocomplete="off">' +
            '</div>' +
            '<div class="form-group">' +
                '<label for="modalS3SecretKey">Secret Key</label>' +
                '<div class="password-field"><input type="password" class="form-control" id="modalS3SecretKey" value="' + escapeHtml(currentSecretKey) + '" placeholder="Your secret key" autocomplete="off">' +
                '<button type="button" class="password-toggle" onclick="togglePw(this)" aria-label="Show password"><i class="fa-solid fa-eye eye-open"></i><i class="fa-solid fa-eye-slash eye-closed d-none"></i></button></div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label for="modalS3Endpoint">Custom Endpoint <span style="color:var(--color-text-muted);font-weight:400">(optional)</span></label>' +
                '<input type="text" class="form-control" id="modalS3Endpoint" value="' + escapeHtml(currentEndpoint) + '" placeholder="e.g. https://nyc3.digitaloceanspaces.com" autocomplete="off">' +
                '<p class="form-hint">For DigitalOcean Spaces, MinIO, or other S3-compatible services</p>' +
            '</div>' +
            '<div class="form-group">' +
                '<label for="modalS3CdnUrl">CDN URL <span style="color:var(--color-text-muted);font-weight:400">(optional)</span></label>' +
                '<input type="text" class="form-control" id="modalS3CdnUrl" value="' + escapeHtml(currentCdnUrl) + '" placeholder="e.g. https://cdn.example.com" autocomplete="off">' +
                '<p class="form-hint">If you use CloudFront or another CDN in front of your bucket</p>' +
            '</div>' +
            '<button type="submit" class="btn-block btn-primary" id="saveS3Btn">Save S3 Settings</button>' +
            '<button type="button" class="btn-block btn-test-email mt-sm" id="testS3Btn"><i class="fa-solid fa-plug icon-sm"></i> Test Connection</button>' +
            (isConnected ? '<button type="button" class="btn-block btn-link mt-sm" id="disconnectS3Btn">Disconnect S3</button>' : '') +
        '</form>';
        TinyShop.openModal('File Storage', html);

        // Save S3 settings
        $('#s3Form').on('submit', function(e) {ldelim}
            e.preventDefault();
            var bucket = $('#modalS3Bucket').val().trim();
            var region = $('#modalS3Region').val();
            var accessKey = $('#modalS3AccessKey').val().trim();
            var secretKey = $('#modalS3SecretKey').val().trim();
            var endpoint = $('#modalS3Endpoint').val().trim();
            var cdnUrl = $('#modalS3CdnUrl').val().trim();
            if (!bucket || !accessKey || !secretKey) {ldelim}
                TinyShop.toast('Bucket name, access key, and secret key are required', 'error');
                return;
            {rdelim}
            var $btn = $('#saveS3Btn').prop('disabled', true).text('Saving...');
            TinyShop.api('PUT', '/api/admin/settings', {ldelim}
                s3_bucket: bucket,
                s3_region: region,
                s3_access_key: accessKey,
                s3_secret_key: secretKey,
                s3_endpoint: endpoint,
                s3_cdn_url: cdnUrl
            {rdelim}).done(function() {ldelim}
                $('#s3Bucket').val(bucket);
                $('#s3Region').val(region);
                $('#s3AccessKey').val(accessKey);
                $('#s3SecretKey').val(secretKey);
                $('#s3Endpoint').val(endpoint);
                $('#s3CdnUrl').val(cdnUrl);
                $('#s3StatusDisplay').text('Connected \u2014 ' + bucket);
                TinyShop.toast('S3 settings saved!');
                TinyShop.closeModal();
                setTimeout(function() {ldelim} location.reload(); {rdelim}, 400);
            {rdelim}).fail(function(xhr) {ldelim}
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to save';
                TinyShop.toast(msg, 'error');
                $btn.prop('disabled', false).text('Save S3 Settings');
            {rdelim});
        {rdelim});

        // Test S3 connection
        $('#testS3Btn').on('click', function() {ldelim}
            var $btn = $(this).prop('disabled', true);
            var originalHtml = $btn.html();
            $btn.html('<span class="btn-spinner"></span> Testing...');
            TinyShop.api('POST', '/api/admin/test-s3', {ldelim}{rdelim}).done(function(res) {ldelim}
                TinyShop.toast(res.message || 'Connection successful!', 'success');
            {rdelim}).fail(function(xhr) {ldelim}
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Connection failed';
                TinyShop.toast(msg, 'error');
            {rdelim}).always(function() {ldelim}
                $btn.prop('disabled', false).html(originalHtml);
            {rdelim});
        {rdelim});

        // Disconnect S3
        $('#disconnectS3Btn').on('click', function() {ldelim}
            TinyShop.confirm('Disconnect S3?', 'New uploads will be saved on this server. Existing S3 files will remain in your bucket.', 'Disconnect', function() {ldelim}
                $('#confirmModalOk').prop('disabled', true).text('Disconnecting...');
                TinyShop.api('PUT', '/api/admin/settings', {ldelim}
                    s3_bucket: '',
                    s3_region: 'us-east-1',
                    s3_access_key: '',
                    s3_secret_key: '',
                    s3_endpoint: '',
                    s3_cdn_url: ''
                {rdelim}).done(function() {ldelim}
                    $('#s3Bucket').val('');
                    $('#s3Region').val('us-east-1');
                    $('#s3AccessKey').val('');
                    $('#s3SecretKey').val('');
                    $('#s3Endpoint').val('');
                    $('#s3CdnUrl').val('');
                    $('#s3StatusDisplay').text('Not connected (using local storage)');
                    TinyShop.toast('S3 disconnected');
                    TinyShop.closeModal();
                    setTimeout(function() {ldelim} location.reload(); {rdelim}, 400);
                {rdelim}).fail(function() {ldelim}
                    TinyShop.toast('Failed to disconnect', 'error');
                    TinyShop.closeModal();
                {rdelim});
            {rdelim}, 'danger');
        {rdelim});
    {rdelim});
{rdelim})();
</script>
{/block}
