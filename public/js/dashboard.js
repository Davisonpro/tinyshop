function escapeHtml(str) {
  if (!str) return "";
  var div = document.createElement("div");
  div.appendChild(document.createTextNode(str));
  return div.innerHTML;
}
TinyShop.formatPrice = function(amount, currency) {
  currency = currency || "KES";
  var num = parseFloat(amount);
  if (isNaN(num)) return "0";
  var noDecimals = ["KES", "NGN", "TZS", "UGX", "RWF", "ETB", "XOF", "GHS"];
  var useDecimals = noDecimals.indexOf(currency) === -1;
  var formatted = useDecimals ? num.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",") : Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
  return currency + " " + formatted;
};
TinyShop.initPriceInput = function($input) {
  function formatDisplay(val) {
    var clean = val.replace(/[^0-9.]/g, "");
    var parts = clean.split(".");
    if (parts.length > 2) clean = parts[0] + "." + parts.slice(1).join("");
    parts = clean.split(".");
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    return parts.join(".");
  }
  function getRawValue($el) {
    return $el.val().replace(/,/g, "");
  }
  var initVal = $input.val();
  if (initVal && !isNaN(parseFloat(initVal))) {
    $input.val(formatDisplay(initVal));
  }
  $input.on("input", function() {
    var cursorPos = this.selectionStart;
    var oldVal = $(this).val();
    var oldLen = oldVal.length;
    var formatted = formatDisplay(oldVal);
    $(this).val(formatted);
    var diff = formatted.length - oldLen;
    this.setSelectionRange(cursorPos + diff, cursorPos + diff);
  });
  $input.data("rawValue", function() {
    return getRawValue($input);
  });
};
var TinyShop = window.TinyShop || {};
TinyShop.api = function(method, url, data) {
  var opts = {
    method,
    url,
    dataType: "json"
  };
  if (data && method !== "GET") {
    opts.contentType = "application/json";
    opts.data = JSON.stringify(data);
  }
  return $.ajax(opts);
};
TinyShop.uploadFile = function(file, onSuccess, onError) {
  var formData = new FormData();
  formData.append("file", file);
  $.ajax({
    url: "/api/upload",
    method: "POST",
    data: formData,
    processData: false,
    contentType: false,
    dataType: "json",
    success: function(res) {
      if (res.success && onSuccess) onSuccess(res.url);
    },
    error: function(xhr) {
      var msg = xhr.responseJSON ? xhr.responseJSON.message : "Upload failed";
      TinyShop.toast(msg, "error");
      if (onError) onError(msg);
    }
  });
};
TinyShop._previousFocus = null;
TinyShop._modalClearTimer = null;
TinyShop.openModal = function(title, contentHtml) {
  if (TinyShop._modalClearTimer) {
    clearTimeout(TinyShop._modalClearTimer);
    TinyShop._modalClearTimer = null;
  }
  TinyShop._previousFocus = document.activeElement;
  $("#modalTitle").text(title);
  $("#modalBody").html(contentHtml);
  $("#modal").addClass("active");
  document.body.style.overflow = "hidden";
  setTimeout(function() {
    var $focusable = $("#modalBody").find("input, button, select, textarea, a[href]").filter(":visible").first();
    if ($focusable.length) $focusable.focus();
    else $("#modalClose").focus();
  }, 100);
};
TinyShop.closeModal = function() {
  $("#modal").removeClass("active");
  document.body.style.overflow = "";
  if (TinyShop._modalClearTimer) clearTimeout(TinyShop._modalClearTimer);
  TinyShop._modalClearTimer = setTimeout(function() {
    $("#modalBody").html("");
    TinyShop._modalClearTimer = null;
  }, 300);
  if (TinyShop._previousFocus) {
    try {
      TinyShop._previousFocus.focus();
    } catch (e) {
    }
    TinyShop._previousFocus = null;
  }
};
TinyShop.confirm = function(title, message, confirmLabel, onConfirm, variant) {
  if (typeof title === "object" && title !== null) {
    var opts = title;
    title = opts.title || "Confirm";
    message = opts.message || "";
    confirmLabel = opts.confirmText || opts.confirmLabel || "Confirm";
    onConfirm = opts.onConfirm;
    variant = opts.variant;
  }
  var btnBg = variant === "danger" ? "#FF3B30" : "var(--color-accent)";
  var html = '<p style="margin-bottom:20px;color:var(--color-text-muted);font-size:0.9rem;">' + message + '</p><div style="display:flex;gap:10px"><button type="button" id="confirmModalCancel" style="flex:1;min-height:48px;font-size:1rem;font-weight:600;border-radius:12px;background:var(--color-bg-secondary);color:var(--color-text);border:none;cursor:pointer;font-family:inherit;">Cancel</button><button type="button" id="confirmModalOk" style="flex:1;min-height:48px;font-size:1rem;font-weight:600;border-radius:12px;background:' + btnBg + ';color:#fff;border:none;cursor:pointer;font-family:inherit;">' + (confirmLabel || "Confirm") + "</button></div>";
  TinyShop.openModal(title, html);
  $("#confirmModalCancel").on("click", function() {
    TinyShop.closeModal();
  });
  $("#confirmModalOk").on("click", function() {
    if (typeof onConfirm === "function") onConfirm();
  });
};
$(document).on("click", "#modalClose, #modal", function(e) {
  if (e.target === this) TinyShop.closeModal();
});
$(document).on("keydown", function(e) {
  if (e.key === "Escape" && $("#modal").hasClass("active")) {
    TinyShop.closeModal();
  }
});
$(document).on("keydown", "#modal", function(e) {
  if (e.key !== "Tab") return;
  var $focusable = $(this).find('input, button, select, textarea, a[href], [tabindex]:not([tabindex="-1"])').filter(":visible");
  if (!$focusable.length) return;
  var first = $focusable.first()[0];
  var last = $focusable.last()[0];
  if (e.shiftKey && document.activeElement === first) {
    e.preventDefault();
    last.focus();
  } else if (!e.shiftKey && document.activeElement === last) {
    e.preventDefault();
    first.focus();
  }
});
TinyShop.autosize = function(el) {
  if (!el.offsetParent) return;
  el.style.overflow = "hidden";
  el.style.resize = "none";
  el.style.height = "auto";
  var h = el.scrollHeight;
  var cs = window.getComputedStyle(el);
  if (cs.boxSizing === "border-box") {
    h += parseFloat(cs.borderTopWidth) + parseFloat(cs.borderBottomWidth);
  }
  el.style.height = h + "px";
};
TinyShop.initAutosize = function() {
  $("textarea.autosize").each(function() {
    TinyShop.autosize(this);
  });
};
TinyShop.initProductList = function() {
  var $grid = $("#productGrid");
  if (!$grid.length) return;
  var $filterBar = $("#categoryFilterBar");
  var $searchBar = $("#productSearchBar");
  var $searchInput = $("#productSearch");
  var $summary = $("#productListSummary");
  var $loadMore = $("#productLoadMore");
  var _allProducts = [];
  var _filteredProducts = [];
  var _activeFilter = null;
  var _searchQuery = "";
  var _currency = typeof _productListConfig !== "undefined" && _productListConfig.currency ? _productListConfig.currency : "KES";
  var PAGE_SIZE = 30;
  var _visibleCount = PAGE_SIZE;
  function loadProducts() {
    TinyShop.api("GET", "/api/products?limit=0").done(function(res) {
      _allProducts = res.products || [];
      buildCategoryTabs(_allProducts);
      if (_allProducts.length >= 3) {
        $searchBar.show();
      }
      var scrollToId = null;
      try {
        scrollToId = sessionStorage.getItem("spa_last_product");
        if (scrollToId) {
          sessionStorage.removeItem("spa_last_product");
          for (var i = 0; i < _allProducts.length; i++) {
            if (String(_allProducts[i].id) === String(scrollToId)) {
              if (i >= _visibleCount) {
                _visibleCount = i + PAGE_SIZE;
              }
              break;
            }
          }
        }
      } catch (ex) {
      }
      applyFilters();
      if (scrollToId) {
        var $el = $grid.find('[data-id="' + scrollToId + '"]');
        if ($el.length) {
          $el[0].scrollIntoView({ block: "center" });
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
      html += '<button class="category-tab" data-cat="' + id + '">' + escapeHtml(cats[id]) + "</button>";
    });
    $filterBar.html(html).show();
  }
  function applyFilters() {
    _filteredProducts = _allProducts;
    if (_activeFilter) {
      _filteredProducts = _filteredProducts.filter(function(p) {
        return String(p.category_id) === _activeFilter;
      });
    }
    if (_searchQuery) {
      var q = _searchQuery.toLowerCase();
      _filteredProducts = _filteredProducts.filter(function(p) {
        return p.name && p.name.toLowerCase().indexOf(q) !== -1 || p.description && p.description.toLowerCase().indexOf(q) !== -1;
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
    var label = total === 1 ? "1 product" : total + " products";
    if (_activeFilter || _searchQuery) {
      label += " of " + _allProducts.length;
    }
    $("#productCount").text(label);
    $summary.show();
  }
  $filterBar.on("click", ".category-tab", function() {
    $filterBar.find(".category-tab").removeClass("active");
    $(this).addClass("active");
    var catId = $(this).data("cat");
    _activeFilter = catId === "" ? null : String(catId);
    applyFilters();
  });
  var _searchTimer;
  $searchInput.on("input", function() {
    clearTimeout(_searchTimer);
    var val = $(this).val();
    _searchTimer = setTimeout(function() {
      _searchQuery = val.trim();
      applyFilters();
    }, 150);
  });
  $("#loadMoreBtn").on("click", function() {
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
        hint = "<p>Try a different search term</p>";
      } else if (_activeFilter) {
        msg = "Nothing in this category";
        hint = "";
      } else {
        msg = "Your store is ready";
        hint = '<p>Add your first product to start selling</p><a href="/dashboard/products/add" class="empty-state-btn">Add product</a>';
      }
      $grid.html(
        '<div class="empty-state"><div class="empty-icon"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#AEAEB2" stroke-width="1.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg></div><h2>' + msg + "</h2>" + hint + "</div>"
      );
      $loadMore.hide();
      return;
    }
    var visible = products.slice(0, _visibleCount);
    var hasMore = products.length > _visibleCount;
    var html = "";
    visible.forEach(function(p) {
      var imgSrc = p.image_url || "/public/img/placeholder.svg";
      var isSold = parseInt(p.is_sold) === 1;
      var isHidden = parseInt(p.is_active) === 0;
      var isFeatured = parseInt(p.is_featured) === 1;
      var hasSale = p.compare_price && parseFloat(p.compare_price) > parseFloat(p.price);
      var cardClass = "product-card-manage" + (isSold ? " product-card-sold" : "") + (isHidden ? " product-card-hidden" : "");
      var badge = "";
      if (isHidden) {
        badge = '<span class="product-card-badge product-card-badge-hidden">Hidden</span>';
      } else if (isSold) {
        badge = '<span class="product-card-badge product-card-badge-sold">Sold</span>';
      } else if (isFeatured) {
        badge = '<span class="product-card-badge product-card-badge-featured">&#9733; Featured</span>';
      } else if (hasSale) {
        badge = '<span class="product-card-badge product-card-badge-sale">Sale</span>';
      }
      var priceHtml = "";
      if (hasSale && !isSold) {
        priceHtml = '<span class="price-compare">' + TinyShop.formatPrice(p.compare_price, _currency) + '</span> <span class="price-sale">' + TinyShop.formatPrice(p.price, _currency) + "</span>";
      } else {
        priceHtml = TinyShop.formatPrice(p.price, _currency);
      }
      var catLabel = p.category_name ? '<div class="product-card-category">' + escapeHtml(p.category_name) + "</div>" : "";
      html += '<a href="/dashboard/products/' + p.id + '/edit" class="' + cardClass + '" data-id="' + p.id + '"><div class="product-card-img-wrap">' + badge + '<img src="' + escapeHtml(imgSrc) + '" alt="' + escapeHtml(p.name) + '" loading="lazy"></div><div class="product-card-body"><h3>' + escapeHtml(p.name) + '</h3><div class="product-price">' + priceHtml + "</div>" + catLabel + "</div></a>";
    });
    $grid.html(html);
    if (hasMore) {
      var remaining = products.length - _visibleCount;
      $("#loadMoreBtn").text("Load " + Math.min(remaining, PAGE_SIZE) + " more of " + remaining + " remaining");
      $loadMore.show();
    } else {
      $loadMore.hide();
    }
  }
  loadProducts();
};
TinyShop.initProductForm = function() {
  var $form = $("#productForm");
  if (!$form.length || typeof _productFormConfig === "undefined") return;
  var isEdit = _productFormConfig.isEdit;
  var productId = _productFormConfig.productId;
  var DRAFT_KEY = "product_draft_new";
  $(".price-input").each(function() {
    TinyShop.initPriceInput($(this));
  });
  $("#trackStock").on("change", function() {
    if ($(this).is(":checked")) {
      $("#stockQtyRow").show();
      $("#stockQuantity").focus();
    } else {
      $("#stockQtyRow").hide();
      $("#stockQuantity").val("");
    }
  });
  var $gallery = $("#imageGallery");
  var $addBtn = $("#addImageBtn");
  var $fileInput = $("#imageInput");
  function getImageUrls() {
    var urls = [];
    $gallery.find(".image-gallery-item").each(function() {
      urls.push($(this).data("url"));
    });
    return urls;
  }
  function addImageToGallery(url) {
    var $item = $('<div class="image-gallery-item" draggable="true" data-url="' + escapeHtml(url) + '"><img src="' + escapeHtml(url) + '" alt=""><button type="button" class="image-gallery-remove">&times;</button></div>');
    $addBtn.before($item);
    bindDrag($item[0]);
    saveDraft();
  }
  $addBtn.on("click", function() {
    $fileInput.click();
  });
  $fileInput.on("change", function() {
    var files = this.files;
    if (!files.length) return;
    for (var i = 0; i < files.length; i++) {
      (function(file) {
        TinyShop.uploadFile(file, function(url) {
          addImageToGallery(url);
          TinyShop.toast("Image uploaded");
        });
      })(files[i]);
    }
    this.value = "";
  });
  $gallery.on("click", ".image-gallery-remove", function(e) {
    e.preventDefault();
    $(this).closest(".image-gallery-item").remove();
    saveDraft();
  });
  var _dragItem = null;
  function bindDrag(el) {
    el.addEventListener("dragstart", function(e) {
      _dragItem = el;
      el.classList.add("dragging");
      e.dataTransfer.effectAllowed = "move";
    });
    el.addEventListener("dragend", function() {
      el.classList.remove("dragging");
      _dragItem = null;
      $gallery.find(".drag-over").removeClass("drag-over");
      saveDraft();
    });
    el.addEventListener("dragover", function(e) {
      e.preventDefault();
      if (_dragItem && _dragItem !== el) {
        el.classList.add("drag-over");
      }
    });
    el.addEventListener("dragleave", function() {
      el.classList.remove("drag-over");
    });
    el.addEventListener("drop", function(e) {
      e.preventDefault();
      el.classList.remove("drag-over");
      if (_dragItem && _dragItem !== el) {
        var items = Array.from($gallery[0].querySelectorAll(".image-gallery-item"));
        var fromIdx = items.indexOf(_dragItem);
        var toIdx = items.indexOf(el);
        if (fromIdx < toIdx) {
          el.parentNode.insertBefore(_dragItem, el.nextSibling);
        } else {
          el.parentNode.insertBefore(_dragItem, el);
        }
      }
    });
    var _touchStartY = 0;
    var _touchStartX = 0;
    el.addEventListener("touchstart", function(e) {
      if (e.target.closest(".image-gallery-remove")) return;
      _touchStartX = e.touches[0].clientX;
      _touchStartY = e.touches[0].clientY;
      _dragItem = el;
      setTimeout(function() {
        if (_dragItem === el) el.classList.add("dragging");
      }, 150);
    }, { passive: true });
    el.addEventListener("touchmove", function(e) {
      if (!_dragItem || _dragItem !== el) return;
      var touch = e.touches[0];
      var dx = touch.clientX - _touchStartX;
      var dy = touch.clientY - _touchStartY;
      if (Math.abs(dx) > 5 || Math.abs(dy) > 5) {
        e.preventDefault();
      }
      var target = document.elementFromPoint(touch.clientX, touch.clientY);
      if (target) target = target.closest(".image-gallery-item");
      $gallery.find(".drag-over").removeClass("drag-over");
      if (target && target !== el) {
        target.classList.add("drag-over");
      }
    }, { passive: false });
    el.addEventListener("touchend", function() {
      if (!_dragItem || _dragItem !== el) return;
      el.classList.remove("dragging");
      var $over = $gallery.find(".drag-over");
      if ($over.length) {
        var overEl = $over[0];
        $over.removeClass("drag-over");
        var items = Array.from($gallery[0].querySelectorAll(".image-gallery-item"));
        var fromIdx = items.indexOf(el);
        var toIdx = items.indexOf(overEl);
        if (fromIdx < toIdx) {
          overEl.parentNode.insertBefore(el, overEl.nextSibling);
        } else {
          overEl.parentNode.insertBefore(el, overEl);
        }
      }
      _dragItem = null;
      saveDraft();
    });
  }
  $gallery.find(".image-gallery-item").each(function() {
    this.setAttribute("draggable", "true");
    bindDrag(this);
  });
  var _categoryTree = _productFormConfig.categoryTree || [];
  function openCategoryPicker() {
    var currentVal = $("#productCategory").val();
    var html = '<div class="category-picker-search"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg><input type="text" id="categorySearchInput" placeholder="Search categories..." autocomplete="off"></div>';
    html += '<div class="category-picker-list">';
    html += '<div class="category-picker-none' + (!currentVal ? " selected" : "") + '" data-id="">No category</div>';
    _categoryTree.forEach(function(parent) {
      html += '<div class="category-picker-group" data-search-parent="' + escapeHtml(parent.name.toLowerCase()) + '">';
      html += '<div class="category-picker-item category-picker-item-parent' + (String(parent.id) === String(currentVal) ? " selected" : "") + '" data-id="' + parent.id + '" data-search-name="' + escapeHtml(parent.name.toLowerCase()) + '"><span>' + escapeHtml(parent.name) + '</span><span class="category-picker-check"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg></span></div>';
      (parent.children || []).forEach(function(child) {
        html += '<div class="category-picker-item category-picker-item-child' + (String(child.id) === String(currentVal) ? " selected" : "") + '" data-id="' + child.id + '" data-search-name="' + escapeHtml(child.name.toLowerCase()) + '"><span>' + escapeHtml(child.name) + '</span><span class="category-picker-check"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg></span></div>';
      });
      html += "</div>";
    });
    html += "</div>";
    TinyShop.openModal("Select Category", html);
    var _searchTimer;
    $("#categorySearchInput").on("input", function() {
      var q = $(this).val().trim().toLowerCase();
      clearTimeout(_searchTimer);
      _searchTimer = setTimeout(function() {
        var $list = $("#modalBody .category-picker-list");
        if (!q) {
          $list.find(".category-picker-group, .category-picker-item, .category-picker-none").show();
          return;
        }
        $list.find(".category-picker-none").hide();
        $list.find(".category-picker-group").each(function() {
          var $group = $(this);
          var parentMatch = $group.find(".category-picker-item-parent").data("search-name").indexOf(q) !== -1;
          var anyChildMatch = false;
          $group.find(".category-picker-item-child").each(function() {
            var match = $(this).data("search-name").indexOf(q) !== -1;
            $(this).toggle(match || parentMatch);
            if (match) anyChildMatch = true;
          });
          $group.find(".category-picker-item-parent").toggle(parentMatch || anyChildMatch);
          $group.toggle(parentMatch || anyChildMatch);
        });
      }, 100);
    }).focus();
    $("#modalBody").on("click", ".category-picker-item, .category-picker-none", function() {
      var id = $(this).data("id");
      var name = $(this).find("span:first").text().trim() || "";
      $("#productCategory").val(id || "");
      if (id) {
        $("#categoryPickerLabel").text(name).removeClass("picker-placeholder");
      } else {
        $("#categoryPickerLabel").text("Select a category").addClass("picker-placeholder");
      }
      TinyShop.closeModal();
      saveDraft();
    });
  }
  $("#openCategoryPicker").on("click", function() {
    openCategoryPicker();
  });
  $("#addCategoryBtn").on("click", function() {
    var html = '<form id="newCategoryForm" autocomplete="off"><div class="form-group"><label for="newCategoryName">Category Name</label><input type="text" class="form-control" id="newCategoryName" placeholder="e.g. Accessories" required autofocus autocomplete="off"></div><button type="submit" class="btn btn-primary" id="saveCategoryBtn" style="width:100%;min-height:52px;font-size:1rem;font-weight:600;border-radius:14px;border:none;cursor:pointer;font-family:inherit;">Add Category</button></form>';
    TinyShop.openModal("New Category", html);
    $("#newCategoryForm").on("submit", function(e) {
      e.preventDefault();
      var name = $("#newCategoryName").val().trim();
      if (!name) return;
      var $btn = $("#saveCategoryBtn").prop("disabled", true).text("Adding...");
      TinyShop.api("POST", "/api/categories", { name }).done(function(res) {
        var cat = res.category;
        _categoryTree.push({ id: cat.id, name: cat.name, children: [] });
        $("#productCategory").val(cat.id);
        $("#categoryPickerLabel").text(cat.name).removeClass("picker-placeholder");
        TinyShop.toast("Category added");
        TinyShop.closeModal();
        saveDraft();
      }).fail(function(xhr) {
        var msg = xhr.responseJSON ? xhr.responseJSON.message : "Failed to add category";
        TinyShop.toast(msg, "error");
        $btn.prop("disabled", false).text("Add Category");
      });
    });
  });
  var $varGroups = $("#variationGroups");
  var _varCounter = 0;
  function getVariations() {
    var groups = [];
    $varGroups.find(".variation-group").each(function() {
      var name = $(this).find(".variation-group-name").val().trim();
      var opts = [];
      $(this).find(".variation-option-row").each(function() {
        var val = $(this).find(".variation-option-value").val().trim();
        var priceStr = $(this).find(".variation-option-price").val().replace(/,/g, "").trim();
        if (val) {
          var opt = { value: val };
          if (priceStr !== "") opt.price = parseFloat(priceStr);
          opts.push(opt);
        }
      });
      if (name && opts.length > 0) {
        groups.push({ name, options: opts });
      }
    });
    return groups;
  }
  function buildOptionRow(value, price) {
    var priceVal = price !== null && price !== void 0 ? price : "";
    return '<div class="variation-option-row"><input type="text" class="variation-option-value" placeholder="Value name" value="' + escapeHtml(value || "") + '" autocomplete="off"><input type="text" class="variation-option-price price-input" placeholder="Price" inputmode="decimal" value="' + escapeHtml(String(priceVal)) + '" autocomplete="off"><button type="button" class="variation-option-remove" title="Remove">&times;</button></div>';
  }
  function addVariationGroup(name, options) {
    var gid = _varCounter++;
    var html = '<div class="variation-group" data-gid="' + gid + '"><div class="variation-group-header"><input type="text" class="variation-group-name" placeholder="Option name (e.g. Size)" value="' + escapeHtml(name || "") + '" autocomplete="off"><button type="button" class="variation-group-remove" title="Remove">&times;</button></div><div class="variation-options">';
    if (options && options.length) {
      options.forEach(function(opt) {
        if (typeof opt === "string") {
          html += buildOptionRow(opt, null);
        } else {
          html += buildOptionRow(opt.value, opt.price);
        }
      });
    }
    html += '</div><button type="button" class="variation-add-value">+ Add value</button></div>';
    $varGroups.append(html);
    if (!options || !options.length) {
      $varGroups.find('.variation-group[data-gid="' + gid + '"] .variation-options').append(buildOptionRow("", null));
    }
    $varGroups.find('.variation-group[data-gid="' + gid + '"] .price-input').each(function() {
      TinyShop.initPriceInput($(this));
    });
    $varGroups.find('.variation-group[data-gid="' + gid + '"] .variation-option-value').first().focus();
  }
  $("#addVariationGroup").on("click", function() {
    addVariationGroup("", []);
    saveDraft();
  });
  $varGroups.on("click", ".variation-group-remove", function() {
    $(this).closest(".variation-group").remove();
    saveDraft();
  });
  $varGroups.on("click", ".variation-option-remove", function() {
    $(this).closest(".variation-option-row").remove();
    saveDraft();
  });
  $varGroups.on("click", ".variation-add-value", function() {
    var $options = $(this).siblings(".variation-options");
    $options.append(buildOptionRow("", null));
    TinyShop.initPriceInput($options.find(".price-input").last());
    $options.find(".variation-option-value").last().focus();
    saveDraft();
  });
  $varGroups.on("keydown", ".variation-option-value", function(e) {
    if (e.key === "Enter") {
      e.preventDefault();
      var $row = $(this).closest(".variation-option-row");
      var $group = $row.closest(".variation-group");
      if ($row.is(":last-child") && $(this).val().trim()) {
        var $options = $group.find(".variation-options");
        $options.append(buildOptionRow("", null));
        $options.find(".variation-option-value").last().focus();
      }
      saveDraft();
    }
  });
  $varGroups.on("input", ".variation-group-name, .variation-option-value, .variation-option-price", function() {
    saveDraft();
  });
  if (_productFormConfig.variations && _productFormConfig.variations.length) {
    _productFormConfig.variations.forEach(function(g) {
      addVariationGroup(g.name, g.options);
    });
  }
  var $seoToggle = $("#seoToggle");
  var $seoFields = $("#seoFields");
  var $metaTitleInput = $("#metaTitle");
  var $metaDescInput = $("#metaDescription");
  $seoToggle.on("click", function() {
    var isOpen = $seoFields.is(":visible");
    $seoFields.slideToggle(200);
    $(this).toggleClass("open", !isOpen);
  });
  if ($metaTitleInput.val() || $metaDescInput.val()) {
    $seoFields.show();
    $seoToggle.addClass("open");
  }
  function updateSeoCounters() {
    $("#metaTitleCount").text($metaTitleInput.val().length);
    $("#metaDescCount").text($metaDescInput.val().length);
  }
  $metaTitleInput.on("input", updateSeoCounters);
  $metaDescInput.on("input", updateSeoCounters);
  updateSeoCounters();
  var _draftTimer;
  function saveDraft() {
    if (isEdit) return;
    clearTimeout(_draftTimer);
    _draftTimer = setTimeout(function() {
      var draft = {
        name: $("#productName").val(),
        price: $("#productPrice").val().replace(/,/g, ""),
        compare_price: $("#productComparePrice").val().replace(/,/g, ""),
        description: $("#productDesc").val(),
        category_id: $("#productCategory").val(),
        images: getImageUrls(),
        variations: getVariations(),
        meta_title: $metaTitleInput.val(),
        meta_description: $metaDescInput.val()
      };
      try {
        localStorage.setItem(DRAFT_KEY, JSON.stringify(draft));
      } catch (e) {
      }
    }, 500);
  }
  function restoreDraft() {
    if (isEdit) return;
    try {
      var raw = localStorage.getItem(DRAFT_KEY);
      if (!raw) return;
      var draft = JSON.parse(raw);
      if (draft.name) $("#productName").val(draft.name);
      if (draft.price) {
        $("#productPrice").val(draft.price).trigger("input");
      }
      if (draft.compare_price) {
        $("#productComparePrice").val(draft.compare_price).trigger("input");
      }
      if (draft.description) {
        $("#productDesc").val(draft.description);
        if (window._setEditorContent) window._setEditorContent(draft.description);
      }
      if (draft.category_id) {
        $("#productCategory").val(draft.category_id);
        _categoryTree.forEach(function(p) {
          if (String(p.id) === String(draft.category_id)) {
            $("#categoryPickerLabel").text(p.name).removeClass("picker-placeholder");
          }
          (p.children || []).forEach(function(c) {
            if (String(c.id) === String(draft.category_id)) {
              $("#categoryPickerLabel").text(c.name).removeClass("picker-placeholder");
            }
          });
        });
      }
      if (draft.images && draft.images.length) {
        draft.images.forEach(function(url) {
          addImageToGallery(url);
        });
      }
      if (draft.variations && draft.variations.length) {
        draft.variations.forEach(function(g) {
          addVariationGroup(g.name, g.options);
        });
      }
      if (draft.meta_title) {
        $metaTitleInput.val(draft.meta_title);
        $seoFields.show();
        $seoToggle.addClass("open");
      }
      if (draft.meta_description) {
        $metaDescInput.val(draft.meta_description);
        $seoFields.show();
        $seoToggle.addClass("open");
      }
      updateSeoCounters();
      TinyShop.toast("Draft restored");
    } catch (e) {
    }
  }
  function clearDraft() {
    try {
      localStorage.removeItem(DRAFT_KEY);
    } catch (e) {
    }
  }
  var _formDirty = false;
  function markDirty() {
    _formDirty = true;
  }
  function markClean() {
    _formDirty = false;
    window.removeEventListener("beforeunload", _beforeUnload);
  }
  function _beforeUnload(e) {
    if (_formDirty) {
      e.preventDefault();
      e.returnValue = "";
    }
  }
  $form.on("input change", "input, textarea, select", function() {
    saveDraft();
    markDirty();
  });
  var richContent = document.querySelector(".rich-editor-content");
  if (richContent) {
    richContent.addEventListener("input", function() {
      saveDraft();
      markDirty();
    });
  }
  var _origAddImage = addImageToGallery;
  addImageToGallery = function(url) {
    _origAddImage(url);
    markDirty();
  };
  $form.on("input change", function() {
    if (_formDirty) window.addEventListener("beforeunload", _beforeUnload);
  });
  restoreDraft();
  if (isEdit) {
    $("#deleteProductBtn").on("click", function() {
      TinyShop.confirm("Delete Product?", "This will permanently delete this product and all its images. This cannot be undone.", "Delete", function() {
        $("#confirmModalOk").prop("disabled", true).text("Deleting...");
        TinyShop.api("DELETE", "/api/products/" + productId).done(function() {
          markClean();
          TinyShop.toast("Product deleted");
          TinyShop.closeModal();
          TinyShop.navigate("/dashboard/products");
        }).fail(function() {
          TinyShop.toast("Failed to delete", "error");
          TinyShop.closeModal();
        });
      }, "danger");
    });
  }
  $form.on("submit", function(e) {
    e.preventDefault();
    var $btn = $("#saveProductBtn").prop("disabled", true).html('<span class="btn-spinner"></span> Saving...');
    var priceRaw = $("#productPrice").val().replace(/,/g, "");
    var compareRaw = $("#productComparePrice").val().replace(/,/g, "");
    var variations = getVariations();
    var stockQty = null;
    if ($("#trackStock").is(":checked")) {
      var qtyVal = $("#stockQuantity").val();
      stockQty = qtyVal !== "" ? parseInt(qtyVal, 10) : 0;
      if (isNaN(stockQty) || stockQty < 0) stockQty = 0;
    }
    var payload = {
      name: $("#productName").val(),
      price: parseFloat(priceRaw),
      compare_price: compareRaw !== "" ? parseFloat(compareRaw) : null,
      description: $("#productDesc").val(),
      category_id: $("#productCategory").val() || null,
      images: getImageUrls(),
      is_sold: $("#productSold").is(":checked") ? 1 : 0,
      stock_quantity: stockQty,
      is_featured: $("#productFeatured").is(":checked") ? 1 : 0,
      is_active: $("#productActive").length ? $("#productActive").is(":checked") ? 1 : 0 : 1,
      variations: variations.length > 0 ? variations : null,
      meta_title: $metaTitleInput.val().trim() || null,
      meta_description: $metaDescInput.val().trim() || null
    };
    var method = isEdit ? "PUT" : "POST";
    var url = isEdit ? "/api/products/" + productId : "/api/products";
    TinyShop.api(method, url, payload).done(function() {
      markClean();
      clearDraft();
      TinyShop.toast(isEdit ? "Product saved!" : "Product added!");
      setTimeout(function() {
        TinyShop.navigate("/dashboard/products");
      }, 600);
    }).fail(function(xhr) {
      var msg = xhr.responseJSON ? xhr.responseJSON.message : "Failed to save";
      TinyShop.toast(msg, "error");
      $btn.prop("disabled", false).text(isEdit ? "Save Changes" : "Add Product");
    });
  });
};
$(document).on("page:init", function() {
  TinyShop.initProductList();
  TinyShop.initProductForm();
  TinyShop.initAutosize();
});
$(function() {
  $(document).on("input", "textarea.autosize", function() {
    TinyShop.autosize(this);
  });
  $(document).on("click", ".seo-toggle", function() {
    var $section = $(this).closest(".form-section");
    setTimeout(function() {
      $section.find("textarea.autosize").each(function() {
        TinyShop.autosize(this);
      });
    }, 250);
  });
});
