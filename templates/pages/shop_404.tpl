{extends file="layouts/base.tpl"}

{block name="body_class"}page-404{/block}

{block name="body"}
<div class="error-page">
    <div class="container" style="text-align:center; padding-top:80px; padding-bottom:80px;">
        <i class="fa-solid fa-face-frown" style="font-size:80px;color:var(--color-text-muted);opacity:0.35;margin-bottom:24px"></i>
        <h1 style="font-size:1.5rem; font-weight:700; margin-bottom:8px;">Shop Not Found</h1>
        <p style="color:var(--color-text-muted); font-size:0.875rem; margin-bottom:32px;">This shop doesn't exist or has been removed.</p>
        <a href="/register" class="btn btn-primary">Create Your Own Shop</a>
    </div>
</div>
{/block}
