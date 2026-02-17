/* ============================================================
   Shop page init — re-runnable on each page:init
   ============================================================ */
TinyShop.initShop = function() {
    var $catalogue = $('#catalogue');
    if (!$catalogue.length) return;

    var $shopPage = $catalogue.closest('.shop-page');
    var subdomain = $shopPage.data('subdomain');
    if (!subdomain) return;

    var $productCount = $('#productCount');
    var $searchEmpty = $('#searchEmpty');
    var $loadMoreWrap = $('#loadMoreWrap');
    var $loadMoreBtn = $('#loadMoreBtn');
    var $loadMoreCount = $('#loadMoreCount');

    var limit = parseInt($shopPage.data('limit'), 10) || 24;
    var currencySymbol = String($shopPage.data('currency') || '');

    // State
    var state = {
        category: 'all',
        categorySlug: '',
        search: '',
        sort: 'default',
        offset: $catalogue.find('.product-card').length,
        total: parseInt($shopPage.data('total'), 10) || 0,
        loading: false,
        ajaxMode: false
    };

    // API base URL
    var apiBase = '/products';

    function buildQuery(overrides) {
        var o = overrides || {};
        var params = {
            limit: o.limit || limit,
            offset: typeof o.offset !== 'undefined' ? o.offset : 0,
            sort: o.sort || state.sort
        };
        if (state.search) params.search = state.search;
        if (state.category !== 'all') params.category = state.category;
        return $.param(params);
    }

    function updateCount(shown, total) {
        state.total = total;
        if (!$productCount.length) return;
        if (total === 0) {
            $productCount.text('0 products');
        } else if (shown < total) {
            $productCount.text('Showing ' + shown + ' of ' + total + ' products');
        } else {
            $productCount.text(total + (total === 1 ? ' product' : ' products'));
        }
    }

    function updateLoadMore(shown, total) {
        if (!$loadMoreWrap.length && shown < total) {
            // Create load more if it doesn't exist yet
            var html = '<div class="load-more-wrap" id="loadMoreWrap">'
                + '<button type="button" class="load-more-btn" id="loadMoreBtn">'
                + 'Show more products <span class="load-more-count" id="loadMoreCount"></span>'
                + '</button></div>';
            $catalogue.after(html);
            $loadMoreWrap = $('#loadMoreWrap');
            $loadMoreBtn = $('#loadMoreBtn');
            $loadMoreCount = $('#loadMoreCount');
        }

        if ($loadMoreWrap.length) {
            var remaining = total - shown;
            if (remaining > 0) {
                $loadMoreCount.text('(' + remaining + ' more)');
                $loadMoreWrap.show();
            } else {
                $loadMoreWrap.hide();
            }
        }
    }

    function showEmpty(show) {
        if (show) {
            $catalogue.hide();
            $searchEmpty.show();
        } else {
            $searchEmpty.hide();
            $catalogue.show();
        }
    }

    // Fetch products from API
    function fetchProducts(opts) {
        if (state.loading) return;
        state.loading = true;
        state.ajaxMode = true;

        var append = opts && opts.append;
        var query = buildQuery(opts);

        // Show loading indicator on search input
        var $shopSearch = $('#shopSearch');
        if ($shopSearch.length) $shopSearch.addClass('search-loading');

        if (!append) {
            $catalogue.html(TinyShop.renderSkeletons(limit > 8 ? 8 : limit));
            $catalogue.show();
            $searchEmpty.hide();
        } else {
            $loadMoreBtn.addClass('loading').text('Loading...');
        }

        $.getJSON(apiBase + '?' + query)
            .done(function(data) {
                var products = data.products || [];
                var total = data.total || 0;

                if (append) {
                    var html = '';
                    for (var i = 0; i < products.length; i++) {
                        html += TinyShop.renderProductCard(products[i], data.currency_symbol || currencySymbol);
                    }
                    $catalogue.append(html);
                    state.offset += products.length;
                } else {
                    if (products.length === 0) {
                        $catalogue.empty();
                        showEmpty(true);
                        state.offset = 0;
                    } else {
                        var html = '';
                        for (var i = 0; i < products.length; i++) {
                            html += TinyShop.renderProductCard(products[i], data.currency_symbol || currencySymbol);
                        }
                        $catalogue.html(html);
                        showEmpty(false);
                        state.offset = products.length;
                    }
                }

                updateCount(state.offset, total);
                updateLoadMore(state.offset, total);
            })
            .fail(function() {
                if (!append) {
                    $catalogue.empty();
                    showEmpty(true);
                }
            })
            .always(function() {
                state.loading = false;
                var $ss = $('#shopSearch');
                if ($ss.length) $ss.removeClass('search-loading');
                $loadMoreBtn.removeClass('loading').html('Show more products <span class="load-more-count" id="loadMoreCount"></span>');
                $loadMoreCount = $('#loadMoreCount');
                updateLoadMore(state.offset, state.total);
            });
    }

    function scrollIntoCenter(el) {
        var container = el.parentElement;
        if (container) {
            var scrollLeft = el.offsetLeft - (container.offsetWidth / 2) + (el.offsetWidth / 2);
            container.scrollTo({ left: scrollLeft, behavior: 'smooth' });
        }
    }

    function syncCategoryUI(category) {
        $('#categoryTabs .category-tab').removeClass('active');
        $('#categoryTabs .category-tab').each(function() {
            if (String($(this).data('category')) === category) $(this).addClass('active');
        });
        if (category === 'all') $('#categoryTabs .category-tab').first().addClass('active');

        $('#categoryCards .category-card').removeClass('active');
        $('#categoryCards .category-card').each(function() {
            if (String($(this).data('category')) === category) $(this).addClass('active');
        });
        if (category === 'all') $('#categoryCards .category-card').first().addClass('active');
    }

    function filterByCategory(category, slug) {
        state.category = category;
        state.categorySlug = (category === 'all') ? '' : (slug || '');
        state.offset = 0;

        // Clear search when switching categories
        var $si = $('#searchInput');
        if ($si.length && $si.val()) {
            $si.val('');
            $('#searchClear').removeClass('visible');
            state.search = '';
        }

        syncCategoryUI(category);
        fetchProducts({ offset: 0 });
    }

    // Category pill tabs
    $('#categoryTabs').on('click', '.category-tab', function() {
        scrollIntoCenter(this);
        filterByCategory(String($(this).data('category')), $(this).data('slug') || '');
    });

    // Category image cards
    $('#categoryCards').on('click', '.category-card', function() {
        scrollIntoCenter(this);
        filterByCategory(String($(this).data('category')), $(this).data('slug') || '');
    });

    // Search toggle (for themes that use icon-only search)
    var $searchToggle = $('#searchToggle');
    var $shopSearch = $('#shopSearch');

    if ($searchToggle.length && $searchToggle.is(':visible')) {
        $shopSearch.addClass('search-collapsed');
        $searchToggle.on('click', function() {
            if ($shopSearch.hasClass('search-collapsed')) {
                $shopSearch.removeClass('search-collapsed').addClass('search-expanded');
                $searchToggle.hide();
                setTimeout(function() { $('#searchInput').focus(); }, 150);
            }
        });
    }

    // Search input — AJAX-based
    var searchTimer;
    var $searchInput = $('#searchInput');
    var $searchClear = $('#searchClear');

    if ($searchInput.length) {
        $searchInput.on('input', function() {
            var query = $.trim($(this).val());
            $searchClear.toggleClass('visible', query.length > 0);
            // Sync desktop header search
            var $ds = $('#bloomDesktopSearch');
            if ($ds.length && $ds.val() !== $(this).val()) $ds.val($(this).val());

            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() {
                state.search = query;
                state.offset = 0;
                fetchProducts({ offset: 0 });
            }, 300);
        });

        $searchClear.on('click', function() {
            $searchInput.val('');
            $searchClear.removeClass('visible');
            $('#bloomDesktopSearch').val('');
            state.search = '';
            state.offset = 0;
            fetchProducts({ offset: 0 });

            if ($searchToggle.length && $shopSearch.hasClass('search-expanded')) {
                $shopSearch.removeClass('search-expanded').addClass('search-collapsed');
                $searchToggle.show();
            } else {
                $searchInput.focus();
            }
        });
    }

    // Desktop header search (Bloom theme) — sync with page search
    var $desktopSearch = $('#bloomDesktopSearch');
    if ($desktopSearch.length) {
        $desktopSearch.on('input', function() {
            var query = $.trim($(this).val());
            // Mirror value to page search input
            if ($searchInput.length) {
                $searchInput.val(query).trigger('input');
            } else {
                // No page search (hidden on desktop) — search directly
                $searchClear.toggleClass('visible', query.length > 0);
                clearTimeout(searchTimer);
                searchTimer = setTimeout(function() {
                    state.search = query;
                    state.offset = 0;
                    fetchProducts({ offset: 0 });
                }, 300);
            }
        });
    }

    // Sort dropdown
    var $sort = $('#productSort');
    if ($sort.length) {
        $sort.on('change', function() {
            state.sort = $(this).val();
            state.offset = 0;
            fetchProducts({ offset: 0 });
        });
    }

    // Load more button — delegated to handle dynamically created button
    $(document).off('click.loadmore').on('click.loadmore', '#loadMoreBtn', function() {
        if (state.loading) return;
        fetchProducts({ offset: state.offset, append: true });
    });

    // Initial state
    updateCount(state.offset, state.total);
    updateLoadMore(state.offset, state.total);

    // ── URL state management ──
    function updateUrl() {
        var params = new URLSearchParams();
        if (state.search) params.set('search', state.search);
        if (state.sort !== 'default') params.set('sort', state.sort);
        var qs = params.toString();
        var basePath = (state.categorySlug) ? '/category/' + encodeURIComponent(state.categorySlug) : '/';
        history.replaceState(null, '', basePath + (qs ? '?' + qs : ''));
    }

    // Restore URL state on page load
    var urlParams = new URLSearchParams(window.location.search);
    var urlSearch = urlParams.get('search');
    var urlSort = urlParams.get('sort');
    var needsFetch = false;

    // Detect server-rendered category page (from /category/{slug} route)
    var serverCategory = $shopPage.data('active-category');
    var serverSlug = $shopPage.data('active-slug');
    if (serverCategory) {
        // Find matching tab to get the full ID list (parent + children)
        var $matchTab = $('#categoryTabs .category-tab[data-slug="' + serverSlug + '"]');
        if ($matchTab.length) {
            state.category = String($matchTab.data('category'));
        } else {
            state.category = String(serverCategory);
        }
        state.categorySlug = String(serverSlug || '');
        syncCategoryUI(state.category);
    }

    // Legacy: support ?category= query param (redirect to clean URL)
    var urlCategory = urlParams.get('category');
    if (urlCategory && !serverCategory) {
        state.category = urlCategory;
        syncCategoryUI(urlCategory);
        var $activeTab = $('#categoryTabs .category-tab.active');
        if ($activeTab.length && $activeTab.data('slug')) {
            state.categorySlug = String($activeTab.data('slug'));
            history.replaceState(null, '', '/category/' + encodeURIComponent(state.categorySlug));
        }
        needsFetch = true;
    }

    if (urlSearch && $searchInput.length) {
        $searchInput.val(urlSearch);
        $searchClear.toggleClass('visible', urlSearch.length > 0);
        var $ds = $('#bloomDesktopSearch');
        if ($ds.length) $ds.val(urlSearch);
        state.search = urlSearch;
        needsFetch = true;
    }
    if (urlSort && $sort.length) {
        state.sort = urlSort;
        $sort.val(urlSort);
        needsFetch = true;
    }
    if (needsFetch) {
        state.offset = 0;
        fetchProducts({ offset: 0 });
    }

    // Patch filter handlers to update URL
    var _origFilterByCategory = filterByCategory;
    filterByCategory = function(category, slug) {
        _origFilterByCategory(category, slug);
        updateUrl();
    };

    // Patch search to update URL
    if ($searchInput.length) {
        $searchInput.on('input.urlstate', function() {
            clearTimeout(window._urlUpdateTimer);
            window._urlUpdateTimer = setTimeout(updateUrl, 350);
        });
        $searchClear.on('click.urlstate', updateUrl);
    }
    if ($sort.length) {
        $sort.on('change.urlstate', updateUrl);
    }

    // ── Scroll-to-top button ──
    var scrollBtn = document.querySelector('.scroll-top-btn');
    if (!scrollBtn) {
        scrollBtn = document.createElement('button');
        scrollBtn.className = 'scroll-top-btn';
        scrollBtn.setAttribute('aria-label', 'Scroll to top');
        scrollBtn.innerHTML = '<i class="fa-solid fa-arrow-up"></i>';
        document.body.appendChild(scrollBtn);
    }
    var scrollThrottle;
    function checkScroll() {
        var threshold = window.innerHeight * 2;
        scrollBtn.classList.toggle('visible', window.scrollY > threshold);
    }
    if (TinyShop._shopScrollHandler) {
        window.removeEventListener('scroll', TinyShop._shopScrollHandler);
    }
    TinyShop._shopScrollHandler = function() {
        if (!scrollThrottle) {
            scrollThrottle = setTimeout(function() {
                scrollThrottle = null;
                checkScroll();
            }, 100);
        }
    };
    window.addEventListener('scroll', TinyShop._shopScrollHandler, { passive: true });
    scrollBtn.addEventListener('click', function() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
    checkScroll();
};

$(document).on('page:init', function() {
    TinyShop.initShop();
});
