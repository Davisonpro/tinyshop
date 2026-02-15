<nav class="dash-tabs">
    <a href="/admin" class="dash-tab{if $active_page == 'dashboard'} active{/if}" data-label="Overview">
        <i class="fa-solid fa-table-cells-large" aria-hidden="true"></i>
        <span>Overview</span>
    </a>
    <a href="/admin/sellers" class="dash-tab{if $active_page == 'sellers'} active{/if}" data-label="Sellers">
        <i class="fa-solid fa-users" aria-hidden="true"></i>
        <span>Sellers</span>
    </a>
    <a href="/admin/plans" class="dash-tab{if $active_page == 'plans'} active{/if}" data-label="Plans">
        <i class="fa-solid fa-crown" aria-hidden="true"></i>
        <span>Plans</span>
    </a>
    <a href="/admin/settings" class="dash-tab{if $active_page == 'settings'} active{/if}" data-label="Settings">
        <i class="fa-solid fa-gear" aria-hidden="true"></i>
        <span>Settings</span>
    </a>
</nav>
