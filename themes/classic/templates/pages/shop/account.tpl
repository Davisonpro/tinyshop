{extends file="layouts/base.tpl"}

{block name="body_class"}page-shop page-account{/block}

{block name="body"}

{include file="partials/shop/palette_vars.tpl" palette_scope="page-account"}

{include file="partials/shop/announcement_bar.tpl"}
{include file="partials/shop/desktop_header.tpl"}
{include file="partials/shop/mobile_header.tpl"}

<main class="shop-content" data-shop-id="{$shop.id|escape}">

{if $customer_logged_in && $customer}

    <div class="account-header">
        <div class="account-greeting">
            <h1 class="account-greeting-name">Hi, {$customer.name|escape}</h1>
            <button type="button" class="account-logout-btn" id="logoutBtn">Log out</button>
        </div>
    </div>

    <section class="account-section">
        <div class="account-section-header">
            <h2 class="account-section-title">Profile</h2>
            <button type="button" class="account-edit-toggle" id="profileEditToggle">Edit</button>
        </div>

        {* Read-only profile display *}
        <div class="account-profile-view" id="profileView">
            <div class="account-profile-row">
                <span class="account-profile-label">Name</span>
                <span class="account-profile-value" id="viewName">{$customer.name|escape}</span>
            </div>
            <div class="account-profile-row">
                <span class="account-profile-label">Email</span>
                <span class="account-profile-value" id="viewEmail">{$customer.email|escape}</span>
            </div>
            {if !empty($customer.phone)}
            <div class="account-profile-row">
                <span class="account-profile-label">Phone</span>
                <span class="account-profile-value" id="viewPhone">{$customer.phone|escape}</span>
            </div>
            {/if}
        </div>

        {* Edit form — hidden by default *}
        <form class="account-profile-form" id="profileForm" style="display:none">
            <div class="account-field">
                <label for="profileName">Name</label>
                <input type="text" id="profileName" name="name" value="{$customer.name|escape}" required>
            </div>
            <div class="account-field">
                <label for="profileEmail">Email</label>
                <input type="email" id="profileEmail" name="email" value="{$customer.email|escape}" required>
            </div>
            <div class="account-field">
                <label for="profilePhone">Phone</label>
                <input type="tel" id="profilePhone" name="phone" value="{$customer.phone|escape}" placeholder="Optional">
            </div>
            <div class="account-form-actions">
                <button type="button" class="account-cancel-btn" id="profileCancel">Cancel</button>
                <button type="submit" class="account-submit" id="profileSubmit">Save</button>
            </div>
        </form>
    </section>

    <section class="account-section">
        <h2 class="account-section-title">Your orders</h2>
        {if !empty($orders)}
        <div class="account-orders">
            {foreach $orders as $order}
            <div class="account-order-card">
                <div class="account-order-header">
                    <span class="account-order-number">#{$order.order_number|escape}</span>
                    <span class="account-order-status account-order-status--{$order.status|escape}">
                        {if $order.status === 'paid'}Completed{elseif $order.status === 'cancelled'}Cancelled{elseif $order.status === 'refunded'}Refunded{else}Processing{/if}
                    </span>
                </div>
                <div class="account-order-date">
                    {$order.created_at|date_format:"%B %e, %Y"}
                </div>
                {if !empty($order.items)}
                <div class="account-order-items">
                    {foreach $order.items as $item}
                    <div class="account-order-item">
                        <img src="{$item.product_image|default:'/public/img/placeholder.svg'}" alt="" class="account-order-item-img" loading="lazy">
                        <div class="account-order-item-info">
                            <span class="account-order-item-name">{$item.product_name|escape}</span>
                            <span class="account-order-item-meta">{if $item.variation}{$item.variation|escape} &middot; {/if}Qty: {$item.quantity}</span>
                        </div>
                    </div>
                    {/foreach}
                </div>
                {/if}
                <div class="account-order-total">
                    {$currency_symbol|escape}{$order.amount|number_format:2}
                </div>
            </div>
            {/foreach}
        </div>
        {else}
        <div class="account-empty">
            <p>No orders yet</p>
        </div>
        {/if}
    </section>

{else}

    <div class="account-auth-wrapper">
        <div class="account-auth-card">
            <h1 class="account-auth-title" id="authTitle">{if !empty($reset_token)}Reset password{else}Welcome back{/if}</h1>
            <p class="account-auth-sub" id="authSub">{if !empty($reset_token)}Choose a new password{else}Sign in to your account{/if}</p>

            {* --- Reset password view (from email link) --- *}
            <div class="account-tab-content" id="tab-reset" style="{if empty($reset_token)}display:none{/if}">
                <form class="account-form" id="resetForm" novalidate>
                    <input type="hidden" id="resetToken" value="{$reset_token|escape}">
                    <div class="account-field">
                        <div class="account-password-field">
                            <input type="password" id="resetPassword" name="password" placeholder="New password (8+ characters)" required autocomplete="new-password">
                            <button type="button" class="account-password-toggle" aria-label="Show password">
                                <i class="fa-solid fa-eye eye-open"></i>
                                <i class="fa-solid fa-eye-slash eye-closed" style="display:none"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="account-submit" id="resetSubmit">Reset Password</button>
                </form>
                <div class="account-auth-footer">
                    <button type="button" class="account-auth-link" data-switch="login">Back to sign in</button>
                </div>
            </div>

            {* --- Login view --- *}
            <div class="account-tab-content" id="tab-login" style="{if !empty($reset_token)}display:none{/if}">
                <form class="account-form" id="loginForm" novalidate>
                    <div class="account-field">
                        <input type="email" id="loginEmail" name="email" placeholder="Email address" required autocomplete="email">
                    </div>
                    <div class="account-field">
                        <div class="account-password-field">
                            <input type="password" id="loginPassword" name="password" placeholder="Password" required autocomplete="current-password">
                            <button type="button" class="account-password-toggle" aria-label="Show password">
                                <i class="fa-solid fa-eye eye-open"></i>
                                <i class="fa-solid fa-eye-slash eye-closed" style="display:none"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="account-submit" id="loginSubmit">Sign In</button>
                </form>
                <div class="account-auth-footer">
                    <button type="button" class="account-auth-link" data-switch="forgot">Forgot password?</button>
                </div>
                <div class="account-auth-footer">
                    Don't have an account? <button type="button" class="account-auth-link" data-switch="register">Create one</button>
                </div>
                <div class="account-auth-footer account-owner-signin">
                    <i class="fa-solid fa-store"></i> Shop owner? <button type="button" class="account-auth-link" data-switch="seller">Sign in here</button>
                </div>
            </div>

            {* --- Seller login view --- *}
            <div class="account-tab-content" id="tab-seller" style="display:none">
                <form class="account-form" id="sellerLoginForm" novalidate>
                    <div class="account-field">
                        <input type="email" id="sellerEmail" name="email" placeholder="Seller email" required autocomplete="email">
                    </div>
                    <div class="account-field">
                        <div class="account-password-field">
                            <input type="password" id="sellerPassword" name="password" placeholder="Password" required autocomplete="current-password">
                            <button type="button" class="account-password-toggle" aria-label="Show password">
                                <i class="fa-solid fa-eye eye-open"></i>
                                <i class="fa-solid fa-eye-slash eye-closed" style="display:none"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="account-submit" id="sellerLoginSubmit">Sign In</button>
                </form>
                <div class="account-auth-footer">
                    <button type="button" class="account-auth-link" data-switch="login">Back to customer sign in</button>
                </div>
            </div>

            {* --- Register view --- *}
            <div class="account-tab-content" id="tab-register" style="display:none">
                <form class="account-form" id="registerForm" novalidate>
                    <div class="account-field">
                        <input type="email" id="regEmail" name="email" placeholder="Email address" required autocomplete="email">
                    </div>
                    <div class="account-field">
                        <input type="tel" id="regPhone" name="phone" placeholder="Phone (optional)" autocomplete="tel">
                    </div>
                    <div class="account-field">
                        <div class="account-password-field">
                            <input type="password" id="regPassword" name="password" placeholder="Password (8+ characters)" required autocomplete="new-password">
                            <button type="button" class="account-password-toggle" aria-label="Show password">
                                <i class="fa-solid fa-eye eye-open"></i>
                                <i class="fa-solid fa-eye-slash eye-closed" style="display:none"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="account-submit" id="registerSubmit">Create Account</button>
                </form>
                <div class="account-auth-footer">
                    Already have an account? <button type="button" class="account-auth-link" data-switch="login">Sign in</button>
                </div>
            </div>

            {* --- Forgot password view --- *}
            <div class="account-tab-content" id="tab-forgot" style="display:none">
                <form class="account-form" id="forgotForm" novalidate>
                    <div class="account-field">
                        <input type="email" id="forgotEmail" name="email" placeholder="Email address" required autocomplete="email">
                    </div>
                    <button type="submit" class="account-submit" id="forgotSubmit">Send Reset Link</button>
                </form>
                <div class="account-auth-footer">
                    <button type="button" class="account-auth-link" data-switch="login">Back to sign in</button>
                </div>
            </div>
        </div>
    </div>

{/if}

</main>

{include file="partials/shop/desktop_footer.tpl"}
{include file="partials/shop/cart_drawer.tpl"}
{include file="partials/shop/contact_sheet.tpl"}
{include file="partials/shop/bottom_nav.tpl"}

{/block}

{block name="page_scripts"}
<script>
(function() {ldelim}
    var shopId = document.querySelector('.shop-content').dataset.shopId;

    function getCsrf() {ldelim}
        var el = document.querySelector('meta[name="csrf-token"]');
        return el ? el.content : '';
    {rdelim}

    function apiCall(url, method, body) {ldelim}
        var opts = {ldelim}
            method: method,
            headers: {ldelim} 'Content-Type': 'application/json', 'X-CSRF-Token': getCsrf() {rdelim},
            credentials: 'same-origin'
        {rdelim};
        if (body) opts.body = JSON.stringify(body);
        return fetch(url, opts).then(function(r) {ldelim} return r.json(); {rdelim});
    {rdelim}

    // View headings per tab
    var headings = {ldelim}
        login:    {ldelim} title: 'Welcome back',     sub: 'Sign in to your account' {rdelim},
        register: {ldelim} title: 'Create account',   sub: 'Join to track your orders' {rdelim},
        forgot:   {ldelim} title: 'Forgot password?',  sub: 'We\'ll email you a reset link' {rdelim},
        reset:    {ldelim} title: 'Reset password',    sub: 'Choose a new password' {rdelim},
        seller:   {ldelim} title: 'Shop owner',        sub: 'Sign in to manage your shop' {rdelim}
    {rdelim};

    // View switching via footer links
    var switchLinks = document.querySelectorAll('.account-auth-link[data-switch]');
    switchLinks.forEach(function(link) {ldelim}
        link.addEventListener('click', function() {ldelim}
            var target = link.dataset.switch;
            var title = document.getElementById('authTitle');
            var sub = document.getElementById('authSub');
            document.querySelectorAll('.account-tab-content').forEach(function(c) {ldelim}
                c.style.display = c.id === 'tab-' + target ? '' : 'none';
            {rdelim});
            var h = headings[target];
            if (h) {ldelim}
                title.textContent = h.title;
                sub.textContent = h.sub;
            {rdelim}
        {rdelim});
    {rdelim});

    // Password toggle
    var toggles = document.querySelectorAll('.account-password-toggle');
    toggles.forEach(function(btn) {ldelim}
        btn.addEventListener('click', function() {ldelim}
            var input = btn.parentElement.querySelector('input');
            var isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            btn.querySelector('.eye-open').style.display = isPassword ? 'none' : '';
            btn.querySelector('.eye-closed').style.display = isPassword ? '' : 'none';
        {rdelim});
    {rdelim});

    // Login
    var loginForm = document.getElementById('loginForm');
    if (loginForm) {ldelim}
        loginForm.addEventListener('submit', function(e) {ldelim}
            e.preventDefault();
            var btn = document.getElementById('loginSubmit');
            btn.disabled = true;
            btn.textContent = 'Signing in...';

            apiCall('/api/customer/login', 'POST', {ldelim}
                shop_id: parseInt(shopId),
                email: document.getElementById('loginEmail').value.trim(),
                password: document.getElementById('loginPassword').value
            {rdelim}).then(function(data) {ldelim}
                if (data.success) {ldelim}
                    window.location.reload();
                {rdelim} else {ldelim}
                    TinyShop.toast(data.message || 'Login failed', 'error');
                    btn.disabled = false;
                    btn.textContent = 'Sign In';
                {rdelim}
            {rdelim}).catch(function() {ldelim}
                TinyShop.toast('Something went wrong. Please try again.', 'error');
                btn.disabled = false;
                btn.textContent = 'Sign In';
            {rdelim});
        {rdelim});
    {rdelim}

    // Register
    var regForm = document.getElementById('registerForm');
    if (regForm) {ldelim}
        regForm.addEventListener('submit', function(e) {ldelim}
            e.preventDefault();
            var btn = document.getElementById('registerSubmit');
            btn.disabled = true;
            btn.textContent = 'Creating account...';

            apiCall('/api/customer/register', 'POST', {ldelim}
                shop_id: parseInt(shopId),
                email: document.getElementById('regEmail').value.trim(),
                phone: document.getElementById('regPhone').value.trim(),
                password: document.getElementById('regPassword').value
            {rdelim}).then(function(data) {ldelim}
                if (data.success) {ldelim}
                    window.location.reload();
                {rdelim} else {ldelim}
                    TinyShop.toast(data.message || 'Registration failed', 'error');
                    btn.disabled = false;
                    btn.textContent = 'Create Account';
                {rdelim}
            {rdelim}).catch(function() {ldelim}
                TinyShop.toast('Something went wrong. Please try again.', 'error');
                btn.disabled = false;
                btn.textContent = 'Create Account';
            {rdelim});
        {rdelim});
    {rdelim}

    // Forgot password
    var forgotForm = document.getElementById('forgotForm');
    if (forgotForm) {ldelim}
        forgotForm.addEventListener('submit', function(e) {ldelim}
            e.preventDefault();
            var btn = document.getElementById('forgotSubmit');
            btn.disabled = true;
            btn.textContent = 'Sending...';

            apiCall('/api/customer/forgot-password', 'POST', {ldelim}
                shop_id: parseInt(shopId),
                email: document.getElementById('forgotEmail').value.trim()
            {rdelim}).then(function(data) {ldelim}
                btn.disabled = false;
                btn.textContent = 'Send Reset Link';
                if (data.success) {ldelim}
                    TinyShop.toast(data.message || 'Check your email for a reset link', 'success');
                {rdelim} else {ldelim}
                    TinyShop.toast(data.message || 'Could not send reset link', 'error');
                {rdelim}
            {rdelim}).catch(function() {ldelim}
                btn.disabled = false;
                btn.textContent = 'Send Reset Link';
                TinyShop.toast('Something went wrong. Please try again.', 'error');
            {rdelim});
        {rdelim});
    {rdelim}

    // Reset password (from email link)
    var resetForm = document.getElementById('resetForm');
    if (resetForm) {ldelim}
        resetForm.addEventListener('submit', function(e) {ldelim}
            e.preventDefault();
            var btn = document.getElementById('resetSubmit');
            btn.disabled = true;
            btn.textContent = 'Resetting...';

            apiCall('/api/customer/reset-password', 'POST', {ldelim}
                token: document.getElementById('resetToken').value,
                password: document.getElementById('resetPassword').value
            {rdelim}).then(function(data) {ldelim}
                btn.disabled = false;
                btn.textContent = 'Reset Password';
                if (data.success) {ldelim}
                    TinyShop.toast(data.message || 'Password reset! You can now sign in.', 'success');
                    resetForm.reset();
                {rdelim} else {ldelim}
                    TinyShop.toast(data.message || 'Could not reset password', 'error');
                {rdelim}
            {rdelim}).catch(function() {ldelim}
                btn.disabled = false;
                btn.textContent = 'Reset Password';
                TinyShop.toast('Something went wrong. Please try again.', 'error');
            {rdelim});
        {rdelim});
    {rdelim}

    // Seller login
    var sellerForm = document.getElementById('sellerLoginForm');
    if (sellerForm) {ldelim}
        sellerForm.addEventListener('submit', function(e) {ldelim}
            e.preventDefault();
            var btn = document.getElementById('sellerLoginSubmit');
            btn.disabled = true;
            btn.textContent = 'Signing in...';

            apiCall('/api/auth/login', 'POST', {ldelim}
                email: document.getElementById('sellerEmail').value.trim(),
                password: document.getElementById('sellerPassword').value
            {rdelim}).then(function(data) {ldelim}
                if (data.success) {ldelim}
                    window.location.href = data.redirect || '/dashboard';
                {rdelim} else {ldelim}
                    TinyShop.toast(data.message || 'Login failed', 'error');
                    btn.disabled = false;
                    btn.textContent = 'Sign In';
                {rdelim}
            {rdelim}).catch(function() {ldelim}
                TinyShop.toast('Something went wrong', 'error');
                btn.disabled = false;
                btn.textContent = 'Sign In';
            {rdelim});
        {rdelim});
    {rdelim}

    // Logout
    var logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {ldelim}
        logoutBtn.addEventListener('click', function() {ldelim}
            apiCall('/api/customer/logout', 'POST', {ldelim}{rdelim}).then(function() {ldelim}
                window.location.reload();
            {rdelim});
        {rdelim});
    {rdelim}

    // Profile: toggle between view and edit
    var profileView = document.getElementById('profileView');
    var profileForm = document.getElementById('profileForm');
    var profileToggle = document.getElementById('profileEditToggle');
    var profileCancel = document.getElementById('profileCancel');

    function showProfileEdit() {ldelim}
        profileView.style.display = 'none';
        profileForm.style.display = '';
        profileToggle.style.display = 'none';
    {rdelim}

    function showProfileView() {ldelim}
        profileView.style.display = '';
        profileForm.style.display = 'none';
        profileToggle.style.display = '';
        var err = document.getElementById('profileError');
        var suc = document.getElementById('profileSuccess');
        if (err) hideMsg(err);
        if (suc) hideMsg(suc);
    {rdelim}

    if (profileToggle) {ldelim}
        profileToggle.addEventListener('click', showProfileEdit);
    {rdelim}

    if (profileCancel) {ldelim}
        profileCancel.addEventListener('click', showProfileView);
    {rdelim}

    // Profile save
    if (profileForm) {ldelim}
        profileForm.addEventListener('submit', function(e) {ldelim}
            e.preventDefault();
            var btn = document.getElementById('profileSubmit');
            btn.disabled = true;
            btn.textContent = 'Saving...';

            var newName = document.getElementById('profileName').value.trim();
            var newEmail = document.getElementById('profileEmail').value.trim();
            var newPhone = document.getElementById('profilePhone').value.trim();

            apiCall('/api/customer/profile', 'PUT', {ldelim}
                shop_id: parseInt(shopId),
                name: newName,
                email: newEmail,
                phone: newPhone
            {rdelim}).then(function(data) {ldelim}
                btn.disabled = false;
                btn.textContent = 'Save';
                if (data.success) {ldelim}
                    // Update read-only view with new values
                    var vn = document.getElementById('viewName');
                    var ve = document.getElementById('viewEmail');
                    var vp = document.getElementById('viewPhone');
                    if (vn) vn.textContent = newName;
                    if (ve) ve.textContent = newEmail;
                    if (vp) vp.textContent = newPhone;
                    // Update greeting
                    var greeting = document.querySelector('.account-greeting-name');
                    if (greeting) greeting.textContent = 'Hi, ' + newName;
                    showProfileView();
                    TinyShop.toast('Profile updated', 'success');
                {rdelim} else {ldelim}
                    TinyShop.toast(data.message || 'Update failed', 'error');
                {rdelim}
            {rdelim}).catch(function() {ldelim}
                btn.disabled = false;
                btn.textContent = 'Save';
                TinyShop.toast('Something went wrong', 'error');
            {rdelim});
        {rdelim});
    {rdelim}
{rdelim})();
</script>
{/block}
