{if !empty($palette_css)}
<style>
.{$palette_scope|default:'page-shop'} {ldelim}
    --palette-anchor: {$palette_css.anchor};
    --palette-depth: {$palette_css.depth};
    --palette-conversion: {$palette_css.conversion};
    --palette-substrate: {$palette_css.substrate};
    --palette-canvas: {$palette_css.canvas};
    --palette-conversion-hover: color-mix(in srgb, {$palette_css.conversion} 80%, white);
    --palette-substrate-border: color-mix(in srgb, {$palette_css.substrate} 60%, {$palette_css.canvas});
    --product-image-fit: {$product_image_fit|default:'cover'};
{rdelim}
</style>
{/if}
