{* Contact Bottom Sheet *}
<div class="contact-sheet-backdrop" id="contactSheetBackdrop">
    <div class="contact-sheet">
        <div class="contact-sheet-handle"></div>
        <div class="contact-sheet-header">
            <span class="contact-sheet-title">Get in touch</span>
            <button type="button" class="contact-sheet-close" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="contact-sheet-body">
            {if $shop.contact_whatsapp}
            <a href="https://wa.me/{$shop.contact_whatsapp}" target="_blank" rel="noopener" class="contact-sheet-item">
                <span class="contact-sheet-icon contact-icon-whatsapp"><i class="fa-brands fa-whatsapp"></i></span>
                <span class="contact-sheet-label">WhatsApp</span>
            </a>
            {/if}
            {if $shop.contact_email}
            <a href="mailto:{$shop.contact_email}" class="contact-sheet-item">
                <span class="contact-sheet-icon"><i class="fa-regular fa-envelope"></i></span>
                <span class="contact-sheet-label">{$shop.contact_email|escape}</span>
            </a>
            {/if}
            {if $shop.contact_phone}
            <a href="tel:{$shop.contact_phone}" class="contact-sheet-item">
                <span class="contact-sheet-icon"><i class="fa-solid fa-phone"></i></span>
                <span class="contact-sheet-label">{$shop.contact_phone|escape}</span>
            </a>
            {/if}
            {if $shop.map_link}
            <a href="{$shop.map_link}" target="_blank" rel="noopener" class="contact-sheet-item">
                <span class="contact-sheet-icon"><i class="fa-solid fa-location-dot"></i></span>
                <span class="contact-sheet-label">Find us on map</span>
            </a>
            {/if}

            {if $shop.social_instagram || $shop.social_tiktok || $shop.social_facebook}
            <div class="contact-sheet-social">
                {if $shop.social_instagram}
                <a href="https://instagram.com/{$shop.social_instagram|escape}" target="_blank" rel="noopener" class="contact-sheet-social-link" aria-label="Instagram">
                    <i class="fa-brands fa-instagram"></i>
                </a>
                {/if}
                {if $shop.social_tiktok}
                <a href="https://tiktok.com/@{$shop.social_tiktok|escape}" target="_blank" rel="noopener" class="contact-sheet-social-link" aria-label="TikTok">
                    <i class="fa-brands fa-tiktok"></i>
                </a>
                {/if}
                {if $shop.social_facebook}
                <a href="https://facebook.com/{$shop.social_facebook|escape}" target="_blank" rel="noopener" class="contact-sheet-social-link" aria-label="Facebook">
                    <i class="fa-brands fa-facebook-f"></i>
                </a>
                {/if}
            </div>
            {/if}
        </div>
    </div>
</div>
