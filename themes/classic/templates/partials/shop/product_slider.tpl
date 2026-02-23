{if !empty($slider_products)}
<div class="product-slider-section" data-scroll-container>
    <div class="section-header">
        <h2 class="section-title">{$slider_title|escape}</h2>
    </div>
    <div class="product-slider-track hide-scrollbar" data-scroll-track>
        {foreach $slider_products as $product}
        <div class="product-slider-card">
            {include file="partials/shop/product_card.tpl"}
        </div>
        {/foreach}
    </div>
    <button class="scroll-arrow scroll-arrow-prev" data-scroll-prev aria-label="Previous">
        <svg width="11" height="11" viewBox="0 0 7 11" fill="currentColor"><path d="M5.5 11L0 5.5L5.5 0L6.476.976 1.953 5.5l4.523 4.524L5.5 11Z"/></svg>
    </button>
    <button class="scroll-arrow scroll-arrow-next" data-scroll-next aria-label="Next">
        <svg width="11" height="11" viewBox="0 0 7 11" fill="currentColor"><path d="M1.5 11L7 5.5 1.5 0 .524.976 5.047 5.5.524 10.024 1.5 11Z"/></svg>
    </button>
</div>
{/if}
