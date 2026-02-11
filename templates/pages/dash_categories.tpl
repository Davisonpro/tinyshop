{extends file="layouts/dashboard.tpl"}

{block name="content"}
<div class="dash-topbar">
    <a href="/dashboard/products" class="dash-topbar-back">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
    </a>
    <span class="dash-topbar-title">Categories</span>
    <span></span>
</div>

<div id="categoryList" class="category-list">
    <div class="skeleton-row"><div class="skeleton-row-img"></div><div class="skeleton-row-text"><div class="skeleton-line skeleton-line-title"></div><div class="skeleton-line skeleton-line-sub"></div></div></div>
    <div class="skeleton-row"><div class="skeleton-row-img"></div><div class="skeleton-row-text"><div class="skeleton-line skeleton-line-title"></div><div class="skeleton-line skeleton-line-sub"></div></div></div>
    <div class="skeleton-row"><div class="skeleton-row-img"></div><div class="skeleton-row-text"><div class="skeleton-line skeleton-line-title"></div><div class="skeleton-line skeleton-line-sub"></div></div></div>
</div>

{* FAB — Add Category *}
<button type="button" class="fab" id="addCategoryFab" title="Add Category">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
</button>

<input type="file" id="catImageInput" accept="image/*" style="display:none">
{/block}

{block name="extra_scripts"}
<script>
$(function() {
    var $list = $('#categoryList');
    var _allCats = [];
    var _tree = [];

    function loadCategories() {
        TinyShop.api('GET', '/api/categories').done(function(res) {
            _allCats = res.categories || [];
            _tree = res.tree || [];
            renderTree(_tree);
        }).fail(function() {
            $list.html('<div class="empty-state"><p>Failed to load categories.</p></div>');
        });
    }

    function catImg(c) {
        if (c.image_url) {
            return '<div class="category-row-img"><img src="' + escapeHtml(c.image_url) + '" alt=""></div>';
        }
        return '<div class="category-row-img category-row-img-empty">' +
            '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>' +
        '</div>';
    }

    function renderTree(tree) {
        if (tree.length === 0 && _allCats.length === 0) {
            $list.html(
                '<div class="empty-state">' +
                    '<div class="empty-icon"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#AEAEB2" stroke-width="1.5"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg></div>' +
                    '<h2>No categories yet</h2>' +
                    '<p>Tap + to create your first category</p>' +
                '</div>'
            );
            return;
        }

        var html = '';
        tree.forEach(function(parent) {
            var childCount = (parent.children || []).length;
            // Parent row
            html += '<div class="category-group">';
            html += '<div class="category-row category-row-parent" data-id="' + parent.id + '">';
            html += '  <div class="category-row-left">';
            html += catImg(parent);
            html += '    <div class="category-row-info">';
            html += '      <span class="category-row-name">' + escapeHtml(parent.name) + '</span>';
            if (childCount > 0) {
                html += '    <span class="category-row-count">' + childCount + ' sub-categor' + (childCount === 1 ? 'y' : 'ies') + '</span>';
            }
            html += '    </div>';
            html += '  </div>';
            html += '  <svg class="category-row-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="9 6 15 12 9 18"/></svg>';
            html += '</div>';

            // Sub-categories
            html += '<div class="category-subcats">';
            (parent.children || []).forEach(function(child) {
                html += '<div class="category-row category-row-child" data-id="' + child.id + '">';
                html += '  <div class="category-row-left">';
                html += catImg(child);
                html += '    <span class="category-row-name">' + escapeHtml(child.name) + '</span>';
                html += '  </div>';
                html += '  <svg class="category-row-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="9 6 15 12 9 18"/></svg>';
                html += '</div>';
            });
            html += '<button type="button" class="category-add-sub" data-parent-id="' + parent.id + '" data-parent-name="' + escapeHtml(parent.name) + '">' +
                '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>' +
                ' Add sub-category' +
            '</button>';
            html += '</div>';
            html += '</div>';
        });
        $list.html(html);
    }

    function openCategoryModal(title, cat, parentId) {
        var isEdit = !!cat;
        var imgUrl = (cat && cat.image_url) || '';
        var name = (cat && cat.name) || '';

        var html = '<form id="catModalForm" autocomplete="off">' +
            '<div class="form-group">' +
                '<div class="cat-img-upload" id="catImgZone">' +
                    '<div class="cat-img-preview" id="catImgPreview" ' + (imgUrl ? '' : 'style="display:none"') + '>' +
                        '<img src="' + escapeHtml(imgUrl) + '" alt="" id="catImgTag">' +
                        '<div class="cat-img-overlay"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg> Change</div>' +
                        '<button type="button" class="cat-img-remove" id="catImgRemove">&times;</button>' +
                    '</div>' +
                    '<div class="cat-img-empty" id="catImgPlaceholder" ' + (imgUrl ? 'style="display:none"' : '') + '>' +
                        '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>' +
                        '<span>Tap to add an image</span>' +
                    '</div>' +
                '</div>' +
                '<input type="hidden" id="catImgUrl" value="' + escapeHtml(imgUrl) + '">' +
            '</div>' +
            '<div class="form-group">' +
                '<label for="catName">Category Name</label>' +
                '<input type="text" class="form-control" id="catName" value="' + escapeHtml(name) + '" placeholder="e.g. Accessories" required autofocus autocomplete="off">' +
            '</div>';

        // Parent selector — only show when editing a sub-category
        if (isEdit && cat.parent_id) {
            var currentParent = _tree.find(function(p) { return String(p.id) === String(cat.parent_id); });
            var currentParentName = currentParent ? currentParent.name : 'Unknown';
            var currentParentImg = currentParent && currentParent.image_url ? currentParent.image_url : '';

            html += '<div class="form-group">' +
                '<label>Parent Category</label>' +
                '<input type="hidden" id="catParent" value="' + cat.parent_id + '">' +
                '<div class="cat-parent-picker" id="catParentBtn">' +
                    '<div class="cat-parent-picker-left">' +
                        (currentParentImg
                            ? '<img class="cat-parent-picker-img" src="' + escapeHtml(currentParentImg) + '" alt="">'
                            : '<div class="cat-parent-picker-img cat-parent-picker-img-empty"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg></div>'
                        ) +
                        '<span id="catParentLabel">' + escapeHtml(currentParentName) + '</span>' +
                    '</div>' +
                    '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>' +
                '</div>' +
                '<div class="cat-parent-list" id="catParentList" style="display:none">';

            _tree.forEach(function(p) {
                var isSelected = String(cat.parent_id) === String(p.id);
                var pImg = p.image_url
                    ? '<img class="cat-parent-list-img" src="' + escapeHtml(p.image_url) + '" alt="">'
                    : '<div class="cat-parent-list-img cat-parent-list-img-empty"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg></div>';
                html += '<div class="cat-parent-list-item' + (isSelected ? ' selected' : '') + '" data-id="' + p.id + '" data-name="' + escapeHtml(p.name) + '" data-img="' + escapeHtml(p.image_url || '') + '">' +
                    '<div class="cat-parent-list-item-left">' + pImg + '<span>' + escapeHtml(p.name) + '</span></div>' +
                    '<span class="cat-parent-list-check"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg></span>' +
                '</div>';
            });

            html += '</div></div>';
        }

        html += '<button type="submit" class="btn btn-primary" id="catSaveBtn" style="width:100%;min-height:52px;font-size:1rem;font-weight:600;border-radius:14px;border:none;cursor:pointer;font-family:inherit;">' + (isEdit ? 'Save Changes' : 'Add Category') + '</button>' +
            (isEdit ? '<button type="button" id="catDeleteBtn" style="width:100%;min-height:48px;font-size:0.9375rem;font-weight:600;border-radius:14px;background:transparent;color:#FF3B30;border:none;cursor:pointer;font-family:inherit;margin-top:12px;">Delete Category</button>' : '') +
        '</form>';

        TinyShop.openModal(title, html);

        // Image upload
        $('#catImgZone').on('click', function(e) {
            if ($(e.target).closest('#catImgRemove').length) return;
            $('#catImageInput').click();
        });

        $('#catImageInput').off('change').on('change', function() {
            var file = this.files[0];
            if (!file) return;
            TinyShop.uploadFile(file, function(url) {
                $('#catImgUrl').val(url);
                $('#catImgTag').attr('src', url);
                $('#catImgPreview').show();
                $('#catImgPlaceholder').hide();
            });
            this.value = '';
        });

        $('#catImgRemove').on('click', function(e) {
            e.stopPropagation();
            $('#catImgUrl').val('');
            $('#catImgPreview').hide();
            $('#catImgPlaceholder').show();
        });

        // Parent category picker toggle & selection
        $('#catParentBtn').on('click', function() {
            var $list = $('#catParentList');
            var $arrow = $(this).find('svg:last');
            $list.slideToggle(200);
            $arrow.css('transform', $list.is(':visible') ? '' : 'rotate(180deg)');
        });
        $('#catParentList').on('click', '.cat-parent-list-item', function() {
            var id = $(this).data('id');
            var name = $(this).data('name');
            var img = $(this).data('img');
            $('#catParent').val(id);
            // Update button display
            var imgHtml = img
                ? '<img class="cat-parent-picker-img" src="' + escapeHtml(img) + '" alt="">'
                : '<div class="cat-parent-picker-img cat-parent-picker-img-empty"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg></div>';
            $('#catParentBtn .cat-parent-picker-left').html(imgHtml + '<span id="catParentLabel">' + escapeHtml(name) + '</span>');
            // Update selection state
            $('#catParentList .cat-parent-list-item').removeClass('selected');
            $(this).addClass('selected');
            // Collapse list
            $('#catParentList').slideUp(200);
            $('#catParentBtn svg:last').css('transform', '');
        });

        // Delete (inside edit modal)
        if (isEdit) {
            $('#catDeleteBtn').on('click', function() {
                var childCount = (cat.children || []).length;
                var warnText = childCount > 0
                    ? 'Delete "' + escapeHtml(cat.name) + '" and its ' + childCount + ' sub-categor' + (childCount === 1 ? 'y' : 'ies') + '? Products will just have no category.'
                    : 'Delete "' + escapeHtml(cat.name) + '"? Products in this category won\'t be deleted, they\'ll just have no category.';

                var html = '<p style="margin-bottom:20px;color:var(--color-text-muted);font-size:0.9rem;">' + warnText + '</p>' +
                    '<div style="display:flex;gap:10px">' +
                        '<button type="button" id="catDelCancel" style="flex:1;min-height:48px;font-size:1rem;font-weight:600;border-radius:12px;background:var(--color-bg-secondary);color:var(--color-text);border:none;cursor:pointer;font-family:inherit;">Cancel</button>' +
                        '<button type="button" id="catDelConfirm" style="flex:1;min-height:48px;font-size:1rem;font-weight:600;border-radius:12px;background:#FF3B30;color:#fff;border:none;cursor:pointer;font-family:inherit;">Delete</button>' +
                    '</div>';
                TinyShop.openModal('Delete Category?', html);

                $('#catDelCancel').on('click', function() { TinyShop.closeModal(); });
                $('#catDelConfirm').on('click', function() {
                    $(this).prop('disabled', true).text('Deleting...');
                    TinyShop.api('DELETE', '/api/categories/' + cat.id).done(function() {
                        TinyShop.toast('Category deleted');
                        TinyShop.closeModal();
                        loadCategories();
                    }).fail(function() {
                        TinyShop.toast('Failed to delete', 'error');
                        TinyShop.closeModal();
                    });
                });
            });
        }

        // Submit
        $('#catModalForm').on('submit', function(e) {
            e.preventDefault();
            var catName = $('#catName').val().trim();
            if (!catName) return;

            var $btn = $('#catSaveBtn').prop('disabled', true).text('Saving...');
            var payload = {
                name: catName,
                image_url: $('#catImgUrl').val() || null
            };

            // Set parent_id
            if (parentId) {
                payload.parent_id = parentId;
            }
            if (isEdit && $('#catParent').length) {
                payload.parent_id = $('#catParent').val();
            }

            if (isEdit) {
                TinyShop.api('PUT', '/api/categories/' + cat.id, payload).done(function() {
                    TinyShop.toast('Category updated');
                    TinyShop.closeModal();
                    loadCategories();
                }).fail(function(xhr) {
                    var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to update';
                    TinyShop.toast(msg, 'error');
                    $btn.prop('disabled', false).text('Save Changes');
                });
            } else {
                TinyShop.api('POST', '/api/categories', payload).done(function() {
                    TinyShop.toast('Category added');
                    TinyShop.closeModal();
                    loadCategories();
                }).fail(function(xhr) {
                    var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to add';
                    TinyShop.toast(msg, 'error');
                    $btn.prop('disabled', false).text('Add Category');
                });
            }
        });
    }

    // FAB — add top-level category
    $('#addCategoryFab').on('click', function() {
        openCategoryModal('New Category', null, null);
    });

    // Tap parent/child row to edit
    $list.on('click', '.category-row', function(e) {
        var id = $(this).data('id');
        // Find cat in flat list
        var cat = _allCats.find(function(c) { return c.id == id; });
        if (!cat) return;
        // If it's a parent, attach children for the delete warning
        if (!cat.parent_id) {
            var treeItem = _tree.find(function(t) { return t.id == id; });
            if (treeItem) cat.children = treeItem.children;
        }
        var title = cat.parent_id ? 'Edit Sub-category' : 'Edit Category';
        openCategoryModal(title, cat, null);
    });

    // Add sub-category button
    $list.on('click', '.category-add-sub', function(e) {
        e.stopPropagation();
        var parentId = $(this).data('parent-id');
        var parentName = $(this).data('parent-name');
        openCategoryModal('New Sub-category in ' + parentName, null, parentId);
    });

    loadCategories();
});
</script>
{/block}
