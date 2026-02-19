{hook name="theme.search_overlay.before"}
<div class="search-overlay" id="searchOverlay">
    <div class="search-overlay-inner">
        <form class="search-overlay-bar" action="/search" method="get">
            <i class="fa-solid fa-magnifying-glass search-overlay-icon"></i>
            <input type="search" name="q" class="search-overlay-input" id="searchOverlayInput" placeholder="Search products..." autocomplete="off">
            <button type="button" class="search-overlay-close" id="searchOverlayClose" aria-label="Close search">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </form>
    </div>
</div>
{hook name="theme.search_overlay.after"}
