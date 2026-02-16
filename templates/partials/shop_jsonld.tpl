{* JSON-LD Structured Data for shop pages *}
<script type="application/ld+json">
{ldelim}
    "@context": "https://schema.org",
    "@graph": [
        {ldelim}
            "@type": "Organization",
            "name": "{$shop.store_name|escape:'javascript'}",
            "url": "{$base_url}/"
            {if $shop.shop_logo},"logo": "{$shop.shop_logo|escape:'javascript'}"{/if}
            {if $shop.shop_tagline},"description": "{$shop.shop_tagline|escape:'javascript'}"{/if}
            {if $shop.contact_email},"email": "{$shop.contact_email|escape:'javascript'}"{/if}
            {if $shop.contact_phone},"telephone": "{$shop.contact_phone|escape:'javascript'}"{/if}
        {rdelim},
        {ldelim}
            "@type": "WebSite",
            "url": "{$base_url}/",
            "name": "{$shop.store_name|escape:'javascript'}"
        {rdelim},
        {ldelim}
            "@type": "CollectionPage",
            "name": "{$shop.store_name|escape:'javascript'}",
            "url": "{$base_url}/",
            "numberOfItems": {$total_products},
            "mainEntity": {ldelim}
                "@type": "ItemList",
                "numberOfItems": {$total_products},
                "itemListElement": [
                    {foreach $products as $p}
                    {ldelim}
                        "@type": "ListItem",
                        "position": {$p@iteration},
                        "url": "{$base_url}/{$p.slug|default:$p.id}",
                        "name": "{$p.name|escape:'javascript'}"
                        {if $p.image_url},"image": "{$p.image_url|escape:'javascript'}"{/if}
                    {rdelim}{if !$p@last},{/if}
                    {/foreach}
                ]
            {rdelim}
        {rdelim},
        {ldelim}
            "@type": "BreadcrumbList",
            "itemListElement": [
                {ldelim}
                    "@type": "ListItem",
                    "position": 1,
                    "name": "{$shop.store_name|escape:'javascript'}",
                    "item": "{$base_url}/"
                {rdelim}
            ]
        {rdelim}
    ]
{rdelim}
</script>
