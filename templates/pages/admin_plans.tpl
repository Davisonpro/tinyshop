{extends file="layouts/admin.tpl"}

{block name="content"}
<div class="dash-topbar">
    <span class="dash-topbar-title">Plans</span>
</div>

<div class="admin-list-wrap" id="plansWrap">
    {if $plans|count == 0}
        <div class="empty-state">
            <div class="empty-icon">
                <i class="fa-solid fa-crown icon-2xl text-muted"></i>
            </div>
            <h2>No plans yet</h2>
            <p>Create your first plan to start monetizing</p>
        </div>
    {else}
        {foreach $plans as $plan}
        <div class="plan-card" data-plan-id="{$plan.id}">
            <div class="plan-card-header">
                <div class="plan-card-info">
                    <h3 class="plan-card-name">{$plan.name|escape}</h3>
                    {if $plan.is_default}<span class="plan-badge plan-badge-default">Default</span>{/if}
                    {if !$plan.is_active}<span class="plan-badge plan-badge-inactive">Inactive</span>{/if}
                </div>
                <button type="button" class="plan-card-edit" data-edit="{$plan.id}" aria-label="Edit plan">
                    <i class="fa-solid fa-pen icon-sm"></i>
                </button>
            </div>
            <div class="plan-card-pricing">
                {if $plan.price_monthly > 0}
                    <span class="plan-card-price">{$plan.currency} {$plan.price_monthly|number_format:0:".":","}</span>
                    <span class="plan-card-cycle">/month</span>
                    {if $plan.price_yearly > 0}
                        <span class="plan-card-yearly">&bull; {$plan.currency} {$plan.price_yearly|number_format:0:".":","}/year</span>
                    {/if}
                {else}
                    <span class="plan-card-price">Free</span>
                {/if}
            </div>
            {if $plan.description}
                <p class="plan-card-desc">{$plan.description|escape}</p>
            {/if}
            <div class="plan-card-features">
                <span class="plan-feature">
                    <i class="fa-solid fa-box"></i>
                    {if $plan.max_products}{$plan.max_products} products{else}Unlimited products{/if}
                </span>
                <span class="plan-feature">
                    <i class="fa-solid fa-palette"></i>
                    {if $plan.allowed_themes}Limited themes{else}All themes{/if}
                </span>
                <span class="plan-feature">
                    <i class="fa-solid fa-globe"></i>
                    {if $plan.custom_domain_allowed}Custom domain{else}No custom domain{/if}
                </span>
                <span class="plan-feature">
                    <i class="fa-solid fa-tag"></i>
                    {if $plan.coupons_allowed}Coupons{else}No coupons{/if}
                </span>
            </div>
            <div class="plan-card-footer">
                <span class="plan-card-subs">{$plan.subscriber_count} subscriber{if $plan.subscriber_count != 1}s{/if}</span>
                <span class="plan-card-order">Order: {$plan.sort_order}</span>
            </div>
        </div>
        {/foreach}
    {/if}
</div>

<button type="button" class="fab" id="addPlanFab" aria-label="Add plan">
    <i class="fa-solid fa-plus"></i>
</button>
{/block}

{block name="extra_scripts"}
<script>
(function() {ldelim}
    var plans = {$plans|json_encode};

    function openPlanForm(plan) {ldelim}
        var isEdit = !!plan;
        var p = plan || {ldelim}{rdelim};

        var themes = null;
        if (p.allowed_themes) {ldelim}
            try {ldelim} themes = JSON.parse(p.allowed_themes); {rdelim} catch(e) {ldelim}{rdelim}
        {rdelim}
        var allThemes = themes === null;

        var validThemes = ['classic','bloom','ember','ivory','monaco','obsidian','volt','halloween'];

        var html = '<form id="planForm" autocomplete="off">'
            + '<div class="form-group">'
            + '<label for="planName">Plan Name</label>'
            + '<input type="text" class="form-control" id="planName" value="' + (p.name || '').replace(/"/g, '&quot;') + '" placeholder="e.g. Pro" required>'
            + '</div>'
            + '<div class="form-group">'
            + '<label for="planDesc">Description</label>'
            + '<input type="text" class="form-control" id="planDesc" value="' + (p.description || '').replace(/"/g, '&quot;') + '" placeholder="Short description" maxlength="300">'
            + '</div>'
            + '<div class="form-group settings-inline-group">'
            + '<div class="settings-inline-field">'
            + '<label for="planPriceMonthly">Monthly Price</label>'
            + '<input type="number" class="form-control" id="planPriceMonthly" value="' + (p.price_monthly || 0) + '" min="0" step="0.01">'
            + '</div>'
            + '<div class="settings-inline-field">'
            + '<label for="planPriceYearly">Yearly Price</label>'
            + '<input type="number" class="form-control" id="planPriceYearly" value="' + (p.price_yearly || 0) + '" min="0" step="0.01">'
            + '</div>'
            + '</div>'
            + '<div class="form-group">'
            + '<label for="planCurrency">Currency</label>'
            + '<select class="form-control" id="planCurrency">';

        var currencies = ['KES','NGN','TZS','UGX','RWF','ETB','GHS','ZAR','USD','EUR','GBP','XOF'];
        var cur = p.currency || 'KES';
        for (var i = 0; i < currencies.length; i++) {ldelim}
            html += '<option value="' + currencies[i] + '"' + (currencies[i] === cur ? ' selected' : '') + '>' + currencies[i] + '</option>';
        {rdelim}

        html += '</select></div>'
            + '<div class="form-section-title mt-md"><i class="fa-solid fa-sliders icon-xs"></i> Features</div>'
            + '<div class="form-group">'
            + '<label for="planMaxProducts">Max Products</label>'
            + '<input type="number" class="form-control" id="planMaxProducts" value="' + (p.max_products !== null && p.max_products !== undefined ? p.max_products : '') + '" min="1" placeholder="Leave empty for unlimited">'
            + '</div>'
            + '<div class="form-group">'
            + '<label>Themes</label>'
            + '<div class="plan-themes-toggle">'
            + '<label class="plan-radio"><input type="radio" name="themeMode" value="all"' + (allThemes ? ' checked' : '') + '> All themes</label>'
            + '<label class="plan-radio"><input type="radio" name="themeMode" value="select"' + (!allThemes ? ' checked' : '') + '> Selected themes</label>'
            + '</div>'
            + '<div id="themeCheckboxes" style="' + (allThemes ? 'display:none;' : '') + 'margin-top:8px">';

        for (var t = 0; t < validThemes.length; t++) {ldelim}
            var checked = !allThemes && themes && themes.indexOf(validThemes[t]) >= 0;
            html += '<label class="plan-checkbox"><input type="checkbox" name="theme" value="' + validThemes[t] + '"' + (checked ? ' checked' : '') + '> ' + validThemes[t].charAt(0).toUpperCase() + validThemes[t].slice(1) + '</label>';
        {rdelim}

        html += '</div></div>'
            + '<div class="form-group">'
            + '<div class="settings-toggle-row"><div class="settings-toggle-info"><span class="settings-toggle-label">Custom Domain</span></div>'
            + '<label class="toggle-switch"><input type="checkbox" id="planCustomDomain"' + (p.custom_domain_allowed ? ' checked' : '') + '><span class="toggle-track"></span></label></div>'
            + '</div>'
            + '<div class="form-group">'
            + '<div class="settings-toggle-row"><div class="settings-toggle-info"><span class="settings-toggle-label">Coupons</span></div>'
            + '<label class="toggle-switch"><input type="checkbox" id="planCoupons"' + (p.coupons_allowed ? ' checked' : '') + '><span class="toggle-track"></span></label></div>'
            + '</div>'
            + '<div class="form-group">'
            + '<div class="settings-toggle-row"><div class="settings-toggle-info"><span class="settings-toggle-label">Default Plan</span><span class="settings-toggle-desc">Given to new sellers on signup</span></div>'
            + '<label class="toggle-switch"><input type="checkbox" id="planDefault"' + (p.is_default ? ' checked' : '') + '><span class="toggle-track"></span></label></div>'
            + '</div>'
            + '<div class="form-group">'
            + '<div class="settings-toggle-row"><div class="settings-toggle-info"><span class="settings-toggle-label">Active</span></div>'
            + '<label class="toggle-switch"><input type="checkbox" id="planActive"' + (p.is_active !== undefined ? (p.is_active ? ' checked' : '') : ' checked') + '><span class="toggle-track"></span></label></div>'
            + '</div>'
            + '<div class="form-group">'
            + '<label for="planSortOrder">Sort Order</label>'
            + '<input type="number" class="form-control" id="planSortOrder" value="' + (p.sort_order || 0) + '" min="0">'
            + '</div>'
            + '<div class="plan-form-actions">'
            + '<button type="submit" class="btn-primary">' + (isEdit ? 'Save Changes' : 'Create Plan') + '</button>';

        if (isEdit && !p.is_default) {ldelim}
            html += '<button type="button" class="btn-block btn-danger-outline" id="deletePlanBtn">Delete Plan</button>';
        {rdelim}

        html += '</div></form>';

        TinyShop.openModal(isEdit ? 'Edit Plan' : 'New Plan', html);

        // Theme mode toggle
        $('input[name="themeMode"]').on('change', function() {ldelim}
            $('#themeCheckboxes').toggle(this.value === 'select');
        {rdelim});

        // Save handler
        $('#planForm').on('submit', function(e) {ldelim}
            e.preventDefault();
            var formData = {ldelim}
                name: $('#planName').val().trim(),
                description: $('#planDesc').val().trim(),
                price_monthly: parseFloat($('#planPriceMonthly').val()) || 0,
                price_yearly: parseFloat($('#planPriceYearly').val()) || 0,
                currency: $('#planCurrency').val(),
                max_products: $('#planMaxProducts').val() || null,
                custom_domain_allowed: $('#planCustomDomain').is(':checked') ? 1 : 0,
                coupons_allowed: $('#planCoupons').is(':checked') ? 1 : 0,
                is_default: $('#planDefault').is(':checked') ? 1 : 0,
                is_active: $('#planActive').is(':checked') ? 1 : 0,
                sort_order: parseInt($('#planSortOrder').val()) || 0
            {rdelim};

            if ($('input[name="themeMode"]:checked').val() === 'all') {ldelim}
                formData.allowed_themes = 'all';
            {rdelim} else {ldelim}
                var sel = [];
                $('input[name="theme"]:checked').each(function() {ldelim} sel.push(this.value); {rdelim});
                formData.allowed_themes = sel.length > 0 ? sel : ['classic'];
            {rdelim}

            if (!formData.name) {ldelim} TinyShop.toast('Plan name is required', 'error'); return; {rdelim}

            var method = isEdit ? 'PUT' : 'POST';
            var url = isEdit ? '/api/admin/plans/' + p.id : '/api/admin/plans';

            var $btn = $(this).find('[type="submit"]').prop('disabled', true).text('Saving...');

            TinyShop.api(method, url, formData).done(function() {ldelim}
                TinyShop.toast(isEdit ? 'Plan updated' : 'Plan created');
                TinyShop.closeModal();
                location.reload();
            {rdelim}).fail(function(xhr) {ldelim}
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to save plan';
                TinyShop.toast(msg, 'error');
                $btn.prop('disabled', false).text(isEdit ? 'Save Changes' : 'Create Plan');
            {rdelim});
        {rdelim});

        // Delete handler
        if (isEdit) {ldelim}
            $('#deletePlanBtn').on('click', function() {ldelim}
                TinyShop.confirm({ldelim}
                    title: 'Delete Plan',
                    message: 'Are you sure you want to delete "' + p.name + '"? This cannot be undone.',
                    confirmText: 'Delete',
                    variant: 'danger',
                    onConfirm: function() {ldelim}
                        TinyShop.api('DELETE', '/api/admin/plans/' + p.id).done(function() {ldelim}
                            TinyShop.toast('Plan deleted');
                            TinyShop.closeModal();
                            location.reload();
                        {rdelim}).fail(function(xhr) {ldelim}
                            var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Cannot delete this plan';
                            TinyShop.toast(msg, 'error');
                        {rdelim});
                    {rdelim}
                {rdelim});
            {rdelim});
        {rdelim}
    {rdelim}

    // Add plan button
    $('#addPlanFab').on('click', function() {ldelim}
        openPlanForm(null);
    {rdelim});

    // Edit plan buttons
    $(document).on('click', '[data-edit]', function() {ldelim}
        var id = parseInt($(this).data('edit'));
        for (var i = 0; i < plans.length; i++) {ldelim}
            if (parseInt(plans[i].id) === id) {ldelim}
                openPlanForm(plans[i]);
                return;
            {rdelim}
        {rdelim}
    {rdelim});
{rdelim})();
</script>
{/block}
