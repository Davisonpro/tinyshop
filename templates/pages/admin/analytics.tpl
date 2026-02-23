{extends file="layouts/admin.tpl"}

{block name="content"}
<div class="dash-greeting">
    <div class="dash-greeting-row">
        <div>
            <small>Admin Panel</small>
            <h1>Analytics</h1>
        </div>
        <div class="dash-avatar admin-avatar">A</div>
    </div>
</div>

{* ── Shop Analytics ── *}
<div class="dash-section">
    <div class="dash-section-header">
        <h2>Shop Views</h2>
    </div>
</div>
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
            <div class="stats-panel-number">{$view_stats.month|default:0|number_format:0:'.':','}</div>
            <div class="stats-panel-label">This Month</div>
        </div>
    </div>
</div>

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
        <h2>Shop Daily Views</h2>
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

{* Shop Traffic Sources *}
{if $traffic_sources|count > 0}
<div class="dash-section">
    <div class="dash-section-header">
        <h2>Shop Traffic Sources</h2>
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

{* Top Shops *}
{if $top_shops|count > 0}
<div class="dash-section">
    <div class="dash-section-header">
        <h2>Top Shops</h2>
        <span class="text-muted" style="font-size:0.75rem">Last 30 days</span>
    </div>
    <div class="top-products-list">
        {foreach $top_shops as $idx => $shop}
            <a href="/admin/sellers/{$shop.user_id}" class="top-product-row">
                <span class="top-product-rank">{$idx + 1}</span>
                <div class="top-product-name">{$shop.store_name|escape|default:'Unnamed Shop'}</div>
                <div class="top-product-views">{$shop.views|number_format:0:'.':','} views &middot; {$shop.unique_visitors|number_format:0:'.':','} unique</div>
            </a>
        {/foreach}
    </div>
</div>
{/if}

{* ── Website Analytics ── *}
<div class="dash-section">
    <div class="dash-section-header">
        <h2>Website Views</h2>
    </div>
</div>
<div class="stats-panel">
    <div class="stats-panel-grid">
        <div class="stats-panel-metric">
            <div class="stats-panel-number">{$site_stats.today|default:0|number_format:0:'.':','}</div>
            <div class="stats-panel-label">Today</div>
        </div>
        <div class="stats-panel-metric">
            <div class="stats-panel-number">{$site_stats.week|default:0|number_format:0:'.':','}</div>
            <div class="stats-panel-label">This Week</div>
        </div>
        <div class="stats-panel-metric">
            <div class="stats-panel-number">{$site_stats.unique_week|default:0|number_format:0:'.':','}</div>
            <div class="stats-panel-label">Unique Visitors</div>
        </div>
        <div class="stats-panel-metric">
            <div class="stats-panel-number">{$site_stats.month|default:0|number_format:0:'.':','}</div>
            <div class="stats-panel-label">This Month</div>
        </div>
    </div>
</div>

{* Website daily views chart *}
<div class="dash-section">
    <div class="dash-section-header">
        <h2>Website Daily Views</h2>
        <div class="date-range-tabs" aria-label="Date range">
            <button type="button" class="date-range-tab{if $site_view_days == 7} active{/if}" data-days="7" data-chart="site-views">7d</button>
            <button type="button" class="date-range-tab{if $site_view_days == 14} active{/if}" data-days="14" data-chart="site-views">14d</button>
            <button type="button" class="date-range-tab{if $site_view_days == 30} active{/if}" data-days="30" data-chart="site-views">30d</button>
        </div>
    </div>
    <div id="siteViewsChart">
    <div class="chart-card">
        {assign var="max_site_views" value=1}
        {foreach $site_daily_views as $sv}
            {if $sv.views > $max_site_views}{assign var="max_site_views" value=$sv.views}{/if}
        {/foreach}
        <div class="bar-chart">
            {foreach $site_daily_views as $sv}
                <div class="bar-col">
                    <div class="bar-value">{$sv.views|number_format:0:'.':','}</div>
                    <div class="bar-fill" style="height:{($sv.views / $max_site_views * 100)|string_format:'%.0f'}%{if $sv.views == 0};min-height:2px{/if}"></div>
                    <div class="bar-label">{if $site_view_days == 7}{$sv.day|date_format:'%a'}{else}{$sv.label|regex_replace:'/^[A-Za-z]+ /':''}{/if}</div>
                </div>
            {/foreach}
        </div>
    </div>
    </div>
</div>

{* Website traffic sources *}
{if $site_traffic_sources|count > 0}
<div class="dash-section">
    <div class="dash-section-header">
        <h2>Website Traffic Sources</h2>
        <span class="text-muted" style="font-size:0.75rem">Last 30 days</span>
    </div>
    <div class="traffic-sources-list">
        {foreach $site_traffic_sources as $src}
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

{* Top Pages *}
{if $site_top_pages|count > 0}
<div class="dash-section">
    <div class="dash-section-header">
        <h2>Top Pages</h2>
        <span class="text-muted" style="font-size:0.75rem">Last 30 days</span>
    </div>
    <div class="top-products-list">
        {foreach $site_top_pages as $idx => $pg}
            <div class="top-product-row">
                <span class="top-product-rank">{$idx + 1}</span>
                <div class="top-product-name">{$pg.label|escape}</div>
                <div class="top-product-views">{$pg.views|number_format:0:'.':','} views &middot; {$pg.unique_visitors|number_format:0:'.':','} unique</div>
            </div>
        {/foreach}
    </div>
</div>
{/if}
{/block}

{block name="extra_scripts"}
<script>
$(function() {ldelim}
    var _viewDays = {$view_days};
    var _salesDays = {$sales_days};
    var _siteViewDays = {$site_view_days};
    var _loading = false;

    $(document).on('click', '.date-range-tab[data-chart]', function() {ldelim}
        var days = parseInt($(this).data('days'));
        var chart = $(this).data('chart');
        if (!days || _loading) return;

        if (chart === 'views' && days === _viewDays) return;
        if (chart === 'sales' && days === _salesDays) return;
        if (chart === 'site-views' && days === _siteViewDays) return;

        if (chart === 'views') _viewDays = days;
        else if (chart === 'sales') _salesDays = days;
        else if (chart === 'site-views') _siteViewDays = days;

        _loading = true;

        $(this).closest('.date-range-tabs').find('.date-range-tab').removeClass('active');
        $(this).addClass('active');

        var targetId = chart === 'sales' ? '#salesChart' : (chart === 'site-views' ? '#siteViewsChart' : '#viewsChart');
        var $target = $(targetId);
        $target.css({ldelim} opacity: 0.5, transition: 'opacity 0.15s' {rdelim});

        $.ajax({ldelim}
            url: '/admin/analytics?view_days=' + _viewDays + '&sales_days=' + _salesDays + '&site_view_days=' + _siteViewDays,
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

                history.replaceState(
                    {ldelim} spa: true, url: location.pathname {rdelim},
                    '',
                    '/admin/analytics?view_days=' + _viewDays + '&sales_days=' + _salesDays + '&site_view_days=' + _siteViewDays
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
