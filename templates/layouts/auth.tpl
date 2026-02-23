{extends file="layouts/base.tpl"}

{block name="body_class"}page-auth{/block}

{block name="extra_css"}{/block}

{block name="body"}
{include file="partials/public/nav.tpl"}
<div class="auth-wrapper">
    <div class="auth-card">
        {block name="content"}{/block}
    </div>
</div>
{/block}