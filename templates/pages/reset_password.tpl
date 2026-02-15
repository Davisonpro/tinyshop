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
                <i class="fa-solid fa-eye eye-open"></i>
                <i class="fa-solid fa-eye-slash eye-closed" style="display:none"></i>
            </button>
        </div>
    </div>

    <div class="form-group">
        <div class="password-field">
            <input type="password" class="form-control" id="passwordConfirm" name="password_confirm" placeholder="Confirm new password" required aria-label="Confirm new password" autocomplete="new-password">
            <button type="button" class="password-toggle" id="togglePasswordConfirm" aria-label="Show password">
                <i class="fa-solid fa-eye eye-open"></i>
                <i class="fa-solid fa-eye-slash eye-closed" style="display:none"></i>
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
<script src="/public/js/auth.js?v={$asset_v}"></script>
{/block}
