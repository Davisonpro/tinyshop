{extends file="layouts/auth.tpl"}

{block name="content"}
<h1>Start selling</h1>
<p class="auth-sub">Create your shop in seconds</p>

<form id="registerForm" novalidate>
    <div class="form-group">
        <input type="text" class="form-control" id="storeName" name="store_name" placeholder="Shop name" required autofocus autocomplete="off" aria-label="Shop name">
    </div>
    <div class="form-group">
        <input type="email" class="form-control" id="email" name="email" placeholder="Email address" required autocomplete="email" aria-label="Email address">
    </div>
    <div class="form-group">
        <div class="password-field">
            <input type="password" class="form-control" id="password" name="password" placeholder="Create a password" required autocomplete="new-password" aria-label="Password" aria-describedby="strengthLabel">
            <button type="button" class="password-toggle" id="togglePassword" aria-label="Show password">
                <i class="fa-solid fa-eye eye-open"></i>
                <i class="fa-solid fa-eye-slash eye-closed" style="display:none"></i>
            </button>
        </div>
        <div class="password-strength" id="passwordStrength" style="display:none">
            <div class="password-strength-bar"><span id="strengthFill"></span></div>
            <span class="password-strength-label" id="strengthLabel"></span>
        </div>
    </div>
    <button type="submit" class="btn btn-primary" id="registerBtn">Create My Shop</button>
</form>

{if $oauth_google || $oauth_instagram || $oauth_tiktok}
<div class="auth-divider"><span>or continue with</span></div>

<div class="social-logins social-logins-row">
    {if $oauth_google}<a href="/auth/google" class="btn-social-icon" title="Google" aria-label="Sign up with Google">
        <i class="fa-brands fa-google" style="font-size:20px"></i>
    </a>{/if}
    {if $oauth_instagram}<a href="/auth/instagram" class="btn-social-icon" title="Instagram" aria-label="Sign up with Instagram">
        <i class="fa-brands fa-instagram" style="font-size:20px"></i>
    </a>{/if}
    {if $oauth_tiktok}<a href="/auth/tiktok" class="btn-social-icon" title="TikTok" aria-label="Sign up with TikTok">
        <i class="fa-brands fa-tiktok" style="font-size:20px"></i>
    </a>{/if}
</div>
{/if}

<div class="auth-footer">
    Already have an account? <a href="/login">Sign in</a>
</div>
{/block}

{block name="page_scripts"}
<script src="/public/js/auth{$min}.js?v={$asset_v}"></script>
{/block}
