/* ============================================================
   Bottom Nav — Contact Sheet (delegated)
   ============================================================ */
$(function() {
    // --- Contact sheet ---
    $(document).on('click', '.contact-sheet-toggle', function(e) {
        e.preventDefault();
        $('#contactSheetBackdrop').addClass('active');
        document.body.style.overflow = 'hidden';
    });

    $(document).on('click', '#contactSheetBackdrop', function(e) {
        if (e.target === this) {
            $(this).removeClass('active');
            document.body.style.overflow = '';
        }
    });

    $(document).on('click', '.contact-sheet-close', function() {
        $('#contactSheetBackdrop').removeClass('active');
        document.body.style.overflow = '';
    });

    // Escape key closes contact sheet
    $(document).on('keydown', function(e) {
        if (e.key !== 'Escape') return;

        var $contact = $('#contactSheetBackdrop');
        if ($contact.hasClass('active')) {
            $contact.removeClass('active');
            document.body.style.overflow = '';
        }
    });
});
