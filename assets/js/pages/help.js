/**
 * Help Center — client-side full-text search over articles.
 *
 * Reads article data from a hidden JSON element, scores
 * results by title/keyword/summary match, and renders
 * a ranked list. Also handles category card scroll-to.
 *
 * @since 1.0.0
 */
(function() {
    'use strict';

    var searchInput  = document.getElementById('helpSearchInput');
    var searchClear  = document.getElementById('helpSearchClear');
    var searchWrap   = document.getElementById('helpSearchWrap');
    var resultsWrap  = document.getElementById('helpSearchResults');
    var resultsList  = document.getElementById('helpResultsList');
    var resultsTitle = document.getElementById('helpResultsTitle');
    var categories   = document.getElementById('helpCategories');
    var sections     = document.getElementById('helpSections');
    var bottom       = document.getElementById('helpBottom');
    var emptyState   = document.getElementById('helpSearchEmpty');

    if (!searchInput || !resultsWrap) return;

    var articles = [];
    try {
        var el = document.getElementById('helpArticleData');
        if (el) articles = JSON.parse(el.textContent || '[]');
    } catch (e) { /* ignore */ }

    var debounceTimer = null;

    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(doSearch, 150);

        if (searchInput.value.trim()) {
            searchWrap.classList.add('has-query');
        } else {
            searchWrap.classList.remove('has-query');
        }
    });

    searchClear.addEventListener('click', function() {
        searchInput.value = '';
        searchWrap.classList.remove('has-query');
        doSearch();
        searchInput.focus();
    });

    /** Run the search and render results. */
    function doSearch() {
        var raw = searchInput.value.trim().toLowerCase();

        if (!raw) {
            resultsWrap.style.display = 'none';
            if (categories) categories.style.display = '';
            if (sections) sections.style.display = '';
            if (bottom) bottom.style.display = '';
            return;
        }

        var terms = raw.split(/\s+/).filter(function(t) { return t.length > 0; });
        var scored = [];

        for (var i = 0; i < articles.length; i++) {
            var a = articles[i];
            var title   = (a.title || '').toLowerCase();
            var summary = (a.summary || '').toLowerCase();
            var kw      = (a.keywords || '').toLowerCase();
            var score   = 0;
            var matched = true;

            for (var j = 0; j < terms.length; j++) {
                var t = terms[j];
                var found = false;

                if (title.indexOf(t) !== -1)   { score += 10; found = true; }
                if (kw.indexOf(t) !== -1)      { score += 5;  found = true; }
                if (summary.indexOf(t) !== -1) { score += 1;  found = true; }

                if (!found) { matched = false; break; }
            }

            if (matched && score > 0) {
                scored.push({ article: a, score: score });
            }
        }

        scored.sort(function(a, b) { return b.score - a.score; });

        if (categories) categories.style.display = 'none';
        if (sections) sections.style.display = 'none';
        if (bottom) bottom.style.display = 'none';
        resultsWrap.style.display = 'block';

        if (scored.length === 0) {
            resultsList.innerHTML = '';
            resultsTitle.textContent = 'No results';
            emptyState.style.display = 'block';
            return;
        }

        emptyState.style.display = 'none';
        resultsTitle.textContent = scored.length + ' result' + (scored.length !== 1 ? 's' : '') + ' found';

        var html = '';
        for (var k = 0; k < scored.length; k++) {
            var art = scored[k].article;
            html += '<a href="/help/' + encodeURIComponent(art.slug) + '" class="help-article-link">';
            html += '<div class="help-article-link-body">';
            html += '<p class="help-article-link-cat">' + escHtml(art.category_name || '') + '</p>';
            html += '<p class="help-article-link-title">' + escHtml(art.title) + '</p>';
            if (art.summary) {
                html += '<p class="help-article-link-summary">' + escHtml(art.summary) + '</p>';
            }
            html += '</div>';
            html += '<i class="fa-solid fa-chevron-right help-article-link-arrow"></i>';
            html += '</a>';
        }
        resultsList.innerHTML = html;
    }

    /** Escape HTML entities (local to this IIFE). */
    function escHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    // Scroll to category section when clicking a category card
    var catCards = document.querySelectorAll('.help-category-card[data-category]');
    for (var c = 0; c < catCards.length; c++) {
        catCards[c].addEventListener('click', function(e) {
            e.preventDefault();
            var cat = this.getAttribute('data-category');
            var target = document.getElementById('section-' + cat);
            if (target) {
                var y = target.getBoundingClientRect().top + window.pageYOffset - 80;
                window.scrollTo({ top: y, behavior: 'smooth' });
            }
        });
    }
})();
