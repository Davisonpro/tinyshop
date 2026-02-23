{extends file="layouts/base.tpl"}

{block name="body_class"}page-pricing{/block}

{block name="extra_css"}
<link rel="stylesheet" href="/public/css/pricing{$min}.css?v={$asset_v}">
{/block}
{block name="body"}

{include file="partials/public/nav.tpl" current_page="pricing"}

{* ── Header ── *}
<header class="pricing-header">
    <h1>Simple pricing</h1>
    <p>Start free, upgrade when you're ready. No hidden fees, no surprises.</p>
</header>

{* ── Monthly / Yearly toggle ── *}
<div class="pricing-toggle-wrap">
    <div class="pricing-toggle">
        <button type="button" class="pricing-toggle-btn active" data-period="monthly">Monthly</button>
        <button type="button" class="pricing-toggle-btn" data-period="yearly">
            Yearly
            <span class="pricing-savings-badge">Save up to 20%</span>
        </button>
    </div>
</div>

{* ── Plan cards ── *}
<div class="pricing-cards">
    {foreach $plans as $plan}
        {assign var="is_free" value=($plan.price_monthly == 0 && $plan.price_yearly == 0)}
        {assign var="is_featured" value=($plan.is_featured)}

        <div class="pricing-card{if $is_featured} pricing-card--featured{/if}">
            {if $plan.badge_text}
                <span class="pricing-badge">{$plan.badge_text|escape}</span>
            {/if}

            <h2 class="pricing-plan-name">{$plan.name|escape}</h2>
            {if $plan.description}
                <p class="pricing-plan-desc">{$plan.description|escape}</p>
            {else}
                <p class="pricing-plan-desc">&nbsp;</p>
            {/if}

            {* Monthly price (shown by default) *}
            <div class="pricing-price" data-monthly>
                {if $is_free}
                    <span class="pricing-amount">Free</span>
                {else}
                    <span class="pricing-currency">{$plan.currency|escape}</span>
                    <span class="pricing-amount">{$plan.price_monthly|number_format:0:".":","}</span>
                    <span class="pricing-period">/month</span>
                {/if}
            </div>

            {* Yearly price (hidden by default) *}
            <div class="pricing-price" data-yearly style="display:none">
                {if $is_free}
                    <span class="pricing-amount">Free</span>
                {else}
                    <span class="pricing-currency">{$plan.currency|escape}</span>
                    <span class="pricing-amount">{($plan.price_yearly / 12)|number_format:0:".":","}</span>
                    <span class="pricing-period">/month</span>
                {/if}
            </div>

            {* Yearly note *}
            <p class="pricing-yearly-note" data-monthly>
                {if $is_free}
                    No credit card needed
                {else}
                    &nbsp;
                {/if}
            </p>
            <p class="pricing-yearly-note" data-yearly style="display:none">
                {if !$is_free}
                    {if $plan.price_yearly > 0}
                        <span class="pricing-yearly-savings">
                            {$plan.currency|escape} {$plan.price_yearly|number_format:0:".":","}/year
                            {if $plan.price_monthly > 0}
                                {assign var="yearly_equiv" value=($plan.price_monthly * 12)}
                                {if $yearly_equiv > $plan.price_yearly}
                                    &mdash; save {$plan.currency|escape} {($yearly_equiv - $plan.price_yearly)|number_format:0:".":","}
                                {/if}
                            {/if}
                        </span>
                    {/if}
                {else}
                    No credit card needed
                {/if}
            </p>

            {* Features — from pre-decoded feature_list or fallback to structured fields *}
            <ul class="pricing-features">
                {if $plan.feature_list}
                    {foreach $plan.feature_list as $feature}
                        <li class="pricing-feature">
                            <span class="pricing-feature-icon pricing-feature-icon--yes">
                                <i class="fa-solid fa-check"></i>
                            </span>
                            {$feature|escape}
                        </li>
                    {/foreach}
                {else}
                    {* Fallback: auto-generate from plan fields *}
                    <li class="pricing-feature">
                        <span class="pricing-feature-icon pricing-feature-icon--yes">
                            <i class="fa-solid fa-check"></i>
                        </span>
                        {if $plan.max_products === null}Unlimited products{else}Up to {$plan.max_products|number_format:0} products{/if}
                    </li>
                    <li class="pricing-feature">
                        <span class="pricing-feature-icon pricing-feature-icon--yes">
                            <i class="fa-solid fa-check"></i>
                        </span>
                        {if $plan.allowed_themes === null || $plan.allowed_themes === ''}All shop designs{else}1 shop design{/if}
                    </li>
                    <li class="pricing-feature{if !$plan.custom_domain_allowed} pricing-feature--disabled{/if}">
                        <span class="pricing-feature-icon {if $plan.custom_domain_allowed}pricing-feature-icon--yes{else}pricing-feature-icon--no{/if}">
                            <i class="fa-solid {if $plan.custom_domain_allowed}fa-check{else}fa-xmark{/if}"></i>
                        </span>
                        Your own web address
                    </li>
                    <li class="pricing-feature{if !$plan.coupons_allowed} pricing-feature--disabled{/if}">
                        <span class="pricing-feature-icon {if $plan.coupons_allowed}pricing-feature-icon--yes{else}pricing-feature-icon--no{/if}">
                            <i class="fa-solid {if $plan.coupons_allowed}fa-check{else}fa-xmark{/if}"></i>
                        </span>
                        Discount codes
                    </li>
                {/if}
            </ul>

            {* CTA — from DB or fallback *}
            {assign var="cta_label" value=$plan.cta_text|default:($is_free ? 'Get started' : 'Upgrade now')}
            {assign var="cta_style" value=($is_featured ? 'primary' : ($is_free ? 'secondary' : 'primary'))}

            {if $logged_in}
                {if $is_free}
                    <a href="/dashboard" class="pricing-cta pricing-cta--{$cta_style}">{$cta_label|escape}</a>
                {else}
                    <a href="/dashboard/billing" class="pricing-cta pricing-cta--{$cta_style}">{$cta_label|escape}</a>
                {/if}
            {else}
                <a href="/register" class="pricing-cta pricing-cta--{$cta_style}">{$cta_label|escape}</a>
            {/if}
        </div>
    {/foreach}
</div>

{* ── FAQ ── *}
<section class="pricing-faq">
    <h2 class="pricing-faq-title">Common questions</h2>
    <div class="pricing-faq-list">
        <div class="pricing-faq-item">
            <p class="pricing-faq-q">Can I really start for free?</p>
            <p class="pricing-faq-a">Yes. Create your shop, add products, and start selling right away. No credit card required. You only pay if you decide to upgrade for extra features.</p>
        </div>
        <div class="pricing-faq-item">
            <p class="pricing-faq-q">Can I change my plan later?</p>
            <p class="pricing-faq-a">Absolutely. You can upgrade or downgrade at any time from your dashboard. Changes take effect immediately.</p>
        </div>
        <div class="pricing-faq-item">
            <p class="pricing-faq-q">What happens if I cancel a paid plan?</p>
            <p class="pricing-faq-a">Your shop stays live and you keep access until the end of your billing period. After that, you move back to the free plan. You never lose your products or orders.</p>
        </div>
        <div class="pricing-faq-item">
            <p class="pricing-faq-q">Is there a transaction fee?</p>
            <p class="pricing-faq-a">{$app_name} does not charge any transaction fees. Your payment provider (like M-Pesa or card processor) may have their own standard fees.</p>
        </div>
    </div>
</section>

{* ── Bottom CTA ── *}
<section class="pricing-bottom">
    <div class="pricing-bottom-inner">
        <h2>Ready to get started?</h2>
        <p>Create your shop and start selling in minutes. Free plan included.</p>
        <div class="pricing-bottom-form">
            <span class="pricing-bottom-url"><i class="fa-solid fa-link"></i> yourshop.{$base_domain|default:'tinyshop.com'}</span>
            <a href="/register" class="pricing-bottom-btn">Claim your shop</a>
        </div>
    </div>
</section>

{include file="partials/public/footer.tpl"}

{/block}

{block name="page_scripts"}
<script src="/public/js/pricing{$min}.js?v={$asset_v}"></script>
{/block}