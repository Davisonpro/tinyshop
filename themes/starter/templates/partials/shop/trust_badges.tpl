{* Trust Badges — infinite scrolling marquee ticker *}
{if !empty($theme_options.trust_badges_enabled) && !empty($theme_options.trust_badges_items)}
<div class="trust-marquee" aria-label="Trust badges">
    <div class="trust-marquee-track">
        {* First set *}
        {foreach $theme_options.trust_badges_items as $badge}
        <div class="trust-marquee-item">
            {if !empty($badge.icon)}<i class="{$badge.icon|escape}"></i>{/if}
            <span>{$badge.title|escape}</span>
        </div>
        <span class="trust-marquee-sep" aria-hidden="true">&middot;</span>
        {/foreach}
        {* Duplicate for seamless loop *}
        {foreach $theme_options.trust_badges_items as $badge}
        <div class="trust-marquee-item">
            {if !empty($badge.icon)}<i class="{$badge.icon|escape}"></i>{/if}
            <span>{$badge.title|escape}</span>
        </div>
        <span class="trust-marquee-sep" aria-hidden="true">&middot;</span>
        {/foreach}
    </div>
</div>
{/if}