{extends file="layouts/base.tpl"}

{block name="body_class"}page-auth{/block}

{block name="body"}
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-logo">{$app_name}</div>
        {block name="content"}{/block}
    </div>
</div>
{/block}
