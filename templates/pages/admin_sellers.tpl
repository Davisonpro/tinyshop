{extends file="layouts/admin.tpl"}

{block name="content"}
<div class="dash-topbar">
    <span class="dash-topbar-title">Sellers</span>
    <span class="sellers-count-badge">{$total}</span>
</div>

<div class="sellers-toolbar">
    <form method="get" action="/admin/sellers" class="sellers-search-form">
        <i class="fa-solid fa-magnifying-glass sellers-search-icon"></i>
        <input type="text" name="q" value="{$search|escape}" placeholder="Search by name or email..." class="sellers-search-input" autocomplete="off">
        {if $search}<a href="/admin/sellers" class="sellers-search-clear" title="Clear">&times;</a>{/if}
    </form>
</div>

<div class="sellers-list-wrap">
    {if $sellers|count == 0}
        <div class="sellers-empty">
            <div class="sellers-empty-icon">
                <i class="fa-solid fa-store"></i>
            </div>
            <h3>{if $search}No sellers matching "{$search|escape}"{else}No sellers yet{/if}</h3>
            <p>{if $search}Try a different search term{else}Sellers will appear here when they sign up{/if}</p>
        </div>
    {else}
        <div class="sellers-card-list">
            {foreach $sellers as $seller}
            <a href="/admin/sellers/{$seller.id}" class="seller-card" data-id="{$seller.id}">
                <div class="seller-card-left">
                    <div class="seller-card-avatar{if !$seller.is_active} suspended{/if}">
                        {$seller.store_name|escape|substr:0:1|upper}
                    </div>
                    <div class="seller-card-info">
                        <div class="seller-card-name">{$seller.store_name|escape}</div>
                        <div class="seller-card-email">{$seller.email|escape}</div>
                    </div>
                </div>
                <div class="seller-card-right">
                    <span class="seller-status-pill{if $seller.is_active} active{/if}">{if $seller.is_active}Active{else}Suspended{/if}</span>
                    <i class="fa-solid fa-chevron-right seller-card-chevron"></i>
                </div>
            </a>
            {/foreach}
        </div>

        {if $total_pages > 1}
        <div class="sellers-pagination">
            {if $current_page > 1}
                <a href="/admin/sellers?page={$current_page - 1}{if $search}&q={$search|escape:'url'}{/if}" class="sellers-page-btn">
                    <i class="fa-solid fa-chevron-left"></i>
                </a>
            {else}
                <span class="sellers-page-btn disabled"><i class="fa-solid fa-chevron-left"></i></span>
            {/if}
            <span class="sellers-page-info">{$current_page} / {$total_pages}</span>
            {if $current_page < $total_pages}
                <a href="/admin/sellers?page={$current_page + 1}{if $search}&q={$search|escape:'url'}{/if}" class="sellers-page-btn">
                    <i class="fa-solid fa-chevron-right"></i>
                </a>
            {else}
                <span class="sellers-page-btn disabled"><i class="fa-solid fa-chevron-right"></i></span>
            {/if}
        </div>
        {/if}
    {/if}
</div>
{/block}
