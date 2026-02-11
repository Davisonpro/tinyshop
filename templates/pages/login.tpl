{extends file="layouts/auth.tpl"}

{block name="content"}
<h1>Welcome back</h1>
<p class="auth-sub">Sign in to your shop</p>

<form id="loginForm" novalidate>
    <div class="form-group">
        <input type="email" class="form-control" id="email" name="email" placeholder="Email address" required autofocus aria-label="Email address" autocomplete="email">
    </div>
    <div class="form-group">
        <div class="password-field">
            <input type="password" class="form-control" id="password" name="password" placeholder="Password" required aria-label="Password" autocomplete="current-password">
            <button type="button" class="password-toggle" id="togglePassword" aria-label="Show password">
                <svg class="eye-open" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg class="eye-closed" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
        </div>
    </div>
    <div class="form-row-between">
        <a href="/forgot-password" class="auth-link-muted">Forgot password?</a>
    </div>
    <button type="submit" class="btn btn-primary" id="loginBtn">Sign In</button>
</form>

<div class="auth-divider"><span>or continue with</span></div>

<div class="social-logins social-logins-row">
    <a href="/auth/google" class="btn-social-icon" title="Google" aria-label="Sign in with Google">
        <svg width="20" height="20" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
    </a>
    <a href="/auth/instagram" class="btn-social-icon" title="Instagram" aria-label="Sign in with Instagram">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/></svg>
    </a>
    <a href="/auth/tiktok" class="btn-social-icon" title="TikTok" aria-label="Sign in with TikTok">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1 0-5.78 2.92 2.92 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 3 15.57 6.33 6.33 0 0 0 9.37 22a6.33 6.33 0 0 0 6.37-6.22V9.4a8.16 8.16 0 0 0 4.85 1.58V7.53a4.78 4.78 0 0 1-1-.84z"/></svg>
    </a>
</div>

{if $allow_registration}
<div class="auth-footer">
    Don't have an account? <a href="/register">Create one</a>
</div>
{/if}
{/block}

{block name="page_scripts"}
<script>
$(function() {
    $('#togglePassword').on('click', function() {
        var $input = $('#password');
        var isPassword = $input.attr('type') === 'password';
        $input.attr('type', isPassword ? 'text' : 'password');
        $(this).find('.eye-open').toggle(!isPassword);
        $(this).find('.eye-closed').toggle(isPassword);
        $(this).attr('aria-label', isPassword ? 'Hide password' : 'Show password');
    });

    $('#loginForm').on('submit', function(e) {
        e.preventDefault();
        var $btn = $('#loginBtn').prop('disabled', true).text('Signing in...');
        $.ajax({
            url: '/api/auth/login',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                email: $('#email').val(),
                password: $('#password').val()
            }),
            success: function(res) {
                if (res.success) window.location.href = res.redirect || '/dashboard';
            },
            error: function(xhr) {
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Something went wrong';
                TinyShop.toast(msg, 'error');
                $btn.prop('disabled', false).text('Sign In');
            }
        });
    });
});
</script>
{/block}
