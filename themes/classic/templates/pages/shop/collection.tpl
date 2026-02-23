{extends file="layouts/base.tpl"}

{block name="body_class"}page-shop page-collection{/block}

{block name="body"}

{include file="partials/shop/palette_vars.tpl" palette_scope="page-collection"}

{include file="partials/shop/announcement_bar.tpl"}
{include file="partials/shop/desktop_header.tpl"}
{include file="partials/shop/mobile_header.tpl"}

<main class="shop-content" data-subdomain="{$shop.subdomain|escape}" data-total="{$total_products}" data-limit="{$products_limit}" data-currency="{$currency_symbol|escape}" data-active-category="{$category.id}">

    {hook name="theme.collection.before"}

    <div class="collection-hero">
        <nav class="collections-breadcrumb">
            <a href="/">Home</a>
            <svg width="6" height="10" viewBox="0 0 6 10" fill="currentColor"><path d="M1.4 10L0 8.6 3.6 5 0 1.4 1.4 0l5 5-5 5z"/></svg>
            <a href="/collections">Collections</a>
            <svg width="6" height="10" viewBox="0 0 6 10" fill="currentColor"><path d="M1.4 10L0 8.6 3.6 5 0 1.4 1.4 0l5 5-5 5z"/></svg>
            <span>{$category.name|escape}</span>
        </nav>

        {if $category.image_url}
        <div class="collection-hero-banner">
            <img src="{$category.image_url|escape}" alt="{$category.name|escape}">
            <div class="collection-hero-overlay">
                <h1 class="collection-hero-title">{$category.name|escape}</h1>
            </div>
        </div>
        {else}
        <h1 class="collections-title">{$category.name|escape}</h1>
        {/if}

        <div class="collection-toolbar">
            <p class="collection-hero-count">{$total_products} {if $total_products == 1}product{else}products{/if}</p>
            <select class="collection-sort" id="collectionSort">
                <option value="default"{if $sort == 'default'} selected{/if}>Featured</option>
                <option value="price_asc"{if $sort == 'price_asc'} selected{/if}>Price: Low to High</option>
                <option value="price_desc"{if $sort == 'price_desc'} selected{/if}>Price: High to Low</option>
                <option value="newest"{if $sort == 'newest'} selected{/if}>Newest</option>
                <option value="name_asc"{if $sort == 'name_asc'} selected{/if}>Name: A to Z</option>
            </select>
        </div>
    </div>

    {hook name="theme.collection.hero.after"}

    {if !empty($subcategories)}
    <div class="collection-filters hide-scrollbar">
        <button class="collection-filter active" data-filter="all">All</button>
        {foreach $subcategories as $sub}
        <button class="collection-filter" data-filter="{$sub.id}">{$sub.name|escape}</button>
        {/foreach}
    </div>
    {/if}

    {hook name="theme.collection.grid.before"}

    <section class="products-section">
        <div class="product-grid" id="productGrid">
            {foreach $products as $product}
                {include file="partials/shop/product_card.tpl"}
            {foreachelse}
                {include file="partials/shop/empty_state.tpl" empty_title="No products yet" empty_subtitle="Check back soon for new items in this collection."}
            {/foreach}
        </div>
    </section>

    {hook name="theme.collection.grid.after"}

    {if $total_products > $products_limit}
    <div class="load-more-wrap">
        <button class="load-more-btn" id="loadMoreBtn">
            Load more <span class="load-more-count">({$total_products - $products_limit} more)</span>
        </button>
    </div>
    {/if}

    {hook name="theme.collection.after"}

</main>

{include file="partials/shop/desktop_footer.tpl"}
{include file="partials/shop/cart_drawer.tpl"}
{include file="partials/shop/contact_sheet.tpl"}
{include file="partials/shop/bottom_nav.tpl"}

{/block}

{block name="page_scripts"}
<script>
(function() {ldelim}
    'use strict';

    var shopContent = document.querySelector('.shop-content');
    var subdomain = shopContent.dataset.subdomain;
    var limit = parseInt(shopContent.dataset.limit, 10) || 24;
    var categoryId = shopContent.dataset.activeCategory || '';
    var grid = document.getElementById('productGrid');
    var sortSelect = document.getElementById('collectionSort');
    var countEl = document.querySelector('.collection-hero-count');
    var loadMoreWrap = document.querySelector('.load-more-wrap');
    var loadMoreBtn = document.getElementById('loadMoreBtn');

    var state = {ldelim}
        sort: sortSelect ? sortSelect.value : 'default',
        filter: 'all',
        offset: grid.querySelectorAll('.product-card').length,
        total: parseInt(shopContent.dataset.total, 10) || 0,
        loading: false
    {rdelim};

    function skeletonCards(n) {ldelim}
        var h = '';
        for (var i = 0; i < n; i++) {ldelim}
            h += '<div class="product-card search-skeleton">'
                + '<div class="search-skeleton-img"></div>'
                + '<div class="search-skeleton-body">'
                + '<div class="search-skeleton-line search-skeleton-title"></div>'
                + '<div class="search-skeleton-line search-skeleton-price"></div>'
                + '</div></div>';
        {rdelim}
        return h;
    {rdelim}

    function buildCategoryParam() {ldelim}
        if (state.filter && state.filter !== 'all') {ldelim}
            return state.filter;
        {rdelim}
        // "All" — include parent + all subcategory IDs
        var ids = [categoryId];
        document.querySelectorAll('.collection-filter[data-filter]').forEach(function(btn) {ldelim}
            var f = btn.getAttribute('data-filter');
            if (f && f !== 'all') ids.push(f);
        {rdelim});
        return ids.join(',');
    {rdelim}

    function fetchProducts(append) {ldelim}
        if (state.loading) return;
        state.loading = true;
        var offset = append ? state.offset : 0;

        if (!append) {ldelim}
            grid.innerHTML = skeletonCards(6);
            if (loadMoreWrap) loadMoreWrap.style.display = 'none';
        {rdelim} else if (loadMoreBtn) {ldelim}
            loadMoreBtn.classList.add('loading');
            loadMoreBtn.textContent = 'Loading...';
        {rdelim}

        var url = '/api/shop/' + encodeURIComponent(subdomain) + '/products'
            + '?category=' + encodeURIComponent(buildCategoryParam())
            + '&sort=' + encodeURIComponent(state.sort)
            + '&offset=' + offset
            + '&limit=' + limit
            + '&format=html';

        fetch(url)
            .then(function(r) {ldelim} return r.json(); {rdelim})
            .then(function(data) {ldelim}
                state.loading = false;
                state.total = data.total || 0;

                if (append) {ldelim}
                    grid.insertAdjacentHTML('beforeend', data.html || '');
                {rdelim} else {ldelim}
                    if (state.total === 0) {ldelim}
                        grid.innerHTML = '<div class="empty-state">'
                            + '<i class="fa-solid fa-bag-shopping empty-state-icon"></i>'
                            + '<p><strong>No products yet</strong></p>'
                            + '<p class="empty-state-subtitle">Check back soon for new items.</p></div>';
                    {rdelim} else {ldelim}
                        grid.innerHTML = data.html || '';
                    {rdelim}
                {rdelim}

                state.offset = offset + (data.limit || limit);
                var shown = Math.min(state.offset, state.total);

                if (countEl) {ldelim}
                    countEl.textContent = state.total + (state.total === 1 ? ' product' : ' products');
                {rdelim}

                var remaining = state.total - shown;
                if (remaining > 0) {ldelim}
                    if (!loadMoreWrap) {ldelim}
                        var wrap = document.createElement('div');
                        wrap.className = 'load-more-wrap';
                        wrap.innerHTML = '<button class="load-more-btn" id="loadMoreBtn">Load more <span class="load-more-count">(' + remaining + ' more)</span></button>';
                        grid.parentElement.insertAdjacentElement('afterend', wrap);
                        loadMoreWrap = wrap;
                        loadMoreBtn = wrap.querySelector('#loadMoreBtn');
                        loadMoreBtn.addEventListener('click', function() {ldelim}
                            fetchProducts(true);
                        {rdelim});
                    {rdelim} else {ldelim}
                        if (loadMoreBtn) {ldelim}
                            loadMoreBtn.classList.remove('loading');
                            loadMoreBtn.innerHTML = 'Load more <span class="load-more-count">(' + remaining + ' more)</span>';
                        {rdelim}
                        loadMoreWrap.style.display = '';
                    {rdelim}
                {rdelim} else if (loadMoreWrap) {ldelim}
                    loadMoreWrap.style.display = 'none';
                {rdelim}

                // Update URL without reload
                var pageUrl = new URL(window.location.href);
                if (state.sort !== 'default') {ldelim}
                    pageUrl.searchParams.set('sort', state.sort);
                {rdelim} else {ldelim}
                    pageUrl.searchParams.delete('sort');
                {rdelim}
                history.replaceState(null, '', pageUrl.toString());

                if (typeof TinyShop !== 'undefined' && TinyShop.Cart && TinyShop.Cart.rebindButtons) {ldelim}
                    TinyShop.Cart.rebindButtons();
                {rdelim}
            {rdelim})
            .catch(function() {ldelim}
                state.loading = false;
                if (loadMoreBtn) {ldelim}
                    loadMoreBtn.classList.remove('loading');
                    loadMoreBtn.innerHTML = 'Load more';
                {rdelim}
            {rdelim});
    {rdelim}

    // Sort change — AJAX, no page reload
    if (sortSelect) {ldelim}
        sortSelect.addEventListener('change', function() {ldelim}
            state.sort = this.value;
            fetchProducts(false);
        {rdelim});
    {rdelim}

    // Load more
    if (loadMoreBtn) {ldelim}
        loadMoreBtn.addEventListener('click', function() {ldelim}
            fetchProducts(true);
        {rdelim});
    {rdelim}

    // Subcategory filter chips — also AJAX now
    var filters = document.querySelectorAll('.collection-filter');
    filters.forEach(function(btn) {ldelim}
        btn.addEventListener('click', function() {ldelim}
            filters.forEach(function(f) {ldelim} f.classList.remove('active'); {rdelim});
            btn.classList.add('active');
            state.filter = btn.getAttribute('data-filter');
            fetchProducts(false);
        {rdelim});
    {rdelim});
{rdelim})();
</script>
{/block}
