{if !empty($palette_css)}
<style>
.{$palette_scope|default:'page-shop'} {ldelim}
    --palette-primary: {$palette_css.primary};
    --palette-bar: {$palette_css.bar};
    --palette-bar-text: {$palette_css.bar_text};
    --palette-accent: {$palette_css.accent};
    --palette-accent-hover: {$palette_css.accent};
{rdelim}
</style>
{/if}
