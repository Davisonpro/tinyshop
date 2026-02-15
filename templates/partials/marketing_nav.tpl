<div class="mk-navbar" id="mkNav">
    <nav class="mk-nav-container">
        <a href="/" class="mk-nav-logo">{$app_name}</a>
        <div class="mk-nav-right">
            {if $current_page|default:'' !== 'pricing'}
                <a href="/pricing" class="mk-nav-link">Pricing</a>
            {/if}
            {if $logged_in|default:false}
                <a href="/dashboard" class="mk-nav-btn mk-nav-login">Dashboard</a>
            {else}
                <a href="/login" class="mk-nav-btn mk-nav-login">Log in</a>
                <a href="/register" class="mk-nav-btn mk-nav-signup">Sign up free</a>
            {/if}
        </div>
    </nav>
</div>