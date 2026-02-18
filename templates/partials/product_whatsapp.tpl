{* WhatsApp order CTA — only when payments are enabled (otherwise sticky CTA already shows WhatsApp) *}
{if !$product.is_sold && !empty($has_payments) && $shop.contact_whatsapp}
    <a href="https://wa.me/{$shop.contact_whatsapp}?text={("Hi! I'm interested in: "|cat:$product.name)|escape:'url'}" class="product-whatsapp-cta" target="_blank" rel="noopener">
        <i class="fa-brands fa-whatsapp"></i>
        <span>Order via WhatsApp</span>
    </a>
{/if}
