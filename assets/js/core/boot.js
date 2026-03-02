/**
 * Boot — initialise the SPA router on document ready and
 * fire the first page:init event so every page module runs.
 *
 * @since 1.0.0
 */
$(function() {
    TinyShop.spa.init();
    $(document).trigger('page:init');
});
