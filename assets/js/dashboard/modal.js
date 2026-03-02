/**
 * Bottom-sheet modal with focus trapping and confirm dialog.
 *
 * Provides TinyShop.openModal / closeModal for generic content
 * and TinyShop.confirm for yes/no confirmation flows.
 *
 * @since 1.0.0
 */
TinyShop._previousFocus = null;
TinyShop._modalClearTimer = null;

/**
 * Open a bottom-sheet modal.
 *
 * @since 1.0.0
 *
 * @param {string} title       Modal heading text.
 * @param {string} contentHtml Inner HTML for the modal body.
 */
TinyShop.openModal = function(title, contentHtml) {
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

/** Close the active modal and restore focus to its trigger. */
TinyShop.closeModal = function() {
    $('#modal').removeClass('active');
    document.body.style.overflow = '';
    if (TinyShop._modalClearTimer) clearTimeout(TinyShop._modalClearTimer);
    TinyShop._modalClearTimer = setTimeout(function() { $('#modalBody').html(''); TinyShop._modalClearTimer = null; }, 300);
    if (TinyShop._previousFocus) {
        try { TinyShop._previousFocus.focus(); } catch(e) {}
        TinyShop._previousFocus = null;
    }
};

/**
 * Show a confirmation dialog inside the modal.
 *
 * Accepts either positional arguments or a single options object:
 *   TinyShop.confirm('Delete?', 'Are you sure?', 'Delete', fn, 'danger')
 *   TinyShop.confirm({ title, message, confirmText, onConfirm, variant })
 *
 * @since 1.0.0
 *
 * @param {string|Object} title        Heading text or options object.
 * @param {string}        [message]    Body text.
 * @param {string}        [confirmLabel] Confirm button label.
 * @param {Function}      [onConfirm]  Called when the user confirms.
 * @param {string}        [variant]    'danger' for red confirm button.
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

// Modal event handlers — delegated so they survive SPA navigation
$(document).on('click', '#modalClose, #modal', function(e) {
    if (e.target === this) TinyShop.closeModal();
});

$(document).on('keydown', function(e) {
    if (e.key === 'Escape' && $('#modal').hasClass('active')) {
        TinyShop.closeModal();
    }
});

// Focus trap — keep Tab cycling inside the modal
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
