{extends file="layouts/admin.tpl"}

{block name="content"}
<div class="dash-topbar">
    <a href="/admin/pages" class="dash-topbar-back" aria-label="Back to pages">
        <i class="fa-solid fa-chevron-left"></i>
    </a>
    <span class="dash-topbar-title">{if $is_edit}Edit Page{else}New Page{/if}</span>
    {if $is_edit}
    <a href="/{$page_data.slug|escape}" target="_blank" class="dash-topbar-action" aria-label="View page">
        <i class="fa-solid fa-arrow-up-right-from-square"></i>
    </a>
    {else}
    <span></span>
    {/if}
</div>

<form id="pageForm" class="dash-form" autocomplete="off">
    <input type="hidden" id="pageId" value="{if $is_edit}{$page_data.id}{/if}">

    <div class="form-section">
        <div class="form-section-title">Page details</div>
        <div class="form-group">
            <label for="pgTitle">Title</label>
            <input type="text" class="form-control" id="pgTitle" value="{if $is_edit}{$page_data.title|escape}{/if}" placeholder="e.g. Terms of Service" required autocomplete="off">
        </div>
        <div class="form-group">
            <label for="pgSlug">Permalink</label>
            <div class="permalink-field">
                <span class="permalink-prefix">/</span>
                <input type="text" class="form-control permalink-input" id="pgSlug" value="{if $is_edit}{$page_data.slug|escape}{/if}" placeholder="terms-of-service" autocomplete="off">
            </div>
            <p class="form-hint">URL-friendly name. Only lowercase letters, numbers, and hyphens.</p>
        </div>
        <div class="form-group">
            <label for="pgMetaDesc">Description</label>
            <input type="text" class="form-control" id="pgMetaDesc" value="{if $is_edit}{$page_data.meta_description|escape}{/if}" placeholder="Short description for search engines" maxlength="500" autocomplete="off">
            <p class="form-hint">Shown in search results under the page title</p>
        </div>
    </div>

    <div class="form-section">
        <div class="form-section-title">Content</div>
        <div class="form-group">
            <div class="rich-editor" id="richEditor"></div>
            <textarea class="form-control" id="pgContent" style="display:none">{if $is_edit}{$page_data.content|escape}{/if}</textarea>
        </div>
    </div>

    <div class="form-section">
        <div class="form-section-title">Visibility</div>
        <div class="form-toggle-row">
            <div>
                <div class="form-toggle-label">Published</div>
                <p class="form-hint" style="margin-top:2px">Visible to everyone</p>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" id="pgPublished" {if !$is_edit || $page_data.is_published}checked{/if}>
                <span class="toggle-slider"></span>
            </label>
        </div>
    </div>

    <button type="submit" class="btn-primary" id="savePageBtn">
        {if $is_edit}Save Changes{else}Create Page{/if}
    </button>

    {if $is_edit}
    <button type="button" class="btn-danger mt-sm" id="deletePageBtn">Delete Page</button>
    {/if}
</form>
{/block}

{block name="extra_scripts"}
<script>
{literal}
(function() {
    var editor = document.getElementById('richEditor');
    var textarea = document.getElementById('pgContent');
    if (!editor) return;

    var content = document.createElement('div');
    content.className = 'rich-editor-content';
    content.contentEditable = true;
    content.setAttribute('data-placeholder', 'Write your page content here...');
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

    content.addEventListener('drop', function(e) {
        var files = e.dataTransfer ? e.dataTransfer.files : null;
        if (files && files.length > 0) {
            e.preventDefault();
            for (var f = 0; f < files.length; f++) {
                if (files[f].type.startsWith('image/')) {
                    TinyShop.uploadFile(files[f], function(url) {
                        content.focus();
                        document.execCommand('insertHTML', false, '<img src="' + url + '" alt="" style="max-width:100%;border-radius:8px;margin:8px 0">');
                        sync();
                    });
                }
            }
        }
    });
})();
{/literal}
</script>
<script>
(function() {ldelim}
    var isEdit = {if $is_edit}true{else}false{/if};
    var pageId = {if $is_edit}{$page_data.id}{else}null{/if};
    var slugEdited = isEdit;

    function toSlug(str) {ldelim}
        return str.toLowerCase().replace(/[^a-z0-9\s-]/g, '').replace(/[\s-]+/g, '-').replace(/^-|-$/g, '');
    {rdelim}

    $('#pgTitle').on('input', function() {ldelim}
        if (!slugEdited) {ldelim}
            $('#pgSlug').val(toSlug($(this).val()));
        {rdelim}
    {rdelim});

    $('#pgSlug').on('input', function() {ldelim}
        slugEdited = true;
        $(this).val($(this).val().toLowerCase().replace(/[^a-z0-9-]/g, ''));
    {rdelim});

    $('#pageForm').on('submit', function(e) {ldelim}
        e.preventDefault();

        var formData = {ldelim}
            title: $('#pgTitle').val().trim(),
            slug: $('#pgSlug').val().trim(),
            content: $('#pgContent').val(),
            meta_description: $('#pgMetaDesc').val().trim(),
            is_published: $('#pgPublished').is(':checked') ? 1 : 0
        {rdelim};

        if (!formData.title) {ldelim}
            TinyShop.toast('Page title is required', 'error');
            return;
        {rdelim}

        if (!formData.slug) {ldelim}
            formData.slug = toSlug(formData.title);
        {rdelim}
        if (!/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(formData.slug)) {ldelim}
            TinyShop.toast('Permalink can only contain lowercase letters, numbers, and hyphens', 'error');
            return;
        {rdelim}

        var method = isEdit ? 'PUT' : 'POST';
        var url = isEdit ? '/api/admin/pages/' + pageId : '/api/admin/pages';

        var $btn = $('#savePageBtn').prop('disabled', true).text('Saving...');

        TinyShop.api(method, url, formData).done(function(res) {ldelim}
            TinyShop.toast(isEdit ? 'Page updated' : 'Page created');
            if (!isEdit && res.page && res.page.id) {ldelim}
                TinyShop.navigate('/admin/pages/' + res.page.id + '/edit');
            {rdelim} else {ldelim}
                $btn.prop('disabled', false).text(isEdit ? 'Save Changes' : 'Create Page');
            {rdelim}
        {rdelim}).fail(function(xhr) {ldelim}
            var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to save';
            TinyShop.toast(msg, 'error');
            $btn.prop('disabled', false).text(isEdit ? 'Save Changes' : 'Create Page');
        {rdelim});
    {rdelim});

    {if $is_edit}
    $('#deletePageBtn').on('click', function() {ldelim}
        TinyShop.confirm({ldelim}
            title: 'Delete Page',
            message: 'Are you sure you want to delete this page? This cannot be undone.',
            confirmText: 'Delete',
            variant: 'danger',
            onConfirm: function() {ldelim}
                TinyShop.api('DELETE', '/api/admin/pages/' + pageId).done(function() {ldelim}
                    TinyShop.toast('Page deleted');
                    TinyShop.navigate('/admin/pages');
                {rdelim}).fail(function(xhr) {ldelim}
                    var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Cannot delete this page';
                    TinyShop.toast(msg, 'error');
                {rdelim});
            {rdelim}
        {rdelim});
    {rdelim});
    {/if}
{rdelim})();
</script>
{/block}
