<a href="/{$product.slug|default:$product.id}" class="product-card{if $product.is_sold} product-card-sold{/if}" data-category="{$product.category_id|default:''}">
    <div class="product-card-img">
        {if $product.is_sold}
            <span class="product-badge product-badge-sold">Sold</span>
        {elseif $product.compare_price && $product.compare_price > $product.price}
            {math equation="round((1 - x/y) * 100)" x=$product.price y=$product.compare_price assign="discount_pct"}
            <span class="product-badge product-badge-sale">-{$discount_pct}%</span>
        {/if}
        {if $product.image_url}
            <img src="{$product.image_url|escape}" alt="{$product.name|escape}" loading="lazy" onload="this.classList.add('loaded')">
        {else}
            <img src="/public/img/placeholder.svg" alt="{$product.name|escape}" loading="lazy" onload="this.classList.add('loaded')">
        {/if}
    </div>
    <div class="product-card-body">
        <div class="product-card-info">
            <h3 class="product-title">{$product.name|escape}</h3>
            <div class="product-price">
                {if $product.compare_price && $product.compare_price > $product.price && !$product.is_sold}
                    <span class="price-compare">{$currency_symbol|default:''}{$product.compare_price|format_price}</span>
                {/if}
                <span{if $product.compare_price && $product.compare_price > $product.price && !$product.is_sold} class="price-sale"{/if}>{$currency_symbol|default:''}{$product.price|format_price}</span>
            </div>
        </div>
        <div class="product-card-arrow">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </div>
    </div>
</a>
