/* ============================================================
   Product List Page (2-col card grid)
   ============================================================ */
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
    var _allProducts = [];
    var _filteredProducts = [];
    var _activeFilter = null;
    var _searchQuery = '';
    var _currency = (typeof _productListConfig !== 'undefined' && _productListConfig.currency) ? _productListConfig.currency : 'KES';
    var PAGE_SIZE = 30;
    var _visibleCount = PAGE_SIZE;

    function loadProducts() {
        TinyShop.api('GET', '/api/products?limit=0').done(function(res) {
            _allProducts = res.products || [];
            buildCategoryTabs(_allProducts);
            if (_allProducts.length >= 3) {
                $searchBar.show();
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

    $('#loadMoreBtn').on('click', function() {
        _visibleCount += PAGE_SIZE;
        renderProducts();
    });

    function renderProducts() {
        var products = _filteredProducts;
        updateSummary();

        if (products.length === 0) {
            var msg, hint;
            if (_searchQuery) {
                msg = 'No results for "' + escapeHtml(_searchQuery) + '"';
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
