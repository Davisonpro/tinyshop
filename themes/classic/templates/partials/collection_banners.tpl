{assign var="banner_cats" value=[]}
{foreach $category_tree as $cat}
    {if $cat.image_url && $banner_cats|@count < 2}
        {append var="banner_cats" value=$cat}
    {/if}
{/foreach}
{if $banner_cats|@count > 0}
<div class="collection-banners">
    {foreach $banner_cats as $bc}
    <a href="/collections/{$bc.slug|escape}" class="collection-banner">
        <img src="{$bc.image_url|escape}" alt="{$bc.name|escape}" loading="lazy">
        <div class="collection-banner-content">
            <h3 class="collection-banner-title">{$bc.name|escape}</h3>
            <span class="collection-banner-link">
                Shop Collection
                <svg xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 8 8" fill="currentColor"><path d="M0.861539 8L0 7.13846L5.90769 1.23077H0.615385V0H8V7.38462H6.76923V2.09231L0.861539 8Z"/></svg>
            </span>
        </div>
    </a>
    {/foreach}
</div>
{/if}
