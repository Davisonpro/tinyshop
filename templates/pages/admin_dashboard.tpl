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
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <strong>Sellers</strong>
        </a>
        <a href="/admin/settings" class="action-card">
            <div class="action-icon blue">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            </div>
            <strong>Settings</strong>
        </a>
        <a href="/logout" class="action-card">
            <div class="action-icon orange">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            </div>
            <strong>Logout</strong>
        </a>
    </div>
</div>
{/block}
