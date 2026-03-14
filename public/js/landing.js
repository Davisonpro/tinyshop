(function() {
  var _scrollHandler = null;
  var _observer = null;
  $(document).on("page:init", function() {
    if (_scrollHandler) {
      window.removeEventListener("scroll", _scrollHandler);
      _scrollHandler = null;
    }
    if (_observer) {
      _observer.disconnect();
      _observer = null;
    }
    var nav = document.getElementById("mkNav");
    if (!nav) return;
    var phone = document.querySelector(".land-phone");
    _scrollHandler = function() {
      var y = window.scrollY;
      nav.classList.toggle("scrolled", y > 10);
      if (phone && y < 800) {
        phone.style.transform = "translateY(" + y * -0.08 + "px)";
      }
    };
    window.addEventListener("scroll", _scrollHandler, { passive: true });
    var faqItems = document.querySelectorAll(".land-faq-item");
    for (var i = 0; i < faqItems.length; i++) {
      faqItems[i].querySelector(".land-faq-q").addEventListener("click", function() {
        var item = this.parentElement;
        var isOpen = item.classList.contains("open");
        for (var j = 0; j < faqItems.length; j++) {
          if (faqItems[j] !== item) {
            faqItems[j].classList.remove("open");
            faqItems[j].querySelector(".land-faq-q").setAttribute("aria-expanded", "false");
          }
        }
        item.classList.toggle("open", !isOpen);
        this.setAttribute("aria-expanded", !isOpen ? "true" : "false");
      });
    }
    var els = document.querySelectorAll(".land-reveal");
    if (!els.length) return;
    if ("IntersectionObserver" in window) {
      _observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(e) {
          if (e.isIntersecting) {
            e.target.classList.add("revealed");
            _observer.unobserve(e.target);
          }
        });
      }, { threshold: 0.08, rootMargin: "0px 0px -40px 0px" });
      els.forEach(function(el) {
        _observer.observe(el);
      });
    } else {
      els.forEach(function(el) {
        el.classList.add("revealed");
      });
    }
  });
})();
