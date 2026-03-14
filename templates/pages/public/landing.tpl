{extends file="layouts/base.tpl"}

{block name="body_class"}page-landing{/block}

{block name="extra_css"}
<link rel="stylesheet" href="/public/css/landing{$min}.css?v={$asset_v}">
{/block}

{block name="body"}

{include file="partials/public/nav.tpl"}

<div class="land-hero-wrap">
    <div class="land-hero-glow"></div>
    <div class="land-hero-desktop">
        <section class="land-hero">
            <div class="land-badge"><i class="fa-solid fa-sparkles"></i> Free forever &mdash; no card needed</div>
            <h1>Everything you sell,<br><span class="land-hero-accent">in one link</span></h1>
            <p class="land-hero-sub">Stop sending screenshots and price lists over DM. Create your shop link, share it on WhatsApp or Instagram, and let customers browse and order on their own.</p>
            <div class="land-hero-actions">
                <a href="/register" class="land-cta">
                    Create your shop &mdash; it&rsquo;s free
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
                <small class="land-hero-small"><i class="fa-solid fa-bolt"></i> Live in under 2 minutes</small>
            </div>
        </section>

        <div class="land-phone-area">
            <div class="land-phone">
                <div class="land-phone-frame">
                    <div class="land-phone-notch"></div>
                    <div class="land-phone-screen">
                        <div class="land-mock-hdr">
                            <div class="land-mock-logo">N</div>
                            <div class="land-mock-shopname">Nairobi Thrifts</div>
                            <div class="land-mock-shoptag">Curated vintage &amp; streetwear</div>
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
                            <span>Tops</span>
                            <span>Dresses</span>
                            <span>Shoes</span>
                        </div>
                        <div class="land-mock-grid">
                            <div class="land-mock-card">
                                <div class="land-mock-img" style="background:#E8D5C4">
                                    <span class="lm-badge">-30%</span>
                                </div>
                                <div class="lm-info">
                                    <div class="lm-name">Vintage Denim Jacket</div>
                                    <div class="lm-price"><s>KES 2,500</s> KES 1,750</div>
                                </div>
                            </div>
                            <div class="land-mock-card">
                                <div class="land-mock-img" style="background:#C5D5C0"></div>
                                <div class="lm-info">
                                    <div class="lm-name">Floral Midi Dress</div>
                                    <div class="lm-price">KES 1,800</div>
                                </div>
                            </div>
                            <div class="land-mock-card">
                                <div class="land-mock-img" style="background:#D4C5E0"></div>
                                <div class="lm-info">
                                    <div class="lm-name">Retro Sneakers</div>
                                    <div class="lm-price">KES 3,200</div>
                                </div>
                            </div>
                            <div class="land-mock-card">
                                <div class="land-mock-img" style="background:#C0D8E8"></div>
                                <div class="lm-info">
                                    <div class="lm-name">Graphic Tee</div>
                                    <div class="lm-price">KES 800</div>
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
                {if !empty($shop.custom_domain)}
                <a href="https://{$shop.custom_domain|escape}" class="land-url" target="_blank"><b>{$shop.custom_domain|escape}</b></a>
                {else}
                <a href="{$scheme}://{$shop.subdomain|escape}.{$base_domain}" class="land-url" target="_blank"><b>{$shop.subdomain|escape}</b>.{$base_domain}</a>
                {/if}
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
        <span class="land-label land-reveal">How it works</span>
        <h2 class="land-reveal d1">Go live in minutes</h2>
        <p class="land-steps-sub land-reveal d2">No developers. No complicated setup. Just you and your phone.</p>
        <div class="land-steps-grid">
            <div class="land-step land-reveal d3">
                <div class="land-step-icon">
                    <i class="fa-solid fa-store"></i>
                    <span class="land-step-num">1</span>
                </div>
                <h3>Pick your shop name</h3>
                <p>Choose a name and get your own link &mdash; like a username for your business.</p>
            </div>
            <div class="land-step land-reveal d4">
                <div class="land-step-icon">
                    <i class="fa-solid fa-camera"></i>
                    <span class="land-step-num">2</span>
                </div>
                <h3>Add what you sell</h3>
                <p>Upload photos, set prices, organize by category. All from your phone.</p>
            </div>
            <div class="land-step land-reveal d5">
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
        <span class="land-label land-reveal">Features</span>
        <h2 class="land-reveal d1">Everything you need to sell online</h2>
        <div class="land-features-grid">
            <div class="land-feature-card land-feature-card--wide land-reveal d2">
                <div class="land-feature-icon coral">
                    <i class="fa-solid fa-box-open"></i>
                </div>
                <h3>Stop sending photos one by one</h3>
                <p>All your items in one beautiful catalog. Customers browse on their own &mdash; no more DM back-and-forth.</p>
            </div>
            <div class="land-feature-card land-feature-card--wide land-reveal d2">
                <div class="land-feature-icon blue">
                    <i class="fa-solid fa-credit-card"></i>
                </div>
                <h3>Get paid without the hassle</h3>
                <p>M-Pesa, cards, or PayPal &mdash; customers pay right on your shop. No more &ldquo;send to till number&rdquo; texts.</p>
            </div>
            <div class="land-feature-card land-reveal d3">
                <div class="land-feature-icon green">
                    <i class="fa-solid fa-mobile-screen"></i>
                </div>
                <h3>Looks amazing on every phone</h3>
                <p>Your customers are on their phones. Your shop will look perfect there &mdash; automatically.</p>
            </div>
            <div class="land-feature-card land-reveal d3">
                <div class="land-feature-icon purple">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
                <h3>See what&rsquo;s actually selling</h3>
                <p>Track views, orders, and best sellers. Know what to restock and what to promote.</p>
            </div>
            <div class="land-feature-card land-reveal d4">
                <div class="land-feature-icon amber">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
                <h3>Never oversell again</h3>
                <p>Stock updates automatically when someone buys. No spreadsheets, no guessing.</p>
            </div>
            <div class="land-feature-card land-reveal d4">
                <div class="land-feature-icon rose">
                    <i class="fa-solid fa-ticket"></i>
                </div>
                <h3>Bring customers back for more</h3>
                <p>Create discount codes in 10 seconds. Share them on your status and watch orders come in.</p>
            </div>
        </div>
    </div>
</section>

{* ── Who Is This For ── *}
<section class="land-usecases">
    <div class="land-usecases-inner">
        <span class="land-label land-label--light land-reveal">Who is it for</span>
        <h2 class="land-reveal d1">Whatever you sell, there&rsquo;s a shop for that</h2>
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
        <span class="land-label land-reveal">Testimonials</span>
        <h2 class="land-reveal d1">Hear from real sellers</h2>
        <div class="land-voices-grid">
            <div class="land-voice land-reveal d2">
                <div class="land-voice-stars">
                    <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                </div>
                <p class="land-voice-quote">&ldquo;I used to answer &lsquo;how much?&rsquo; a hundred times a day. Now my shop does that for me.&rdquo;</p>
                <div class="land-voice-footer">
                    <span class="land-voice-avatar">A</span>
                    <div>
                        <span class="land-voice-name">Amara</span>
                        <span class="land-voice-biz">Nairobi Thrifts</span>
                    </div>
                </div>
            </div>
            <div class="land-voice land-reveal d3">
                <div class="land-voice-stars">
                    <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                </div>
                <p class="land-voice-quote">&ldquo;I set up my menu in 10 minutes. That same week, I got my first online order.&rdquo;</p>
                <div class="land-voice-footer">
                    <span class="land-voice-avatar">M</span>
                    <div>
                        <span class="land-voice-name">Mary</span>
                        <span class="land-voice-biz">Mary&rsquo;s Kitchen</span>
                    </div>
                </div>
            </div>
            <div class="land-voice land-reveal d4">
                <div class="land-voice-stars">
                    <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                </div>
                <p class="land-voice-quote">&ldquo;Other platforms were too complicated and expensive. This one just works.&rdquo;</p>
                <div class="land-voice-footer">
                    <span class="land-voice-avatar">F</span>
                    <div>
                        <span class="land-voice-name">Fatima</span>
                        <span class="land-voice-biz">Mia Boutique</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{* ── FAQ / Objections ── *}
<section class="land-faq">
    <div class="land-faq-inner">
        <span class="land-label land-reveal">FAQ</span>
        <h2 class="land-reveal d1">Questions? We get it.</h2>
        <div class="land-faq-list">
            <div class="land-faq-item land-reveal d2">
                <button type="button" class="land-faq-q" aria-expanded="false">
                    <span>Is it really free?</span>
                    <i class="fa-solid fa-plus"></i>
                </button>
                <div class="land-faq-a">
                    <p>Yes. You can create your shop, add products, and start selling without paying anything. No credit card, no trial period. We offer paid plans later if you want extras like a custom domain &mdash; but the free plan works forever.</p>
                </div>
            </div>
            <div class="land-faq-item land-reveal d3">
                <button type="button" class="land-faq-q" aria-expanded="false">
                    <span>Do I need tech skills?</span>
                    <i class="fa-solid fa-plus"></i>
                </button>
                <div class="land-faq-a">
                    <p>Not at all. If you can post on WhatsApp or Instagram, you can set up your shop. Pick a name, upload photos, set prices &mdash; done. Most sellers go live in under 5 minutes.</p>
                </div>
            </div>
            <div class="land-faq-item land-reveal d3">
                <button type="button" class="land-faq-q" aria-expanded="false">
                    <span>How do I get paid?</span>
                    <i class="fa-solid fa-plus"></i>
                </button>
                <div class="land-faq-a">
                    <p>You connect your M-Pesa, bank account, or PayPal. When a customer places an order, payment goes directly to you. We don&rsquo;t hold your money or take a cut of your sales.</p>
                </div>
            </div>
            <div class="land-faq-item land-reveal d4">
                <button type="button" class="land-faq-q" aria-expanded="false">
                    <span>Can I use my own domain name?</span>
                    <i class="fa-solid fa-plus"></i>
                </button>
                <div class="land-faq-a">
                    <p>Yes! You get a free link like yourshop.{$base_domain|default:'myduka.link'} right away. If you have your own domain (like mystore.com), you can connect it in your shop settings &mdash; SSL certificate included.</p>
                </div>
            </div>
            <div class="land-faq-item land-reveal d4">
                <button type="button" class="land-faq-q" aria-expanded="false">
                    <span>What can I sell?</span>
                    <i class="fa-solid fa-plus"></i>
                </button>
                <div class="land-faq-a">
                    <p>Clothes, food, art, electronics, beauty products &mdash; anything legal. Whether you have 5 items or 500, your shop handles it. Sellers use {$app_name|default:'MyDuka'} for everything from thrift fashion to homemade cakes.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{* ── Bottom CTA + Footer ── *}
<section class="land-bottom">
    <div class="land-bottom-glow"></div>
    <div class="land-bottom-inner">
        <h2 class="land-bottom-text land-reveal">Your next customer is<br>one link away</h2>
        <p class="land-bottom-sub land-reveal d1">Create your shop. Share it everywhere. Start getting orders.</p>
        <div class="land-bottom-form land-reveal d2">
            <span class="land-bottom-url"><i class="fa-solid fa-link"></i> yourshop.{$base_domain|default:'tinyshop.com'}</span>
            <a href="/register" class="land-bottom-btn">Get started &mdash; it&rsquo;s free</a>
        </div>
    </div>
    <div class="land-bottom-footer">
        <div class="mk-footer-cols">
            <div class="mk-footer-col">
                <div class="mk-footer-col-title">{$app_name}</div>
                <div class="mk-footer-col-links">
                    <a href="/" class="mk-footer-link">Home</a>
                    <a href="/pricing" class="mk-footer-link">Pricing</a>
                    <a href="/login" class="mk-footer-link">Log in</a>
                    <a href="/register" class="mk-footer-link">Sign up</a>
                </div>
            </div>
            <div class="mk-footer-col">
                <div class="mk-footer-col-title">Support</div>
                <div class="mk-footer-col-links">
                    <a href="/help" class="mk-footer-link">Help center</a>
                    <a href="mailto:{if $support_email}{$support_email|escape}{else}hello@{$base_domain|default:'tinyshop.com'}{/if}" class="mk-footer-link">Contact us</a>
                </div>
            </div>
            <div class="mk-footer-col">
                <div class="mk-footer-col-title">Legal</div>
                <div class="mk-footer-col-links">
                    <a href="/terms" class="mk-footer-link">Terms of Service</a>
                    <a href="/privacy" class="mk-footer-link">Privacy Policy</a>
                </div>
            </div>
        </div>
        <div class="mk-footer-bar">
            <span>&copy; {$smarty.now|date_format:"%Y"} {$app_name}. All rights reserved.</span>
        </div>
    </div>
</section>

{/block}

{block name="page_scripts"}
<script src="/public/js/landing{$min}.js?v={$asset_v}"></script>
{/block}