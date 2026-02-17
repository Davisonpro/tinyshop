{extends file="layouts/base.tpl"}

{block name="body_class"}page-content{/block}

{block name="body"}

{include file="partials/marketing_nav.tpl"}

<article class="page-content-wrap">
    <header class="page-content-header">
        <h1>{$page_data.title|escape}</h1>
        <p class="page-content-updated">Last updated {$page_data.updated_at|date_format:"%B %e, %Y"}</p>
    </header>
    <div class="page-content-body">
        {$page_data.content}
    </div>
</article>

{include file="partials/marketing_footer.tpl"}

{/block}
