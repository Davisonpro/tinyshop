<header class="shop-header{if $shop.logo_alignment === 'centered'} shop-header-centered{/if}">
    <div class="shop-header-profile">
        {if $shop.show_logo|default:1}
            {if $shop.shop_logo}
                <img src="{$shop.shop_logo}" alt="{$shop.store_name}" class="shop-logo">
            {else}
                <div class="shop-logo shop-logo-placeholder">{$shop.store_name|truncate:1:''|upper}</div>
            {/if}
        {/if}
        <div class="shop-header-info">
            {if $shop.show_store_name|default:1}
                <h1 class="shop-name">{$shop.store_name}</h1>
            {/if}
            {if $shop.show_tagline|default:1 && $shop.shop_tagline}
                <p class="shop-tagline">{$shop.shop_tagline}</p>
            {/if}
        </div>
    </div>

    <nav class="shop-contact">
        {if $shop.contact_whatsapp}
            <a href="https://wa.me/{$shop.contact_whatsapp}" target="_blank" rel="noopener">
                <i class="fa-brands fa-whatsapp"></i>
                <span>WhatsApp</span>
            </a>
        {/if}
        {if $shop.contact_email}
            <a href="mailto:{$shop.contact_email}">
                <i class="fa-solid fa-envelope"></i>
                <span>Email</span>
            </a>
        {/if}
        {if $shop.contact_phone}
            <a href="tel:{$shop.contact_phone}">
                <i class="fa-solid fa-phone"></i>
                <span>Call</span>
            </a>
        {/if}
        {if $shop.map_link}
            <a href="{$shop.map_link}" target="_blank" rel="noopener">
                <i class="fa-solid fa-location-dot"></i>
                <span>Map</span>
            </a>
        {/if}
        <button type="button" class="shop-share-btn" data-share-trigger aria-label="Share this shop">
            <i class="fa-solid fa-arrow-up-from-bracket"></i>
            <span>Share</span>
        </button>
        {if !empty($has_payments)}
            <button type="button" class="cart-trigger" aria-label="Shopping cart">
                <i class="fa-solid fa-cart-shopping"></i>
                <span class="cart-badge" style="display:none">0</span>
            </button>
        {/if}
    </nav>

    {if $shop.social_instagram || $shop.social_tiktok || $shop.social_facebook}
    <nav class="shop-social">
        {if $shop.social_instagram}
            <a href="https://instagram.com/{$shop.social_instagram|escape}" target="_blank" rel="noopener" title="Instagram">
                <i class="fa-brands fa-instagram"></i>
            </a>
        {/if}
        {if $shop.social_tiktok}
            <a href="https://tiktok.com/@{$shop.social_tiktok|escape}" target="_blank" rel="noopener" title="TikTok">
                <i class="fa-brands fa-tiktok"></i>
            </a>
        {/if}
        {if $shop.social_facebook}
            <a href="https://facebook.com/{$shop.social_facebook|escape}" target="_blank" rel="noopener" title="Facebook">
                <i class="fa-brands fa-facebook-f"></i>
            </a>
        {/if}
    </nav>
    {/if}
</header>
