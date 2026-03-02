/**
 * Bottom nav — contact sheet interactions (delegated).
 *
 * All handlers are bound to the document so they survive
 * SPA body swaps.
 *
 * @since 1.0.0
 */
$(function() {
    // Open contact sheet
    $(document).on('click', '.contact-sheet-toggle', function(e) {
        e.preventDefault();
        $('#contactSheetBackdrop').addClass('active');
        document.body.style.overflow = 'hidden';
    });

    // Close on backdrop click
    $(document).on('click', '#contactSheetBackdrop', function(e) {
        if (e.target === this) {
            $(this).removeClass('active');
            document.body.style.overflow = '';
        }
    });

    // Close button
    $(document).on('click', '.contact-sheet-close', function() {
        $('#contactSheetBackdrop').removeClass('active');
        document.body.style.overflow = '';
    });

    // Escape key
    $(document).on('keydown', function(e) {
        if (e.key !== 'Escape') return;
        var $contact = $('#contactSheetBackdrop');
        if ($contact.hasClass('active')) {
            $contact.removeClass('active');
            document.body.style.overflow = '';
        }
    });
});
