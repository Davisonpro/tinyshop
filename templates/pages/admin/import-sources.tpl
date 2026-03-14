{extends file="layouts/admin.tpl"}

{block name="content"}
<div class="dash-topbar">
    <span class="dash-topbar-title">Import Sources</span>
</div>

<div class="dash-form">
    <div class="form-section">
        <div class="form-section-title">
            <i class="fa-solid fa-globe icon-sm"></i>
            Whitelisted Sources
        </div>
        <p class="form-hint mb-md">Configure websites where the Quick Add feature can look up product details. Each source needs a search URL template and CSS selectors for extracting data.</p>

        <div id="sourcesList">
            {if $sources|@count == 0}
                <p class="form-hint">No sources configured yet. Add one below.</p>
            {else}
                {foreach $sources as $src}
                <div class="account-row source-row" data-id="{$src.id}">
                    <div class="account-row-left">
                        <i class="fa-solid fa-globe icon-lg" style="color:var(--color-primary)"></i>
                        <div class="account-row-info">
                            <span class="account-row-title">{$src.name|escape}</span>
                            <span class="account-row-sub">{$src.base_url|escape}</span>
                        </div>
                    </div>
                    <div style="display:flex;gap:8px;align-items:center">
                        <span class="badge {if $src.is_active}badge-success{else}badge-muted{/if}">{if $src.is_active}Active{else}Off{/if}</span>
                        <button type="button" class="btn-icon edit-source-btn" data-id="{$src.id}" title="Edit">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button type="button" class="btn-icon delete-source-btn" data-id="{$src.id}" data-name="{$src.name|escape}" title="Delete">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>
                {/foreach}
            {/if}
        </div>
    </div>

    <div class="form-section">
        <div class="form-section-title">Add New Source</div>

        <div class="form-group">
            <label for="srcName">Name</label>
            <input type="text" class="form-control" id="srcName" placeholder="e.g. Amazon Kenya">
        </div>
        <div class="form-group">
            <label for="srcBaseUrl">Base URL</label>
            <input type="url" class="form-control" id="srcBaseUrl" placeholder="https://www.amazon.com">
        </div>
        <div class="form-group">
            <label for="srcSearchUrl">Search URL Template</label>
            <input type="text" class="form-control" id="srcSearchUrl" placeholder="https://www.amazon.com/s?k={ldelim}query{rdelim}">
            <p class="form-hint">{ldelim}query{rdelim} is replaced with the search term</p>
        </div>
        <div class="form-group">
            <label for="srcSelectors">CSS Selectors (JSON)</label>
            <textarea class="form-control" id="srcSelectors" rows="6" placeholder='{ldelim}
  "search_result_link": ".s-result-item a.a-link-normal",
  "product_name": "h1#productTitle",
  "description": "#productDescription",
  "images": "#altImages img",
  "specs": "#productDetails_techSpec_section_1",
  "category": ".a-breadcrumb a"
{rdelim}'></textarea>
        </div>
        <div class="form-group">
            <label for="srcPriority">Priority</label>
            <input type="number" class="form-control" id="srcPriority" value="10" min="1" max="99">
            <p class="form-hint">Lower number = checked first</p>
        </div>

        <button type="button" class="btn btn-primary" id="addSourceBtn" style="width:100%;min-height:48px">
            <i class="fa-solid fa-plus"></i> Add Source
        </button>
    </div>
</div>
{/block}

{block name="extra_scripts"}
<script>
{literal}
(function($) {
    $('#addSourceBtn').on('click', function() {
        var name = $('#srcName').val().trim();
        var baseUrl = $('#srcBaseUrl').val().trim();
        var searchUrl = $('#srcSearchUrl').val().trim();
        var selectorsText = $('#srcSelectors').val().trim();
        var priority = parseInt($('#srcPriority').val()) || 10;

        if (!name || !baseUrl) {
            TinyShop.toast('Name and base URL are required', 'error');
            return;
        }

        var selectors = {};
        if (selectorsText) {
            try { selectors = JSON.parse(selectorsText); }
            catch(e) { TinyShop.toast('Invalid JSON in selectors', 'error'); return; }
        }

        var $btn = $(this).prop('disabled', true).text('Adding...');

        TinyShop.api('POST', '/api/admin/import-sources', {
            name: name,
            base_url: baseUrl,
            search_url_template: searchUrl,
            selectors: selectors,
            priority: priority,
            is_active: 1
        }).done(function() {
            TinyShop.toast('Source added');
            location.reload();
        }).fail(function(xhr) {
            TinyShop.toast(xhr.responseJSON ? xhr.responseJSON.message : 'Failed', 'error');
            $btn.prop('disabled', false).html('<i class="fa-solid fa-plus"></i> Add Source');
        });
    });

    $(document).on('click', '.delete-source-btn', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        if (!confirm('Delete source "' + name + '"?')) return;

        TinyShop.api('DELETE', '/api/admin/import-sources/' + id).done(function() {
            TinyShop.toast('Source deleted');
            location.reload();
        }).fail(function() {
            TinyShop.toast('Failed to delete', 'error');
        });
    });
})($);
{/literal}
</script>
{/block}
