{* Category navigation — pill tabs + image cards. Themes choose which to show via CSS. *}
{if $category_tree|@count > 0}
{assign var="has_cat_images" value=false}
{foreach $category_tree as $cat}
    {if $cat.image_url}{assign var="has_cat_images" value=true}{/if}
{/foreach}

{* Pill tabs — always rendered *}
<nav class="category-tabs{if !$has_cat_images} category-tabs-only{/if}" id="categoryTabs">
    <button class="category-tab active" data-category="all">All</button>
    {foreach $category_tree as $parent}
        {assign var="childIds" value=$parent.id}
        {if !empty($parent.children)}
            {foreach $parent.children as $child}
                {assign var="childIds" value="`$childIds`,`$child.id`"}
            {/foreach}
        {/if}
        <button class="category-tab" data-category="{$childIds}">{$parent.name|escape}</button>
    {/foreach}
</nav>

{* Image cards — rendered if images exist, themes hide via CSS if not wanted *}
{if $has_cat_images}
<div class="category-cards-scroll" id="categoryCards">
    <button class="category-card active" data-category="all">
        <div class="category-card-icon">
            <i class="fa-solid fa-grid-2" style="font-size:20px"></i>
        </div>
        <span class="category-card-name">All</span>
    </button>
    {foreach $category_tree as $parent}
        {assign var="childIds" value=$parent.id}
        {if !empty($parent.children)}
            {foreach $parent.children as $child}
                {assign var="childIds" value="`$childIds`,`$child.id`"}
            {/foreach}
        {/if}
        <button class="category-card" data-category="{$childIds}">
            {if $parent.image_url}
                <img src="{$parent.image_url|escape}" alt="{$parent.name|escape}" loading="lazy" onload="this.classList.add('loaded')">
            {else}
                <div class="category-card-icon">
                    <i class="fa-solid fa-circle" style="font-size:20px"></i>
                </div>
            {/if}
            <span class="category-card-name">{$parent.name|escape}</span>
        </button>
    {/foreach}
</div>
{/if}
{/if}
