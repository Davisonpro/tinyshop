{extends file="layouts/admin.tpl"}

{block name="content"}
<div class="dash-topbar">
    <span class="dash-topbar-title">Settings</span>
</div>

<form id="settingsForm" class="dash-form" autocomplete="off">

    {* --- Platform --- *}
    <div class="form-section">
        <div class="form-section-title">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
            Platform
        </div>
        <div class="form-group">
            <label for="siteName">Platform Name</label>
            <input type="text" class="form-control" id="siteName" value="{$settings.site_name|escape}" data-key="site_name" autocomplete="off">
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
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            Email (SMTP)
        </div>
        <div class="form-group">
            <label for="mailFromName">From Name</label>
            <input type="text" class="form-control" id="mailFromName" value="{$settings.mail_from_name|escape}" data-key="mail_from_name" placeholder="TinyShop" autocomplete="off">
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
                    <svg class="eye-open" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg class="eye-closed" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                </button>
            </div>
            <p class="form-hint">For Gmail, use an App Password (not your regular password)</p>
        </div>
        <div class="form-group">
            <button type="button" class="btn-test-email" id="testEmailBtn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M22 2L11 13"/><path d="M22 2L15 22 11 13 2 9z"/></svg>
                Send Test Email
            </button>
            <p class="form-hint">Sends a test email to your support email address</p>
        </div>
    </div>

    {* --- Limits --- *}
    <div class="form-section">
        <div class="form-section-title">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20v-4"/></svg>
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
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
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
                    <span class="toggle-track"></span>
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
                    <span class="toggle-track"></span>
                </label>
            </div>
        </div>
    </div>

    <button type="submit" class="btn-primary" id="saveSettingsBtn">Save Changes</button>
</form>
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
{rdelim})();
</script>
{/block}
