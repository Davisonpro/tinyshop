{hook name="theme.desktop_header.before"}
<header class="desktop-header clean-header{if $shop.logo_alignment === 'centered'} desktop-header-centered{/if}">
    <div class="container">
        <div class="desktop-header-inner">
            <a href="/" class="desktop-header-brand">
                {if $shop.shop_logo}
                    <img src="{$shop.shop_logo}" alt="{$shop.store_name|escape}" class="desktop-header-logo">
                {else}
                    <span class="desktop-header-logo desktop-header-logo-placeholder">{$shop.store_name|truncate:1:''|upper}</span>
                {/if}
                <div class="desktop-header-brand-text">
                    {if $shop.show_store_name|default:1}
                        <span class="desktop-header-name">{$shop.store_name|escape}</span>
                    {/if}
                    {if $shop.show_tagline|default:1 && $shop.shop_tagline}
                        <span class="desktop-header-tagline">{$shop.shop_tagline|escape}</span>
                    {/if}
                </div>
            </a>
            <nav class="desktop-header-nav">
                {hook name="theme.desktop_nav.before"}
                {if $shop.contact_whatsapp}
                    <a href="https://wa.me/{$shop.contact_whatsapp}" target="_blank" rel="noopener" class="desktop-header-link"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>
                {/if}
                {if $shop.contact_email}
                    <a href="mailto:{$shop.contact_email}" class="desktop-header-link"><i class="fa-regular fa-envelope"></i> Email</a>
                {/if}
                {if $shop.contact_phone}
                    <a href="tel:{$shop.contact_phone}" class="desktop-header-link"><i class="fa-solid fa-phone"></i> Call</a>
                {/if}
                {hook name="theme.desktop_nav.after"}
            </nav>
            <div class="desktop-header-actions">
                <button type="button" class="desktop-header-btn search-toggle" aria-label="Search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
                <button type="button" class="desktop-header-btn" data-share-trigger aria-label="Share this shop">
                    <i class="fa-solid fa-arrow-up-from-bracket"></i>
                </button>
                {if !empty($has_payments)}
                    <button type="button" class="desktop-header-btn desktop-header-cart cart-trigger" aria-label="Shopping cart">
                        <i class="fa-solid fa-bag-shopping"></i>
                        <span class="cart-badge cart-badge-hidden">0</span>
                    </button>
                {/if}
                {hook name="theme.desktop_actions.after"}
            </div>
        </div>
    </div>
</header>
{hook name="theme.desktop_header.after"}
