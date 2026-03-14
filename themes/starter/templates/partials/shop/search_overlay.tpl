{hook name="theme.search_overlay.before"}
<div class="search-overlay" id="searchOverlay">
    <div class="search-overlay-backdrop"></div>
    <div class="search-overlay-inner">
        <div class="search-palette">
            <form class="search-overlay-bar" action="/search" method="get">
                <svg class="search-palette-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="search" name="q" class="search-overlay-input" id="searchOverlayInput" placeholder="Search products..." autocomplete="off">
                <kbd class="search-palette-kbd">ESC</kbd>
            </form>
            <button type="button" class="search-overlay-close" id="searchOverlayClose" aria-label="Close search">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
    </div>
</div>
{hook name="theme.search_overlay.after"}