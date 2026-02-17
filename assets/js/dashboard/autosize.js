/* ============================================================
   Autosize Textareas
   ============================================================ */
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

TinyShop.initAutosize = function() {
    $('textarea.autosize').each(function() {
        TinyShop.autosize(this);
    });
};
