/* ============================================================
   Global SPA — JSON fragment navigation with page cache,
   link prefetch, instant click, and view transitions
   ============================================================ */
TinyShop.spa = {
    _ready: false,
    _loading: false,
    _xhr: null,
    _loadedScripts: {},

    /* --- Inflight fetch dedup map (url → Promise<data|null>) --- */
    _fetchMap: {},

    /* --- Client-side page cache (5min TTL, max 20 entries) --- */
    _cache: {},
    _cacheTimeout: 300000,

    _cacheKey: function(url) {
        return url.split('#')[0];
    },

    _getCached: function(url) {
        var key = this._cacheKey(url);
        var entry = this._cache[key];
        if (!entry) return null;
        if (Date.now() - entry.time > this._cacheTimeout) {
            delete this._cache[key];
            return null;
        }
        return entry.data;
    },

    _setCache: function(url, data) {
        var key = this._cacheKey(url);
        this._cache[key] = { data: data, time: Date.now() };

        // Evict oldest entry when cache exceeds 20
        var keys = Object.keys(this._cache);
        if (keys.length > 20) {
            var oldest = keys[0], oldestTime = this._cache[oldest].time;
            for (var i = 1; i < keys.length; i++) {
                if (this._cache[keys[i]].time < oldestTime) {
                    oldest = keys[i];
                    oldestTime = this._cache[keys[i]].time;
                }
            }
            delete this._cache[oldest];
        }
    },

    /* --- Link validation helper --- */
    _isInternalLink: function(href) {
        if (!href || href.charAt(0) === '#') return false;
        if (href.indexOf('://') !== -1 && href.indexOf(location.origin) !== 0) return false;
        if (/^(mailto:|tel:|javascript:|blob:)/.test(href)) return false;
        if (/\.(pdf|zip|csv|xlsx?)$/i.test(href)) return false;
        return true;
    },

    init: function() {
        var self = this;

        // Track scripts already loaded on the page
        $('script[src]').each(function() {
            self._loadedScripts[this.src] = true;
        });

        // Store initial state
        history.replaceState({ spa: true, url: location.pathname + location.search }, '', location.pathname + location.search);

        // Instant click — start prefetching on mousedown/touchstart (saves 100-200ms)
        // Reuses inflight hover prefetch via shared _fetchMap — no duplicate requests
        $(document).on('mousedown touchstart', 'a', function(e) {
            if (e.type === 'mousedown' && e.which !== 1) return;
            if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
            if (this.target === '_blank') return;

            var href = this.getAttribute('href');
            if (!self._isInternalLink(href)) return;
            if (href === location.pathname + location.search) return;
            if (href === '/logout') return;

            // Start prefetching if not cached and no inflight fetch for this URL
            if (!self._getCached(href) && !self._loading && window.fetch && !self._fetchMap[href]) {
                self._fetchMap[href] = fetch(href, {
                    headers: { 'X-SPA': '1' },
                    credentials: 'same-origin'
                }).then(function(res) {
                    if (!res.ok) throw new Error(res.status);
                    return res.text();
                }).then(function(text) {
                    var data = self._parseResponse(text);
                    if (data && !data.redirect) {
                        self._setCache(href, data);
                        self._preloadStyles(data.styles);
                    }
                    return data;
                }).catch(function() {
                    return null;
                }).then(function(data) {
                    delete self._fetchMap[href];
                    return data;
                });
            }
        });

        // Intercept ALL internal link clicks
        $(document).on('click', 'a', function(e) {
            if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
            if (this.target === '_blank') return;
            if (e.isDefaultPrevented()) return;

            var href = this.getAttribute('href');
            if (!self._isInternalLink(href)) return;

            e.preventDefault();

            // Don't re-navigate to the same page
            if (href === location.pathname + location.search) return;

            // Remember product card for scroll-back (dashboard products)
            var $card = $(this).closest('[data-id]');
            if ($card.length) {
                try { sessionStorage.setItem('spa_last_product', $card.data('id')); } catch(ex) {}
            }

            self.go(href);
        });

        // Handle browser back/forward
        window.addEventListener('popstate', function(e) {
            if (e.state && e.state.spa) {
                self.go(e.state.url, true);
            }
        });

        // Init link prefetching
        self._initPrefetch();

        self._ready = true;
    },

    /* --- Link prefetching (hover + viewport) --- */
    _initPrefetch: function() {
        var self = this;
        var prefetched = {};
        var _inflight = 0;
        var MAX_INFLIGHT = 3;
        var _budget = 8;

        function shouldPrefetch(href) {
            if (!self._isInternalLink(href)) return false;
            if (href === '/logout') return false;
            if (prefetched[href]) return false;
            if (href === location.pathname + location.search) return false;
            if (self._getCached(href)) return false;
            return true;
        }

        function doPrefetch(href) {
            if (prefetched[href] || !window.fetch) return;
            if (_inflight >= MAX_INFLIGHT || _budget <= 0) return;
            if (self._fetchMap[href]) return;

            prefetched[href] = true;
            _inflight++;
            _budget--;

            self._fetchMap[href] = fetch(href, {
                headers: { 'X-SPA': '1' },
                credentials: 'same-origin',
                priority: 'low'
            }).then(function(res) {
                if (!res.ok) throw new Error(res.status);
                return res.text();
            }).then(function(text) {
                var data = self._parseResponse(text);
                if (data && !data.redirect) {
                    self._setCache(href, data);
                    self._preloadStyles(data.styles);
                }
                return data;
            }).catch(function() {
                return null;
            }).then(function(data) {
                _inflight--;
                delete self._fetchMap[href];
                return data;
            });
        }

        // Hover prefetch (desktop) — 65ms delay, skipped on slow / save-data
        $(document).on('mouseenter', 'a', function() {
            var quality = TinyShop._networkQuality();
            if (quality === 'slow' || quality === 'save-data') return;

            var href = this.getAttribute('href');
            if (!shouldPrefetch(href)) return;
            var el = this;
            el._prefetchTimer = setTimeout(function() {
                doPrefetch(href);
            }, 65);
        }).on('mouseleave', 'a', function() {
            if (this._prefetchTimer) {
                clearTimeout(this._prefetchTimer);
                this._prefetchTimer = null;
            }
        });

        // Viewport prefetch — nav links only, connection-aware, deferred until idle
        if ('IntersectionObserver' in window) {
            var NAV_SELECTORS = '.dash-tab, .dash-sidebar a, .pricing-card a, .pricing-nav a, .land-nav-container a, .land-cta, .auth-footer a, .mk-nav-link';

            var observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (!entry.isIntersecting) return;
                    var a = entry.target.tagName === 'A' ? entry.target : entry.target.querySelector('a');
                    if (a) {
                        var href = a.getAttribute('href');
                        if (shouldPrefetch(href)) doPrefetch(href);
                    }
                    observer.unobserve(entry.target);
                });
            }, { rootMargin: '200px' });

            var _observeTimer = null;
            function observeLinks() {
                prefetched = {};
                _budget = 8;
                observer.disconnect();

                var quality = TinyShop._networkQuality();
                if (quality === 'slow' || quality === 'save-data') return;

                if (_observeTimer) clearTimeout(_observeTimer);
                var idle = window.requestIdleCallback || function(cb) { setTimeout(cb, 200); };
                idle(function() {
                    var els = document.querySelectorAll(NAV_SELECTORS);
                    for (var i = 0; i < els.length; i++) {
                        observer.observe(els[i]);
                    }
                });
            }

            $(document).on('page:init', observeLinks);
            observeLinks();
        }
    },

    /* --- Parse response (JSON fragment or full HTML fallback) --- */
    _parseResponse: function(text) {
        try {
            var data = JSON.parse(text);
            if (data && (data.body !== undefined || data.redirect)) {
                return data;
            }
        } catch(e) {}

        try {
            var parser = new DOMParser();
            var doc = parser.parseFromString(text, 'text/html');

            var styles = [];
            doc.querySelectorAll('head link[rel="stylesheet"], head link[rel="preload"][as="style"]').forEach(function(l) {
                styles.push(l.getAttribute('href'));
            });

            var inlineStyleBlocks = [];
            doc.querySelectorAll('head style').forEach(function(s) {
                if (s.textContent.trim()) {
                    inlineStyleBlocks.push(s.textContent);
                }
            });

            var scripts = [];
            var inlineScripts = [];
            doc.body.querySelectorAll('script').forEach(function(s) {
                if (s.src) {
                    scripts.push(s.src);
                } else if (s.textContent.trim()) {
                    if (s.textContent.indexOf('serviceWorker') !== -1) return;
                    inlineScripts.push(s.textContent);
                }
                s.parentNode.removeChild(s);
            });

            return {
                title: doc.title || '',
                bodyClass: doc.body.className || '',
                csrf: (function() {
                    var m = doc.querySelector('meta[name="csrf-token"]');
                    return m ? m.getAttribute('content') : '';
                })(),
                styles: styles,
                inlineStyles: inlineStyleBlocks,
                scripts: scripts,
                inlineScripts: inlineScripts,
                body: doc.body.innerHTML
            };
        } catch(e) {
            return null;
        }
    },

    go: function(url, isPopState) {
        var self = this;

        if (self._xhr) {
            self._xhr.abort();
            self._xhr = null;
        }

        var cached = self._getCached(url);
        if (cached) {
            if (cached.redirect) {
                if (cached.redirect === '/login' || cached.redirect === '/register') {
                    delete self._cache[self._cacheKey(url)];
                    TinyShop.showLoginModal(url);
                    self._loading = false;
                    self.hideProgress();
                    return;
                }
                self.go(cached.redirect, isPopState);
            } else {
                self._applyPage(cached, url, isPopState);
            }
            return;
        }

        self._loading = true;
        self.showProgress();

        if (self._fetchMap[url]) {
            self._fetchMap[url].then(function(data) {
                if (!data) {
                    self._doFetch(url, isPopState);
                } else if (data.redirect) {
                    self._cache = {};
                    self._loading = false;
                    self.hideProgress();
                    if (data.redirect === '/login' && url !== '/logout') {
                        TinyShop.showLoginModal(url);
                    } else {
                        self.go(data.redirect, isPopState);
                    }
                } else {
                    self._setCache(url, data);
                    self._applyPage(data, url, isPopState);
                }
            }).catch(function() {
                self._doFetch(url, isPopState);
            });
            return;
        }

        self._doFetch(url, isPopState);
    },

    _doFetch: function(url, isPopState) {
        var self = this;

        self._xhr = $.ajax({
            url: url,
            method: 'GET',
            dataType: 'text',
            headers: { 'X-SPA': '1' },
            success: function(text, status, xhr) {
                self._xhr = null;

                var data = self._parseResponse(text);
                if (!data) {
                    window.location.href = url;
                    return;
                }

                if (data.redirect) {
                    self._cache = {};
                    if (data.redirect === '/login' && url !== '/logout') {
                        self._loading = false;
                        self.hideProgress();
                        TinyShop.showLoginModal(url);
                        return;
                    }
                    self.go(data.redirect, isPopState);
                    return;
                }

                self._setCache(url, data);
                self._applyPage(data, url, isPopState);
            },
            error: function(xhr, status) {
                self._xhr = null;
                self._loading = false;
                self.hideProgress();
                if (status === 'abort') return;
                window.location.href = url;
            }
        });
    },

    /* --- Apply page data with view transition --- */
    _applyPage: function(data, url, isPopState) {
        var self = this;

        function doSwap(onDomReady) {
            document.title = data.title || (document.querySelector('meta[name="apple-mobile-web-app-title"]') || {}).content || 'Shop';

            var announcer = document.getElementById('spaAnnouncer');
            if (!announcer) {
                announcer = document.createElement('div');
                announcer.id = 'spaAnnouncer';
                announcer.setAttribute('role', 'status');
                announcer.setAttribute('aria-live', 'polite');
                announcer.setAttribute('aria-atomic', 'true');
                announcer.className = 'sr-only';
                document.body.appendChild(announcer);
            }
            announcer.textContent = '';
            setTimeout(function() {
                announcer.textContent = (data.title || 'Page') + ' loaded';
            }, 100);

            document.body.className = data.bodyClass || '';

            if (data.csrf) {
                var oldCsrf = document.querySelector('meta[name="csrf-token"]');
                if (oldCsrf) oldCsrf.setAttribute('content', data.csrf);
                TinyShop.csrfToken = data.csrf;
                $.ajaxSetup({ headers: { 'X-CSRF-Token': data.csrf } });
            }

            self._syncStylesFromList(data.styles || [], data.inlineStyles || [], function() {
                document.body.innerHTML = data.body;

                if (!isPopState) {
                    history.pushState({ spa: true, url: url }, '', url);
                    window.scrollTo(0, 0);
                }

                if (onDomReady) onDomReady();

                var scriptObjs = [];
                (data.scripts || []).forEach(function(src) {
                    scriptObjs.push({ src: src, text: '', type: '' });
                });
                (data.inlineScripts || []).forEach(function(code) {
                    scriptObjs.push({ src: '', text: code, type: '' });
                });

                self.loadScripts(scriptObjs, function() {
                    self._loading = false;
                    self.hideProgress();
                    $(document).trigger('page:init');
                });
            });
        }

        if (document.startViewTransition) {
            document.startViewTransition(function() {
                return new Promise(function(resolve) {
                    doSwap(resolve);
                });
            });
        } else {
            document.body.classList.add('spa-transitioning');
            requestAnimationFrame(function() {
                requestAnimationFrame(function() {
                    doSwap(function() {
                        document.body.classList.remove('spa-transitioning');
                        document.body.classList.add('spa-transitioned');
                        setTimeout(function() {
                            document.body.classList.remove('spa-transitioned');
                        }, 100);
                    });
                });
            });
        }
    },

    /* --- Preload stylesheets into browser cache --- */
    _preloadStyles: function(styleHrefs) {
        if (!styleHrefs || !styleHrefs.length || !window.fetch) return;
        var loaded = {};
        var links = document.head.querySelectorAll('link[rel="stylesheet"]');
        for (var i = 0; i < links.length; i++) {
            loaded[links[i].getAttribute('href')] = true;
        }
        for (var i = 0; i < styleHrefs.length; i++) {
            if (!loaded[styleHrefs[i]]) {
                fetch(styleHrefs[i], { credentials: 'same-origin', priority: 'low' }).catch(function() {});
            }
        }
    },

    /* --- Sync stylesheets and inline styles --- */
    _syncStylesFromList: function(styleHrefs, inlineStyles, onReady) {
        var neededSet = {};
        for (var i = 0; i < styleHrefs.length; i++) neededSet[styleHrefs[i]] = true;

        var existingHrefs = {};
        var allLinks = document.head.querySelectorAll('link[rel="stylesheet"], link[rel="preload"][as="style"]');
        for (var i = 0; i < allLinks.length; i++) {
            existingHrefs[allLinks[i].getAttribute('href')] = true;
        }

        var oldSpaStyles = document.head.querySelectorAll('[data-spa-style]');

        var newLinks = [];
        for (var i = 0; i < styleHrefs.length; i++) {
            if (!existingHrefs[styleHrefs[i]]) {
                var link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = styleHrefs[i];
                link.setAttribute('data-spa-style', '');
                document.head.appendChild(link);
                newLinks.push(link);
            }
        }

        function finish() {
            for (var i = 0; i < oldSpaStyles.length; i++) {
                var el = oldSpaStyles[i];
                if (el.tagName === 'STYLE') {
                    if (el.parentNode) el.parentNode.removeChild(el);
                } else if (el.tagName === 'LINK') {
                    var href = el.getAttribute('href');
                    if (!neededSet[href] && el.parentNode) {
                        el.parentNode.removeChild(el);
                    }
                }
            }

            if (inlineStyles && inlineStyles.length) {
                for (var i = 0; i < inlineStyles.length; i++) {
                    var style = document.createElement('style');
                    style.textContent = inlineStyles[i];
                    style.setAttribute('data-spa-style', '');
                    document.head.appendChild(style);
                }
            }

            if (onReady) onReady();
        }

        if (newLinks.length === 0) {
            finish();
            return;
        }

        var remaining = newLinks.length;
        var done = false;

        function check() {
            if (done) return;
            if (--remaining <= 0) {
                done = true;
                finish();
            }
        }

        for (var i = 0; i < newLinks.length; i++) {
            newLinks[i].onload = check;
            newLinks[i].onerror = check;
        }

        setTimeout(function() {
            if (!done) {
                done = true;
                finish();
            }
        }, 3000);
    },

    loadScripts: function(scripts, callback) {
        var self = this;
        var externals = [];
        var inlines = [];

        scripts.forEach(function(s) {
            var t = (s.type || '').toLowerCase();
            if (t && t !== 'text/javascript' && t !== 'module') return;

            if (s.src) {
                var a = document.createElement('a');
                a.href = s.src;
                var fullSrc = a.href;
                if (!self._loadedScripts[fullSrc]) {
                    externals.push(fullSrc);
                    self._loadedScripts[fullSrc] = true;
                }
            } else if (s.text && s.text.trim()) {
                if (s.text.indexOf('serviceWorker') !== -1 && s.text.indexOf('register') !== -1) return;
                inlines.push(s.text);
            }
        });

        function loadNext(idx) {
            if (idx >= externals.length) {
                inlines.forEach(function(code) {
                    try {
                        var el = document.createElement('script');
                        el.textContent = code;
                        document.body.appendChild(el);
                        document.body.removeChild(el);
                    } catch(err) {
                        if (window.console) console.warn('[SPA] inline script error:', err);
                    }
                });
                if (callback) callback();
                return;
            }
            var el = document.createElement('script');
            el.src = externals[idx];
            el.onload = function() { loadNext(idx + 1); };
            el.onerror = function() { loadNext(idx + 1); };
            document.body.appendChild(el);
        }
        loadNext(0);
    },

    _getBar: function() {
        var bar = document.getElementById('spaProgress');
        if (!bar) {
            var track = document.createElement('div');
            track.className = 'spa-progress-track';
            track.innerHTML = '<div class="spa-progress-bar" id="spaProgress"></div>';
            document.body.appendChild(track);
            bar = document.getElementById('spaProgress');
        }
        return bar;
    },

    showProgress: function() {
        var bar = this._getBar();
        bar.className = 'spa-progress-bar';
        bar.style.width = '0%';
        bar.offsetWidth; // force reflow
        bar.classList.add('spa-active');
        bar.style.width = '70%';
    },

    hideProgress: function() {
        var bar = this._getBar();
        bar.style.width = '100%';
        setTimeout(function() {
            bar.classList.add('spa-done');
            bar.classList.remove('spa-active');
            setTimeout(function() {
                bar.style.width = '0%';
                bar.classList.remove('spa-done');
            }, 200);
        }, 150);
    }
};
