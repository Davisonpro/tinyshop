{extends file="layouts/base.tpl"}

{block name="body_class"}page-landing{/block}

{block name="extra_css"}
<link rel="stylesheet" href="/public/css/landing{$min}.css?v={$asset_v}">
{/block}

{block name="body"}

{include file="partials/public/nav.tpl"}

<div class="land-hero-wrap">
    <div class="land-hero-desktop">
        <section class="land-hero">
            <div class="land-badge">Free forever &mdash; no card needed</div>
            <h1>Everything you sell, in one link</h1>
            <p class="land-hero-sub">Stop sending screenshots and price lists over DM. Create your shop link, share it on WhatsApp or Instagram, and let customers browse and order on their own.</p>
            <a href="/register" class="land-cta">
                Create your shop &mdash; it&rsquo;s free
                <i class="fa-solid fa-arrow-right"></i>
            </a>
            <small class="land-hero-small">Live in under 2 minutes</small>
        </section>

        <div class="land-phone-area">
            <div class="land-phone">
                <div class="land-phone-frame">
                    <div class="land-phone-notch"></div>
                    <div class="land-phone-screen">
                        <div class="land-mock-hdr">
                            <div class="land-mock-logo">M</div>
                            <div class="land-mock-shopname">Mary's Kitchen</div>
                            <div class="land-mock-shoptag">Homemade cakes & pastries</div>
                        </div>
                        <div class="land-mock-pills">
                            <span class="lm-wa"><i class="fa-brands fa-whatsapp"></i> WhatsApp</span>
                            <span><i class="fa-solid fa-envelope"></i> Email</span>
                            <span><i class="fa-solid fa-arrow-up-from-bracket"></i> Share</span>
                        </div>
                        <div class="land-mock-social">
                            <span><i class="fa-brands fa-instagram"></i></span>
                            <span><i class="fa-brands fa-tiktok"></i></span>
                        </div>
                        <div class="land-mock-search">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <span>Search products...</span>
                        </div>
                        <div class="land-mock-tabs">
                            <span class="active">All</span>
                            <span>Cakes</span>
                            <span>Pastries</span>
                            <span>Drinks</span>
                        </div>
                        <div class="land-mock-grid">
                            <div class="land-mock-card">
                                <div class="land-mock-img" style="background:#E8D5C4">
                                    <span class="lm-badge">-20%</span>
                                </div>
                                <div class="lm-info">
                                    <div class="lm-name">Chocolate Cake</div>
                                    <div class="lm-price"><s>KES 1,500</s> KES 1,200</div>
                                </div>
                            </div>
                            <div class="land-mock-card">
                                <div class="land-mock-img" style="background:#C5D5C0"></div>
                                <div class="lm-info">
                                    <div class="lm-name">Red Velvet</div>
                                    <div class="lm-price">KES 1,500</div>
                                </div>
                            </div>
                            <div class="land-mock-card">
                                <div class="land-mock-img" style="background:#D4C5E0"></div>
                                <div class="lm-info">
                                    <div class="lm-name">Blueberry Muffin</div>
                                    <div class="lm-price">KES 350</div>
                                </div>
                            </div>
                            <div class="land-mock-card">
                                <div class="land-mock-img" style="background:#C0D8E8"></div>
                                <div class="lm-info">
                                    <div class="lm-name">Fresh Juice</div>
                                    <div class="lm-price">KES 250</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="land-urls-strip">
        <div class="land-urls-label">Every shop gets its own link</div>
        <div class="land-urls">
            {if $showcased_shops && $showcased_shops|count > 0}
                {foreach $showcased_shops as $shop}
                <a href="{$scheme}://{$shop.subdomain|escape}.{$base_domain}" class="land-url" target="_blank"><b>{$shop.subdomain|escape}</b>.{$base_domain}</a>
                {/foreach}
            {else}
                <span class="land-url"><b>yourshop</b>.{$base_domain|default:'tinyshop.com'}</span>
            {/if}
        </div>
    </div>
</div>

{* ── How It Works ── *}
<section class="land-steps">
    <div class="land-steps-inner">
        <h2 class="land-reveal">Go live in minutes</h2>
        <p class="land-steps-sub land-reveal d1">No developers. No complicated setup. Just you and your phone.</p>
        <div class="land-steps-grid">
            <div class="land-step land-reveal d2">
                <div class="land-step-icon">
                    <i class="fa-solid fa-store"></i>
                    <span class="land-step-num">1</span>
                </div>
                <h3>Pick your shop name</h3>
                <p>Choose a name and get your own link &mdash; like a username for your business.</p>
            </div>
            <div class="land-step land-reveal d3">
                <div class="land-step-icon">
                    <i class="fa-solid fa-camera"></i>
                    <span class="land-step-num">2</span>
                </div>
                <h3>Add what you sell</h3>
                <p>Upload photos, set prices, organize by category. All from your phone.</p>
            </div>
            <div class="land-step land-reveal d4">
                <div class="land-step-icon">
                    <i class="fa-solid fa-share-nodes"></i>
                    <span class="land-step-num">3</span>
                </div>
                <h3>Share &amp; earn</h3>
                <p>Drop your link on WhatsApp, Instagram, TikTok &mdash; wherever your customers are.</p>
            </div>
        </div>
    </div>
</section>

{* ── Features ── *}
<section class="land-features">
    <div class="land-features-inner">
        <h2 class="land-reveal">Everything you need to sell online</h2>
        <div class="land-features-grid">
            <div class="land-feature-card land-reveal d2">
                <div class="land-feature-icon coral">
                    <i class="fa-solid fa-box-open"></i>
                </div>
                <h3>Your product catalog</h3>
                <p>All your items in one place. No more sending photos one by one.</p>
            </div>
            <div class="land-feature-card land-reveal d2">
                <div class="land-feature-icon blue">
                    <i class="fa-solid fa-credit-card"></i>
                </div>
                <h3>Collect payments</h3>
                <p>M-Pesa, cards, or PayPal &mdash; customers pay directly on your shop.</p>
            </div>
            <div class="land-feature-card land-reveal d3">
                <div class="land-feature-icon green">
                    <i class="fa-solid fa-mobile-screen"></i>
                </div>
                <h3>Mobile-first design</h3>
                <p>Your shop looks great on any phone. Because that&rsquo;s where your customers are.</p>
            </div>
            <div class="land-feature-card land-reveal d3">
                <div class="land-feature-icon purple">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
                <h3>Sales dashboard</h3>
                <p>See your views, orders, and best sellers. Know exactly what&rsquo;s working.</p>
            </div>
            <div class="land-feature-card land-reveal d4">
                <div class="land-feature-icon amber">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
                <h3>Smart inventory</h3>
                <p>Stock updates automatically when you make a sale. No spreadsheets needed.</p>
            </div>
            <div class="land-feature-card land-reveal d4">
                <div class="land-feature-icon rose">
                    <i class="fa-solid fa-ticket"></i>
                </div>
                <h3>Coupons &amp; promos</h3>
                <p>Create discount codes to bring customers back. Takes 10 seconds.</p>
            </div>
        </div>
    </div>
</section>

{* ── Who Is This For ── *}
<section class="land-usecases">
    <div class="land-usecases-inner">
        <h2 class="land-reveal">Whatever you sell, there&rsquo;s a shop for that</h2>
        <div class="land-usecases-grid">
            <div class="land-usecase land-reveal d2">
                <div class="land-usecase-icon coral">
                    <i class="fa-solid fa-shirt"></i>
                </div>
                <div>
                    <h3>Thrift &amp; fashion</h3>
                    <p>Show your full collection instead of posting one item at a time on your status.</p>
                </div>
            </div>
            <div class="land-usecase land-reveal d2">
                <div class="land-usecase-icon amber">
                    <i class="fa-solid fa-utensils"></i>
                </div>
                <div>
                    <h3>Food &amp; bakery</h3>
                    <p>Put your full menu online. Customers order what they want &mdash; no phone calls needed.</p>
                </div>
            </div>
            <div class="land-usecase land-reveal d3">
                <div class="land-usecase-icon purple">
                    <i class="fa-solid fa-palette"></i>
                </div>
                <div>
                    <h3>Artists &amp; crafters</h3>
                    <p>Give your work a proper home. A clean storefront you can share with anyone.</p>
                </div>
            </div>
            <div class="land-usecase land-reveal d3">
                <div class="land-usecase-icon blue">
                    <i class="fa-solid fa-basket-shopping"></i>
                </div>
                <div>
                    <h3>Any small business</h3>
                    <p>Get online today. Your customers are already there.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{* ── Voices ── *}
<section class="land-voices">
    <div class="land-voices-inner">
        <h2 class="land-reveal">Hear from real sellers</h2>
        <div class="land-voices-grid">
            <div class="land-voice land-voice--lavender land-reveal d1">
                <p class="land-voice-quote">&ldquo;I used to answer &lsquo;how much?&rsquo; a hundred times a day. Now my shop does that for me.&rdquo;</p>
                <span class="land-voice-who">Amara &middot; Nairobi Thrifts</span>
            </div>
            <div class="land-voice land-voice--green land-reveal d2">
                <p class="land-voice-quote">&ldquo;I set up my menu in 10 minutes. That same week, I got my first online order.&rdquo;</p>
                <span class="land-voice-who">Mary &middot; Mary&rsquo;s Kitchen</span>
            </div>
            <div class="land-voice land-voice--poppy land-reveal d3">
                <p class="land-voice-quote">&ldquo;Other platforms were too complicated and expensive. This one just works.&rdquo;</p>
                <span class="land-voice-who">Fatima &middot; Mia Boutique</span>
            </div>
        </div>
    </div>
</section>

{* ── Bottom CTA ── *}
<section class="land-bottom">
    <div class="land-bottom-inner">
        <h2 class="land-bottom-text land-reveal">Your next customer is one link away</h2>
        <p class="land-bottom-sub land-reveal d1">Create your shop. Share it everywhere. Start getting orders.</p>
        <div class="land-bottom-form land-reveal d2">
            <span class="land-bottom-url"><i class="fa-solid fa-link"></i> yourshop.{$base_domain|default:'tinyshop.com'}</span>
            <a href="/register" class="land-bottom-btn">Get started &mdash; it&rsquo;s free</a>
        </div>
    </div>
</section>

{include file="partials/public/footer.tpl"}

{/block}

{block name="page_scripts"}
<script src="/public/js/landing{$min}.js?v={$asset_v}"></script>
{/block}