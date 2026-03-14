{extends file="layouts/base.tpl"}

{block name="body_class"}page-shop page-search{/block}

{block name="body"}

{include file="partials/shop/palette_vars.tpl" palette_scope="page-search"}

{include file="partials/shop/announcement_bar.tpl"}
{include file="partials/shop/desktop_header.tpl"}
{include file="partials/shop/mobile_header.tpl"}

<main class="shop-content" data-subdomain="{$shop.subdomain|escape}" data-total="{$total_products}" data-limit="{$products_limit}" data-currency="{$currency_symbol|escape}">

    <div class="search-page-header">
        <h1 class="search-page-title">{if $search_query}Results{else}Search{/if}</h1>
        <form class="search-page-form" id="searchForm" action="/search" method="get">
            <div class="search-page-bar">
                <svg class="search-page-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" name="q" id="searchInput" class="search-page-input" value="{$search_query|escape}" placeholder="What are you looking for?" autocomplete="off" autofocus>
                <button type="button" class="search-page-clear" id="searchClear" aria-label="Clear search" style="{if !$search_query}display:none{/if}">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
        </form>

        <p class="search-page-count" id="searchCount" style="{if !$search_query}display:none{/if}">
            {if $search_query}{$total_products} {if $total_products == 1}result{else}results{/if} for &ldquo;{$search_query|escape}&rdquo;{/if}
        </p>
    </div>

    <section class="products-section" id="searchResults" style="{if !$search_query}display:none{/if}">
        <div class="product-grid" id="productGrid">
            {if $search_query}
                {foreach $products as $product}
                    {include file="partials/shop/product_card.tpl"}
                {foreachelse}
                    {include file="partials/shop/empty_state.tpl" empty_title="No results found" empty_subtitle="Try a different search term."}
                {/foreach}
            {/if}
        </div>
    </section>

    <div class="load-more-wrap" id="loadMoreWrap" style="{if !$search_query || $total_products <= $products_limit}display:none{/if}">
        <button type="button" class="load-more-btn" id="loadMoreBtn">
            Show more <span class="load-more-count" id="loadMoreCount">{if $search_query && $total_products > $products_limit}({$total_products - $products_limit} more){/if}</span>
        </button>
    </div>

    <section class="browse-collections" id="browseCollections" style="{if $search_query}display:none{/if}">
        {if !empty($categories)}
            <h2 class="browse-collections-title">Browse collections</h2>
            {include file="partials/shop/collection_list.tpl"}
        {/if}
    </section>

</main>

{include file="partials/shop/desktop_footer.tpl"}
{include file="partials/shop/cart_drawer.tpl"}
{include file="partials/shop/contact_sheet.tpl"}
{include file="partials/shop/bottom_nav.tpl"}

{/block}

{block name="page_scripts"}
<script>
(function() {ldelim}
    var subdomain = document.querySelector('.shop-content').dataset.subdomain;
    var limit = parseInt(document.querySelector('.shop-content').dataset.limit, 10) || 24;

    var form = document.getElementById('searchForm');
    var input = document.getElementById('searchInput');
    var clearBtn = document.getElementById('searchClear');
    var countEl = document.getElementById('searchCount');
    var resultsEl = document.getElementById('searchResults');
    var gridEl = document.getElementById('productGrid');
    var browseEl = document.getElementById('browseCollections');
    var loadMoreWrap = document.getElementById('loadMoreWrap');
    var loadMoreBtn = document.getElementById('loadMoreBtn');
    var loadMoreCount = document.getElementById('loadMoreCount');

    var state = {ldelim}
        query: input.value.trim(),
        offset: gridEl.querySelectorAll('.product-card').length,
        total: parseInt(document.querySelector('.shop-content').dataset.total, 10) || 0,
        loading: false
    {rdelim};

    var debounceTimer = null;

    function skeletonCards(n) {ldelim}
        var h = '';
        for (var i = 0; i < n; i++) {ldelim}
            h += '<div class="product-card product-card-skeleton">'
                + '<div class="product-card-img skeleton-pulse" style="border-radius:16px"></div>'
                + '<div class="product-card-body">'
                + '<div class="skeleton-pulse" style="height:12px;width:40%;border-radius:6px;margin-bottom:6px"></div>'
                + '<div class="skeleton-pulse" style="height:13px;width:70%;border-radius:6px"></div>'
                + '</div></div>';
        {rdelim}
        return h;
    {rdelim}

    function doSearch(query, append) {ldelim}
        if (state.loading) return;
        state.loading = true;

        var offset = append ? state.offset : 0;

        if (!append) {ldelim}
            gridEl.innerHTML = skeletonCards(6);
            resultsEl.style.display = '';
            browseEl.style.display = 'none';
            loadMoreWrap.style.display = 'none';
        {rdelim} else {ldelim}
            loadMoreBtn.classList.add('loading');
            loadMoreBtn.textContent = 'Loading...';
        {rdelim}

        fetch('/api/shop/' + encodeURIComponent(subdomain) + '/products?search=' + encodeURIComponent(query) + '&offset=' + offset + '&limit=' + limit + '&format=html')
            .then(function(r) {ldelim} return r.json(); {rdelim})
            .then(function(data) {ldelim}
                state.loading = false;
                state.total = data.total || 0;

                if (append) {ldelim}
                    gridEl.insertAdjacentHTML('beforeend', data.html || '');
                {rdelim} else {ldelim}
                    if (state.total === 0) {ldelim}
                        gridEl.innerHTML = '<div class="empty-state"><i class="fa-solid fa-bag-shopping empty-state-icon"></i><p><strong>No results found</strong></p><p class="empty-state-subtitle">Try a different search term.</p></div>';
                    {rdelim} else {ldelim}
                        gridEl.innerHTML = data.html || '';
                    {rdelim}
                {rdelim}

                state.offset = offset + (data.limit || limit);
                var shown = Math.min(state.offset, state.total);

                countEl.textContent = state.total + (state.total === 1 ? ' result' : ' results') + ' for \u201c' + query + '\u201d';
                countEl.style.display = '';

                var remaining = state.total - shown;
                if (remaining > 0) {ldelim}
                    loadMoreBtn.classList.remove('loading');
                    loadMoreBtn.innerHTML = 'Show more <span class="load-more-count">(' + remaining + ' more)</span>';
                    loadMoreWrap.style.display = '';
                {rdelim} else {ldelim}
                    loadMoreWrap.style.display = 'none';
                {rdelim}

                var url = '/search' + (query ? '?q=' + encodeURIComponent(query) : '');
                history.replaceState(null, '', url);

                if (typeof TinyShop !== 'undefined' && TinyShop.Cart && TinyShop.Cart.rebindButtons) {ldelim}
                    TinyShop.Cart.rebindButtons();
                {rdelim}
            {rdelim})
            .catch(function() {ldelim}
                state.loading = false;
                loadMoreBtn.classList.remove('loading');
                loadMoreBtn.innerHTML = 'Show more <span class="load-more-count"></span>';
            {rdelim});
    {rdelim}

    form.addEventListener('submit', function(e) {ldelim}
        e.preventDefault();
        var q = input.value.trim();
        if (!q) return;
        state.query = q;
        clearBtn.style.display = '';
        doSearch(q, false);
    {rdelim});

    input.addEventListener('input', function() {ldelim}
        clearTimeout(debounceTimer);
        var q = input.value.trim();
        clearBtn.style.display = q ? '' : 'none';

        if (!q) {ldelim}
            resultsEl.style.display = 'none';
            loadMoreWrap.style.display = 'none';
            countEl.style.display = 'none';
            browseEl.style.display = '';
            history.replaceState(null, '', '/search');
            return;
        {rdelim}

        debounceTimer = setTimeout(function() {ldelim}
            state.query = q;
            doSearch(q, false);
        {rdelim}, 350);
    {rdelim});

    clearBtn.addEventListener('click', function() {ldelim}
        input.value = '';
        input.focus();
        clearBtn.style.display = 'none';
        resultsEl.style.display = 'none';
        loadMoreWrap.style.display = 'none';
        countEl.style.display = 'none';
        browseEl.style.display = '';
        state.query = '';
        state.offset = 0;
        state.total = 0;
        history.replaceState(null, '', '/search');
    {rdelim});

    loadMoreBtn.addEventListener('click', function() {ldelim}
        if (state.loading || !state.query) return;
        doSearch(state.query, true);
    {rdelim});
{rdelim})();
</script>
{/block}