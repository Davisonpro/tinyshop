{* Desktop-only header — hidden on mobile via CSS, shown at 1024px+ *}
<header class="desktop-header{if $shop.logo_alignment === 'centered'} desktop-header-centered{/if}">
    <div class="desktop-header-inner">
        <a href="/" class="desktop-header-brand">
            {if $shop.show_logo|default:1}
                {if $shop.shop_logo}
                    <img src="{$shop.shop_logo}" alt="{$shop.store_name|escape}" class="desktop-header-logo">
                {else}
                    <span class="desktop-header-logo desktop-header-logo-placeholder">{$shop.store_name|truncate:1:''|upper}</span>
                {/if}
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
            {if $shop.contact_whatsapp}
                <a href="https://wa.me/{$shop.contact_whatsapp}" target="_blank" rel="noopener" class="desktop-header-link">WhatsApp</a>
            {/if}
            {if $shop.contact_email}
                <a href="mailto:{$shop.contact_email}" class="desktop-header-link">Email</a>
            {/if}
            {if $shop.contact_phone}
                <a href="tel:{$shop.contact_phone}" class="desktop-header-link">Call</a>
            {/if}
        </nav>
        <div class="desktop-header-actions">
            <button type="button" class="desktop-header-btn" data-share-trigger aria-label="Share this shop">
                <i class="fa-solid fa-arrow-up-from-bracket"></i>
            </button>
            <a href="/account" class="desktop-header-btn" aria-label="My account">
                <i class="fa-solid {if !empty($customer_logged_in)}fa-user-check{else}fa-user{/if}"></i>
            </a>
            {if !empty($has_payments)}
                <button type="button" class="desktop-header-btn desktop-header-cart cart-trigger" aria-label="Shopping cart">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <span class="cart-badge" style="display:none">0</span>
                </button>
            {/if}
        </div>
    </div>
</header>
