{extends file="layouts/base.tpl"}

{block name="body_class"}page-404{/block}

{block name="body"}
<div class="error-page">
    <div class="container" style="text-align:center; padding-top:80px; padding-bottom:80px;">
        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="var(--color-text-muted)" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" style="opacity:0.35; margin-bottom:24px">
            <circle cx="12" cy="12" r="10"/>
            <path d="M8 15s1.5-2 4-2 4 2 4 2"/>
            <line x1="9" y1="9" x2="9.01" y2="9"/>
            <line x1="15" y1="9" x2="15.01" y2="9"/>
        </svg>
        <h1 style="font-size:1.5rem; font-weight:700; margin-bottom:8px;">Shop Not Found</h1>
        <p style="color:var(--color-text-muted); font-size:0.875rem; margin-bottom:32px;">This shop doesn't exist or has been removed.</p>
        <a href="/register" class="btn btn-primary">Create Your Own Shop</a>
    </div>
</div>
{/block}
