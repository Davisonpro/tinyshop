/* ============================================================
   Image Viewer — fullscreen lightbox for product gallery
   ============================================================ */
TinyShop.imageViewer = {
    _el: null,
    _images: [],
    _current: 0,
    _keyBound: false,
    _scale: 1,
    _panX: 0,
    _panY: 0,

    open: function(images, startIndex) {
        var self = this;
        self._images = images;
        self._current = startIndex || 0;

        // Rebuild if element was removed by SPA body swap
        if (!self._el || !self._el.isConnected) {
            self._el = null;
            self._build();
        }

        self._show();
        setTimeout(function() { self._el.classList.add('active'); }, 10);
    },

    close: function() {
        var self = this;
        if (!self._el) return;
        if (self._resetZoom) self._resetZoom();
        self._el.classList.remove('active');
    },

    _build: function() {
        var self = this;
        var div = document.createElement('div');
        div.className = 'image-viewer';
        div.innerHTML =
            '<button class="image-viewer-close" aria-label="Close">' +
                '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' +
            '</button>' +
            '<button class="image-viewer-prev" aria-label="Previous">' +
                '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>' +
            '</button>' +
            '<img class="image-viewer-img" src="" alt="">' +
            '<button class="image-viewer-next" aria-label="Next">' +
                '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>' +
            '</button>' +
            '<div class="image-viewer-counter"></div>';
        document.body.appendChild(div);
        self._el = div;

        // Close
        div.querySelector('.image-viewer-close').addEventListener('click', function() { self.close(); });
        div.addEventListener('click', function(e) {
            if (e.target === div) self.close();
        });

        // Nav
        div.querySelector('.image-viewer-prev').addEventListener('click', function(e) {
            e.stopPropagation();
            self._go(self._current - 1);
        });
        div.querySelector('.image-viewer-next').addEventListener('click', function(e) {
            e.stopPropagation();
            self._go(self._current + 1);
        });

        // Keyboard — bind once on document, survives rebuilds
        if (!self._keyBound) {
            self._keyBound = true;
            document.addEventListener('keydown', function(e) {
                if (!self._el || !self._el.classList.contains('active')) return;
                if (e.key === 'Escape') self.close();
                if (e.key === 'ArrowLeft') self._go(self._current - 1);
                if (e.key === 'ArrowRight') self._go(self._current + 1);
            });
        }

        // --- Touch gestures: swipe, pinch-to-zoom, pan, double-tap ---
        var img = div.querySelector('.image-viewer-img');
        var startX = 0, startY = 0, tracking = false;
        var pinchStartDist = 0, pinchStartScale = 1;
        var panStartX = 0, panStartY = 0, panBaseX = 0, panBaseY = 0, isPanning = false;
        var lastTap = 0;

        function getDistance(t1, t2) {
            var dx = t1.clientX - t2.clientX;
            var dy = t1.clientY - t2.clientY;
            return Math.sqrt(dx * dx + dy * dy);
        }

        function applyTransform() {
            img.style.transform = 'translate(' + self._panX + 'px, ' + self._panY + 'px) scale(' + self._scale + ')';
        }

        self._resetZoom = function() {
            self._scale = 1;
            self._panX = 0;
            self._panY = 0;
            img.style.transform = '';
            img.style.transition = '';
        };

        img.addEventListener('touchstart', function(e) {
            img.style.transition = 'none';

            if (e.touches.length === 2) {
                // Pinch start
                tracking = false;
                isPanning = false;
                pinchStartDist = getDistance(e.touches[0], e.touches[1]);
                pinchStartScale = self._scale;
                return;
            }

            if (e.touches.length === 1) {
                // Double-tap detection
                var now = Date.now();
                if (now - lastTap < 300) {
                    e.preventDefault();
                    if (self._scale > 1.05) {
                        img.style.transition = 'transform 0.25s ease';
                        self._scale = 1;
                        self._panX = 0;
                        self._panY = 0;
                    } else {
                        img.style.transition = 'transform 0.25s ease';
                        self._scale = 2;
                        self._panX = 0;
                        self._panY = 0;
                    }
                    applyTransform();
                    lastTap = 0;
                    tracking = false;
                    return;
                }
                lastTap = now;

                if (self._scale > 1.05) {
                    // Pan mode when zoomed
                    isPanning = true;
                    tracking = false;
                    panStartX = e.touches[0].clientX;
                    panStartY = e.touches[0].clientY;
                    panBaseX = self._panX;
                    panBaseY = self._panY;
                } else {
                    // Swipe mode at 1×
                    isPanning = false;
                    startX = e.touches[0].clientX;
                    startY = e.touches[0].clientY;
                    tracking = true;
                }
            }
        }, { passive: false });

        img.addEventListener('touchmove', function(e) {
            if (e.touches.length === 2) {
                // Pinch zoom
                e.preventDefault();
                var dist = getDistance(e.touches[0], e.touches[1]);
                var newScale = pinchStartScale * (dist / pinchStartDist);
                self._scale = Math.min(Math.max(newScale, 1), 3);
                if (self._scale <= 1.02) {
                    self._panX = 0;
                    self._panY = 0;
                }
                applyTransform();
                return;
            }

            if (isPanning && e.touches.length === 1) {
                e.preventDefault();
                self._panX = panBaseX + (e.touches[0].clientX - panStartX);
                self._panY = panBaseY + (e.touches[0].clientY - panStartY);
                applyTransform();
            }
        }, { passive: false });

        img.addEventListener('touchend', function(e) {
            if (e.touches.length === 0 && isPanning) {
                isPanning = false;
                // Snap back if scale is ~1
                if (self._scale <= 1.02) {
                    img.style.transition = 'transform 0.2s ease';
                    self._resetZoom();
                }
                return;
            }

            if (!tracking) return;
            tracking = false;
            var dx = e.changedTouches[0].clientX - startX;
            var dy = e.changedTouches[0].clientY - startY;
            if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) {
                if (dx < 0) self._go(self._current + 1);
                else self._go(self._current - 1);
            }
        }, { passive: true });

        // Transition end — remove from DOM after close
        div.addEventListener('transitionend', function() {
            if (!div.classList.contains('active')) {
                div.style.display = '';
            }
        });
    },

    _show: function() {
        var self = this;
        var img = self._el.querySelector('.image-viewer-img');
        var counter = self._el.querySelector('.image-viewer-counter');
        var prev = self._el.querySelector('.image-viewer-prev');
        var next = self._el.querySelector('.image-viewer-next');

        self._el.style.display = 'flex';
        img.src = self._images[self._current];
        counter.textContent = (self._current + 1) + ' / ' + self._images.length;
        prev.style.display = self._images.length > 1 ? '' : 'none';
        next.style.display = self._images.length > 1 ? '' : 'none';
        counter.style.display = self._images.length > 1 ? '' : 'none';
    },

    _go: function(idx) {
        var self = this;
        if (self._images.length <= 1) return;
        if (self._resetZoom) self._resetZoom();
        self._current = ((idx % self._images.length) + self._images.length) % self._images.length;
        self._show();
    }
};

// Image viewer — delegated click survives SPA navigation
$(document).on('click', '.product-gallery-slide img', function() {
    var gallery = document.getElementById('productGallery');
    if (!gallery) return;
    var slides = gallery.querySelectorAll('.product-gallery-slide img');
    var images = [];
    slides.forEach(function(s) { images.push(s.src); });
    var idx = Array.prototype.indexOf.call(slides, this);
    TinyShop.imageViewer.open(images, idx >= 0 ? idx : 0);
});
