/**
 * Navigate to a URL using SPA when available, else full page load.
 *
 * Closes any open modal before navigating to keep body scroll
 * in a clean state.
 *
 * @since 1.0.0
 *
 * @param {string} url The destination URL.
 */
TinyShop.navigate = function(url) {
    if (typeof TinyShop.closeModal === 'function') TinyShop.closeModal();
    document.body.style.overflow = '';
    if (TinyShop.spa && TinyShop.spa._ready) {
        TinyShop.spa.go(url);
    } else {
        window.location.href = url;
    }
};
