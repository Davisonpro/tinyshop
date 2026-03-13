/**
 * Escape a string for safe insertion into HTML.
 *
 * Uses the DOM to entity-encode the string rather than
 * a regex, so it handles all edge cases correctly.
 *
 * @since 1.0.0
 *
 * @param {string} str Raw string.
 * @return {string} HTML-safe string.
 */
TinyShop.escapeHtml = function(str) {
    if (!str) return '';
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
};

/* Global alias so inline <script> blocks in templates can call escapeHtml() directly. */
var escapeHtml = TinyShop.escapeHtml;

/**
 * Format a numeric amount as a currency string.
 *
 * Currencies listed in the noDecimals array (e.g. KES, NGN)
 * are rendered without decimal places; all others get two.
 *
 * @since 1.0.0
 *
 * @param {number|string} amount   The numeric value.
 * @param {string}        [currency] ISO currency code (default 'KES').
 * @return {string} Formatted price string, e.g. "KES 1,200".
 */
TinyShop.formatPrice = function(amount, currency) {
    currency = currency || 'KES';
    var num = parseFloat(amount);
    if (isNaN(num)) return '0';

    var noDecimals = ['KES','NGN','TZS','UGX','RWF','ETB','XOF','GHS'];
    var useDecimals = noDecimals.indexOf(currency) === -1;
    var formatted = useDecimals
        ? num.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')
        : Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');

    return currency + ' ' + formatted;
};

/**
 * Initialise a price input with live comma formatting.
 *
 * Adds thousand-separator commas as the user types while
 * preserving the cursor position. Attach a rawValue getter
 * via $input.data('rawValue')() to retrieve the clean number.
 *
 * @since 1.0.0
 *
 * @param {jQuery} $input A jQuery-wrapped <input> element.
 */
TinyShop.initPriceInput = function($input) {
    /** Format a raw numeric string with thousand separators. */
    function formatDisplay(val) {
        var clean = val.replace(/[^0-9.]/g, '');
        var parts = clean.split('.');
        if (parts.length > 2) clean = parts[0] + '.' + parts.slice(1).join('');
        parts = clean.split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        return parts.join('.');
    }

    /** Strip commas to get the raw numeric value. */
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
