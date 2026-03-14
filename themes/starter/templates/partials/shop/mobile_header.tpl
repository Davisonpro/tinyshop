{hook name="theme.mobile_header.before"}
<header class="mobile-header{if $shop.logo_alignment === 'centered'} shop-header-centered{/if}">
    <a href="/" class="mobile-header-profile">
        {if $shop.show_logo|default:1}
            {if $shop.shop_logo}
                <img src="{$shop.shop_logo}" alt="{$shop.store_name|escape}" class="shop-logo">
            {else}
                <div class="shop-logo shop-logo-placeholder">{$shop.store_name|truncate:1:''|upper}</div>
            {/if}
        {/if}
        {if $shop.show_store_name|default:1}
            <span class="shop-name">{$shop.store_name|escape}</span>
        {/if}
        {if $shop.show_tagline|default:1 && $shop.shop_tagline}
            <span class="shop-tagline">{$shop.shop_tagline|escape}</span>
        {/if}
    </a>
</header>
{hook name="theme.mobile_header.after"}
