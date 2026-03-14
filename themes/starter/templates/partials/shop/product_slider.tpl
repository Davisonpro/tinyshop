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
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
    </button>
    <button class="scroll-arrow scroll-arrow-next" data-scroll-next aria-label="Next">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
    </button>
</div>
{/if}