{* Trust Badges — dynamically rendered from theme options *}
{if !empty($theme_options.trust_badges_enabled) && !empty($theme_options.trust_badges_items)}
<div class="trust-badges hide-scrollbar">
    {foreach $theme_options.trust_badges_items as $badge}
    <div class="trust-badge">
        {if !empty($badge.icon)}
            <div class="trust-badge-icon">
                <i class="{$badge.icon|escape}"></i>
            </div>
        {/if}
        <div class="trust-badge-text">
            <span class="trust-badge-title">{$badge.title|escape}</span>
            {if !empty($badge.description)}
                <span class="trust-badge-desc">{$badge.description|escape}</span>
            {/if}
        </div>
    </div>
    {/foreach}
</div>
{/if}
