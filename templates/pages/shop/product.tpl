{extends file="layouts/base.tpl"}

{block name="body_class"}page-shop page-product{/block}

{block name="body"}
{include file="partials/shop/palette_vars.tpl" palette_scope="page-product"}
{include file="partials/shop/desktop_header.tpl"}
<div class="product-page" id="main-content">
    <div class="container">
        {* Breadcrumb — desktop only, above gallery *}
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="/">Shop</a>
            {if $product.category_name && $product.category_slug}
                <span class="breadcrumb-sep" aria-hidden="true">/</span>
                <a href="/collections/{$product.category_slug|escape}">{$product.category_name|escape}</a>
            {/if}
            <span class="breadcrumb-sep" aria-hidden="true">/</span>
            <span class="breadcrumb-current" aria-current="page">{$product.name|escape|truncate:40}</span>
        </nav>

        {* Full-bleed Image Gallery *}
        <div class="product-gallery" id="productGallery">
            {* Floating nav: back left, share right *}
            <div class="product-gallery-nav">
                <button type="button" class="product-nav-btn" onclick="if(history.length>1){ldelim}history.back(){rdelim}else{ldelim}location.href='/'{rdelim}" aria-label="Back">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
                <button type="button" class="product-nav-btn" data-share-trigger aria-label="Share">
                    <i class="fa-solid fa-arrow-up-from-bracket"></i>
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
                    <i class="fa-solid fa-image product-gallery-empty-icon"></i>
                </div>
            {/if}

            {* Badge overlay *}
            <div class="product-detail-badges">
                {if $product.is_sold}
                    <span class="product-detail-badge badge-sold">Sold Out</span>
                {else}
                    {if $product.is_featured}
                        <span class="product-detail-badge badge-featured">Best Seller</span>
                    {/if}
                    {if $product.created_at|is_recent:14}
                        <span class="product-detail-badge badge-new">New</span>
                    {/if}
                {/if}
            </div>
        </div>

        {* Product Info *}
        <div class="product-info">
            <div class="product-info-header">
                <div>
                    {if $product.category_name}
                        <span class="product-info-category">{$product.category_name|escape}</span>
                    {/if}
                    <h1 class="product-info-name">{$product.name|escape}</h1>
                </div>
            </div>

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

            {* Savings message *}
            {if $product.compare_price && $product.compare_price > $product.price && !$product.is_sold}
                <div class="savings-message" id="savingsMessage">
                    You save {$currency_symbol}{($product.compare_price - $product.price)|format_price}
                </div>
            {/if}

            {if !$product.is_sold && $product.stock_quantity !== null}
                {if $product.stock_quantity > 0 && $product.stock_quantity <= 5}
                    <div class="stock-badge stock-badge-low">Only {$product.stock_quantity} left in stock</div>
                {elseif $product.stock_quantity > 5}
                    <div class="stock-badge">In Stock</div>
                {/if}
            {/if}

            {if $product.description}
                <div class="product-info-summary">{$product.description nofilter}</div>
            {/if}
        </div>

        {* Variations *}
        {if !empty($product.variations_data)}
        <div class="product-variations" id="productVariations">
            {foreach $product.variations_data as $vgroup}
                <div class="product-variation-group" data-group="{$vgroup@index}">
                    <div class="product-variation-label">
                        {$vgroup.name|escape}
                        <span class="variation-selected-value variation-prompt" id="varSelected{$vgroup@index}">— Pick one</span>
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
                    <div class="variation-error-msg" id="varError{$vgroup@index}"></div>
                </div>
            {/foreach}
        </div>
        <script>
        window._productBasePrice = {$product.price};
        window._productComparePrice = {if $product.compare_price}{$product.compare_price}{else}0{/if};
        window._hasVariations = true;
        </script>
        {/if}

        {include file="partials/shop/product_whatsapp.tpl"}

        {* Sticky CTA — inside container for desktop grid placement *}
        {include file="partials/shop/product_cta.tpl"}

    </div>

    {* Full Description — collapsible on mobile *}
    {if $product.full_description}
    <div class="container">
        <div class="product-full-desc" id="productFullDesc">
            <button type="button" class="product-full-desc-toggle" id="fullDescToggle" aria-expanded="false">
                <span>Product description</span>
                <i class="fa-solid fa-chevron-down product-full-desc-icon"></i>
            </button>
            <div class="product-full-desc-body" id="fullDescBody">
                {$product.full_description nofilter}
            </div>
            <button type="button" class="product-full-desc-more product-full-desc-more-hidden" id="fullDescMore">
                <span>Read more</span>
                <i class="fa-solid fa-chevron-down product-full-desc-more-icon"></i>
            </button>
        </div>
    </div>
    {/if}

    {* More Products *}
    {if $more_products|@count > 0}
    <div class="container">
        <div class="more-products">
            <h2 class="more-products-title">More from this shop</h2>
            <div class="more-products-scroll hide-scrollbar">
                {foreach $more_products as $product}
                    <div class="more-product-card">
                        {include file="partials/shop/product_card.tpl"}
                    </div>
                {/foreach}
            </div>
        </div>
    </div>
    {/if}

    {include file="partials/shop/share_sheet.tpl"}
    {include file="partials/shop/cart_drawer.tpl"}
    {include file="partials/shop/contact_sheet.tpl"}
    {include file="partials/shop/bottom_nav.tpl"}
</div>
{include file="partials/shop/desktop_footer.tpl"}

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
    // Full description toggle
    var fullDesc = document.getElementById('productFullDesc');
    var fullDescToggle = document.getElementById('fullDescToggle');
    var fullDescBody = document.getElementById('fullDescBody');
    var fullDescMore = document.getElementById('fullDescMore');

    if (fullDesc && fullDescBody) {
        // Mobile: accordion toggle
        if (fullDescToggle) {
            fullDescToggle.addEventListener('click', function() {
                var isOpen = fullDesc.classList.toggle('open');
                fullDescToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                if (isOpen) {
                    fullDescBody.style.maxHeight = fullDescBody.scrollHeight + 'px';
                } else {
                    fullDescBody.style.maxHeight = '0';
                }
            });
        }

        // Desktop: "Read more" for overflowing content
        if (fullDescMore) {
            function checkDescOverflow() {
                if (fullDescBody.scrollHeight > fullDescBody.offsetHeight + 1) {
                    fullDescBody.classList.add('has-overflow');
                    fullDescMore.classList.remove('product-full-desc-more-hidden');
                }
            }
            fullDescMore.addEventListener('click', function() {
                var expanded = fullDescBody.classList.toggle('expanded');
                fullDesc.classList.toggle('open', expanded);
                fullDescMore.classList.toggle('expanded', expanded);
                fullDescMore.querySelector('span').textContent = expanded ? 'Show less' : 'Read more';
            });
            requestAnimationFrame(checkDescOverflow);
            window.addEventListener('load', checkDescOverflow);
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
