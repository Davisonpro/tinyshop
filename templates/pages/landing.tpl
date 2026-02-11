{extends file="layouts/base.tpl"}

{block name="body_class"}page-landing{/block}

{block name="body"}

<div class="landing">
    <div class="container">

        {* ── Hero ── *}
        <div class="landing-hero">
            <div class="landing-badge">Free forever</div>
            <h1>Your products.<br>One beautiful link.</h1>
            <p class="landing-sub">Create a mobile shop in under 2 minutes. Share it on WhatsApp, Instagram, TikTok — start selling today.</p>
            <a href="/register" class="btn btn-primary btn-lg">Create Your Shop</a>
            <a href="/login" class="btn btn-ghost">Sign In</a>
        </div>

        {* ── Features ── *}
        <div class="landing-features">
            <div class="feature">
                <div class="feature-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </div>
                <h3>Add Products</h3>
                <p>Upload photos, set prices. Done in seconds.</p>
            </div>
            <div class="feature">
                <div class="feature-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                </div>
                <h3>Share Anywhere</h3>
                <p>One link for WhatsApp, social media, anywhere.</p>
            </div>
            <div class="feature">
                <div class="feature-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                </div>
                <h3>Start Selling</h3>
                <p>Customers browse and contact you directly.</p>
            </div>
        </div>

    </div>
</div>

{/block}
