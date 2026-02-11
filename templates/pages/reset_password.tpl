{extends file="layouts/auth.tpl"}

{block name="content"}
<h1>Reset your password</h1>
<p class="auth-sub">Choose a new password for your account</p>

<form id="resetForm" novalidate>
    <input type="hidden" id="token" name="token" value="{$token|escape:'html'}">

    <div class="form-group">
        <div class="password-field">
            <input type="password" class="form-control" id="password" name="password" placeholder="New password" required autofocus aria-label="New password" autocomplete="new-password">
            <button type="button" class="password-toggle" id="togglePassword" aria-label="Show password">
                <svg class="eye-open" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg class="eye-closed" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
        </div>
    </div>

    <div class="form-group">
        <div class="password-field">
            <input type="password" class="form-control" id="passwordConfirm" name="password_confirm" placeholder="Confirm new password" required aria-label="Confirm new password" autocomplete="new-password">
            <button type="button" class="password-toggle" id="togglePasswordConfirm" aria-label="Show password">
                <svg class="eye-open" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg class="eye-closed" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
        </div>
    </div>

    <button type="submit" class="btn btn-primary" id="resetBtn">Reset Password</button>
</form>

<div class="auth-footer">
    Remember your password? <a href="/login">Sign in</a>
</div>
{/block}

{block name="page_scripts"}
<script>
$(function() {ldelim}
    function bindToggle(toggleId, inputId) {ldelim}
        $('#' + toggleId).on('click', function() {ldelim}
            var $input = $('#' + inputId);
            var isPassword = $input.attr('type') === 'password';
            $input.attr('type', isPassword ? 'text' : 'password');
            $(this).find('.eye-open').toggle(!isPassword);
            $(this).find('.eye-closed').toggle(isPassword);
            $(this).attr('aria-label', isPassword ? 'Hide password' : 'Show password');
        {rdelim});
    {rdelim}

    bindToggle('togglePassword', 'password');
    bindToggle('togglePasswordConfirm', 'passwordConfirm');

    $('#resetForm').on('submit', function(e) {ldelim}
        e.preventDefault();

        var password = $('#password').val();
        var confirm  = $('#passwordConfirm').val();
        var token    = $('#token').val();

        if (!password || password.length < 6) {ldelim}
            TinyShop.toast('Password must be at least 6 characters', 'error');
            return;
        {rdelim}

        if (password !== confirm) {ldelim}
            TinyShop.toast('Passwords do not match', 'error');
            return;
        {rdelim}

        var $btn = $('#resetBtn').prop('disabled', true).text('Resetting...');

        $.ajax({ldelim}
            url: '/api/auth/reset-password',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ldelim}
                token: token,
                password: password,
                password_confirm: confirm
            {rdelim}),
            success: function(res) {ldelim}
                if (res.success) {ldelim}
                    TinyShop.toast('Password reset successfully! Redirecting...', 'success');
                    setTimeout(function() {ldelim}
                        window.location.href = '/login';
                    {rdelim}, 1500);
                {rdelim}
            {rdelim},
            error: function(xhr) {ldelim}
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Something went wrong';
                TinyShop.toast(msg, 'error');
                $btn.prop('disabled', false).text('Reset Password');
            {rdelim}
        {rdelim});
    {rdelim});
{rdelim});
</script>
{/block}
