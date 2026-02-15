<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>Order Confirmed</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style>
        :root {
            color-scheme: light dark;
            supported-color-schemes: light dark;
        }
        body {
            margin: 0;
            padding: 0;
            width: 100% !important;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        table {
            border-collapse: collapse;
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }
        img {
            border: 0;
            line-height: 100%;
            outline: none;
            text-decoration: none;
        }
        @media (prefers-color-scheme: dark) {
            .email-bg { background-color: #111 !important; }
            .email-card { background-color: #1a1a1a !important; }
            .email-heading { color: #f0f0f0 !important; }
            .email-text { color: #aaa !important; }
            .email-text-muted { color: #777 !important; }
            .email-footer-text { color: #555 !important; }
            .email-divider { border-color: #333 !important; }
            .email-order-box { background-color: #222 !important; }
            .email-item-border { border-color: #333 !important; }
        }
        @media only screen and (max-width: 600px) {
            .email-container { width: 100% !important; padding: 12px !important; }
            .email-card { padding: 28px 20px !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">

    <div style="display: none; max-height: 0; overflow: hidden; mso-hide: all;">
        Order #{$order.order_number} confirmed &mdash; thank you for your purchase!
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="email-bg" style="background-color: #f4f4f7;">
        <tr>
            <td align="center" style="padding: 40px 16px;">

                <table role="presentation" class="email-container" width="560" cellpadding="0" cellspacing="0" border="0" style="max-width: 560px; width: 100%;">

                    <!-- Store name -->
                    <tr>
                        <td align="center" style="padding-bottom: 24px;">
                            <span class="email-heading" style="font-size: 22px; font-weight: 700; color: #111; letter-spacing: -0.5px;">{$store_name|escape}</span>
                        </td>
                    </tr>

                    <!-- Card -->
                    <tr>
                        <td class="email-card" style="background-color: #ffffff; border-radius: 12px; padding: 40px 36px; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">

                            <!-- Checkmark -->
                            <div style="text-align: center; margin-bottom: 20px;">
                                <!--[if mso]>
                                <v:oval style="width:48px;height:48px;" fillcolor="#22c55e" stroked="f">
                                    <v:textbox inset="0,0,0,0" style="mso-fit-shape-to-text:false;">
                                        <center style="font-size:22px;color:#ffffff;line-height:48px;">&#10003;</center>
                                    </v:textbox>
                                </v:oval>
                                <![endif]-->
                                <!--[if !mso]><!-->
                                <div style="display: inline-block; width: 48px; height: 48px; border-radius: 50%; background-color: #22c55e; line-height: 48px; text-align: center; font-size: 22px; color: #fff;">&#10003;</div>
                                <!--<![endif]-->
                            </div>

                            <h1 class="email-heading" style="margin: 0 0 12px; font-size: 22px; font-weight: 700; color: #111; line-height: 1.3; text-align: center;">
                                Order Confirmed
                            </h1>

                            <p class="email-text" style="margin: 0 0 28px; font-size: 15px; color: #555; line-height: 1.6; text-align: center;">
                                Hi {$customer_name|escape}, thank you for your order!
                            </p>

                            <!-- Order number box -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 24px;">
                                <tr>
                                    <td class="email-order-box" style="background: #f7f7f8; border-radius: 8px; padding: 14px 16px; text-align: center;">
                                        <span class="email-text-muted" style="font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 0.08em;">Order Number</span><br>
                                        <span class="email-heading" style="font-size: 16px; font-weight: 700; color: #111; letter-spacing: 0.02em;">{$order.order_number|escape}</span>
                                    </td>
                                </tr>
                            </table>

                            <!-- Items -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                {foreach $items as $item}
                                <tr>
                                    <td class="email-item-border email-text" style="padding: 10px 0; border-bottom: 1px solid #f0f0f0; font-size: 14px; color: #333;">
                                        {$item.product_name|escape}{if !empty($item.variation)} <span class="email-text-muted" style="color: #888;">({$item.variation|escape})</span>{/if}
                                        <span class="email-text-muted" style="color: #888;">&times; {$item.quantity}</span>
                                    </td>
                                    <td class="email-item-border email-text" style="padding: 10px 0; border-bottom: 1px solid #f0f0f0; font-size: 14px; color: #333; text-align: right; white-space: nowrap;">
                                        {$currency} {$item.total|number_format:2}
                                    </td>
                                </tr>
                                {/foreach}
                                <tr>
                                    <td class="email-heading" style="padding: 14px 0 0; font-size: 15px; font-weight: 700; color: #111;">
                                        Total
                                    </td>
                                    <td class="email-heading" style="padding: 14px 0 0; font-size: 15px; font-weight: 700; color: #111; text-align: right; white-space: nowrap;">
                                        {$currency} {$order.amount|number_format:2}
                                    </td>
                                </tr>
                            </table>

                            {if !empty($order.notes)}
                            <div style="height: 20px; font-size: 0; line-height: 0;">&nbsp;</div>
                            <hr class="email-divider" style="border: none; border-top: 1px solid #e8e8f0; margin: 0;">
                            <p class="email-text-muted" style="margin: 16px 0 0; font-size: 13px; color: #888; line-height: 1.5;">
                                <strong class="email-text" style="color: #555;">Your note:</strong> {$order.notes|escape}
                            </p>
                            {/if}

                            <div style="height: 24px; font-size: 0; line-height: 0;">&nbsp;</div>
                            <hr class="email-divider" style="border: none; border-top: 1px solid #e8e8f0; margin: 0 0 16px;">

                            <p class="email-text-muted" style="margin: 0; font-size: 13px; color: #888; line-height: 1.5; text-align: center;">
                                Your order is being processed. If you have any questions, contact the shop directly.
                            </p>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="padding: 28px 16px 0;">
                            <p class="email-footer-text" style="margin: 0; font-size: 12px; color: #bbb; line-height: 1.5;">
                                &copy; {$smarty.now|date_format:"%Y"} {$store_name|escape}
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
