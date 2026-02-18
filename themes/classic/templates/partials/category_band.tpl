{if $category_tree|@count > 0}
{* Check if any category has an image *}
{assign var="has_cat_images" value=false}
{foreach $category_tree as $cat}{if $cat.image_url}{assign var="has_cat_images" value=true}{break}{/if}{/foreach}
<section class="category-band{if !$has_cat_images} category-band--pills{/if}">
    <div class="section-header">
        <h2 class="section-title">Shop by category</h2>
        <a href="/collections" class="section-link">See all</a>
    </div>
    <div class="category-band-wrapper" data-scroll-container>
        <div class="category-band-track hide-scrollbar" data-scroll-track>
            {foreach $category_tree as $cat}
            {if $has_cat_images}
            <a href="/collections/{$cat.slug|escape}" class="category-band-item">
                <div class="category-band-circle">
                    {if $cat.image_url}
                        <img src="{$cat.image_url|escape}" alt="{$cat.name|escape}" loading="lazy">
                    {else}
                        <i class="fa-solid fa-tag category-band-icon"></i>
                    {/if}
                </div>
                <span class="category-band-name">{$cat.name|escape}</span>
            </a>
            {else}
            <a href="/collections/{$cat.slug|escape}" class="category-pill">{$cat.name|escape}</a>
            {/if}
            {/foreach}
        </div>
        <button class="scroll-arrow scroll-arrow-prev" data-scroll-prev aria-label="Scroll left">
            <svg width="11" height="11" viewBox="0 0 7 11" fill="currentColor"><path d="M5.5 11L0 5.5L5.5 0L6.476.976 1.953 5.5l4.523 4.524L5.5 11Z"/></svg>
        </button>
        <button class="scroll-arrow scroll-arrow-next" data-scroll-next aria-label="Scroll right">
            <svg width="11" height="11" viewBox="0 0 7 11" fill="currentColor"><path d="M1.5 11L7 5.5 1.5 0 .524.976 5.047 5.5.524 10.024 1.5 11Z"/></svg>
        </button>
    </div>
</section>
{/if}
