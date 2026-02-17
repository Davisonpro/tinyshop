/* ============================================================
   Init SPA on document ready
   ============================================================ */
$(function() {
    TinyShop.spa.init();
    $(document).trigger('page:init');
});
