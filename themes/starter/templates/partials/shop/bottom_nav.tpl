{* Bottom Navigation — mobile only, hidden on desktop *}
<nav class="bottom-nav" id="bottomNav">
    <a href="/" class="bottom-nav-tab{if $current_page === 'home'} active{/if}">
        <i class="fa-solid fa-house"></i>
        <span>Home</span>
    </a>
    <a href="/search" class="bottom-nav-tab{if $current_page === 'search'} active{/if}">
        <i class="fa-solid fa-magnifying-glass"></i>
        <span>Search</span>
    </a>
    {if !empty($has_payments)}
    <button type="button" class="bottom-nav-tab cart-trigger">
        <i class="fa-solid fa-bag-shopping"></i>
        <span>Cart</span>
        <span class="cart-badge cart-badge-hidden"></span>
    </button>
    {/if}
    <a href="/account" class="bottom-nav-tab{if $current_page === 'account'} active{/if}">
        <i class="fa-solid {if !empty($customer_logged_in)}fa-user-check{else}fa-user{/if}"></i>
        <span>Account</span>
    </a>
    <button type="button" class="bottom-nav-tab contact-sheet-toggle">
        <i class="fa-solid fa-message"></i>
        <span>Contact</span>
    </button>
</nav>
