/**
 * Import Product Page
 *
 * Three import modes: Link, Page Source (HTML), Quick Add (AI).
 * Each mode parses product data and presents it for review before saving.
 *
 * @requires TinyShop (namespace, api, toast, modal, initPriceInput)
 * @requires jQuery
 */

// ════════════════════════════════════════════════════
// § Rich Editor Factory
// ════════════════════════════════════════════════════

/**
 * Create a rich-text editor with basic formatting toolbar.
 * Used for both single-product preview and Quick Add accordion cards.
 *
 * @param {string|HTMLElement} editorEl  - Editor container (element or ID).
 * @param {string|HTMLElement} textareaEl - Hidden textarea to sync HTML into.
 * @param {string} placeholder - Placeholder text.
 * @param {Object} [opts] - Options.
 * @param {boolean} [opts.full=false] - Show heading + ordered list buttons.
 * @returns {{ setContent: Function, sync: Function }|null}
 */
function initRichEditor(editorEl, textareaEl, placeholder, opts) {
    if (typeof editorEl === 'string') editorEl = document.getElementById(editorEl);
    if (typeof textareaEl === 'string') textareaEl = document.getElementById(textareaEl);
    if (!editorEl || !textareaEl) return null;

    opts = opts || {};
    var full = opts.full || false;

    var content = document.createElement('div');
    content.className = 'rich-editor-content';
    content.contentEditable = true;
    content.setAttribute('data-placeholder', placeholder);
    content.setAttribute('role', 'textbox');
    content.setAttribute('aria-label', placeholder);
    editorEl.appendChild(content);

    // Toolbar actions
    var actions = [
        { icon: '<span style="font-weight:800;font-size:14px">B</span>', cmd: 'bold', label: 'Bold' },
        { icon: '<span style="font-style:italic;font-size:14px;font-family:Georgia,serif">I</span>', cmd: 'italic', label: 'Italic' }
    ];
    if (full) {
        actions.push({ icon: '<span style="font-weight:700;font-size:13px">H</span>', cmd: 'heading', label: 'Heading' });
    }
    actions.push({ icon: '<i class="fa-solid fa-list-ul"></i>', cmd: 'insertUnorderedList', label: 'Bullet list' });
    if (full) {
        actions.push({ icon: '<i class="fa-solid fa-list-ol"></i>', cmd: 'insertOrderedList', label: 'Numbered list' });
    }

    var toolbar = document.createElement('div');
    toolbar.className = 'rich-editor-toolbar';
    var buttons = [];

    actions.forEach(function(a, i) {
        // Separator before lists
        if (full && i === 3) {
            var sep = document.createElement('div');
            sep.className = 'rich-editor-sep';
            toolbar.appendChild(sep);
        }
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'rich-editor-btn';
        btn.innerHTML = a.icon;
        btn.title = a.label;
        btn.setAttribute('aria-label', a.label);
        btn.addEventListener('mousedown', function(e) {
            e.preventDefault();
            if (a.cmd === 'heading') {
                var block = document.queryCommandValue('formatBlock');
                document.execCommand('formatBlock', false, block === 'h3' ? '<p>' : '<h3>');
            } else {
                document.execCommand(a.cmd, false, null);
            }
            sync();
            updateActive();
        });
        toolbar.appendChild(btn);
        buttons.push({ el: btn, action: a });
    });
    editorEl.appendChild(toolbar);

    function sync() {
        var html = content.innerHTML;
        textareaEl.value = (!html || html === '<br>' || html === '<p><br></p>') ? '' : html;
    }

    function updateActive() {
        buttons.forEach(function(b) {
            var active = false;
            if (b.action.cmd === 'heading') active = document.queryCommandValue('formatBlock') === 'h3';
            else active = document.queryCommandState(b.action.cmd);
            b.el.classList.toggle('is-active', active);
        });
    }

    content.addEventListener('input', function() { sync(); updateActive(); });
    content.addEventListener('blur', sync);
    if (full) {
        content.addEventListener('keyup', updateActive);
        content.addEventListener('mouseup', function() { setTimeout(updateActive, 10); });
    }

    // Keyboard shortcuts
    content.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && !e.shiftKey) {
            if (e.key === 'b') { e.preventDefault(); document.execCommand('bold'); sync(); updateActive(); }
            if (e.key === 'i') { e.preventDefault(); document.execCommand('italic'); sync(); updateActive(); }
        }
    });

    // Sanitized paste
    content.addEventListener('paste', function(e) {
        e.preventDefault();
        var clipHtml = (e.clipboardData || window.clipboardData).getData('text/html');
        var clipText = (e.clipboardData || window.clipboardData).getData('text/plain');
        if (clipHtml && full) {
            var tmp = document.createElement('div');
            tmp.innerHTML = clipHtml;
            tmp.querySelectorAll('*').forEach(function(el) {
                el.removeAttribute('style');
                el.removeAttribute('class');
                el.removeAttribute('id');
            });
            var allowed = ['P', 'BR', 'B', 'STRONG', 'I', 'EM', 'UL', 'OL', 'LI', 'H2', 'H3', 'A'];
            tmp.querySelectorAll('*').forEach(function(el) {
                if (allowed.indexOf(el.tagName) === -1) el.replaceWith(document.createTextNode(el.textContent));
            });
            document.execCommand('insertHTML', false, tmp.innerHTML);
        } else {
            document.execCommand('insertText', false, clipText);
        }
        sync();
    });

    return { setContent: function(html) { content.innerHTML = html || ''; sync(); }, sync: sync };
}

// ════════════════════════════════════════════════════
// § Main IIFE
// ════════════════════════════════════════════════════

(function($) {
    // ── Config ──
    var CURRENCY = ($('.dash-form').data('currency') || 'KES');

    // ── Shared state ──
    var _categoryTree = [];
    var fetchedData = null;
    var _importVarGroups = [];

    // ── Init editors for single-product preview ──
    var importShortDescRich = initRichEditor('importShortDescEditor', 'prevShortDescription', 'Short description...', { full: true });
    var importDescRich = initRichEditor('importRichEditor', 'prevDescription', 'Product description...', { full: true });

    // Load categories on init
    TinyShop.api('GET', '/api/import/categories').done(function(res) {
        _categoryTree = res.categories || [];
    });

    // ── Helpers ──

    function escHtml(s) {
        if (!s) return '';
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function resetBtn($btn, labelSel, loadingSel) {
        $btn.prop('disabled', false);
        $btn.find(loadingSel).hide();
        $btn.find(labelSel).show();
    }

    /**
     * Advance the progress step indicator.
     * @param {number} step - Active step (1=parsing, 2=looking up, 3=done).
     * @param {string} text - Status text to display.
     */
    function sipStep(step, text) {
        $('#sipStatus').text(text);
        for (var i = 1; i <= 3; i++) {
            var $s = $('#sipStep' + i);
            $s.removeClass('active done');
            if (i < step) $s.addClass('done');
            else if (i === step) $s.addClass('active');
        }
        // Mark connecting lines as done
        var $lines = $('.sip-step-line');
        $lines.removeClass('done');
        if (step >= 2) $lines.eq(0).addClass('done');
        if (step >= 3) $lines.eq(1).addClass('done');
    }

    // ════════════════════════════════════════════════════
    // § Tab Switching
    // ════════════════════════════════════════════════════

    $('.import-source-tab').on('click', function() {
        var tab = $(this).data('tab');
        $('.import-source-tab').removeClass('active');
        $(this).addClass('active');
        $('.import-tab-panel').hide();

        if (tab === 'link') $('#tabLink').show();
        else if (tab === 'html') $('#tabHtml').show();
        else if (tab === 'quick') $('#tabQuick').show();

        if (tab === 'quick') {
            $('#importPreview').hide();
            $('#quickAddResults').show();
        } else {
            $('#quickAddResults').hide();
        }
    });

    // ════════════════════════════════════════════════════
    // § Link Import
    // ════════════════════════════════════════════════════

    $('#fetchBtn').on('click', function() {
        var url = $('#importUrl').val().trim();
        if (!url) { TinyShop.toast('Please enter a product URL', 'error'); return; }

        var $btn = $(this);
        $btn.prop('disabled', true);
        $btn.find('.fetch-btn-label').hide();
        $btn.find('.fetch-btn-loading').show();
        $('#importPreview').hide();
        fetchedData = null;
        _importVarGroups = [];

        TinyShop.api('POST', '/api/import/fetch', { url: url })
            .done(function(res) {
                fetchedData = res.product;
                renderPreview(res.product);
                $('#importPreview').show();
            })
            .fail(function(xhr) {
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to fetch product';
                if (msg.indexOf('403') !== -1 || msg.indexOf('HTTP') !== -1 || msg.indexOf('failed') !== -1) {
                    $('.import-source-tab').removeClass('active');
                    $('.import-source-tab[data-tab="html"]').addClass('active');
                    $('#tabLink').hide();
                    $('#tabHtml').show();
                    TinyShop.toast('Site blocked our server. Paste the page source instead.', 'error');
                } else {
                    TinyShop.toast(msg, 'error');
                }
            })
            .always(function() { resetBtn($btn, '.fetch-btn-label', '.fetch-btn-loading'); });
    });

    // ════════════════════════════════════════════════════
    // § HTML Paste Import
    // ════════════════════════════════════════════════════

    $('#parseHtmlBtn').on('click', function() {
        var html = $('#pasteHtml').val().trim();
        if (!html || html.length < 100) { TinyShop.toast('Please paste the full page source', 'error'); return; }

        var $btn = $(this);
        $btn.prop('disabled', true);
        $btn.find('.parse-btn-label').hide();
        $btn.find('.parse-btn-loading').show();
        $('#importPreview').hide();
        fetchedData = null;
        _importVarGroups = [];

        TinyShop.api('POST', '/api/import/fetch', { html: html })
            .done(function(res) {
                fetchedData = res.product;
                renderPreview(res.product);
                $('#importPreview').show();
                $('#pasteHtml').val('');
            })
            .fail(function(xhr) {
                TinyShop.toast(xhr.responseJSON ? xhr.responseJSON.message : 'Failed to parse HTML', 'error');
            })
            .always(function() { resetBtn($btn, '.parse-btn-label', '.parse-btn-loading'); });
    });

    // ════════════════════════════════════════════════════
    // § Single-Product Preview
    // ════════════════════════════════════════════════════

    function renderPreview(p) {
        $('#prevTitle').val(p.title || '');
        if (importShortDescRich) importShortDescRich.setContent(p.description || '');
        if (importDescRich) importDescRich.setContent(p.full_description || '');
        $('#prevPrice').val(p.price || '');
        $('#prevComparePrice').val(p.compare_price || '');
        $('#prevPlatform').text(p.source_platform || '');
        $('#prevCurrency').text(p.currency || '');
        $('#prevCurrencyPrefix').text(p.currency || CURRENCY);
        $('#prevCompareCurrencyPrefix').text(p.currency || CURRENCY);
        $('#prevIsFeatured').prop('checked', !!p.is_featured);
        $('#prevIsSold').prop('checked', !!p.is_sold);

        if (p.existing_product_id) {
            $('#reimportNotice').show();
            $('#reimportName').text(p.existing_product_name || 'ID ' + p.existing_product_id);
        } else {
            $('#reimportNotice').hide();
        }

        renderCategories();
        renderPreviewImages(p);
        renderVariations();

        TinyShop.initPriceInput($('#prevPrice'));
        TinyShop.initPriceInput($('#prevComparePrice'));
    }

    function renderPreviewImages(p) {
        var $gallery = $('#prevImages').empty();
        if (p.images && p.images.length) {
            p.images.forEach(function(src, i) {
                var $item = $('<div class="image-gallery-item" data-index="' + i + '">');
                $item.append('<img src="' + escHtml(src) + '" alt="" loading="lazy">');
                $item.append('<button type="button" class="image-gallery-remove" data-index="' + i + '">&times;</button>');
                $gallery.append($item);
            });
        } else {
            $gallery.append('<p class="form-hint" style="margin:0">No images found</p>');
        }
    }

    $(document).on('click', '#prevImages .image-gallery-remove', function(e) {
        e.stopPropagation();
        var idx = $(this).data('index');
        if (fetchedData && fetchedData.images) {
            fetchedData.images.splice(idx, 1);
            renderPreview(fetchedData);
        }
    });

    // ── Variations (single-product) ──

    function flatToGroups(variations) {
        var groups = {};
        var groupOrder = [];
        variations.forEach(function(v) {
            var attrs = v.attributes || {};
            var price = v.price != null ? v.price : null;
            var keys = Object.keys(attrs);
            if (!keys.length) {
                if (!groups['_default']) { groups['_default'] = {}; groupOrder.push('_default'); }
                var name = v.name || '—';
                if (!groups['_default'][name] || (price !== null && price < groups['_default'][name])) {
                    groups['_default'][name] = price;
                }
                return;
            }
            keys.forEach(function(groupName) {
                if (!groups[groupName]) { groups[groupName] = {}; groupOrder.push(groupName); }
                var val = attrs[groupName];
                if (val && (!groups[groupName][val] || (price !== null && price < groups[groupName][val]))) {
                    groups[groupName][val] = price;
                }
            });
        });
        return groupOrder.map(function(name) {
            var opts = [];
            Object.keys(groups[name]).forEach(function(val) {
                var opt = { value: val };
                if (groups[name][val] !== null) opt.price = groups[name][val];
                opts.push(opt);
            });
            return { name: name === '_default' ? 'Option' : name, options: opts };
        });
    }

    function renderVariations() {
        if (!fetchedData || !fetchedData.variations || !fetchedData.variations.length) {
            if (!_importVarGroups.length) { $('#prevVariationsWrap').hide(); return; }
        }
        if (!_importVarGroups.length && fetchedData && fetchedData.variations && fetchedData.variations.length) {
            _importVarGroups = flatToGroups(fetchedData.variations);
        }
        $('#prevVariationsWrap').show();
        renderVariationGroups($('#prevVariations'), _importVarGroups);
    }

    function readImportVarGroups() {
        _importVarGroups = [];
        $('#prevVariations .variation-group').each(function() {
            var name = $(this).find('.variation-group-name').val().trim();
            var opts = [];
            $(this).find('.variation-option-row').each(function() {
                var val = $(this).find('.variation-option-value').val().trim();
                var ps = $(this).find('.variation-option-price').val().replace(/,/g, '').trim();
                if (val) { var o = { value: val }; if (ps !== '') o.price = parseFloat(ps); opts.push(o); }
            });
            if (name && opts.length) _importVarGroups.push({ name: name, options: opts });
        });
    }

    // ── Categories (single-product) ──

    function renderCategories() {
        var $cats = $('#prevCategories').empty();
        var cats = fetchedData ? (fetchedData.categories || []) : [];
        renderCategoryChips($cats, cats, 'import-cat-remove');
    }

    $(document).on('click', '.import-cat-remove', function(e) {
        e.stopPropagation();
        var idx = $(this).data('index');
        if (fetchedData && fetchedData.categories) {
            fetchedData.categories.splice(idx, 1);
            renderCategories();
        }
    });

    $('#openImportCatPicker').on('click', function() {
        var existing = fetchedData ? (fetchedData.categories || []) : [];
        openCategoryPicker(existing, function(name) {
            if (!fetchedData) fetchedData = { categories: [] };
            if (!fetchedData.categories) fetchedData.categories = [];
            if (fetchedData.categories.indexOf(name) === -1) fetchedData.categories.push(name);
            renderCategories();
        });
    });

    $('#addImportCatBtn').on('click', function() {
        openNewCategoryModal(function(cat) {
            _categoryTree.push({ id: cat.id, name: cat.name, children: [] });
            if (!fetchedData) fetchedData = { categories: [] };
            if (!fetchedData.categories) fetchedData.categories = [];
            if (fetchedData.categories.indexOf(cat.name) === -1) fetchedData.categories.push(cat.name);
            renderCategories();
        });
    });

    // ── Variation group editing (single-product) ──

    var $vc = $('#prevVariations');
    $vc.on('click', '.variation-group-remove', function() {
        $(this).closest('.variation-group').remove();
        readImportVarGroups();
        if (!_importVarGroups.length) $('#prevVariationsWrap').hide();
    });
    $vc.on('click', '.variation-option-remove', function() {
        $(this).closest('.variation-option-row').remove();
        readImportVarGroups();
    });
    $vc.on('click', '.variation-add-value', function() {
        appendVariationOption($(this).siblings('.variation-options'));
    });
    $vc.on('keydown', '.variation-option-value', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            var $row = $(this).closest('.variation-option-row');
            if ($row.is(':last-child') && $(this).val().trim()) {
                $row.closest('.variation-group').find('.variation-add-value').click();
            }
        }
    });
    $('#addImportVarGroup').on('click', function() {
        $('#prevVariationsWrap').show();
        _importVarGroups.push({ name: '', options: [{ value: '' }] });
        renderVariations();
        $vc.find('.variation-group').last().find('.variation-group-name').focus();
    });

    // ── Save (single-product) ──

    $('#saveBtn').on('click', function() {
        var title = $('#prevTitle').val().trim();
        if (!title) { TinyShop.toast('Product title is required', 'error'); return; }

        var $btn = $(this);
        $btn.prop('disabled', true).html('<span class="btn-spinner"></span> Importing...');

        readImportVarGroups();

        var payload = {
            title: title,
            description: $('#prevShortDescription').val().trim(),
            full_description: $('#prevDescription').val().trim(),
            price: parseFloat(($('#prevPrice').val() || '0').replace(/,/g, '')) || 0,
            compare_price: $('#prevComparePrice').val().replace(/,/g, '').trim() ? parseFloat($('#prevComparePrice').val().replace(/,/g, '')) : null,
            categories: fetchedData ? fetchedData.categories : [],
            images: fetchedData ? fetchedData.images : [],
            variations: _importVarGroups,
            source_url: fetchedData ? (fetchedData.source_url || '') : '',
            is_featured: $('#prevIsFeatured').is(':checked') ? 1 : 0,
            is_sold: $('#prevIsSold').is(':checked') ? 1 : 0
        };

        TinyShop.api('POST', '/api/import/save', payload)
            .done(function() {
                TinyShop.toast('Product imported!');
                fetchedData = null;
                $('#importPreview').hide();
                $('#importUrl').val('');
            })
            .fail(function(xhr) {
                TinyShop.toast(xhr.responseJSON ? xhr.responseJSON.message : 'Failed to save product', 'error');
            })
            .always(function() {
                $btn.prop('disabled', false).html('<i class="fa-solid fa-check"></i> Save to Store');
            });
    });

    // ════════════════════════════════════════════════════
    // § Quick Add (AI Smart Import)
    // ════════════════════════════════════════════════════

    var _quickResults = [];
    var _quickAbort = null;
    var _quickSavedTotal = 0;

    // ── Parse + Resolve flow ──

    $('#quickFindBtn').on('click', function() {
        var text = $('#quickAddText').val().trim();
        if (!text) { TinyShop.toast('Type some products first', 'error'); return; }

        var $btn = $(this);
        $btn.prop('disabled', true);
        $btn.find('.quick-btn-label').hide();
        $btn.find('.quick-btn-loading').show();
        $('#quickAddAccordion').empty();
        $('#quickAddBulkBar').hide();
        $('#quickAddSuccess').hide();
        $('#quickAddNotice').hide().empty();
        $('#quickAddProgress').show();
        _quickResults = [];
        _quickSavedTotal = 0;

        var cancelled = false;
        _quickAbort = function() { cancelled = true; };

        // Reset step indicator
        sipStep(1, 'Reading your product list...');

        TinyShop.api('POST', '/api/import/smart-parse', { text: text })
            .done(function(res) {
                if (cancelled) { finish(); return; }
                var items = res.items || [];
                if (!items.length) {
                    TinyShop.toast('No products found. Try including product names and prices.', 'error');
                    finish();
                    return;
                }

                sipStep(2, 'Found ' + items.length + ' product(s). Getting details & images...');

                TinyShop.api('POST', '/api/import/smart-resolve', { items: items })
                    .done(function(res2) {
                        if (cancelled) { finish(); return; }
                        _quickResults = res2.results || [];
                        // Show skipped/info message as inline notice
                        var $notice = $('#quickAddNotice').hide().empty();
                        if (res2.message) {
                            var type = res2.count > 0 ? 'info' : 'warning';
                            var icon = type === 'info' ? 'fa-circle-info' : 'fa-triangle-exclamation';
                            $notice.html('<i class="fa-solid ' + icon + '"></i> ' + escHtml(res2.message)).attr('data-type', type).show();
                        }
                        if (_quickResults.length) {
                            sipStep(3, _quickResults.length + ' product(s) ready to review');
                            renderQuickAccordion();
                        } else {
                            TinyShop.toast('No products with complete data found. Try more specific names.', 'error');
                        }
                    })
                    .fail(function(xhr) {
                        TinyShop.toast(xhr.responseJSON ? xhr.responseJSON.message : 'Failed to look up products', 'error');
                    })
                    .always(finish);
            })
            .fail(function(xhr) {
                TinyShop.toast(xhr.responseJSON ? xhr.responseJSON.message : 'Failed to parse', 'error');
                finish();
            });

        function finish() {
            resetBtn($btn, '.quick-btn-label', '.quick-btn-loading');
            $('#quickAddProgress').hide();
            _quickAbort = null;
        }
    });

    $('#quickAddCancel').on('click', function() {
        if (_quickAbort) _quickAbort();
        _quickAbort = null;
        TinyShop.toast('Cancelled');
    });

    $('#quickAddMore').on('click', function() {
        $('#quickAddSuccess').hide();
        $('#quickAddText').val('').focus();
    });

    // ── Accordion Renderer ──

    function renderQuickAccordion() {
        var $acc = $('#quickAddAccordion').empty();

        _quickResults.forEach(function(r, i) {
            var thumbHtml = (r.images && r.images.length)
                ? '<img src="' + escHtml(r.images[0]) + '" alt="" loading="lazy" onerror="this.parentNode.innerHTML=\'<div class=\\\'qi-thumb-placeholder\\\'><i class=\\\'fa-solid fa-image\\\'></i></div>\'">'
                : '<div class="qi-thumb-placeholder"><i class="fa-solid fa-image"></i></div>';

            var priceDisplay = r.price > 0 ? Number(r.price).toLocaleString() : '—';
            var sourceLabel = r.source === 'pkb' ? 'Catalog' : r.source === 'web' ? 'Web' : 'AI';
            var catLabel = r.matched_category_name || r.category_hint || '';
            var itemId = 'qi-' + i;

            var html = '<div class="qi-item" data-index="' + i + '" id="' + itemId + '">'
                + '<div class="qi-header" role="button" tabindex="0" aria-expanded="false" aria-controls="' + itemId + '-body">'
                + '<div class="qi-thumb">' + thumbHtml + '</div>'
                + '<div class="qi-header-info">'
                + '<div class="qi-header-name">' + escHtml(r.name || '') + '</div>'
                + '<div class="qi-header-meta">'
                + '<span class="qi-price-tag">' + CURRENCY + ' ' + priceDisplay + '</span>'
                + '<span class="smart-import-badge smart-import-badge-source">' + sourceLabel + '</span>'
                + (catLabel ? '<span class="smart-import-badge smart-import-badge-source">' + escHtml(catLabel) + '</span>' : '')
                + '</div></div>'
                + '<i class="fa-solid fa-chevron-down qi-chevron"></i>'
                + '</div>'

                + '<div class="qi-body" id="' + itemId + '-body" role="region" style="display:none">'
                // Details
                + '<div class="form-section"><div class="form-section-title">Product details</div>'
                + '<div class="form-group"><label>Title</label>'
                + '<input type="text" class="form-control qi-field" data-field="name" value="' + escHtml(r.name || '') + '"></div>'
                + '<div class="form-group"><label>Short description</label>'
                + '<div class="rich-editor qi-short-desc-editor"></div>'
                + '<textarea class="form-control qi-field" data-field="description" style="display:none">' + escHtml(r.description || '') + '</textarea></div>'
                + '<div class="form-group"><label>Product description</label>'
                + '<div class="rich-editor qi-full-desc-editor"></div>'
                + '<textarea class="form-control qi-field" data-field="full_description" style="display:none"></textarea></div>'
                + '</div>'
                // Pricing
                + '<div class="form-section"><div class="form-section-title">Pricing</div>'
                + '<div class="form-group"><label>Selling price</label>'
                + '<div class="input-group"><span class="input-group-prefix">' + CURRENCY + '</span>'
                + '<input type="text" class="form-control price-input qi-field" data-field="price" value="' + (r.price || '') + '" inputmode="decimal">'
                + '</div></div>'
                + '<div class="form-group"><label>Original price</label>'
                + '<div class="input-group"><span class="input-group-prefix">' + CURRENCY + '</span>'
                + '<input type="text" class="form-control price-input qi-field" data-field="compare_price" value="' + (r.compare_price && r.compare_price > (r.price || 0) ? r.compare_price : '') + '" placeholder="Leave empty if no discount" inputmode="decimal">'
                + '</div><p class="form-hint">If set, customers see this crossed out next to the sale price</p>'
                + '</div></div>'
                // Photos
                + '<div class="form-section"><div class="form-section-title">Photos</div>'
                + '<p class="form-hint mb-md">First photo is the main one. Tap &times; to remove.</p>'
                + '<div class="image-gallery qi-images"></div></div>'
                // Categories
                + '<div class="form-section"><div class="form-section-title">Categories</div>'
                + '<div class="import-categories qi-categories"></div>'
                + '<div class="category-select-row" style="margin-top:12px">'
                + '<div class="category-picker-btn qi-open-cat-picker"><span class="picker-placeholder">Select a category</span><i class="fa-solid fa-chevron-down" style="font-size:12px;color:#C7C7CC"></i></div>'
                + '<button type="button" class="btn-add-category qi-add-cat-btn" title="Add category"><i class="fa-solid fa-plus"></i></button>'
                + '</div></div>'
                // Variations
                + '<div class="form-section qi-var-section"' + (!(r.variations && r.variations.length) ? ' style="display:none"' : '') + '>'
                + '<div class="form-section-title">Options</div>'
                + '<p class="form-hint" style="margin-bottom:16px">Sizes, colors, or styles. You can edit, remove, or add new ones.</p>'
                + '<div class="qi-variations"></div>'
                + '<button type="button" class="variation-add-group qi-add-var-group"><i class="fa-solid fa-plus"></i> Add option like Size or Color</button>'
                + '</div>'
                // Toggles
                + '<div class="form-section"><div class="form-section-title">Options</div>'
                + '<div class="form-toggle-row"><div><div class="form-toggle-label">Featured</div><p class="form-hint mt-xs">Show at the top of your store</p></div>'
                + '<label class="toggle-switch"><input type="checkbox" class="qi-field" data-field="is_featured"><span class="toggle-slider"></span></label></div>'
                + '<div class="form-toggle-row"><div><div class="form-toggle-label">Sold out</div><p class="form-hint mt-xs">Customers can\'t buy it</p></div>'
                + '<label class="toggle-switch"><input type="checkbox" class="qi-field" data-field="is_sold"><span class="toggle-slider"></span></label></div>'
                + '</div>'
                // Actions
                + '<div class="qi-actions">'
                + '<button type="button" class="btn btn-primary qi-save-btn" data-index="' + i + '"><i class="fa-solid fa-check"></i> Save to Store</button>'
                + '<button type="button" class="btn btn-ghost qi-remove-btn" data-index="' + i + '" title="Remove"><i class="fa-solid fa-trash-can"></i></button>'
                + '</div></div></div>';

            $acc.append(html);
            var $item = $acc.find('.qi-item').last();

            // Init editors
            var shortEd = initRichEditor($item.find('.qi-short-desc-editor')[0], $item.find('[data-field="description"]')[0], 'Short description...');
            if (shortEd) shortEd.setContent(r.description || '');

            var fullEd = initRichEditor($item.find('.qi-full-desc-editor')[0], $item.find('[data-field="full_description"]')[0], 'Full product description...');
            if (fullEd) fullEd.setContent(r.full_description || '');

            $item.find('.price-input').each(function() { TinyShop.initPriceInput($(this)); });
            renderQiImages($item, r.images || []);
            renderQiCategories($item, catLabel ? [catLabel] : []);
            if (r.variations && r.variations.length) renderVariationGroups($item.find('.qi-variations'), r.variations);
        });

        // All collapsed by default — user taps to expand

        // Bulk bar
        updateBulkBar();
    }

    // ── Accordion toggle ──

    $(document).on('click keydown', '.qi-header', function(e) {
        if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') return;
        if (e.type === 'keydown') e.preventDefault();
        var $item = $(this).closest('.qi-item');
        // Debounce — ignore if animation in progress
        if ($item.find('.qi-body').is(':animated')) return;
        var willExpand = !$item.hasClass('qi-expanded');
        $item.toggleClass('qi-expanded', willExpand);
        $(this).attr('aria-expanded', willExpand ? 'true' : 'false');
        if (willExpand) {
            $item.find('.qi-body').slideDown(200);
        } else {
            $item.find('.qi-body').slideUp(200);
        }
    });

    // ── Quick Add: Images ──

    function renderQiImages($item, images) {
        var $gallery = $item.find('.qi-images').empty();
        $item.data('images', images);
        if (!images.length) {
            $gallery.append('<p class="form-hint" style="margin:0">No images found</p>');
            return;
        }
        images.forEach(function(src, i) {
            var $wrap = $('<div class="image-gallery-item" data-img-index="' + i + '">'
                + '<img src="' + escHtml(src) + '" alt="" loading="lazy">'
                + '<button type="button" class="image-gallery-remove qi-remove-img" data-img-index="' + i + '">&times;</button></div>');
            $wrap.find('img').on('error', function() {
                var imgs = $item.data('images') || [];
                imgs = imgs.filter(function(u) { return u !== $wrap.find('img').attr('src'); });
                $item.data('images', imgs);
                $wrap.remove();
                $gallery.find('.image-gallery-item').each(function(j) {
                    $(this).attr('data-img-index', j).find('.qi-remove-img').attr('data-img-index', j);
                });
                if (!imgs.length) $gallery.append('<p class="form-hint" style="margin:0">No images found</p>');
                updateQiThumb($item);
            });
            $gallery.append($wrap);
        });
    }

    function updateQiThumb($item) {
        var imgs = $item.data('images') || [];
        var $thumb = $item.find('.qi-thumb');
        if (imgs.length) {
            $thumb.html('<img src="' + escHtml(imgs[0]) + '" alt="" loading="lazy">');
        } else {
            $thumb.html('<div class="qi-thumb-placeholder"><i class="fa-solid fa-image"></i></div>');
        }
    }

    $(document).on('click', '.qi-remove-img', function(e) {
        e.stopPropagation();
        var $item = $(this).closest('.qi-item');
        var idx = parseInt($(this).data('img-index'));
        var imgs = $item.data('images') || [];
        imgs.splice(idx, 1);
        renderQiImages($item, imgs);
    });

    // ── Quick Add: Categories ──

    function renderQiCategories($item, cats) {
        $item.data('categories', cats);
        var $cont = $item.find('.qi-categories').empty();
        renderCategoryChips($cont, cats, 'qi-remove-cat');
    }

    $(document).on('click', '.qi-remove-cat', function(e) {
        e.stopPropagation();
        var $item = $(this).closest('.qi-item');
        var cats = $item.data('categories') || [];
        cats.splice(parseInt($(this).data('cat-index')), 1);
        renderQiCategories($item, cats);
    });

    $(document).on('click', '.qi-open-cat-picker', function() {
        var $item = $(this).closest('.qi-item');
        openCategoryPicker($item.data('categories') || [], function(name) {
            var cats = $item.data('categories') || [];
            if (cats.indexOf(name) === -1) cats.push(name);
            renderQiCategories($item, cats);
        });
    });

    $(document).on('click', '.qi-add-cat-btn', function() {
        var $item = $(this).closest('.qi-item');
        openNewCategoryModal(function(cat) {
            _categoryTree.push({ id: cat.id, name: cat.name, children: [] });
            var cats = $item.data('categories') || [];
            if (cats.indexOf(cat.name) === -1) cats.push(cat.name);
            renderQiCategories($item, cats);
        });
    });

    // ── Quick Add: Variations ──

    function readQiVariations($item) {
        var groups = [];
        $item.find('.qi-variations .variation-group').each(function() {
            var name = $(this).find('.variation-group-name').val().trim();
            var opts = [];
            $(this).find('.variation-option-row').each(function() {
                var val = $(this).find('.variation-option-value').val().trim();
                var ps = $(this).find('.variation-option-price').val().replace(/,/g, '').trim();
                if (val) { var o = { value: val }; if (ps !== '') o.price = parseFloat(ps); opts.push(o); }
            });
            if (name && opts.length) groups.push({ name: name, options: opts });
        });
        return groups;
    }

    $('#quickAddAccordion').on('click', '.variation-group-remove', function() {
        var $item = $(this).closest('.qi-item');
        $(this).closest('.variation-group').remove();
        if (!$item.find('.variation-group').length) $item.find('.qi-var-section').hide();
    }).on('click', '.variation-option-remove', function() {
        $(this).closest('.variation-option-row').remove();
    }).on('click', '.variation-add-value', function() {
        appendVariationOption($(this).siblings('.variation-options'));
    }).on('click', '.qi-add-var-group', function() {
        var $item = $(this).closest('.qi-item');
        $item.find('.qi-var-section').show();
        var groups = readQiVariations($item);
        groups.push({ name: '', options: [{ value: '' }] });
        renderVariationGroups($item.find('.qi-variations'), groups);
        $item.find('.variation-group').last().find('.variation-group-name').focus();
    });

    // ── Quick Add: Remove item ──

    $(document).on('click', '.qi-remove-btn', function() {
        $(this).closest('.qi-item').slideUp(200, function() {
            $(this).remove();
            updateBulkBar();
        });
    });

    // ── Quick Add: Save Single ──

    $(document).on('click', '.qi-save-btn', function() {
        var $item = $(this).closest('.qi-item');
        var $btn = $(this);
        var product = buildQiProduct($item);
        if (!product) return;

        $btn.prop('disabled', true).html('<span class="btn-spinner"></span> Saving...');

        TinyShop.api('POST', '/api/import/smart-save', { products: [product] })
            .done(function(res) {
                if (res.saved_count > 0) {
                    var msg = (res.duplicates && res.duplicates.length)
                        ? product.name + ' added (similar product already exists)'
                        : product.name + ' added!';
                    TinyShop.toast(msg, (res.duplicates && res.duplicates.length) ? 'info' : 'success');
                    _quickSavedTotal += res.saved_count;
                    $item.slideUp(300, function() {
                        $(this).remove();
                        updateBulkBar();
                        if (!$('#quickAddAccordion .qi-item').length) showQuickSuccess(_quickSavedTotal);
                    });
                } else {
                    TinyShop.toast(res.errors && res.errors[0] ? res.errors[0].message : 'Failed', 'error');
                    $btn.prop('disabled', false).html('<i class="fa-solid fa-check"></i> Save to Store');
                }
            })
            .fail(function() {
                TinyShop.toast('Failed to save product', 'error');
                $btn.prop('disabled', false).html('<i class="fa-solid fa-check"></i> Save to Store');
            });
    });

    // ── Quick Add: Save All ──

    var _bulkConfirmTimer = null;

    $('#quickAddAllBtn').on('click', function() {
        var $btn = $(this);
        var $items = $('#quickAddAccordion .qi-item');
        if (!$items.length) return;

        // Two-step confirmation: first tap arms, second tap fires
        if (!$btn.hasClass('bulk-armed')) {
            $btn.addClass('bulk-armed btn-primary').removeClass('btn-outline');
            $btn.html('<i class="fa-solid fa-triangle-exclamation"></i> Tap again to add all products');

            // Reset after 3s if user doesn't confirm
            clearTimeout(_bulkConfirmTimer);
            _bulkConfirmTimer = setTimeout(function() {
                $btn.removeClass('bulk-armed btn-primary').addClass('btn-outline');
                $btn.html('<i class="fa-solid fa-check-double"></i> <span id="quickAddAllLabel">Add All (' + $items.length + ')</span>');
            }, 3000);
            return;
        }

        clearTimeout(_bulkConfirmTimer);
        $btn.removeClass('bulk-armed');

        var products = [];
        $items.each(function() {
            var p = buildQiProduct($(this));
            if (p) products.push(p);
        });
        if (!products.length) { TinyShop.toast('No valid products', 'error'); return; }

        $btn.prop('disabled', true).addClass('btn-primary').removeClass('btn-outline');
        $btn.html('<span class="btn-spinner"></span> Adding ' + products.length + '...');

        TinyShop.api('POST', '/api/import/smart-save', { products: products })
            .done(function(res) {
                _quickSavedTotal += (res.saved_count || 0);
                if (res.errors && res.errors.length) {
                    TinyShop.toast((res.saved_count || 0) + ' saved, ' + res.errors.length + ' failed', res.saved_count > 0 ? 'info' : 'error');
                }
                if (res.duplicates && res.duplicates.length) {
                    TinyShop.toast(res.duplicates.length + ' similar product(s) already existed', 'info');
                }
                $items.slideUp(200);
                setTimeout(function() { $items.remove(); showQuickSuccess(_quickSavedTotal); }, 300);
            })
            .fail(function() { TinyShop.toast('Failed to save products', 'error'); })
            .always(function() {
                $btn.prop('disabled', false).removeClass('btn-primary').addClass('btn-outline');
                $btn.html('<i class="fa-solid fa-check-double"></i> <span id="quickAddAllLabel">Add All</span>');
            });
    });

    // ── Quick Add: Build product payload ──

    function buildQiProduct($item) {
        syncAllEditors($item);

        var name = $item.find('[data-field="name"]').val().trim();
        var price = parseFloat(($item.find('[data-field="price"]').val() || '').replace(/,/g, '')) || 0;

        if (!name) { TinyShop.toast('Product name is required', 'error'); return null; }
        if (price <= 0) { TinyShop.toast('Price must be greater than 0 for "' + name + '"', 'error'); return null; }

        var compareStr = ($item.find('[data-field="compare_price"]').val() || '').replace(/,/g, '').trim();
        var comparePrice = compareStr ? parseFloat(compareStr) : null;
        if (comparePrice !== null && comparePrice <= price) comparePrice = null;

        // Validate variations
        var variations = readQiVariations($item);
        var hasIncomplete = false;
        $item.find('.qi-variations .variation-group').each(function() {
            var gname = $(this).find('.variation-group-name').val().trim();
            var opts = [];
            $(this).find('.variation-option-value').each(function() { if ($(this).val().trim()) opts.push(1); });
            if ((gname && !opts.length) || (!gname && opts.length)) hasIncomplete = true;
        });
        if (hasIncomplete) { TinyShop.toast('Fix incomplete variations for "' + name + '"', 'error'); return null; }

        var cats = $item.data('categories') || [];
        var idx = parseInt($item.data('index'));
        var r = _quickResults[idx] || {};

        return {
            name: name,
            price: price,
            compare_price: comparePrice,
            description: $item.find('[data-field="description"]').val().trim(),
            full_description: $item.find('[data-field="full_description"]').val().trim(),
            images: $item.data('images') || [],
            categories: cats,
            category_hint: cats[0] || r.category_hint || '',
            variations: variations,
            is_featured: $item.find('[data-field="is_featured"]').is(':checked') ? 1 : 0,
            is_sold: $item.find('[data-field="is_sold"]').is(':checked') ? 1 : 0,
            source: r.source || 'none',
            source_url: r.source_url || '',
            specs: r.specs || {}
        };
    }

    function syncAllEditors($item) {
        $item.find('.rich-editor-content').each(function() { $(this).trigger('blur'); });
    }

    function showQuickSuccess(count) {
        $('#quickAddBulkBar').hide();
        $('#quickAddSuccessCount').text(count);
        $('#quickAddSuccess').show();
        $('#quickAddText').val('');
    }

    function updateBulkBar() {
        var count = $('#quickAddAccordion .qi-item').length;
        if (count > 1) {
            $('#quickAddAllLabel').text('Add All (' + count + ')');
            $('#quickAddBulkBar').show();
        } else {
            $('#quickAddBulkBar').hide();
        }

        // All items removed — show empty state to retry with a new query
        if (count === 0 && !$('#quickAddSuccess').is(':visible')) {
            showQuickEmpty();
        }
    }

    function showQuickEmpty() {
        var $acc = $('#quickAddAccordion');
        $acc.html(
            '<div class="qi-empty">'
            + '<div class="qi-empty-icon"><i class="fa-solid fa-broom"></i></div>'
            + '<div class="qi-empty-text">All products removed</div>'
            + '<p class="qi-empty-hint">Edit your search below and try again with different products.</p>'
            + '<button type="button" id="quickRetryBtn" class="btn btn-primary qi-success-btn">'
            + '<i class="fa-solid fa-arrow-rotate-left"></i> Try Again</button>'
            + '</div>'
        );
        $('#quickAddBulkBar').hide();
    }

    $(document).on('click', '#quickRetryBtn', function() {
        $('#quickAddResults').hide();
        $('#quickAddAccordion').empty();
        var $textarea = $('#quickAddText');
        $textarea.focus();
        // Select the text so user can easily replace it
        $textarea[0].select();
    });

    // ════════════════════════════════════════════════════
    // § Shared UI Helpers
    // ════════════════════════════════════════════════════

    /** Render category chips into a container. */
    function renderCategoryChips($container, cats, removeClass) {
        $container.empty();
        if (!cats.length) {
            $container.append('<p class="form-hint" style="margin:0">No categories yet</p>');
            return;
        }
        cats.forEach(function(c, i) {
            $container.append(
                '<span class="import-cat-chip"><i class="fa-solid fa-tag"></i> ' + escHtml(c)
                + '<button type="button" class="import-cat-remove ' + removeClass + '" data-cat-index="' + i + '" data-index="' + i + '">&times;</button></span>'
            );
        });
    }

    /** Render variation groups into a container. */
    function renderVariationGroups($container, groups) {
        $container.empty();
        (groups || []).forEach(function(group, gi) {
            var html = '<div class="variation-group" data-gidx="' + gi + '">'
                + '<div class="variation-group-header">'
                + '<input type="text" class="variation-group-name" placeholder="Option name (e.g. Size)" value="' + escHtml(group.name || '') + '" autocomplete="off">'
                + '<button type="button" class="variation-group-remove" title="Remove">&times;</button></div>'
                + '<div class="variation-options">';
            (group.options || []).forEach(function(opt) {
                var pv = (opt.price !== null && opt.price !== undefined) ? opt.price : '';
                html += '<div class="variation-option-row">'
                    + '<input type="text" class="variation-option-value" placeholder="Value" value="' + escHtml(opt.value || '') + '" autocomplete="off">'
                    + '<input type="text" class="variation-option-price price-input" placeholder="Price" inputmode="decimal" value="' + escHtml(String(pv)) + '" autocomplete="off">'
                    + '<button type="button" class="variation-option-remove" title="Remove">&times;</button></div>';
            });
            html += '</div><button type="button" class="variation-add-value">+ Add value</button></div>';
            $container.append(html);
        });
        $container.find('.price-input').each(function() { TinyShop.initPriceInput($(this)); });
    }

    /** Append a new option row to a variation group. */
    function appendVariationOption($optionsContainer) {
        $optionsContainer.append(
            '<div class="variation-option-row">'
            + '<input type="text" class="variation-option-value" placeholder="Value" autocomplete="off">'
            + '<input type="text" class="variation-option-price price-input" placeholder="Price" inputmode="decimal" autocomplete="off">'
            + '<button type="button" class="variation-option-remove" title="Remove">&times;</button></div>'
        );
        TinyShop.initPriceInput($optionsContainer.find('.price-input').last());
        $optionsContainer.find('.variation-option-value').last().focus();
    }

    /** Open the shared category picker modal. */
    function openCategoryPicker(existingCats, onSelect) {
        var html = '<div class="category-picker-search">'
            + '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>'
            + '<input type="text" id="catPickerSearch" placeholder="Search categories..." autocomplete="off">'
            + '</div><div class="category-picker-list">';

        if (!_categoryTree.length) {
            html += '<p class="form-hint" style="padding:20px 16px;margin:0">No categories yet. Use the + button to create one.</p>';
        }

        _categoryTree.forEach(function(parent) {
            html += '<div class="category-picker-group">';
            html += buildPickerItem(parent.name, existingCats, true);
            (parent.children || []).forEach(function(child) {
                html += buildPickerItem(child.name, existingCats, false);
            });
            html += '</div>';
        });
        html += '</div>';

        TinyShop.openModal('Select Category', html);

        // Search with debounce
        var _t;
        $('#catPickerSearch').on('input', function() {
            var q = $(this).val().trim().toLowerCase();
            clearTimeout(_t);
            _t = setTimeout(function() {
                var $list = $('#modalBody .category-picker-list');
                if (!q) { $list.find('.category-picker-group, .category-picker-item').show(); return; }
                $list.find('.category-picker-group').each(function() {
                    var $g = $(this);
                    var parentMatch = ($g.find('.category-picker-item-parent').data('search-name') || '').indexOf(q) !== -1;
                    var anyChild = false;
                    $g.find('.category-picker-item-child').each(function() {
                        var m = ($(this).data('search-name') || '').indexOf(q) !== -1;
                        $(this).toggle(m || parentMatch);
                        if (m) anyChild = true;
                    });
                    $g.find('.category-picker-item-parent').toggle(parentMatch || anyChild);
                    $g.toggle(parentMatch || anyChild);
                });
            }, 80);
        }).focus();

        $('#modalBody').on('click', '.category-picker-item', function() {
            onSelect($(this).data('name'));
            TinyShop.closeModal();
        });
    }

    function buildPickerItem(name, existing, isParent) {
        var sel = existing.indexOf(name) !== -1;
        var cls = isParent ? 'category-picker-item-parent' : 'category-picker-item-child';
        return '<div class="category-picker-item ' + cls + (sel ? ' selected' : '') + '" data-name="' + escHtml(name) + '" data-search-name="' + escHtml(name.toLowerCase()) + '">'
            + '<span>' + escHtml(name) + '</span>'
            + '<span class="category-picker-check"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg></span></div>';
    }

    /** Open the new category creation modal. */
    function openNewCategoryModal(onCreated) {
        var html = '<form id="newCatForm" autocomplete="off">'
            + '<div class="form-group"><label for="newCatName">Category Name</label>'
            + '<input type="text" class="form-control" id="newCatName" placeholder="e.g. Accessories" required autofocus autocomplete="off"></div>'
            + '<button type="submit" class="btn btn-primary" id="saveCatBtn" style="width:100%;min-height:52px;font-size:1rem;font-weight:600;border-radius:14px">Add Category</button>'
            + '</form>';
        TinyShop.openModal('New Category', html);

        $('#newCatForm').on('submit', function(e) {
            e.preventDefault();
            var name = $('#newCatName').val().trim();
            if (!name) return;
            var $btn = $('#saveCatBtn').prop('disabled', true).text('Adding...');
            TinyShop.api('POST', '/api/import/save-category', { name: name })
                .done(function(res) {
                    onCreated(res.category);
                    TinyShop.toast('Category added');
                    TinyShop.closeModal();
                })
                .fail(function(xhr) {
                    TinyShop.toast(xhr.responseJSON ? xhr.responseJSON.message : 'Failed', 'error');
                    $btn.prop('disabled', false).text('Add Category');
                });
        });
    }

})(jQuery);
