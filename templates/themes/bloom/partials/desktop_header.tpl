{* Bloom — BR.F-style desktop header
   Top bar: logo left, search center, cart+icons right
   Bottom row: contact/social nav links with subtle border *}
<header class="desktop-header">
    <div class="desktop-header-inner">
        {* Brand — logo + name *}
        <a href="/" class="desktop-header-brand">
            {if $shop.shop_logo}
                <img src="{$shop.shop_logo}" alt="{$shop.store_name|escape}" class="desktop-header-logo">
            {else}
                <span class="desktop-header-logo desktop-header-logo-placeholder">{$shop.store_name|truncate:1:''|upper}</span>
            {/if}
            {if $shop.show_store_name|default:1}
                <span class="desktop-header-name">{$shop.store_name|escape}</span>
            {/if}
        </a>

        {* Search — centered input *}
        <div class="bloom-desktop-search">
            <i class="fa-solid fa-magnifying-glass bloom-desktop-search-icon" style="font-size:14px"></i>
            <input type="text" class="bloom-desktop-search-input" placeholder="Search products..." id="bloomDesktopSearch" autocomplete="off">
        </div>

        {* Actions — icon groups with labels (BR.F style) *}
        <div class="bloom-desktop-actions">
            {if !empty($has_payments)}
                <button type="button" class="bloom-desktop-action cart-trigger" aria-label="Shopping cart">
                    <span class="bloom-desktop-action-icon">
                        <i class="fa-solid fa-bag-shopping"></i>
                        <span class="cart-badge" style="display:none">0</span>
                    </span>
                    <span class="bloom-desktop-action-label">Cart</span>
                </button>
            {/if}
        </div>
    </div>

    {* Nav row — contact + social links *}
    {if $shop.contact_whatsapp || $shop.contact_email || $shop.contact_phone || $shop.social_instagram || $shop.social_tiktok || $shop.social_facebook || $shop.map_link}
    <div class="bloom-desktop-nav">
        <div class="bloom-desktop-nav-inner">
            <div class="bloom-desktop-nav-links">
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
            </div>
            <div class="bloom-desktop-nav-social">
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
    </div>
    {/if}
</header>
