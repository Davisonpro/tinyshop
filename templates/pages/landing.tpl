{extends file="layouts/base.tpl"}

{block name="body_class"}page-landing{/block}

{block name="extra_css"}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="/public/css/marketing.css?v={$asset_v}">
<link rel="stylesheet" href="/public/css/landing.css?v={$asset_v}">
{/block}

{block name="body"}

{include file="partials/marketing_nav.tpl"}

<div class="land-hero-wrap">
    <div class="land-hero-desktop">
        <section class="land-hero">
            <div class="land-badge">Free to start &mdash; no card needed</div>
            <h1>Your own online shop, ready in minutes</h1>
            <p class="land-hero-sub">Your customers are already asking for prices. Give them a shop link where they can see everything, pick what they want, and pay &mdash; no more endless DMs.</p>
            <a href="/register" class="land-cta">
                Create my free shop
                <i class="fa-solid fa-arrow-right"></i>
            </a>
            <small class="land-hero-small">Takes 2 minutes. Share it on WhatsApp right away.</small>
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
            <span class="land-url"><b>nairobithrifts</b>.{$base_domain|default:'tinyshop.com'}</span>
            <span class="land-url"><b>mia-boutique</b>.{$base_domain|default:'tinyshop.com'}</span>
            <span class="land-url"><b>freshjuiceke</b>.{$base_domain|default:'tinyshop.com'}</span>
        </div>
    </div>
</div>

{* ── How It Works ── *}
<section class="land-steps">
    <div class="land-steps-inner">
        <h2 class="land-reveal">It&rsquo;s easier than you think</h2>
        <p class="land-steps-sub land-reveal d1">If you can post on social media, you can do this.</p>
        <div class="land-steps-grid">
            <div class="land-step land-reveal d2">
                <div class="land-step-icon">
                    <i class="fa-solid fa-store"></i>
                    <span class="land-step-num">1</span>
                </div>
                <h3>Name your shop</h3>
                <p>Choose a name and your shop goes live with its own link &mdash; like an Instagram handle, but for your business.</p>
            </div>
            <div class="land-step land-reveal d3">
                <div class="land-step-icon">
                    <i class="fa-solid fa-camera"></i>
                    <span class="land-step-num">2</span>
                </div>
                <h3>Add what you sell</h3>
                <p>Snap a photo, type a price, done. You can add products from your phone anytime, anywhere.</p>
            </div>
            <div class="land-step land-reveal d4">
                <div class="land-step-icon">
                    <i class="fa-solid fa-share-nodes"></i>
                    <span class="land-step-num">3</span>
                </div>
                <h3>Share your link</h3>
                <p>Post it on WhatsApp, drop it in your Instagram bio, send it to group chats. Customers tap and shop.</p>
            </div>
        </div>
    </div>
</section>

{* ── Features ── *}
<section class="land-features">
    <div class="land-features-inner">
        <h2 class="land-reveal">Everything you need to sell online</h2>
        <p class="land-features-sub land-reveal d1">All the tools you need to start selling, with a free plan to get going.</p>
        <div class="land-features-grid">
            <div class="land-feature-card land-reveal d2">
                <div class="land-feature-icon coral">
                    <i class="fa-solid fa-box-open"></i>
                </div>
                <h3>List your products easily</h3>
                <p>Add photos, set prices, and organize by category — all from your phone.</p>
            </div>
            <div class="land-feature-card land-reveal d2">
                <div class="land-feature-icon blue">
                    <i class="fa-solid fa-credit-card"></i>
                </div>
                <h3>Get paid online</h3>
                <p>Customers can pay right in your shop, or you can arrange payment your own way.</p>
            </div>
            <div class="land-feature-card land-reveal d3">
                <div class="land-feature-icon green">
                    <i class="fa-solid fa-mobile-screen"></i>
                </div>
                <h3>Looks great on any phone</h3>
                <p>Your shop works perfectly on whatever device your customers are using.</p>
            </div>
            <div class="land-feature-card land-reveal d3">
                <div class="land-feature-icon purple">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
                <h3>See how your shop is doing</h3>
                <p>Know who visited, what sold, and which products people love most.</p>
            </div>
            <div class="land-feature-card land-reveal d4">
                <div class="land-feature-icon amber">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
                <h3>Know what&rsquo;s left in stock</h3>
                <p>Keep track of your inventory so you never sell something you don&rsquo;t have.</p>
            </div>
            <div class="land-feature-card land-reveal d4">
                <div class="land-feature-icon rose">
                    <i class="fa-solid fa-ticket"></i>
                </div>
                <h3>Offer discounts and coupons</h3>
                <p>Create promo codes to reward your loyal customers or run a sale.</p>
            </div>
        </div>
    </div>
</section>

{* ── Who Is This For ── *}
<section class="land-usecases">
    <div class="land-usecases-inner">
        <h2 class="land-reveal">Made for people just like you</h2>
        <p class="land-usecases-sub land-reveal d1">No matter what you sell, this works for you.</p>
        <div class="land-usecases-grid">
            <div class="land-usecase land-reveal d2">
                <div class="land-usecase-icon coral">
                    <i class="fa-solid fa-shirt"></i>
                </div>
                <div>
                    <h3>Thrift &amp; fashion sellers</h3>
                    <p>No more posting prices on your WhatsApp status every morning.</p>
                </div>
            </div>
            <div class="land-usecase land-reveal d2">
                <div class="land-usecase-icon amber">
                    <i class="fa-solid fa-utensils"></i>
                </div>
                <div>
                    <h3>Food &amp; bakery vendors</h3>
                    <p>Let customers browse your menu and order without calling you.</p>
                </div>
            </div>
            <div class="land-usecase land-reveal d3">
                <div class="land-usecase-icon purple">
                    <i class="fa-solid fa-palette"></i>
                </div>
                <div>
                    <h3>Artists &amp; crafters</h3>
                    <p>Show your work in a beautiful shop and take orders from anywhere.</p>
                </div>
            </div>
            <div class="land-usecase land-reveal d3">
                <div class="land-usecase-icon blue">
                    <i class="fa-solid fa-basket-shopping"></i>
                </div>
                <div>
                    <h3>Small shops &amp; retailers</h3>
                    <p>Give your business an online presence your customers can share.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{* ── Voices ── *}
<section class="land-voices">
    <div class="land-voices-inner">
        <h2 class="land-reveal">They started just like you</h2>
        <div class="land-voices-grid">
            <div class="land-voice land-voice--lavender land-reveal d1">
                <p class="land-voice-quote">I used to spend hours answering &lsquo;how much?&rsquo; in my DMs. Now I just send my shop link.</p>
                <span class="land-voice-who">Amara &middot; Thrift seller, Lagos</span>
            </div>
            <div class="land-voice land-voice--green land-reveal d2">
                <p class="land-voice-quote">Set up my bakery menu in 10 minutes from my phone. Customers just pick and order.</p>
                <span class="land-voice-who">Kwame &middot; Baker, Accra</span>
            </div>
            <div class="land-voice land-voice--poppy land-reveal d3">
                <p class="land-voice-quote">Other platforms were complicated and expensive. This just works, and it&rsquo;s free.</p>
                <span class="land-voice-who">Fatima &middot; Fashion seller, Nairobi</span>
            </div>
        </div>
    </div>
</section>

{* ── Bottom CTA ── *}
<section class="land-bottom">
    <div class="land-bottom-inner">
        <h2 class="land-bottom-text land-reveal">Ready to stop juggling DMs?</h2>
        <p class="land-bottom-sub land-reveal d1">Create your own shop and start selling properly. Free plan included.</p>
        <div class="land-bottom-form land-reveal d2">
            <span class="land-bottom-url"><i class="fa-solid fa-link"></i> yourshop.{$base_domain|default:'tinyshop.com'}</span>
            <a href="/register" class="land-bottom-btn">Claim your shop</a>
        </div>
    </div>
</section>

{include file="partials/marketing_footer.tpl"}

{/block}

{block name="page_scripts"}
<script src="/public/js/landing.js?v={$asset_v}"></script>
{/block}