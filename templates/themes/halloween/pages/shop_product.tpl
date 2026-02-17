{extends file="layouts/base.tpl"}

{block name="body_class"}page-shop page-product{/block}

{block name="body"}
{include file="partials/desktop_header.tpl"}
<div class="product-page">
    <div class="container">
        {* Full-bleed Image Gallery *}
        <div class="product-gallery" id="productGallery">
            {* Floating nav: back + share *}
            <div class="product-gallery-nav">
                <a href="/" class="product-nav-btn" aria-label="Back to {$shop.store_name|escape}">
                    <i class="fa-solid fa-chevron-left" style="font-size:20px"></i>
                </a>
                <button type="button" class="product-nav-btn" data-share-trigger aria-label="Share">
                    <i class="fa-solid fa-share-from-square" style="font-size:18px"></i>
                </button>
                {if !empty($has_payments)}
                <button type="button" class="product-nav-btn cart-trigger" aria-label="Shopping cart">
                    <i class="fa-solid fa-cart-shopping" style="font-size:18px"></i>
                    <span class="cart-badge" style="display:none">0</span>
                </button>
                {/if}
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
                <div class="product-gallery-thumbs" id="galleryThumbs">
                    {foreach $images as $img}
                        <button type="button" class="gallery-thumb{if $img@first} active{/if}" data-index="{$img@index}">
                            <img src="{$img.image_url|escape}" alt="" loading="lazy">
                        </button>
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
                    <i class="fa-solid fa-image" style="font-size:48px;color:#D1D5DB"></i>
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
            {if $product.category_name}
                <span class="product-info-category">{$product.category_name|escape}</span>
            {/if}
            <h1 class="product-info-name">{$product.name|escape}</h1>

            <div class="product-info-price" id="productPriceArea">
                {if $product.compare_price && $product.compare_price > $product.price && !$product.is_sold}
                    <span class="price-compare" id="productComparePrice">{$currency_symbol}{$product.compare_price|format_price}</span>
                {/if}
                <span class="price-current{if $product.compare_price && $product.compare_price > $product.price && !$product.is_sold} price-sale{/if}" id="productPriceCurrent">{$currency_symbol}{$product.price|format_price}</span>
                {if $product.compare_price && $product.compare_price > $product.price && !$product.is_sold}
                    {math equation="round((1 - x/y) * 100)" x=$product.price y=$product.compare_price assign="discount_pct"}
                    <span class="price-discount-badge" id="productDiscountBadge">-{$discount_pct}%</span>
                {/if}
            </div>

            {if !$product.is_sold && $product.stock_quantity !== null}
                {if $product.stock_quantity > 0 && $product.stock_quantity <= 5}
                    <div class="stock-badge stock-badge-low">Only {$product.stock_quantity} left in stock</div>
                {elseif $product.stock_quantity > 5}
                    <div class="stock-badge">In Stock</div>
                {/if}
            {/if}

            {if $product.description}
                <div class="product-info-desc" id="productDesc">{$product.description nofilter}</div>
                <button type="button" class="product-desc-toggle" id="descToggle" style="display:none">Read more</button>
            {/if}
        </div>

        {include file="partials/product_whatsapp.tpl"}

        {* Variations *}
        {if !empty($product.variations_data)}
        <div class="product-variations" id="productVariations">
            {foreach $product.variations_data as $vgroup}
                <div class="product-variation-group" data-group="{$vgroup@index}">
                    <div class="product-variation-label">
                        {$vgroup.name|escape}
                        <span class="variation-selected-value" id="varSelected{$vgroup@index}"></span>
                    </div>
                    <div class="product-variation-options">
                        {foreach $vgroup.options as $opt}
                            {if is_array($opt)}
                                <button type="button" class="product-variation-option"
                                    data-value="{$opt.value|escape}"
                                    {if !empty($opt.price)}data-price="{$opt.price}"{/if}>
                                    {$opt.value|escape}
                                    {if !empty($opt.price)}
                                        {math equation="x-y" x=$opt.price y=$product.price assign="price_delta"}
                                        {if $price_delta > 0}
                                            <span class="variation-price-delta">+{$currency_symbol}{$price_delta|format_price}</span>
                                        {elseif $price_delta < 0}
                                            {math equation="0-x" x=$price_delta assign="abs_delta"}
                                            <span class="variation-price-delta variation-price-less">&minus;{$currency_symbol}{$abs_delta|format_price}</span>
                                        {/if}
                                    {/if}
                                </button>
                            {else}
                                <button type="button" class="product-variation-option" data-value="{$opt|escape}">
                                    {$opt|escape}
                                </button>
                            {/if}
                        {/foreach}
                    </div>
                </div>
            {/foreach}
        </div>
        <script>
        window._productBasePrice = {$product.price};
        window._productComparePrice = {if $product.compare_price}{$product.compare_price}{else}0{/if};
        window._hasVariations = true;
        </script>
        {/if}

        {include file="partials/product_cta.tpl"}

        {* Inline share buttons — desktop only via CSS *}
        <div class="product-share-inline">
            <span class="product-share-label">Share</span>
            <a href="https://wa.me/?text={$product.name|escape:'url'}%20{$smarty.server.REQUEST_URI|escape:'url'}" target="_blank" rel="noopener" class="product-share-btn" aria-label="Share on WhatsApp">
                <i class="fa-brands fa-whatsapp" style="font-size:18px"></i>
            </a>
            <a href="https://www.facebook.com/sharer/sharer.php?u={$smarty.server.REQUEST_URI|escape:'url'}" target="_blank" rel="noopener" class="product-share-btn" aria-label="Share on Facebook">
                <i class="fa-brands fa-facebook-f" style="font-size:18px"></i>
            </a>
            <a href="https://twitter.com/intent/tweet?text={$product.name|escape:'url'}&url={$smarty.server.REQUEST_URI|escape:'url'}" target="_blank" rel="noopener" class="product-share-btn" aria-label="Share on X">
                <i class="fa-brands fa-x-twitter" style="font-size:16px"></i>
            </a>
            <button type="button" class="product-share-btn" data-share-trigger aria-label="More sharing options">
                <i class="fa-solid fa-share-from-square" style="font-size:18px"></i>
            </button>
        </div>
    </div>

    {* More Products *}
    {if $more_products|@count > 0}
    <div class="container">
        <div class="more-products">
            <h2 class="more-products-title">
                <span class="halloween-sparkle halloween-sparkle--sm" style="margin-right:8px"><svg viewBox="0 0 24 24" fill="#AE7FF7"><path d="M12 0L14 10L24 12L14 14L12 24L10 14L0 12L10 10Z"/></svg></span>
                You Might Like
                <span class="halloween-sparkle halloween-sparkle--sm" style="margin-left:8px"><svg viewBox="0 0 24 24" fill="#CCE156"><path d="M12 0L14 10L24 12L14 14L12 24L10 14L0 12L10 10Z"/></svg></span>
            </h2>
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

    {include file="partials/share_sheet.tpl"}
    {include file="partials/cart_drawer.tpl"}
</div>
{include file="partials/desktop_footer.tpl"}

{* JSON-LD Structured Data *}
<script type="application/ld+json">
{ldelim}
    "@context": "https://schema.org",
    "@graph": [
        {ldelim}
            "@type": "Product",
            "name": "{$product.name|escape:'javascript'}",
            "url": "{$base_url}/{$product.slug|default:$product.id}",
            "sku": "PROD-{$product.id}"
            {if $product.description},"description": "{$product.description|strip_tags|escape:'javascript'}"{/if}
            {if $product.category_name},"category": "{$product.category_name|escape:'javascript'}"{/if}
            ,"brand": {ldelim}
                "@type": "Organization",
                "name": "{$shop.store_name|escape:'javascript'}"
            {rdelim}
            ,"image": [{if $images|@count > 0}{foreach $images as $img}"{$img.image_url|escape:'javascript'}"{if !$img@last},{/if}{/foreach}{elseif $product.image_url}"{$product.image_url|escape:'javascript'}"{/if}]
            ,"offers": {ldelim}
                "@type": "Offer",
                "url": "{$base_url}/{$product.slug|default:$product.id}",
                "price": "{$product.price|string_format:"%.2f"}",
                "priceCurrency": "{$currency|escape:'javascript'}",
                "availability": "https://schema.org/{if $product.is_sold}SoldOut{elseif $product.stock_quantity !== null && $product.stock_quantity == 0}OutOfStock{elseif $product.stock_quantity !== null && $product.stock_quantity <= 5}LimitedAvailability{else}InStock{/if}",
                "itemCondition": "https://schema.org/NewCondition",
                "seller": {ldelim}
                    "@type": "Organization",
                    "name": "{$shop.store_name|escape:'javascript'}"
                {rdelim}
                {if $product.compare_price && $product.compare_price > $product.price}
                ,"priceValidUntil": "{$smarty.now|date_format:'%Y-12-31'}"
                {/if}
            {rdelim}
        {rdelim},
        {ldelim}
            "@type": "BreadcrumbList",
            "itemListElement": [
                {ldelim}
                    "@type": "ListItem",
                    "position": 1,
                    "name": "{$shop.store_name|escape:'javascript'}",
                    "item": "{$base_url}/"
                {rdelim}
                {if $product.category_name}
                ,{ldelim}
                    "@type": "ListItem",
                    "position": 2,
                    "name": "{$product.category_name|escape:'javascript'}"
                {rdelim}
                ,{ldelim}
                    "@type": "ListItem",
                    "position": 3,
                    "name": "{$product.name|escape:'javascript'}"
                {rdelim}
                {else}
                ,{ldelim}
                    "@type": "ListItem",
                    "position": 2,
                    "name": "{$product.name|escape:'javascript'}"
                {rdelim}
                {/if}
            ]
        {rdelim}
    ]
{rdelim}
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
            desc.classList.add('has-overflow');
            toggle.style.display = '';
            toggle.addEventListener('click', function() {
                var expanded = desc.classList.toggle('expanded');
                toggle.textContent = expanded ? 'Show less' : 'Read more';
            });
        }
    }

    var track = document.getElementById('galleryTrack');
    var dots = document.getElementById('galleryDots');
    var thumbs = document.getElementById('galleryThumbs');
    if (!track) return;

    var slides = track.querySelectorAll('.product-gallery-slide');
    var allDots = dots ? dots.querySelectorAll('.gallery-dot') : [];
    var allThumbs = thumbs ? thumbs.querySelectorAll('.gallery-thumb') : [];
    if (slides.length < 2) return;

    var current = 0;

    function updateIndicators(idx) {
        allDots.forEach(function(d, i) {
            d.classList.toggle('active', i === idx);
        });
        allThumbs.forEach(function(t, i) {
            t.classList.toggle('active', i === idx);
        });
    }

    // Scroll-snap based: listen to scroll end
    var scrollTimer;
    track.addEventListener('scroll', function() {
        clearTimeout(scrollTimer);
        scrollTimer = setTimeout(function() {
            var slideWidth = track.offsetWidth;
            current = Math.round(track.scrollLeft / slideWidth);
            updateIndicators(current);
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

    // Click thumbnails to navigate
    if (thumbs) {
        thumbs.addEventListener('click', function(e) {
            var thumb = e.target.closest('.gallery-thumb');
            if (!thumb) return;
            var idx = parseInt(thumb.dataset.index, 10);
            track.scrollTo({ left: idx * track.offsetWidth, behavior: 'smooth' });
        });
    }
})();
</script>
{/block}
