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
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary, #6c5ce7)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
            <polyline points="22 4 12 14.01 9 11.01"/>
        </svg>
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
<script>
$(function() {ldelim}
    $('#forgotForm').on('submit', function(e) {ldelim}
        e.preventDefault();

        var email = $('#email').val().trim();
        if (!email) {ldelim}
            TinyShop.toast('Please enter your email address', 'error');
            return;
        {rdelim}

        var $btn = $('#forgotBtn').prop('disabled', true).text('Sending...');

        $.ajax({ldelim}
            url: '/api/auth/forgot-password',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ldelim} email: email {rdelim}),
            success: function(res) {ldelim}
                if (res.success) {ldelim}
                    $('#sentEmail').text(email);
                    $('#forgotForm').hide();
                    $('#successMessage').show();
                {rdelim}
            {rdelim},
            error: function(xhr) {ldelim}
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Something went wrong';
                TinyShop.toast(msg, 'error');
                $btn.prop('disabled', false).text('Send Reset Link');
            {rdelim}
        {rdelim});
    {rdelim});
{rdelim});
</script>
{/block}
