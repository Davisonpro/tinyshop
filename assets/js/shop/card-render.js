/* ============================================================
   Theme card helpers — shared utilities for theme renderers
   ============================================================ */
TinyShop.cardHelpers = {
    badge: function(p) {
        if (p.is_sold == 1) return { type: 'sold', text: 'Sold out', pct: 0 };
        if (p.compare_price && parseFloat(p.compare_price) > parseFloat(p.price)) {
            var pct = Math.round((1 - parseFloat(p.price) / parseFloat(p.compare_price)) * 100);
            return { type: 'sale', text: '-' + pct + '%', pct: pct };
        }
        return null;
    },
    badgeHtml: function(p) {
        var b = this.badge(p);
        if (!b) return '';
        return '<span class="product-badge product-badge-' + b.type + '">' + b.text + '</span>';
    },
    escapeName: function(name) {
        return $('<span>').text(name).html();
    },
    imgSrc: function(p) {
        return p.image_url || '/public/img/placeholder.svg';
    },
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

/* ============================================================
   Render product card — delegates to theme renderer if set
   ============================================================ */
TinyShop.renderProductCard = function(p, currencySymbol) {
    if (window.TinyShopTheme && typeof window.TinyShopTheme.renderProductCard === 'function') {
        return window.TinyShopTheme.renderProductCard(p, currencySymbol);
    }
    return TinyShop._defaultRenderProductCard(p, currencySymbol);
};

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

/* ============================================================
   Render skeleton placeholders — delegates to theme if set
   ============================================================ */
TinyShop.renderSkeletons = function(count) {
    if (window.TinyShopTheme && typeof window.TinyShopTheme.renderSkeletons === 'function') {
        return window.TinyShopTheme.renderSkeletons(count);
    }
    return TinyShop._defaultRenderSkeletons(count);
};

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
