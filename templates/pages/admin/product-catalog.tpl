{extends file="layouts/admin.tpl"}

{block name="content"}
<div class="dash-topbar">
    <span class="dash-topbar-title">Product Catalog</span>
</div>

<div class="dash-form">
    <div class="form-section">
        <div class="form-section-title">
            <i class="fa-solid fa-database icon-sm"></i>
            Product Knowledge Base
        </div>
        <p class="form-hint mb-md">Cached product data shared across all sellers. When a product is looked up, it's stored here so future lookups are instant.</p>

        <div class="form-group">
            <form method="get" action="/admin/product-catalog" style="display:flex;gap:8px">
                <input type="text" class="form-control" name="search" value="{$search|escape}" placeholder="Search brand, model...">
                <button type="submit" class="btn btn-primary" style="min-height:44px;padding:0 20px;white-space:nowrap">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </form>
        </div>

        {if $entries|@count == 0}
            <p class="form-hint" style="padding:20px 0;text-align:center">
                {if $search}No results for "{$search|escape}"{else}No products in catalog yet. They'll appear as sellers use Quick Add.{/if}
            </p>
        {else}
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Source</th>
                            <th>Lookups</th>
                            <th>Quality</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $entries as $entry}
                        <tr data-id="{$entry.id}">
                            <td>
                                <strong>{$entry.brand|escape}</strong> {$entry.model|escape}
                                <br><small class="text-muted">{$entry.canonical_name|escape}</small>
                            </td>
                            <td>
                                {if $entry.source_site}
                                    <span class="badge badge-muted">{$entry.source_site|escape}</span>
                                {else}
                                    <span class="text-muted">—</span>
                                {/if}
                            </td>
                            <td>{$entry.lookup_count}</td>
                            <td>
                                <span class="badge {if $entry.quality_score >= 60}badge-success{elseif $entry.quality_score >= 30}badge-warning{else}badge-muted{/if}">
                                    {$entry.quality_score}%
                                </span>
                            </td>
                            <td>
                                <button type="button" class="btn-icon delete-catalog-btn" data-id="{$entry.id}" data-name="{$entry.canonical_name|escape}" title="Delete">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>

            {if $entries|@count >= 50}
            <div style="padding:16px 0;text-align:center">
                <a href="/admin/product-catalog?page={$page_num+1}{if $search}&search={$search|escape:'url'}{/if}" class="btn btn-ghost">
                    Next Page <i class="fa-solid fa-chevron-right" style="font-size:0.75rem"></i>
                </a>
            </div>
            {/if}
        {/if}
    </div>
</div>
{/block}

{block name="extra_scripts"}
<script>
{literal}
(function($) {
    $(document).on('click', '.delete-catalog-btn', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        if (!confirm('Delete "' + name + '" from catalog?')) return;

        TinyShop.api('DELETE', '/api/admin/product-catalog/' + id).done(function() {
            TinyShop.toast('Entry deleted');
            $('tr[data-id="' + id + '"]').fadeOut(200, function() { $(this).remove(); });
        }).fail(function() {
            TinyShop.toast('Failed to delete', 'error');
        });
    });
})($);
{/literal}
</script>
{/block}
