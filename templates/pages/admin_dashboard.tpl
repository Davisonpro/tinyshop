{extends file="layouts/admin.tpl"}

{block name="content"}
<div class="dash-greeting">
    <div class="dash-greeting-row">
        <div>
            <small>Admin Panel</small>
            <h1>Overview</h1>
        </div>
        <div class="dash-avatar admin-avatar">A</div>
    </div>
</div>

<div class="dash-stats admin-stats-3">
    <div class="stat-card">
        <div class="stat-number">{$total_sellers}</div>
        <div class="stat-label">Sellers</div>
    </div>
    <div class="stat-card">
        <div class="stat-number">{$active_sellers}</div>
        <div class="stat-label">Active</div>
    </div>
    <div class="stat-card">
        <div class="stat-number">{$new_signups}</div>
        <div class="stat-label">New This Week</div>
    </div>
</div>

<div class="dash-stats admin-stats-3 admin-stats-row-2">
    <div class="stat-card">
        <div class="stat-number">{$total_products}</div>
        <div class="stat-label">Products</div>
    </div>
    <div class="stat-card">
        <div class="stat-number">{$total_orders}</div>
        <div class="stat-label">Orders</div>
    </div>
    <div class="stat-card">
        <div class="stat-number">&nbsp;</div>
        <div class="stat-label">&nbsp;</div>
    </div>
</div>

<div class="dash-section">
    <div class="dash-section-header">
        <h2>Quick Actions</h2>
    </div>
    <div class="quick-actions">
        <a href="/admin/sellers" class="action-card">
            <div class="action-icon purple">
                <i class="fa-solid fa-users icon-lg"></i>
            </div>
            <strong>Sellers</strong>
        </a>
        <a href="/admin/settings" class="action-card">
            <div class="action-icon blue">
                <i class="fa-solid fa-gear icon-lg"></i>
            </div>
            <strong>Settings</strong>
        </a>
        <a href="/logout" class="action-card">
            <div class="action-icon orange">
                <i class="fa-solid fa-right-from-bracket icon-lg"></i>
            </div>
            <strong>Logout</strong>
        </a>
    </div>
</div>
{/block}
