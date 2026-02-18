var TinyShop = window.TinyShop = window.TinyShop || {};
TinyShop._networkQuality = function() {
  var conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
  if (!conn) return "fast";
  if (conn.saveData) return "save-data";
  var ect = conn.effectiveType || "";
  if (ect === "slow-2g" || ect === "2g") return "slow";
  if (ect === "3g") return "medium";
  return "fast";
};
(function() {
  var meta = document.querySelector('meta[name="csrf-token"]');
  var token = meta ? meta.getAttribute("content") : "";
  TinyShop.csrfToken = token;
  if (token) {
    $.ajaxSetup({ headers: { "X-CSRF-Token": token } });
    var _fetch = window.fetch;
    window.fetch = function(url, opts) {
      opts = opts || {};
      var currentToken = TinyShop.csrfToken;
      var isSameOrigin = typeof url === "string" && (url.startsWith("/") || url.startsWith(location.origin));
      if (isSameOrigin && currentToken) {
        if (opts.headers instanceof Headers) {
          if (!opts.headers.has("X-CSRF-Token")) opts.headers.set("X-CSRF-Token", currentToken);
        } else {
          opts.headers = Object.assign({ "X-CSRF-Token": currentToken }, opts.headers || {});
        }
      }
      return _fetch.call(this, url, opts);
    };
  }
})();
(function() {
  var MAX_TOASTS = 3;
  var DISMISS_MS = 3e3;
  var _queue = [];
  var _id = 0;
  var _icons = {
    success: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>',
    error: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
    warning: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>'
  };
  function getContainer() {
    var c = document.getElementById("toast-container");
    if (!c) {
      c = document.createElement("div");
      c.id = "toast-container";
      c.className = "toast-container";
      c.setAttribute("aria-live", "polite");
      c.setAttribute("aria-atomic", "false");
      document.body.appendChild(c);
    }
    return c;
  }
  function dismiss(id) {
    var idx = -1;
    for (var i = 0; i < _queue.length; i++) {
      if (_queue[i].id === id) {
        idx = i;
        break;
      }
    }
    if (idx === -1) return;
    var item = _queue[idx];
    clearTimeout(item.timer);
    item.el.classList.add("toast-out");
    setTimeout(function() {
      if (item.el.parentNode) item.el.parentNode.removeChild(item.el);
    }, 250);
    _queue.splice(idx, 1);
  }
  TinyShop.toast = function(message, type) {
    type = type || "success";
    var container = getContainer();
    var id = ++_id;
    var icon = _icons[type] || _icons.success;
    while (_queue.length >= MAX_TOASTS) {
      dismiss(_queue[0].id);
    }
    var el = document.createElement("div");
    el.className = "toast-item toast-" + type;
    el.setAttribute("role", "alert");
    var safe = document.createElement("span");
    safe.textContent = message;
    el.innerHTML = '<span class="toast-icon">' + icon + '</span><span class="toast-msg">' + safe.innerHTML + '</span><button type="button" class="toast-close" aria-label="Dismiss">&times;</button>';
    container.appendChild(el);
    requestAnimationFrame(function() {
      requestAnimationFrame(function() {
        el.classList.add("toast-show");
      });
    });
    var timer = setTimeout(function() {
      dismiss(id);
    }, DISMISS_MS);
    el.querySelector(".toast-close").addEventListener("click", function() {
      dismiss(id);
    });
    _queue.push({ id, el, timer });
  };
})();
TinyShop.navigate = function(url) {
  if (typeof TinyShop.closeModal === "function") TinyShop.closeModal();
  document.body.style.overflow = "";
  if (TinyShop.spa && TinyShop.spa._ready) {
    TinyShop.spa.go(url);
  } else {
    window.location.href = url;
  }
};
TinyShop.formatPrice = function(n) {
  var num = parseFloat(n) || 0;
  return num.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
};
(function() {
  var _overlay = null;
  var _showing = false;
  var _pendingUrl = null;
  function getOverlay() {
    if (_overlay && _overlay.isConnected) return _overlay;
    var div = document.createElement("div");
    div.className = "login-modal-overlay";
    div.id = "loginModal";
    div.innerHTML = '<div class="login-modal-box"><div class="login-modal-handle"></div><div class="login-modal-header"><h2>Session expired</h2><p>Please sign in again to continue.</p></div><div class="login-modal-body"><form id="loginModalForm"><div class="login-modal-field"><label for="loginModalEmail">Email</label><input type="email" id="loginModalEmail" placeholder="you@example.com" autocomplete="email" required></div><div class="login-modal-field"><label for="loginModalPassword">Password</label><input type="password" id="loginModalPassword" placeholder="Your password" autocomplete="current-password" required></div><div class="login-modal-error" id="loginModalError"></div><button type="submit" class="login-modal-btn" id="loginModalBtn">Sign In</button></form></div></div>';
    document.body.appendChild(div);
    _overlay = div;
    div.querySelector("#loginModalForm").addEventListener("submit", function(e) {
      e.preventDefault();
      var email = div.querySelector("#loginModalEmail").value.trim();
      var password = div.querySelector("#loginModalPassword").value;
      var btn = div.querySelector("#loginModalBtn");
      var errEl = div.querySelector("#loginModalError");
      if (!email || !password) {
        errEl.textContent = "Email and password are required";
        errEl.classList.add("visible");
        return;
      }
      btn.disabled = true;
      btn.textContent = "Signing in...";
      errEl.classList.remove("visible");
      $.ajax({
        url: "/api/auth/login",
        method: "POST",
        contentType: "application/json",
        data: JSON.stringify({ email, password }),
        success: function(res) {
          if (res.success) {
            TinyShop.hideLoginModal();
            if (TinyShop.spa) TinyShop.spa._cache = {};
            if (res.csrf) {
              TinyShop.csrfToken = res.csrf;
              $.ajaxSetup({ headers: { "X-CSRF-Token": res.csrf } });
              var meta = document.querySelector('meta[name="csrf-token"]');
              if (meta) meta.setAttribute("content", res.csrf);
            }
            var dest = _pendingUrl || location.pathname + location.search;
            if (TinyShop.spa && TinyShop.spa._ready) {
              TinyShop.spa.go(dest);
            } else {
              window.location.reload();
            }
          }
        },
        error: function(xhr) {
          var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : "Something went wrong";
          errEl.textContent = msg;
          errEl.classList.add("visible");
          btn.disabled = false;
          btn.textContent = "Sign In";
        }
      });
    });
    return div;
  }
  TinyShop.showLoginModal = function(targetUrl) {
    if (_showing) return;
    _showing = true;
    _pendingUrl = targetUrl || null;
    var overlay = getOverlay();
    overlay.querySelector("#loginModalEmail").value = "";
    overlay.querySelector("#loginModalPassword").value = "";
    overlay.querySelector("#loginModalError").classList.remove("visible");
    overlay.querySelector("#loginModalBtn").disabled = false;
    overlay.querySelector("#loginModalBtn").textContent = "Sign In";
    $.getJSON("/api/auth/check").done(function(res) {
      if (res.csrf) {
        TinyShop.csrfToken = res.csrf;
        $.ajaxSetup({ headers: { "X-CSRF-Token": res.csrf } });
        var meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) meta.setAttribute("content", res.csrf);
      }
    });
    overlay.classList.add("active");
    document.body.classList.add("login-modal-open");
    setTimeout(function() {
      overlay.querySelector("#loginModalEmail").focus();
    }, 100);
  };
  TinyShop.hideLoginModal = function() {
    if (!_showing || !_overlay) return;
    _showing = false;
    _pendingUrl = null;
    _overlay.classList.remove("active");
    document.body.classList.remove("login-modal-open");
  };
  TinyShop._isLoginModalShowing = function() {
    return _showing;
  };
  $(document).ajaxError(function(event, xhr, settings) {
    if (xhr.status === 401 && settings.url && settings.url.indexOf("/api/") !== -1) {
      if (settings.url.indexOf("/api/auth/login") !== -1) return;
      if (_showing) return;
      TinyShop.showLoginModal();
    }
  });
})();
$(function() {
  var $bloomSearch = $("#bloomDesktopSearch");
  if ($bloomSearch.length && !$("#catalogue").length) {
    $bloomSearch.on("keydown", function(e) {
      if (e.key === "Enter") {
        e.preventDefault();
        var q = $.trim($(this).val());
        if (q) {
          TinyShop.navigate("/?search=" + encodeURIComponent(q));
        } else {
          TinyShop.navigate("/");
        }
      }
    });
  }
});
$(function() {
  $(document).on("click", 'a[href^="#"]', function(e) {
    var target = $(this.getAttribute("href"));
    if (target.length) {
      e.preventDefault();
      $("html, body").animate({ scrollTop: target.offset().top - 60 }, 400);
    }
  });
  $(document).on("click", ".product-variation-option", function() {
    var $group = $(this).closest(".product-variation-options");
    $group.find(".product-variation-option").removeClass("selected");
    $(this).addClass("selected");
  });
  $(document).on("click", "[data-share-trigger]", function(e) {
    e.preventDefault();
    var url = window.location.href;
    var title = document.title;
    if (navigator.share) {
      navigator.share({ title, url }).then(function() {
        TinyShop.toast("Thanks for sharing!");
      }).catch(function() {
      });
      return;
    }
    var $b = $("#shareSheetBackdrop");
    $b.find('[data-share-action="whatsapp"]').attr(
      "href",
      "https://wa.me/?text=" + encodeURIComponent(title + " " + url)
    );
    $b.find('[data-share-action="facebook"]').attr(
      "href",
      "https://www.facebook.com/sharer/sharer.php?u=" + encodeURIComponent(url)
    );
    $b.find('[data-share-action="twitter"]').attr(
      "href",
      "https://twitter.com/intent/tweet?text=" + encodeURIComponent(title) + "&url=" + encodeURIComponent(url)
    );
    $b.find('[data-share-action="email"]').attr(
      "href",
      "mailto:?subject=" + encodeURIComponent(title) + "&body=" + encodeURIComponent(url)
    );
    $b.addClass("active");
    document.body.style.overflow = "hidden";
  });
  $(document).on("click", "#shareSheetBackdrop", function(e) {
    if (e.target === this) {
      $(this).removeClass("active");
      document.body.style.overflow = "";
    }
  });
  $(document).on("click", "#shareSheetBackdrop .share-sheet-close", function() {
    $("#shareSheetBackdrop").removeClass("active");
    document.body.style.overflow = "";
  });
  $(document).on("click", '#shareSheetBackdrop [data-share-action="copy"]', function() {
    var $label = $(this).find(".share-sheet-label");
    var url = window.location.href;
    function onCopied() {
      $label.text("Copied!");
      setTimeout(function() {
        $label.text("Copy Link");
        $("#shareSheetBackdrop").removeClass("active");
        document.body.style.overflow = "";
      }, 800);
      TinyShop.toast("Link copied!");
    }
    if (navigator.clipboard) {
      navigator.clipboard.writeText(url).then(onCopied, function() {
        TinyShop.toast("Could not copy link", "error");
      });
    } else {
      var ta = document.createElement("textarea");
      ta.value = url;
      ta.style.position = "fixed";
      ta.style.opacity = "0";
      document.body.appendChild(ta);
      ta.select();
      document.execCommand("copy");
      document.body.removeChild(ta);
      onCopied();
    }
  });
  $(document).on("click", "#shareSheetBackdrop a[data-share-action]", function() {
    setTimeout(function() {
      $("#shareSheetBackdrop").removeClass("active");
      document.body.style.overflow = "";
    }, 300);
  });
});
TinyShop.imageViewer = {
  _el: null,
  _images: [],
  _current: 0,
  _keyBound: false,
  open: function(images, startIndex) {
    var self = this;
    self._images = images;
    self._current = startIndex || 0;
    if (!self._el || !self._el.isConnected) {
      self._el = null;
      self._build();
    }
    self._show();
    setTimeout(function() {
      self._el.classList.add("active");
    }, 10);
  },
  close: function() {
    var self = this;
    if (!self._el) return;
    self._el.classList.remove("active");
  },
  _build: function() {
    var self = this;
    var div = document.createElement("div");
    div.className = "image-viewer";
    div.innerHTML = '<button class="image-viewer-close" aria-label="Close"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button><button class="image-viewer-prev" aria-label="Previous"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg></button><img class="image-viewer-img" src="" alt=""><button class="image-viewer-next" aria-label="Next"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></button><div class="image-viewer-counter"></div>';
    document.body.appendChild(div);
    self._el = div;
    div.querySelector(".image-viewer-close").addEventListener("click", function() {
      self.close();
    });
    div.addEventListener("click", function(e) {
      if (e.target === div) self.close();
    });
    div.querySelector(".image-viewer-prev").addEventListener("click", function(e) {
      e.stopPropagation();
      self._go(self._current - 1);
    });
    div.querySelector(".image-viewer-next").addEventListener("click", function(e) {
      e.stopPropagation();
      self._go(self._current + 1);
    });
    if (!self._keyBound) {
      self._keyBound = true;
      document.addEventListener("keydown", function(e) {
        if (!self._el || !self._el.classList.contains("active")) return;
        if (e.key === "Escape") self.close();
        if (e.key === "ArrowLeft") self._go(self._current - 1);
        if (e.key === "ArrowRight") self._go(self._current + 1);
      });
    }
    var startX = 0, startY = 0, tracking = false;
    var img = div.querySelector(".image-viewer-img");
    img.addEventListener("touchstart", function(e) {
      if (e.touches.length === 1) {
        startX = e.touches[0].clientX;
        startY = e.touches[0].clientY;
        tracking = true;
      }
    }, { passive: true });
    img.addEventListener("touchend", function(e) {
      if (!tracking) return;
      tracking = false;
      var dx = e.changedTouches[0].clientX - startX;
      var dy = e.changedTouches[0].clientY - startY;
      if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) {
        if (dx < 0) self._go(self._current + 1);
        else self._go(self._current - 1);
      }
    }, { passive: true });
    div.addEventListener("transitionend", function() {
      if (!div.classList.contains("active")) {
        div.style.display = "";
      }
    });
  },
  _show: function() {
    var self = this;
    var img = self._el.querySelector(".image-viewer-img");
    var counter = self._el.querySelector(".image-viewer-counter");
    var prev = self._el.querySelector(".image-viewer-prev");
    var next = self._el.querySelector(".image-viewer-next");
    self._el.style.display = "flex";
    img.src = self._images[self._current];
    counter.textContent = self._current + 1 + " / " + self._images.length;
    prev.style.display = self._images.length > 1 ? "" : "none";
    next.style.display = self._images.length > 1 ? "" : "none";
    counter.style.display = self._images.length > 1 ? "" : "none";
  },
  _go: function(idx) {
    var self = this;
    if (self._images.length <= 1) return;
    self._current = (idx % self._images.length + self._images.length) % self._images.length;
    self._show();
  }
};
$(document).on("click", ".product-gallery-slide img", function() {
  var gallery = document.getElementById("productGallery");
  if (!gallery) return;
  var slides = gallery.querySelectorAll(".product-gallery-slide img");
  var images = [];
  slides.forEach(function(s) {
    images.push(s.src);
  });
  var idx = Array.prototype.indexOf.call(slides, this);
  TinyShop.imageViewer.open(images, idx >= 0 ? idx : 0);
});
TinyShop.cardHelpers = {
  badge: function(p) {
    if (p.is_sold == 1) return { type: "sold", text: "Sold out", pct: 0 };
    if (p.compare_price && parseFloat(p.compare_price) > parseFloat(p.price)) {
      var pct = Math.round((1 - parseFloat(p.price) / parseFloat(p.compare_price)) * 100);
      return { type: "sale", text: "-" + pct + "%", pct };
    }
    return null;
  },
  badgeHtml: function(p) {
    var b = this.badge(p);
    if (!b) return "";
    return '<span class="product-badge product-badge-' + b.type + '">' + b.text + "</span>";
  },
  escapeName: function(name) {
    return $("<span>").text(name).html();
  },
  imgSrc: function(p) {
    return p.image_url || "/public/img/placeholder.svg";
  },
  priceHtml: function(p, currencySymbol) {
    var compare = "";
    if (p.compare_price && parseFloat(p.compare_price) > parseFloat(p.price) && p.is_sold != 1) {
      compare = '<span class="price-compare">' + currencySymbol + TinyShop.formatPrice(p.compare_price) + "</span>";
    }
    var cls = compare ? ' class="price-sale"' : "";
    var main = "<span" + cls + ">" + currencySymbol + TinyShop.formatPrice(p.price) + "</span>";
    return { compare, main, full: compare + main };
  }
};
TinyShop.renderProductCard = function(p, currencySymbol) {
  if (window.TinyShopTheme && typeof window.TinyShopTheme.renderProductCard === "function") {
    return window.TinyShopTheme.renderProductCard(p, currencySymbol);
  }
  return TinyShop._defaultRenderProductCard(p, currencySymbol);
};
TinyShop._defaultRenderProductCard = function(p, currencySymbol) {
  var h = TinyShop.cardHelpers;
  var slug = p.slug || p.id;
  var soldClass = p.is_sold == 1 ? " product-card-sold" : "";
  var name = h.escapeName(p.name);
  var price = h.priceHtml(p, currencySymbol);
  return '<a href="/' + slug + '" class="product-card' + soldClass + '" data-category="' + (p.category_id || "") + '"><div class="product-card-img">' + h.badgeHtml(p) + '<img src="' + h.imgSrc(p) + '" alt="' + name + `" loading="lazy" decoding="async" onload="this.classList.add('loaded')"></div><div class="product-card-body"><h3 class="product-title">` + name + '</h3><div class="product-price">' + price.full + "</div></div></a>";
};
TinyShop.renderSkeletons = function(count) {
  if (window.TinyShopTheme && typeof window.TinyShopTheme.renderSkeletons === "function") {
    return window.TinyShopTheme.renderSkeletons(count);
  }
  return TinyShop._defaultRenderSkeletons(count);
};
TinyShop._defaultRenderSkeletons = function(count) {
  var html = "";
  for (var i = 0; i < count; i++) {
    html += '<div class="product-card product-card-skeleton"><div class="product-card-img"></div><div class="product-card-body"><div class="skeleton-line skeleton-line-short"></div><div class="skeleton-line skeleton-line-price"></div></div></div>';
  }
  return html;
};
TinyShop.initShop = function() {
  var $catalogue = $("#catalogue");
  if (!$catalogue.length) return;
  var $shopPage = $catalogue.closest(".shop-page");
  var subdomain = $shopPage.data("subdomain");
  if (!subdomain) return;
  var $productCount = $("#productCount");
  var $searchEmpty = $("#searchEmpty");
  var $loadMoreWrap = $("#loadMoreWrap");
  var $loadMoreBtn = $("#loadMoreBtn");
  var $loadMoreCount = $("#loadMoreCount");
  var limit = parseInt($shopPage.data("limit"), 10) || 24;
  var currencySymbol = String($shopPage.data("currency") || "");
  var state = {
    category: "all",
    categorySlug: "",
    search: "",
    sort: "default",
    offset: $catalogue.find(".product-card").length,
    total: parseInt($shopPage.data("total"), 10) || 0,
    loading: false,
    ajaxMode: false
  };
  var apiBase = "/api/shop/" + encodeURIComponent(subdomain) + "/products";
  var _activeXhr = null;
  function buildQuery(overrides) {
    var o = overrides || {};
    var params = {
      limit: o.limit || limit,
      offset: typeof o.offset !== "undefined" ? o.offset : 0,
      sort: o.sort || state.sort,
      format: "html"
    };
    if (state.search) params.search = state.search;
    if (state.category !== "all") params.category = state.category;
    return $.param(params);
  }
  function updateCount(shown, total) {
    state.total = total;
    if (!$productCount.length) return;
    if (total === 0) {
      $productCount.text("0 products");
    } else if (shown < total) {
      $productCount.text("Showing " + shown + " of " + total + " products");
    } else {
      $productCount.text(total + (total === 1 ? " product" : " products"));
    }
  }
  function updateLoadMore(shown, total) {
    if (!$loadMoreWrap.length && shown < total) {
      var html = '<div class="load-more-wrap" id="loadMoreWrap"><button type="button" class="load-more-btn" id="loadMoreBtn">Load more <span class="load-more-count" id="loadMoreCount"></span></button></div>';
      $catalogue.after(html);
      $loadMoreWrap = $("#loadMoreWrap");
      $loadMoreBtn = $("#loadMoreBtn");
      $loadMoreCount = $("#loadMoreCount");
    }
    if ($loadMoreWrap.length) {
      var remaining = total - shown;
      if (remaining > 0) {
        $loadMoreCount.text("(" + remaining + " more)");
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
  function fetchProducts(opts) {
    if (_activeXhr) {
      _activeXhr.abort();
      _activeXhr = null;
    }
    state.loading = true;
    state.ajaxMode = true;
    var append = opts && opts.append;
    var query = buildQuery(opts);
    var $shopSearch2 = $("#shopSearch");
    if ($shopSearch2.length) $shopSearch2.addClass("search-loading");
    if (!append) {
      $catalogue.html(TinyShop.renderSkeletons(limit > 8 ? 8 : limit));
      $catalogue.show();
      $searchEmpty.hide();
    } else {
      $loadMoreBtn.addClass("loading").text("Loading...");
    }
    _activeXhr = $.getJSON(apiBase + "?" + query).done(function(data) {
      var total = data.total || 0;
      var html = data.html || "";
      if (append) {
        $catalogue.append(html);
        var newCount = $(html).filter(".product-card").length || $(html).find(".product-card").length || 0;
        if (newCount === 0) {
          var tmp = $("<div>").html(html);
          newCount = tmp.find(".product-card").addBack(".product-card").length;
        }
        state.offset += newCount;
      } else {
        if (!html || total === 0) {
          $catalogue.empty();
          showEmpty(true);
          state.offset = 0;
        } else {
          $catalogue.html(html);
          showEmpty(false);
          state.offset = $catalogue.find(".product-card").length;
        }
      }
      if (window.TinyShopTheme && typeof window.TinyShopTheme.reinit === "function") {
        window.TinyShopTheme.reinit();
      }
      updateCount(state.offset, total);
      updateLoadMore(state.offset, total);
    }).fail(function() {
      if (!append) {
        $catalogue.empty();
        showEmpty(true);
      }
    }).always(function() {
      state.loading = false;
      _activeXhr = null;
      var $ss = $("#shopSearch");
      if ($ss.length) $ss.removeClass("search-loading");
      $loadMoreBtn.removeClass("loading").html('Load more <span class="load-more-count" id="loadMoreCount"></span>');
      $loadMoreCount = $("#loadMoreCount");
      updateLoadMore(state.offset, state.total);
    });
  }
  function scrollIntoCenter(el) {
    var container = el.parentElement;
    if (container) {
      var scrollLeft = el.offsetLeft - container.offsetWidth / 2 + el.offsetWidth / 2;
      container.scrollTo({ left: scrollLeft, behavior: "smooth" });
    }
  }
  function syncCategoryUI(category) {
    $("#categoryTabs .category-tab").removeClass("active");
    $("#categoryTabs .category-tab").each(function() {
      if (String($(this).data("category")) === category) $(this).addClass("active");
    });
    if (category === "all") $("#categoryTabs .category-tab").first().addClass("active");
    $("#categoryCards .category-card").removeClass("active");
    $("#categoryCards .category-card").each(function() {
      if (String($(this).data("category")) === category) $(this).addClass("active");
    });
    if (category === "all") $("#categoryCards .category-card").first().addClass("active");
  }
  function showSubcategoryRow(parentSlug) {
    $(".subcategory-tabs").hide();
    if (parentSlug) {
      var $row = $('.subcategory-tabs[data-parent-slug="' + parentSlug + '"]');
      if ($row.length) {
        $row.show();
        $row.find(".category-tab-sub").removeClass("active").first().addClass("active");
      }
    }
  }
  function activateSubcategoryBySlug(childSlug) {
    var $subPill = $('.subcategory-tabs .category-tab-sub[data-slug="' + childSlug + '"]');
    if ($subPill.length) {
      var $row = $subPill.closest(".subcategory-tabs");
      var parentSlug = $row.data("parent-slug");
      $(".subcategory-tabs").hide();
      $row.show();
      $row.find(".category-tab-sub").removeClass("active");
      $subPill.addClass("active");
      var $parentTab = $('#categoryTabs .category-tab[data-slug="' + parentSlug + '"]');
      if ($parentTab.length) {
        $("#categoryTabs .category-tab").removeClass("active");
        $parentTab.addClass("active");
        $("#categoryCards .category-card").removeClass("active");
        $('#categoryCards .category-card[data-slug="' + parentSlug + '"]').addClass("active");
      }
      return true;
    }
    return false;
  }
  function filterByCategory(category, slug) {
    state.category = category;
    state.categorySlug = category === "all" ? "" : slug || "";
    state.offset = 0;
    var $si = $("#searchInput");
    if ($si.length && $si.val()) {
      $si.val("");
      $("#searchClear").removeClass("visible");
      state.search = "";
    }
    syncCategoryUI(category);
    if (category === "all") {
      showSubcategoryRow(null);
    } else {
      showSubcategoryRow(slug);
    }
    fetchProducts({ offset: 0 });
  }
  $("#categoryTabs").on("click", ".category-tab", function() {
    scrollIntoCenter(this);
    filterByCategory(String($(this).data("category")), $(this).data("slug") || "");
  });
  $("#categoryCards").on("click", ".category-card", function() {
    scrollIntoCenter(this);
    filterByCategory(String($(this).data("category")), $(this).data("slug") || "");
  });
  $(document).on("click", ".category-tab-sub", function() {
    var $row = $(this).closest(".subcategory-tabs");
    $row.find(".category-tab-sub").removeClass("active");
    $(this).addClass("active");
    scrollIntoCenter(this);
    var category = String($(this).data("category"));
    var slug = String($(this).data("slug") || "");
    state.category = category;
    state.categorySlug = slug;
    state.offset = 0;
    var $si = $("#searchInput");
    if ($si.length && $si.val()) {
      $si.val("");
      $("#searchClear").removeClass("visible");
      state.search = "";
    }
    fetchProducts({ offset: 0 });
    updateUrl();
  });
  var $searchToggle = $("#searchToggle");
  var $shopSearch = $("#shopSearch");
  if ($searchToggle.length && $searchToggle.is(":visible")) {
    $shopSearch.addClass("search-collapsed");
    $searchToggle.on("click", function() {
      if ($shopSearch.hasClass("search-collapsed")) {
        $shopSearch.removeClass("search-collapsed").addClass("search-expanded");
        $searchToggle.hide();
        setTimeout(function() {
          $("#searchInput").focus();
        }, 150);
      }
    });
  }
  var searchTimer;
  var $searchInput = $("#searchInput");
  var $searchClear = $("#searchClear");
  if ($searchInput.length) {
    $searchInput.on("input", function() {
      var query = $.trim($(this).val());
      $searchClear.toggleClass("visible", query.length > 0);
      var $ds2 = $("#bloomDesktopSearch");
      if ($ds2.length && $ds2.val() !== $(this).val()) $ds2.val($(this).val());
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function() {
        state.search = query;
        state.offset = 0;
        fetchProducts({ offset: 0 });
      }, 300);
    });
    $searchClear.on("click", function() {
      $searchInput.val("");
      $searchClear.removeClass("visible");
      $("#bloomDesktopSearch").val("");
      state.search = "";
      state.offset = 0;
      fetchProducts({ offset: 0 });
      if ($searchToggle.length && $shopSearch.hasClass("search-expanded")) {
        $shopSearch.removeClass("search-expanded").addClass("search-collapsed");
        $searchToggle.show();
      } else {
        $searchInput.focus();
      }
    });
  }
  var $desktopSearch = $("#bloomDesktopSearch");
  if ($desktopSearch.length) {
    $desktopSearch.on("input", function() {
      var query = $.trim($(this).val());
      if ($searchInput.length) {
        $searchInput.val(query).trigger("input");
      } else {
        $searchClear.toggleClass("visible", query.length > 0);
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() {
          state.search = query;
          state.offset = 0;
          fetchProducts({ offset: 0 });
        }, 300);
      }
    });
  }
  var $sort = $("#productSort");
  if ($sort.length) {
    $sort.on("change", function() {
      state.sort = $(this).val();
      state.offset = 0;
      fetchProducts({ offset: 0 });
    });
  }
  $(document).off("click.loadmore").on("click.loadmore", "#loadMoreBtn", function() {
    if (state.loading) return;
    fetchProducts({ offset: state.offset, append: true });
  });
  updateCount(state.offset, state.total);
  updateLoadMore(state.offset, state.total);
  function updateUrl() {
    var params = new URLSearchParams();
    if (state.search) params.set("search", state.search);
    if (state.sort !== "default") params.set("sort", state.sort);
    var qs = params.toString();
    var basePath = state.categorySlug ? "/collections/" + encodeURIComponent(state.categorySlug) : "/";
    history.replaceState(null, "", basePath + (qs ? "?" + qs : ""));
  }
  var urlParams = new URLSearchParams(window.location.search);
  var urlSearch = urlParams.get("search");
  var urlSort = urlParams.get("sort");
  var needsFetch = false;
  var serverCategory = $shopPage.data("active-category");
  var serverSlug = $shopPage.data("active-slug");
  var serverParent = $shopPage.data("active-parent");
  if (serverCategory) {
    if (serverParent && parseInt(serverParent, 10) > 0) {
      activateSubcategoryBySlug(String(serverSlug));
      state.category = String(serverCategory);
      state.categorySlug = String(serverSlug || "");
    } else {
      var $matchTab = $('#categoryTabs .category-tab[data-slug="' + serverSlug + '"]');
      if ($matchTab.length) {
        state.category = String($matchTab.data("category"));
      } else {
        state.category = String(serverCategory);
      }
      state.categorySlug = String(serverSlug || "");
      syncCategoryUI(state.category);
      showSubcategoryRow(String(serverSlug));
    }
  }
  var urlCategory = urlParams.get("category");
  if (urlCategory && !serverCategory) {
    state.category = urlCategory;
    syncCategoryUI(urlCategory);
    var $activeTab = $("#categoryTabs .category-tab.active");
    if ($activeTab.length && $activeTab.data("slug")) {
      state.categorySlug = String($activeTab.data("slug"));
      history.replaceState(null, "", "/collections/" + encodeURIComponent(state.categorySlug));
    }
    needsFetch = true;
  }
  if (urlSearch && $searchInput.length) {
    $searchInput.val(urlSearch);
    $searchClear.toggleClass("visible", urlSearch.length > 0);
    var $ds = $("#bloomDesktopSearch");
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
  var _origFilterByCategory = filterByCategory;
  filterByCategory = function(category, slug) {
    _origFilterByCategory(category, slug);
    updateUrl();
  };
  if ($searchInput.length) {
    $searchInput.on("input.urlstate", function() {
      clearTimeout(window._urlUpdateTimer);
      window._urlUpdateTimer = setTimeout(updateUrl, 350);
    });
    $searchClear.on("click.urlstate", updateUrl);
  }
  if ($sort.length) {
    $sort.on("change.urlstate", updateUrl);
  }
  var scrollBtn = document.querySelector(".scroll-top-btn");
  if (!scrollBtn) {
    scrollBtn = document.createElement("button");
    scrollBtn.className = "scroll-top-btn";
    scrollBtn.setAttribute("aria-label", "Scroll to top");
    scrollBtn.innerHTML = '<i class="fa-solid fa-arrow-up"></i>';
    document.body.appendChild(scrollBtn);
  }
  var scrollThrottle;
  function checkScroll() {
    var threshold = window.innerHeight * 2;
    scrollBtn.classList.toggle("visible", window.scrollY > threshold);
  }
  if (TinyShop._shopScrollHandler) {
    window.removeEventListener("scroll", TinyShop._shopScrollHandler);
  }
  TinyShop._shopScrollHandler = function() {
    if (!scrollThrottle) {
      scrollThrottle = setTimeout(function() {
        scrollThrottle = null;
        checkScroll();
      }, 100);
    }
  };
  window.addEventListener("scroll", TinyShop._shopScrollHandler, { passive: true });
  scrollBtn.addEventListener("click", function() {
    window.scrollTo({ top: 0, behavior: "smooth" });
  });
  checkScroll();
};
$(document).on("page:init", function() {
  TinyShop.initShop();
});
TinyShop.spa = {
  _ready: false,
  _loading: false,
  _xhr: null,
  _loadedScripts: {},
  /* --- Inflight fetch dedup map (url → Promise<data|null>) --- */
  _fetchMap: {},
  /* --- Client-side page cache (5min TTL, max 20 entries) --- */
  _cache: {},
  _cacheTimeout: 3e5,
  _cacheKey: function(url) {
    return url.split("#")[0];
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
    this._cache[key] = { data, time: Date.now() };
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
    if (!href || href.charAt(0) === "#") return false;
    if (href.indexOf("://") !== -1 && href.indexOf(location.origin) !== 0) return false;
    if (/^(mailto:|tel:|javascript:|blob:)/.test(href)) return false;
    if (/\.(pdf|zip|csv|xlsx?)$/i.test(href)) return false;
    return true;
  },
  init: function() {
    var self = this;
    $("script[src]").each(function() {
      self._loadedScripts[this.src] = true;
    });
    history.replaceState({ spa: true, url: location.pathname + location.search }, "", location.pathname + location.search);
    $(document).on("mousedown touchstart", "a", function(e) {
      if (e.type === "mousedown" && e.which !== 1) return;
      if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
      if (this.target === "_blank") return;
      var href = this.getAttribute("href");
      if (!self._isInternalLink(href)) return;
      if (href === location.pathname + location.search) return;
      if (href === "/logout") return;
      if (!self._getCached(href) && !self._loading && window.fetch && !self._fetchMap[href]) {
        self._fetchMap[href] = fetch(href, {
          headers: { "X-SPA": "1" },
          credentials: "same-origin"
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
    $(document).on("click", "a", function(e) {
      if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
      if (this.target === "_blank") return;
      if (e.isDefaultPrevented()) return;
      var href = this.getAttribute("href");
      if (!self._isInternalLink(href)) return;
      e.preventDefault();
      if (href === location.pathname + location.search) return;
      var $card = $(this).closest("[data-id]");
      if ($card.length) {
        try {
          sessionStorage.setItem("spa_last_product", $card.data("id"));
        } catch (ex) {
        }
      }
      self.go(href);
    });
    window.addEventListener("popstate", function(e) {
      if (e.state && e.state.spa) {
        self.go(e.state.url, true);
      }
    });
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
      if (href === "/logout") return false;
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
        headers: { "X-SPA": "1" },
        credentials: "same-origin",
        priority: "low"
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
    $(document).on("mouseenter", "a", function() {
      var quality = TinyShop._networkQuality();
      if (quality === "slow" || quality === "save-data") return;
      var href = this.getAttribute("href");
      if (!shouldPrefetch(href)) return;
      var el = this;
      el._prefetchTimer = setTimeout(function() {
        doPrefetch(href);
      }, 65);
    }).on("mouseleave", "a", function() {
      if (this._prefetchTimer) {
        clearTimeout(this._prefetchTimer);
        this._prefetchTimer = null;
      }
    });
    if ("IntersectionObserver" in window) {
      let observeLinks2 = function() {
        prefetched = {};
        _budget = 8;
        observer.disconnect();
        var quality = TinyShop._networkQuality();
        if (quality === "slow" || quality === "save-data") return;
        if (_observeTimer) clearTimeout(_observeTimer);
        var idle = window.requestIdleCallback || function(cb) {
          setTimeout(cb, 200);
        };
        idle(function() {
          var els = document.querySelectorAll(NAV_SELECTORS);
          for (var i = 0; i < els.length; i++) {
            observer.observe(els[i]);
          }
        });
      };
      var observeLinks = observeLinks2;
      var NAV_SELECTORS = ".dash-tab, .dash-sidebar a, .pricing-card a, .pricing-nav a, .land-nav-container a, .land-cta, .auth-footer a, .mk-nav-link";
      var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
          if (!entry.isIntersecting) return;
          var a = entry.target.tagName === "A" ? entry.target : entry.target.querySelector("a");
          if (a) {
            var href = a.getAttribute("href");
            if (shouldPrefetch(href)) doPrefetch(href);
          }
          observer.unobserve(entry.target);
        });
      }, { rootMargin: "200px" });
      var _observeTimer = null;
      $(document).on("page:init", observeLinks2);
      observeLinks2();
    }
  },
  /* --- Parse response (JSON fragment or full HTML fallback) --- */
  _parseResponse: function(text) {
    try {
      var data = JSON.parse(text);
      if (data && (data.body !== void 0 || data.redirect)) {
        return data;
      }
    } catch (e) {
    }
    try {
      var parser = new DOMParser();
      var doc = parser.parseFromString(text, "text/html");
      var styles = [];
      doc.querySelectorAll('head link[rel="stylesheet"], head link[rel="preload"][as="style"]').forEach(function(l) {
        styles.push(l.getAttribute("href"));
      });
      var inlineStyleBlocks = [];
      doc.querySelectorAll("head style").forEach(function(s) {
        if (s.textContent.trim()) {
          inlineStyleBlocks.push(s.textContent);
        }
      });
      var scripts = [];
      var inlineScripts = [];
      doc.body.querySelectorAll("script").forEach(function(s) {
        if (s.src) {
          scripts.push(s.src);
        } else if (s.textContent.trim()) {
          if (s.textContent.indexOf("serviceWorker") !== -1) return;
          inlineScripts.push(s.textContent);
        }
        s.parentNode.removeChild(s);
      });
      return {
        title: doc.title || "",
        bodyClass: doc.body.className || "",
        csrf: function() {
          var m = doc.querySelector('meta[name="csrf-token"]');
          return m ? m.getAttribute("content") : "";
        }(),
        styles,
        inlineStyles: inlineStyleBlocks,
        scripts,
        inlineScripts,
        body: doc.body.innerHTML
      };
    } catch (e) {
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
        if (cached.redirect === "/login" || cached.redirect === "/register") {
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
          if (data.redirect === "/login" && url !== "/logout") {
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
      url,
      method: "GET",
      dataType: "text",
      headers: { "X-SPA": "1" },
      success: function(text, status, xhr) {
        self._xhr = null;
        var data = self._parseResponse(text);
        if (!data) {
          window.location.href = url;
          return;
        }
        if (data.redirect) {
          self._cache = {};
          if (data.redirect === "/login" && url !== "/logout") {
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
        if (status === "abort") return;
        window.location.href = url;
      }
    });
  },
  /* --- Apply page data with view transition --- */
  _applyPage: function(data, url, isPopState) {
    var self = this;
    function doSwap(onDomReady) {
      document.title = data.title || (document.querySelector('meta[name="apple-mobile-web-app-title"]') || {}).content || "Shop";
      var announcer = document.getElementById("spaAnnouncer");
      if (!announcer) {
        announcer = document.createElement("div");
        announcer.id = "spaAnnouncer";
        announcer.setAttribute("role", "status");
        announcer.setAttribute("aria-live", "polite");
        announcer.setAttribute("aria-atomic", "true");
        announcer.className = "sr-only";
        document.body.appendChild(announcer);
      }
      announcer.textContent = "";
      setTimeout(function() {
        announcer.textContent = (data.title || "Page") + " loaded";
      }, 100);
      document.body.className = data.bodyClass || "";
      if (data.csrf) {
        var oldCsrf = document.querySelector('meta[name="csrf-token"]');
        if (oldCsrf) oldCsrf.setAttribute("content", data.csrf);
        TinyShop.csrfToken = data.csrf;
        $.ajaxSetup({ headers: { "X-CSRF-Token": data.csrf } });
      }
      self._syncStylesFromList(data.styles || [], data.inlineStyles || [], function() {
        document.body.innerHTML = data.body;
        if (!isPopState) {
          history.pushState({ spa: true, url }, "", url);
          window.scrollTo(0, 0);
        }
        if (onDomReady) onDomReady();
        var scriptObjs = [];
        (data.scripts || []).forEach(function(src) {
          scriptObjs.push({ src, text: "", type: "" });
        });
        (data.inlineScripts || []).forEach(function(code) {
          scriptObjs.push({ src: "", text: code, type: "" });
        });
        self.loadScripts(scriptObjs, function() {
          self._loading = false;
          self.hideProgress();
          $(document).trigger("page:init");
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
      document.body.classList.add("spa-transitioning");
      requestAnimationFrame(function() {
        requestAnimationFrame(function() {
          doSwap(function() {
            document.body.classList.remove("spa-transitioning");
            document.body.classList.add("spa-transitioned");
            setTimeout(function() {
              document.body.classList.remove("spa-transitioned");
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
      loaded[links[i].getAttribute("href")] = true;
    }
    for (var i = 0; i < styleHrefs.length; i++) {
      if (!loaded[styleHrefs[i]]) {
        fetch(styleHrefs[i], { credentials: "same-origin", priority: "low" }).catch(function() {
        });
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
      existingHrefs[allLinks[i].getAttribute("href")] = true;
    }
    var oldSpaStyles = document.head.querySelectorAll("[data-spa-style]");
    var newLinks = [];
    for (var i = 0; i < styleHrefs.length; i++) {
      if (!existingHrefs[styleHrefs[i]]) {
        var link = document.createElement("link");
        link.rel = "stylesheet";
        link.href = styleHrefs[i];
        link.setAttribute("data-spa-style", "");
        document.head.appendChild(link);
        newLinks.push(link);
      }
    }
    function finish() {
      for (var i2 = 0; i2 < oldSpaStyles.length; i2++) {
        var el = oldSpaStyles[i2];
        if (el.tagName === "STYLE") {
          if (el.parentNode) el.parentNode.removeChild(el);
        } else if (el.tagName === "LINK") {
          var href = el.getAttribute("href");
          if (!neededSet[href] && el.parentNode) {
            el.parentNode.removeChild(el);
          }
        }
      }
      if (inlineStyles && inlineStyles.length) {
        for (var i2 = 0; i2 < inlineStyles.length; i2++) {
          var style = document.createElement("style");
          style.textContent = inlineStyles[i2];
          style.setAttribute("data-spa-style", "");
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
    }, 3e3);
  },
  loadScripts: function(scripts, callback) {
    var self = this;
    var externals = [];
    var inlines = [];
    scripts.forEach(function(s) {
      var t = (s.type || "").toLowerCase();
      if (t && t !== "text/javascript" && t !== "module") return;
      if (s.src) {
        var a = document.createElement("a");
        a.href = s.src;
        var fullSrc = a.href;
        if (!self._loadedScripts[fullSrc]) {
          externals.push(fullSrc);
          self._loadedScripts[fullSrc] = true;
        }
      } else if (s.text && s.text.trim()) {
        if (s.text.indexOf("serviceWorker") !== -1 && s.text.indexOf("register") !== -1) return;
        inlines.push(s.text);
      }
    });
    function loadNext(idx) {
      if (idx >= externals.length) {
        inlines.forEach(function(code) {
          try {
            var el2 = document.createElement("script");
            el2.textContent = code;
            document.body.appendChild(el2);
            document.body.removeChild(el2);
          } catch (err) {
            if (window.console) console.warn("[SPA] inline script error:", err);
          }
        });
        if (callback) callback();
        return;
      }
      var el = document.createElement("script");
      el.src = externals[idx];
      el.onload = function() {
        loadNext(idx + 1);
      };
      el.onerror = function() {
        loadNext(idx + 1);
      };
      document.body.appendChild(el);
    }
    loadNext(0);
  },
  _getBar: function() {
    var bar = document.getElementById("spaProgress");
    if (!bar) {
      var track = document.createElement("div");
      track.className = "spa-progress-track";
      track.innerHTML = '<div class="spa-progress-bar" id="spaProgress"></div>';
      document.body.appendChild(track);
      bar = document.getElementById("spaProgress");
    }
    return bar;
  },
  showProgress: function() {
    var bar = this._getBar();
    bar.className = "spa-progress-bar";
    bar.style.width = "0%";
    bar.offsetWidth;
    bar.classList.add("spa-active");
    bar.style.width = "70%";
  },
  hideProgress: function() {
    var bar = this._getBar();
    bar.style.width = "100%";
    setTimeout(function() {
      bar.classList.add("spa-done");
      bar.classList.remove("spa-active");
      setTimeout(function() {
        bar.style.width = "0%";
        bar.classList.remove("spa-done");
      }, 200);
    }, 150);
  }
};
$(function() {
  TinyShop.spa.init();
  $(document).trigger("page:init");
});
