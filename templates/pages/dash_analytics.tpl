{extends file="layouts/dashboard.tpl"}

{block name="content"}
<div class="dash-topbar">
    <span class="dash-topbar-title">Analytics</span>
    <a href="/dashboard/shop" class="dash-topbar-avatar" aria-label="Settings">{if $user.shop_logo}<img src="{$user.shop_logo|escape}" alt="">{else}{$user.name|escape|substr:0:1|upper}{/if}</a>
</div>

{* Stats overview *}
<div class="dash-stats">
    <div class="stat-card">
        <div class="stat-number">{$view_stats.today|default:0}</div>
        <div class="stat-label">Today</div>
    </div>
    <div class="stat-card">
        <div class="stat-number">{$view_stats.week|default:0}</div>
        <div class="stat-label">This Week</div>
    </div>
    <div class="stat-card">
        <div class="stat-number">{$view_stats.unique_week|default:0}</div>
        <div class="stat-label">Unique</div>
    </div>
    <div class="stat-card">
        <div class="stat-number">{$view_stats.total|default:0}</div>
        <div class="stat-label">All Time</div>
    </div>
</div>

{* Daily views chart *}
<div class="dash-section">
    <div class="dash-section-header">
        <h2>Daily Views</h2>
        <div class="date-range-tabs" aria-label="Date range">
            <a href="/dashboard/analytics?days=7" class="date-range-tab{if $selected_days == 7} active{/if}">7d</a>
            <a href="/dashboard/analytics?days=14" class="date-range-tab{if $selected_days == 14} active{/if}">14d</a>
            <a href="/dashboard/analytics?days=30" class="date-range-tab{if $selected_days == 30} active{/if}">30d</a>
        </div>
    </div>
    <div class="chart-card">
        {assign var="max_views" value=1}
        {foreach $daily_views as $d}
            {if $d.views > $max_views}{assign var="max_views" value=$d.views}{/if}
        {/foreach}
        <div class="bar-chart">
            {foreach $daily_views as $d}
                <div class="bar-col">
                    <div class="bar-value">{$d.views}</div>
                    <div class="bar-fill" style="height:{($d.views / $max_views * 100)|string_format:'%.0f'}%{if $d.views == 0};min-height:2px{/if}"></div>
                    <div class="bar-label">{if $selected_days == 7}{$d.day|date_format:'%a'}{else}{$d.label|regex_replace:'/^[A-Za-z]+ /':''}{/if}</div>
                </div>
            {/foreach}
        </div>
    </div>
</div>

{* Top products *}
<div class="dash-section">
    <div class="dash-section-header">
        <h2>Popular Products</h2>
        <span class="text-muted" style="font-size:0.75rem">Last 30 days</span>
    </div>
    {if $top_products|count > 0}
        <div class="top-products-list">
            {foreach $top_products as $idx => $tp}
                <a href="/dashboard/products/{$tp.product_id}/edit" class="top-product-row">
                    <span class="top-product-rank">{$idx + 1}</span>
                    <div class="top-product-img">
                        {if $tp.image_url}
                            <img src="{$tp.image_url|escape}" alt="{$tp.name|escape}">
                        {else}
                            <div class="top-product-img-placeholder">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                            </div>
                        {/if}
                    </div>
                    <div class="top-product-name">{$tp.name|escape}</div>
                    <div class="top-product-views">{$tp.views} views</div>
                </a>
            {/foreach}
        </div>
    {else}
        <div class="chart-card" style="text-align:center;padding:32px 20px;color:var(--color-text-muted);font-size:0.875rem">
            No product views yet. Share your shop link!
        </div>
    {/if}
</div>
{/block}
