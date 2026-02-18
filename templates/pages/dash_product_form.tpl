{extends file="layouts/dashboard.tpl"}

{block name="content"}
<div class="dash-topbar">
    <a href="/dashboard/products" class="dash-topbar-back" aria-label="Back to products">
        <i class="fa-solid fa-chevron-left"></i>
    </a>
    <span class="dash-topbar-title">{if $is_edit}Edit Product{else}Add Product{/if}</span>
    {if $is_edit}
    <a href="{$scheme}://{$user.subdomain|escape}.{$base_domain}/{$product.slug|default:$product.id}" target="_blank" class="dash-topbar-action" aria-label="View product on storefront">
        <i class="fa-solid fa-arrow-up-right-from-square"></i>
    </a>
    {else}
    <span></span>
    {/if}
</div>

{if !$is_edit && !empty($usage) && !$usage.products_unlimited && $usage.product_count >= $usage.max_products}
<div class="plan-limit-banner">
    <i class="fa-solid fa-circle-exclamation"></i>
    <div>
        <strong>Product limit reached</strong>
        <p>You've used all {$usage.max_products} products on your plan.{if $usage.can_upgrade} <a href="/dashboard/billing">Upgrade</a> to add more.{/if}</p>
    </div>
</div>
{elseif !$is_edit && !empty($usage) && !$usage.products_unlimited && $usage.product_count >= ($usage.max_products * 0.8)}
<div class="plan-limit-banner plan-limit-banner-warning">
    <i class="fa-solid fa-triangle-exclamation"></i>
    <div>
        <strong>{if $usage.product_count >= $usage.max_products}Product limit reached ({$usage.max_products}){else}{$usage.product_count} of {$usage.max_products} products used{/if}</strong>
        <p>You're almost at your limit.{if $usage.can_upgrade} <a href="/dashboard/billing">Upgrade</a> for unlimited products.{/if}</p>
    </div>
</div>
{/if}

<form id="productForm" class="dash-form" autocomplete="off">
    <input type="hidden" id="productId" value="{if $is_edit}{$product.id}{/if}">

    <div class="form-section">
        <div class="form-section-title">What are you selling?</div>
        <div class="form-group">
            <label for="productName">Name</label>
            <input type="text" class="form-control" id="productName" name="name" value="{if $is_edit}{$product.name|escape}{/if}" placeholder="e.g. Handmade Bracelet" required autocomplete="off">
        </div>
        <div class="form-group">
            <label>Description</label>
            <div class="rich-editor" id="richEditor"></div>
            <textarea class="form-control" id="productDesc" name="description" style="display:none">{if $is_edit}{$product.description|escape}{/if}</textarea>
        </div>
        <div class="form-group">
            <label>Category</label>
            <input type="hidden" id="productCategory" name="category_id" value="{if $is_edit}{$product.category_id}{/if}">
            <div class="category-select-row">
                <div class="category-picker-btn" id="openCategoryPicker">
                    <span id="categoryPickerLabel" class="{if !$is_edit || !$product.category_id}picker-placeholder{/if}">
                        {if $is_edit && $product.category_id}
                            {foreach $categories as $cat}{if $cat.id == $product.category_id}{$cat.name|escape}{/if}{/foreach}
                        {else}
                            Select a category
                        {/if}
                    </span>
                    <i class="fa-solid fa-chevron-down"></i>
                </div>
                <button type="button" class="btn-add-category" id="addCategoryBtn" title="Add category">
                    <i class="fa-solid fa-plus"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="form-section">
        <div class="form-section-title">Pricing</div>
        <div class="form-group">
            <label for="productPrice">Selling price</label>
            <div class="input-group">
                <span class="input-group-prefix">{$currency}</span>
                <input type="text" class="form-control price-input" id="productPrice" name="price" value="{if $is_edit}{$product.price}{/if}" placeholder="0.00" inputmode="decimal" required autocomplete="off">
            </div>
        </div>
        <div class="form-group">
            <label for="productComparePrice">Original price</label>
            <div class="input-group">
                <span class="input-group-prefix">{$currency}</span>
                <input type="text" class="form-control price-input" id="productComparePrice" name="compare_price" value="{if $is_edit && $product.compare_price}{$product.compare_price}{/if}" placeholder="Leave empty if no discount" inputmode="decimal" autocomplete="off">
            </div>
            <p class="form-hint">If set, customers see this crossed out next to the sale price</p>
        </div>
    </div>

    <div class="form-section">
        <div class="form-section-title">Inventory</div>
        <div class="form-toggle-row">
            <div>
                <div class="form-toggle-label">Track stock</div>
                <p class="form-hint mt-xs">Automatically marks as sold when stock runs out</p>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" id="trackStock" {if $is_edit && $product.stock_quantity !== null}checked{/if}>
                <span class="toggle-slider"></span>
            </label>
        </div>
        <div id="stockQtyRow" {if !$is_edit || $product.stock_quantity === null}style="display:none"{/if}>
            <div class="form-group mt-sm">
                <label for="stockQuantity">Quantity in stock</label>
                <input type="number" class="form-control" id="stockQuantity" name="stock_quantity" min="0" inputmode="numeric" placeholder="0" value="{if $is_edit && $product.stock_quantity !== null}{$product.stock_quantity}{/if}" autocomplete="off">
            </div>
        </div>
    </div>

    <div class="form-section">
        <div class="form-section-title">Photos</div>
        <p class="form-hint" style="margin-bottom:16px">First photo is the main one customers see. Drag to reorder.</p>
        <div class="image-gallery" id="imageGallery">
            {if $is_edit}
                {foreach $images as $img}
                    <div class="image-gallery-item" data-url="{$img.image_url|escape}">
                        <img src="{$img.image_url|escape}" alt="">
                        <button type="button" class="image-gallery-remove">&times;</button>
                    </div>
                {/foreach}
            {/if}
            <div class="image-gallery-add" id="addImageBtn">
                <i class="fa-solid fa-image icon-xl"></i>
                <span>Add</span>
            </div>
        </div>
        <input type="file" id="imageInput" accept="image/*" class="d-none" multiple>
    </div>

    <div class="form-section">
        <div class="form-section-title">Options</div>
        <p class="form-hint" style="margin-bottom:16px">Does this come in different sizes, colors, or styles? You can set a different price for each.</p>
        <div id="variationGroups"></div>
        <button type="button" class="variation-add-group" id="addVariationGroup">
            <i class="fa-solid fa-plus"></i>
            Add option like Size or Color
        </button>
    </div>

    <div class="form-section">
        <div class="form-toggle-row">
            <div>
                <div class="form-toggle-label">Featured product</div>
                <p class="form-hint mt-xs">Shows at the top of your shop</p>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" id="productFeatured" name="is_featured" {if $is_edit && $product.is_featured}checked{/if}>
                <span class="toggle-slider"></span>
            </label>
        </div>
        {if $is_edit}
        <div class="form-toggle-row mt-md">
            <div>
                <div class="form-toggle-label">Visible in shop</div>
                <p class="form-hint mt-xs">Turn off to hide without deleting</p>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" id="productActive" name="is_active" {if !$is_edit || $product.is_active}checked{/if}>
                <span class="toggle-slider"></span>
            </label>
        </div>
        <div class="form-toggle-row mt-md">
            <div>
                <div class="form-toggle-label">Mark as sold out</div>
                <p class="form-hint mt-xs">Customers will see a "Sold out" badge</p>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" id="productSold" name="is_sold" {if $product.is_sold}checked{/if}>
                <span class="toggle-slider"></span>
            </label>
        </div>
        {/if}
    </div>

    {* --- SEO / Advanced (collapsible) --- *}
    <div class="form-section">
        <button type="button" class="seo-toggle" id="seoToggle">
            <span>SEO &amp; Google</span>
            <i class="fa-solid fa-chevron-down seo-toggle-arrow"></i>
        </button>
        <div class="seo-fields" id="seoFields" style="display:none">
            <p class="form-hint mb-md">Customize how this product appears on Google and social media. Leave empty to use defaults.</p>
            <div class="form-group">
                <label for="metaTitle">SEO Title</label>
                <input type="text" class="form-control" id="metaTitle" name="meta_title" value="{if $is_edit && $product.meta_title}{$product.meta_title|escape}{/if}" placeholder="Custom page title for Google" maxlength="200" autocomplete="off">
                <p class="form-hint"><span id="metaTitleCount">0</span>/200 characters</p>
            </div>
            <div class="form-group">
                <label for="metaDescription">SEO Description</label>
                <textarea class="form-control autosize" id="metaDescription" name="meta_description" placeholder="Custom description for search results" rows="2" maxlength="500" autocomplete="off">{if $is_edit && $product.meta_description}{$product.meta_description|escape}{/if}</textarea>
                <p class="form-hint"><span id="metaDescCount">0</span>/500 characters</p>
            </div>
        </div>
    </div>

    <button type="submit" class="btn-primary" id="saveProductBtn">
        {if $is_edit}Save Changes{else}Add Product{/if}
    </button>

    {if $is_edit}
    <button type="button" class="btn-outline mt-sm" id="duplicateProductBtn">
        <i class="fa-solid fa-copy"></i> Duplicate Product
    </button>
    <button type="button" class="btn-danger mt-sm" id="deleteProductBtn">Delete Product</button>
    {/if}
</form>
{/block}

{block name="extra_scripts"}
<script>
{literal}
// Rich text editor — clean, native-feeling
(function() {
    var editor = document.getElementById('richEditor');
    var textarea = document.getElementById('productDesc');
    if (!editor) return;

    // Content area (first — toolbar goes at bottom like keyboard accessory)
    var content = document.createElement('div');
    content.className = 'rich-editor-content';
    content.contentEditable = true;
    content.setAttribute('data-placeholder', 'Tell your customers about this product...');
    content.innerHTML = textarea.value || '';
    editor.appendChild(content);

    // Toolbar (bottom — like iOS keyboard accessory bar)
    var toolbar = document.createElement('div');
    toolbar.className = 'rich-editor-toolbar';

    var actions = [
        {
            icon: '<span style="font-weight:800;font-size:14px">B</span>',
            cmd: 'bold', title: 'Bold'
        },
        {
            icon: '<span style="font-style:italic;font-size:14px;font-family:Georgia,serif">I</span>',
            cmd: 'italic', title: 'Italic'
        },
        {
            icon: '<span style="font-weight:700;font-size:13px">H</span>',
            cmd: 'heading', title: 'Heading', query: 'h3'
        },
        {
            icon: '<i class="fa-solid fa-list-ul"></i>',
            cmd: 'insertUnorderedList', title: 'Bullet list'
        },
        {
            icon: '<i class="fa-solid fa-list-ol"></i>',
            cmd: 'insertOrderedList', title: 'Numbered list'
        }
    ];

    var buttons = [];
    actions.forEach(function(a, i) {
        // Add separator between text formatting (B, I, H) and lists
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
        btn.setAttribute('data-cmd', a.cmd);
        btn.addEventListener('mousedown', function(e) {
            e.preventDefault();
            if (a.cmd === 'heading') {
                var block = document.queryCommandValue('formatBlock');
                if (block === 'h3') {
                    document.execCommand('formatBlock', false, '<p>');
                } else {
                    document.execCommand('formatBlock', false, '<h3>');
                }
            } else {
                document.execCommand(a.cmd, false, null);
            }
            sync();
            updateActiveStates();
        });
        toolbar.appendChild(btn);
        buttons.push({ el: btn, action: a });
    });
    editor.appendChild(toolbar);

    function sync() {
        var html = content.innerHTML;
        textarea.value = (!html || html === '<br>' || html === '<p><br></p>') ? '' : html;
    }

    function updateActiveStates() {
        buttons.forEach(function(b) {
            var active = false;
            if (b.action.cmd === 'heading') {
                active = document.queryCommandValue('formatBlock') === 'h3';
            } else if (b.action.cmd === 'bold' || b.action.cmd === 'italic') {
                active = document.queryCommandState(b.action.cmd);
            } else if (b.action.cmd === 'insertUnorderedList' || b.action.cmd === 'insertOrderedList') {
                active = document.queryCommandState(b.action.cmd);
            }
            b.el.classList.toggle('is-active', active);
        });
    }

    content.addEventListener('input', function() { sync(); updateActiveStates(); });
    content.addEventListener('blur', sync);
    content.addEventListener('keyup', updateActiveStates);
    content.addEventListener('mouseup', function() { setTimeout(updateActiveStates, 10); });

    // Keyboard shortcuts
    content.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && !e.shiftKey) {
            if (e.key === 'b') { e.preventDefault(); document.execCommand('bold'); sync(); updateActiveStates(); }
            if (e.key === 'i') { e.preventDefault(); document.execCommand('italic'); sync(); updateActiveStates(); }
        }
    });

    // Clean paste — strip unwanted formatting from pasted content
    content.addEventListener('paste', function(e) {
        e.preventDefault();
        var html = (e.clipboardData || window.clipboardData).getData('text/html');
        var text = (e.clipboardData || window.clipboardData).getData('text/plain');
        if (html) {
            // Strip to allowed tags only
            var tmp = document.createElement('div');
            tmp.innerHTML = html;
            // Remove all style/class attributes
            tmp.querySelectorAll('*').forEach(function(el) {
                el.removeAttribute('style');
                el.removeAttribute('class');
                el.removeAttribute('id');
            });
            // Only keep allowed tag names
            var allowed = ['P','BR','B','STRONG','I','EM','UL','OL','LI','H2','H3','A'];
            tmp.querySelectorAll('*').forEach(function(el) {
                if (allowed.indexOf(el.tagName) === -1) {
                    // Replace unsupported elements with their text content
                    el.replaceWith(document.createTextNode(el.textContent));
                }
            });
            document.execCommand('insertHTML', false, tmp.innerHTML);
        } else {
            document.execCommand('insertText', false, text);
        }
        sync();
    });

    // Expose for draft restore
    window._setEditorContent = function(html) { content.innerHTML = html || ''; sync(); };
})();
{/literal}
</script>
<script>
var _productFormConfig = {
    isEdit: {if $is_edit}true{else}false{/if},
    productId: {if $is_edit}{$product.id}{else}null{/if},
    currency: '{$currency|escape:"javascript"}',
    stockQuantity: {if $is_edit && $product.stock_quantity !== null}{$product.stock_quantity}{else}null{/if},
    variations: {if $is_edit && $product.variations}{$product.variations nofilter}{else}[]{/if},
    categoryTree: {literal}[{/literal}{foreach $category_tree as $parent}{literal}{{/literal}"id":{$parent.id},"name":"{$parent.name|escape:'javascript'}","children":[{foreach $parent.children as $child}{literal}{{/literal}"id":{$child.id},"name":"{$child.name|escape:'javascript'}"{literal}}{/literal}{if !$child@last},{/if}{/foreach}]{literal}}{/literal}{if !$parent@last},{/if}{/foreach}{literal}]{/literal}
};
</script>
{if $is_edit}
<script>
{literal}
$('#duplicateProductBtn').on('click', function() {
    var $btn = $(this);
    var productId = {/literal}{$product.id}{literal};
    TinyShop.confirm('Duplicate Product?', 'A copy of this product will be created. Images will not be copied.', 'Duplicate', function() {
        TinyShop.closeModal();
        $btn.prop('disabled', true).html('<span class="btn-spinner"></span> Duplicating...');
        $.ajax({
            url: '/api/products/' + productId + '/duplicate',
            method: 'POST',
            contentType: 'application/json',
            dataType: 'json',
            success: function(res) {
                if (res.success && res.redirect_url) {
                    TinyShop.toast('Product duplicated!');
                    setTimeout(function() { TinyShop.navigate(res.redirect_url); }, 500);
                }
            },
            error: function(xhr) {
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to duplicate';
                TinyShop.toast(msg, 'error');
                $btn.prop('disabled', false).html('<i class="fa-solid fa-copy"></i> Duplicate Product');
            }
        });
    });
});
{/literal}
</script>
{/if}
{/block}
