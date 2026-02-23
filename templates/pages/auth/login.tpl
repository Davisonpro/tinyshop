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
                <i class="fa-solid fa-eye eye-open"></i>
                <i class="fa-solid fa-eye-slash eye-closed" style="display:none"></i>
            </button>
        </div>
    </div>
    <div class="form-row-between">
        <a href="/forgot-password" class="auth-link-muted">Forgot password?</a>
    </div>
    <button type="submit" class="btn btn-primary" id="loginBtn">Sign In</button>
</form>

{if $oauth_google || $oauth_instagram || $oauth_tiktok}
<div class="auth-divider"><span>or continue with</span></div>

<div class="social-logins social-logins-row">
    {if $oauth_google}<a href="/auth/google" class="btn-social-icon" title="Google" aria-label="Sign in with Google">
        <i class="fa-brands fa-google" style="font-size:20px"></i>
    </a>{/if}
    {if $oauth_instagram}<a href="/auth/instagram" class="btn-social-icon" title="Instagram" aria-label="Sign in with Instagram">
        <i class="fa-brands fa-instagram" style="font-size:20px"></i>
    </a>{/if}
    {if $oauth_tiktok}<a href="/auth/tiktok" class="btn-social-icon" title="TikTok" aria-label="Sign in with TikTok">
        <i class="fa-brands fa-tiktok" style="font-size:20px"></i>
    </a>{/if}
</div>
{/if}

{if $allow_registration}
<div class="auth-footer">
    Don't have an account? <a href="/register">Create one</a>
</div>
{/if}
{/block}

{block name="page_scripts"}
<script src="/public/js/auth{$min}.js?v={$asset_v}"></script>
{/block}
