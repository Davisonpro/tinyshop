{extends file="layouts/dashboard.tpl"}

{block name="content"}
<div class="dash-topbar">
    <span class="dash-topbar-title">Analytics</span>
    <a href="/dashboard/shop" class="dash-topbar-avatar" aria-label="Settings">{$user.store_name|default:$user.name|escape|substr:0:1|upper}</a>
</div>

{* Views *}
<div class="stats-panel">
    <div class="stats-panel-grid">
        <div class="stats-panel-metric">
            <div class="stats-panel-number">{$view_stats.today|default:0|number_format:0:'.':','}</div>
            <div class="stats-panel-label">Today</div>
        </div>
        <div class="stats-panel-metric">
            <div class="stats-panel-number">{$view_stats.week|default:0|number_format:0:'.':','}</div>
            <div class="stats-panel-label">This Week</div>
        </div>
        <div class="stats-panel-metric">
            <div class="stats-panel-number">{$view_stats.unique_week|default:0|number_format:0:'.':','}</div>
            <div class="stats-panel-label">Unique Visitors</div>
        </div>
        <div class="stats-panel-metric">
            <div class="stats-panel-number">{$view_stats.total|default:0|number_format:0:'.':','}</div>
            <div class="stats-panel-label">All Time</div>
        </div>
    </div>
</div>

{* Sales *}
{if $order_stats.total > 0}
<div class="stats-panel">
    <div class="stats-panel-grid">
        <div class="stats-panel-metric">
            <div class="stats-panel-number">{$order_stats.total|default:0|number_format:0:'.':','}</div>
            <div class="stats-panel-label">Orders</div>
        </div>
        <div class="stats-panel-metric">
            <div class="stats-panel-number">{$order_stats.completed|default:0|number_format:0:'.':','}</div>
            <div class="stats-panel-label">Paid</div>
        </div>
        <div class="stats-panel-metric">
            <div class="stats-panel-number">{$order_stats.pending|default:0|number_format:0:'.':','}</div>
            <div class="stats-panel-label">Pending</div>
        </div>
        <div class="stats-panel-metric">
            <div class="stats-panel-number"><span class="stats-panel-currency">{$currency}</span> {$order_stats.revenue|default:0|number_format:0:'.':','}</div>
            <div class="stats-panel-label">Revenue</div>
        </div>
    </div>
</div>
{/if}

{* Daily sales chart *}
{assign var="has_sales" value=false}
{foreach $daily_sales as $ds}
    {if $ds.revenue > 0}{assign var="has_sales" value=true}{/if}
{/foreach}
{if $has_sales}
<div class="dash-section">
    <div class="dash-section-header">
        <h2>Daily Sales</h2>
        <div class="date-range-tabs" aria-label="Date range">
            <a href="/dashboard/analytics?days=7" class="date-range-tab{if $selected_days == 7} active{/if}">7d</a>
            <a href="/dashboard/analytics?days=14" class="date-range-tab{if $selected_days == 14} active{/if}">14d</a>
            <a href="/dashboard/analytics?days=30" class="date-range-tab{if $selected_days == 30} active{/if}">30d</a>
        </div>
    </div>
    <div class="chart-card">
        {assign var="max_revenue" value=1}
        {foreach $daily_sales as $ds}
            {if $ds.revenue > $max_revenue}{assign var="max_revenue" value=$ds.revenue}{/if}
        {/foreach}
        <div class="bar-chart">
            {foreach $daily_sales as $ds}
                <div class="bar-col">
                    <div class="bar-value">{if $ds.revenue > 0}{$currency} {$ds.revenue|number_format:0:'.':','}{else}0{/if}</div>
                    <div class="bar-fill bar-fill-sales" style="height:{($ds.revenue / $max_revenue * 100)|string_format:'%.0f'}%{if $ds.revenue == 0};min-height:2px{/if}"></div>
                    <div class="bar-label">{if $selected_days == 7}{$ds.day|date_format:'%a'}{else}{$ds.label|regex_replace:'/^[A-Za-z]+ /':''}{/if}</div>
                </div>
            {/foreach}
        </div>
    </div>
</div>
{/if}

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
                    <div class="bar-value">{$d.views|number_format:0:'.':','}</div>
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
                                <i class="fa-solid fa-image" style="font-size:16px"></i>
                            </div>
                        {/if}
                    </div>
                    <div class="top-product-name">{$tp.name|escape}</div>
                    <div class="top-product-views">{$tp.views|number_format:0:'.':','} views</div>
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
