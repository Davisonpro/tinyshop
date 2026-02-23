{extends file="layouts/dashboard.tpl"}

{block name="content"}
<div class="dash-topbar">
    <span class="dash-topbar-title">Import Product</span>
</div>

<div class="dash-form">
    <div class="form-section">
        <div class="form-section-title">Product source</div>
        <div class="form-group">
            <div class="import-source-tabs">
                <button type="button" class="import-source-tab active" data-tab="link">Link</button>
                <button type="button" class="import-source-tab" data-tab="html">Page Source</button>
            </div>
            <div id="tabLink" class="import-tab-panel">
                <div class="import-url-row">
                    <input type="url" id="importUrl" class="form-control" placeholder="https://example.com/product/...">
                    <button type="button" id="fetchBtn" class="btn btn-primary import-fetch-btn">
                        <span class="fetch-btn-label"><i class="fa-solid fa-magnifying-glass"></i> Fetch</span>
                        <span class="fetch-btn-loading" style="display:none"><span class="btn-spinner"></span> Fetching...</span>
                    </button>
                </div>
                <p class="form-hint">Paste any product link from a supported store</p>
            </div>
            <div id="tabHtml" class="import-tab-panel" style="display:none">
                <textarea id="pasteHtml" class="form-control" rows="5" placeholder="Right-click the product page > View Page Source > Select All > Paste here"></textarea>
                <p class="form-hint">Open the product page in your browser, copy the page source, and paste it above.</p>
                <button type="button" id="parseHtmlBtn" class="btn btn-primary import-fetch-btn" style="margin-top:8px">
                    <span class="parse-btn-label"><i class="fa-solid fa-code"></i> Parse</span>
                    <span class="parse-btn-loading" style="display:none"><span class="btn-spinner"></span> Parsing...</span>
                </button>
            </div>
        </div>
    </div>

    {* Preview (hidden until fetch) *}
    <div id="importPreview" style="display:none">
        <div id="reimportNotice" class="import-reimport-notice" style="display:none">
            <i class="fa-solid fa-circle-info"></i>
            <span>This product was previously imported as <strong id="reimportName"></strong>.</span>
        </div>

        <div class="form-section">
            <div class="form-section-title">Product details</div>
            <div class="form-group">
                <label>Title</label>
                <input type="text" id="prevTitle" class="form-control" placeholder="Product name">
            </div>
            <div class="form-group">
                <label>Short description</label>
                <div class="rich-editor" id="importShortDescEditor"></div>
                <textarea id="prevShortDescription" class="form-control" style="display:none"></textarea>
            </div>
            <div class="form-group">
                <label>Product description</label>
                <div class="rich-editor" id="importRichEditor"></div>
                <textarea id="prevDescription" class="form-control" style="display:none"></textarea>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title">Pricing</div>
            <div class="form-group">
                <label>Selling price</label>
                <div class="input-group">
                    <span class="input-group-prefix" id="prevCurrencyPrefix">KES</span>
                    <input type="text" id="prevPrice" class="form-control price-input" placeholder="0" inputmode="decimal">
                </div>
            </div>
            <div class="form-group">
                <label>Original price</label>
                <div class="input-group">
                    <span class="input-group-prefix" id="prevCompareCurrencyPrefix">KES</span>
                    <input type="text" id="prevComparePrice" class="form-control price-input" placeholder="Leave empty if no discount" inputmode="decimal">
                </div>
                <p class="form-hint">If set, customers see this crossed out next to the sale price</p>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title">Photos</div>
            <p class="form-hint mb-md">First photo is the main one. Tap the &times; to remove.</p>
            <div class="image-gallery" id="prevImages"></div>
        </div>

        <div class="form-section">
            <div class="form-section-title">Categories</div>
            <div id="prevCategories" class="import-categories"></div>
            <div class="category-select-row" style="margin-top:12px">
                <div class="category-picker-btn" id="openImportCatPicker">
                    <span id="importCatPickerLabel" class="picker-placeholder">Select a category</span>
                    <i class="fa-solid fa-chevron-down" style="font-size:12px;color:#C7C7CC"></i>
                </div>
                <button type="button" class="btn-add-category" id="addImportCatBtn" title="Add category">
                    <i class="fa-solid fa-plus"></i>
                </button>
            </div>
        </div>

        <div id="prevVariationsWrap" style="display:none">
            <div class="form-section">
                <div class="form-section-title">Options</div>
                <p class="form-hint" style="margin-bottom:16px">Sizes, colors, or styles imported from the source. You can edit, remove, or add new ones.</p>
                <div id="prevVariations"></div>
                <button type="button" class="variation-add-group" id="addImportVarGroup">
                    <i class="fa-solid fa-plus"></i>
                    Add option like Size or Color
                </button>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title">Options</div>
            <div class="form-toggle-row">
                <div>
                    <div class="form-toggle-label">Featured</div>
                    <p class="form-hint mt-xs">Show this product at the top of your store</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" id="prevIsFeatured">
                    <span class="toggle-slider"></span>
                </label>
            </div>
            <div class="form-toggle-row">
                <div>
                    <div class="form-toggle-label">Sold out</div>
                    <p class="form-hint mt-xs">Mark as sold out so customers can't buy it</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" id="prevIsSold">
                    <span class="toggle-slider"></span>
                </label>
            </div>
        </div>

        <div class="form-section">
            <div class="import-source-row">
                <i class="fa-solid fa-globe"></i>
                <span>Imported from</span>
                <span id="prevPlatform" class="badge badge-muted"></span>
                <span id="prevCurrency" class="badge badge-muted"></span>
            </div>
        </div>

        <button type="button" id="saveBtn" class="btn btn-primary" style="gap:8px">
            <i class="fa-solid fa-check"></i> Save to Store
        </button>
    </div>
</div>

{/block}

{block name="extra_scripts"}
<script>
{literal}
// Reusable rich editor factory
function initImportEditor(editorId, textareaId, placeholder) {
    var editor = document.getElementById(editorId);
    var textarea = document.getElementById(textareaId);
    if (!editor) return null;

    var content = document.createElement('div');
    content.className = 'rich-editor-content';
    content.contentEditable = true;
    content.setAttribute('data-placeholder', placeholder);
    editor.appendChild(content);

    var toolbar = document.createElement('div');
    toolbar.className = 'rich-editor-toolbar';

    var actions = [
        { icon: '<span style="font-weight:800;font-size:14px">B</span>', cmd: 'bold', title: 'Bold' },
        { icon: '<span style="font-style:italic;font-size:14px;font-family:Georgia,serif">I</span>', cmd: 'italic', title: 'Italic' },
        { icon: '<span style="font-weight:700;font-size:13px">H</span>', cmd: 'heading', title: 'Heading', query: 'h3' },
        { icon: '<i class="fa-solid fa-list-ul"></i>', cmd: 'insertUnorderedList', title: 'Bullet list' },
        { icon: '<i class="fa-solid fa-list-ol"></i>', cmd: 'insertOrderedList', title: 'Numbered list' }
    ];

    var buttons = [];
    actions.forEach(function(a, i) {
        if (i === 3) {
            var sep = document.createElement('div');
            sep.className = 'rich-editor-sep';
            toolbar.appendChild(sep);
        }
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'rich-editor-btn';
        btn.innerHTML = a.icon;
        btn.title = a.title;
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
    editor.appendChild(toolbar);

    function sync() {
        var html = content.innerHTML;
        textarea.value = (!html || html === '<br>' || html === '<p><br></p>') ? '' : html;
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
    content.addEventListener('keyup', updateActive);
    content.addEventListener('mouseup', function() { setTimeout(updateActive, 10); });

    content.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && !e.shiftKey) {
            if (e.key === 'b') { e.preventDefault(); document.execCommand('bold'); sync(); updateActive(); }
            if (e.key === 'i') { e.preventDefault(); document.execCommand('italic'); sync(); updateActive(); }
        }
    });

    content.addEventListener('paste', function(e) {
        e.preventDefault();
        var html = (e.clipboardData || window.clipboardData).getData('text/html');
        var text = (e.clipboardData || window.clipboardData).getData('text/plain');
        if (html) {
            var tmp = document.createElement('div');
            tmp.innerHTML = html;
            tmp.querySelectorAll('*').forEach(function(el) {
                el.removeAttribute('style');
                el.removeAttribute('class');
                el.removeAttribute('id');
            });
            var allowed = ['P','BR','B','STRONG','I','EM','UL','OL','LI','H2','H3','A'];
            tmp.querySelectorAll('*').forEach(function(el) {
                if (allowed.indexOf(el.tagName) === -1) el.replaceWith(document.createTextNode(el.textContent));
            });
            document.execCommand('insertHTML', false, tmp.innerHTML);
        } else {
            document.execCommand('insertText', false, text);
        }
        sync();
    });

    return { setContent: function(html) { content.innerHTML = html || ''; sync(); } };
}

var importShortDescRich = initImportEditor('importShortDescEditor', 'prevShortDescription', 'Short description...');
var importDescRich = initImportEditor('importRichEditor', 'prevDescription', 'Product description...');

window._setImportEditorContent = function(html) { if (importDescRich) importDescRich.setContent(html); };
window._setImportShortDescContent = function(html) { if (importShortDescRich) importShortDescRich.setContent(html); };

(function($) {
    var fetchedData = null;
    var _categoryTree = [];

    // Load categories on init
    TinyShop.api('GET', '/api/import/categories').done(function(res) {
        _categoryTree = res.categories || [];
    });

    function escHtml(s) {
        if (!s) return '';
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    // ── Source tabs ──
    $('.import-source-tab').on('click', function() {
        var tab = $(this).data('tab');
        $('.import-source-tab').removeClass('active');
        $(this).addClass('active');
        if (tab === 'link') {
            $('#tabLink').show();
            $('#tabHtml').hide();
        } else {
            $('#tabLink').hide();
            $('#tabHtml').show();
        }
    });

    // ── Fetch ──
    $('#fetchBtn').on('click', function() {
        var url = $('#importUrl').val().trim();
        if (!url) {
            TinyShop.toast('Please enter a product URL', 'error');
            return;
        }

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
            .always(function() {
                $btn.prop('disabled', false);
                $btn.find('.fetch-btn-loading').hide();
                $btn.find('.fetch-btn-label').show();
            });
    });

    // ── Parse pasted HTML ──
    $('#parseHtmlBtn').on('click', function() {
        var html = $('#pasteHtml').val().trim();
        if (!html || html.length < 100) {
            TinyShop.toast('Please paste the full page source', 'error');
            return;
        }

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
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to parse HTML';
                TinyShop.toast(msg, 'error');
            })
            .always(function() {
                $btn.prop('disabled', false);
                $btn.find('.parse-btn-loading').hide();
                $btn.find('.parse-btn-label').show();
            });
    });

    // ── Render Preview ──
    function renderPreview(p) {
        $('#prevTitle').val(p.title || '');
        if (window._setImportShortDescContent) window._setImportShortDescContent(p.description || '');
        if (window._setImportEditorContent) window._setImportEditorContent(p.full_description || '');
        $('#prevPrice').val(p.price || '');
        $('#prevComparePrice').val(p.compare_price || '');
        $('#prevPlatform').text(p.source_platform || '');
        $('#prevCurrency').text(p.currency || '');
        $('#prevCurrencyPrefix').text(p.currency || 'KES');
        $('#prevCompareCurrencyPrefix').text(p.currency || 'KES');
        $('#prevIsFeatured').prop('checked', !!p.is_featured);
        $('#prevIsSold').prop('checked', !!p.is_sold);

        if (p.existing_product_id) {
            $('#reimportNotice').show();
            $('#reimportName').text(p.existing_product_name || 'ID ' + p.existing_product_id);
        } else {
            $('#reimportNotice').hide();
        }

        renderCategories();

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

        renderVariations();

        TinyShop.initPriceInput($('#prevPrice'));
        TinyShop.initPriceInput($('#prevComparePrice'));
    }

    // Convert flat WooCommerce variations into grouped format for the UI
    function flatToGroups(variations) {
        var groups = {};
        var groupOrder = [];
        variations.forEach(function(v) {
            var attrs = v.attributes || {};
            var price = v.price != null ? v.price : null;
            var keys = Object.keys(attrs);
            if (!keys.length) {
                // No attributes — treat the whole variation as a single-group option
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

    // Grouped variations stored separately from the flat fetchedData.variations
    var _importVarGroups = [];

    function renderVariations() {
        var p = fetchedData;
        if (!p || !p.variations || !p.variations.length) {
            if (!_importVarGroups.length) {
                $('#prevVariationsWrap').hide();
                return;
            }
        }
        // Convert flat → grouped on first render, then use _importVarGroups for edits
        if (!_importVarGroups.length && p && p.variations && p.variations.length) {
            _importVarGroups = flatToGroups(p.variations);
        }
        $('#prevVariationsWrap').show();
        var $container = $('#prevVariations').empty();
        _importVarGroups.forEach(function(group, gi) {
            var html = '<div class="variation-group" data-gidx="' + gi + '">'
                + '<div class="variation-group-header">'
                + '<input type="text" class="variation-group-name" placeholder="Option name (e.g. Size)" value="' + escHtml(group.name) + '" autocomplete="off">'
                + '<button type="button" class="variation-group-remove" title="Remove">&times;</button>'
                + '</div>'
                + '<div class="variation-options">';
            (group.options || []).forEach(function(opt) {
                var priceVal = (opt.price !== null && opt.price !== undefined) ? opt.price : '';
                html += '<div class="variation-option-row">'
                    + '<input type="text" class="variation-option-value" placeholder="Value name" value="' + escHtml(opt.value || '') + '" autocomplete="off">'
                    + '<input type="text" class="variation-option-price price-input" placeholder="Price" inputmode="decimal" value="' + escHtml(String(priceVal)) + '" autocomplete="off">'
                    + '<button type="button" class="variation-option-remove" title="Remove">&times;</button>'
                    + '</div>';
            });
            html += '</div>'
                + '<button type="button" class="variation-add-value">+ Add value</button>'
                + '</div>';
            $container.append(html);
        });
        // Init price formatting on all price inputs
        $container.find('.price-input').each(function() { TinyShop.initPriceInput($(this)); });
    }

    function readImportVarGroups() {
        _importVarGroups = [];
        $('#prevVariations .variation-group').each(function() {
            var name = $(this).find('.variation-group-name').val().trim();
            var opts = [];
            $(this).find('.variation-option-row').each(function() {
                var val = $(this).find('.variation-option-value').val().trim();
                var priceStr = $(this).find('.variation-option-price').val().replace(/,/g, '').trim();
                if (val) {
                    var opt = { value: val };
                    if (priceStr !== '') opt.price = parseFloat(priceStr);
                    opts.push(opt);
                }
            });
            if (name && opts.length) _importVarGroups.push({ name: name, options: opts });
        });
    }

    // ── Categories ──
    function renderCategories() {
        var $cats = $('#prevCategories').empty();
        var cats = fetchedData ? (fetchedData.categories || []) : [];
        if (cats.length) {
            cats.forEach(function(c, i) {
                $cats.append(
                    '<span class="import-cat-chip">'
                    + '<i class="fa-solid fa-tag"></i> ' + escHtml(c)
                    + '<button type="button" class="import-cat-remove" data-index="' + i + '">&times;</button>'
                    + '</span>'
                );
            });
        } else {
            $cats.append('<p class="form-hint" style="margin:0">No categories yet</p>');
        }
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
        var existingCats = fetchedData ? (fetchedData.categories || []) : [];
        var html = '<div class="category-picker-search">'
            + '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>'
            + '<input type="text" id="importCatSearchInput" placeholder="Search categories..." autocomplete="off">'
            + '</div>';
        html += '<div class="category-picker-list">';
        if (!_categoryTree.length) {
            html += '<p class="form-hint" style="padding:20px 16px;margin:0">No categories yet. Use the + button to create one.</p>';
        }
        _categoryTree.forEach(function(parent) {
            var already = existingCats.indexOf(parent.name) !== -1;
            html += '<div class="category-picker-group" data-search-parent="' + escHtml(parent.name.toLowerCase()) + '">';
            html += '<div class="category-picker-item category-picker-item-parent' + (already ? ' selected' : '') + '" data-name="' + escHtml(parent.name) + '" data-search-name="' + escHtml(parent.name.toLowerCase()) + '">'
                + '<span>' + escHtml(parent.name) + '</span>'
                + '<span class="category-picker-check"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg></span>'
                + '</div>';
            (parent.children || []).forEach(function(child) {
                var childAlready = existingCats.indexOf(child.name) !== -1;
                html += '<div class="category-picker-item category-picker-item-child' + (childAlready ? ' selected' : '') + '" data-name="' + escHtml(child.name) + '" data-search-name="' + escHtml(child.name.toLowerCase()) + '">'
                    + '<span>' + escHtml(child.name) + '</span>'
                    + '<span class="category-picker-check"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg></span>'
                    + '</div>';
            });
            html += '</div>';
        });
        html += '</div>';
        TinyShop.openModal('Select Category', html);

        var _t;
        $('#importCatSearchInput').on('input', function() {
            var q = $(this).val().trim().toLowerCase();
            clearTimeout(_t);
            _t = setTimeout(function() {
                var $list = $('#modalBody .category-picker-list');
                if (!q) { $list.find('.category-picker-group, .category-picker-item').show(); return; }
                $list.find('.category-picker-group').each(function() {
                    var $group = $(this);
                    var parentMatch = ($group.find('.category-picker-item-parent').data('search-name') || '').indexOf(q) !== -1;
                    var anyChildMatch = false;
                    $group.find('.category-picker-item-child').each(function() {
                        var match = ($(this).data('search-name') || '').indexOf(q) !== -1;
                        $(this).toggle(match || parentMatch);
                        if (match) anyChildMatch = true;
                    });
                    $group.find('.category-picker-item-parent').toggle(parentMatch || anyChildMatch);
                    $group.toggle(parentMatch || anyChildMatch);
                });
            }, 80);
        }).focus();

        $('#modalBody').on('click', '.category-picker-item', function() {
            var name = $(this).data('name');
            if (!fetchedData) fetchedData = { categories: [] };
            if (!fetchedData.categories) fetchedData.categories = [];
            if (fetchedData.categories.indexOf(name) === -1) {
                fetchedData.categories.push(name);
            }
            renderCategories();
            TinyShop.closeModal();
        });
    });

    // ── Add new category ──
    $('#addImportCatBtn').on('click', function() {
        var html = '<form id="newImportCatForm" autocomplete="off">'
            + '<div class="form-group"><label for="newImportCatName">Category Name</label>'
            + '<input type="text" class="form-control" id="newImportCatName" placeholder="e.g. Accessories" required autofocus autocomplete="off">'
            + '</div>'
            + '<button type="submit" class="btn btn-primary" id="saveImportCatBtn" style="width:100%;min-height:52px;font-size:1rem;font-weight:600;border-radius:14px;border:none;cursor:pointer;font-family:inherit;">Add Category</button>'
            + '</form>';
        TinyShop.openModal('New Category', html);
        $('#newImportCatForm').on('submit', function(e) {
            e.preventDefault();
            var name = $('#newImportCatName').val().trim();
            if (!name) return;
            var $btn = $('#saveImportCatBtn').prop('disabled', true).text('Adding...');
            TinyShop.api('POST', '/api/import/save-category', { name: name })
                .done(function(res) {
                    var cat = res.category;
                    _categoryTree.push({ id: cat.id, name: cat.name, children: [] });
                    if (!fetchedData) fetchedData = { categories: [] };
                    if (!fetchedData.categories) fetchedData.categories = [];
                    if (fetchedData.categories.indexOf(cat.name) === -1) {
                        fetchedData.categories.push(cat.name);
                    }
                    renderCategories();
                    TinyShop.toast('Category added');
                    TinyShop.closeModal();
                })
                .fail(function(xhr) {
                    var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to add category';
                    TinyShop.toast(msg, 'error');
                    $btn.prop('disabled', false).text('Add Category');
                });
        });
    });

    // ── Variation group editing ──
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
        var $options = $(this).siblings('.variation-options');
        var row = '<div class="variation-option-row">'
            + '<input type="text" class="variation-option-value" placeholder="Value name" autocomplete="off">'
            + '<input type="text" class="variation-option-price price-input" placeholder="Price" inputmode="decimal" autocomplete="off">'
            + '<button type="button" class="variation-option-remove" title="Remove">&times;</button>'
            + '</div>';
        $options.append(row);
        TinyShop.initPriceInput($options.find('.price-input').last());
        $options.find('.variation-option-value').last().focus();
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

    // Remove image
    $(document).on('click', '#prevImages .image-gallery-remove', function(e) {
        e.stopPropagation();
        var idx = $(this).data('index');
        if (fetchedData && fetchedData.images) {
            fetchedData.images.splice(idx, 1);
            renderPreview(fetchedData);
        }
    });

    // ── Save ──
    $('#saveBtn').on('click', function() {
        var title = $('#prevTitle').val().trim();
        if (!title) {
            TinyShop.toast('Product title is required', 'error');
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).html('<span class="btn-spinner"></span> Importing...');

        var rawPrice = ($('#prevPrice').val() || '0').replace(/,/g, '');
        var rawCompare = ($('#prevComparePrice').val() || '').replace(/,/g, '');

        // Read variation groups from the DOM
        readImportVarGroups();

        var payload = {
            title: title,
            description: $('#prevShortDescription').val().trim(),
            full_description: $('#prevDescription').val().trim(),
            price: parseFloat(rawPrice) || 0,
            compare_price: rawCompare ? parseFloat(rawCompare) : null,
            categories: fetchedData ? fetchedData.categories : [],
            images: fetchedData ? fetchedData.images : [],
            variations: _importVarGroups,
            source_url: fetchedData ? (fetchedData.source_url || '') : '',
            is_featured: $('#prevIsFeatured').is(':checked') ? 1 : 0,
            is_sold: $('#prevIsSold').is(':checked') ? 1 : 0
        };

        TinyShop.api('POST', '/api/import/save', payload)
            .done(function(res) {
                TinyShop.toast('Product imported!');
                fetchedData = null;
                $('#importPreview').hide();
                $('#importUrl').val('');
            })
            .fail(function(xhr) {
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to save product';
                TinyShop.toast(msg, 'error');
            })
            .always(function() {
                $btn.prop('disabled', false).html('<i class="fa-solid fa-check"></i> Save to Store');
            });
    });
})($);
{/literal}
</script>
{/block}
