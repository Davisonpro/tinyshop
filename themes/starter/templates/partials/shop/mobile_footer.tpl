{hook name="theme.mobile_footer.before"}
<footer class="mobile-footer">
    <div class="mobile-footer-brand">
        {if $shop.shop_logo}
            <img src="{$shop.shop_logo}" alt="{$shop.store_name|escape}" class="mobile-footer-logo">
        {else}
            <span class="mobile-footer-logo mobile-footer-logo-placeholder">{$shop.store_name|truncate:1:''|upper}</span>
        {/if}
        {if $shop.show_store_name|default:1}
            <div class="mobile-footer-name">{$shop.store_name|escape}</div>
        {/if}
        {if $shop.show_tagline|default:1 && $shop.shop_tagline}
            <div class="mobile-footer-tagline">{$shop.shop_tagline|escape}</div>
        {/if}
    </div>

    {if $shop.contact_whatsapp || $shop.contact_email || $shop.contact_phone}
    <div class="mobile-footer-section">
        <div class="mobile-footer-heading">Contact</div>
        <div class="mobile-footer-links">
            {hook name="theme.footer_contact.before"}
            {if $shop.contact_whatsapp}
                <a href="https://wa.me/{$shop.contact_whatsapp}" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>
            {/if}
            {if $shop.contact_email}
                <a href="mailto:{$shop.contact_email}"><i class="fa-regular fa-envelope"></i> {$shop.contact_email|escape}</a>
            {/if}
            {if $shop.contact_phone}
                <a href="tel:{$shop.contact_phone}"><i class="fa-solid fa-phone"></i> {$shop.contact_phone|escape}</a>
            {/if}
            {hook name="theme.footer_contact.after"}
        </div>
    </div>
    {/if}

    <div class="mobile-footer-section">
        <div class="mobile-footer-heading">Information</div>
        <div class="mobile-footer-links">
            {hook name="theme.footer_links.before"}
            <a href="/collections">Collections</a>
            <a href="#">Shipping &amp; Returns</a>
            <a href="#">Privacy Policy</a>
            {hook name="theme.footer_links.after"}
        </div>
    </div>

    {if $shop.social_instagram || $shop.social_tiktok || $shop.social_facebook}
    <div class="mobile-footer-social">
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

    <div class="mobile-footer-bottom">
        &copy; {$smarty.now|date_format:"%Y"} {$shop.store_name|escape}.{if !isset($theme_options.show_powered_by) || $theme_options.show_powered_by} Powered by TinyShop.{/if}
    </div>
</footer>
{hook name="theme.mobile_footer.after"}
