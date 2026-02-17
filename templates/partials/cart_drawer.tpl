{* Cart Drawer — bottom-sheet modal for cart contents *}
{if !empty($has_payments)}
{* Floating mobile cart button — top-right, hidden on desktop *}
<button type="button" class="mobile-cart-fab cart-trigger" aria-label="Shopping cart">
    <i class="fa-solid fa-cart-shopping"></i>
    <span class="cart-badge" style="display:none">0</span>
</button>
<div class="cart-drawer-backdrop" id="cartDrawerBackdrop">
    <div class="cart-drawer">
        <div class="cart-drawer-handle"></div>
        <div class="cart-drawer-header">
            <h2 class="cart-drawer-title">Your Cart <span id="cartDrawerCount"></span></h2>
            <button type="button" class="cart-drawer-close" aria-label="Close cart">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="cart-drawer-body" id="cartDrawerBody">
            <div class="cart-empty">
                <i class="fa-solid fa-cart-shopping" style="font-size:40px;color:var(--color-text-muted);opacity:0.4;margin-bottom:12px"></i>
                <p style="font-weight:600;margin-bottom:4px">Your cart is empty</p>
                <p style="font-size:0.8125rem;color:var(--color-text-muted)">Add items to get started</p>
            </div>
        </div>
        <div class="cart-drawer-footer" id="cartDrawerFooter" style="display:none">
            <div class="cart-drawer-total">
                <span>Subtotal</span>
                <span id="cartDrawerTotal"></span>
            </div>
            <button type="button" class="btn btn-accent cart-checkout-btn" id="cartCheckoutBtn">Checkout &mdash; <span id="cartCheckoutTotal"></span></button>
        </div>
    </div>
</div>
{/if}
