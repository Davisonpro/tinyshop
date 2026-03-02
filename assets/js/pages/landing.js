/**
 * Landing page — nav scroll effect and reveal animations.
 *
 * Uses page:init so it re-initialises on SPA navigation.
 * Cleans up window listeners when leaving the landing page
 * to avoid leaking handlers.
 *
 * @since 1.0.0
 */
(function() {
    var _scrollHandler = null;
    var _observer = null;

    $(document).on('page:init', function() {
        // Clean up previous instance
        if (_scrollHandler) {
            window.removeEventListener('scroll', _scrollHandler);
            _scrollHandler = null;
        }
        if (_observer) {
            _observer.disconnect();
            _observer = null;
        }

        var nav = document.getElementById('mkNav');
        if (!nav) return;

        // Nav scroll effect
        _scrollHandler = function() {
            nav.classList.toggle('scrolled', window.scrollY > 10);
        };
        window.addEventListener('scroll', _scrollHandler, { passive: true });

        // Reveal animations via IntersectionObserver
        var els = document.querySelectorAll('.land-reveal');
        if (!els.length) return;

        if ('IntersectionObserver' in window) {
            _observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(e) {
                    if (e.isIntersecting) {
                        e.target.classList.add('revealed');
                        _observer.unobserve(e.target);
                    }
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -30px 0px' });
            els.forEach(function(el) { _observer.observe(el); });
        } else {
            els.forEach(function(el) { el.classList.add('revealed'); });
        }
    });
})();
