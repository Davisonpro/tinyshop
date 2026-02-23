{extends file="layouts/base.tpl"}

{block name="body_class"}page-shop page-collections{/block}

{block name="body"}

{include file="partials/shop/palette_vars.tpl" palette_scope="page-collections"}

{include file="partials/shop/announcement_bar.tpl"}
{include file="partials/shop/desktop_header.tpl"}
{include file="partials/shop/mobile_header.tpl"}

<main class="shop-content">

    {hook name="theme.collections.before"}

    <div class="collections-header">
        <h1 class="collections-title">Collections</h1>
    </div>

    {hook name="theme.collections.header.after"}

    {if !empty($categories)}
        {include file="partials/shop/collection_list.tpl"}
    {else}
    <div class="collections-empty">
        {include file="partials/shop/empty_state.tpl" empty_title="No collections yet" empty_subtitle="Check back soon."}
    </div>
    {/if}

    {hook name="theme.collections.after"}

</main>

{include file="partials/shop/desktop_footer.tpl"}
{include file="partials/shop/cart_drawer.tpl"}
{include file="partials/shop/contact_sheet.tpl"}
{include file="partials/shop/bottom_nav.tpl"}

{/block}
