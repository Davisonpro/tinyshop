{extends file="layouts/admin.tpl"}

{block name="content"}
<div class="dash-topbar">
    <span class="dash-topbar-title">Pages</span>
</div>

<div class="admin-list-wrap" id="pagesWrap">
    {if $pages_list|count == 0}
        <div class="empty-state">
            <div class="empty-icon">
                <i class="fa-solid fa-file-lines icon-2xl text-muted"></i>
            </div>
            <h2>No pages yet</h2>
            <p>Create pages like Terms of Service or Privacy Policy.</p>
        </div>
    {else}
        <div class="pages-card-list">
            {foreach $pages_list as $pg}
            <a href="/admin/pages/{$pg.id}/edit" class="page-card-row">
                <div class="page-card-icon">
                    <i class="fa-solid fa-file-lines"></i>
                </div>
                <div class="page-card-info">
                    <div class="page-card-title">{$pg.title|escape}</div>
                    <div class="page-card-slug">/{$pg.slug|escape}</div>
                </div>
                <div class="page-card-right">
                    {if !$pg.is_published}<span class="plan-badge plan-badge-inactive">Draft</span>{/if}
                    <i class="fa-solid fa-chevron-right page-card-chevron"></i>
                </div>
            </a>
            {/foreach}
        </div>
    {/if}
</div>

<a href="/admin/pages/add" class="fab" id="addPageFab" aria-label="Add page">
    <i class="fa-solid fa-plus"></i>
</a>
{/block}
