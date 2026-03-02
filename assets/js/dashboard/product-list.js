/**
 * Product list page (2-column card grid).
 *
 * Loads all products via the API, renders them client-side
 * with category filter tabs, search, pagination, bulk select
 * mode, and per-product share.
 *
 * @since 1.0.0
 */
TinyShop.initProductList = function() {
    var $grid = $('#productGrid');
    if (!$grid.length) return;

    // Prevent duplicate bindings on SPA re-navigation
    if ($grid.data('initialized')) return;
    $grid.data('initialized', true);

    var $filterBar = $('#categoryFilterBar');
    var $searchBar = $('#productSearchBar');
    var $searchInput = $('#productSearch');
    var $summary = $('#productListSummary');
    var $loadMore = $('#productLoadMore');
    var $bulkToggle = $('#bulkSelectToggle');
    var $bulkBar = $('#bulkActionBar');
    var $bulkCount = $('#bulkCount');
    var $fab = $('#addProductFab');
    var _allProducts = [];
    var _filteredProducts = [];
    var _activeFilter = null;
    var _searchQuery = '';
    var _currency = (typeof _productListConfig !== 'undefined' && _productListConfig.currency) ? _productListConfig.currency : 'KES';
    var _subdomain = (typeof _productListConfig !== 'undefined' && _productListConfig.subdomain) ? _productListConfig.subdomain : '';
    var _baseDomain = (typeof _productListConfig !== 'undefined' && _productListConfig.baseDomain) ? _productListConfig.baseDomain : window.location.host;
    var PAGE_SIZE = 30;
    var _visibleCount = PAGE_SIZE;
    var _selectMode = false;
    var _selected = {};

    /** Fetch all products from the API and render. */
    function loadProducts() {
        TinyShop.api('GET', '/api/products?limit=0').done(function(res) {
            _allProducts = res.products || [];
            buildCategoryTabs(_allProducts);
            if (_allProducts.length >= 3) {
                $searchBar.show();
            }
            if (_allProducts.length >= 2) {
                $bulkToggle.show();
            }

            var scrollToId = null;
            try {
                scrollToId = sessionStorage.getItem('spa_last_product');
                if (scrollToId) {
                    sessionStorage.removeItem('spa_last_product');
                    for (var i = 0; i < _allProducts.length; i++) {
                        if (String(_allProducts[i].id) === String(scrollToId)) {
                            if (i >= _visibleCount) {
                                _visibleCount = i + PAGE_SIZE;
                            }
                            break;
                        }
                    }
                }
            } catch(ex) {}

            applyFilters();

            if (scrollToId) {
                var $el = $grid.find('[data-id="' + scrollToId + '"]');
                if ($el.length) {
                    $el[0].scrollIntoView({ block: 'center' });
                }
            }
        }).fail(function() {
            $grid.html('<div class="empty-state"><p>Failed to load products.</p></div>');
        });
    }

    /** Build the category filter pill tabs from product data. */
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
            html += '<button class="category-tab" data-cat="' + id + '">' + TinyShop.escapeHtml(cats[id]) + '</button>';
        });
        $filterBar.html(html).show();
    }

    /** Apply active category filter and search query, then re-render. */
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

    /** Update the "N products" summary label. */
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

    $('#loadMoreBtn').on('click', function() {
        _visibleCount += PAGE_SIZE;
        renderProducts();
    });

    /** Render the product card grid from _filteredProducts. */
    function renderProducts() {
        var products = _filteredProducts;
        updateSummary();

        if (products.length === 0) {
            var msg, hint;
            if (_searchQuery) {
                msg = 'No results for "' + TinyShop.escapeHtml(_searchQuery) + '"';
                hint = '<p>Try a different search term</p>';
            } else if (_activeFilter) {
                msg = 'Nothing in this category';
                hint = '';
            } else {
                msg = 'Your store is ready';
                hint = '<p>Add your first product to start selling</p>' +
                       '<a href="/dashboard/products/add" class="empty-state-btn">Add product</a>';
            }
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
            var catLabel = p.category_name ? '<div class="product-card-category">' + TinyShop.escapeHtml(p.category_name) + '</div>' : '';
            var checkHtml = _selectMode
                ? '<span class="product-select-check' + (_selected[p.id] ? ' checked' : '') + '"><i class="fa-solid fa-check"></i></span>'
                : '';
            var shareBtn = !_selectMode && !isHidden
                ? '<button type="button" class="product-share-btn" data-slug="' + TinyShop.escapeHtml(p.slug || p.id) + '" data-name="' + TinyShop.escapeHtml(p.name) + '" title="Share"><i class="fa-solid fa-arrow-up-from-bracket"></i></button>'
                : '';
            html += '<a href="/dashboard/products/' + p.id + '/edit" class="' + cardClass + '" data-id="' + p.id + '">' +
                checkHtml +
                '<div class="product-card-img-wrap">' +
                    badge +
                    shareBtn +
                    '<img src="' + TinyShop.escapeHtml(imgSrc) + '" alt="' + TinyShop.escapeHtml(p.name) + '" loading="lazy">' +
                '</div>' +
                '<div class="product-card-body">' +
                    '<h3>' + TinyShop.escapeHtml(p.name) + '</h3>' +
                    '<div class="product-price">' + priceHtml + '</div>' +
                    catLabel +
                '</div>' +
            '</a>';
        });
        $grid.html(html);

        if (hasMore) {
            var remaining = products.length - _visibleCount;
            $('#loadMoreBtn').text('Load ' + Math.min(remaining, PAGE_SIZE) + ' more of ' + remaining + ' remaining');
            $loadMore.show();
        } else {
            $loadMore.hide();
        }
    }

    // --- Bulk select mode ---

    /** Enter multi-select mode for bulk actions. */
    function enterSelectMode() {
        _selectMode = true;
        _selected = {};
        $grid.addClass('select-mode');
        $bulkToggle.addClass('active');
        $fab.hide();
        updateBulkBar();
        renderProducts();
    }

    /** Exit multi-select mode. */
    function exitSelectMode() {
        _selectMode = false;
        _selected = {};
        $grid.removeClass('select-mode');
        $bulkToggle.removeClass('active');
        $bulkBar.hide();
        $fab.show();
        renderProducts();
    }

    /** Update the bulk action bar count label. */
    function updateBulkBar() {
        var count = Object.keys(_selected).length;
        if (count > 0) {
            $bulkCount.text(count + ' selected');
            $bulkBar.show();
        } else {
            $bulkBar.hide();
        }
    }

    $bulkToggle.on('click', function() {
        if (_selectMode) {
            exitSelectMode();
        } else {
            enterSelectMode();
        }
    });

    $grid.on('click', '.product-select-check', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var id = $(this).closest('.product-card-manage').data('id');
        if (_selected[id]) {
            delete _selected[id];
            $(this).removeClass('checked');
        } else {
            _selected[id] = true;
            $(this).addClass('checked');
        }
        updateBulkBar();
    });

    $grid.on('click', '.product-card-manage', function(e) {
        if (!_selectMode) return;
        e.preventDefault();
        var id = $(this).data('id');
        var $check = $(this).find('.product-select-check');
        if (_selected[id]) {
            delete _selected[id];
            $check.removeClass('checked');
        } else {
            _selected[id] = true;
            $check.addClass('checked');
        }
        updateBulkBar();
    });

    $('#bulkArchiveBtn').on('click', function() {
        var ids = Object.keys(_selected).map(Number);
        if (!ids.length) return;
        var label = ids.length === 1 ? '1 product' : ids.length + ' products';
        TinyShop.confirm('Hide ' + label + '?', 'They won\'t show in your shop until you unhide them.', 'Hide', function() {
            TinyShop.closeModal();
            TinyShop.api('POST', '/api/products/bulk-archive', { ids: ids }).done(function(res) {
                TinyShop.toast(res.archived + ' product' + (res.archived !== 1 ? 's' : '') + ' hidden', 'success');
                exitSelectMode();
                loadProducts();
            }).fail(function() {
                TinyShop.toast('Something went wrong', 'error');
            });
        });
    });

    $('#bulkDeleteBtn').on('click', function() {
        var ids = Object.keys(_selected).map(Number);
        if (!ids.length) return;
        var label = ids.length === 1 ? '1 product' : ids.length + ' products';
        TinyShop.confirm('Delete ' + label + '?', 'This will permanently delete the selected products and their images. This cannot be undone.', 'Delete', function() {
            TinyShop.closeModal();
            TinyShop.api('POST', '/api/products/bulk-delete', { ids: ids }).done(function(res) {
                TinyShop.toast(res.deleted + ' product' + (res.deleted !== 1 ? 's' : '') + ' deleted', 'success');
                exitSelectMode();
                loadProducts();
            }).fail(function() {
                TinyShop.toast('Something went wrong', 'error');
            });
        }, 'danger');
    });

    // --- Product share button ---
    $grid.on('click', '.product-share-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var slug = $(this).data('slug');
        var name = $(this).data('name');
        var port = window.location.port ? ':' + window.location.port : '';
        var productUrl = window.location.protocol + '//' + _subdomain + '.' + _baseDomain + port + '/' + slug;

        var html = '<div class="share-quick-btns share-quick-btns-modal">' +
            '<button type="button" class="share-quick-btn share-btn-whatsapp" data-channel="whatsapp" title="WhatsApp"><i class="fa-brands fa-whatsapp"></i></button>' +
            '<button type="button" class="share-quick-btn share-btn-facebook" data-channel="facebook" title="Facebook"><i class="fa-brands fa-facebook-f"></i></button>' +
            '<button type="button" class="share-quick-btn share-btn-x" data-channel="x" title="X (Twitter)"><i class="fa-brands fa-x-twitter"></i></button>' +
            '<button type="button" class="share-quick-btn share-btn-email" data-channel="email" title="Email"><i class="fa-solid fa-envelope"></i></button>' +
        '</div>' +
        '<div class="share-link-row" style="margin-top:12px">' +
            '<input type="text" value="' + TinyShop.escapeHtml(productUrl) + '" id="shareProductUrl" readonly>' +
            '<button type="button" class="btn-copy" id="copyShareUrl">Copy</button>' +
        '</div>';

        TinyShop.openModal('Share ' + TinyShop.escapeHtml(name), html);

        var text = encodeURIComponent('Check out ' + name + '!');
        $('.share-quick-btns-modal .share-quick-btn').on('click', function() {
            var channel = $(this).data('channel');
            var tracked = encodeURIComponent(productUrl + '?utm_source=' + channel);
            var link = '';
            switch (channel) {
                case 'whatsapp': link = 'https://wa.me/?text=' + text + '%20' + tracked; break;
                case 'facebook': link = 'https://www.facebook.com/sharer/sharer.php?u=' + tracked; break;
                case 'x': link = 'https://twitter.com/intent/tweet?text=' + text + '&url=' + tracked; break;
                case 'email': link = 'mailto:?subject=' + text + '&body=' + tracked; break;
            }
            if (link) window.open(link, '_blank');
        });

        $('#copyShareUrl').on('click', function() {
            var $btn = $(this);
            if (navigator.clipboard) {
                navigator.clipboard.writeText(productUrl).then(function() {
                    $btn.text('Copied!');
                    TinyShop.toast('Link copied');
                    setTimeout(function() { $btn.text('Copy'); }, 2000);
                });
            } else {
                $('#shareProductUrl').select();
                document.execCommand('copy');
                $btn.text('Copied!');
                TinyShop.toast('Link copied');
                setTimeout(function() { $btn.text('Copy'); }, 2000);
            }
        });
    });

    loadProducts();
};
