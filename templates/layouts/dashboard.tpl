{extends file="layouts/base.tpl"}

{block name="body_class"}page-dashboard{/block}

{block name="head"}
    {include file="partials/head.tpl"}
    <link rel="stylesheet" href="/public/css/dashboard.css?v={$asset_v}">
{/block}

{block name="body"}
{if $is_impersonating}
<div class="impersonate-banner">
    <span>Viewing as seller account</span>
    <a href="/admin/stop-impersonate">Exit</a>
</div>
{/if}
<div class="dashboard-layout">
    <main class="dash-content">
        {block name="content"}{/block}
    </main>

    <aside class="dash-sidebar">
        <div class="dash-sidebar-brand">
            <span class="dash-sidebar-logo">{$app_name|escape|substr:0:1|upper}</span>
            <span class="dash-sidebar-name">{$app_name|escape}</span>
        </div>
        {include file="partials/dash_nav.tpl"}
    </aside>
</div>

{* Bottom sheet modal *}
<div class="modal-overlay" id="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="modal-box" id="modalBox">
        <div class="modal-handle"></div>
        <div class="modal-header">
            <h2 id="modalTitle"></h2>
            <button type="button" class="modal-close" id="modalClose" aria-label="Close">&times;</button>
        </div>
        <div class="modal-body" id="modalBody"></div>
    </div>
</div>
{/block}

{block name="page_scripts"}
    <script src="/public/js/dashboard.js?v={$asset_v}"></script>
    {block name="extra_scripts"}{/block}
{/block}
