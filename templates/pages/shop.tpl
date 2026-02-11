{extends file="layouts/base.tpl"}

{block name="body_class"}page-shop{/block}

{block name="body"}
<div class="shop-page">
    <div class="container">
        {include file="partials/shop_header.tpl"}

        {if $products|@count > 0}
        <div class="shop-search" id="shopSearch">
            <svg class="shop-search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" class="shop-search-input" id="searchInput" placeholder="Search products..." autocomplete="off">
            <button type="button" class="shop-search-clear" id="searchClear" aria-label="Clear search">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        {/if}

        {if $category_tree|@count > 0}
        {* Check if any category has an image *}
        {assign var="has_cat_images" value=false}
        {foreach $category_tree as $cat}
            {if $cat.image_url}{assign var="has_cat_images" value=true}{/if}
        {/foreach}

        {* Always render pill tabs — some themes use these *}
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

        {* Also render image cards if images exist — themes choose which to show *}
        {if $has_cat_images}
        <div class="category-cards-scroll" id="categoryCards">
            <button class="category-card active" data-category="all">
                <div class="category-card-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
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
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/></svg>
                        </div>
                    {/if}
                    <span class="category-card-name">{$parent.name|escape}</span>
                </button>
            {/foreach}
        </div>
        {/if}
        {/if}

        {if $products|@count > 0}
            <div class="product-toolbar">
                <div class="product-count" id="productCount">{$products|@count} {if $products|@count == 1}product{else}products{/if}</div>
            </div>
        {/if}

        <div class="search-empty" id="searchEmpty" style="display:none">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--color-text-muted)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="opacity:0.3; margin-bottom:12px"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <p class="search-empty-title">No results found</p>
            <p class="search-empty-hint">Try a different search term</p>
        </div>

        <section class="product-grid" id="catalogue">
            {foreach $products as $product}
                {include file="partials/product_card.tpl" product=$product currency_symbol=$currency_symbol}
            {foreachelse}
                <div class="empty-state">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--color-text-muted)" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" style="opacity:0.4; margin-bottom:16px">
                        <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/>
                    </svg>
                    <p><strong>No products yet</strong></p>
                    <p style="font-size:0.8125rem">This shop is setting up — check back soon!</p>
                </div>
            {/foreach}
        </section>

        <footer class="shop-footer">
            Powered by <a href="{$base_url}">{$app_name}</a>
        </footer>
    </div>

    {* Sticky CTA — WhatsApp or generic contact *}
    {if $shop.contact_whatsapp}
        <div class="sticky-cta">
            <a href="https://wa.me/{$shop.contact_whatsapp}?text=Hi! I'm interested in your products" class="btn btn-accent" target="_blank" rel="noopener">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="margin-right:8px"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 2C6.477 2 2 6.477 2 12c0 1.89.525 3.66 1.438 5.168L2 22l4.832-1.438A9.955 9.955 0 0 0 12 22c5.523 0 10-4.477 10-10S17.523 2 12 2zm0 18a8 8 0 0 1-4.243-1.214l-.293-.175-3.04.797.81-2.957-.192-.304A7.963 7.963 0 0 1 4 12a8 8 0 1 1 16 0 8 8 0 0 1-8 8z"/></svg>
                Chat on WhatsApp
            </a>
        </div>
    {elseif $shop.contact_phone}
        <div class="sticky-cta">
            <a href="tel:{$shop.contact_phone}" class="btn btn-accent">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:8px"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                Contact Seller
            </a>
        </div>
    {elseif $shop.contact_email}
        <div class="sticky-cta">
            <a href="mailto:{$shop.contact_email}" class="btn btn-accent">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:8px"><rect x="2" y="4" width="20" height="16" rx="2"/><polyline points="22,6 12,13 2,6"/></svg>
                Email Seller
            </a>
        </div>
    {/if}

    {include file="partials/share_sheet.tpl"}
</div>
{/block}
