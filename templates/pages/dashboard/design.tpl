{extends file="layouts/dashboard.tpl"}

{block name="content"}
<div class="dash-topbar">
    <span class="dash-topbar-title">Design</span>
    <a href="/dashboard/shop" class="dash-topbar-avatar">{$user.store_name|escape|substr:0:1|upper}</a>
</div>

{* --- Color Palette --- *}
<div class="dash-form">
    <div class="form-section">
        <div class="form-section-title">Color Palette</div>
        <p class="form-hint" style="margin-bottom:16px">Choose a color scheme for your storefront</p>
        {assign var="currentPalette" value=$user.color_palette|default:'default'}
        <div class="palette-grid" id="paletteGrid">
            <label class="palette-swatch{if $currentPalette == 'default'} active{/if}" data-palette="default">
                <input type="radio" name="color_palette" value="default" {if $currentPalette == 'default'}checked{/if}>
                <div class="palette-preview">
                    <span class="palette-strip" style="background:#222222"></span>
                    <span class="palette-strip" style="background:#555555"></span>
                    <span class="palette-strip" style="background:#888888"></span>
                    <span class="palette-strip" style="background:#e0e0e0"></span>
                    <span class="palette-strip" style="background:#ffffff"></span>
                </div>
                <span class="palette-name">Classic</span>
                <div class="palette-check"><i class="fa-solid fa-check"></i></div>
            </label>
            <label class="palette-swatch{if $currentPalette == 'ocean'} active{/if}" data-palette="ocean">
                <input type="radio" name="color_palette" value="ocean" {if $currentPalette == 'ocean'}checked{/if}>
                <div class="palette-preview">
                    <span class="palette-strip" style="background:#0F2B46"></span>
                    <span class="palette-strip" style="background:#1B4D7A"></span>
                    <span class="palette-strip" style="background:#E07A5F"></span>
                    <span class="palette-strip" style="background:#E8A393"></span>
                    <span class="palette-strip" style="background:#FDF0EC"></span>
                </div>
                <span class="palette-name">Ocean</span>
                <div class="palette-check"><i class="fa-solid fa-check"></i></div>
            </label>
            <label class="palette-swatch{if $currentPalette == 'forest'} active{/if}" data-palette="forest">
                <input type="radio" name="color_palette" value="forest" {if $currentPalette == 'forest'}checked{/if}>
                <div class="palette-preview">
                    <span class="palette-strip" style="background:#1B4332"></span>
                    <span class="palette-strip" style="background:#2D6A4F"></span>
                    <span class="palette-strip" style="background:#D4A373"></span>
                    <span class="palette-strip" style="background:#E2C4A0"></span>
                    <span class="palette-strip" style="background:#F5EEDF"></span>
                </div>
                <span class="palette-name">Forest</span>
                <div class="palette-check"><i class="fa-solid fa-check"></i></div>
            </label>
            <label class="palette-swatch{if $currentPalette == 'sunset'} active{/if}" data-palette="sunset">
                <input type="radio" name="color_palette" value="sunset" {if $currentPalette == 'sunset'}checked{/if}>
                <div class="palette-preview">
                    <span class="palette-strip" style="background:#5C1A0A"></span>
                    <span class="palette-strip" style="background:#8B3A2A"></span>
                    <span class="palette-strip" style="background:#457B9D"></span>
                    <span class="palette-strip" style="background:#7BABC5"></span>
                    <span class="palette-strip" style="background:#E0EDF5"></span>
                </div>
                <span class="palette-name">Sunset</span>
                <div class="palette-check"><i class="fa-solid fa-check"></i></div>
            </label>
            <label class="palette-swatch{if $currentPalette == 'lavender'} active{/if}" data-palette="lavender">
                <input type="radio" name="color_palette" value="lavender" {if $currentPalette == 'lavender'}checked{/if}>
                <div class="palette-preview">
                    <span class="palette-strip" style="background:#2D2457"></span>
                    <span class="palette-strip" style="background:#5B4D8A"></span>
                    <span class="palette-strip" style="background:#F2CC8F"></span>
                    <span class="palette-strip" style="background:#F6DDB4"></span>
                    <span class="palette-strip" style="background:#FDF5E8"></span>
                </div>
                <span class="palette-name">Lavender</span>
                <div class="palette-check"><i class="fa-solid fa-check"></i></div>
            </label>
            <label class="palette-swatch{if $currentPalette == 'cherry'} active{/if}" data-palette="cherry">
                <input type="radio" name="color_palette" value="cherry" {if $currentPalette == 'cherry'}checked{/if}>
                <div class="palette-preview">
                    <span class="palette-strip" style="background:#7B1E34"></span>
                    <span class="palette-strip" style="background:#A63D50"></span>
                    <span class="palette-strip" style="background:#C9534A"></span>
                    <span class="palette-strip" style="background:#E0918B"></span>
                    <span class="palette-strip" style="background:#FBE9E7"></span>
                </div>
                <span class="palette-name">Cherry</span>
                <div class="palette-check"><i class="fa-solid fa-check"></i></div>
            </label>
            <label class="palette-swatch{if $currentPalette == 'sage'} active{/if}" data-palette="sage">
                <input type="radio" name="color_palette" value="sage" {if $currentPalette == 'sage'}checked{/if}>
                <div class="palette-preview">
                    <span class="palette-strip" style="background:#5A7247"></span>
                    <span class="palette-strip" style="background:#7D9B6A"></span>
                    <span class="palette-strip" style="background:#8B6F4E"></span>
                    <span class="palette-strip" style="background:#C4AD8F"></span>
                    <span class="palette-strip" style="background:#F3EDE4"></span>
                </div>
                <span class="palette-name">Sage</span>
                <div class="palette-check"><i class="fa-solid fa-check"></i></div>
            </label>
            <label class="palette-swatch{if $currentPalette == 'midnight'} active{/if}" data-palette="midnight">
                <input type="radio" name="color_palette" value="midnight" {if $currentPalette == 'midnight'}checked{/if}>
                <div class="palette-preview">
                    <span class="palette-strip" style="background:#151B2B"></span>
                    <span class="palette-strip" style="background:#2A3450"></span>
                    <span class="palette-strip" style="background:#4A7CFF"></span>
                    <span class="palette-strip" style="background:#8DABFF"></span>
                    <span class="palette-strip" style="background:#E8EEFF"></span>
                </div>
                <span class="palette-name">Midnight</span>
                <div class="palette-check"><i class="fa-solid fa-check"></i></div>
            </label>
            <label class="palette-swatch{if $currentPalette == 'mocha'} active{/if}" data-palette="mocha">
                <input type="radio" name="color_palette" value="mocha" {if $currentPalette == 'mocha'}checked{/if}>
                <div class="palette-preview">
                    <span class="palette-strip" style="background:#3E2723"></span>
                    <span class="palette-strip" style="background:#5D4037"></span>
                    <span class="palette-strip" style="background:#A1887F"></span>
                    <span class="palette-strip" style="background:#C8B7AF"></span>
                    <span class="palette-strip" style="background:#F5F0ED"></span>
                </div>
                <span class="palette-name">Mocha</span>
                <div class="palette-check"><i class="fa-solid fa-check"></i></div>
            </label>
            <label class="palette-swatch{if $currentPalette == 'blush'} active{/if}" data-palette="blush">
                <input type="radio" name="color_palette" value="blush" {if $currentPalette == 'blush'}checked{/if}>
                <div class="palette-preview">
                    <span class="palette-strip" style="background:#4A3040"></span>
                    <span class="palette-strip" style="background:#6E4D60"></span>
                    <span class="palette-strip" style="background:#D4889E"></span>
                    <span class="palette-strip" style="background:#E8B4C4"></span>
                    <span class="palette-strip" style="background:#FDF0F4"></span>
                </div>
                <span class="palette-name">Blush</span>
                <div class="palette-check"><i class="fa-solid fa-check"></i></div>
            </label>
        </div>
    </div>
</div>

{* --- Logo & Branding --- *}
<div class="dash-form dash-form-flush">
    <div class="form-section">
        <div class="form-section-title">Logo & Branding</div>
        <div class="form-group">
            <div class="brand-uploads">
                <div class="brand-upload-item">
                    <input type="file" id="logoInput" accept="image/*" style="display:none">
                    <div class="logo-upload" id="logoZone">
                        <div class="logo-upload-preview" id="logoPreview" {if !$user.shop_logo}style="display:none"{/if}>
                            <img src="{$user.shop_logo|escape}" alt="Logo" id="logoImg">
                            <div class="logo-upload-overlay">
                                <i class="fa-solid fa-camera"></i>
                                <span>Change</span>
                            </div>
                        </div>
                        <div class="logo-upload-empty" id="logoPlaceholder" {if $user.shop_logo}style="display:none"{/if}>
                            <i class="fa-solid fa-camera" style="font-size:28px"></i>
                            <span>Add logo</span>
                        </div>
                    </div>
                    <input type="hidden" id="shopLogo" name="shop_logo" value="{$user.shop_logo|escape}">
                    <span class="brand-upload-label">Logo</span>
                </div>
                <div class="brand-upload-item">
                    <input type="file" id="faviconInput" accept="image/*" style="display:none">
                    <div class="favicon-upload" id="faviconZone">
                        <div class="favicon-upload-preview" id="faviconPreview" {if !$user.shop_favicon}style="display:none"{/if}>
                            <img src="{$user.shop_favicon|escape}" alt="Favicon" id="faviconImg">
                            <div class="logo-upload-overlay">
                                <i class="fa-solid fa-camera"></i>
                                <span>Change</span>
                            </div>
                        </div>
                        <div class="favicon-upload-empty" id="faviconPlaceholder" {if $user.shop_favicon}style="display:none"{/if}>
                            <i class="fa-solid fa-globe icon-lg"></i>
                            <span>Add</span>
                        </div>
                    </div>
                    <input type="hidden" id="shopFavicon" name="shop_favicon" value="{$user.shop_favicon|escape}">
                    <span class="brand-upload-label">Favicon</span>
                </div>
            </div>
            <p class="form-hint">Your logo shows on your shop page. The favicon is the small icon in browser tabs.</p>
        </div>

        <div class="form-group" style="margin-bottom:0">
            <label>Logo Position on Desktop</label>
            <div class="alignment-toggle" id="alignmentToggle">
                {assign var="currentAlign" value=$user.logo_alignment|default:'left'}
                <button type="button" class="alignment-btn{if $currentAlign == 'left'} active{/if}" data-value="left">
                    <i class="fa-solid fa-align-left"></i> Left
                </button>
                <button type="button" class="alignment-btn{if $currentAlign == 'centered'} active{/if}" data-value="centered">
                    <i class="fa-solid fa-align-center"></i> Centered
                </button>
            </div>
            <p class="form-hint" style="margin-top:8px">How your logo and shop name appear on desktop</p>
        </div>
    </div>
</div>

{* --- Announcement --- *}
<div class="dash-form dash-form-flush">
    <div class="form-section">
        <div class="form-section-title">Announcement Bar</div>
        <div class="form-group">
            <input type="text" class="form-control" id="announcementText" value="{$user.announcement_text|escape}" placeholder="e.g. Free delivery on orders over KES 1,000!" maxlength="500">
            <p class="form-hint">Shows a banner at the top of your shop. Leave empty to hide.</p>
        </div>
        <button type="button" class="btn-primary btn-block" id="saveAnnouncementBtn">Save</button>
    </div>
</div>

{* --- Layout --- *}
<div class="dash-form dash-form-flush">
    <div class="form-section">
        <div class="form-section-title">Layout</div>
        <p class="form-hint" style="margin-bottom:16px">Control what visitors see on your shop page</p>

        <div class="form-subsection-label">Header</div>
        <div class="form-toggle-row">
            <div>
                <div class="form-toggle-label">Show logo</div>
                <p class="form-hint" style="margin-top:2px">Display your logo image in the header</p>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" class="design-toggle" data-field="show_logo" {if $user.show_logo|default:1}checked{/if}>
                <span class="toggle-slider"></span>
            </label>
        </div>
        <div class="form-toggle-row">
            <div>
                <div class="form-toggle-label">Show store name</div>
                <p class="form-hint" style="margin-top:2px">Display your shop name next to the logo</p>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" class="design-toggle" data-field="show_store_name" {if $user.show_store_name|default:1}checked{/if}>
                <span class="toggle-slider"></span>
            </label>
        </div>
        <div class="form-toggle-row">
            <div>
                <div class="form-toggle-label">Show tagline</div>
                <p class="form-hint" style="margin-top:2px">Display the short description below your name</p>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" class="design-toggle" data-field="show_tagline" {if $user.show_tagline|default:1}checked{/if}>
                <span class="toggle-slider"></span>
            </label>
        </div>

        <div class="form-subsection-label" style="margin-top:24px">Product Images</div>
        <div class="form-group" style="margin-bottom:0">
            <label>How images display in product cards</label>
            <div class="alignment-toggle" id="imageFitToggle">
                {assign var="currentFit" value=$user.product_image_fit|default:'cover'}
                <button type="button" class="alignment-btn{if $currentFit == 'cover'} active{/if}" data-value="cover">
                    <i class="fa-solid fa-crop"></i> Fill
                </button>
                <button type="button" class="alignment-btn{if $currentFit == 'contain'} active{/if}" data-value="contain">
                    <i class="fa-solid fa-expand"></i> Fit
                </button>
            </div>
            <div class="form-hint" style="margin-top:10px">
                <div style="margin-bottom:6px"><strong>Fill</strong> (recommended) &mdash; Images fill the entire card for a clean, uniform grid. Some edges may be cropped if the photo isn't the same shape as the card.</div>
                <div><strong>Fit</strong> &mdash; Shows the complete image with nothing cut off. Great for products where every detail matters, like shoes, electronics, or artwork.</div>
            </div>
        </div>

    </div>
</div>

{* --- Theme Customizer Settings (registered by active theme) --- *}
{if !empty($customizer_schema)}
{foreach $customizer_schema as $section}
<div class="dash-form dash-form-flush">
    <div class="form-section" data-customizer-section="{$section.id|escape}">
        <div class="form-section-title"><i class="{$section.icon|escape}"></i> {$section.title|escape}</div>
        {if $section.description}
            <p class="form-hint" style="margin-bottom:16px">{$section.description|escape}</p>
        {/if}

        {assign var="needs_save_btn" value=false}
        {foreach $section.controls as $control}
            {assign var="ctrl_value" value=$theme_option_values[$control.id]|default:$control.default}
            {include file="partials/dashboard/customizer_control.tpl" control=$control value=$ctrl_value}
            {if $control.type == 'text' || $control.type == 'textarea' || $control.type == 'number' || $control.type == 'color' || $control.type == 'image' || $control.type == 'repeater'}
                {assign var="needs_save_btn" value=true}
            {/if}
        {/foreach}

        {if $needs_save_btn}
        <button type="button" class="btn-primary btn-block customizer-save-btn"
                data-section="{$section.id|escape}" style="margin-top:16px">Save</button>
        {/if}
    </div>
</div>
{/foreach}
{/if}
{/block}

{block name="extra_scripts"}
<script>
$(function() {ldelim}
    // --- Color Palette ---
    $('#paletteGrid').on('change', 'input[name="color_palette"]', function() {ldelim}
        var palette = $(this).val();
        $('#paletteGrid .palette-swatch').removeClass('active');
        $(this).closest('.palette-swatch').addClass('active');
        TinyShop.api('PUT', '/api/shop', {ldelim} color_palette: palette {rdelim}).done(function() {ldelim}
            TinyShop.toast('Color palette updated!');
        {rdelim}).fail(function() {ldelim}
            TinyShop.toast('Failed to update', 'error');
        {rdelim});
    {rdelim});

    // --- Logo Upload ---
    $('#logoZone').on('click', function() {ldelim} $('#logoInput').click(); {rdelim});
    $('#logoInput').on('change', function() {ldelim}
        var file = this.files[0];
        if (!file) return;
        TinyShop.uploadFile(file, function(url) {ldelim}
            $('#shopLogo').val(url);
            $('#logoPreview img').attr('src', url);
            $('#logoPreview').show();
            $('#logoPlaceholder').hide();
            TinyShop.api('PUT', '/api/shop', {ldelim} shop_logo: url {rdelim}).done(function() {ldelim}
                TinyShop.toast('Logo saved!');
            {rdelim}).fail(function() {ldelim}
                TinyShop.toast('Logo uploaded but failed to save', 'error');
            {rdelim});
        {rdelim});
    {rdelim});

    // --- Favicon Upload ---
    $('#faviconZone').on('click', function() {ldelim} $('#faviconInput').click(); {rdelim});
    $('#faviconInput').on('change', function() {ldelim}
        var file = this.files[0];
        if (!file) return;
        TinyShop.uploadFile(file, function(url) {ldelim}
            $('#shopFavicon').val(url);
            $('#faviconPreview img').attr('src', url);
            $('#faviconPreview').show();
            $('#faviconPlaceholder').hide();
            TinyShop.api('PUT', '/api/shop', {ldelim} shop_favicon: url {rdelim}).done(function() {ldelim}
                TinyShop.toast('Favicon saved!');
            {rdelim}).fail(function() {ldelim}
                TinyShop.toast('Favicon uploaded but failed to save', 'error');
            {rdelim});
        {rdelim});
    {rdelim});

    // --- Logo Alignment ---
    $('#alignmentToggle').on('click', '.alignment-btn', function() {ldelim}
        var val = $(this).data('value');
        $('#alignmentToggle .alignment-btn').removeClass('active');
        $(this).addClass('active');
        TinyShop.api('PUT', '/api/shop', {ldelim} logo_alignment: val {rdelim}).done(function() {ldelim}
            TinyShop.toast('Logo alignment updated!');
        {rdelim}).fail(function() {ldelim}
            TinyShop.toast('Failed to update', 'error');
        {rdelim});
    {rdelim});

    // --- Product Image Fit ---
    $('#imageFitToggle').on('click', '.alignment-btn', function() {ldelim}
        var val = $(this).data('value');
        $('#imageFitToggle .alignment-btn').removeClass('active');
        $(this).addClass('active');
        TinyShop.api('PUT', '/api/shop', {ldelim} product_image_fit: val {rdelim}).done(function() {ldelim}
            TinyShop.toast('Image display updated!');
        {rdelim}).fail(function() {ldelim}
            TinyShop.toast('Failed to update', 'error');
        {rdelim});
    {rdelim});

    // --- Design Toggles ---
    $('.design-toggle').on('change', function() {ldelim}
        var field = $(this).data('field');
        var val = this.checked ? 1 : 0;
        var payload = {ldelim}{rdelim};
        payload[field] = val;
        TinyShop.api('PUT', '/api/shop', payload).done(function() {ldelim}
            TinyShop.toast('Setting updated');
        {rdelim}).fail(function() {ldelim}
            TinyShop.toast('Failed to update', 'error');
        {rdelim});
    {rdelim});

    // --- Announcement save ---
    $('#saveAnnouncementBtn').on('click', function() {ldelim}
        var $btn = $(this).prop('disabled', true).text('Saving...');
        var text = $('#announcementText').val().trim();
        TinyShop.api('PUT', '/api/shop', {ldelim} announcement_text: text {rdelim}).done(function() {ldelim}
            TinyShop.toast('Announcement saved');
            $btn.prop('disabled', false).text('Save');
        {rdelim}).fail(function() {ldelim}
            TinyShop.toast('Failed to save', 'error');
            $btn.prop('disabled', false).text('Save');
        {rdelim});
    {rdelim});

    // --- Theme Customizer ---
    // Unbind previous listeners (SPA safe)
    $(document).off('.tcd');

    // Icon picker — curated FA 6 icons for e-commerce / trust badges
    var faIcons = [
        {ldelim}c:'fa-solid fa-truck-fast',n:'Shipping'{rdelim},
        {ldelim}c:'fa-solid fa-shield-halved',n:'Security'{rdelim},
        {ldelim}c:'fa-solid fa-lock',n:'Secure'{rdelim},
        {ldelim}c:'fa-solid fa-credit-card',n:'Payment'{rdelim},
        {ldelim}c:'fa-solid fa-money-bill-wave',n:'Money'{rdelim},
        {ldelim}c:'fa-solid fa-rotate-left',n:'Returns'{rdelim},
        {ldelim}c:'fa-solid fa-box-open',n:'Package'{rdelim},
        {ldelim}c:'fa-solid fa-gift',n:'Gift'{rdelim},
        {ldelim}c:'fa-solid fa-percent',n:'Discount'{rdelim},
        {ldelim}c:'fa-solid fa-tag',n:'Price'{rdelim},
        {ldelim}c:'fa-solid fa-tags',n:'Tags'{rdelim},
        {ldelim}c:'fa-solid fa-star',n:'Star'{rdelim},
        {ldelim}c:'fa-solid fa-heart',n:'Heart'{rdelim},
        {ldelim}c:'fa-solid fa-thumbs-up',n:'Like'{rdelim},
        {ldelim}c:'fa-solid fa-circle-check',n:'Verified'{rdelim},
        {ldelim}c:'fa-solid fa-certificate',n:'Certified'{rdelim},
        {ldelim}c:'fa-solid fa-award',n:'Award'{rdelim},
        {ldelim}c:'fa-solid fa-medal',n:'Medal'{rdelim},
        {ldelim}c:'fa-solid fa-trophy',n:'Trophy'{rdelim},
        {ldelim}c:'fa-solid fa-crown',n:'Premium'{rdelim},
        {ldelim}c:'fa-solid fa-gem',n:'Quality'{rdelim},
        {ldelim}c:'fa-solid fa-bolt',n:'Fast'{rdelim},
        {ldelim}c:'fa-solid fa-clock',n:'Time'{rdelim},
        {ldelim}c:'fa-solid fa-stopwatch',n:'Quick'{rdelim},
        {ldelim}c:'fa-solid fa-headset',n:'Support'{rdelim},
        {ldelim}c:'fa-solid fa-phone',n:'Phone'{rdelim},
        {ldelim}c:'fa-solid fa-envelope',n:'Email'{rdelim},
        {ldelim}c:'fa-solid fa-comment',n:'Chat'{rdelim},
        {ldelim}c:'fa-solid fa-comments',n:'Messages'{rdelim},
        {ldelim}c:'fa-solid fa-earth-americas',n:'Global'{rdelim},
        {ldelim}c:'fa-solid fa-globe',n:'World'{rdelim},
        {ldelim}c:'fa-solid fa-location-dot',n:'Location'{rdelim},
        {ldelim}c:'fa-solid fa-store',n:'Store'{rdelim},
        {ldelim}c:'fa-solid fa-shop',n:'Shop'{rdelim},
        {ldelim}c:'fa-solid fa-bag-shopping',n:'Shopping'{rdelim},
        {ldelim}c:'fa-solid fa-cart-shopping',n:'Cart'{rdelim},
        {ldelim}c:'fa-solid fa-basket-shopping',n:'Basket'{rdelim},
        {ldelim}c:'fa-solid fa-hand-holding-heart',n:'Care'{rdelim},
        {ldelim}c:'fa-solid fa-handshake',n:'Trust'{rdelim},
        {ldelim}c:'fa-solid fa-hands-holding',n:'Careful'{rdelim},
        {ldelim}c:'fa-solid fa-leaf',n:'Natural'{rdelim},
        {ldelim}c:'fa-solid fa-seedling',n:'Eco'{rdelim},
        {ldelim}c:'fa-solid fa-recycle',n:'Recycle'{rdelim},
        {ldelim}c:'fa-solid fa-tree',n:'Organic'{rdelim},
        {ldelim}c:'fa-solid fa-fire',n:'Hot'{rdelim},
        {ldelim}c:'fa-solid fa-sun',n:'Sun'{rdelim},
        {ldelim}c:'fa-solid fa-snowflake',n:'Cold'{rdelim},
        {ldelim}c:'fa-solid fa-droplet',n:'Water'{rdelim},
        {ldelim}c:'fa-solid fa-utensils',n:'Food'{rdelim},
        {ldelim}c:'fa-solid fa-mug-hot',n:'Drinks'{rdelim},
        {ldelim}c:'fa-solid fa-shirt',n:'Clothing'{rdelim},
        {ldelim}c:'fa-solid fa-scissors',n:'Custom'{rdelim},
        {ldelim}c:'fa-solid fa-paint-roller',n:'Design'{rdelim},
        {ldelim}c:'fa-solid fa-palette',n:'Art'{rdelim},
        {ldelim}c:'fa-solid fa-camera',n:'Photo'{rdelim},
        {ldelim}c:'fa-solid fa-mobile-screen',n:'Mobile'{rdelim},
        {ldelim}c:'fa-solid fa-laptop',n:'Laptop'{rdelim},
        {ldelim}c:'fa-solid fa-wifi',n:'Connected'{rdelim},
        {ldelim}c:'fa-solid fa-gauge-high',n:'Speed'{rdelim},
        {ldelim}c:'fa-solid fa-wrench',n:'Tools'{rdelim},
        {ldelim}c:'fa-solid fa-screwdriver-wrench',n:'Repair'{rdelim},
        {ldelim}c:'fa-solid fa-house',n:'Home'{rdelim},
        {ldelim}c:'fa-solid fa-building',n:'Business'{rdelim},
        {ldelim}c:'fa-solid fa-users',n:'Community'{rdelim},
        {ldelim}c:'fa-solid fa-user-check',n:'Verified User'{rdelim},
        {ldelim}c:'fa-solid fa-face-smile',n:'Happy'{rdelim},
        {ldelim}c:'fa-solid fa-check',n:'Check'{rdelim},
        {ldelim}c:'fa-solid fa-check-double',n:'Double Check'{rdelim},
        {ldelim}c:'fa-solid fa-shield',n:'Shield'{rdelim},
        {ldelim}c:'fa-solid fa-eye',n:'Visible'{rdelim},
        {ldelim}c:'fa-solid fa-fingerprint',n:'Unique'{rdelim},
        {ldelim}c:'fa-solid fa-key',n:'Access'{rdelim},
        {ldelim}c:'fa-solid fa-barcode',n:'Barcode'{rdelim},
        {ldelim}c:'fa-solid fa-qrcode',n:'QR Code'{rdelim},
        {ldelim}c:'fa-solid fa-chart-line',n:'Growth'{rdelim},
        {ldelim}c:'fa-solid fa-arrow-trend-up',n:'Trending'{rdelim},
        {ldelim}c:'fa-solid fa-infinity',n:'Unlimited'{rdelim},
        {ldelim}c:'fa-solid fa-feather',n:'Light'{rdelim},
        {ldelim}c:'fa-solid fa-mountain-sun',n:'Adventure'{rdelim},
        {ldelim}c:'fa-solid fa-paw',n:'Pets'{rdelim},
        {ldelim}c:'fa-solid fa-baby',n:'Kids'{rdelim}
    ];

    // Icon picker: open modal
    $(document).on('click.tcd', '.icon-picker-btn', function() {ldelim}
        var $btn = $(this);
        var currentVal = $btn.closest('.customizer-repeater-field').find('.icon-picker-value').val();

        var html = '<div class="icon-picker-search-wrap">' +
            '<i class="fa-solid fa-magnifying-glass"></i>' +
            '<input type="text" class="icon-picker-search" placeholder="Search icons...">' +
            '</div>' +
            '<div class="icon-picker-grid">';
        for (var i = 0; i < faIcons.length; i++) {ldelim}
            var ic = faIcons[i];
            html += '<button type="button" class="icon-picker-item' + (currentVal === ic.c ? ' selected' : '') + '" data-icon="' + escapeHtml(ic.c) + '" data-name="' + escapeHtml(ic.n) + '">' +
                '<i class="' + escapeHtml(ic.c) + '"></i>' +
                '<span>' + escapeHtml(ic.n) + '</span>' +
                '</button>';
        {rdelim}
        html += '</div>';

        TinyShop.openModal('Choose Icon', html);

        // Search filter
        $(document).on('input.iconpicker', '.icon-picker-search', function() {ldelim}
            var q = $(this).val().toLowerCase();
            $('.icon-picker-item').each(function() {ldelim}
                var name = $(this).data('name').toLowerCase();
                var icon = $(this).data('icon').toLowerCase();
                $(this).toggle(name.indexOf(q) !== -1 || icon.indexOf(q) !== -1);
            {rdelim});
            var visible = $('.icon-picker-item:visible').length;
            $('.icon-picker-empty').remove();
            if (visible === 0) {ldelim}
                $('.icon-picker-grid').after('<div class="icon-picker-empty">No icons found</div>');
            {rdelim}
        {rdelim});

        // Select icon
        $(document).on('click.iconpicker', '.icon-picker-item', function() {ldelim}
            var iconClass = $(this).data('icon');
            var iconName = $(this).data('name');
            $btn.closest('.customizer-repeater-field').find('.icon-picker-value').val(iconClass);
            $btn.html('<i class="' + escapeHtml(iconClass) + '"></i> <span>' + escapeHtml(iconName) + '</span>');
            $(document).off('.iconpicker');
            TinyShop.closeModal();
        {rdelim});
    {rdelim});

    // Repeater: toggle collapse
    $(document).on('click.tcd', '.customizer-repeater-item-header', function(e) {ldelim}
        if ($(e.target).closest('.customizer-repeater-remove').length) return;
        $(this).closest('.customizer-repeater-item').toggleClass('collapsed');
    {rdelim});

    // Repeater: sync title field to header
    $(document).on('input.tcd', '.customizer-repeater-item [data-field="title"]', function() {ldelim}
        var val = $.trim($(this).val());
        $(this).closest('.customizer-repeater-item').find('.customizer-repeater-item-number').text(val);
    {rdelim});

    // Repeater: add item
    $(document).on('click.tcd', '.customizer-repeater-add', function() {ldelim}
        var $repeater = $(this).closest('.customizer-repeater');
        var max = parseInt($repeater.data('max'), 10) || 0;
        var $items = $repeater.find('.customizer-repeater-items');
        var count = $items.find('.customizer-repeater-item').length;

        if (max > 0 && count >= max) {ldelim}
            TinyShop.toast('Maximum ' + max + ' items allowed', 'error');
            return;
        {rdelim}

        // Remove empty state
        $items.find('.customizer-repeater-empty').remove();

        var fields = $repeater.data('fields');
        var html = '<div class="customizer-repeater-item">' +
            '<div class="customizer-repeater-item-header">' +
                '<div class="customizer-repeater-item-toggle"><i class="fa-solid fa-chevron-right customizer-repeater-chevron"></i><span class="customizer-repeater-item-number"></span></div>' +
                '<button type="button" class="customizer-repeater-remove" title="Remove"><i class="fa-solid fa-trash-can"></i> Remove</button>' +
            '</div>' +
            '<div class="customizer-repeater-item-fields">';
        for (var key in fields) {ldelim}
            if (fields.hasOwnProperty(key)) {ldelim}
                var f = fields[key];
                var fType = f.type || 'text';
                if (fType === 'image') {ldelim}
                    html += '<div class="customizer-repeater-field">' +
                        '<span class="customizer-repeater-field-label">' + escapeHtml(f.label) + '</span>' +
                        '<input type="file" class="repeater-img-file" accept="image/*" style="display:none">' +
                        '<div class="repeater-img-zone">' +
                            '<div class="repeater-img-preview" style="display:none"><img src="" alt=""><div class="repeater-img-change">Change</div></div>' +
                            '<div class="repeater-img-empty"><i class="fa-solid fa-image"></i><span>Upload</span></div>' +
                        '</div>' +
                        '<input type="hidden" class="repeater-img-value" data-field="' + escapeHtml(key) + '" value="">' +
                        '</div>';
                {rdelim} else if (fType === 'icon') {ldelim}
                    html += '<div class="customizer-repeater-field">' +
                        '<span class="customizer-repeater-field-label">' + escapeHtml(f.label) + '</span>' +
                        '<button type="button" class="icon-picker-btn" data-field="' + escapeHtml(key) + '">' +
                            '<i class="fa-solid fa-icons" style="opacity:0.3"></i> <span>Choose icon</span>' +
                        '</button>' +
                        '<input type="hidden" class="icon-picker-value" data-field="' + escapeHtml(key) + '" value="">' +
                        '</div>';
                {rdelim} else {ldelim}
                    html += '<div class="customizer-repeater-field">' +
                        '<span class="customizer-repeater-field-label">' + escapeHtml(f.label) + '</span>' +
                        '<input type="text" class="form-control" data-field="' + escapeHtml(key) + '"' +
                        ' placeholder="' + escapeHtml(f.placeholder || f.label) + '">' +
                        '</div>';
                {rdelim}
            {rdelim}
        {rdelim}
        html += '</div></div>';
        $items.append(html);
    {rdelim});

    // Repeater: remove item
    $(document).on('click.tcd', '.customizer-repeater-remove', function() {ldelim}
        var $item = $(this).closest('.customizer-repeater-item');
        var $items = $item.closest('.customizer-repeater-items');
        $item.slideUp(200, function() {ldelim}
            $(this).remove();
            if ($items.find('.customizer-repeater-item').length === 0) {ldelim}
                $items.html('<div class="customizer-repeater-empty"><i class="fa-solid fa-layer-group"></i><span>No items yet</span></div>');
            {rdelim}
        {rdelim});
    {rdelim});

    // Repeater: image upload
    $(document).on('click.tcd', '.repeater-img-zone', function() {ldelim}
        $(this).closest('.customizer-repeater-field').find('.repeater-img-file').click();
    {rdelim});
    $(document).on('change.tcd', '.repeater-img-file', function() {ldelim}
        var file = this.files[0];
        if (!file) return;
        var $field = $(this).closest('.customizer-repeater-field');
        TinyShop.uploadFile(file, function(url) {ldelim}
            $field.find('.repeater-img-value').val(url);
            $field.find('.repeater-img-preview img').attr('src', url);
            $field.find('.repeater-img-preview').show();
            $field.find('.repeater-img-empty').hide();
        {rdelim});
    {rdelim});

    // Image upload in customizer
    $(document).on('click.tcd', '.customizer-image-upload', function() {ldelim}
        $(this).closest('.form-group').find('.customizer-image-file').click();
    {rdelim});
    $(document).on('change.tcd', '.customizer-image-file', function() {ldelim}
        var file = this.files[0];
        if (!file) return;
        var $group = $(this).closest('.form-group');
        TinyShop.uploadFile(file, function(url) {ldelim}
            $group.find('.customizer-image-value').val(url);
            $group.find('.customizer-image-preview img').attr('src', url);
            $group.find('.customizer-image-preview').show();
            $group.find('.customizer-image-empty').hide();
        {rdelim});
    {rdelim});

    // Color picker sync
    $(document).on('input.tcd', '.customizer-color-input', function() {ldelim}
        $(this).closest('.customizer-color-group').find('.customizer-color-hex').val($(this).val());
    {rdelim});
    $(document).on('change.tcd', '.customizer-color-hex', function() {ldelim}
        var val = $(this).val();
        if (/^#[0-9a-fA-F]{ldelim}6{rdelim}$/.test(val)) {ldelim}
            $(this).closest('.customizer-color-group').find('.customizer-color-input').val(val);
        {rdelim}
    {rdelim});

    // Autosave: customizer toggles, selects, radios
    function autosaveOption(key, val, $el, revertFn) {ldelim}
        var payload = {ldelim}{rdelim};
        payload[key] = val;
        TinyShop.api('PUT', '/api/theme-options', payload).done(function() {ldelim}
            TinyShop.toast('Setting updated');
        {rdelim}).fail(function(xhr) {ldelim}
            var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to update';
            TinyShop.toast(msg, 'error');
            if (revertFn) revertFn();
        {rdelim});
    {rdelim}

    $(document).on('change.tcd', '[data-customizer-section] [data-type="toggle"]', function() {ldelim}
        var $el = $(this);
        autosaveOption($el.data('option'), $el.is(':checked') ? '1' : '0', $el, function() {ldelim}
            $el.prop('checked', !$el.prop('checked'));
        {rdelim});
    {rdelim});

    $(document).on('change.tcd', '[data-customizer-section] select[data-type="select"]', function() {ldelim}
        var $el = $(this);
        var prev = $el.data('prev-val') || $el.find('option:first').val();
        autosaveOption($el.data('option'), $el.val(), $el, function() {ldelim}
            $el.val(prev);
        {rdelim});
        $el.data('prev-val', $el.val());
    {rdelim});

    $(document).on('focus.tcd', '[data-customizer-section] select[data-type="select"]', function() {ldelim}
        $(this).data('prev-val', $(this).val());
    {rdelim});

    // Radio button toggle + autosave
    $(document).on('click.tcd', '.customizer-radio-btn', function() {ldelim}
        var $btn = $(this);
        var $group = $btn.closest('.customizer-radio-group');
        var $prev = $group.find('.customizer-radio-btn.active');
        $group.find('.customizer-radio-btn').removeClass('active');
        $btn.addClass('active');

        if ($group.closest('[data-customizer-section]').length) {ldelim}
            autosaveOption($group.data('option'), $btn.data('value'), $btn, function() {ldelim}
                $group.find('.customizer-radio-btn').removeClass('active');
                $prev.addClass('active');
            {rdelim});
        {rdelim}
    {rdelim});

    // Section save
    $(document).on('click.tcd', '.customizer-save-btn', function() {ldelim}
        var $btn = $(this).prop('disabled', true).text('Saving...');
        var $section = $(this).closest('[data-customizer-section]');
        var data = {ldelim}{rdelim};

        // Collect simple controls
        $section.find('[data-option]').each(function() {ldelim}
            var key = $(this).data('option');
            var type = $(this).data('type');
            if (type === 'toggle') {ldelim}
                data[key] = $(this).is(':checked') ? '1' : '0';
            {rdelim} else if (type === 'repeater') {ldelim}
                // Handled below
            {rdelim} else if (type === 'image') {ldelim}
                if ($(this).is('input[type="hidden"]')) {ldelim}
                    data[key] = $(this).val();
                {rdelim}
            {rdelim} else if (type === 'color') {ldelim}
                if ($(this).is('input[type="color"]')) {ldelim}
                    data[key] = $(this).val();
                {rdelim}
            {rdelim} else if (type === 'radio') {ldelim}
                data[key] = $(this).find('.customizer-radio-btn.active').data('value') || '';
            {rdelim} else {ldelim}
                data[key] = $(this).val();
            {rdelim}
        {rdelim});

        // Collect repeater data
        $section.find('.customizer-repeater').each(function() {ldelim}
            var key = $(this).data('option');
            var items = [];
            $(this).find('.customizer-repeater-item').each(function() {ldelim}
                var item = {ldelim}{rdelim};
                $(this).find('[data-field]').each(function() {ldelim}
                    item[$(this).data('field')] = $(this).val();
                {rdelim});
                items.push(item);
            {rdelim});
            data[key] = JSON.stringify(items);
        {rdelim});

        TinyShop.api('PUT', '/api/theme-options', data).done(function() {ldelim}
            TinyShop.toast('Settings saved');
            $btn.prop('disabled', false).text('Save');
        {rdelim}).fail(function(xhr) {ldelim}
            var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to save';
            TinyShop.toast(msg, 'error');
            $btn.prop('disabled', false).text('Save');
        {rdelim});
    {rdelim});
{rdelim});
</script>
{/block}
