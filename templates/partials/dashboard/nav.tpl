<nav class="dash-tabs" aria-label="Dashboard navigation">
    <a href="/dashboard" class="dash-tab{if $active_page == 'home'} active{/if}" data-label="Home"{if $active_page == 'home'} aria-current="page"{/if}>
        <i class="fa-solid fa-house" aria-hidden="true"></i>
        <span>Home</span>
    </a>
    <a href="/dashboard/products" class="dash-tab{if $active_page == 'products'} active{/if}" data-label="Products"{if $active_page == 'products'} aria-current="page"{/if}>
        <i class="fa-solid fa-box" aria-hidden="true"></i>
        <span>Products</span>
    </a>
    <a href="/dashboard/orders" class="dash-tab{if $active_page == 'orders'} active{/if}" data-label="Orders"{if $active_page == 'orders'} aria-current="page"{/if}>
        <i class="fa-solid fa-bag-shopping" aria-hidden="true"></i>
        <span>Orders</span>
    </a>
    <a href="/dashboard/analytics" class="dash-tab{if $active_page == 'analytics'} active{/if}" data-label="Analytics"{if $active_page == 'analytics'} aria-current="page"{/if}>
        <i class="fa-solid fa-chart-bar" aria-hidden="true"></i>
        <span>Analytics</span>
    </a>
    <a href="/dashboard/shop" class="dash-tab{if $active_page == 'shop'} active{/if}" data-label="Settings"{if $active_page == 'shop'} aria-current="page"{/if}>
        <i class="fa-solid fa-gear" aria-hidden="true"></i>
        <span>Settings</span>
    </a>
</nav>
