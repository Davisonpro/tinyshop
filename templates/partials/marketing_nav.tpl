<div class="mk-navbar" id="mkNav">
    <nav class="mk-nav-container">
        <a href="/" class="mk-nav-logo">{$app_name}</a>
        <div class="mk-nav-right mk-nav-desktop">
            <a href="/pricing" class="mk-nav-link{if $current_page|default:'' === 'pricing'} mk-nav-link--active{/if}">Pricing</a>
            <a href="/help" class="mk-nav-link{if $current_page|default:'' === 'help'} mk-nav-link--active{/if}">Help</a>
            {if $logged_in|default:false}
                <a href="/dashboard" class="mk-nav-btn mk-nav-login">Dashboard</a>
            {elseif $current_page|default:'' === 'login' || $current_page|default:'' === 'forgot_password' || $current_page|default:'' === 'reset_password'}
                <a href="/register" class="mk-nav-btn mk-nav-signup">Sign up free</a>
            {elseif $current_page|default:'' === 'register'}
                <a href="/login" class="mk-nav-btn mk-nav-login">Log in</a>
            {else}
                <a href="/login" class="mk-nav-btn mk-nav-login">Log in</a>
                <a href="/register" class="mk-nav-btn mk-nav-signup">Sign up free</a>
            {/if}
        </div>
        <button type="button" class="mk-nav-burger" id="mkNavBurger" aria-label="Open menu" aria-expanded="false">
            <span class="mk-burger-line"></span>
            <span class="mk-burger-line"></span>
            <span class="mk-burger-line"></span>
        </button>
    </nav>
</div>

{* ── Mobile menu (bottom sheet) ── *}
<div class="mk-mobile-backdrop" id="mkMobileBackdrop"></div>
<div class="mk-mobile-menu" id="mkMobileMenu">
    <div class="mk-mobile-handle"></div>
    <div class="mk-mobile-links">
        <a href="/pricing" class="mk-mobile-link{if $current_page|default:'' === 'pricing'} mk-mobile-link--active{/if}">
            <i class="fa-solid fa-tag"></i> Pricing
        </a>
        <a href="/help" class="mk-mobile-link{if $current_page|default:'' === 'help'} mk-mobile-link--active{/if}">
            <i class="fa-solid fa-circle-question"></i> Help
        </a>
    </div>
    <div class="mk-mobile-actions">
        {if $logged_in|default:false}
            <a href="/dashboard" class="mk-mobile-btn mk-mobile-btn--primary">Dashboard</a>
        {elseif $current_page|default:'' === 'login' || $current_page|default:'' === 'forgot_password' || $current_page|default:'' === 'reset_password'}
            <a href="/register" class="mk-mobile-btn mk-mobile-btn--primary">Sign up free</a>
        {elseif $current_page|default:'' === 'register'}
            <a href="/login" class="mk-mobile-btn mk-mobile-btn--secondary">Log in</a>
        {else}
            <a href="/login" class="mk-mobile-btn mk-mobile-btn--secondary">Log in</a>
            <a href="/register" class="mk-mobile-btn mk-mobile-btn--primary">Sign up free</a>
        {/if}
    </div>
</div>

<script>
(function() {ldelim}
    var burger = document.getElementById('mkNavBurger');
    var menu = document.getElementById('mkMobileMenu');
    var backdrop = document.getElementById('mkMobileBackdrop');
    if (!burger || !menu || !backdrop) return;

    function openMenu() {ldelim}
        menu.classList.add('mk-mobile-menu--open');
        backdrop.classList.add('mk-mobile-backdrop--show');
        burger.setAttribute('aria-expanded', 'true');
        burger.classList.add('mk-burger--open');
        document.body.style.overflow = 'hidden';
    {rdelim}

    function closeMenu() {ldelim}
        menu.classList.remove('mk-mobile-menu--open');
        backdrop.classList.remove('mk-mobile-backdrop--show');
        burger.setAttribute('aria-expanded', 'false');
        burger.classList.remove('mk-burger--open');
        document.body.style.overflow = '';
    {rdelim}

    burger.addEventListener('click', function() {ldelim}
        if (menu.classList.contains('mk-mobile-menu--open')) {ldelim}
            closeMenu();
        {rdelim} else {ldelim}
            openMenu();
        {rdelim}
    {rdelim});

    backdrop.addEventListener('click', closeMenu);

    // Close on link click
    menu.querySelectorAll('a').forEach(function(link) {ldelim}
        link.addEventListener('click', closeMenu);
    {rdelim});
{rdelim})();
</script>
