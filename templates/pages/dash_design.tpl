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

{* --- Hero Slider --- *}
<div class="dash-form dash-form-flush">
    <div class="form-section">
        <div class="form-section-title">Hero Slider</div>
        <p class="form-hint" style="margin-bottom:16px">Add banner images to the top of your shop page. Max 6 slides.</p>

        <div class="hero-slide-list" id="heroSlideList">
            {foreach $hero_slides as $slide}
            <div class="hero-slide-card" data-id="{$slide.id}">
                <div class="hero-slide-handle" aria-label="Drag to reorder">
                    <i class="fa-solid fa-grip-vertical"></i>
                </div>
                <div class="hero-slide-thumb">
                    <img src="{$slide.image_url|escape}" alt="{$slide.heading|escape}">
                </div>
                <div class="hero-slide-info">
                    <div class="hero-slide-title">{$slide.heading|escape|default:'No heading'}</div>
                    {if $slide.link_url}<div class="hero-slide-link">{$slide.link_text|escape|default:'Shop Now'}</div>{/if}
                </div>
                <div class="hero-slide-actions">
                    <button type="button" class="hero-slide-btn hero-slide-edit" title="Edit">
                        <i class="fa-solid fa-pen"></i>
                    </button>
                    <button type="button" class="hero-slide-btn hero-slide-delete" title="Delete">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>
            {/foreach}
        </div>

        <button type="button" class="hero-slide-add" id="addSlideBtn">
            <i class="fa-solid fa-plus"></i>
            <span>Add Slide</span>
        </button>
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

    // --- Hero Slider ---
    function openSlideModal(slide) {ldelim}
        var isEdit = !!slide;
        var title = isEdit ? 'Edit Slide' : 'Add Slide';

        var html = '<form id="slideForm" autocomplete="off">' +
            '<div class="form-group">' +
                '<label>Banner Image</label>' +
                '<input type="file" id="slideImageInput" accept="image/*" style="display:none">' +
                '<div class="slide-upload-zone" id="slideUploadZone">' +
                    '<div class="slide-upload-preview" id="slidePreview"' + (isEdit && slide.image_url ? '' : ' style="display:none"') + '>' +
                        '<img src="' + (isEdit ? escapeHtml(slide.image_url) : '') + '" id="slidePreviewImg">' +
                        '<div class="slide-upload-change">Change image</div>' +
                    '</div>' +
                    '<div class="slide-upload-empty" id="slideUploadEmpty"' + (isEdit && slide.image_url ? ' style="display:none"' : '') + '>' +
                        '<i class="fa-solid fa-image" style="font-size:32px;opacity:0.3"></i>' +
                        '<span>Tap to upload banner image</span>' +
                    '</div>' +
                '</div>' +
                '<input type="hidden" id="slideImageUrl" value="' + (isEdit ? escapeHtml(slide.image_url) : '') + '">' +
            '</div>' +
            '<div class="form-group">' +
                '<label for="slideHeading">Heading</label>' +
                '<input type="text" class="form-control" id="slideHeading" value="' + (isEdit && slide.heading ? escapeHtml(slide.heading) : '') + '" placeholder="e.g. New Arrivals" maxlength="200">' +
            '</div>' +
            '<div class="form-group">' +
                '<label for="slideSubheading">Subheading</label>' +
                '<input type="text" class="form-control" id="slideSubheading" value="' + (isEdit && slide.subheading ? escapeHtml(slide.subheading) : '') + '" placeholder="e.g. Check out our latest products" maxlength="500">' +
            '</div>' +
            '<div class="form-group">' +
                '<label for="slideLinkUrl">Link URL</label>' +
                '<input type="text" class="form-control" id="slideLinkUrl" value="' + (isEdit && slide.link_url ? escapeHtml(slide.link_url) : '') + '" placeholder="e.g. /collections/new-arrivals">' +
            '</div>' +
            '<div class="form-group">' +
                '<label for="slideLinkText">Button Text</label>' +
                '<input type="text" class="form-control" id="slideLinkText" value="' + (isEdit && slide.link_text ? escapeHtml(slide.link_text) : '') + '" placeholder="Shop Now" maxlength="100">' +
            '</div>' +
            '<button type="submit" class="btn-block btn-primary" id="saveSlideBtn">' + (isEdit ? 'Update Slide' : 'Add Slide') + '</button>' +
        '</form>';

        TinyShop.openModal(title, html);

        // Image upload
        $('#slideUploadZone').on('click', function() {ldelim} $('#slideImageInput').click(); {rdelim});
        $('#slideImageInput').on('change', function() {ldelim}
            var file = this.files[0];
            if (!file) return;
            TinyShop.uploadFile(file, function(url) {ldelim}
                $('#slideImageUrl').val(url);
                $('#slidePreviewImg').attr('src', url);
                $('#slidePreview').show();
                $('#slideUploadEmpty').hide();
            {rdelim});
        {rdelim});

        // Submit
        $('#slideForm').on('submit', function(e) {ldelim}
            e.preventDefault();
            var imageUrl = $('#slideImageUrl').val();
            if (!imageUrl) {ldelim}
                TinyShop.toast('Please upload a banner image', 'error');
                return;
            {rdelim}

            var payload = {ldelim}
                image_url: imageUrl,
                heading: $('#slideHeading').val().trim(),
                subheading: $('#slideSubheading').val().trim(),
                link_url: $('#slideLinkUrl').val().trim(),
                link_text: $('#slideLinkText').val().trim()
            {rdelim};

            var $btn = $('#saveSlideBtn').prop('disabled', true).text('Saving...');

            if (isEdit) {ldelim}
                TinyShop.api('PUT', '/api/hero-slides/' + slide.id, payload).done(function(res) {ldelim}
                    TinyShop.toast('Slide updated!');
                    TinyShop.closeModal();
                    location.reload();
                {rdelim}).fail(function(xhr) {ldelim}
                    var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to update';
                    TinyShop.toast(msg, 'error');
                    $btn.prop('disabled', false).text('Update Slide');
                {rdelim});
            {rdelim} else {ldelim}
                TinyShop.api('POST', '/api/hero-slides', payload).done(function(res) {ldelim}
                    TinyShop.toast('Slide added!');
                    TinyShop.closeModal();
                    location.reload();
                {rdelim}).fail(function(xhr) {ldelim}
                    var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to add slide';
                    TinyShop.toast(msg, 'error');
                    $btn.prop('disabled', false).text('Add Slide');
                {rdelim});
            {rdelim}
        {rdelim});
    {rdelim}

    // Add slide
    $('#addSlideBtn').on('click', function() {ldelim}
        openSlideModal(null);
    {rdelim});

    // Edit slide
    $('#heroSlideList').on('click', '.hero-slide-edit', function() {ldelim}
        var $card = $(this).closest('.hero-slide-card');
        var id = $card.data('id');
        TinyShop.api('GET', '/api/hero-slides').done(function(res) {ldelim}
            var slide = (res.slides || []).find(function(s) {ldelim} return s.id == id; {rdelim});
            if (slide) openSlideModal(slide);
        {rdelim});
    {rdelim});

    // --- Drag to reorder ---
    (function() {ldelim}
        var list = document.getElementById('heroSlideList');
        if (!list) return;
        var dragEl = null;

        // Only allow drag from handle
        list.addEventListener('mousedown', function(e) {ldelim}
            var handle = e.target.closest('.hero-slide-handle');
            if (!handle) return;
            var card = handle.closest('.hero-slide-card');
            if (card) card.setAttribute('draggable', 'true');
        {rdelim});
        list.addEventListener('mouseup', function() {ldelim}
            list.querySelectorAll('.hero-slide-card').forEach(function(c) {ldelim}
                c.removeAttribute('draggable');
            {rdelim});
        {rdelim});

        // HTML5 drag events (desktop)
        list.addEventListener('dragstart', function(e) {ldelim}
            var card = e.target.closest('.hero-slide-card');
            if (!card) return;
            dragEl = card;
            card.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', '');
        {rdelim});
        list.addEventListener('dragover', function(e) {ldelim}
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            var target = e.target.closest('.hero-slide-card');
            list.querySelectorAll('.hero-slide-card').forEach(function(c) {ldelim} c.classList.remove('drag-over'); {rdelim});
            if (target && target !== dragEl) target.classList.add('drag-over');
        {rdelim});
        list.addEventListener('drop', function(e) {ldelim}
            e.preventDefault();
            var target = e.target.closest('.hero-slide-card');
            if (target && dragEl && target !== dragEl) {ldelim}
                var cards = Array.from(list.querySelectorAll('.hero-slide-card'));
                var fromIdx = cards.indexOf(dragEl);
                var toIdx = cards.indexOf(target);
                if (fromIdx < toIdx) {ldelim}
                    target.parentNode.insertBefore(dragEl, target.nextSibling);
                {rdelim} else {ldelim}
                    target.parentNode.insertBefore(dragEl, target);
                {rdelim}
                saveOrder();
            {rdelim}
        {rdelim});
        list.addEventListener('dragend', function() {ldelim}
            list.querySelectorAll('.hero-slide-card').forEach(function(c) {ldelim}
                c.classList.remove('dragging', 'drag-over');
            {rdelim});
            dragEl = null;
        {rdelim});

        // Touch events (mobile)
        var touchCard = null;
        var touchClone = null;
        var touchStartY = 0;
        var touchOffsetY = 0;

        list.addEventListener('touchstart', function(e) {ldelim}
            var handle = e.target.closest('.hero-slide-handle');
            if (!handle) return;
            var card = handle.closest('.hero-slide-card');
            if (!card) return;
            touchCard = card;
            var rect = card.getBoundingClientRect();
            touchStartY = e.touches[0].clientY;
            touchOffsetY = touchStartY - rect.top;

            touchClone = card.cloneNode(true);
            touchClone.classList.add('drag-clone');
            touchClone.style.width = rect.width + 'px';
            touchClone.style.top = rect.top + 'px';
            touchClone.style.left = rect.left + 'px';
            document.body.appendChild(touchClone);

            card.classList.add('dragging');
        {rdelim}, {ldelim} passive: true {rdelim});

        list.addEventListener('touchmove', function(e) {ldelim}
            if (!touchCard || !touchClone) return;
            e.preventDefault();
            var y = e.touches[0].clientY;
            touchClone.style.top = (y - touchOffsetY) + 'px';

            // Find card under finger
            touchClone.style.pointerEvents = 'none';
            var el = document.elementFromPoint(e.touches[0].clientX, y);
            touchClone.style.pointerEvents = '';
            var target = el ? el.closest('.hero-slide-card') : null;
            list.querySelectorAll('.hero-slide-card').forEach(function(c) {ldelim} c.classList.remove('drag-over'); {rdelim});
            if (target && target !== touchCard) target.classList.add('drag-over');
        {rdelim}, {ldelim} passive: false {rdelim});

        list.addEventListener('touchend', function() {ldelim}
            if (!touchCard) return;
            var overCard = list.querySelector('.hero-slide-card.drag-over');
            if (overCard && overCard !== touchCard) {ldelim}
                var cards = Array.from(list.querySelectorAll('.hero-slide-card'));
                var fromIdx = cards.indexOf(touchCard);
                var toIdx = cards.indexOf(overCard);
                if (fromIdx < toIdx) {ldelim}
                    overCard.parentNode.insertBefore(touchCard, overCard.nextSibling);
                {rdelim} else {ldelim}
                    overCard.parentNode.insertBefore(touchCard, overCard);
                {rdelim}
                saveOrder();
            {rdelim}
            list.querySelectorAll('.hero-slide-card').forEach(function(c) {ldelim}
                c.classList.remove('dragging', 'drag-over');
            {rdelim});
            if (touchClone) touchClone.remove();
            touchCard = null;
            touchClone = null;
        {rdelim});

        function saveOrder() {ldelim}
            var ids = [];
            list.querySelectorAll('.hero-slide-card').forEach(function(c) {ldelim}
                ids.push(Number(c.dataset.id));
            {rdelim});
            TinyShop.api('PUT', '/api/hero-slides/reorder', {ldelim} ids: ids {rdelim}).done(function() {ldelim}
                TinyShop.toast('Slide order saved');
            {rdelim}).fail(function() {ldelim}
                TinyShop.toast('Failed to save order', 'error');
            {rdelim});
        {rdelim}
    {rdelim})();

    // Delete slide
    $('#heroSlideList').on('click', '.hero-slide-delete', function() {ldelim}
        var $card = $(this).closest('.hero-slide-card');
        var id = $card.data('id');
        TinyShop.confirm('Delete this slide?', 'This will remove the banner from your shop.', 'Delete', function() {ldelim}
            $('#confirmModalOk').prop('disabled', true).text('Deleting...');
            TinyShop.api('DELETE', '/api/hero-slides/' + id).done(function() {ldelim}
                TinyShop.toast('Slide deleted');
                TinyShop.closeModal();
                $card.slideUp(200, function() {ldelim} $card.remove(); {rdelim});
            {rdelim}).fail(function() {ldelim}
                TinyShop.toast('Failed to delete', 'error');
                TinyShop.closeModal();
            {rdelim});
        {rdelim}, 'danger');
    {rdelim});
{rdelim});
</script>
{/block}
