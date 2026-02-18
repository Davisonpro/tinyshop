{hook name="theme.empty_state.before"}
<div class="empty-state">
    <i class="fa-solid fa-bag-shopping empty-state-icon"></i>
    <p><strong>{$empty_title|default:'Coming soon'}</strong></p>
    <p class="empty-state-subtitle">{$empty_subtitle|default:'This shop is getting ready — check back soon!'}</p>
</div>
{hook name="theme.empty_state.after"}
