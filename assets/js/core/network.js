/**
 * Detect network quality using the Network Information API.
 *
 * Returns a simple tier string so callers can decide whether
 * to prefetch, lazy-load, or skip non-essential requests.
 *
 * @since 1.0.0
 *
 * @return {string} One of 'fast', 'medium', 'slow', or 'save-data'.
 */
TinyShop._networkQuality = function() {
    var conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
    if (!conn) return 'fast';
    if (conn.saveData) return 'save-data';
    var ect = conn.effectiveType || '';
    if (ect === 'slow-2g' || ect === '2g') return 'slow';
    if (ect === '3g') return 'medium';
    return 'fast';
};
