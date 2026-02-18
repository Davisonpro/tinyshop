{if !empty($hero_slides)}
<div class="hero-slider" id="heroSlider" data-count="{$hero_slides|@count}">
    <div class="hero-slider-track" id="heroSliderTrack">
        {foreach $hero_slides as $slide}
        <div class="hero-slide">
            <div class="hero-slide-img" style="background-image: url('{$slide.image_url|escape}');"></div>
            {if $slide.heading || $slide.link_url}
            <div class="hero-slide-content">
                {if $slide.heading}<h2 class="hero-slide-title">{$slide.heading|escape}</h2>{/if}
                {if $slide.subheading}<p class="hero-slide-desc">{$slide.subheading|escape}</p>{/if}
                {if $slide.link_url}
                <a href="{$slide.link_url|escape}" class="hero-slide-cta">
                    {$slide.link_text|default:'Shop Now'|escape}
                    <svg width="12" height="12" viewBox="0 0 64 64" fill="currentColor"><path d="M12,56.43,36.48,32,12,7.52,19.53,0,51.61,32,19.53,64Z"/></svg>
                </a>
                {/if}
            </div>
            {/if}
        </div>
        {/foreach}
    </div>
    {if $hero_slides|@count > 1}
    <button class="hero-slider-prev" aria-label="Previous slide">
        <svg width="11" height="11" viewBox="0 0 7 11" fill="currentColor"><path d="M5.5 11L0 5.5L5.5 0L6.476.976 1.953 5.5l4.523 4.524L5.5 11Z"/></svg>
    </button>
    <button class="hero-slider-next" aria-label="Next slide">
        <svg width="11" height="11" viewBox="0 0 7 11" fill="currentColor"><path d="M1.5 11L7 5.5 1.5 0 .524.976 5.047 5.5.524 10.024 1.5 11Z"/></svg>
    </button>
    <div class="hero-slider-dots"></div>
    {/if}
</div>
{/if}
