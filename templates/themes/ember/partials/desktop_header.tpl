{* Ember theme — luxf-style editorial desktop header *}
<header class="desktop-header">
    <div class="desktop-header-inner">
        {* Brand: italic serif name only — no logo image, no tagline *}
        <a href="/" class="desktop-header-brand ember-brand-text">
            {if $shop.show_store_name|default:1}
                <span class="desktop-header-name">{$shop.store_name|default:$shop.name|escape}</span>
            {/if}
        </a>

        {* Nav links: contact channels as simple text links *}
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
            {if $shop.map_link}
                <a href="{$shop.map_link}" target="_blank" rel="noopener" class="desktop-header-link">Location</a>
            {/if}
        </nav>

        {* Icon buttons: circular outline — share + socials + cart *}
        <div class="desktop-header-actions">
            {if $shop.social_instagram}
                <a href="https://instagram.com/{$shop.social_instagram|escape}" target="_blank" rel="noopener" class="desktop-header-btn" aria-label="Instagram">
                    <i class="fa-brands fa-instagram"></i>
                </a>
            {/if}
            <button type="button" class="desktop-header-btn" data-share-trigger aria-label="Share this shop">
                <i class="fa-solid fa-arrow-up-from-bracket"></i>
            </button>
            {if !empty($has_payments)}
                <button type="button" class="desktop-header-btn desktop-header-cart cart-trigger" aria-label="Shopping cart">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <span class="cart-badge" style="display:none">0</span>
                </button>
            {/if}
        </div>
    </div>
</header>
