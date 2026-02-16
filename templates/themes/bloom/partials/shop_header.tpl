{* Bloom — BR.F-style compact nav header
   Mobile: logo+name left, action icons right, contact row below
   Desktop: hidden (desktop_header.tpl takes over at 1024px) *}
<header class="shop-header">
    {* Top bar — brand + actions *}
    <div class="bloom-topbar">
        <a href="/" class="bloom-topbar-brand">
            {if $shop.shop_logo}
                <img src="{$shop.shop_logo}" alt="{$shop.store_name}" class="shop-logo">
            {else}
                <div class="shop-logo shop-logo-placeholder">{$shop.store_name|truncate:1:''|upper}</div>
            {/if}
            {if $shop.show_store_name|default:1}
            <span class="shop-name">{$shop.store_name}</span>
            {/if}
        </a>
        <div class="bloom-topbar-actions">
            <button type="button" class="bloom-action-btn" data-share-trigger aria-label="Share">
                <i class="fa-solid fa-arrow-up-from-bracket" style="font-size:14px"></i>
            </button>
            {if !empty($has_payments)}
            <button type="button" class="bloom-action-btn bloom-cart-btn cart-trigger" aria-label="Cart">
                <i class="fa-solid fa-bag-shopping" style="font-size:15px"></i>
                <span class="cart-badge" style="display:none">0</span>
            </button>
            {/if}
        </div>
    </div>

    {if $shop.show_tagline|default:1 && $shop.shop_tagline}
        <p class="shop-tagline">{$shop.shop_tagline}</p>
    {/if}

    {* Contact row — compact icon+text links *}
    {if $shop.contact_whatsapp || $shop.contact_email || $shop.contact_phone || $shop.social_instagram || $shop.social_tiktok || $shop.social_facebook}
    <nav class="bloom-contact-row">
        {if $shop.contact_whatsapp}
            <a href="https://wa.me/{$shop.contact_whatsapp}" target="_blank" rel="noopener">
                <i class="fa-brands fa-whatsapp"></i> WhatsApp
            </a>
        {/if}
        {if $shop.contact_email}
            <a href="mailto:{$shop.contact_email}">
                <i class="fa-solid fa-envelope"></i> Email
            </a>
        {/if}
        {if $shop.contact_phone}
            <a href="tel:{$shop.contact_phone}">
                <i class="fa-solid fa-phone"></i> Call
            </a>
        {/if}
        {if $shop.map_link}
            <a href="{$shop.map_link}" target="_blank" rel="noopener">
                <i class="fa-solid fa-location-dot"></i> Map
            </a>
        {/if}
        {if $shop.social_instagram}
            <a href="https://instagram.com/{$shop.social_instagram|escape}" target="_blank" rel="noopener">
                <i class="fa-brands fa-instagram"></i>
            </a>
        {/if}
        {if $shop.social_tiktok}
            <a href="https://tiktok.com/@{$shop.social_tiktok|escape}" target="_blank" rel="noopener">
                <i class="fa-brands fa-tiktok"></i>
            </a>
        {/if}
        {if $shop.social_facebook}
            <a href="https://facebook.com/{$shop.social_facebook|escape}" target="_blank" rel="noopener">
                <i class="fa-brands fa-facebook-f"></i>
            </a>
        {/if}
    </nav>
    {/if}
</header>
