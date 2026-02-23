/* ============================================================
   One-time delegated handlers (survive SPA body swaps)
   ============================================================ */
$(function() {
    // Bloom desktop search — redirect on product pages
    var $bloomSearch = $('#bloomDesktopSearch');
    if ($bloomSearch.length && !$('#catalogue').length) {
        $bloomSearch.on('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                var q = $.trim($(this).val());
                if (q) {
                    TinyShop.navigate('/?search=' + encodeURIComponent(q));
                } else {
                    TinyShop.navigate('/');
                }
            }
        });
    }
});

$(function() {
    // Anchor scrolling
    $(document).on('click', 'a[href^="#"]', function(e) {
        var target = $(this.getAttribute('href'));
        if (target.length) {
            e.preventDefault();
            $('html, body').animate({ scrollTop: target.offset().top - 60 }, 400);
        }
    });

    // Variation option selection
    $(document).on('click', '.product-variation-option', function() {
        var $group = $(this).closest('.product-variation-options');
        $group.find('.product-variation-option').removeClass('selected');
        $(this).addClass('selected');
    });

    // Share sheet (all delegated)
    $(document).on('click', '[data-share-trigger]', function(e) {
        e.preventDefault();
        var baseUrl = window.location.href.split('?')[0];
        var title = document.title;

        // Use native share on supported devices
        if (navigator.share) {
            navigator.share({ title: title, url: baseUrl + '?utm_source=native' }).then(function() {
                TinyShop.toast('Thanks for sharing!');
            }).catch(function() { /* user cancelled — no action */ });
            return;
        }

        var $b = $('#shareSheetBackdrop');

        $b.find('[data-share-action="whatsapp"]').attr('href',
            'https://wa.me/?text=' + encodeURIComponent(title + ' ' + baseUrl + '?utm_source=whatsapp'));
        $b.find('[data-share-action="facebook"]').attr('href',
            'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(baseUrl + '?utm_source=facebook'));
        $b.find('[data-share-action="twitter"]').attr('href',
            'https://twitter.com/intent/tweet?text=' + encodeURIComponent(title) + '&url=' + encodeURIComponent(baseUrl + '?utm_source=x'));
        $b.find('[data-share-action="email"]').attr('href',
            'mailto:?subject=' + encodeURIComponent(title) + '&body=' + encodeURIComponent(baseUrl + '?utm_source=email'));
        $b.addClass('active');
        document.body.style.overflow = 'hidden';
    });

    $(document).on('click', '#shareSheetBackdrop', function(e) {
        if (e.target === this) {
            $(this).removeClass('active');
            document.body.style.overflow = '';
        }
    });
    $(document).on('click', '#shareSheetBackdrop .share-sheet-close', function() {
        $('#shareSheetBackdrop').removeClass('active');
        document.body.style.overflow = '';
    });
    $(document).on('click', '#shareSheetBackdrop [data-share-action="copy"]', function() {
        var $label = $(this).find('.share-sheet-label');
        var url = window.location.href;
        function onCopied() {
            $label.text('Copied!');
            setTimeout(function() {
                $label.text('Copy Link');
                $('#shareSheetBackdrop').removeClass('active');
                document.body.style.overflow = '';
            }, 800);
            TinyShop.toast('Link copied!');
        }
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(onCopied, function() {
                TinyShop.toast('Could not copy link', 'error');
            });
        } else {
            var ta = document.createElement('textarea');
            ta.value = url;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            onCopied();
        }
    });
    $(document).on('click', '#shareSheetBackdrop a[data-share-action]', function() {
        setTimeout(function() { $('#shareSheetBackdrop').removeClass('active'); document.body.style.overflow = ''; }, 300);
    });
});
