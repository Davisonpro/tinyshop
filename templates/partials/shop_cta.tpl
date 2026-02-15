{* Floating contact CTA — included in base layout, shows on all shop pages.
   Mobile: full-width bottom bar (hidden on product pages where product_cta handles it).
   Desktop: circular FAB in bottom-right corner. *}
{if !empty($shop.contact_whatsapp)}
    <div class="floating-contact-cta">
        <a href="https://wa.me/{$shop.contact_whatsapp}?text={("Hi! I'm interested in your products")|escape:'url'}" class="btn btn-whatsapp" target="_blank" rel="noopener">
            <i class="fa-brands fa-whatsapp"></i>
            <span class="btn-label">Chat on WhatsApp</span>
        </a>
    </div>
{elseif !empty($shop.contact_phone)}
    <div class="floating-contact-cta">
        <a href="tel:{$shop.contact_phone}" class="btn btn-accent">
            <i class="fa-solid fa-phone"></i>
            <span class="btn-label">Call Us</span>
        </a>
    </div>
{elseif !empty($shop.contact_email)}
    <div class="floating-contact-cta">
        <a href="mailto:{$shop.contact_email}" class="btn btn-accent">
            <i class="fa-solid fa-envelope"></i>
            <span class="btn-label">Email Us</span>
        </a>
    </div>
{/if}
