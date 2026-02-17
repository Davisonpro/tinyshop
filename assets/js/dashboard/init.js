/* ============================================================
   page:init — global event fired on every page load / SPA swap.
   All page-specific init code listens for this event.
   ============================================================ */
$(document).on('page:init', function() {
    TinyShop.initProductList();
    TinyShop.initProductForm();
    TinyShop.initAutosize();
});

// One-time global delegates (survive SPA navigations)
$(function() {
    $(document).on('input', 'textarea.autosize', function() {
        TinyShop.autosize(this);
    });
    $(document).on('click', '.seo-toggle', function() {
        var $section = $(this).closest('.form-section');
        setTimeout(function() {
            $section.find('textarea.autosize').each(function() {
                TinyShop.autosize(this);
            });
        }, 250);
    });
});
