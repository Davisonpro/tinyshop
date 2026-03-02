/**
 * Dashboard page:init handler.
 *
 * Runs on every page load and SPA swap to initialise
 * dashboard-specific modules (product list, form, autosize).
 *
 * @since 1.0.0
 */
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
