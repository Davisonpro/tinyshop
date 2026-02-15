{extends file="layouts/base.tpl"}

{block name="body_class"}page-pricing{/block}

{block name="extra_css"}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="/public/css/marketing.css?v={$asset_v}">
<link rel="stylesheet" href="/public/css/pricing.css?v={$asset_v}">
{/block}
{block name="body"}

{include file="partials/marketing_nav.tpl" current_page="pricing"}

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
        {* Determine if this is the free plan *}
        {assign var="is_free" value=(($plan.price_monthly == 0 && $plan.price_yearly == 0) || $plan.price_monthly == 0)}
        {* Featured = non-free plan marked as default, or first paid plan *}
        {assign var="is_featured" value=($plan.is_default && !$is_free)}

        <div class="pricing-card{if $is_featured} pricing-card--featured{/if}">
            {if $is_featured}
                <span class="pricing-badge">Most popular</span>
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
                {if !$is_free}
                    &nbsp;
                {else}
                    No credit card needed
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

            {* Features *}
            <ul class="pricing-features">
                {* Products limit *}
                <li class="pricing-feature">
                    <span class="pricing-feature-icon pricing-feature-icon--yes">
                        <i class="fa-solid fa-check"></i>
                    </span>
                    {if $plan.max_products === null}
                        Unlimited products
                    {else}
                        Up to {$plan.max_products|number_format:0} products
                    {/if}
                </li>

                {* Shop designs *}
                <li class="pricing-feature">
                    <span class="pricing-feature-icon pricing-feature-icon--yes">
                        <i class="fa-solid fa-check"></i>
                    </span>
                    {if $plan.allowed_themes === null || $plan.allowed_themes === ''}
                        All shop designs
                    {else}
                        1 shop design
                    {/if}
                </li>

                {* Custom web address *}
                <li class="pricing-feature{if !$plan.custom_domain_allowed} pricing-feature--disabled{/if}">
                    <span class="pricing-feature-icon {if $plan.custom_domain_allowed}pricing-feature-icon--yes{else}pricing-feature-icon--no{/if}">
                        <i class="fa-solid {if $plan.custom_domain_allowed}fa-check{else}fa-xmark{/if}"></i>
                    </span>
                    Your own web address
                </li>

                {* Discount codes *}
                <li class="pricing-feature{if !$plan.coupons_allowed} pricing-feature--disabled{/if}">
                    <span class="pricing-feature-icon {if $plan.coupons_allowed}pricing-feature-icon--yes{else}pricing-feature-icon--no{/if}">
                        <i class="fa-solid {if $plan.coupons_allowed}fa-check{else}fa-xmark{/if}"></i>
                    </span>
                    Discount codes
                </li>
            </ul>

            {* CTA *}
            {if $is_free}
                <a href="/register" class="pricing-cta pricing-cta--secondary">Get started</a>
            {else}
                {if $logged_in}
                    <a href="/dashboard/billing" class="pricing-cta pricing-cta--primary">Upgrade now</a>
                {else}
                    <a href="/register" class="pricing-cta pricing-cta--primary">Upgrade now</a>
                {/if}
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

{include file="partials/marketing_footer.tpl"}

{/block}

{block name="page_scripts"}
<script src="/public/js/pricing.js?v={$asset_v}"></script>
{/block}