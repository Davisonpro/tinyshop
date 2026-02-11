/**
 * TinyShop — Global JS
 */
var TinyShop = window.TinyShop || {};

/**
 * CSRF protection — covers both jQuery $.ajax and native fetch().
 * Runs immediately (not in DOMReady) since scripts load at body end.
 */
(function() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    var token = meta ? meta.getAttribute('content') : '';
    TinyShop.csrfToken = token;

    if (token) {
        // jQuery AJAX
        $.ajaxSetup({ headers: { 'X-CSRF-Token': token } });

        // Native fetch — auto-include CSRF header on same-origin requests
        var _fetch = window.fetch;
        window.fetch = function(url, opts) {
            opts = opts || {};
            var isSameOrigin = typeof url === 'string' && (url.startsWith('/') || url.startsWith(location.origin));
            if (isSameOrigin) {
                if (opts.headers instanceof Headers) {
                    if (!opts.headers.has('X-CSRF-Token')) opts.headers.set('X-CSRF-Token', token);
                } else {
                    opts.headers = Object.assign({ 'X-CSRF-Token': token }, opts.headers || {});
                }
            }
            return _fetch.call(this, url, opts);
        };
    }
})();

/**
 * Toast notification
 */
TinyShop.toast = function(message, type) {
    type = type || 'success';
    var $toast = $('#toast');
    var icons = {
        success: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>',
        error: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
        warning: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>'
    };
    var icon = icons[type] || icons.success;
    $toast.attr('class', 'toast toast-' + type).html('<span class="toast-icon">' + icon + '</span><span class="toast-msg">' + $('<span>').text(message).html() + '</span>').addClass('show');
    clearTimeout(TinyShop._toastTimer);
    TinyShop._toastTimer = setTimeout(function() {
        $toast.removeClass('show');
    }, 3000);
};

$(function() {
    // Smooth scroll for anchor links
    $(document).on('click', 'a[href^="#"]', function(e) {
        var target = $(this.getAttribute('href'));
        if (target.length) {
            e.preventDefault();
            $('html, body').animate({ scrollTop: target.offset().top - 60 }, 400);
        }
    });

    // Variation option selection on product detail page
    $(document).on('click', '.product-variation-option', function() {
        var $group = $(this).closest('.product-variation-options');
        $group.find('.product-variation-option').removeClass('selected');
        $(this).addClass('selected');
    });

    // Category + search filtering on storefront
    var $productCount = $('#productCount');
    var $searchEmpty = $('#searchEmpty');
    var $catalogue = $('#catalogue');
    var totalProducts = $catalogue.find('.product-card').length;
    var activeCategory = 'all';

    function updateProductCount(visible) {
        if ($productCount.length) {
            $productCount.text(visible + (visible === 1 ? ' product' : ' products'));
        }
        if (visible === 0 && totalProducts > 0) {
            $catalogue.hide();
            $searchEmpty.show();
        } else {
            $searchEmpty.hide();
            $catalogue.show();
        }
    }

    function scrollIntoCenter(el) {
        var container = el.parentElement;
        if (container) {
            var scrollLeft = el.offsetLeft - (container.offsetWidth / 2) + (el.offsetWidth / 2);
            container.scrollTo({ left: scrollLeft, behavior: 'smooth' });
        }
    }

    function filterByCategory(category) {
        activeCategory = category;
        var $cards = $catalogue.find('.product-card');
        var visible = 0;

        // Clear search
        var $si = $('#searchInput');
        if ($si.length && $si.val()) {
            $si.val('');
            $('#searchClear').removeClass('visible');
        }

        if (category === 'all') {
            $cards.show();
            visible = totalProducts;
        } else {
            var ids = category.split(',');
            $cards.each(function() {
                var $card = $(this);
                var cardCat = String($card.data('category'));
                if (ids.indexOf(cardCat) !== -1) {
                    $card.show();
                    visible++;
                } else {
                    $card.hide();
                }
            });
        }

        updateProductCount(visible);

        // Sync pill tabs
        $('#categoryTabs .category-tab').removeClass('active');
        $('#categoryTabs .category-tab').each(function() {
            if (String($(this).data('category')) === category) $(this).addClass('active');
        });
        if (category === 'all') $('#categoryTabs .category-tab').first().addClass('active');

        // Sync image cards
        $('#categoryCards .category-card').removeClass('active');
        $('#categoryCards .category-card').each(function() {
            if (String($(this).data('category')) === category) $(this).addClass('active');
        });
        if (category === 'all') $('#categoryCards .category-card').first().addClass('active');
    }

    // Category tabs (pill filters)
    $('#categoryTabs').on('click', '.category-tab', function() {
        scrollIntoCenter(this);
        filterByCategory(String($(this).data('category')));
    });

    // Category image cards
    $('#categoryCards').on('click', '.category-card', function() {
        scrollIntoCenter(this);
        filterByCategory(String($(this).data('category')));
    });

    // --- Search toggle (for themes that use icon-only search) ---
    var $searchToggle = $('#searchToggle');
    var $shopSearch = $('#shopSearch');

    if ($searchToggle.length && $searchToggle.is(':visible')) {
        // Theme is using toggle mode — collapse the search bar initially
        $shopSearch.addClass('search-collapsed');

        $searchToggle.on('click', function() {
            if ($shopSearch.hasClass('search-collapsed')) {
                $shopSearch.removeClass('search-collapsed').addClass('search-expanded');
                $searchToggle.hide();
                setTimeout(function() { $('#searchInput').focus(); }, 150);
            }
        });
    }

    // --- Search ---
    var searchTimer;
    var $searchInput = $('#searchInput');
    var $searchClear = $('#searchClear');

    if ($searchInput.length) {
        $searchInput.on('input', function() {
            var query = $.trim($(this).val()).toLowerCase();
            $searchClear.toggleClass('visible', query.length > 0);

            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() {
                var $cards = $catalogue.find('.product-card');
                var visible = 0;

                if (!query) {
                    filterByCategory(activeCategory);
                    return;
                }

                $cards.each(function() {
                    var $card = $(this);
                    var name = ($card.find('.product-title').text() || '').toLowerCase();
                    if (name.indexOf(query) !== -1) {
                        $card.show();
                        visible++;
                    } else {
                        $card.hide();
                    }
                });

                updateProductCount(visible);
            }, 150);
        });

        $searchClear.on('click', function() {
            $searchInput.val('').trigger('input');
            // If using toggle mode, collapse search bar back
            if ($searchToggle.length && $shopSearch.hasClass('search-expanded')) {
                $shopSearch.removeClass('search-expanded').addClass('search-collapsed');
                $searchToggle.show();
            } else {
                $searchInput.focus();
            }
        });
    }

    // --- Share Sheet ---
    var $backdrop = $('#shareSheetBackdrop');
    if ($backdrop.length) {
        // Open share sheet
        $(document).on('click', '[data-share-trigger]', function(e) {
            e.preventDefault();
            var url = window.location.href;
            var title = document.title;

            $backdrop.find('[data-share-action="whatsapp"]').attr('href',
                'https://wa.me/?text=' + encodeURIComponent(title + ' ' + url));
            $backdrop.find('[data-share-action="facebook"]').attr('href',
                'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url));
            $backdrop.find('[data-share-action="twitter"]').attr('href',
                'https://twitter.com/intent/tweet?text=' + encodeURIComponent(title) + '&url=' + encodeURIComponent(url));
            $backdrop.find('[data-share-action="email"]').attr('href',
                'mailto:?subject=' + encodeURIComponent(title) + '&body=' + encodeURIComponent(url));

            $backdrop.addClass('active');
        });

        // Close share sheet (backdrop click or close button)
        $backdrop.on('click', function(e) {
            if (e.target === this) $backdrop.removeClass('active');
        });
        $backdrop.on('click', '.share-sheet-close', function() {
            $backdrop.removeClass('active');
        });

        // Copy link action
        $backdrop.on('click', '[data-share-action="copy"]', function() {
            var $label = $(this).find('.share-sheet-label');
            navigator.clipboard.writeText(window.location.href).then(function() {
                $label.text('Copied!');
                setTimeout(function() {
                    $label.text('Copy Link');
                    $backdrop.removeClass('active');
                }, 1200);
            });
        });

        // Close on share link click (after short delay)
        $backdrop.on('click', 'a[data-share-action]', function() {
            var $b = $backdrop;
            setTimeout(function() { $b.removeClass('active'); }, 300);
        });
    }
});
