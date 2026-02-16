{extends file="layouts/admin.tpl"}

{block name="content"}
<div class="dash-topbar">
    <a href="/admin/help" class="dash-topbar-back" aria-label="Back to help center">
        <i class="fa-solid fa-chevron-left"></i>
    </a>
    <span class="dash-topbar-title">{if $is_edit}Edit Article{else}New Article{/if}</span>
    {if $is_edit}
    <a href="/help/{$article.slug|escape}" target="_blank" class="dash-topbar-action" aria-label="View article">
        <i class="fa-solid fa-arrow-up-right-from-square"></i>
    </a>
    {else}
    <span></span>
    {/if}
</div>

<form id="articleForm" class="dash-form" autocomplete="off">
    <input type="hidden" id="articleId" value="{if $is_edit}{$article.id}{/if}">

    <div class="form-section">
        <div class="form-section-title">Article details</div>
        <div class="form-group">
            <label for="artTitle">Title</label>
            <input type="text" class="form-control" id="artTitle" value="{if $is_edit}{$article.title|escape}{/if}" placeholder="e.g. How to add a product" required autocomplete="off">
        </div>
        <div class="form-group">
            <label for="artCategory">Category</label>
            <select class="form-control" id="artCategory">
                {foreach $categories as $cat}
                <option value="{$cat.id}"{if $is_edit && $article.category_id == $cat.id} selected{/if}>{$cat.name|escape}</option>
                {/foreach}
            </select>
        </div>
        <div class="form-group">
            <label for="artSummary">Summary</label>
            <input type="text" class="form-control" id="artSummary" value="{if $is_edit}{$article.summary|escape}{/if}" placeholder="Short description shown in search results" maxlength="500" autocomplete="off">
            <p class="form-hint">Appears below the title in article lists and search results</p>
        </div>
    </div>

    <div class="form-section">
        <div class="form-section-title">Content</div>
        <div class="form-group">
            <div class="rich-editor" id="richEditor"></div>
            <textarea class="form-control" id="artContent" style="display:none">{if $is_edit}{$article.content|escape}{/if}</textarea>
        </div>
        <div class="form-group">
            <label>Images</label>
            <p class="form-hint mb-md">Add images to illustrate your article. Copy the URL after upload and paste it in the editor.</p>
            <div class="image-gallery" id="imageGallery">
                <div class="image-gallery-add" id="addImageBtn">
                    <i class="fa-solid fa-image icon-xl"></i>
                    <span>Add</span>
                </div>
            </div>
            <input type="file" id="imageInput" accept="image/*" class="d-none" multiple>
        </div>
    </div>

    <div class="form-section">
        <div class="form-section-title">Search &amp; visibility</div>
        <div class="form-group">
            <label for="artKeywords">Keywords</label>
            <input type="text" class="form-control" id="artKeywords" value="{if $is_edit}{$article.keywords|escape}{/if}" placeholder="search, terms, separated, by, commas" autocomplete="off">
            <p class="form-hint">Helps customers find this article when searching</p>
        </div>
        <div class="form-group">
            <label for="artOrder">Sort Order</label>
            <input type="number" class="form-control" id="artOrder" value="{if $is_edit}{$article.sort_order}{else}0{/if}" min="0" autocomplete="off">
            <p class="form-hint">Lower numbers appear first within a category</p>
        </div>
        <div class="form-toggle-row">
            <div>
                <div class="form-toggle-label">Published</div>
                <p class="form-hint mt-xs">Visible on the help center</p>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" id="artPublished" {if !$is_edit || $article.is_published}checked{/if}>
                <span class="toggle-track"></span>
            </label>
        </div>
    </div>

    <button type="submit" class="btn-primary" id="saveArticleBtn">
        {if $is_edit}Save Changes{else}Create Article{/if}
    </button>

    {if $is_edit}
    <button type="button" class="btn-danger mt-sm" id="deleteArticleBtn">Delete Article</button>
    {/if}
</form>
{/block}

{block name="extra_scripts"}
<script>
{literal}
// Rich text editor — same as product description
(function() {
    var editor = document.getElementById('richEditor');
    var textarea = document.getElementById('artContent');
    if (!editor) return;

    var content = document.createElement('div');
    content.className = 'rich-editor-content';
    content.contentEditable = true;
    content.setAttribute('data-placeholder', 'Write your article content here...');
    content.innerHTML = textarea.value || '';
    editor.appendChild(content);

    var toolbar = document.createElement('div');
    toolbar.className = 'rich-editor-toolbar';

    var actions = [
        { icon: '<span style="font-weight:800;font-size:14px">B</span>', cmd: 'bold', title: 'Bold' },
        { icon: '<span style="font-style:italic;font-size:14px;font-family:Georgia,serif">I</span>', cmd: 'italic', title: 'Italic' },
        { icon: '<span style="font-weight:700;font-size:13px">H</span>', cmd: 'heading', title: 'Heading', query: 'h3' },
        { icon: '<i class="fa-solid fa-list-ul"></i>', cmd: 'insertUnorderedList', title: 'Bullet list' },
        { icon: '<i class="fa-solid fa-list-ol"></i>', cmd: 'insertOrderedList', title: 'Numbered list' },
        { icon: '<i class="fa-solid fa-image"></i>', cmd: 'insertImage', title: 'Insert image' }
    ];

    var buttons = [];
    actions.forEach(function(a, i) {
        if (i === 3 || i === 5) {
            var sep = document.createElement('div');
            sep.className = 'rich-editor-sep';
            toolbar.appendChild(sep);
        }
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'rich-editor-btn';
        btn.innerHTML = a.icon;
        btn.title = a.title;
        btn.setAttribute('data-cmd', a.cmd);
        btn.addEventListener('mousedown', function(e) {
            e.preventDefault();
            if (a.cmd === 'heading') {
                var block = document.queryCommandValue('formatBlock');
                document.execCommand('formatBlock', false, block === 'h3' ? '<p>' : '<h3>');
            } else if (a.cmd === 'insertImage') {
                var url = prompt('Image URL:');
                if (url && url.trim()) {
                    document.execCommand('insertHTML', false, '<img src="' + url.trim().replace(/"/g, '&quot;') + '" alt="" style="max-width:100%;border-radius:8px;margin:8px 0">');
                }
            } else {
                document.execCommand(a.cmd, false, null);
            }
            sync();
            updateActiveStates();
        });
        toolbar.appendChild(btn);
        buttons.push({ el: btn, action: a });
    });
    editor.appendChild(toolbar);

    function sync() {
        var html = content.innerHTML;
        textarea.value = (!html || html === '<br>' || html === '<p><br></p>') ? '' : html;
    }

    function updateActiveStates() {
        buttons.forEach(function(b) {
            var active = false;
            if (b.action.cmd === 'heading') {
                active = document.queryCommandValue('formatBlock') === 'h3';
            } else if (b.action.cmd === 'bold' || b.action.cmd === 'italic') {
                active = document.queryCommandState(b.action.cmd);
            } else if (b.action.cmd === 'insertUnorderedList' || b.action.cmd === 'insertOrderedList') {
                active = document.queryCommandState(b.action.cmd);
            }
            b.el.classList.toggle('is-active', active);
        });
    }

    content.addEventListener('input', function() { sync(); updateActiveStates(); });
    content.addEventListener('blur', sync);
    content.addEventListener('keyup', updateActiveStates);
    content.addEventListener('mouseup', function() { setTimeout(updateActiveStates, 10); });

    content.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && !e.shiftKey) {
            if (e.key === 'b') { e.preventDefault(); document.execCommand('bold'); sync(); updateActiveStates(); }
            if (e.key === 'i') { e.preventDefault(); document.execCommand('italic'); sync(); updateActiveStates(); }
        }
    });

    content.addEventListener('paste', function(e) {
        e.preventDefault();
        var html = (e.clipboardData || window.clipboardData).getData('text/html');
        var text = (e.clipboardData || window.clipboardData).getData('text/plain');
        if (html) {
            var tmp = document.createElement('div');
            tmp.innerHTML = html;
            tmp.querySelectorAll('*').forEach(function(el) {
                el.removeAttribute('style');
                el.removeAttribute('class');
                el.removeAttribute('id');
            });
            var allowed = ['P','BR','B','STRONG','I','EM','UL','OL','LI','H2','H3','A','IMG'];
            tmp.querySelectorAll('*').forEach(function(el) {
                if (allowed.indexOf(el.tagName) === -1) {
                    el.replaceWith(document.createTextNode(el.textContent));
                }
            });
            document.execCommand('insertHTML', false, tmp.innerHTML);
        } else {
            document.execCommand('insertText', false, text);
        }
        sync();
    });

    // Allow dropping images
    content.addEventListener('drop', function(e) {
        var files = e.dataTransfer ? e.dataTransfer.files : null;
        if (files && files.length > 0) {
            e.preventDefault();
            for (var f = 0; f < files.length; f++) {
                if (files[f].type.startsWith('image/')) {
                    uploadAndInsertImage(files[f]);
                }
            }
        }
    });

    function uploadAndInsertImage(file) {
        TinyShop.uploadFile(file, function(url) {
            content.focus();
            document.execCommand('insertHTML', false, '<img src="' + url + '" alt="" style="max-width:100%;border-radius:8px;margin:8px 0">');
            sync();
        });
    }

    window._articleEditor = content;
})();
{/literal}
</script>
<script>
(function() {ldelim}
    var isEdit = {if $is_edit}true{else}false{/if};
    var articleId = {if $is_edit}{$article.id}{else}null{/if};

    // Image gallery — upload and show URL for copying
    var gallery = document.getElementById('imageGallery');
    var addBtn = document.getElementById('addImageBtn');
    var fileInput = document.getElementById('imageInput');

    addBtn.addEventListener('click', function() {ldelim} fileInput.click(); {rdelim});

    fileInput.addEventListener('change', function() {ldelim}
        var files = this.files;
        for (var i = 0; i < files.length; i++) {ldelim}
            uploadImage(files[i]);
        {rdelim}
        this.value = '';
    {rdelim});

    function uploadImage(file) {ldelim}
        TinyShop.uploadFile(file, function(url) {ldelim}
            var item = document.createElement('div');
            item.className = 'image-gallery-item';
            item.setAttribute('data-url', url);
            item.innerHTML = '<img src="' + url + '" alt="">'
                + '<button type="button" class="image-gallery-remove">&times;</button>';
            gallery.insertBefore(item, addBtn);

            // Insert into editor
            if (window._articleEditor) {ldelim}
                window._articleEditor.focus();
                document.execCommand('insertHTML', false, '<img src="' + url + '" alt="" style="max-width:100%;border-radius:8px;margin:8px 0"><br>');
                var textarea = document.getElementById('artContent');
                if (textarea) textarea.value = window._articleEditor.innerHTML;
            {rdelim}

            TinyShop.toast('Image added to article');
        {rdelim});
    {rdelim}

    $(document).on('click', '.image-gallery-remove', function() {ldelim}
        $(this).parent('.image-gallery-item').remove();
    {rdelim});

    // Save article
    $('#articleForm').on('submit', function(e) {ldelim}
        e.preventDefault();

        var formData = {ldelim}
            title: $('#artTitle').val().trim(),
            category_id: parseInt($('#artCategory').val()),
            summary: $('#artSummary').val().trim(),
            content: $('#artContent').val(),
            keywords: $('#artKeywords').val().trim(),
            sort_order: parseInt($('#artOrder').val()) || 0,
            is_published: $('#artPublished').is(':checked') ? 1 : 0
        {rdelim};

        if (!formData.title) {ldelim}
            TinyShop.toast('Article title is required', 'error');
            return;
        {rdelim}

        var method = isEdit ? 'PUT' : 'POST';
        var url = isEdit ? '/api/admin/help-articles/' + articleId : '/api/admin/help-articles';

        var $btn = $('#saveArticleBtn').prop('disabled', true).text('Saving...');

        TinyShop.api(method, url, formData).done(function(res) {ldelim}
            TinyShop.toast(isEdit ? 'Article updated' : 'Article created');
            if (!isEdit && res.article && res.article.id) {ldelim}
                TinyShop.navigate('/admin/help/articles/' + res.article.id + '/edit');
            {rdelim} else {ldelim}
                $btn.prop('disabled', false).text(isEdit ? 'Save Changes' : 'Create Article');
            {rdelim}
        {rdelim}).fail(function(xhr) {ldelim}
            var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to save';
            TinyShop.toast(msg, 'error');
            $btn.prop('disabled', false).text(isEdit ? 'Save Changes' : 'Create Article');
        {rdelim});
    {rdelim});

    // Delete article
    {if $is_edit}
    $('#deleteArticleBtn').on('click', function() {ldelim}
        TinyShop.confirm({ldelim}
            title: 'Delete Article',
            message: 'Are you sure you want to delete this article? This cannot be undone.',
            confirmText: 'Delete',
            variant: 'danger',
            onConfirm: function() {ldelim}
                TinyShop.api('DELETE', '/api/admin/help-articles/' + articleId).done(function() {ldelim}
                    TinyShop.toast('Article deleted');
                    TinyShop.navigate('/admin/help');
                {rdelim}).fail(function(xhr) {ldelim}
                    var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Cannot delete this article';
                    TinyShop.toast(msg, 'error');
                {rdelim});
            {rdelim}
        {rdelim});
    {rdelim});
    {/if}
{rdelim})();
</script>
{/block}
