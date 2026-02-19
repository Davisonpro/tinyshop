/**
 * TinyShop Classic Theme
 *
 * Entry point for all theme-specific UI behaviour.
 * Modules: HeroSlider, ScrollArrows, SearchOverlay, ImageReveal
 *
 * Conventions
 * - IIFE scope, 'use strict', zero globals (aside from window.TinyShopTheme)
 * - No framework dependencies — vanilla DOM only
 * - requestAnimationFrame for layout-triggering reads/writes
 * - Passive listeners where applicable
 * - All timers / observers cleaned up if the host element is removed (SPA-safe)
 */
(function () {
    'use strict';

    /* ================================================================
       UTILITIES
       ================================================================ */

    /** Debounce: trailing-edge only. */
    function debounce(fn, ms) {
        var id;
        return function () {
            var ctx = this, args = arguments;
            clearTimeout(id);
            id = setTimeout(function () { fn.apply(ctx, args); }, ms);
        };
    }


    /* ================================================================
       HERO SLIDER
       Infinite-loop, scroll-snap center-mode carousel.
       Auto-advances every 5 s, pauses on hover / touch / focus.
       ================================================================ */

    function initHeroSlider(slider) {
        var track      = slider.querySelector('.hero-slider-track');
        var dotsWrap   = slider.querySelector('.hero-slider-dots');
        var prevBtn    = slider.querySelector('.hero-slider-prev');
        var nextBtn    = slider.querySelector('.hero-slider-next');
        var origSlides = Array.from(track.querySelectorAll('.hero-slide'));
        var count      = origSlides.length;

        if (!track || count < 2) return;

        slider.setAttribute('data-count', count);

        /* --- Clone edge slides for infinite wrapping --- */
        var lastClone  = origSlides[count - 1].cloneNode(true);
        var firstClone = origSlides[0].cloneNode(true);
        lastClone.setAttribute('aria-hidden', 'true');
        firstClone.setAttribute('aria-hidden', 'true');
        lastClone.classList.add('hero-slide--clone');
        firstClone.classList.add('hero-slide--clone');
        track.insertBefore(lastClone, origSlides[0]);
        track.appendChild(firstClone);

        var allSlides = Array.from(track.querySelectorAll('.hero-slide'));

        /* --- Dots --- */
        var dots = [];
        if (dotsWrap) {
            for (var i = 0; i < count; i++) {
                var dot = document.createElement('button');
                dot.className = 'hero-slider-dot' + (i === 0 ? ' active' : '');
                dot.setAttribute('aria-label', 'Go to slide ' + (i + 1));
                dot.dataset.index = i;
                dotsWrap.appendChild(dot);
                dots.push(dot);
            }
            dotsWrap.addEventListener('click', function (e) {
                var btn = e.target.closest('.hero-slider-dot');
                if (!btn) return;
                var ri = parseInt(btn.dataset.index, 10);
                scrollToSlide(ri + 1, true);
                resetAutoPlay();
            });
        }

        /* --- Layout helpers --- */

        /** Instantly jump (no animation, no snap) to allSlides[idx]. */
        function jumpTo(idx) {
            var slide = allSlides[idx];
            if (!slide) return;
            track.style.scrollSnapType  = 'none';
            track.style.scrollBehavior  = 'auto';
            track.scrollLeft = slide.offsetLeft - (track.offsetWidth - slide.offsetWidth) / 2;
            requestAnimationFrame(function () {
                track.style.scrollSnapType = '';
                track.style.scrollBehavior = '';
            });
        }

        /** Smooth-scroll to allSlides[idx]. */
        function scrollToSlide(idx, smooth) {
            var slide = allSlides[idx];
            if (!slide) return;
            if (smooth) {
                slide.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            } else {
                jumpTo(idx);
            }
        }

        /** Which allSlides index is closest to track centre? */
        function getCenterIdx() {
            var cx   = track.scrollLeft + track.offsetWidth / 2;
            var best = 0;
            var bestDist = Infinity;
            for (var i = 0; i < allSlides.length; i++) {
                var d = Math.abs(allSlides[i].offsetLeft + allSlides[i].offsetWidth / 2 - cx);
                if (d < bestDist) { bestDist = d; best = i; }
            }
            return best;
        }

        /** Distance (px) between two consecutive slides — used for arrow steps. */
        function getStep() {
            if (allSlides.length < 3) return track.offsetWidth;
            return allSlides[2].offsetLeft - allSlides[1].offsetLeft;
        }

        /* --- Initial position (real slide 0 → allSlides[1]) --- */
        requestAnimationFrame(function () { jumpTo(1); });

        /* --- Scroll end → detect clones, loop, update dots --- */
        var jumping   = false;
        var scrollEnd = debounce(function () {
            var idx = getCenterIdx();
            var realIdx;

            if (idx === 0) {
                // On prepended clone → jump to real last
                jumping = true;
                jumpTo(allSlides.length - 2);
                realIdx = count - 1;
                setTimeout(function () { jumping = false; }, 80);
            } else if (idx === allSlides.length - 1) {
                // On appended clone → jump to real first
                jumping = true;
                jumpTo(1);
                realIdx = 0;
                setTimeout(function () { jumping = false; }, 80);
            } else {
                realIdx = idx - 1;
            }

            for (var j = 0; j < dots.length; j++) {
                dots[j].classList.toggle('active', j === realIdx);
            }
        }, 120);

        track.addEventListener('scroll', function () {
            if (!jumping) scrollEnd();
        }, { passive: true });

        /* --- Arrow buttons --- */
        if (prevBtn) {
            prevBtn.addEventListener('click', function () {
                track.scrollBy({ left: -getStep(), behavior: 'smooth' });
                resetAutoPlay();
            });
        }
        if (nextBtn) {
            nextBtn.addEventListener('click', function () {
                track.scrollBy({ left: getStep(), behavior: 'smooth' });
                resetAutoPlay();
            });
        }

        /* --- Auto-play (5 s interval, pause on interaction) --- */
        var AUTO_INTERVAL = 5000;
        var autoTimer     = null;
        var paused        = false;

        function autoAdvance() {
            track.scrollBy({ left: getStep(), behavior: 'smooth' });
        }

        function startAutoPlay() {
            if (autoTimer) return;
            autoTimer = setInterval(autoAdvance, AUTO_INTERVAL);
        }

        function stopAutoPlay() {
            clearInterval(autoTimer);
            autoTimer = null;
        }

        function resetAutoPlay() {
            if (paused) return;
            stopAutoPlay();
            startAutoPlay();
        }

        // Pause while pointer / touch is inside the slider
        slider.addEventListener('mouseenter', function () { paused = true;  stopAutoPlay(); });
        slider.addEventListener('mouseleave', function () { paused = false; startAutoPlay(); });
        slider.addEventListener('touchstart',  function () { paused = true;  stopAutoPlay(); }, { passive: true });
        slider.addEventListener('touchend',    function () { paused = false; startAutoPlay(); }, { passive: true });

        // Pause if tab is hidden
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) { stopAutoPlay(); } else if (!paused) { startAutoPlay(); }
        });

        startAutoPlay();
    }


    /* ================================================================
       SCROLL ARROWS
       Generic handler for [data-scroll-container] wrappers.
       ================================================================ */

    function initScrollArrows(wrapper) {
        var container = wrapper.querySelector('[data-scroll-track]');
        var prevBtn   = wrapper.querySelector('[data-scroll-prev]');
        var nextBtn   = wrapper.querySelector('[data-scroll-next]');

        if (!container) return;

        function scrollBy(dir) {
            container.scrollBy({ left: dir * container.offsetWidth * 0.8, behavior: 'smooth' });
        }

        if (prevBtn) prevBtn.addEventListener('click', function () { scrollBy(-1); });
        if (nextBtn) nextBtn.addEventListener('click', function () { scrollBy(1); });

        // Mouse drag-to-scroll for desktop
        var dragging = false;
        var startX = 0;
        var scrollStart = 0;
        var moved = false;

        container.addEventListener('mousedown', function (e) {
            if (e.button !== 0) return;
            dragging = true;
            moved = false;
            startX = e.pageX;
            scrollStart = container.scrollLeft;
            container.style.cursor = 'grabbing';
            container.style.userSelect = 'none';
        });

        window.addEventListener('mousemove', function (e) {
            if (!dragging) return;
            var dx = e.pageX - startX;
            if (Math.abs(dx) > 3) moved = true;
            container.scrollLeft = scrollStart - dx;
        });

        window.addEventListener('mouseup', function () {
            if (!dragging) return;
            dragging = false;
            container.style.cursor = '';
            container.style.userSelect = '';
        });

        // Prevent click on links after drag
        container.addEventListener('click', function (e) {
            if (moved) {
                e.preventDefault();
                e.stopPropagation();
                moved = false;
            }
        }, true);
    }


    /* ================================================================
       SEARCH OVERLAY
       Toggles .active class, traps focus, Esc to close.
       ================================================================ */

    function initSearchOverlay() {
        var overlay = document.getElementById('searchOverlay');
        if (!overlay) return;

        var input   = overlay.querySelector('.search-overlay-input');
        var closeBtn = overlay.querySelector('.search-overlay-close');
        var results  = document.getElementById('searchOverlayResults');

        var shopPage = document.querySelector('.shop-page');
        var subdomain = shopPage ? shopPage.getAttribute('data-subdomain') : null;
        var currency  = shopPage ? (shopPage.getAttribute('data-currency') || '') : '';
        var searchTimer = null;
        var activeXhr = null;

        function open() {
            overlay.classList.add('active');
            if (input) {
                setTimeout(function () { input.focus(); }, 300);
            }
        }

        function close() {
            overlay.classList.remove('active');
            if (input) input.value = '';
            if (results) results.innerHTML = '';
            if (activeXhr) { activeXhr.abort(); activeXhr = null; }
        }

        function doSearch(query) {
            if (!subdomain || !results) return;
            if (activeXhr) { activeXhr.abort(); activeXhr = null; }

            if (!query) {
                results.innerHTML = '';
                return;
            }

            results.innerHTML = '<div class="search-overlay-loading"><i class="fa-solid fa-spinner fa-spin"></i></div>';

            activeXhr = $.getJSON('/api/shop/' + encodeURIComponent(subdomain) + '/products', {
                search: query,
                limit: 8,
                format: 'html'
            }).done(function (data) {
                if (!data.html || data.total === 0) {
                    results.innerHTML = '<div class="search-overlay-empty">No products found</div>';
                    return;
                }
                results.innerHTML = '<div class="search-overlay-grid">' + data.html + '</div>';
            }).fail(function (_, status) {
                if (status !== 'abort') {
                    results.innerHTML = '<div class="search-overlay-empty">Search failed</div>';
                }
            }).always(function () {
                activeXhr = null;
            });
        }

        // Live search on input
        if (input) {
            input.addEventListener('input', function () {
                var query = input.value.trim();
                clearTimeout(searchTimer);
                searchTimer = setTimeout(function () {
                    doSearch(query);
                }, 300);
            });

            // Submit on Enter — navigate to main catalogue with search
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    var query = input.value.trim();
                    if (!query) return;
                    // If on the shop page, use the existing search input
                    var mainSearch = document.getElementById('searchInput');
                    if (mainSearch) {
                        mainSearch.value = query;
                        $(mainSearch).trigger('input');
                        close();
                        var catalogue = document.getElementById('catalogue');
                        if (catalogue) catalogue.scrollIntoView({ behavior: 'smooth' });
                    } else if (subdomain) {
                        // Navigate to shop page with search
                        window.location.href = '/?search=' + encodeURIComponent(query);
                    }
                }
            });
        }

        // Toggle buttons (any .search-toggle on the page)
        document.addEventListener('click', function (e) {
            if (e.target.closest('.search-toggle')) {
                e.preventDefault();
                open();
            }
        });

        if (closeBtn) closeBtn.addEventListener('click', close);

        // Close on backdrop click
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) close();
        });

        // Esc key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && overlay.classList.contains('active')) close();
        });
    }


    /* ================================================================
       IMAGE REVEAL
       Progressive fade-in for lazy-loaded images inside product cards.
       Uses a single IntersectionObserver instead of per-image onload.
       ================================================================ */

    function initImageReveal() {
        // For browsers without IO, images are already visible via CSS (opacity: 1 in storefront.css)
        if (!('IntersectionObserver' in window)) return;

        var images = document.querySelectorAll('.product-card-img img:not(.loaded), .product-slider-img img:not(.loaded)');
        if (!images.length) return;

        var observer = new IntersectionObserver(function (entries) {
            for (var i = 0; i < entries.length; i++) {
                if (entries[i].isIntersecting) {
                    var img = entries[i].target;
                    if (img.complete && img.naturalWidth > 0) {
                        img.classList.add('loaded');
                    } else {
                        img.addEventListener('load', function () { this.classList.add('loaded'); }, { once: true });
                    }
                    observer.unobserve(img);
                }
            }
        }, { rootMargin: '200px' });

        images.forEach(function (img) { observer.observe(img); });
    }


    /* ================================================================
       SKELETON RENDERER
       Exposed for TinyShop SPA — renders product card loading placeholders.
       ================================================================ */

    function renderSkeletons(count) {
        var html = '';
        for (var i = 0; i < count; i++) {
            html +=
                '<div class="product-card product-card-skeleton">' +
                    '<div class="product-card-img skeleton-pulse"></div>' +
                    '<div class="product-card-body">' +
                        '<div class="skeleton-pulse" style="height:14px;width:80%;border-radius:6px;margin-bottom:8px"></div>' +
                        '<div class="skeleton-pulse" style="height:16px;width:50%;border-radius:6px"></div>' +
                    '</div>' +
                '</div>';
        }
        return html;
    }


    /* ================================================================
       BOOT
       ================================================================ */

    function boot() {
        // Hero sliders (skip already-initialised ones)
        var sliders = document.querySelectorAll('.hero-slider:not([data-init])');
        for (var i = 0; i < sliders.length; i++) {
            sliders[i].setAttribute('data-init', '1');
            initHeroSlider(sliders[i]);
        }

        // Scroll arrow containers (skip already-initialised)
        var scrollContainers = document.querySelectorAll('[data-scroll-container]:not([data-init])');
        for (var j = 0; j < scrollContainers.length; j++) {
            scrollContainers[j].setAttribute('data-init', '1');
            initScrollArrows(scrollContainers[j]);
        }

        // Search overlay
        initSearchOverlay();

        // Image reveal
        initImageReveal();
    }

    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    // Re-init on SPA page transitions (jQuery event from app.js)
    if (window.jQuery) {
        jQuery(document).on('page:init', boot);
    }

    // Expose theme API for SPA integration
    window.TinyShopTheme = {
        renderSkeletons: renderSkeletons,
        reinit: boot
    };

})();
