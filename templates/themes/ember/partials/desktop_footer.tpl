{* Ember theme — luxf-style editorial desktop footer *}
<footer class="desktop-footer ember-footer">
    <div class="ember-footer-inner">
        {* Top row: brand name (left) + social circles (right) *}
        <div class="ember-footer-top">
            <a href="/" class="ember-footer-brand">
                {if $shop.show_store_name|default:1}
                    {$shop.store_name|escape}
                {/if}
            </a>
            {if $shop.social_instagram || $shop.social_tiktok || $shop.social_facebook}
            <div class="ember-footer-social">
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
            {/if}
        </div>

        {* Divider *}
        <div class="ember-footer-divider"></div>

        {* Bottom row: contact links (left) + copyright (right) *}
        <div class="ember-footer-bottom">
            <nav class="ember-footer-links">
                {if $shop.contact_whatsapp}
                    <a href="https://wa.me/{$shop.contact_whatsapp}" target="_blank" rel="noopener">WhatsApp</a>
                {/if}
                {if $shop.contact_email}
                    <a href="mailto:{$shop.contact_email}">Email</a>
                {/if}
                {if $shop.contact_phone}
                    <a href="tel:{$shop.contact_phone}">Call</a>
                {/if}
                {if $shop.map_link}
                    <a href="{$shop.map_link}" target="_blank" rel="noopener">Location</a>
                {/if}
            </nav>
            <span class="ember-footer-copy">&copy; {$shop.store_name|escape}</span>
        </div>
    </div>
</footer>
