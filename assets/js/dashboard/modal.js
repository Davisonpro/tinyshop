/* ============================================================
   Modal (Bottom Sheet) — kept for generic use
   ============================================================ */
TinyShop._previousFocus = null;

TinyShop._modalClearTimer = null;
TinyShop.openModal = function(title, contentHtml) {
    // Cancel any pending clear from a previous closeModal
    if (TinyShop._modalClearTimer) { clearTimeout(TinyShop._modalClearTimer); TinyShop._modalClearTimer = null; }
    TinyShop._previousFocus = document.activeElement;
    $('#modalTitle').text(title);
    $('#modalBody').html(contentHtml);
    $('#modal').addClass('active');
    document.body.style.overflow = 'hidden';
    // Focus first focusable element inside modal
    setTimeout(function() {
        var $focusable = $('#modalBody').find('input, button, select, textarea, a[href]').filter(':visible').first();
        if ($focusable.length) $focusable.focus();
        else $('#modalClose').focus();
    }, 100);
};

TinyShop.closeModal = function() {
    $('#modal').removeClass('active');
    document.body.style.overflow = '';
    if (TinyShop._modalClearTimer) clearTimeout(TinyShop._modalClearTimer);
    TinyShop._modalClearTimer = setTimeout(function() { $('#modalBody').html(''); TinyShop._modalClearTimer = null; }, 300);
    // Restore focus to trigger element
    if (TinyShop._previousFocus) {
        try { TinyShop._previousFocus.focus(); } catch(e) {}
        TinyShop._previousFocus = null;
    }
};

/**
 * TinyShop.confirm(title, message, confirmLabel, onConfirm, variant)
 * Also accepts: TinyShop.confirm({ title, message, confirmText, onConfirm, variant })
 */
TinyShop.confirm = function(title, message, confirmLabel, onConfirm, variant) {
    if (typeof title === 'object' && title !== null) {
        var opts = title;
        title = opts.title || 'Confirm';
        message = opts.message || '';
        confirmLabel = opts.confirmText || opts.confirmLabel || 'Confirm';
        onConfirm = opts.onConfirm;
        variant = opts.variant;
    }
    var btnBg = variant === 'danger' ? '#FF3B30' : 'var(--color-accent)';
    var html = '<p style="margin-bottom:20px;color:var(--color-text-muted);font-size:0.9rem;">' + message + '</p>' +
        '<div style="display:flex;gap:10px">' +
            '<button type="button" id="confirmModalCancel" style="flex:1;min-height:48px;font-size:1rem;font-weight:600;border-radius:12px;background:var(--color-bg-secondary);color:var(--color-text);border:none;cursor:pointer;font-family:inherit;">Cancel</button>' +
            '<button type="button" id="confirmModalOk" style="flex:1;min-height:48px;font-size:1rem;font-weight:600;border-radius:12px;background:' + btnBg + ';color:#fff;border:none;cursor:pointer;font-family:inherit;">' + (confirmLabel || 'Confirm') + '</button>' +
        '</div>';
    TinyShop.openModal(title, html);
    $('#confirmModalCancel').on('click', function() { TinyShop.closeModal(); });
    $('#confirmModalOk').on('click', function() {
        if (typeof onConfirm === 'function') onConfirm();
    });
};

// Modal event handlers — use document delegation so they survive SPA navigation
$(document).on('click', '#modalClose, #modal', function(e) {
    if (e.target === this) TinyShop.closeModal();
});

$(document).on('keydown', function(e) {
    if (e.key === 'Escape' && $('#modal').hasClass('active')) {
        TinyShop.closeModal();
    }
});

$(document).on('keydown', '#modal', function(e) {
    if (e.key !== 'Tab') return;
    var $focusable = $(this).find('input, button, select, textarea, a[href], [tabindex]:not([tabindex="-1"])').filter(':visible');
    if (!$focusable.length) return;
    var first = $focusable.first()[0];
    var last = $focusable.last()[0];
    if (e.shiftKey && document.activeElement === first) {
        e.preventDefault();
        last.focus();
    } else if (!e.shiftKey && document.activeElement === last) {
        e.preventDefault();
        first.focus();
    }
});
