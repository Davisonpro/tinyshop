{* Bloom — Warm refined desktop footer
   Warm gray bg, gold headings, icon-only social, subtle borders.
   Compact layout with brand, contact links, social icons. *}
<footer class="desktop-footer">
    <div class="desktop-footer-inner">
        <div class="desktop-footer-brand">
            {if $shop.shop_logo}
                <img src="{$shop.shop_logo}" alt="{$shop.store_name|escape}" class="desktop-footer-logo">
            {else}
                <span class="desktop-footer-logo desktop-footer-logo-placeholder">{$shop.store_name|truncate:1:''|upper}</span>
            {/if}
            <div class="desktop-footer-brand-info">
                {if $shop.show_store_name|default:1}
                    <span class="desktop-footer-name">{$shop.store_name|default:$shop.name|escape}</span>
                {/if}
                {if $shop.show_tagline|default:1 && $shop.shop_tagline}
                    <span class="desktop-footer-tagline">{$shop.shop_tagline|escape}</span>
                {/if}
            </div>
        </div>

        {if $shop.contact_whatsapp || $shop.contact_email || $shop.contact_phone || $shop.map_link}
        <div class="desktop-footer-section">
            <h4 class="desktop-footer-heading">Contact</h4>
            <div class="desktop-footer-links">
                {if $shop.contact_whatsapp}
                    <a href="https://wa.me/{$shop.contact_whatsapp}" target="_blank" rel="noopener">
                        <i class="fa-brands fa-whatsapp"></i>
                        WhatsApp
                    </a>
                {/if}
                {if $shop.contact_email}
                    <a href="mailto:{$shop.contact_email}">
                        <i class="fa-solid fa-envelope"></i>
                        {$shop.contact_email|escape}
                    </a>
                {/if}
                {if $shop.contact_phone}
                    <a href="tel:{$shop.contact_phone}">
                        <i class="fa-solid fa-phone"></i>
                        {$shop.contact_phone|escape}
                    </a>
                {/if}
                {if $shop.map_link}
                    <a href="{$shop.map_link}" target="_blank" rel="noopener">
                        <i class="fa-solid fa-location-dot"></i>
                        Visit Us
                    </a>
                {/if}
            </div>
        </div>
        {/if}

        {if $shop.social_instagram || $shop.social_tiktok || $shop.social_facebook}
        <div class="desktop-footer-section">
            <h4 class="desktop-footer-heading">Follow</h4>
            <div class="desktop-footer-social">
                {if $shop.social_instagram}
                    <a href="https://instagram.com/{$shop.social_instagram|escape}" target="_blank" rel="noopener" aria-label="Instagram">
                        <i class="fa-brands fa-instagram"></i>
                    </a>
                {/if}
                {if $shop.social_tiktok}
                    <a href="https://tiktok.com/@{$shop.social_tiktok|escape}" target="_blank" rel="noopener" aria-label="TikTok">
                        <i class="fa-brands fa-tiktok"></i>
                    </a>
                {/if}
                {if $shop.social_facebook}
                    <a href="https://facebook.com/{$shop.social_facebook|escape}" target="_blank" rel="noopener" aria-label="Facebook">
                        <i class="fa-brands fa-facebook-f"></i>
                    </a>
                {/if}
            </div>
        </div>
        {/if}
    </div>
    <div class="desktop-footer-bottom">
        <span>&copy; {$shop.store_name|escape} &middot; Made with <i class="fa-solid fa-heart" style="font-size:12px;color:var(--color-accent)"></i> on <a href="{$base_url}">{$app_name}</a></span>
    </div>
</footer>
