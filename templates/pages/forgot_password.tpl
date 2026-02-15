{extends file="layouts/auth.tpl"}

{block name="content"}
<h1>Forgot password?</h1>
<p class="auth-sub">Enter your email and we'll send you a reset link</p>

<form id="forgotForm" novalidate>
    <div class="form-group">
        <input type="email" class="form-control" id="email" name="email" placeholder="Email address" required autofocus aria-label="Email address" autocomplete="email">
    </div>
    <button type="submit" class="btn btn-primary" id="forgotBtn">Send Reset Link</button>
</form>

<div id="successMessage" style="display:none;">
    <div class="auth-success">
        <i class="fa-solid fa-circle-check" style="font-size:48px;color:var(--color-primary, #6c5ce7)"></i>
        <h2>Check your email</h2>
        <p>If an account exists for <strong id="sentEmail"></strong>, we've sent a password reset link. It may take a minute to arrive.</p>
        <p class="auth-text-muted">Don't see it? Check your spam folder.</p>
    </div>
</div>

<div class="auth-footer">
    Remember your password? <a href="/login">Sign in</a>
</div>
{/block}

{block name="page_scripts"}
<script src="/public/js/auth.js?v={$asset_v}"></script>
{/block}
