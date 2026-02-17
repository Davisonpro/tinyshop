{extends file="layouts/admin.tpl"}

{block name="content"}
<div class="dash-topbar">
    <span class="dash-topbar-title">Import Product</span>
</div>

<div class="dash-form">
    {* Step 1: Seller + URL *}
    <div class="form-section">
        <div class="form-section-title">Where to import</div>
        <div class="form-group">
            <label>Seller</label>
            <input type="hidden" id="importSellerId" value="">
            <div class="category-select-row">
                <div class="category-picker-btn" id="openSellerPicker">
                    <span id="sellerPickerLabel" class="picker-placeholder">Select a seller</span>
                    <i class="fa-solid fa-chevron-down" style="font-size:12px;color:#C7C7CC"></i>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label>Product URL</label>
            <div class="import-url-row">
                <input type="url" id="importUrl" class="form-control" placeholder="https://example.com/product/...">
                <button type="button" id="fetchBtn" class="btn btn-primary import-fetch-btn">
                    <span class="fetch-btn-label"><i class="fa-solid fa-magnifying-glass"></i> Fetch</span>
                    <span class="fetch-btn-loading" style="display:none"><span class="btn-spinner"></span> Fetching...</span>
                </button>
            </div>
            <p class="form-hint">Paste any product link from a supported store</p>
        </div>
    </div>

    {* Step 2: Preview (hidden until fetch) *}
    <div id="importPreview" style="display:none">
        <div class="form-section">
            <div class="form-section-title">Product details</div>
            <div class="form-group">
                <label>Title</label>
                <input type="text" id="prevTitle" class="form-control" placeholder="Product name">
            </div>
            <div class="form-group">
                <label>Description</label>
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
        </div>

        <div id="prevVariationsWrap" style="display:none">
            <div class="form-section">
                <div class="form-section-title">Variations</div>
                <div id="prevVariations" class="import-variations-wrap"></div>
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
var _sellersData = [{foreach $sellers as $s}{ldelim}"id":{$s.id},"store_name":"{$s.store_name|escape:'javascript'}","name":"{$s.name|escape:'javascript'}","email":"{$s.email|escape:'javascript'}"{rdelim}{if !$s@last},{/if}{/foreach}];
</script>
<script>
{literal}
// Rich text editor for imported description
(function() {
    var editor = document.getElementById('importRichEditor');
    var textarea = document.getElementById('prevDescription');
    if (!editor) return;

    var content = document.createElement('div');
    content.className = 'rich-editor-content';
    content.contentEditable = true;
    content.setAttribute('data-placeholder', 'Product description...');
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

    window._setImportEditorContent = function(html) { content.innerHTML = html || ''; sync(); };
})();

(function($) {
    var fetchedData = null;

    function escHtml(s) {
        if (!s) return '';
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    // ── Seller Picker (modal, searchable — same UX as category picker) ──
    function openSellerPicker() {
        var currentVal = $('#importSellerId').val();
        var html = '<div class="category-picker-search">'
            + '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>'
            + '<input type="text" id="sellerSearchInput" placeholder="Search sellers..." autocomplete="off">'
            + '</div>';
        html += '<div class="category-picker-list">';
        _sellersData.forEach(function(s) {
            var selected = String(s.id) === String(currentVal) ? ' selected' : '';
            var label = escHtml(s.store_name || s.name);
            var sub = s.email ? '<span class="import-picker-sub">' + escHtml(s.email) + '</span>' : '';
            html += '<div class="category-picker-item' + selected + '" data-id="' + s.id
                + '" data-search-name="' + escHtml((s.store_name + ' ' + s.name + ' ' + s.email).toLowerCase()) + '">'
                + '<div class="import-picker-info"><span>' + label + '</span>' + sub + '</div>'
                + '<span class="category-picker-check"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg></span>'
                + '</div>';
        });
        html += '</div>';

        TinyShop.openModal('Select Seller', html);

        var _t;
        $('#sellerSearchInput').on('input', function() {
            var q = $(this).val().trim().toLowerCase();
            clearTimeout(_t);
            _t = setTimeout(function() {
                var $list = $('#modalBody .category-picker-list');
                if (!q) { $list.find('.category-picker-item').show(); return; }
                $list.find('.category-picker-item').each(function() {
                    var match = ($(this).data('search-name') || '').indexOf(q) !== -1;
                    $(this).toggle(match);
                });
            }, 80);
        }).focus();

        $('#modalBody').on('click', '.category-picker-item', function() {
            var id = $(this).data('id');
            var name = $(this).find('.import-picker-info span:first').text().trim();
            $('#importSellerId').val(id);
            $('#sellerPickerLabel').text(name).removeClass('picker-placeholder');
            TinyShop.closeModal();
        });
    }

    $('#openSellerPicker').on('click', openSellerPicker);

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

        TinyShop.api('POST', '/api/admin/import/fetch', { url: url })
            .done(function(res) {
                fetchedData = res.product;
                renderPreview(res.product);
                $('#importPreview').show();
            })
            .fail(function(xhr) {
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to fetch product';
                TinyShop.toast(msg, 'error');
            })
            .always(function() {
                $btn.prop('disabled', false);
                $btn.find('.fetch-btn-loading').hide();
                $btn.find('.fetch-btn-label').show();
            });
    });

    // ── Render Preview ──
    function renderPreview(p) {
        $('#prevTitle').val(p.title || '');
        if (window._setImportEditorContent) window._setImportEditorContent(p.description || '');
        $('#prevPrice').val(p.price || '');
        $('#prevComparePrice').val(p.compare_price || '');
        $('#prevPlatform').text(p.source_platform || '');
        $('#prevCurrency').text(p.currency || '');
        $('#prevCurrencyPrefix').text(p.currency || 'KES');
        $('#prevCompareCurrencyPrefix').text(p.currency || 'KES');

        // Categories
        var $cats = $('#prevCategories').empty();
        if (p.categories && p.categories.length) {
            p.categories.forEach(function(c) {
                $cats.append('<span class="import-cat-chip"><i class="fa-solid fa-tag"></i> ' + escHtml(c) + '</span>');
            });
        } else {
            $cats.append('<p class="form-hint" style="margin:0">No categories found</p>');
        }

        // Images — use the same .image-gallery pattern as the product form
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

        // Variations
        if (p.variations && p.variations.length) {
            $('#prevVariationsWrap').show();
            var html = '';
            p.variations.forEach(function(v) {
                var attrs = [];
                if (v.attributes) {
                    Object.keys(v.attributes).forEach(function(k) {
                        if (v.attributes[k]) attrs.push(v.attributes[k]);
                    });
                }
                var label = attrs.length ? attrs.join(' / ') : (v.name || '—');
                var price = v.price !== null && v.price !== undefined ? parseFloat(v.price).toLocaleString() : '—';
                html += '<div class="import-var-row">'
                    + '<span class="import-var-name">' + escHtml(label) + '</span>'
                    + '<span class="import-var-price">' + (p.currency || '') + ' ' + price + '</span>'
                    + '</div>';
            });
            $('#prevVariations').html(html);
        } else {
            $('#prevVariationsWrap').hide();
        }

        // Init price formatting
        TinyShop.initPriceInput($('#prevPrice'));
        TinyShop.initPriceInput($('#prevComparePrice'));
    }

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
        var sellerId = $('#importSellerId').val();
        if (!sellerId) {
            TinyShop.toast('Please select a seller first', 'error');
            return;
        }
        var title = $('#prevTitle').val().trim();
        if (!title) {
            TinyShop.toast('Product title is required', 'error');
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).html('<span class="btn-spinner"></span> Importing...');

        // Get raw price values (strip commas from formatted display)
        var rawPrice = ($('#prevPrice').val() || '0').replace(/,/g, '');
        var rawCompare = ($('#prevComparePrice').val() || '').replace(/,/g, '');

        var payload = {
            seller_id: parseInt(sellerId, 10),
            title: title,
            description: $('#prevDescription').val().trim(),
            price: parseFloat(rawPrice) || 0,
            compare_price: rawCompare ? parseFloat(rawCompare) : null,
            categories: fetchedData ? fetchedData.categories : [],
            images: fetchedData ? fetchedData.images : [],
            variations: fetchedData ? fetchedData.variations : []
        };

        TinyShop.api('POST', '/api/admin/import/save', payload)
            .done(function(res) {
                TinyShop.toast('Product imported!');
                // Reset for next import
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
