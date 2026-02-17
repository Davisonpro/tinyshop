/* ============================================================
   Navigate helper — uses SPA when available, else full load
   ============================================================ */
TinyShop.navigate = function(url) {
    // Close any open modal/confirm before navigating
    if (typeof TinyShop.closeModal === 'function') TinyShop.closeModal();
    document.body.style.overflow = '';
    if (TinyShop.spa && TinyShop.spa._ready) {
        TinyShop.spa.go(url);
    } else {
        window.location.href = url;
    }
};

/* ============================================================
   Format price — matches PHP number_format(n, 2, '.', ',')
   ============================================================ */
TinyShop.formatPrice = function(n) {
    var num = parseFloat(n) || 0;
    return num.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
};
