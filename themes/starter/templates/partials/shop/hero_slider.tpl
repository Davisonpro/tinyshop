{if !empty($theme_options.hero_slides_enabled) && !empty($theme_options.hero_slides_items)}
{assign var="slides" value=$theme_options.hero_slides_items}
<div class="hero-slider" id="heroSlider" data-count="{$slides|@count}">
    <div class="hero-slider-track" id="heroSliderTrack">
        {foreach $slides as $slide}
        {if !empty($slide.image)}
        <div class="hero-slide">
            <div class="hero-slide-img" style="background-image: url('{$slide.image|escape}');"></div>
            {if $slide.title || $slide.link_url}
            <div class="hero-slide-content">
                <div class="hero-slide-glass">
                    {if $slide.title}<h2 class="hero-slide-title">{$slide.title|escape}</h2>{/if}
                    {if $slide.subtitle}<p class="hero-slide-desc">{$slide.subtitle|escape}</p>{/if}
                    {if $slide.link_url}
                    <a href="{$slide.link_url|escape}" class="hero-slide-cta">
                        {$slide.link_text|default:'Shop Now'|escape}
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17L17 7M17 7H7M17 7V17"/></svg>
                    </a>
                    {/if}
                </div>
            </div>
            {/if}
        </div>
        {/if}
        {/foreach}
    </div>
    {if $slides|@count > 1}
    <button class="hero-slider-prev" aria-label="Previous slide">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
    </button>
    <button class="hero-slider-next" aria-label="Next slide">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
    </button>
    <div class="hero-slider-dots"></div>
    {/if}
</div>
{/if}