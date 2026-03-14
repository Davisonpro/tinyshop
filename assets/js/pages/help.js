/**
 * Help Center — search + topic accordion.
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
    var helpMain     = document.getElementById('helpMain');
    var bottom       = document.getElementById('helpBottom');
    var emptyState   = document.getElementById('helpSearchEmpty');

    if (!searchInput || !resultsWrap) return;

    // ── Load article data for search ──
    var articles = [];
    try {
        var el = document.getElementById('helpArticleData');
        if (el) articles = JSON.parse(el.textContent || '[]');
    } catch (e) { /* ignore */ }

    // ── Search ──
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

    function doSearch() {
        var raw = searchInput.value.trim().toLowerCase();

        if (!raw) {
            resultsWrap.style.display = 'none';
            if (helpMain) helpMain.style.display = '';
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

        if (helpMain) helpMain.style.display = 'none';
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

    function escHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    // ── Topic accordion ──
    var headers = document.querySelectorAll('.help-topic-header');
    for (var h = 0; h < headers.length; h++) {
        headers[h].addEventListener('click', function() {
            var topic = this.parentElement;
            var isOpen = topic.classList.contains('open');

            // Close all others
            var allTopics = document.querySelectorAll('.help-topic.open');
            for (var t = 0; t < allTopics.length; t++) {
                if (allTopics[t] !== topic) {
                    allTopics[t].classList.remove('open');
                    allTopics[t].querySelector('.help-topic-header').setAttribute('aria-expanded', 'false');
                }
            }

            // Toggle this one
            topic.classList.toggle('open', !isOpen);
            this.setAttribute('aria-expanded', !isOpen ? 'true' : 'false');
        });
    }

    // Open topic if URL hash matches
    if (window.location.hash) {
        var target = document.getElementById('topic-' + window.location.hash.slice(1).replace('section-', ''));
        if (target) {
            target.classList.add('open');
            target.querySelector('.help-topic-header').setAttribute('aria-expanded', 'true');
            setTimeout(function() {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 100);
        }
    }
})();
