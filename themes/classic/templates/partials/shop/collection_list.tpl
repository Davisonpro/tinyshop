{* Reusable collection list — expects $categories array with children *}
<div class="collection-list">
    {foreach $categories as $cat}
    {if !empty($cat.children)}
    <div class="collection-group">
        <a href="/collections/{$cat.slug|escape}" class="collection-item collection-item--parent">
            <span class="collection-item-name">{$cat.name|escape}</span>
            <span class="collection-item-meta">
                <span class="collection-item-count">{$cat.product_count}</span>
                <svg class="collection-item-arrow" width="6" height="10" viewBox="0 0 6 10" fill="currentColor"><path d="M1.4 10L0 8.6 3.6 5 0 1.4 1.4 0l5 5-5 5z"/></svg>
            </span>
        </a>
        <div class="collection-children">
            {foreach $cat.children as $child}
            <a href="/collections/{$child.slug|escape}" class="collection-item collection-item--child">
                <span class="collection-item-name">{$child.name|escape}</span>
                <svg class="collection-item-arrow" width="6" height="10" viewBox="0 0 6 10" fill="currentColor"><path d="M1.4 10L0 8.6 3.6 5 0 1.4 1.4 0l5 5-5 5z"/></svg>
            </a>
            {/foreach}
        </div>
    </div>
    {else}
    <a href="/collections/{$cat.slug|escape}" class="collection-item">
        <span class="collection-item-name">{$cat.name|escape}</span>
        <span class="collection-item-meta">
            <span class="collection-item-count">{$cat.product_count}</span>
            <svg class="collection-item-arrow" width="6" height="10" viewBox="0 0 6 10" fill="currentColor"><path d="M1.4 10L0 8.6 3.6 5 0 1.4 1.4 0l5 5-5 5z"/></svg>
        </span>
    </a>
    {/if}
    {/foreach}
</div>
