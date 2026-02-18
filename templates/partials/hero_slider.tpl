{if !empty($hero_slides)}
<div class="hero-slider" id="heroSlider">
    <div class="hero-slider-track" id="heroSliderTrack">
        {foreach $hero_slides as $slide}
        <div class="hero-slide">
            <img src="{$slide.image_url|escape}" alt="{$slide.heading|escape}" class="hero-slide-img" loading="{if $slide@first}eager{else}lazy{/if}">
            {if $slide.heading || $slide.link_url}
            <div class="hero-slide-overlay">
                {if $slide.heading}<h2 class="hero-slide-heading">{$slide.heading|escape}</h2>{/if}
                {if $slide.subheading}<p class="hero-slide-subheading">{$slide.subheading|escape}</p>{/if}
                {if $slide.link_url}<a href="{$slide.link_url|escape}" class="hero-slide-cta">{$slide.link_text|default:'Shop Now'|escape}</a>{/if}
            </div>
            {/if}
        </div>
        {/foreach}
    </div>
    {if $hero_slides|@count > 1}
    <div class="hero-slider-dots" id="heroSliderDots">
        {foreach $hero_slides as $slide}
        <button class="hero-slider-dot{if $slide@first} active{/if}" data-index="{$slide@index}" aria-label="Slide {$slide@index + 1}"></button>
        {/foreach}
    </div>
    {/if}
</div>
<script>
(function() {
    var track = document.getElementById('heroSliderTrack');
    if (!track) return;
    var dots = document.querySelectorAll('#heroSliderDots .hero-slider-dot');
    var slideCount = track.children.length;
    if (slideCount <= 1) return;

    var current = 0;
    var autoTimer = null;

    function goTo(idx) {
        if (idx < 0) idx = slideCount - 1;
        if (idx >= slideCount) idx = 0;
        current = idx;
        track.children[idx].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'start' });
        dots.forEach(function(d, i) { d.classList.toggle('active', i === idx); });
    }

    // Dot click
    dots.forEach(function(dot) {
        dot.addEventListener('click', function() {
            goTo(parseInt(this.dataset.index, 10));
            resetAuto();
        });
    });

    // Detect scroll-snap settling
    var scrollTimeout;
    track.addEventListener('scroll', function() {
        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(function() {
            var w = track.offsetWidth;
            if (w === 0) return;
            var idx = Math.round(track.scrollLeft / w);
            if (idx !== current) {
                current = idx;
                dots.forEach(function(d, i) { d.classList.toggle('active', i === idx); });
            }
        }, 80);
    }, { passive: true });

    // Auto-advance
    function startAuto() {
        autoTimer = setInterval(function() { goTo(current + 1); }, 5000);
    }
    function resetAuto() {
        clearInterval(autoTimer);
        startAuto();
    }

    // Pause on touch / interaction
    track.addEventListener('touchstart', function() { clearInterval(autoTimer); }, { passive: true });
    track.addEventListener('touchend', function() { resetAuto(); }, { passive: true });

    // Pause when not visible
    if ('IntersectionObserver' in window) {
        new IntersectionObserver(function(entries) {
            if (entries[0].isIntersecting) { resetAuto(); } else { clearInterval(autoTimer); }
        }, { threshold: 0.3 }).observe(track.parentElement);
    } else {
        startAuto();
    }
})();
</script>
{/if}
