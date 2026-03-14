{extends file="layouts/dashboard.tpl"}

{block name="content"}
<div class="dash-topbar">
    <span class="dash-topbar-title">Import Product</span>
</div>

<div class="dash-form" data-currency="{$currency|escape}">
    <div class="form-section">
        <div class="form-section-title">Product source</div>
        <div class="form-group">
            <div class="import-source-tabs">
                <button type="button" class="import-source-tab active" data-tab="link">Link</button>
                <button type="button" class="import-source-tab" data-tab="html">Page Source</button>
                <button type="button" class="import-source-tab" data-tab="quick">Quick Add</button>
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
            <div id="tabQuick" class="import-tab-panel" style="display:none">
                <textarea id="quickAddText" class="form-control" rows="6" placeholder="Type your products in any format, for example:

JBL Wave 200TWS 5500
Samsung Galaxy A15 18000
Nike Air Max 90
2x Anker PowerCore 10000"></textarea>
                <p class="form-hint">Type product names with prices. AI will look up all the details, images, and descriptions for you.</p>
                <button type="button" id="quickFindBtn" class="btn btn-primary import-fetch-btn" style="margin-top:8px">
                    <span class="quick-btn-label"><i class="fa-solid fa-wand-magic-sparkles"></i> Find Products</span>
                    <span class="quick-btn-loading" style="display:none"><span class="btn-spinner"></span> Looking up...</span>
                </button>
            </div>
        </div>
    </div>

    {* Single-product preview (hidden until fetch/parse) *}
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

    {* Quick Add results — accordion product forms *}
    <div id="quickAddResults" style="display:none">
        <div id="quickAddProgress" class="smart-import-progress" style="display:none">
            <div class="sip-icon"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
            <div class="sip-status" id="sipStatus">Reading your product list...</div>
            <div class="sip-steps">
                <div class="sip-step active" id="sipStep1">
                    <span class="sip-step-dot"></span>
                    <span class="sip-step-label">Parsing</span>
                </div>
                <div class="sip-step-line"></div>
                <div class="sip-step" id="sipStep2">
                    <span class="sip-step-dot"></span>
                    <span class="sip-step-label">Looking up</span>
                </div>
                <div class="sip-step-line"></div>
                <div class="sip-step" id="sipStep3">
                    <span class="sip-step-dot"></span>
                    <span class="sip-step-label">Done</span>
                </div>
            </div>
            <div class="smart-import-skeletons">
                <div class="smart-import-skeleton"></div>
                <div class="smart-import-skeleton"></div>
                <div class="smart-import-skeleton"></div>
            </div>
            <button type="button" id="quickAddCancel" class="btn btn-ghost sip-cancel">Cancel</button>
        </div>

        <div id="quickAddNotice" class="sip-notice" style="display:none"></div>
        <div id="quickAddAccordion"></div>

        <div id="quickAddSuccess" class="qi-success" style="display:none">
            <div class="qi-success-icon"><i class="fa-solid fa-circle-check"></i></div>
            <div class="qi-success-text"><span id="quickAddSuccessCount">0</span> product(s) added to your store</div>
            <a href="/dashboard/products" class="btn btn-primary qi-success-btn"><i class="fa-solid fa-store"></i> View Products</a>
            <button type="button" id="quickAddMore" class="btn btn-ghost qi-success-btn">Import More</button>
        </div>

        <div id="quickAddBulkBar" class="smart-import-bulk-bar" style="display:none">
            <button type="button" id="quickAddAllBtn" class="btn btn-outline">
                <i class="fa-solid fa-check-double"></i> <span id="quickAddAllLabel">Add All</span>
            </button>
        </div>
    </div>
</div>

{/block}

{block name="extra_scripts"}
<script src="/public/js/import{$min}.js?v={$asset_v}"></script>
{/block}
