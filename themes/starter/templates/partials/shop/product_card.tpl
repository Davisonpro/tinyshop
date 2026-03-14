{hook name="theme.product_card.before" product=$product}
<a href="/{$product.slug|default:$product.id}" class="product-card{if $product.is_sold} product-card-sold{/if}{if !empty($product.variations_data)} has-variations{/if}" data-category="{$product.category_id|default:''}">
    <div class="product-card-img">
        <div class="product-badges">
            {if $product.is_sold}
                <span class="product-badge product-badge-sold">Sold out</span>
            {elseif $product.compare_price && $product.compare_price > $product.price}
                {math equation="round((1 - x/y) * 100)" x=$product.price y=$product.compare_price assign="discount_pct"}
                <span class="product-badge product-badge-sale">{$discount_pct}% off</span>
            {elseif $product.is_featured}
                <span class="product-badge product-badge-featured">Best Seller</span>
            {elseif $product.created_at|is_recent:14}
                <span class="product-badge product-badge-new">New</span>
            {/if}
        </div>
        {if $product.image_url}
            <img src="{$product.image_url|escape}" alt="{$product.name|escape}" loading="lazy" decoding="async" onload="this.classList.add('loaded')">
        {else}
            <img src="/public/img/placeholder.svg" alt="{$product.name|escape}" loading="lazy" decoding="async" onload="this.classList.add('loaded')">
        {/if}
        {if !$product.is_sold}
            {if !empty($product.variations_data)}
                <button type="button" class="product-card-atc product-card-atc-options">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/></svg>
                    <span class="product-card-atc-label">Options</span>
                </button>
            {else}
                <button type="button" class="product-card-atc"
                    data-product-id="{$product.id}"
                    data-product-name="{$product.name|escape}"
                    data-product-price="{$product.price}"
                    data-product-compare-price="{$product.compare_price|default:0}"
                    data-product-image="{$product.image_url|default:''|escape}"
                    data-product-slug="{$product.slug|default:$product.id}">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <span class="product-card-atc-label">Add</span>
                </button>
            {/if}
        {/if}
    </div>
    <div class="product-card-body">
        <div class="product-price">
            {if $product.compare_price && $product.compare_price > $product.price && !$product.is_sold}
                <span class="price-compare">{$currency_symbol|default:''}{$product.compare_price|format_price}</span>
                <span class="price-sale">{$currency_symbol|default:''}{$product.price|format_price}</span>
            {else}
                <span>{$currency_symbol|default:''}{$product.price|format_price}</span>
            {/if}
        </div>
        <h3 class="product-title">{$product.name|escape}</h3>
    </div>
</a>
{hook name="theme.product_card.after" product=$product}