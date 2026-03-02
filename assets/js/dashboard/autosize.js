/**
 * Auto-resize a textarea to fit its content.
 *
 * @since 1.0.0
 *
 * @param {HTMLTextAreaElement} el The textarea element.
 */
TinyShop.autosize = function(el) {
    if (!el.offsetParent) return;
    el.style.overflow = 'hidden';
    el.style.resize = 'none';
    el.style.height = 'auto';
    var h = el.scrollHeight;
    var cs = window.getComputedStyle(el);
    if (cs.boxSizing === 'border-box') {
        h += parseFloat(cs.borderTopWidth) + parseFloat(cs.borderBottomWidth);
    }
    el.style.height = h + 'px';
};

/** Autosize all textarea.autosize elements on the page. */
TinyShop.initAutosize = function() {
    $('textarea.autosize').each(function() {
        TinyShop.autosize(this);
    });
};
