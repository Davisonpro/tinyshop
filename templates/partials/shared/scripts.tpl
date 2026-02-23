<script src="/public/js/jquery.min.js?v={$asset_v}"></script>
<script src="/public/js/app{$min}.js?v={$asset_v}"></script>
{if !empty($has_payments) && !empty($shop)}
<script>
window._shopId = {$shop.id|escape:'javascript'};
window._shopCurrency = '{$currency|escape:'javascript'}';
window._shopCurrencySymbol = '{$currency_symbol|escape:'javascript'}';
</script>
<script src="/public/js/cart{$min}.js?v={$asset_v}"></script>
<script>TinyShop.Cart.init({$shop.id|escape:'javascript'});</script>
{/if}
{literal}<script>if('serviceWorker' in navigator){navigator.serviceWorker.register('/sw.js');}</script>{/literal}
