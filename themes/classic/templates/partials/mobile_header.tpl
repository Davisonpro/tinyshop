{hook name="theme.mobile_header.before"}
<header class="mobile-header{if $shop.logo_alignment === 'centered'} shop-header-centered{/if}">
    <div class="mobile-header-profile">
        {if $shop.shop_logo}
            <img src="{$shop.shop_logo}" alt="{$shop.store_name|escape}" class="shop-logo">
        {else}
            <div class="shop-logo shop-logo-placeholder">{$shop.store_name|truncate:1:''|upper}</div>
        {/if}
        {if $shop.show_store_name|default:1}
            <h1 class="shop-name">{$shop.store_name|escape}</h1>
        {/if}
        {if $shop.show_tagline|default:1 && $shop.shop_tagline}
            <p class="shop-tagline">{$shop.shop_tagline|escape}</p>
        {/if}
    </div>
    {if !empty($show_contact_links)}
    <div class="shop-contact">
        {if $shop.contact_whatsapp}
            <a href="https://wa.me/{$shop.contact_whatsapp}" target="_blank" rel="noopener">
                <i class="fa-brands fa-whatsapp"></i>
                <span>WhatsApp</span>
            </a>
        {/if}
        {if $shop.contact_email}
            <a href="mailto:{$shop.contact_email}">
                <i class="fa-regular fa-envelope"></i>
                <span>Email</span>
            </a>
        {/if}
        {if $shop.contact_phone}
            <a href="tel:{$shop.contact_phone}">
                <i class="fa-solid fa-phone"></i>
                <span>Call</span>
            </a>
        {/if}
        <button type="button" class="search-toggle" aria-label="Search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <span>Search</span>
        </button>
        {if !empty($has_payments)}
            <button type="button" class="cart-trigger" aria-label="Cart">
                <i class="fa-solid fa-bag-shopping"></i>
                <span>Cart</span>
                <span class="cart-badge cart-badge-hidden">0</span>
            </button>
        {/if}
    </div>
    {/if}
    {if !empty($show_social_links) && ($shop.social_instagram || $shop.social_tiktok || $shop.social_facebook)}
    <div class="shop-social">
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
</header>
{hook name="theme.mobile_header.after"}
