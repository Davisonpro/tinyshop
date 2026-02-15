<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>New Order Received</title>
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
            .email-summary-box { background-color: #222 !important; }
            .email-item-border { border-color: #333 !important; }
            .email-detail-label { color: #666 !important; }
            .email-detail-value { color: #ccc !important; }
        }
        @media only screen and (max-width: 600px) {
            .email-container { width: 100% !important; padding: 12px !important; }
            .email-card { padding: 28px 20px !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">

    <div style="display: none; max-height: 0; overflow: hidden; mso-hide: all;">
        New order #{$order.order_number} &mdash; {$currency} {$order.amount|number_format:2}
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="email-bg" style="background-color: #f4f4f7;">
        <tr>
            <td align="center" style="padding: 40px 16px;">

                <table role="presentation" class="email-container" width="560" cellpadding="0" cellspacing="0" border="0" style="max-width: 560px; width: 100%;">

                    <!-- Brand -->
                    <tr>
                        <td align="center" style="padding-bottom: 24px;">
                            <span class="email-heading" style="font-size: 22px; font-weight: 700; color: #111; letter-spacing: -0.5px;">{$app_name}</span>
                        </td>
                    </tr>

                    <!-- Card -->
                    <tr>
                        <td class="email-card" style="background-color: #ffffff; border-radius: 12px; padding: 40px 36px; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">

                            <h1 class="email-heading" style="margin: 0 0 8px; font-size: 22px; font-weight: 700; color: #111; line-height: 1.3;">
                                New Order Received
                            </h1>

                            <p class="email-text" style="margin: 0 0 24px; font-size: 15px; color: #555; line-height: 1.6;">
                                Hi {$seller_name|escape}, you've received a new order.
                            </p>

                            <!-- Order summary box -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 24px;">
                                <tr>
                                    <td class="email-summary-box" style="background: #f7f7f8; border-radius: 8px; padding: 16px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="vertical-align: top;">
                                                    <span class="email-text-muted" style="font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 0.08em;">Order</span><br>
                                                    <span class="email-heading" style="font-size: 15px; font-weight: 700; color: #111;">{$order.order_number|escape}</span>
                                                </td>
                                                <td style="text-align: right; vertical-align: top;">
                                                    <span class="email-text-muted" style="font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 0.08em;">Total</span><br>
                                                    <span class="email-heading" style="font-size: 15px; font-weight: 700; color: #111;">{$currency} {$order.amount|number_format:2}</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Customer details -->
                            <p class="email-text-muted" style="margin: 0 0 10px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: #888;">Customer</p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 24px;">
                                <tr>
                                    <td class="email-detail-label" style="padding: 4px 0; font-size: 13px; color: #888; width: 60px; vertical-align: top;">Name</td>
                                    <td class="email-detail-value" style="padding: 4px 0; font-size: 13px; color: #333;">{$order.customer_name|escape}</td>
                                </tr>
                                <tr>
                                    <td class="email-detail-label" style="padding: 4px 0; font-size: 13px; color: #888; width: 60px; vertical-align: top;">Email</td>
                                    <td class="email-detail-value" style="padding: 4px 0; font-size: 13px; color: #333;">
                                        <a href="mailto:{$order.customer_email|escape}" style="color: #6c5ce7; text-decoration: none;">{$order.customer_email|escape}</a>
                                    </td>
                                </tr>
                                {if !empty($order.customer_phone)}
                                <tr>
                                    <td class="email-detail-label" style="padding: 4px 0; font-size: 13px; color: #888; width: 60px; vertical-align: top;">Phone</td>
                                    <td class="email-detail-value" style="padding: 4px 0; font-size: 13px; color: #333;">{$order.customer_phone|escape}</td>
                                </tr>
                                {/if}
                            </table>

                            <!-- Items -->
                            <p class="email-text-muted" style="margin: 0 0 10px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: #888;">Items</p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 24px;">
                                {foreach $items as $item}
                                <tr>
                                    <td class="email-item-border email-text" style="padding: 8px 0; border-bottom: 1px solid #f0f0f0; font-size: 13px; color: #333;">
                                        {$item.product_name|escape}{if !empty($item.variation)} <span class="email-text-muted" style="color: #888;">({$item.variation|escape})</span>{/if}
                                        <span class="email-text-muted" style="color: #888;">&times; {$item.quantity}</span>
                                    </td>
                                    <td class="email-item-border email-text" style="padding: 8px 0; border-bottom: 1px solid #f0f0f0; font-size: 13px; color: #333; text-align: right; white-space: nowrap;">
                                        {$currency} {$item.total|number_format:2}
                                    </td>
                                </tr>
                                {/foreach}
                            </table>

                            {if !empty($order.notes)}
                            <p class="email-text-muted" style="margin: 0 0 10px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: #888;">Customer Note</p>
                            <p class="email-text" style="margin: 0 0 24px; font-size: 13px; color: #333; line-height: 1.5; background: #fffbeb; padding: 10px 12px; border-radius: 6px;">{$order.notes|escape}</p>
                            {/if}

                            <hr class="email-divider" style="border: none; border-top: 1px solid #e8e8f0; margin: 0 0 24px;">

                            <!-- View Orders button -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center">
                                        <!--[if mso]>
                                        <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{$dashboard_url}" style="height:44px;v-text-anchor:middle;width:200px;" arcsize="18%" stroke="f" fillcolor="#111111">
                                            <w:anchorlock/>
                                            <center style="color:#ffffff;font-family:sans-serif;font-size:14px;font-weight:bold;">View Orders</center>
                                        </v:roundrect>
                                        <![endif]-->
                                        <!--[if !mso]><!-->
                                        <a href="{$dashboard_url}" target="_blank" style="display: inline-block; background-color: #111; color: #fff; font-size: 14px; font-weight: 600; text-decoration: none; padding: 12px 32px; border-radius: 8px; line-height: 1;">
                                            View Orders
                                        </a>
                                        <!--<![endif]-->
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="padding: 28px 16px 0;">
                            <p class="email-footer-text" style="margin: 0; font-size: 12px; color: #bbb; line-height: 1.5;">
                                &copy; {$smarty.now|date_format:"%Y"} {$app_name}
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
