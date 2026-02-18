/**
 * TinyShop Hero Slider — Infinite loop (merry-go-round), scroll-snap center mode.
 * Clones edge slides so the last slide peeks left of the first, and vice versa.
 */
(function () {
    'use strict';

    document.querySelectorAll('.hero-slider').forEach(function (slider) {
        var track = slider.querySelector('.hero-slider-track');
        var origSlides = Array.from(slider.querySelectorAll('.hero-slide'));
        var dotsContainer = slider.querySelector('.hero-slider-dots');

        if (!track || origSlides.length < 2) return;

        var count = origSlides.length;
        slider.setAttribute('data-count', count);

        // --- Clone edge slides for infinite loop ---
        var lastClone = origSlides[count - 1].cloneNode(true);
        var firstClone = origSlides[0].cloneNode(true);
        lastClone.setAttribute('aria-hidden', 'true');
        firstClone.setAttribute('aria-hidden', 'true');
        track.insertBefore(lastClone, origSlides[0]);
        track.appendChild(firstClone);

        // All slides: [cloneLast, orig0, orig1, ..., origN-1, cloneFirst]
        var allSlides = Array.from(track.querySelectorAll('.hero-slide'));

        // --- Instantly jump to a slide index (no animation, no snap) ---
        function jumpTo(idx) {
            var slide = allSlides[idx];
            if (!slide) return;
            track.style.scrollSnapType = 'none';
            track.style.scrollBehavior = 'auto';
            track.scrollLeft = slide.offsetLeft - (track.offsetWidth - slide.offsetWidth) / 2;
            // Re-enable on next frame
            requestAnimationFrame(function () {
                track.style.scrollSnapType = '';
                track.style.scrollBehavior = '';
            });
        }

        // Start at real first slide (index 1)
        requestAnimationFrame(function () {
            jumpTo(1);
        });

        // --- Create dots for real slides only ---
        if (dotsContainer) {
            for (var i = 0; i < count; i++) {
                var dot = document.createElement('button');
                dot.className = 'hero-slider-dot' + (i === 0 ? ' active' : '');
                dot.setAttribute('aria-label', 'Go to slide ' + (i + 1));
                (function (ri) {
                    dot.addEventListener('click', function () {
                        allSlides[ri + 1].scrollIntoView({
                            behavior: 'smooth', block: 'nearest', inline: 'center'
                        });
                    });
                })(i);
                dotsContainer.appendChild(dot);
            }
        }

        var dots = dotsContainer ? Array.from(dotsContainer.querySelectorAll('.hero-slider-dot')) : [];

        // --- Find which slide is closest to the track center ---
        function getCenterIdx() {
            var cx = track.scrollLeft + track.offsetWidth / 2;
            var best = 0, bestD = 1e9;
            allSlides.forEach(function (s, i) {
                var d = Math.abs(s.offsetLeft + s.offsetWidth / 2 - cx);
                if (d < bestD) { bestD = d; best = i; }
            });
            return best;
        }

        // --- On scroll end: detect clone, jump to real counterpart ---
        var jumping = false;
        var timer;
        track.addEventListener('scroll', function () {
            if (jumping) return;
            clearTimeout(timer);
            timer = setTimeout(function () {
                var idx = getCenterIdx();
                var realIdx;

                if (idx === 0) {
                    // On prepended clone (last slide's clone) → jump to real last
                    jumping = true;
                    jumpTo(allSlides.length - 2);
                    realIdx = count - 1;
                    setTimeout(function () { jumping = false; }, 60);
                } else if (idx === allSlides.length - 1) {
                    // On appended clone (first slide's clone) → jump to real first
                    jumping = true;
                    jumpTo(1);
                    realIdx = 0;
                    setTimeout(function () { jumping = false; }, 60);
                } else {
                    realIdx = idx - 1;
                }

                dots.forEach(function (d, j) {
                    d.classList.toggle('active', j === realIdx);
                });
            }, 120);
        });

        // --- Arrow buttons: scroll by one slide width ---
        var prevBtn = slider.querySelector('.hero-slider-prev');
        var nextBtn = slider.querySelector('.hero-slider-next');

        function getStep() {
            if (allSlides.length < 3) return track.offsetWidth;
            return allSlides[2].offsetLeft - allSlides[1].offsetLeft;
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', function () {
                track.scrollBy({ left: -getStep(), behavior: 'smooth' });
            });
        }
        if (nextBtn) {
            nextBtn.addEventListener('click', function () {
                track.scrollBy({ left: getStep(), behavior: 'smooth' });
            });
        }
    });

    // --- Generic scroll-arrow handler for any container ---
    document.querySelectorAll('[data-scroll-container]').forEach(function (wrapper) {
        var container = wrapper.querySelector('[data-scroll-track]');
        var prevBtn = wrapper.querySelector('[data-scroll-prev]');
        var nextBtn = wrapper.querySelector('[data-scroll-next]');

        if (!container) return;

        if (prevBtn) {
            prevBtn.addEventListener('click', function () {
                container.scrollBy({ left: -container.offsetWidth * 0.8, behavior: 'smooth' });
            });
        }
        if (nextBtn) {
            nextBtn.addEventListener('click', function () {
                container.scrollBy({ left: container.offsetWidth * 0.8, behavior: 'smooth' });
            });
        }
    });
})();
