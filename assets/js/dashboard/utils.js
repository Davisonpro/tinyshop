/* ============================================================
   Escape HTML helper
   ============================================================ */
function escapeHtml(str) {
    if (!str) return '';
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

/* ============================================================
   Currency formatter
   ============================================================ */
TinyShop.formatPrice = function(amount, currency) {
    currency = currency || 'KES';
    var num = parseFloat(amount);
    if (isNaN(num)) return '0';

    // Currencies that don't use decimal places
    var noDecimals = ['KES','NGN','TZS','UGX','RWF','ETB','XOF','GHS'];
    var useDecimals = noDecimals.indexOf(currency) === -1;
    var formatted = useDecimals
        ? num.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')
        : Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');

    return currency + ' ' + formatted;
};

/* ============================================================
   Price Input Formatting (comma-separated with decimal)
   ============================================================ */
TinyShop.initPriceInput = function($input) {
    function formatDisplay(val) {
        var clean = val.replace(/[^0-9.]/g, '');
        var parts = clean.split('.');
        if (parts.length > 2) clean = parts[0] + '.' + parts.slice(1).join('');
        parts = clean.split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        return parts.join('.');
    }

    function getRawValue($el) {
        return $el.val().replace(/,/g, '');
    }

    var initVal = $input.val();
    if (initVal && !isNaN(parseFloat(initVal))) {
        $input.val(formatDisplay(initVal));
    }

    $input.on('input', function() {
        var cursorPos = this.selectionStart;
        var oldVal = $(this).val();
        var oldLen = oldVal.length;
        var formatted = formatDisplay(oldVal);
        $(this).val(formatted);
        var diff = formatted.length - oldLen;
        this.setSelectionRange(cursorPos + diff, cursorPos + diff);
    });

    $input.data('rawValue', function() {
        return getRawValue($input);
    });
};
