/**
 * Pricing page — monthly/yearly toggle.
 * Uses page:init so it re-initializes on SPA navigation.
 */
$(document).on('page:init', function() {
    var btns = document.querySelectorAll('.pricing-toggle-btn');
    if (!btns.length) return;

    var monthlyEls = document.querySelectorAll('[data-monthly]');
    var yearlyEls = document.querySelectorAll('[data-yearly]');

    btns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var period = this.getAttribute('data-period');

            btns.forEach(function(b) { b.classList.remove('active'); });
            this.classList.add('active');

            if (period === 'yearly') {
                monthlyEls.forEach(function(el) { el.style.display = 'none'; });
                yearlyEls.forEach(function(el) { el.style.display = ''; });
            } else {
                monthlyEls.forEach(function(el) { el.style.display = ''; });
                yearlyEls.forEach(function(el) { el.style.display = 'none'; });
            }
        });
    });
});
