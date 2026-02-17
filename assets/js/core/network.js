/* ============================================================
   Network quality — connection-aware prefetch & loading
   ============================================================ */
TinyShop._networkQuality = function() {
    var conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
    if (!conn) return 'fast';
    if (conn.saveData) return 'save-data';
    var ect = conn.effectiveType || '';
    if (ect === 'slow-2g' || ect === '2g') return 'slow';
    if (ect === '3g') return 'medium';
    return 'fast';
};
