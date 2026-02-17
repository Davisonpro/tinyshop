{* Volt theme — bold, neon-accented CTA *}
{if !$product.is_sold}
    {if !empty($has_payments)}
        <div class="sticky-cta">
            <div class="sticky-cta-row">
                <div class="cart-qty-selector">
                    <button type="button" class="cart-qty-btn" id="cartQtyMinus">&minus;</button>
                    <input type="number" id="cartQty" value="1" min="1" max="{if $product.stock_quantity !== null}{$product.stock_quantity}{else}99{/if}" readonly>
                    <button type="button" class="cart-qty-btn" id="cartQtyPlus">+</button>
                </div>
                <button type="button" class="btn btn-accent sticky-cta-btn" id="addToCartBtn"
                    data-product-id="{$product.id}"
                    data-product-name="{$product.name|escape}"
                    data-product-price="{$product.price}"
                    data-product-compare-price="{$product.compare_price|default:0}"
                    data-product-image="{$product.image_url|escape}"
                    data-product-slug="{$product.slug|escape}">
                    Add to Cart
                </button>
            </div>
        </div>
    {elseif $shop.contact_whatsapp}
        <div class="sticky-cta">
            <a href="https://wa.me/{$shop.contact_whatsapp}?text={("Interested in: "|cat:$product.name)|escape:'url'}" class="btn btn-whatsapp" target="_blank" rel="noopener">
                <i class="fa-brands fa-whatsapp"></i>
                <span class="btn-label">Send a Message</span>
            </a>
        </div>
    {elseif $shop.contact_phone}
        <div class="sticky-cta">
            <a href="tel:{$shop.contact_phone}" class="btn btn-accent">
                <i class="fa-solid fa-phone"></i>
                <span class="btn-label">Send a Message</span>
            </a>
        </div>
    {elseif $shop.contact_email}
        <div class="sticky-cta">
            <a href="mailto:{$shop.contact_email}?subject={("Order: "|cat:$product.name)|escape:'url'}" class="btn btn-accent">
                <i class="fa-solid fa-envelope"></i>
                <span class="btn-label">Send a Message</span>
            </a>
        </div>
    {/if}
{/if}
