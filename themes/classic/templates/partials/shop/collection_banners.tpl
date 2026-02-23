{if !empty($theme_options.collection_banners_enabled) && !empty($theme_options.collection_banners_items)}
{assign var="cb_layout" value=$theme_options.collection_banners_layout|default:'2-col'}
{assign var="cb_size" value=$theme_options.collection_banners_size|default:'medium'}
{assign var="cb_pos" value=$theme_options.collection_banners_text_position|default:'bottom-left'}
<div class="collection-banners cb-{$cb_layout|escape} cb-size-{$cb_size|escape} cb-pos-{$cb_pos|escape}">
    {foreach $theme_options.collection_banners_items as $banner}
    {if !empty($banner.image)}
    <a href="{$banner.link_url|default:'#'|escape}" class="collection-banner">
        <img src="{$banner.image|escape}" alt="{$banner.title|escape}" loading="lazy">
        <div class="collection-banner-content">
            {if $banner.title}<h3 class="collection-banner-title">{$banner.title|escape}</h3>{/if}
            {if $banner.description}<p class="collection-banner-desc">{$banner.description|escape}</p>{/if}
            {if $banner.link_text}
            <span class="collection-banner-link">
                {$banner.link_text|escape}
                <svg xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 8 8" fill="currentColor"><path d="M0.861539 8L0 7.13846L5.90769 1.23077H0.615385V0H8V7.38462H6.76923V2.09231L0.861539 8Z"/></svg>
            </span>
            {/if}
        </div>
    </a>
    {/if}
    {/foreach}
</div>
{/if}
