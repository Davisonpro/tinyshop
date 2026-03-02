/**
 * Theme card helpers and product card rendering.
 *
 * Provides shared utilities (badge, price HTML, image src)
 * and a default product card renderer that themes can
 * override via window.TinyShopTheme.renderProductCard().
 *
 * @since 1.0.0
 */
TinyShop.cardHelpers = {
    /**
     * Compute the badge for a product (sold-out or sale).
     *
     * @param {Object} p Product data.
     * @return {Object|null} Badge object with type, text, and pct, or null.
     */
    badge: function(p) {
        if (p.is_sold == 1) return { type: 'sold', text: 'Sold out', pct: 0 };
        if (p.compare_price && parseFloat(p.compare_price) > parseFloat(p.price)) {
            var pct = Math.round((1 - parseFloat(p.price) / parseFloat(p.compare_price)) * 100);
            return { type: 'sale', text: '-' + pct + '%', pct: pct };
        }
        return null;
    },

    /**
     * Return badge HTML or empty string.
     *
     * @param {Object} p Product data.
     * @return {string} Badge markup.
     */
    badgeHtml: function(p) {
        var b = this.badge(p);
        if (!b) return '';
        return '<span class="product-badge product-badge-' + b.type + '">' + b.text + '</span>';
    },

    /** HTML-escape a product name. */
    escapeName: function(name) {
        return $('<span>').text(name).html();
    },

    /** Return the product image URL or placeholder. */
    imgSrc: function(p) {
        return p.image_url || '/public/img/placeholder.svg';
    },

    /**
     * Build the price HTML for a product card.
     *
     * @param {Object} p              Product data.
     * @param {string} currencySymbol Currency prefix string.
     * @return {Object} Object with compare, main, and full HTML strings.
     */
    priceHtml: function(p, currencySymbol) {
        var compare = '';
        if (p.compare_price && parseFloat(p.compare_price) > parseFloat(p.price) && p.is_sold != 1) {
            compare = '<span class="price-compare">' + currencySymbol + TinyShop.formatPrice(p.compare_price) + '</span>';
        }
        var cls = compare ? ' class="price-sale"' : '';
        var main = '<span' + cls + '>' + currencySymbol + TinyShop.formatPrice(p.price) + '</span>';
        return { compare: compare, main: main, full: compare + main };
    }
};

/**
 * Render a product card. Delegates to the active theme's
 * renderer if one is registered, otherwise uses the default.
 *
 * @since 1.0.0
 *
 * @param {Object} p              Product data.
 * @param {string} currencySymbol Currency prefix string.
 * @return {string} Card HTML.
 */
TinyShop.renderProductCard = function(p, currencySymbol) {
    if (window.TinyShopTheme && typeof window.TinyShopTheme.renderProductCard === 'function') {
        return window.TinyShopTheme.renderProductCard(p, currencySymbol);
    }
    return TinyShop._defaultRenderProductCard(p, currencySymbol);
};

/** Default product card renderer. */
TinyShop._defaultRenderProductCard = function(p, currencySymbol) {
    var h = TinyShop.cardHelpers;
    var slug = p.slug || p.id;
    var soldClass = p.is_sold == 1 ? ' product-card-sold' : '';
    var name = h.escapeName(p.name);
    var price = h.priceHtml(p, currencySymbol);

    return '<a href="/' + slug + '" class="product-card' + soldClass + '" data-category="' + (p.category_id || '') + '">'
        + '<div class="product-card-img">' + h.badgeHtml(p)
        + '<img src="' + h.imgSrc(p) + '" alt="' + name + '" loading="lazy" decoding="async" onload="this.classList.add(\'loaded\')">'
        + '</div>'
        + '<div class="product-card-body">'
        + '<h3 class="product-title">' + name + '</h3>'
        + '<div class="product-price">' + price.full + '</div>'
        + '</div></a>';
};

/**
 * Render skeleton loading placeholders for the product grid.
 *
 * @since 1.0.0
 *
 * @param {number} count Number of skeletons to render.
 * @return {string} Skeleton HTML.
 */
TinyShop.renderSkeletons = function(count) {
    if (window.TinyShopTheme && typeof window.TinyShopTheme.renderSkeletons === 'function') {
        return window.TinyShopTheme.renderSkeletons(count);
    }
    return TinyShop._defaultRenderSkeletons(count);
};

/** Default skeleton renderer. */
TinyShop._defaultRenderSkeletons = function(count) {
    var html = '';
    for (var i = 0; i < count; i++) {
        html += '<div class="product-card product-card-skeleton">'
            + '<div class="product-card-img"></div>'
            + '<div class="product-card-body">'
            + '<div class="skeleton-line skeleton-line-short"></div>'
            + '<div class="skeleton-line skeleton-line-price"></div>'
            + '</div></div>';
    }
    return html;
};
