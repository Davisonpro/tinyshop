{extends file="layouts/dashboard.tpl"}

{block name="content"}
<div class="dash-topbar">
    <span class="dash-topbar-title">Analytics</span>
    <a href="/dashboard/shop" class="dash-topbar-avatar" aria-label="Settings">{$user.store_name|escape|substr:0:1|upper}</a>
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
            <button type="button" class="date-range-tab{if $sales_days == 7} active{/if}" data-days="7" data-chart="sales">7d</button>
            <button type="button" class="date-range-tab{if $sales_days == 14} active{/if}" data-days="14" data-chart="sales">14d</button>
            <button type="button" class="date-range-tab{if $sales_days == 30} active{/if}" data-days="30" data-chart="sales">30d</button>
        </div>
    </div>
    <div id="salesChart">
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
                    <div class="bar-label">{if $sales_days == 7}{$ds.day|date_format:'%a'}{else}{$ds.label|regex_replace:'/^[A-Za-z]+ /':''}{/if}</div>
                </div>
            {/foreach}
        </div>
    </div>
    </div>
</div>
{/if}

{* Daily views chart *}
<div class="dash-section">
    <div class="dash-section-header">
        <h2>Daily Views</h2>
        <div class="date-range-tabs" aria-label="Date range">
            <button type="button" class="date-range-tab{if $view_days == 7} active{/if}" data-days="7" data-chart="views">7d</button>
            <button type="button" class="date-range-tab{if $view_days == 14} active{/if}" data-days="14" data-chart="views">14d</button>
            <button type="button" class="date-range-tab{if $view_days == 30} active{/if}" data-days="30" data-chart="views">30d</button>
        </div>
    </div>
    <div id="viewsChart">
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
                    <div class="bar-label">{if $view_days == 7}{$d.day|date_format:'%a'}{else}{$d.label|regex_replace:'/^[A-Za-z]+ /':''}{/if}</div>
                </div>
            {/foreach}
        </div>
    </div>
    </div>
</div>

{* Traffic Sources *}
{if $traffic_sources|count > 0}
<div class="dash-section">
    <div class="dash-section-header">
        <h2>Traffic Sources</h2>
        <span class="text-muted" style="font-size:0.75rem">Last 30 days</span>
    </div>
    <div class="traffic-sources-list">
        {foreach $traffic_sources as $src}
        <div class="traffic-source-row">
            <div class="traffic-source-icon">
                {if $src.key === 'direct'}
                    <i class="fa-solid fa-arrow-pointer"></i>
                {elseif $src.key === 'google'}
                    <i class="fa-brands fa-google"></i>
                {elseif $src.key === 'facebook'}
                    <i class="fa-brands fa-facebook"></i>
                {elseif $src.key === 'instagram'}
                    <i class="fa-brands fa-instagram"></i>
                {elseif $src.key === 'tiktok'}
                    <i class="fa-brands fa-tiktok"></i>
                {elseif $src.key === 'twitter'}
                    <i class="fa-brands fa-x-twitter"></i>
                {elseif $src.key === 'whatsapp'}
                    <i class="fa-brands fa-whatsapp"></i>
                {elseif $src.key === 'youtube'}
                    <i class="fa-brands fa-youtube"></i>
                {elseif $src.key === 'pinterest'}
                    <i class="fa-brands fa-pinterest"></i>
                {else}
                    <i class="fa-solid fa-globe"></i>
                {/if}
            </div>
            <div class="traffic-source-info">
                <div class="traffic-source-name">{$src.label|escape}</div>
                <div class="traffic-source-bar">
                    <div class="traffic-source-bar-fill" style="width:{$src.percent}%"></div>
                </div>
            </div>
            <div class="traffic-source-stats">
                <span class="traffic-source-views">{$src.views|number_format:0:'.':','} views</span>
                <span class="traffic-source-pct">{$src.unique|number_format:0:'.':','} unique &middot; {$src.percent}%</span>
            </div>
        </div>
        {/foreach}
    </div>
</div>
{/if}

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

{block name="extra_scripts"}
<script>
$(function() {ldelim}
    var _viewDays = {$view_days};
    var _salesDays = {$sales_days};
    var _loading = false;

    $(document).on('click', '.date-range-tab[data-chart]', function() {ldelim}
        var days = parseInt($(this).data('days'));
        var chart = $(this).data('chart');
        if (!days || _loading) return;

        // Skip if already active
        if (chart === 'views' && days === _viewDays) return;
        if (chart === 'sales' && days === _salesDays) return;

        // Update tracked state
        if (chart === 'views') _viewDays = days;
        else _salesDays = days;

        _loading = true;

        // Update active state only within this tab group
        $(this).closest('.date-range-tabs').find('.date-range-tab').removeClass('active');
        $(this).addClass('active');

        // Fade just the target chart
        var targetId = chart === 'sales' ? '#salesChart' : '#viewsChart';
        var $target = $(targetId);
        $target.css({ldelim} opacity: 0.5, transition: 'opacity 0.15s' {rdelim});

        $.ajax({ldelim}
            url: '/dashboard/analytics?view_days=' + _viewDays + '&sales_days=' + _salesDays,
            headers: {ldelim} 'X-SPA': '1' {rdelim},
            dataType: 'text',
            success: function(text) {ldelim}
                var body = '';
                try {ldelim}
                    var json = JSON.parse(text);
                    body = json.body || '';
                {rdelim} catch(e) {ldelim}
                    body = text;
                {rdelim}

                var $temp = $('<div>').html(body);
                var newContent = $temp.find(targetId).html();
                if (newContent) {ldelim}
                    $target.html(newContent);
                {rdelim}

                // Update URL
                history.replaceState(
                    {ldelim} spa: true, url: location.pathname {rdelim},
                    '',
                    '/dashboard/analytics?view_days=' + _viewDays + '&sales_days=' + _salesDays
                );

                $target.css({ldelim} opacity: 1 {rdelim});
                _loading = false;
            {rdelim},
            error: function() {ldelim}
                $target.css({ldelim} opacity: 1 {rdelim});
                _loading = false;
                TinyShop.toast('Failed to load data', 'error');
            {rdelim}
        {rdelim});
    {rdelim});
{rdelim});
</script>
{/block}
