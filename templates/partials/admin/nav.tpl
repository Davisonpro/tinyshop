<nav class="dash-tabs">
    <a href="/admin" class="dash-tab{if $active_page == 'dashboard'} active{/if}" data-label="Overview">
        <i class="fa-solid fa-table-cells-large" aria-hidden="true"></i>
        <span>Overview</span>
    </a>
    <a href="/admin/sellers" class="dash-tab{if $active_page == 'sellers'} active{/if}" data-label="Sellers">
        <i class="fa-solid fa-users" aria-hidden="true"></i>
        <span>Sellers</span>
    </a>
    <a href="/admin/analytics" class="dash-tab{if $active_page == 'analytics'} active{/if}" data-label="Analytics">
        <i class="fa-solid fa-chart-simple" aria-hidden="true"></i>
        <span>Analytics</span>
    </a>
    <a href="/admin/help" class="dash-tab{if $active_page == 'help'} active{/if}" data-label="Help">
        <i class="fa-solid fa-circle-question" aria-hidden="true"></i>
        <span>Help</span>
    </a>
    <a href="/admin/settings" class="dash-tab{if $active_page == 'settings'} active{/if}" data-label="Settings">
        <i class="fa-solid fa-gear" aria-hidden="true"></i>
        <span>Settings</span>
    </a>
</nav>
