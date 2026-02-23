{extends file="layouts/admin.tpl"}

{block name="content"}
<div class="dash-topbar">
    <span class="dash-topbar-title">Help Center</span>
    <button type="button" class="btn-sm btn-outline" id="addCategoryBtn">
        <i class="fa-solid fa-folder-plus icon-xs"></i> Category
    </button>
</div>

{* ── Categories + Articles ── *}
<div class="admin-list-wrap" id="helpWrap">
    {if $categories|count == 0}
        <div class="empty-state">
            <div class="empty-icon">
                <i class="fa-solid fa-circle-question icon-2xl text-muted"></i>
            </div>
            <h2>No categories yet</h2>
            <p>Create a category to start adding help articles.</p>
        </div>
    {else}
        {foreach $categories as $cat}
        <div class="help-admin-cat" data-cat-id="{$cat.id}">
            <div class="help-admin-cat-header">
                <div class="help-admin-cat-info">
                    <span class="help-admin-cat-icon"><i class="fa-solid {$cat.icon}"></i></span>
                    <h3 class="help-admin-cat-name">{$cat.name|escape}</h3>
                </div>
                <div class="help-admin-cat-actions">
                    <span class="help-admin-cat-count">{$cat.article_count} article{if $cat.article_count != 1}s{/if}</span>
                    <button type="button" class="plan-card-edit" data-edit-cat="{$cat.id}" aria-label="Edit category">
                        <i class="fa-solid fa-pen icon-sm"></i>
                    </button>
                </div>
            </div>
            {if $cat.description}
                <p class="help-admin-cat-desc">{$cat.description|escape}</p>
            {/if}

            {* Articles in this category *}
            {assign var="has_articles" value=false}
            {assign var="art_num" value=0}
            <div class="help-admin-articles">
            {foreach $articles as $art}
                {if $art.category_id == $cat.id}
                    {assign var="has_articles" value=true}
                    {assign var="art_num" value=$art_num+1}
                    <a href="/admin/help/articles/{$art.id}/edit" class="help-admin-article">
                        <div class="help-admin-article-info">
                            <span class="help-admin-article-title">{$art.title|escape}</span>
                            {if !$art.is_published}<span class="plan-badge plan-badge-inactive">Draft</span>{/if}
                        </div>
                        <i class="fa-solid fa-chevron-right icon-sm help-admin-article-arrow"></i>
                    </a>
                {/if}
            {/foreach}
            {if !$has_articles}
                <div class="help-admin-cat-empty">No articles yet</div>
            {/if}
            </div>
        </div>
        {/foreach}
    {/if}
</div>

<a href="/admin/help/articles/add" class="fab" id="addArticleFab" aria-label="Add article">
    <i class="fa-solid fa-plus"></i>
</a>
{/block}

{block name="extra_scripts"}
<script>
(function() {ldelim}
    var categories = {$categories|json_encode};

    // ── Category form (modal — simple fields) ──
    function openCategoryForm(cat) {ldelim}
        var isEdit = !!cat;
        var c = cat || {ldelim}{rdelim};

        var html = '<form id="catForm" autocomplete="off">'
            + '<div class="form-group">'
            + '<label for="catName">Name</label>'
            + '<input type="text" class="form-control" id="catName" value="' + escAttr(c.name || '') + '" placeholder="e.g. Getting Started" required>'
            + '</div>'
            + '<div class="form-group">'
            + '<label for="catIcon">Icon</label>'
            + '<input type="text" class="form-control" id="catIcon" value="' + escAttr(c.icon || 'fa-circle-question') + '" placeholder="fa-rocket">'
            + '<small style="color:var(--color-text-muted);margin-top:4px;display:block">Font Awesome class, e.g. fa-rocket, fa-box-open</small>'
            + '</div>'
            + '<div class="form-group">'
            + '<label for="catDesc">Description</label>'
            + '<input type="text" class="form-control" id="catDesc" value="' + escAttr(c.description || '') + '" placeholder="Short description" maxlength="255">'
            + '</div>'
            + '<div class="form-group">'
            + '<label for="catOrder">Sort Order</label>'
            + '<input type="number" class="form-control" id="catOrder" value="' + (c.sort_order || 0) + '" min="0">'
            + '</div>'
            + '<div class="plan-form-actions">'
            + '<button type="submit" class="btn-primary">' + (isEdit ? 'Save Changes' : 'Create Category') + '</button>';

        if (isEdit) {ldelim}
            html += '<button type="button" class="btn-block btn-danger-outline" id="deleteCatBtn">Delete Category</button>';
        {rdelim}

        html += '</div></form>';

        TinyShop.openModal(isEdit ? 'Edit Category' : 'New Category', html);

        $('#catForm').on('submit', function(e) {ldelim}
            e.preventDefault();
            var formData = {ldelim}
                name: $('#catName').val().trim(),
                icon: $('#catIcon').val().trim() || 'fa-circle-question',
                description: $('#catDesc').val().trim(),
                sort_order: parseInt($('#catOrder').val()) || 0
            {rdelim};

            if (!formData.name) {ldelim} TinyShop.toast('Category name is required', 'error'); return; {rdelim}

            var method = isEdit ? 'PUT' : 'POST';
            var url = isEdit ? '/api/admin/help-categories/' + c.id : '/api/admin/help-categories';

            var $btn = $(this).find('[type="submit"]').prop('disabled', true).text('Saving...');

            TinyShop.api(method, url, formData).done(function() {ldelim}
                TinyShop.toast(isEdit ? 'Category updated' : 'Category created');
                TinyShop.closeModal();
                location.reload();
            {rdelim}).fail(function(xhr) {ldelim}
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to save';
                TinyShop.toast(msg, 'error');
                $btn.prop('disabled', false).text(isEdit ? 'Save Changes' : 'Create Category');
            {rdelim});
        {rdelim});

        if (isEdit) {ldelim}
            $('#deleteCatBtn').on('click', function() {ldelim}
                TinyShop.confirm({ldelim}
                    title: 'Delete Category',
                    message: 'Are you sure? All articles in this category will be deleted too.',
                    confirmText: 'Delete',
                    variant: 'danger',
                    onConfirm: function() {ldelim}
                        TinyShop.api('DELETE', '/api/admin/help-categories/' + c.id).done(function() {ldelim}
                            TinyShop.toast('Category deleted');
                            TinyShop.closeModal();
                            location.reload();
                        {rdelim}).fail(function(xhr) {ldelim}
                            var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Cannot delete this category';
                            TinyShop.toast(msg, 'error');
                        {rdelim});
                    {rdelim}
                {rdelim});
            {rdelim});
        {rdelim}
    {rdelim}

    function escAttr(str) {ldelim}
        return String(str).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    {rdelim}

    // ── Event handlers ──

    $('#addCategoryBtn').on('click', function() {ldelim}
        openCategoryForm(null);
    {rdelim});

    $(document).on('click', '[data-edit-cat]', function() {ldelim}
        var id = parseInt($(this).data('edit-cat'));
        for (var i = 0; i < categories.length; i++) {ldelim}
            if (parseInt(categories[i].id) === id) {ldelim}
                openCategoryForm(categories[i]);
                return;
            {rdelim}
        {rdelim}
    {rdelim});
{rdelim})();
</script>
{/block}
