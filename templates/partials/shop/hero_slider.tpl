{if !empty($theme_options.hero_slides_enabled) && !empty($theme_options.hero_slides_items)}
{assign var="slides" value=$theme_options.hero_slides_items}
<div class="hero-slider" id="heroSlider">
    <div class="hero-slider-track" id="heroSliderTrack">
        {foreach $slides as $slide}
        {if !empty($slide.image)}
        <div class="hero-slide">
            <img src="{$slide.image|escape}" alt="{$slide.title|escape}" class="hero-slide-img" loading="{if $slide@first}eager{else}lazy{/if}">
            {if $slide.title || $slide.link_url}
            <div class="hero-slide-overlay">
                {if $slide.title}<h2 class="hero-slide-heading">{$slide.title|escape}</h2>{/if}
                {if $slide.subtitle}<p class="hero-slide-subheading">{$slide.subtitle|escape}</p>{/if}
                {if $slide.link_url}<a href="{$slide.link_url|escape}" class="hero-slide-cta">{$slide.link_text|default:'Shop Now'|escape}</a>{/if}
            </div>
            {/if}
        </div>
        {/if}
        {/foreach}
    </div>
    {if $slides|@count > 1}
    <div class="hero-slider-dots" id="heroSliderDots">
        {assign var="dotIdx" value=0}
        {foreach $slides as $slide}
        {if !empty($slide.image)}
        <button class="hero-slider-dot{if $dotIdx == 0} active{/if}" data-index="{$dotIdx}" aria-label="Slide {$dotIdx + 1}"></button>
        {assign var="dotIdx" value=$dotIdx+1}
        {/if}
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

    dots.forEach(function(dot) {
        dot.addEventListener('click', function() {
            goTo(parseInt(this.dataset.index, 10));
            resetAuto();
        });
    });

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

    function startAuto() {
        autoTimer = setInterval(function() { goTo(current + 1); }, 5000);
    }
    function resetAuto() {
        clearInterval(autoTimer);
        startAuto();
    }

    track.addEventListener('touchstart', function() { clearInterval(autoTimer); }, { passive: true });
    track.addEventListener('touchend', function() { resetAuto(); }, { passive: true });

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
