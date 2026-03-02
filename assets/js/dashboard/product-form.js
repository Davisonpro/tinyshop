/**
 * Product form page (add / edit).
 *
 * Handles image gallery with drag-to-reorder, category picker,
 * variations editor, SEO fields, localStorage draft persistence,
 * unsaved-changes warning, and form submission.
 *
 * @since 1.0.0
 */
TinyShop.initProductForm = function() {
    var $form = $('#productForm');
    if (!$form.length || typeof _productFormConfig === 'undefined') return;

    // Prevent duplicate bindings on SPA re-navigation
    if ($form.data('initialized')) return;
    $form.data('initialized', true);

    var isEdit = _productFormConfig.isEdit;
    var productId = _productFormConfig.productId;
    var DRAFT_KEY = 'product_draft_new';

    // Initialize price formatting
    $('.price-input').each(function() {
        TinyShop.initPriceInput($(this));
    });

    // --- Track Stock Toggle ---
    $('#trackStock').on('change', function() {
        if ($(this).is(':checked')) {
            $('#stockQtyRow').show();
            $('#stockQuantity').focus();
        } else {
            $('#stockQtyRow').hide();
            $('#stockQuantity').val('');
        }
    });

    // --- Image Gallery ---
    var $gallery = $('#imageGallery');
    var $addBtn = $('#addImageBtn');
    var $fileInput = $('#imageInput');

    /** Collect all image URLs from the gallery. */
    function getImageUrls() {
        var urls = [];
        $gallery.find('.image-gallery-item').each(function() {
            urls.push($(this).data('url'));
        });
        return urls;
    }

    /** Append a fully-uploaded image to the gallery. */
    function addImageToGallery(url) {
        var $item = $('<div class="image-gallery-item" draggable="true" data-url="' + TinyShop.escapeHtml(url) + '">' +
            '<img src="' + TinyShop.escapeHtml(url) + '" alt="">' +
            '<button type="button" class="image-gallery-remove">&times;</button>' +
        '</div>');
        $addBtn.before($item);
        bindDrag($item[0]);
        saveDraft();
    }

    /** Show a local preview placeholder while uploading. */
    function addImagePlaceholder(file) {
        var $item = $('<div class="image-gallery-item image-gallery-uploading">' +
            '<div class="image-gallery-preview-img"></div>' +
            '<div class="image-gallery-loader"><span class="btn-spinner"></span></div>' +
        '</div>');
        var reader = new FileReader();
        reader.onload = function(e) {
            $item.find('.image-gallery-preview-img').css('background-image', 'url(' + e.target.result + ')');
        };
        reader.readAsDataURL(file);
        $addBtn.before($item);
        return $item;
    }

    $addBtn.on('click', function() {
        $fileInput.click();
    });

    $fileInput.on('change', function() {
        var files = this.files;
        if (!files.length) return;
        for (var i = 0; i < files.length; i++) {
            (function(file) {
                var $placeholder = addImagePlaceholder(file);
                TinyShop.uploadFile(file, function(url) {
                    var $item = $('<div class="image-gallery-item" draggable="true" data-url="' + TinyShop.escapeHtml(url) + '">' +
                        '<img src="' + TinyShop.escapeHtml(url) + '" alt="">' +
                        '<button type="button" class="image-gallery-remove">&times;</button>' +
                    '</div>');
                    $placeholder.replaceWith($item);
                    bindDrag($item[0]);
                    saveDraft();
                }, function() {
                    $placeholder.remove();
                });
            })(files[i]);
        }
        this.value = '';
    });

    $gallery.on('click', '.image-gallery-remove', function(e) {
        e.preventDefault();
        $(this).closest('.image-gallery-item').remove();
        saveDraft();
    });

    // --- Drag to Reorder (desktop + touch) ---
    var _dragItem = null;

    /** Bind desktop drag and touch reorder handlers to a gallery item. */
    function bindDrag(el) {
        el.addEventListener('dragstart', function(e) {
            _dragItem = el;
            el.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        });
        el.addEventListener('dragend', function() {
            el.classList.remove('dragging');
            _dragItem = null;
            $gallery.find('.drag-over').removeClass('drag-over');
            saveDraft();
        });
        el.addEventListener('dragover', function(e) {
            e.preventDefault();
            if (_dragItem && _dragItem !== el) {
                el.classList.add('drag-over');
            }
        });
        el.addEventListener('dragleave', function() {
            el.classList.remove('drag-over');
        });
        el.addEventListener('drop', function(e) {
            e.preventDefault();
            el.classList.remove('drag-over');
            if (_dragItem && _dragItem !== el) {
                var items = Array.from($gallery[0].querySelectorAll('.image-gallery-item'));
                var fromIdx = items.indexOf(_dragItem);
                var toIdx = items.indexOf(el);
                if (fromIdx < toIdx) {
                    el.parentNode.insertBefore(_dragItem, el.nextSibling);
                } else {
                    el.parentNode.insertBefore(_dragItem, el);
                }
            }
        });

        var _touchStartY = 0;
        var _touchStartX = 0;

        el.addEventListener('touchstart', function(e) {
            if (e.target.closest('.image-gallery-remove')) return;
            _touchStartX = e.touches[0].clientX;
            _touchStartY = e.touches[0].clientY;
            _dragItem = el;
            setTimeout(function() {
                if (_dragItem === el) el.classList.add('dragging');
            }, 150);
        }, { passive: true });

        el.addEventListener('touchmove', function(e) {
            if (!_dragItem || _dragItem !== el) return;
            var touch = e.touches[0];
            var dx = touch.clientX - _touchStartX;
            var dy = touch.clientY - _touchStartY;
            if (Math.abs(dx) > 5 || Math.abs(dy) > 5) {
                e.preventDefault();
            }
            var target = document.elementFromPoint(touch.clientX, touch.clientY);
            if (target) target = target.closest('.image-gallery-item');
            $gallery.find('.drag-over').removeClass('drag-over');
            if (target && target !== el) {
                target.classList.add('drag-over');
            }
        }, { passive: false });

        el.addEventListener('touchend', function() {
            if (!_dragItem || _dragItem !== el) return;
            el.classList.remove('dragging');
            var $over = $gallery.find('.drag-over');
            if ($over.length) {
                var overEl = $over[0];
                $over.removeClass('drag-over');
                var items = Array.from($gallery[0].querySelectorAll('.image-gallery-item'));
                var fromIdx = items.indexOf(el);
                var toIdx = items.indexOf(overEl);
                if (fromIdx < toIdx) {
                    overEl.parentNode.insertBefore(el, overEl.nextSibling);
                } else {
                    overEl.parentNode.insertBefore(el, overEl);
                }
            }
            _dragItem = null;
            saveDraft();
        });
    }

    $gallery.find('.image-gallery-item').each(function() {
        this.setAttribute('draggable', 'true');
        bindDrag(this);
    });

    // --- Category Picker (bottom sheet) ---
    var _categoryTree = _productFormConfig.categoryTree || [];

    /** Open the category picker modal with search. */
    function openCategoryPicker() {
        var currentVal = $('#productCategory').val();
        var html = '<div class="category-picker-search">' +
            '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>' +
            '<input type="text" id="categorySearchInput" placeholder="Search categories..." autocomplete="off">' +
        '</div>';
        html += '<div class="category-picker-list">';

        html += '<div class="category-picker-none' + (!currentVal ? ' selected' : '') + '" data-id="">' +
            'No category' +
        '</div>';

        _categoryTree.forEach(function(parent) {
            html += '<div class="category-picker-group" data-search-parent="' + TinyShop.escapeHtml(parent.name.toLowerCase()) + '">';
            html += '<div class="category-picker-item category-picker-item-parent' + (String(parent.id) === String(currentVal) ? ' selected' : '') + '" data-id="' + parent.id + '" data-search-name="' + TinyShop.escapeHtml(parent.name.toLowerCase()) + '">' +
                '<span>' + TinyShop.escapeHtml(parent.name) + '</span>' +
                '<span class="category-picker-check"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg></span>' +
            '</div>';
            (parent.children || []).forEach(function(child) {
                html += '<div class="category-picker-item category-picker-item-child' + (String(child.id) === String(currentVal) ? ' selected' : '') + '" data-id="' + child.id + '" data-search-name="' + TinyShop.escapeHtml(child.name.toLowerCase()) + '">' +
                    '<span>' + TinyShop.escapeHtml(child.name) + '</span>' +
                    '<span class="category-picker-check"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg></span>' +
                '</div>';
            });
            html += '</div>';
        });
        html += '</div>';

        TinyShop.openModal('Select Category', html);

        var _searchTimer;
        $('#categorySearchInput').on('input', function() {
            var q = $(this).val().trim().toLowerCase();
            clearTimeout(_searchTimer);
            _searchTimer = setTimeout(function() {
                var $list = $('#modalBody .category-picker-list');
                if (!q) {
                    $list.find('.category-picker-group, .category-picker-item, .category-picker-none').show();
                    return;
                }
                $list.find('.category-picker-none').hide();
                $list.find('.category-picker-group').each(function() {
                    var $group = $(this);
                    var parentMatch = $group.find('.category-picker-item-parent').data('search-name').indexOf(q) !== -1;
                    var anyChildMatch = false;
                    $group.find('.category-picker-item-child').each(function() {
                        var match = $(this).data('search-name').indexOf(q) !== -1;
                        $(this).toggle(match || parentMatch);
                        if (match) anyChildMatch = true;
                    });
                    $group.find('.category-picker-item-parent').toggle(parentMatch || anyChildMatch);
                    $group.toggle(parentMatch || anyChildMatch);
                });
            }, 100);
        }).focus();

        $('#modalBody').on('click', '.category-picker-item, .category-picker-none', function() {
            var id = $(this).data('id');
            var name = $(this).find('span:first').text().trim() || '';
            $('#productCategory').val(id || '');
            if (id) {
                $('#categoryPickerLabel').text(name).removeClass('picker-placeholder');
            } else {
                $('#categoryPickerLabel').text('Select a category').addClass('picker-placeholder');
            }
            TinyShop.closeModal();
            saveDraft();
        });
    }

    $('#openCategoryPicker').on('click', function() {
        openCategoryPicker();
    });

    // --- Category inline add (modal) ---
    $('#addCategoryBtn').on('click', function() {
        var html = '<form id="newCategoryForm" autocomplete="off">' +
            '<div class="form-group">' +
                '<label for="newCategoryName">Category Name</label>' +
                '<input type="text" class="form-control" id="newCategoryName" placeholder="e.g. Accessories" required autofocus autocomplete="off">' +
            '</div>' +
            '<button type="submit" class="btn btn-primary" id="saveCategoryBtn" style="width:100%;min-height:52px;font-size:1rem;font-weight:600;border-radius:14px;border:none;cursor:pointer;font-family:inherit;">Add Category</button>' +
        '</form>';
        TinyShop.openModal('New Category', html);

        $('#newCategoryForm').on('submit', function(e) {
            e.preventDefault();
            var name = $('#newCategoryName').val().trim();
            if (!name) return;
            var $btn = $('#saveCategoryBtn').prop('disabled', true).text('Adding...');
            TinyShop.api('POST', '/api/categories', { name: name }).done(function(res) {
                var cat = res.category;
                _categoryTree.push({ id: cat.id, name: cat.name, children: [] });
                $('#productCategory').val(cat.id);
                $('#categoryPickerLabel').text(cat.name).removeClass('picker-placeholder');
                TinyShop.toast('Category added');
                TinyShop.closeModal();
                saveDraft();
            }).fail(function(xhr) {
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to add category';
                TinyShop.toast(msg, 'error');
                $btn.prop('disabled', false).text('Add Category');
            });
        });
    });

    // --- Variations Editor ---
    var $varGroups = $('#variationGroups');
    var _varCounter = 0;

    /** Collect all variation groups and their options from the DOM. */
    function getVariations() {
        var groups = [];
        $varGroups.find('.variation-group').each(function() {
            var name = $(this).find('.variation-group-name').val().trim();
            var opts = [];
            $(this).find('.variation-option-row').each(function() {
                var val = $(this).find('.variation-option-value').val().trim();
                var priceStr = $(this).find('.variation-option-price').val().replace(/,/g, '').trim();
                if (val) {
                    var opt = { value: val };
                    if (priceStr !== '') opt.price = parseFloat(priceStr);
                    opts.push(opt);
                }
            });
            if (name && opts.length > 0) {
                groups.push({ name: name, options: opts });
            }
        });
        return groups;
    }

    /** Build the HTML for a single variation option row. */
    function buildOptionRow(value, price) {
        var priceVal = (price !== null && price !== undefined) ? price : '';
        return '<div class="variation-option-row">' +
            '<input type="text" class="variation-option-value" placeholder="Value name" value="' + TinyShop.escapeHtml(value || '') + '" autocomplete="off">' +
            '<input type="text" class="variation-option-price price-input" placeholder="Price" inputmode="decimal" value="' + TinyShop.escapeHtml(String(priceVal)) + '" autocomplete="off">' +
            '<button type="button" class="variation-option-remove" title="Remove">&times;</button>' +
        '</div>';
    }

    /** Add a complete variation group to the editor. */
    function addVariationGroup(name, options) {
        var gid = _varCounter++;
        var html = '<div class="variation-group" data-gid="' + gid + '">' +
            '<div class="variation-group-header">' +
                '<input type="text" class="variation-group-name" placeholder="Option name (e.g. Size)" value="' + TinyShop.escapeHtml(name || '') + '" autocomplete="off">' +
                '<button type="button" class="variation-group-remove" title="Remove">&times;</button>' +
            '</div>' +
            '<div class="variation-options">';
        if (options && options.length) {
            options.forEach(function(opt) {
                if (typeof opt === 'string') {
                    html += buildOptionRow(opt, null);
                } else {
                    html += buildOptionRow(opt.value, opt.price);
                }
            });
        }
        html += '</div>' +
            '<button type="button" class="variation-add-value">+ Add value</button>' +
            '</div>';
        $varGroups.append(html);
        if (!options || !options.length) {
            $varGroups.find('.variation-group[data-gid="' + gid + '"] .variation-options').append(buildOptionRow('', null));
        }
        $varGroups.find('.variation-group[data-gid="' + gid + '"] .price-input').each(function() {
            TinyShop.initPriceInput($(this));
        });
        $varGroups.find('.variation-group[data-gid="' + gid + '"] .variation-option-value').first().focus();
    }

    $('#addVariationGroup').on('click', function() {
        addVariationGroup('', []);
        saveDraft();
    });

    $varGroups.on('click', '.variation-group-remove', function() {
        $(this).closest('.variation-group').remove();
        saveDraft();
    });

    $varGroups.on('click', '.variation-option-remove', function() {
        $(this).closest('.variation-option-row').remove();
        saveDraft();
    });

    $varGroups.on('click', '.variation-add-value', function() {
        var $options = $(this).siblings('.variation-options');
        $options.append(buildOptionRow('', null));
        TinyShop.initPriceInput($options.find('.price-input').last());
        $options.find('.variation-option-value').last().focus();
        saveDraft();
    });

    $varGroups.on('keydown', '.variation-option-value', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            var $row = $(this).closest('.variation-option-row');
            var $group = $row.closest('.variation-group');
            if ($row.is(':last-child') && $(this).val().trim()) {
                var $options = $group.find('.variation-options');
                $options.append(buildOptionRow('', null));
                $options.find('.variation-option-value').last().focus();
            }
            saveDraft();
        }
    });

    $varGroups.on('input', '.variation-group-name, .variation-option-value, .variation-option-price', function() {
        saveDraft();
    });

    if (_productFormConfig.variations && _productFormConfig.variations.length) {
        _productFormConfig.variations.forEach(function(g) {
            addVariationGroup(g.name, g.options);
        });
    }

    // --- SEO Toggle ---
    var $seoToggle = $('#seoToggle');
    var $seoFields = $('#seoFields');
    var $metaTitleInput = $('#metaTitle');
    var $metaDescInput = $('#metaDescription');

    $seoToggle.on('click', function() {
        var isOpen = $seoFields.is(':visible');
        $seoFields.slideToggle(200);
        $(this).toggleClass('open', !isOpen);
    });

    if ($metaTitleInput.val() || $metaDescInput.val()) {
        $seoFields.show();
        $seoToggle.addClass('open');
    }

    /** Update the character count labels for SEO fields. */
    function updateSeoCounters() {
        $('#metaTitleCount').text($metaTitleInput.val().length);
        $('#metaDescCount').text($metaDescInput.val().length);
    }
    $metaTitleInput.on('input', updateSeoCounters);
    $metaDescInput.on('input', updateSeoCounters);
    updateSeoCounters();

    // --- localStorage Draft (add mode only) ---
    var _draftTimer;

    /** Debounced save of the current form state to localStorage. */
    function saveDraft() {
        if (isEdit) return;
        clearTimeout(_draftTimer);
        _draftTimer = setTimeout(function() {
            var draft = {
                name: $('#productName').val(),
                price: $('#productPrice').val().replace(/,/g, ''),
                compare_price: $('#productComparePrice').val().replace(/,/g, ''),
                full_description: $('#productDesc').val(),
                description: $('#productShortDesc').val(),
                category_id: $('#productCategory').val(),
                images: getImageUrls(),
                variations: getVariations(),
                meta_title: $metaTitleInput.val(),
                meta_description: $metaDescInput.val()
            };
            try { localStorage.setItem(DRAFT_KEY, JSON.stringify(draft)); } catch(e) {}
        }, 500);
    }

    /** Restore a previously saved draft from localStorage. */
    function restoreDraft() {
        if (isEdit) return;
        try {
            var raw = localStorage.getItem(DRAFT_KEY);
            if (!raw) return;
            var draft = JSON.parse(raw);
            if (draft.name) $('#productName').val(draft.name);
            if (draft.price) { $('#productPrice').val(draft.price).trigger('input'); }
            if (draft.compare_price) { $('#productComparePrice').val(draft.compare_price).trigger('input'); }
            if (draft.full_description) {
                $('#productDesc').val(draft.full_description);
                if (window._setEditorContent) window._setEditorContent(draft.full_description);
            }
            if (draft.description) {
                $('#productShortDesc').val(draft.description);
                if (window._setShortDescContent) window._setShortDescContent(draft.description);
            }
            if (draft.category_id) {
                $('#productCategory').val(draft.category_id);
                _categoryTree.forEach(function(p) {
                    if (String(p.id) === String(draft.category_id)) { $('#categoryPickerLabel').text(p.name).removeClass('picker-placeholder'); }
                    (p.children || []).forEach(function(c) {
                        if (String(c.id) === String(draft.category_id)) { $('#categoryPickerLabel').text(c.name).removeClass('picker-placeholder'); }
                    });
                });
            }
            if (draft.images && draft.images.length) {
                draft.images.forEach(function(url) {
                    addImageToGallery(url);
                });
            }
            if (draft.variations && draft.variations.length) {
                draft.variations.forEach(function(g) {
                    addVariationGroup(g.name, g.options);
                });
            }
            if (draft.meta_title) { $metaTitleInput.val(draft.meta_title); $seoFields.show(); $seoToggle.addClass('open'); }
            if (draft.meta_description) { $metaDescInput.val(draft.meta_description); $seoFields.show(); $seoToggle.addClass('open'); }
            updateSeoCounters();
            TinyShop.toast('Draft restored');
        } catch(e) {}
    }

    /** Remove the saved draft from localStorage. */
    function clearDraft() {
        try { localStorage.removeItem(DRAFT_KEY); } catch(e) {}
    }

    // --- Unsaved Changes Warning ---
    var _formDirty = false;
    function markDirty() { _formDirty = true; }
    function markClean() { _formDirty = false; window.removeEventListener('beforeunload', _beforeUnload); }
    function _beforeUnload(e) { if (_formDirty) { e.preventDefault(); e.returnValue = ''; } }

    $form.on('input change', 'input, textarea, select', function() {
        saveDraft();
        markDirty();
    });

    document.querySelectorAll('.rich-editor-content').forEach(function(el) {
        el.addEventListener('input', function() { saveDraft(); markDirty(); });
    });

    var _origAddImage = addImageToGallery;
    addImageToGallery = function(url) { _origAddImage(url); markDirty(); };

    $form.on('input change', function() {
        if (_formDirty) window.addEventListener('beforeunload', _beforeUnload);
    });

    restoreDraft();

    // --- Delete Product (edit mode) ---
    if (isEdit) {
        $('#deleteProductBtn').on('click', function() {
            TinyShop.confirm('Delete Product?', 'This will permanently delete this product and all its images. This cannot be undone.', 'Delete', function() {
                $('#confirmModalOk').prop('disabled', true).text('Deleting...');
                TinyShop.api('DELETE', '/api/products/' + productId).done(function() {
                    markClean();
                    TinyShop.toast('Product deleted');
                    TinyShop.closeModal();
                    TinyShop.navigate('/dashboard/products');
                }).fail(function() {
                    TinyShop.toast('Failed to delete', 'error');
                    TinyShop.closeModal();
                });
            }, 'danger');
        });
    }

    // --- Form Submit ---
    $form.on('submit', function(e) {
        e.preventDefault();
        var $btn = $('#saveProductBtn').prop('disabled', true).html('<span class="btn-spinner"></span> Saving...');

        var priceRaw = $('#productPrice').val().replace(/,/g, '');
        var compareRaw = $('#productComparePrice').val().replace(/,/g, '');
        var variations = getVariations();
        var stockQty = null;
        if ($('#trackStock').is(':checked')) {
            var qtyVal = $('#stockQuantity').val();
            stockQty = qtyVal !== '' ? parseInt(qtyVal, 10) : 0;
            if (isNaN(stockQty) || stockQty < 0) stockQty = 0;
        }

        var payload = {
            name: $('#productName').val(),
            price: parseFloat(priceRaw),
            compare_price: compareRaw !== '' ? parseFloat(compareRaw) : null,
            description: $('#productShortDesc').val().trim(),
            full_description: $('#productDesc').val(),
            category_id: $('#productCategory').val() || null,
            images: getImageUrls(),
            is_sold: $('#productSold').is(':checked') ? 1 : 0,
            stock_quantity: stockQty,
            is_featured: $('#productFeatured').is(':checked') ? 1 : 0,
            is_active: $('#productActive').length ? ($('#productActive').is(':checked') ? 1 : 0) : 1,
            variations: variations.length > 0 ? variations : null,
            meta_title: $metaTitleInput.val().trim() || null,
            meta_description: $metaDescInput.val().trim() || null
        };

        var method = isEdit ? 'PUT' : 'POST';
        var url = isEdit ? '/api/products/' + productId : '/api/products';

        TinyShop.api(method, url, payload).done(function() {
            markClean();
            clearDraft();
            TinyShop.toast(isEdit ? 'Product saved!' : 'Product added!');
            setTimeout(function() {
                TinyShop.navigate('/dashboard/products');
            }, 600);
        }).fail(function(xhr) {
            var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to save';
            TinyShop.toast(msg, 'error');
            $btn.prop('disabled', false).text(isEdit ? 'Save Changes' : 'Add Product');
        });
    });
};
