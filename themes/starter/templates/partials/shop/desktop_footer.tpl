{hook name="theme.desktop_footer.before"}
<footer class="desktop-footer">
    <div class="desktop-footer-inner">
        <div class="desktop-footer-brand">
            <span class="desktop-footer-name">{$shop.store_name|escape}</span>
        </div>
        <div class="desktop-footer-right">
            {hook name="theme.footer_contact.before"}
            {if $shop.contact_whatsapp}
                <a href="https://wa.me/{$shop.contact_whatsapp}" target="_blank" rel="noopener" class="desktop-footer-link"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>
            {/if}
            {if $shop.contact_email}
                <a href="mailto:{$shop.contact_email}" class="desktop-footer-link"><i class="fa-regular fa-envelope"></i> Email</a>
            {/if}
            {if $shop.contact_phone}
                <a href="tel:{$shop.contact_phone}" class="desktop-footer-link"><i class="fa-solid fa-phone"></i> Call</a>
            {/if}
            {hook name="theme.footer_contact.after"}
            {if $shop.social_instagram || $shop.social_tiktok || $shop.social_facebook}
            <span class="desktop-footer-divider"></span>
            <div class="desktop-footer-social">
                {if $shop.social_instagram}
                    <a href="https://instagram.com/{$shop.social_instagram|escape}" target="_blank" rel="noopener" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                {/if}
                {if $shop.social_tiktok}
                    <a href="https://tiktok.com/@{$shop.social_tiktok|escape}" target="_blank" rel="noopener" aria-label="TikTok"><i class="fa-brands fa-tiktok"></i></a>
                {/if}
                {if $shop.social_facebook}
                    <a href="https://facebook.com/{$shop.social_facebook|escape}" target="_blank" rel="noopener" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                {/if}
            </div>
            {/if}
        </div>
    </div>
    <div class="desktop-footer-bottom">
        &copy; {$smarty.now|date_format:"%Y"} {$shop.store_name|escape}.{if !isset($theme_options.show_powered_by) || $theme_options.show_powered_by} Powered by TinyShop.{/if}
    </div>
</footer>
{hook name="theme.desktop_footer.after"}
