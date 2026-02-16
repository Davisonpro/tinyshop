<header class="shop-header">
    {* --- Decorative header: logo flanked by sparkles --- *}
    <div class="halloween-header-deco">
        <span class="halloween-sparkle halloween-sparkle--sm"><svg viewBox="0 0 24 24" fill="#AE7FF7"><path d="M12 0L14 10L24 12L14 14L12 24L10 14L0 12L10 10Z"/></svg></span>
        {if $shop.shop_logo}
            <img src="{$shop.shop_logo}" alt="{$shop.store_name}" class="shop-logo">
        {else}
            <div class="shop-logo shop-logo-placeholder">{$shop.store_name|truncate:1:''|upper}</div>
        {/if}
        <span class="halloween-sparkle halloween-sparkle--sm"><svg viewBox="0 0 24 24" fill="#CCE156"><path d="M12 0L14 10L24 12L14 14L12 24L10 14L0 12L10 10Z"/></svg></span>
    </div>

    {* --- Shop name with sparkle accents --- *}
    {if $shop.show_store_name|default:1}
    <div class="halloween-name-row">
        <span class="halloween-sparkle halloween-sparkle--md"><svg viewBox="0 0 24 24" fill="#FFFFFF" opacity="0.3"><path d="M12 0L14 10L24 12L14 14L12 24L10 14L0 12L10 10Z"/></svg></span>
        <h1 class="shop-name">{$shop.store_name}</h1>
        <span class="halloween-sparkle halloween-sparkle--md"><svg viewBox="0 0 24 24" fill="#FFFFFF" opacity="0.3"><path d="M12 0L14 10L24 12L14 14L12 24L10 14L0 12L10 10Z"/></svg></span>
    </div>
    {/if}
    {if $shop.show_tagline|default:1 && $shop.shop_tagline}
        <p class="shop-tagline">{$shop.shop_tagline}</p>
    {/if}

    {* --- Spooky eye divider --- *}
    <div class="halloween-eye" style="margin: 8px auto 12px; opacity: 0.25;">
        <svg width="40" height="20" viewBox="0 0 80 40" fill="none" stroke="#F5F5F5" stroke-width="2">
            <path d="M2 20C2 20 18 4 40 4C62 4 78 20 78 20C78 20 62 36 40 36C18 36 2 20 2 20Z"/>
            <ellipse cx="40" cy="20" rx="10" ry="14" fill="#F5F5F5"/>
            <ellipse cx="40" cy="20" rx="5" ry="10" fill="#000"/>
            {* Eyelash ticks *}
            <line x1="20" y1="8" x2="18" y2="2"/>
            <line x1="30" y1="5" x2="29" y2="0"/>
            <line x1="40" y1="4" x2="40" y2="0"/>
            <line x1="50" y1="5" x2="51" y2="0"/>
            <line x1="60" y1="8" x2="62" y2="2"/>
        </svg>
    </div>

    {if $shop.social_instagram || $shop.social_tiktok || $shop.social_facebook}
    <nav class="shop-social">
        {if $shop.social_instagram}
            <a href="https://instagram.com/{$shop.social_instagram|escape}" target="_blank" rel="noopener" title="Instagram">
                <i class="fa-brands fa-instagram" style="font-size:17px"></i>
            </a>
        {/if}
        {if $shop.social_tiktok}
            <a href="https://tiktok.com/@{$shop.social_tiktok|escape}" target="_blank" rel="noopener" title="TikTok">
                <i class="fa-brands fa-tiktok" style="font-size:17px"></i>
            </a>
        {/if}
        {if $shop.social_facebook}
            <a href="https://facebook.com/{$shop.social_facebook|escape}" target="_blank" rel="noopener" title="Facebook">
                <i class="fa-brands fa-facebook-f" style="font-size:17px"></i>
            </a>
        {/if}
    </nav>
    {/if}

    <nav class="shop-contact">
        {if $shop.contact_whatsapp}
            <a href="https://wa.me/{$shop.contact_whatsapp}" target="_blank" rel="noopener">
                <i class="fa-brands fa-whatsapp" style="font-size:14px"></i>
                <span>WhatsApp</span>
            </a>
        {/if}
        {if $shop.contact_email}
            <a href="mailto:{$shop.contact_email}">
                <i class="fa-solid fa-envelope" style="font-size:14px"></i>
                <span>Email</span>
            </a>
        {/if}
        {if $shop.contact_phone}
            <a href="tel:{$shop.contact_phone}">
                <i class="fa-solid fa-phone" style="font-size:14px"></i>
                <span>Call</span>
            </a>
        {/if}
        {if $shop.map_link}
            <a href="{$shop.map_link}" target="_blank" rel="noopener">
                <i class="fa-solid fa-location-dot" style="font-size:14px"></i>
                <span>Map</span>
            </a>
        {/if}
        <button type="button" class="shop-share-btn" data-share-trigger aria-label="Share this shop">
            <i class="fa-solid fa-share-from-square" style="font-size:14px"></i>
            <span>Share</span>
        </button>
        {if !empty($has_payments)}
            <button type="button" class="cart-trigger" aria-label="Shopping cart">
                <i class="fa-solid fa-cart-shopping" style="font-size:14px"></i>
                <span class="cart-badge" style="display:none">0</span>
            </button>
        {/if}
    </nav>
</header>
