/**
 * Client-side cart system using localStorage, keyed per shop.
 *
 * Handles variation selection, dynamic price recalculation,
 * cart drawer rendering, quantity controls, and checkout
 * navigation. Exposes a public API on TinyShop.Cart.
 *
 * @since 1.0.0
 */
TinyShop.Cart = (function() {
    var _shopId = null;
    var _items = [];
    var _bound = false;

    /** Build the localStorage key for this shop's cart. */
    function storageKey() {
        return 'tinyshop_cart_' + _shopId;
    }

    /** Load cart items from localStorage. */
    function load() {
        try {
            var raw = localStorage.getItem(storageKey());
            _items = raw ? JSON.parse(raw) : [];
            if (!Array.isArray(_items)) _items = [];
        } catch(e) {
            _items = [];
        }
    }

    /** Persist cart items to localStorage and refresh the UI. */
    function save() {
        try {
            localStorage.setItem(storageKey(), JSON.stringify(_items));
        } catch(e) {}
        updateBadge();
        renderDrawer();
    }

    /** Build a unique key for a cart line item. */
    function itemKey(productId, variation) {
        return productId + '-' + (variation || '');
    }

    /** Update all .cart-badge elements with the current count. */
    function updateBadge() {
        var count = getCount();
        var $badge = $('.cart-badge');
        if (count > 0) {
            $badge.text(count).removeClass('cart-badge-hidden');
        } else {
            $badge.addClass('cart-badge-hidden');
        }
    }

    /** Total number of items (sum of quantities). */
    function getCount() {
        var c = 0;
        _items.forEach(function(item) { c += item.quantity; });
        return c;
    }

    /** Total price of all items. */
    function getTotal() {
        var t = 0;
        _items.forEach(function(item) { t += item.price * item.quantity; });
        return t;
    }

    /** Return a shallow copy of the items array. */
    function getItems() {
        return _items.slice();
    }

    /**
     * Add a product to the cart (or increment if already present).
     *
     * @param {Object} product          Product data.
     * @param {number} [qty]            Quantity to add (default 1).
     * @param {string} [variation]      Variation string (e.g. "Size: M, Color: Red").
     */
    function addItem(product, qty, variation) {
        qty = qty || 1;
        var key = itemKey(product.id, variation);
        var existing = null;
        for (var i = 0; i < _items.length; i++) {
            if (itemKey(_items[i].productId, _items[i].variation) === key) {
                existing = i;
                break;
            }
        }
        if (existing !== null) {
            _items[existing].quantity += qty;
        } else {
            _items.push({
                productId: product.id,
                name: product.name,
                price: parseFloat(product.price),
                comparePrice: parseFloat(product.comparePrice) || 0,
                image: product.image || '',
                slug: product.slug || '',
                quantity: qty,
                variation: variation || ''
            });
        }
        save();
    }

    /** Update quantity for a specific line item (removes if qty <= 0). */
    function updateQty(productId, variation, qty) {
        var key = itemKey(productId, variation);
        for (var i = 0; i < _items.length; i++) {
            if (itemKey(_items[i].productId, _items[i].variation) === key) {
                if (qty <= 0) {
                    _items.splice(i, 1);
                } else {
                    _items[i].quantity = qty;
                }
                break;
            }
        }
        save();
    }

    /** Remove a line item entirely. */
    function removeItem(productId, variation) {
        var key = itemKey(productId, variation);
        _items = _items.filter(function(item) {
            return itemKey(item.productId, item.variation) !== key;
        });
        save();
    }

    /** Empty the entire cart. */
    function clear() {
        _items = [];
        save();
    }

    // ── Variation Selector ──

    /** Count the total variation groups on the product page. */
    function getVariationGroupCount() {
        return document.querySelectorAll('.product-variation-group').length;
    }

    /** Count how many variation groups have a selected option. */
    function getSelectedVariationCount() {
        var groups = document.querySelectorAll('.product-variation-group');
        var count = 0;
        for (var i = 0; i < groups.length; i++) {
            if (groups[i].querySelector('.product-variation-option.selected')) {
                count++;
            }
        }
        return count;
    }

    /** Whether every variation group has a selection. */
    function allVariationsSelected() {
        var total = getVariationGroupCount();
        if (total === 0) return true;
        return getSelectedVariationCount() === total;
    }

    /** Calculate the effective price based on selected variations. */
    function getEffectivePrice() {
        var basePrice = window._productBasePrice;
        if (!basePrice && basePrice !== 0) return 0;
        basePrice = parseFloat(basePrice);

        var groups = document.querySelectorAll('.product-variation-group');
        var effectivePrice = basePrice;

        for (var i = 0; i < groups.length; i++) {
            var selected = groups[i].querySelector('.product-variation-option.selected');
            if (selected && selected.dataset.price) {
                var optPrice = parseFloat(selected.dataset.price);
                effectivePrice += (optPrice - basePrice);
            }
        }

        return Math.max(0, effectivePrice);
    }

    /** Build a human-readable variation string (e.g. "Size: M, Color: Red"). */
    function buildVariationString() {
        var parts = [];
        var groups = document.querySelectorAll('.product-variation-group');
        for (var i = 0; i < groups.length; i++) {
            var selected = groups[i].querySelector('.product-variation-option.selected');
            if (selected) {
                var label = groups[i].querySelector('.product-variation-label');
                var groupName = '';
                if (label) {
                    var nodes = label.childNodes;
                    for (var n = 0; n < nodes.length; n++) {
                        if (nodes[n].nodeType === 3) {
                            groupName = nodes[n].textContent.trim();
                            if (groupName) break;
                        }
                    }
                }
                var value = selected.dataset.value || '';
                if (groupName && value) {
                    parts.push(groupName + ': ' + value);
                } else if (value) {
                    parts.push(value);
                }
            }
        }
        return parts.join(', ');
    }

    /** Update the price display on the product page. */
    function updatePriceDisplay(effectivePrice) {
        var currencySymbol = window._shopCurrencySymbol || '';
        var comparePrice = parseFloat(window._productComparePrice) || 0;

        var currentEl = document.getElementById('productPriceCurrent');
        if (currentEl) {
            currentEl.textContent = currencySymbol + formatNum(effectivePrice);
            if (comparePrice > 0 && comparePrice > effectivePrice) {
                currentEl.classList.add('price-sale');
            } else {
                currentEl.classList.remove('price-sale');
            }
        }

        var compareEl = document.getElementById('productComparePrice');
        if (compareEl) {
            if (comparePrice > 0 && comparePrice > effectivePrice) {
                compareEl.textContent = currencySymbol + formatNum(comparePrice);
                compareEl.style.display = '';
            } else {
                compareEl.style.display = 'none';
            }
        }

        var badgeEl = document.getElementById('productDiscountBadge');
        if (badgeEl) {
            if (comparePrice > 0 && comparePrice > effectivePrice) {
                var pct = Math.round((1 - effectivePrice / comparePrice) * 100);
                badgeEl.textContent = '-' + pct + '%';
                badgeEl.style.display = '';
            } else {
                badgeEl.style.display = 'none';
            }
        }
    }

    /** Toggle the "needs selection" visual hint on the CTA button. */
    function updateCtaState() {
        var btn = document.getElementById('addToCartBtn');
        if (!btn || btn.disabled) return;

        if (!window._hasVariations) {
            btn.classList.remove('cta-needs-selection');
            return;
        }

        if (allVariationsSelected()) {
            btn.classList.remove('cta-needs-selection');
        } else {
            btn.classList.add('cta-needs-selection');
        }
    }

    /** Handle a click on a variation option pill. */
    function handleVariationClick(e) {
        var opt = e.target.closest('.product-variation-option');
        if (!opt) return;

        var group = opt.closest('.product-variation-group');
        if (!group) return;

        var options = group.querySelectorAll('.product-variation-option');
        var wasSelected = opt.classList.contains('selected');

        for (var i = 0; i < options.length; i++) {
            options[i].classList.remove('selected');
        }

        if (!wasSelected) {
            opt.classList.add('selected');
        }

        // Update selected value label
        var groupIndex = group.dataset.group;
        var labelSpan = document.getElementById('varSelected' + groupIndex);
        if (labelSpan) {
            var selectedOpt = group.querySelector('.product-variation-option.selected');
            if (selectedOpt) {
                labelSpan.textContent = '— ' + selectedOpt.dataset.value;
                labelSpan.classList.remove('variation-prompt');
            } else {
                labelSpan.textContent = '— Pick one';
                labelSpan.classList.add('variation-prompt');
            }
        }

        // Clear error state for this group
        group.classList.remove('needs-selection');
        var errorEl = document.getElementById('varError' + groupIndex);
        if (errorEl) {
            errorEl.textContent = '';
            errorEl.classList.remove('visible');
        }

        var effectivePrice = getEffectivePrice();
        updatePriceDisplay(effectivePrice);
        updateCtaState();
    }

    // ── Cart Drawer Rendering ──

    /** Re-render the cart drawer contents from _items. */
    function renderDrawer() {
        var $body = $('#cartDrawerBody');
        var $footer = $('#cartDrawerFooter');
        if (!$body.length) return;

        var currencySymbol = window._shopCurrencySymbol || '';

        if (_items.length === 0) {
            $body.html(
                '<div class="cart-empty">' +
                    '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--color-text-muted)" stroke-width="1.5" stroke-linecap="round" style="opacity:0.4;margin-bottom:12px"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>' +
                    '<p style="font-weight:600;margin-bottom:4px">Your cart is empty</p>' +
                    '<p style="font-size:0.8125rem;color:var(--color-text-muted)">Add items to get started</p>' +
                '</div>'
            );
            $footer.hide();
            $('#cartDrawerCount').text('');
            return;
        }

        var html = '';
        _items.forEach(function(item) {
            var key = item.productId + '-' + (item.variation || '');
            var imgSrc = item.image || '/public/img/placeholder.svg';
            var productUrl = '/' + escapeHtml(item.slug || item.productId);
            html += '<div class="cart-item" data-key="' + escapeHtml(key) + '">' +
                '<a href="' + productUrl + '" class="cart-item-img cart-item-link"><img src="' + escapeHtml(imgSrc) + '" alt="' + escapeHtml(item.name) + '"></a>' +
                '<div class="cart-item-info">' +
                    '<a href="' + productUrl + '" class="cart-item-name cart-item-link">' + escapeHtml(item.name) + '</a>' +
                    (item.variation ? '<div class="cart-item-variation">' + escapeHtml(item.variation) + '</div>' : '') +
                    '<div class="cart-item-price">' + escapeHtml(currencySymbol) + formatNum(item.price) + '</div>' +
                '</div>' +
                '<div class="cart-item-actions">' +
                    '<div class="cart-qty-controls">' +
                        '<button type="button" class="cart-qty-btn cart-qty-minus" data-pid="' + item.productId + '" data-var="' + escapeHtml(item.variation) + '">-</button>' +
                        '<span class="cart-qty-value">' + item.quantity + '</span>' +
                        '<button type="button" class="cart-qty-btn cart-qty-plus" data-pid="' + item.productId + '" data-var="' + escapeHtml(item.variation) + '">+</button>' +
                    '</div>' +
                    '<button type="button" class="cart-item-remove" data-pid="' + item.productId + '" data-var="' + escapeHtml(item.variation) + '" aria-label="Remove">' +
                        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' +
                    '</button>' +
                '</div>' +
            '</div>';
        });
        $body.html(html);
        $('#cartDrawerCount').text('(' + getCount() + ')');

        var total = getTotal();
        var totalStr = escapeHtml(currencySymbol) + formatNum(total);
        $('#cartDrawerTotal').text(totalStr);
        $('#cartCheckoutTotal').text(totalStr);

        // Savings summary
        var totalSavings = 0;
        _items.forEach(function(item) {
            if (item.comparePrice && item.comparePrice > item.price) {
                totalSavings += (item.comparePrice - item.price) * item.quantity;
            }
        });
        var $savings = $('#cartDrawerSavings');
        if (totalSavings > 0) {
            if (!$savings.length) {
                $footer.find('.cart-drawer-total').before('<div class="cart-savings" id="cartDrawerSavings"></div>');
                $savings = $('#cartDrawerSavings');
            }
            $savings.html('You\'re saving ' + escapeHtml(currencySymbol) + formatNum(totalSavings) + '!').show();
        } else if ($savings.length) {
            $savings.hide();
        }

        $footer.show();
    }

    /** Format a number with two decimal places and thousand separators. */
    function formatNum(n) {
        return parseFloat(n).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    /** Private escapeHtml for the cart IIFE (avoids external dependency). */
    function escapeHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    // ── Init ──

    /**
     * Initialise the cart for a given shop.
     *
     * @since 1.0.0
     *
     * @param {string|number} shopId The shop identifier used as the localStorage key suffix.
     */
    function init(shopId) {
        _shopId = shopId;
        load();
        updateBadge();

        if (window._hasVariations) {
            updateCtaState();
        }

        // Cross-tab cart sync via storage events
        if (!window._cartStorageListener) {
            window._cartStorageListener = true;
            window.addEventListener('storage', function(e) {
                if (e.key && e.key.indexOf('tinyshop_cart_') === 0) {
                    load();
                    updateBadge();
                    renderDrawer();
                }
            });
        }

        // Only bind delegated event handlers once
        if (_bound) return;
        _bound = true;

        // ── Product card quick add-to-cart ──
        $(document).on('click', '.product-card-atc', function(e) {
            var $atc = $(this);

            if ($atc.hasClass('product-card-atc-options')) return;

            e.preventDefault();
            e.stopPropagation();

            var product = {
                id: $atc.data('product-id'),
                name: $atc.data('product-name'),
                price: parseFloat($atc.data('product-price')),
                comparePrice: parseFloat($atc.data('product-compare-price')) || 0,
                image: $atc.data('product-image') || '',
                slug: $atc.data('product-slug') || ''
            };

            addItem(product, 1);

            $atc.addClass('product-card-atc-added').text('Added!');
            setTimeout(function() {
                $atc.removeClass('product-card-atc-added').text('Add to Cart');
            }, 1200);

            var $badge = $('.cart-badge');
            $badge.removeClass('bounce');
            $badge[0] && $badge[0].offsetWidth;
            $badge.addClass('bounce');
        });

        // Cart drawer: quantity -/+
        $(document).on('click', '.cart-qty-minus', function() {
            var pid = $(this).data('pid');
            var v = $(this).data('var') || '';
            var key = itemKey(pid, v);
            for (var i = 0; i < _items.length; i++) {
                if (itemKey(_items[i].productId, _items[i].variation) === key) {
                    updateQty(pid, v, _items[i].quantity - 1);
                    break;
                }
            }
        });

        $(document).on('click', '.cart-qty-plus', function() {
            var pid = $(this).data('pid');
            var v = $(this).data('var') || '';
            var key = itemKey(pid, v);
            for (var i = 0; i < _items.length; i++) {
                if (itemKey(_items[i].productId, _items[i].variation) === key) {
                    updateQty(pid, v, _items[i].quantity + 1);
                    break;
                }
            }
        });

        // Cart drawer: remove item
        $(document).on('click', '.cart-item-remove', function() {
            var pid = $(this).data('pid');
            var v = $(this).data('var') || '';
            removeItem(pid, v);
        });

        // Open cart drawer
        $(document).on('click', '.cart-trigger', function(e) {
            e.preventDefault();
            renderDrawer();
            $('#cartDrawerBackdrop').addClass('active');
            document.body.style.overflow = 'hidden';
        });

        // Close cart drawer (delegated — survives SPA body swaps)
        $(document).on('click', '#cartDrawerBackdrop', function(e) {
            if (e.target === this) {
                $(this).removeClass('active');
                document.body.style.overflow = '';
            }
        });
        $(document).on('click', '.cart-drawer-close', function() {
            $('#cartDrawerBackdrop').removeClass('active');
            document.body.style.overflow = '';
        });

        // ── Variation selector (product page) ──
        $(document).on('click', '.product-variation-option', function(e) {
            handleVariationClick(e);
        });

        // ── Product page quantity selector +/- ──
        $(document).on('click', '#cartQtyMinus', function() {
            var $input = $('#cartQty');
            var val = parseInt($input.val(), 10) || 1;
            if (val > 1) $input.val(val - 1);
        });
        $(document).on('click', '#cartQtyPlus', function() {
            var $input = $('#cartQty');
            var val = parseInt($input.val(), 10) || 1;
            var max = parseInt($input.attr('max'), 10) || 99;
            if (val < max) $input.val(val + 1);
        });

        // ── Add to cart from product page ──
        $(document).on('click', '#addToCartBtn', function() {
            var $btn = $(this);
            if ($btn.prop('disabled')) return;

            if (window._hasVariations && !allVariationsSelected()) {
                var groups = document.querySelectorAll('.product-variation-group');
                var firstUnselected = null;

                for (var g = 0; g < groups.length; g++) {
                    var groupIndex = groups[g].dataset.group;
                    var errorEl = document.getElementById('varError' + groupIndex);

                    if (!groups[g].querySelector('.product-variation-option.selected')) {
                        groups[g].classList.add('needs-selection');

                        var label = groups[g].querySelector('.product-variation-label');
                        var groupName = '';
                        if (label) {
                            var nodes = label.childNodes;
                            for (var n = 0; n < nodes.length; n++) {
                                if (nodes[n].nodeType === 3) {
                                    groupName = nodes[n].textContent.trim();
                                    if (groupName) break;
                                }
                            }
                        }

                        if (errorEl) {
                            errorEl.textContent = 'Please select a ' + (groupName.toLowerCase() || 'option');
                            errorEl.classList.add('visible');
                        }

                        if (!firstUnselected) firstUnselected = groups[g];
                    } else {
                        groups[g].classList.remove('needs-selection');
                        if (errorEl) {
                            errorEl.textContent = '';
                            errorEl.classList.remove('visible');
                        }
                    }
                }

                if (firstUnselected) {
                    firstUnselected.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return;
            }

            var effectivePrice = window._hasVariations ? getEffectivePrice() : parseFloat($btn.data('product-price'));
            var variation = window._hasVariations ? buildVariationString() : '';

            var product = {
                id: $btn.data('product-id'),
                name: $btn.data('product-name'),
                price: effectivePrice,
                comparePrice: parseFloat($btn.data('product-compare-price')) || 0,
                image: $btn.data('product-image'),
                slug: $btn.data('product-slug')
            };

            var qty = parseInt($('#cartQty').val(), 10) || 1;
            addItem(product, qty, variation);

            $('#cartQty').val(1);

            var origHtml = $btn.html();
            $btn.html('<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:6px"><polyline points="20 6 9 17 4 12"/></svg> Added!').prop('disabled', true);
            setTimeout(function() {
                $btn.html(origHtml).prop('disabled', false);
            }, 1200);

            var $badge = $('.cart-badge');
            $badge.removeClass('bounce');
            $badge[0] && $badge[0].offsetWidth;
            $badge.addClass('bounce');
        });

        // Cart drawer: clicking a product link closes the drawer
        $(document).on('click', '.cart-item-link', function() {
            $('#cartDrawerBackdrop').removeClass('active');
            document.body.style.overflow = '';
        });

        // ── Checkout button ──
        $(document).on('click', '#cartCheckoutBtn', function() {
            if (_items.length === 0) return;
            $('#cartDrawerBackdrop').removeClass('active');
            document.body.style.overflow = '';
            TinyShop.navigate('/checkout');
        });
    }

    return {
        init: init,
        getItems: getItems,
        addItem: addItem,
        updateQty: updateQty,
        removeItem: removeItem,
        clear: clear,
        getCount: getCount,
        getTotal: getTotal
    };
})();
