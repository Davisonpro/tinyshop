{extends file="layouts/dashboard.tpl"}

{block name="content"}
<div class="dash-topbar">
    <a href="/dashboard/products" class="dash-topbar-back">
        <i class="fa-solid fa-chevron-left"></i>
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
    <i class="fa-solid fa-plus"></i>
</button>

<input type="file" id="catImageInput" accept="image/*" class="d-none">
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
            '<i class="fa-solid fa-folder icon-lg"></i>' +
        '</div>';
    }

    function renderTree(tree) {
        if (tree.length === 0 && _allCats.length === 0) {
            $list.html(
                '<div class="empty-state">' +
                    '<div class="empty-icon"><i class="fa-solid fa-folder icon-2xl text-muted"></i></div>' +
                    '<h2>Organize your products</h2>' +
                    '<p>Create categories to help customers browse your shop</p>' +
                    '<button class="empty-state-btn" onclick="document.querySelector(\'.fab\').click()">Create category</button>' +
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
            html += '  <i class="fa-solid fa-chevron-right category-row-chevron"></i>';
            html += '</div>';

            // Sub-categories
            html += '<div class="category-subcats">';
            (parent.children || []).forEach(function(child) {
                html += '<div class="category-row category-row-child" data-id="' + child.id + '">';
                html += '  <div class="category-row-left">';
                html += catImg(child);
                html += '    <span class="category-row-name">' + escapeHtml(child.name) + '</span>';
                html += '  </div>';
                html += '  <i class="fa-solid fa-chevron-right category-row-chevron icon-sm"></i>';
                html += '</div>';
            });
            html += '<button type="button" class="category-add-sub" data-parent-id="' + parent.id + '" data-parent-name="' + escapeHtml(parent.name) + '">' +
                '<i class="fa-solid fa-plus icon-sm"></i>' +
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
                        '<div class="cat-img-overlay"><i class="fa-solid fa-camera icon-lg"></i> Change</div>' +
                        '<button type="button" class="cat-img-remove" id="catImgRemove">&times;</button>' +
                    '</div>' +
                    '<div class="cat-img-empty" id="catImgPlaceholder" ' + (imgUrl ? 'style="display:none"' : '') + '>' +
                        '<i class="fa-solid fa-camera icon-2xl"></i>' +
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
                            : '<div class="cat-parent-picker-img cat-parent-picker-img-empty"><i class="fa-solid fa-folder icon-md"></i></div>'
                        ) +
                        '<span id="catParentLabel">' + escapeHtml(currentParentName) + '</span>' +
                    '</div>' +
                    '<i class="fa-solid fa-chevron-down"></i>' +
                '</div>' +
                '<div class="cat-parent-list" id="catParentList" style="display:none">';

            _tree.forEach(function(p) {
                var isSelected = String(cat.parent_id) === String(p.id);
                var pImg = p.image_url
                    ? '<img class="cat-parent-list-img" src="' + escapeHtml(p.image_url) + '" alt="">'
                    : '<div class="cat-parent-list-img cat-parent-list-img-empty"><i class="fa-solid fa-folder icon-sm"></i></div>';
                html += '<div class="cat-parent-list-item' + (isSelected ? ' selected' : '') + '" data-id="' + p.id + '" data-name="' + escapeHtml(p.name) + '" data-img="' + escapeHtml(p.image_url || '') + '">' +
                    '<div class="cat-parent-list-item-left">' + pImg + '<span>' + escapeHtml(p.name) + '</span></div>' +
                    '<span class="cat-parent-list-check"><i class="fa-solid fa-check icon-sm"></i></span>' +
                '</div>';
            });

            html += '</div></div>';
        }

        html += '<button type="submit" class="btn btn-block btn-primary" id="catSaveBtn">' + (isEdit ? 'Save Changes' : 'Add Category') + '</button>' +
            (isEdit ? '<button type="button" id="catDeleteBtn" class="btn btn-block btn-link mt-sm" style="color:#FF3B30">Delete Category</button>' : '') +
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
                : '<div class="cat-parent-picker-img cat-parent-picker-img-empty"><i class="fa-solid fa-folder icon-md"></i></div>';
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
