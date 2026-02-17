<footer class="mk-footer">
    <div class="mk-footer-card">
        <div class="mk-footer-cols">
            <div class="mk-footer-col">
                <div class="mk-footer-col-title">{$app_name}</div>
                <div class="mk-footer-col-links">
                    <a href="/" class="mk-footer-link">Home</a>
                    <a href="/pricing" class="mk-footer-link">Pricing</a>
                    <a href="/login" class="mk-footer-link">Log in</a>
                    <a href="/register" class="mk-footer-link">Sign up</a>
                </div>
            </div>
            <div class="mk-footer-col">
                <div class="mk-footer-col-title">Support</div>
                <div class="mk-footer-col-links">
                    <a href="/help" class="mk-footer-link">Help center</a>
                    <a href="mailto:{if $support_email}{$support_email|escape}{else}hello@{$base_domain|default:'tinyshop.com'}{/if}" class="mk-footer-link">Contact us</a>
                </div>
            </div>
            <div class="mk-footer-col">
                <div class="mk-footer-col-title">Legal</div>
                <div class="mk-footer-col-links">
                    <a href="/terms" class="mk-footer-link">Terms of Service</a>
                    <a href="/privacy" class="mk-footer-link">Privacy Policy</a>
                </div>
            </div>
        </div>
        <div class="mk-footer-bar">
            <span>&copy; {$smarty.now|date_format:"%Y"} {$app_name}. All rights reserved.</span>
        </div>
    </div>
</footer>