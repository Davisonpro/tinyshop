{extends file="layouts/base.tpl"}

{block name="body_class"}page-shop page-confirmation{/block}

{block name="extra_css"}
<link rel="stylesheet" href="/public/css/confirmation.css?v={$asset_v}">
{/block}
{block name="body"}
<div class="confirm-page">
    <div class="confirm-check">
        <i class="fa-solid fa-check" style="font-size:32px;color:#fff"></i>
    </div>

    <h1 class="confirm-title">Order Confirmed!</h1>
    <p class="confirm-order-number">Order #{$order.order_number|escape}</p>

    {if $order.status == 'paid'}
        <div class="confirm-status confirm-status-paid">Paid</div>
    {else}
        <div class="confirm-status confirm-status-pending">Payment Pending</div>
    {/if}

    {* Order items *}
    <div class="confirm-section">
        <div class="confirm-section-title">Items</div>
        <ul class="confirm-items">
            {foreach $order_items as $item}
            <li class="confirm-item">
                <div class="confirm-item-img">
                    {if $item.product_image}
                        <img src="{$item.product_image|escape}" alt="{$item.product_name|escape}">
                    {else}
                        <img src="/public/img/placeholder.svg" alt="{$item.product_name|escape}">
                    {/if}
                </div>
                <div class="confirm-item-info">
                    <div class="confirm-item-name">{$item.product_name|escape}</div>
                    <div class="confirm-item-meta">
                        {if $item.variation}{$item.variation|escape} &middot; {/if}Qty: {$item.quantity}
                    </div>
                </div>
                <div class="confirm-item-price">{$currency_symbol}{$item.total|format_price}</div>
            </li>
            {/foreach}
        </ul>
        <div class="confirm-total-row">
            <span>Total</span>
            <span>{$currency_symbol}{$order.amount|format_price}</span>
        </div>
    </div>

    {* Customer details *}
    <div class="confirm-section">
        <div class="confirm-section-title">Details</div>
        <div class="confirm-detail-row">
            <span class="confirm-detail-label">Name</span>
            <span class="confirm-detail-value">{$order.customer_name|escape}</span>
        </div>
        <div class="confirm-detail-row">
            <span class="confirm-detail-label">Email</span>
            <span class="confirm-detail-value">{$order.customer_email|escape}</span>
        </div>
        {if $order.customer_phone}
        <div class="confirm-detail-row">
            <span class="confirm-detail-label">Phone</span>
            <span class="confirm-detail-value">{$order.customer_phone|escape}</span>
        </div>
        {/if}
        <div class="confirm-detail-row">
            <span class="confirm-detail-label">Payment</span>
            <span class="confirm-detail-value">{$order.payment_gateway|escape|capitalize}</span>
        </div>
    </div>

    <p class="confirm-note">Your order is being processed. You'll receive updates at <strong>{$order.customer_email|escape}</strong>.</p>

    <a href="/" class="btn btn-accent confirm-action">Continue Shopping</a>
    <a href="/orders/track" class="confirm-track-link">Track your order</a>
</div>
{/block}
