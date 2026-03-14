{if $category_tree|@count > 0}
{* Check if any category has an image *}
{assign var="has_cat_images" value=false}
{foreach $category_tree as $cat}{if $cat.image_url}{assign var="has_cat_images" value=true}{break}{/if}{/foreach}
<section class="category-band{if !$has_cat_images} category-band--tags{/if}">
    <div class="section-header">
        <h2 class="section-title">Categories</h2>
    </div>
    {if $has_cat_images}
    <div class="category-cards-wrapper" data-scroll-container>
        <div class="category-cards-track hide-scrollbar" data-scroll-track>
            {foreach $category_tree as $cat}
            <a href="/collections/{$cat.slug|escape}" class="category-card">
                <div class="category-card-img">
                    {if $cat.image_url}
                        <img src="{$cat.image_url|escape}" alt="{$cat.name|escape}" loading="lazy">
                    {/if}
                </div>
                <div class="category-card-info">
                    <span class="category-card-name">{$cat.name|escape}</span>
                    <svg class="category-card-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17L17 7M17 7H7M17 7V17"/></svg>
                </div>
            </a>
            {/foreach}
        </div>
        <button class="scroll-arrow scroll-arrow-prev" data-scroll-prev aria-label="Scroll left">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
        </button>
        <button class="scroll-arrow scroll-arrow-next" data-scroll-next aria-label="Scroll right">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
        </button>
    </div>
    {else}
    <div class="category-tags-wrap">
        {foreach $category_tree as $cat}
        <a href="/collections/{$cat.slug|escape}" class="category-tag">{$cat.name|escape}</a>
        {/foreach}
    </div>
    {/if}
</section>
{/if}