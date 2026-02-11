{extends file="layouts/base.tpl"}

{block name="body_class"}page-shop page-product{/block}

{block name="body"}
<div class="product-page">
    <div class="container">
        {* Full-bleed Image Gallery *}
        <div class="product-gallery" id="productGallery">
            {* Floating nav: back + share *}
            <div class="product-gallery-nav">
                <a href="/" class="product-nav-btn" aria-label="Back to {$shop.store_name|default:$shop.name|escape}">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                </a>
                <button type="button" class="product-nav-btn" data-share-trigger aria-label="Share">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg>
                </button>
            </div>

            {if $images|@count > 0}
                <div class="product-gallery-track" id="galleryTrack">
                    {foreach $images as $img}
                        <div class="product-gallery-slide">
                            <img src="{$img.image_url|escape}" alt="{$product.name|escape}" loading="{if $img@first}eager{else}lazy{/if}" onload="this.classList.add('loaded')">
                        </div>
                    {/foreach}
                </div>
                {if $images|@count > 1}
                <div class="product-gallery-dots" id="galleryDots">
                    {foreach $images as $img}
                        <span class="gallery-dot{if $img@first} active{/if}" data-index="{$img@index}"></span>
                    {/foreach}
                </div>
                {/if}
            {elseif $product.image_url}
                <div class="product-gallery-track">
                    <div class="product-gallery-slide">
                        <img src="{$product.image_url|escape}" alt="{$product.name|escape}" onload="this.classList.add('loaded')">
                    </div>
                </div>
            {else}
                <div class="product-gallery-empty">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#D1D5DB" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                </div>
            {/if}

            {* Badge overlay *}
            {if $product.is_sold}
                <span class="product-detail-badge badge-sold">Sold Out</span>
            {elseif $product.is_featured}
                <span class="product-detail-badge badge-featured">Featured</span>
            {/if}
        </div>

        {* Product Info *}
        <div class="product-info">
            <h1 class="product-info-name">{$product.name|escape}</h1>

            <div class="product-info-price">
                {if $product.compare_price && $product.compare_price > $product.price && !$product.is_sold}
                    <span class="price-compare">{$currency_symbol}{$product.compare_price|format_price}</span>
                {/if}
                <span class="price-current{if $product.compare_price && $product.compare_price > $product.price && !$product.is_sold} price-sale{/if}">{$currency_symbol}{$product.price|format_price}</span>
                {if $product.compare_price && $product.compare_price > $product.price && !$product.is_sold}
                    {math equation="round((1 - x/y) * 100)" x=$product.price y=$product.compare_price assign="discount_pct"}
                    <span class="price-discount-badge">-{$discount_pct}%</span>
                {/if}
            </div>

            {if $product.description}
                <div class="product-info-desc" id="productDesc">{$product.description|escape|nl2br}</div>
                <button type="button" class="product-desc-toggle" id="descToggle" style="display:none">Read more</button>
            {/if}
        </div>

        {* Variations *}
        {if !empty($product.variations_data)}
        <div class="product-variations">
            {foreach $product.variations_data as $vgroup}
                <div class="product-variation-group">
                    <div class="product-variation-label">{$vgroup.name|escape}</div>
                    <div class="product-variation-options">
                        {foreach $vgroup.options as $opt}
                            {if is_array($opt)}
                                <span class="product-variation-option">
                                    {$opt.value|escape}
                                    {if !empty($opt.price)}
                                        <small>{$currency_symbol}{$opt.price|format_price}</small>
                                    {/if}
                                </span>
                            {else}
                                <span class="product-variation-option">{$opt|escape}</span>
                            {/if}
                        {/foreach}
                    </div>
                </div>
            {/foreach}
        </div>
        {/if}
    </div>

    {* More Products *}
    {if $more_products|@count > 0}
    <div class="container">
        <div class="more-products">
            <h2 class="more-products-title">{if $shop_theme == 'monaco'}From the Collection{elseif $shop_theme == 'obsidian'}More Drops{elseif $shop_theme == 'ember'}You Might Also Love{elseif $shop_theme == 'bloom'}More Picks for You{elseif $shop_theme == 'ivory'}More{elseif $shop_theme == 'volt'}Related{else}More from this shop{/if}</h2>
            <div class="more-products-scroll">
                {foreach $more_products as $mp}
                    <a href="/{$mp.slug|default:$mp.id}" class="more-product-card">
                        <div class="more-product-img">
                            {if $mp.image_url}
                                <img src="{$mp.image_url|escape}" alt="{$mp.name|escape}" loading="lazy" onload="this.classList.add('loaded')">
                            {else}
                                <img src="/public/img/placeholder.svg" alt="{$mp.name|escape}" loading="lazy" onload="this.classList.add('loaded')">
                            {/if}
                        </div>
                        <div class="more-product-name">{$mp.name|escape}</div>
                        <div class="more-product-price">{$currency_symbol}{$mp.price|format_price}</div>
                    </a>
                {/foreach}
            </div>
        </div>
    </div>
    {/if}

    {* Sticky CTA — themed via partials/product_cta.tpl override *}
    {include file="partials/product_cta.tpl"}

    {include file="partials/share_sheet.tpl"}
</div>

{* JSON-LD Structured Data *}
<script type="application/ld+json">
{literal}{{/literal}
    "@context": "https://schema.org",
    "@type": "Product",
    "name": "{$product.name|escape:'javascript'}",
    {if $product.description}"description": "{$product.description|escape:'javascript'}",{/if}
    {if $product.image_url}"image": "{$product.image_url|escape:'javascript'}",{/if}
    "offers": {literal}{{/literal}
        "@type": "Offer",
        "price": "{$product.price|string_format:"%.2f"}",
        "priceCurrency": "{$currency|escape:'javascript'}",
        "availability": "https://schema.org/{if $product.is_sold}SoldOut{else}InStock{/if}"
    {literal}}{/literal}
{literal}}{/literal}
</script>
{/block}

{block name="page_scripts"}
<script>
(function() {
    // Read more toggle for long descriptions
    var desc = document.getElementById('productDesc');
    var toggle = document.getElementById('descToggle');
    if (desc && toggle) {
        // Show toggle only if text overflows
        if (desc.scrollHeight > desc.offsetHeight + 4) {
            toggle.style.display = '';
            toggle.addEventListener('click', function() {
                var expanded = desc.classList.toggle('expanded');
                toggle.textContent = expanded ? 'Show less' : 'Read more';
            });
        }
    }

    var track = document.getElementById('galleryTrack');
    var dots = document.getElementById('galleryDots');
    if (!track) return;

    var slides = track.querySelectorAll('.product-gallery-slide');
    var allDots = dots ? dots.querySelectorAll('.gallery-dot') : [];
    if (slides.length < 2) return;

    var current = 0;

    function updateDots(idx) {
        allDots.forEach(function(d, i) {
            d.classList.toggle('active', i === idx);
        });
    }

    // Scroll-snap based: listen to scroll end
    var scrollTimer;
    track.addEventListener('scroll', function() {
        clearTimeout(scrollTimer);
        scrollTimer = setTimeout(function() {
            var slideWidth = track.offsetWidth;
            current = Math.round(track.scrollLeft / slideWidth);
            updateDots(current);
        }, 50);
    });

    // Tap dots to navigate
    if (dots) {
        dots.addEventListener('click', function(e) {
            var dot = e.target.closest('.gallery-dot');
            if (!dot) return;
            var idx = parseInt(dot.dataset.index, 10);
            track.scrollTo({ left: idx * track.offsetWidth, behavior: 'smooth' });
        });
    }
})();
</script>
{/block}
