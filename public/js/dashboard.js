/**
 * TinyShop — Dashboard JS
 * Product list, product form page, image uploads, categories, localStorage draft.
 * SPA-like navigation with progress bar.
 */
var TinyShop = window.TinyShop || {};

/* ============================================================
   API Helper
   ============================================================ */
TinyShop.api = function(method, url, data) {
    var opts = {
        method: method,
        url: url,
        dataType: 'json'
    };
    if (data && method !== 'GET') {
        opts.contentType = 'application/json';
        opts.data = JSON.stringify(data);
    }
    return $.ajax(opts);
};

/* ============================================================
   File Upload
   ============================================================ */
TinyShop.uploadFile = function(file, onSuccess, onError) {
    var formData = new FormData();
    formData.append('file', file);
    $.ajax({
        url: '/api/upload',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res) {
            if (res.success && onSuccess) onSuccess(res.url);
        },
        error: function(xhr) {
            var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Upload failed';
            TinyShop.toast(msg, 'error');
            if (onError) onError(msg);
        }
    });
};

/* ============================================================
   Modal (Bottom Sheet) — kept for generic use
   ============================================================ */
TinyShop._previousFocus = null;

TinyShop.openModal = function(title, contentHtml) {
    TinyShop._previousFocus = document.activeElement;
    $('#modalTitle').text(title);
    $('#modalBody').html(contentHtml);
    $('#modal').addClass('active');
    // Focus first focusable element inside modal
    setTimeout(function() {
        var $focusable = $('#modalBody').find('input, button, select, textarea, a[href]').filter(':visible').first();
        if ($focusable.length) $focusable.focus();
        else $('#modalClose').focus();
    }, 100);
};

TinyShop.closeModal = function() {
    $('#modal').removeClass('active');
    setTimeout(function() { $('#modalBody').html(''); }, 300);
    // Restore focus to trigger element
    if (TinyShop._previousFocus) {
        try { TinyShop._previousFocus.focus(); } catch(e) {}
        TinyShop._previousFocus = null;
    }
};

$(function() {
    $('#modalClose, #modal').on('click', function(e) {
        if (e.target === this) TinyShop.closeModal();
    });

    // Escape key closes modal
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('#modal').hasClass('active')) {
            TinyShop.closeModal();
        }
    });

    // Focus trap: Tab cycles within modal when open
    $('#modal').on('keydown', function(e) {
        if (e.key !== 'Tab') return;
        var $focusable = $(this).find('input, button, select, textarea, a[href], [tabindex]:not([tabindex="-1"])').filter(':visible');
        if (!$focusable.length) return;
        var first = $focusable.first()[0];
        var last = $focusable.last()[0];
        if (e.shiftKey && document.activeElement === first) {
            e.preventDefault();
            last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault();
            first.focus();
        }
    });
});

/* ============================================================
   Escape HTML helper
   ============================================================ */
function escapeHtml(str) {
    if (!str) return '';
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

/* ============================================================
   Currency formatter
   ============================================================ */
TinyShop.formatPrice = function(amount, currency) {
    currency = currency || 'KES';
    var num = parseFloat(amount);
    if (isNaN(num)) return '0';

    // Currencies that don't use decimal places
    var noDecimals = ['KES','NGN','TZS','UGX','RWF','ETB','XOF','GHS'];
    var useDecimals = noDecimals.indexOf(currency) === -1;
    var formatted = useDecimals
        ? num.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')
        : Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');

    return currency + ' ' + formatted;
};

/* ============================================================
   Navigate helper — uses SPA when available, else location
   ============================================================ */
TinyShop.navigate = function(url) {
    if (TinyShop.spa && TinyShop.spa._ready) {
        TinyShop.spa.go(url);
    } else {
        window.location.href = url;
    }
};

/* ============================================================
   Product List Page (2-col card grid)
   ============================================================ */
TinyShop.initProductList = function() {
    var $grid = $('#productGrid');
    if (!$grid.length) return;

    var $filterBar = $('#categoryFilterBar');
    var $searchBar = $('#productSearchBar');
    var $searchInput = $('#productSearch');
    var $summary = $('#productListSummary');
    var $loadMore = $('#productLoadMore');
    var _allProducts = [];
    var _filteredProducts = [];
    var _activeFilter = null; // null = "All"
    var _searchQuery = '';
    var _currency = (typeof _productListConfig !== 'undefined' && _productListConfig.currency) ? _productListConfig.currency : 'KES';
    var PAGE_SIZE = 30;
    var _visibleCount = PAGE_SIZE;

    function loadProducts() {
        TinyShop.api('GET', '/api/products').done(function(res) {
            _allProducts = res.products || [];
            buildCategoryTabs(_allProducts);
            // Always show search bar if 3+ products
            if (_allProducts.length >= 3) {
                $searchBar.show();
            }
            applyFilters();
        }).fail(function() {
            $grid.html('<div class="empty-state"><p>Failed to load products.</p></div>');
        });
    }

    function buildCategoryTabs(products) {
        var cats = {};
        products.forEach(function(p) {
            if (p.category_id && p.category_name) {
                cats[p.category_id] = p.category_name;
            }
        });

        var catIds = Object.keys(cats);
        if (catIds.length === 0) {
            $filterBar.hide();
            return;
        }

        var html = '<button class="category-tab active" data-cat="">All</button>';
        catIds.forEach(function(id) {
            html += '<button class="category-tab" data-cat="' + id + '">' + escapeHtml(cats[id]) + '</button>';
        });
        $filterBar.html(html).show();
    }

    function applyFilters() {
        _filteredProducts = _allProducts;
        if (_activeFilter) {
            _filteredProducts = _filteredProducts.filter(function(p) { return String(p.category_id) === _activeFilter; });
        }
        if (_searchQuery) {
            var q = _searchQuery.toLowerCase();
            _filteredProducts = _filteredProducts.filter(function(p) {
                return (p.name && p.name.toLowerCase().indexOf(q) !== -1) ||
                       (p.description && p.description.toLowerCase().indexOf(q) !== -1);
            });
        }
        _visibleCount = PAGE_SIZE;
        renderProducts();
    }

    function updateSummary() {
        var total = _filteredProducts.length;
        if (total === 0 || _allProducts.length < 3) {
            $summary.hide();
            return;
        }
        var label = total === 1 ? '1 product' : total + ' products';
        if (_activeFilter || _searchQuery) {
            label += ' of ' + _allProducts.length;
        }
        $('#productCount').text(label);
        $summary.show();
    }

    $filterBar.on('click', '.category-tab', function() {
        $filterBar.find('.category-tab').removeClass('active');
        $(this).addClass('active');
        var catId = $(this).data('cat');
        _activeFilter = catId === '' ? null : String(catId);
        applyFilters();
    });

    var _searchTimer;
    $searchInput.on('input', function() {
        clearTimeout(_searchTimer);
        var val = $(this).val();
        _searchTimer = setTimeout(function() {
            _searchQuery = val.trim();
            applyFilters();
        }, 150);
    });

    // Load more button
    $('#loadMoreBtn').on('click', function() {
        _visibleCount += PAGE_SIZE;
        renderProducts();
    });

    function renderProducts() {
        var products = _filteredProducts;
        updateSummary();

        if (products.length === 0) {
            var msg = _searchQuery ? 'No products match "' + escapeHtml(_searchQuery) + '"'
                    : _activeFilter ? 'No products in this category'
                    : 'No products yet';
            var hint = (!_activeFilter && !_searchQuery) ? '<p>Tap + to add your first product</p>' : '';
            $grid.html(
                '<div class="empty-state">' +
                    '<div class="empty-icon"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#AEAEB2" stroke-width="1.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg></div>' +
                    '<h2>' + msg + '</h2>' +
                    hint +
                '</div>'
            );
            $loadMore.hide();
            return;
        }

        // Paginate: only show up to _visibleCount
        var visible = products.slice(0, _visibleCount);
        var hasMore = products.length > _visibleCount;

        var html = '';
        visible.forEach(function(p) {
            var imgSrc = p.image_url || '/public/img/placeholder.svg';
            var isSold = parseInt(p.is_sold) === 1;
            var isHidden = parseInt(p.is_active) === 0;
            var isFeatured = parseInt(p.is_featured) === 1;
            var hasSale = p.compare_price && parseFloat(p.compare_price) > parseFloat(p.price);
            var cardClass = 'product-card-manage' + (isSold ? ' product-card-sold' : '') + (isHidden ? ' product-card-hidden' : '');
            var badge = '';
            if (isHidden) {
                badge = '<span class="product-card-badge product-card-badge-hidden">Hidden</span>';
            } else if (isSold) {
                badge = '<span class="product-card-badge product-card-badge-sold">Sold</span>';
            } else if (isFeatured) {
                badge = '<span class="product-card-badge product-card-badge-featured">&#9733; Featured</span>';
            } else if (hasSale) {
                badge = '<span class="product-card-badge product-card-badge-sale">Sale</span>';
            }
            var priceHtml = '';
            if (hasSale && !isSold) {
                priceHtml = '<span class="price-compare">' + TinyShop.formatPrice(p.compare_price, _currency) + '</span> ' +
                    '<span class="price-sale">' + TinyShop.formatPrice(p.price, _currency) + '</span>';
            } else {
                priceHtml = TinyShop.formatPrice(p.price, _currency);
            }
            var catLabel = p.category_name ? '<div class="product-card-category">' + escapeHtml(p.category_name) + '</div>' : '';
            html += '<a href="/dashboard/products/' + p.id + '/edit" class="' + cardClass + '" data-id="' + p.id + '">' +
                '<div class="product-card-img-wrap">' +
                    badge +
                    '<img src="' + escapeHtml(imgSrc) + '" alt="' + escapeHtml(p.name) + '" loading="lazy">' +
                '</div>' +
                '<div class="product-card-body">' +
                    '<h3>' + escapeHtml(p.name) + '</h3>' +
                    '<div class="product-price">' + priceHtml + '</div>' +
                    catLabel +
                '</div>' +
            '</a>';
        });
        $grid.html(html);

        // Show/hide load more
        if (hasMore) {
            var remaining = products.length - _visibleCount;
            $('#loadMoreBtn').text('Load ' + Math.min(remaining, PAGE_SIZE) + ' more of ' + remaining + ' remaining');
            $loadMore.show();
        } else {
            $loadMore.hide();
        }
    }

    loadProducts();
};

/* ============================================================
   Price Input Formatting (comma-separated with decimal)
   ============================================================ */
TinyShop.initPriceInput = function($input) {
    function formatDisplay(val) {
        // Strip everything except digits and dot
        var clean = val.replace(/[^0-9.]/g, '');
        // Only allow one decimal point
        var parts = clean.split('.');
        if (parts.length > 2) clean = parts[0] + '.' + parts.slice(1).join('');
        parts = clean.split('.');
        // Add commas to integer part
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        return parts.join('.');
    }

    function getRawValue($el) {
        return $el.val().replace(/,/g, '');
    }

    // Format initial value if present
    var initVal = $input.val();
    if (initVal && !isNaN(parseFloat(initVal))) {
        $input.val(formatDisplay(initVal));
    }

    $input.on('input', function() {
        var cursorPos = this.selectionStart;
        var oldVal = $(this).val();
        var oldLen = oldVal.length;
        var formatted = formatDisplay(oldVal);
        $(this).val(formatted);
        // Adjust cursor for added/removed commas
        var diff = formatted.length - oldLen;
        this.setSelectionRange(cursorPos + diff, cursorPos + diff);
    });

    // Store a method to get numeric value
    $input.data('rawValue', function() {
        return getRawValue($input);
    });
};

/* ============================================================
   Product Form Page (add / edit)
   ============================================================ */
TinyShop.initProductForm = function() {
    var $form = $('#productForm');
    if (!$form.length || typeof _productFormConfig === 'undefined') return;

    var isEdit = _productFormConfig.isEdit;
    var productId = _productFormConfig.productId;
    var DRAFT_KEY = 'product_draft_new';

    // Initialize price formatting
    $('.price-input').each(function() {
        TinyShop.initPriceInput($(this));
    });

    // --- Image Gallery ---
    var $gallery = $('#imageGallery');
    var $addBtn = $('#addImageBtn');
    var $fileInput = $('#imageInput');

    function getImageUrls() {
        var urls = [];
        $gallery.find('.image-gallery-item').each(function() {
            urls.push($(this).data('url'));
        });
        return urls;
    }

    function addImageToGallery(url) {
        var $item = $('<div class="image-gallery-item" draggable="true" data-url="' + escapeHtml(url) + '">' +
            '<img src="' + escapeHtml(url) + '" alt="">' +
            '<button type="button" class="image-gallery-remove">&times;</button>' +
        '</div>');
        $addBtn.before($item);
        bindDrag($item[0]);
        saveDraft();
    }

    $addBtn.on('click', function() {
        $fileInput.click();
    });

    $fileInput.on('change', function() {
        var files = this.files;
        if (!files.length) return;
        for (var i = 0; i < files.length; i++) {
            (function(file) {
                TinyShop.uploadFile(file, function(url) {
                    addImageToGallery(url);
                    TinyShop.toast('Image uploaded');
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

    function bindDrag(el) {
        // Desktop drag
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

        // Touch drag
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

    // Bind drag to existing items
    $gallery.find('.image-gallery-item').each(function() {
        this.setAttribute('draggable', 'true');
        bindDrag(this);
    });

    // --- Category Picker (bottom sheet) ---
    var _categoryTree = _productFormConfig.categoryTree || [];

    function openCategoryPicker() {
        var currentVal = $('#productCategory').val();
        var html = '<div class="category-picker-search">' +
            '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>' +
            '<input type="text" id="categorySearchInput" placeholder="Search categories..." autocomplete="off">' +
        '</div>';
        html += '<div class="category-picker-list">';

        // "No category" option
        html += '<div class="category-picker-none' + (!currentVal ? ' selected' : '') + '" data-id="">' +
            'No category' +
        '</div>';

        // Tree items
        _categoryTree.forEach(function(parent) {
            html += '<div class="category-picker-group" data-search-parent="' + escapeHtml(parent.name.toLowerCase()) + '">';
            html += '<div class="category-picker-item category-picker-item-parent' + (String(parent.id) === String(currentVal) ? ' selected' : '') + '" data-id="' + parent.id + '" data-search-name="' + escapeHtml(parent.name.toLowerCase()) + '">' +
                '<span>' + escapeHtml(parent.name) + '</span>' +
                '<span class="category-picker-check"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg></span>' +
            '</div>';
            (parent.children || []).forEach(function(child) {
                html += '<div class="category-picker-item category-picker-item-child' + (String(child.id) === String(currentVal) ? ' selected' : '') + '" data-id="' + child.id + '" data-search-name="' + escapeHtml(child.name.toLowerCase()) + '">' +
                    '<span>' + escapeHtml(child.name) + '</span>' +
                    '<span class="category-picker-check"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg></span>' +
                '</div>';
            });
            html += '</div>';
        });
        html += '</div>';

        TinyShop.openModal('Select Category', html);

        // Search filter
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
                // Hide "No category" when searching
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

        // Handle selection
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
                // Add to local tree for picker
                _categoryTree.push({ id: cat.id, name: cat.name, children: [] });
                // Select the new category
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

    function buildOptionRow(value, price) {
        var priceVal = (price !== null && price !== undefined) ? price : '';
        return '<div class="variation-option-row">' +
            '<input type="text" class="variation-option-value" placeholder="Value name" value="' + escapeHtml(value || '') + '" autocomplete="off">' +
            '<input type="text" class="variation-option-price price-input" placeholder="Price" inputmode="decimal" value="' + escapeHtml(String(priceVal)) + '" autocomplete="off">' +
            '<button type="button" class="variation-option-remove" title="Remove">&times;</button>' +
        '</div>';
    }

    function addVariationGroup(name, options) {
        var gid = _varCounter++;
        var html = '<div class="variation-group" data-gid="' + gid + '">' +
            '<div class="variation-group-header">' +
                '<input type="text" class="variation-group-name" placeholder="Option name (e.g. Size)" value="' + escapeHtml(name || '') + '" autocomplete="off">' +
                '<button type="button" class="variation-group-remove" title="Remove">&times;</button>' +
            '</div>' +
            '<div class="variation-options">';
        if (options && options.length) {
            options.forEach(function(opt) {
                // Support old format (plain strings) and new format (objects)
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
            // Auto-add first empty row for new groups
            $varGroups.find('.variation-group[data-gid="' + gid + '"] .variation-options').append(buildOptionRow('', null));
        }
        // Init price formatting on new price inputs
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
            // If this is the last row and has a value, add a new row
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

    // Load existing variations
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

    // Auto-open if SEO fields have values
    if ($metaTitleInput.val() || $metaDescInput.val()) {
        $seoFields.show();
        $seoToggle.addClass('open');
    }

    // Character counters
    function updateSeoCounters() {
        $('#metaTitleCount').text($metaTitleInput.val().length);
        $('#metaDescCount').text($metaDescInput.val().length);
    }
    $metaTitleInput.on('input', updateSeoCounters);
    $metaDescInput.on('input', updateSeoCounters);
    updateSeoCounters();

    // --- localStorage Draft (add mode only) ---
    var _draftTimer;
    function saveDraft() {
        if (isEdit) return;
        clearTimeout(_draftTimer);
        _draftTimer = setTimeout(function() {
            var draft = {
                name: $('#productName').val(),
                price: $('#productPrice').val().replace(/,/g, ''),
                compare_price: $('#productComparePrice').val().replace(/,/g, ''),
                description: $('#productDesc').val(),
                category_id: $('#productCategory').val(),
                images: getImageUrls(),
                variations: getVariations(),
                meta_title: $metaTitleInput.val(),
                meta_description: $metaDescInput.val()
            };
            try { localStorage.setItem(DRAFT_KEY, JSON.stringify(draft)); } catch(e) {}
        }, 500);
    }

    function restoreDraft() {
        if (isEdit) return;
        try {
            var raw = localStorage.getItem(DRAFT_KEY);
            if (!raw) return;
            var draft = JSON.parse(raw);
            if (draft.name) $('#productName').val(draft.name);
            if (draft.price) { $('#productPrice').val(draft.price).trigger('input'); }
            if (draft.compare_price) { $('#productComparePrice').val(draft.compare_price).trigger('input'); }
            if (draft.description) $('#productDesc').val(draft.description);
            if (draft.category_id) {
                $('#productCategory').val(draft.category_id);
                // Update picker label from tree
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

    function clearDraft() {
        try { localStorage.removeItem(DRAFT_KEY); } catch(e) {}
    }

    // Bind input changes to draft save
    $form.on('input change', 'input, textarea, select', function() {
        saveDraft();
    });

    // Restore draft on load (add mode only)
    restoreDraft();

    // --- Delete Product (edit mode, with confirm modal) ---
    if (isEdit) {
        $('#deleteProductBtn').on('click', function() {
            var html = '<p style="margin-bottom:20px;color:var(--color-text-muted);font-size:0.9rem;">This will permanently delete this product and all its images. This cannot be undone.</p>' +
                '<div style="display:flex;gap:10px">' +
                    '<button type="button" class="btn-primary" id="confirmDeleteCancel" style="flex:1;min-height:48px;font-size:1rem;font-weight:600;border-radius:12px;background:var(--color-bg-secondary);color:var(--color-text);border:none;cursor:pointer;font-family:inherit;">Cancel</button>' +
                    '<button type="button" class="btn-primary" id="confirmDeleteYes" style="flex:1;min-height:48px;font-size:1rem;font-weight:600;border-radius:12px;background:#FF3B30;color:#fff;border:none;cursor:pointer;font-family:inherit;">Delete</button>' +
                '</div>';
            TinyShop.openModal('Delete Product?', html);

            $('#confirmDeleteCancel').on('click', function() {
                TinyShop.closeModal();
            });

            $('#confirmDeleteYes').on('click', function() {
                $(this).prop('disabled', true).text('Deleting...');
                TinyShop.api('DELETE', '/api/products/' + productId).done(function() {
                    TinyShop.toast('Product deleted');
                    TinyShop.navigate('/dashboard/products');
                }).fail(function() {
                    TinyShop.toast('Failed to delete', 'error');
                    TinyShop.closeModal();
                });
            });
        });
    }

    // --- Form Submit ---
    $form.on('submit', function(e) {
        e.preventDefault();
        var $btn = $('#saveProductBtn').prop('disabled', true).text('Saving...');

        var priceRaw = $('#productPrice').val().replace(/,/g, '');
        var compareRaw = $('#productComparePrice').val().replace(/,/g, '');
        var variations = getVariations();
        var payload = {
            name: $('#productName').val(),
            price: parseFloat(priceRaw),
            compare_price: compareRaw !== '' ? parseFloat(compareRaw) : null,
            description: $('#productDesc').val(),
            category_id: $('#productCategory').val() || null,
            images: getImageUrls(),
            is_sold: $('#productSold').is(':checked') ? 1 : 0,
            is_featured: $('#productFeatured').is(':checked') ? 1 : 0,
            is_active: $('#productActive').length ? ($('#productActive').is(':checked') ? 1 : 0) : 1,
            variations: variations.length > 0 ? variations : null,
            meta_title: $metaTitleInput.val().trim() || null,
            meta_description: $metaDescInput.val().trim() || null
        };

        var method = isEdit ? 'PUT' : 'POST';
        var url = isEdit ? '/api/products/' + productId : '/api/products';

        TinyShop.api(method, url, payload).done(function() {
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

/* ============================================================
   Autosize Textareas
   ============================================================ */
TinyShop.autosize = function(el) {
    // Skip elements hidden by a parent (scrollHeight is 0)
    if (!el.offsetParent) return;
    el.style.overflow = 'hidden';
    el.style.resize = 'none';
    // Reset to natural height (rows attr acts as minimum), then expand
    el.style.height = 'auto';
    var h = el.scrollHeight;
    var cs = window.getComputedStyle(el);
    if (cs.boxSizing === 'border-box') {
        h += parseFloat(cs.borderTopWidth) + parseFloat(cs.borderBottomWidth);
    }
    el.style.height = h + 'px';
};

TinyShop.initAutosize = function() {
    $('textarea.autosize').each(function() {
        TinyShop.autosize(this);
    });
};

/* ============================================================
   page:init — global event fired on every page load / SPA swap.
   All page-specific init code listens for this event.
   ============================================================ */
$(document).on('page:init', function() {
    TinyShop.initProductList();
    TinyShop.initProductForm();
    TinyShop.initAutosize();
});

// One-time global delegates (survive SPA navigations)
$(function() {
    $(document).on('input', 'textarea.autosize', function() {
        TinyShop.autosize(this);
    });
    $(document).on('click', '.seo-toggle', function() {
        var $section = $(this).closest('.form-section');
        setTimeout(function() {
            $section.find('textarea.autosize').each(function() {
                TinyShop.autosize(this);
            });
        }, 250);
    });

    // Fire page:init on first load
    $(document).trigger('page:init');
});

/* ============================================================
   SPA Navigation — AJAX page loading for dashboard
   ============================================================ */
TinyShop.spa = {
    _ready: false,
    _loading: false,
    _currentXhr: null,

    init: function() {
        var self = this;

        // Store initial state
        history.replaceState({ spa: true, url: location.pathname + location.search }, '', location.pathname + location.search);

        // Intercept dashboard link clicks (delegated)
        $(document).on('click', 'a', function(e) {
            // Skip if modifier key (new tab intent)
            if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

            var href = this.getAttribute('href');
            if (!href) return;

            // Only intercept dashboard routes
            if (!href.match(/^\/dashboard(\/|$)/)) return;

            // Skip anchors, blobs, javascript:, etc.
            if (href.charAt(0) === '#' || this.target === '_blank') return;

            e.preventDefault();

            // Don't re-navigate to the same page
            if (href === location.pathname + location.search) return;

            self.go(href);
        });

        // Handle browser back/forward
        window.addEventListener('popstate', function(e) {
            if (e.state && e.state.spa) {
                self.go(e.state.url, true);
            }
        });

        self._ready = true;
    },

    go: function(url, isPopState) {
        var self = this;

        // Abort any in-flight request
        if (self._currentXhr) {
            self._currentXhr.abort();
            self._currentXhr = null;
        }

        self._loading = true;
        self.showProgress();

        self._currentXhr = $.ajax({
            url: url,
            method: 'GET',
            dataType: 'html',
            headers: { 'X-SPA': '1' },
            success: function(html) {
                self._currentXhr = null;

                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');

                // Extract new content
                var newContent = doc.querySelector('.dash-content');
                if (!newContent) {
                    // Not a dashboard page — full reload
                    window.location.href = url;
                    return;
                }

                // Swap content
                var $content = $('.dash-content');
                $content.html(newContent.innerHTML);

                // Execute inline scripts in global scope via DOM insertion.
                // This ensures function declarations, vars, and onclick handlers
                // work exactly as on a full page load.
                var scripts = doc.querySelectorAll('script');
                scripts.forEach(function(s) {
                    if (s.src) return;
                    var code = s.textContent;
                    if (!code.trim()) return;
                    // Skip the service worker registration
                    if (code.indexOf('serviceWorker') !== -1 && code.indexOf('register') !== -1) return;
                    var el = document.createElement('script');
                    el.textContent = code;
                    document.body.appendChild(el);
                    document.body.removeChild(el);
                });

                // Trigger page:init — re-runs all registered init hooks
                $(document).trigger('page:init');

                // Update active tab
                self.updateTabs(url);

                // Update history
                if (!isPopState) {
                    history.pushState({ spa: true, url: url }, '', url);
                }

                // Scroll to top
                window.scrollTo(0, 0);

                self._loading = false;
                self.hideProgress();
            },
            error: function(xhr, status) {
                self._currentXhr = null;
                self._loading = false;
                self.hideProgress();

                // Don't do anything on abort
                if (status === 'abort') return;

                // Fallback to full page load
                window.location.href = url;
            }
        });
    },

    showProgress: function() {
        var $bar = $('#spaProgress');
        if (!$bar.length) return;
        // Reset and start
        $bar.removeClass('spa-progress-done').css('width', '0%');
        // Force reflow
        $bar[0].offsetWidth;
        $bar.addClass('spa-progress-active').css('width', '70%');
    },

    hideProgress: function() {
        var $bar = $('#spaProgress');
        if (!$bar.length) return;
        $bar.css('width', '100%');
        setTimeout(function() {
            $bar.addClass('spa-progress-done').removeClass('spa-progress-active');
            setTimeout(function() {
                $bar.css('width', '0%').removeClass('spa-progress-done');
            }, 200);
        }, 150);
    },

    updateTabs: function(url) {
        var $tabs = $('.dash-tabs .dash-tab');
        $tabs.removeClass('active').removeAttr('aria-current');

        // Match by path
        var path = url.split('?')[0];
        $tabs.each(function() {
            var tabHref = this.getAttribute('href');
            var isMatch = false;
            if (tabHref === '/dashboard') {
                isMatch = path === '/dashboard' || path === '/dashboard/';
            } else {
                isMatch = path.indexOf(tabHref) === 0;
            }
            if (isMatch) {
                $(this).addClass('active').attr('aria-current', 'page');
            }
        });
    }
};

// Initialize SPA on DOM ready
$(function() {
    TinyShop.spa.init();
});
