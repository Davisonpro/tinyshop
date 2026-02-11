{extends file="layouts/dashboard.tpl"}

{block name="content"}
<div class="dash-topbar">
    <a href="/dashboard/products" class="dash-topbar-back" aria-label="Back to products">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
    </a>
    <span class="dash-topbar-title">{if $is_edit}Edit Product{else}Add Product{/if}</span>
    {if $is_edit}
    <a href="{$scheme}://{$user.subdomain|escape}.{$base_domain}/{$product.slug|default:$product.id}" target="_blank" class="dash-topbar-action" aria-label="View product on storefront">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
    </a>
    {else}
    <span></span>
    {/if}
</div>

<form id="productForm" class="dash-form" autocomplete="off">
    <input type="hidden" id="productId" value="{if $is_edit}{$product.id}{/if}">

    <div class="form-section">
        <div class="form-section-title">What are you selling?</div>
        <div class="form-group">
            <label for="productName">Name</label>
            <input type="text" class="form-control" id="productName" name="name" value="{if $is_edit}{$product.name|escape}{/if}" placeholder="e.g. Handmade Bracelet" required autocomplete="off">
        </div>
        <div class="form-group">
            <label for="productDesc">Description</label>
            <textarea class="form-control autosize" id="productDesc" name="description" placeholder="Tell your customers about this product..." rows="3" autocomplete="off">{if $is_edit}{$product.description|escape}{/if}</textarea>
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
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
                <button type="button" class="btn-add-category" id="addCategoryBtn" title="Add category">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
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
        <div class="form-section-title">Photos</div>
        <p class="form-hint" style="margin-bottom: 12px;">First photo is the main one customers see. Drag to reorder.</p>
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
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                <span>Add</span>
            </div>
        </div>
        <input type="file" id="imageInput" accept="image/*" style="display:none" multiple>
    </div>

    <div class="form-section">
        <div class="form-section-title">Options</div>
        <p class="form-hint" style="margin-bottom: 12px;">Does this come in different sizes, colors, or styles? You can set a different price for each.</p>
        <div id="variationGroups"></div>
        <button type="button" class="variation-add-group" id="addVariationGroup">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add option like Size or Color
        </button>
    </div>

    <div class="form-section">
        <div class="form-toggle-row">
            <div>
                <div class="form-toggle-label">Featured product</div>
                <p class="form-hint" style="margin-top:2px">Shows at the top of your shop</p>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" id="productFeatured" name="is_featured" {if $is_edit && $product.is_featured}checked{/if}>
                <span class="toggle-slider"></span>
            </label>
        </div>
        {if $is_edit}
        <div class="form-toggle-row" style="margin-top:16px">
            <div>
                <div class="form-toggle-label">Visible in shop</div>
                <p class="form-hint" style="margin-top:2px">Turn off to hide without deleting</p>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" id="productActive" name="is_active" {if !$is_edit || $product.is_active}checked{/if}>
                <span class="toggle-slider"></span>
            </label>
        </div>
        <div class="form-toggle-row" style="margin-top:16px">
            <div>
                <div class="form-toggle-label">Mark as sold out</div>
                <p class="form-hint" style="margin-top:2px">Customers will see a "Sold" badge</p>
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
            <svg class="seo-toggle-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="seo-fields" id="seoFields" style="display:none">
            <p class="form-hint" style="margin-bottom:12px">Customize how this product appears on Google and social media. Leave empty to use defaults.</p>
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
    <button type="button" class="btn-danger" id="deleteProductBtn" style="margin-top:12px">Delete Product</button>
    {/if}
</form>
{/block}

{block name="extra_scripts"}
<script>
var _productFormConfig = {
    isEdit: {if $is_edit}true{else}false{/if},
    productId: {if $is_edit}{$product.id}{else}null{/if},
    currency: '{$currency|escape:"javascript"}',
    variations: {if $is_edit && $product.variations}{$product.variations nofilter}{else}[]{/if},
    categoryTree: {literal}[{/literal}{foreach $category_tree as $parent}{literal}{{/literal}"id":{$parent.id},"name":"{$parent.name|escape:'javascript'}","children":[{foreach $parent.children as $child}{literal}{{/literal}"id":{$child.id},"name":"{$child.name|escape:'javascript'}"{literal}}{/literal}{if !$child@last},{/if}{/foreach}]{literal}}{/literal}{if !$parent@last},{/if}{/foreach}{literal}]{/literal}
};
</script>
{/block}
