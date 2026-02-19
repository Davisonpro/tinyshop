{hook name="theme.search_overlay.before"}
<div class="search-overlay" id="searchOverlay">
    <div class="search-overlay-inner">
        <div class="search-overlay-bar">
            <i class="fa-solid fa-magnifying-glass search-overlay-icon"></i>
            <input type="search" class="search-overlay-input" id="searchOverlayInput" placeholder="Search products..." autocomplete="off">
            <button class="search-overlay-close" id="searchOverlayClose" aria-label="Close search">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="search-overlay-results" id="searchOverlayResults"></div>
    </div>
</div>
{hook name="theme.search_overlay.after"}
