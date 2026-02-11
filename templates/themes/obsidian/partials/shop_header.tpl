<header class="shop-header">
    <div class="shop-header-band">
        <div class="shop-header-top">
            {if $shop.shop_logo}
                <img src="{$shop.shop_logo}" alt="{$shop.store_name}" class="shop-logo">
            {else}
                <div class="shop-logo shop-logo-placeholder">{$shop.store_name|truncate:1:''|upper}</div>
            {/if}
            <div class="shop-header-actions">
                <button type="button" class="shop-search-toggle" id="searchToggle" aria-label="Search products">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </button>
                <button type="button" class="shop-share-btn" data-share-trigger aria-label="Share this shop">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg>
                </button>
            </div>
        </div>

        <h1 class="shop-name">{$shop.store_name|default:$shop.name}</h1>
        {if $shop.shop_tagline}
            <p class="shop-tagline">{$shop.shop_tagline}</p>
        {/if}

        <nav class="shop-contact">
            {if $shop.contact_whatsapp}
                <a href="https://wa.me/{$shop.contact_whatsapp}" target="_blank" rel="noopener"><span>WhatsApp</span></a>
            {/if}
            {if $shop.contact_email}
                <a href="mailto:{$shop.contact_email}"><span>Email</span></a>
            {/if}
            {if $shop.contact_phone}
                <a href="tel:{$shop.contact_phone}"><span>Call</span></a>
            {/if}
            {if $shop.map_link}
                <a href="{$shop.map_link}" target="_blank" rel="noopener"><span>Map</span></a>
            {/if}
        </nav>
    </div>

    {if $shop.social_instagram || $shop.social_tiktok || $shop.social_facebook}
    <nav class="shop-social">
        {if $shop.social_instagram}
            <a href="https://instagram.com/{$shop.social_instagram|escape}" target="_blank" rel="noopener" title="Instagram">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
            </a>
        {/if}
        {if $shop.social_tiktok}
            <a href="https://tiktok.com/@{$shop.social_tiktok|escape}" target="_blank" rel="noopener" title="TikTok">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1 0-5.78 2.92 2.92 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 3 15.57 6.33 6.33 0 0 0 9.37 22a6.33 6.33 0 0 0 6.37-6.22V9.4a8.16 8.16 0 0 0 3.85.96V7.04a4.85 4.85 0 0 1-0-.35z"/></svg>
            </a>
        {/if}
        {if $shop.social_facebook}
            <a href="https://facebook.com/{$shop.social_facebook|escape}" target="_blank" rel="noopener" title="Facebook">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
            </a>
        {/if}
    </nav>
    {/if}
</header>
