<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
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
</head>
<body style="margin: 0; padding: 0; background-color: #f5f5f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%;">

    <!-- Preheader -->
    <div style="display: none; max-height: 0; overflow: hidden; mso-hide: all;">
        New order #{$order.order_number|escape} — {$currency} {$order.amount|number_format:2}
    </div>

    <!-- Outer wrapper -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f5f5f5;">
        <tr>
            <td align="center" style="padding: 40px 16px;">

                <!-- Main container -->
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px; width: 100%;">

                    <!-- Brand header -->
                    <tr>
                        <td align="center" style="padding-bottom: 24px;">
                            <span style="font-size: 22px; font-weight: 700; color: #111111; letter-spacing: -0.5px; text-decoration: none;">{$app_name}</span>
                        </td>
                    </tr>

                    <!-- Card -->
                    <tr>
                        <td style="background-color: #ffffff; border-radius: 12px; padding: 40px 36px;">

                            <!-- Accent bar -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="padding-bottom: 20px;">
                                        <div style="width: 48px; height: 4px; background-color: #d2e823; border-radius: 2px;"></div>
                                    </td>
                                </tr>
                            </table>

                            <!-- Heading -->
                            <h1 style="margin: 0 0 8px; font-size: 24px; font-weight: 700; color: #111111; line-height: 1.3;">
                                New Order Received
                            </h1>

                            <p style="margin: 0 0 28px; font-size: 15px; color: #555555; line-height: 1.6;">
                                Hi {$seller_name|escape}, you've received a new order.
                            </p>

                            <!-- Order summary box -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 28px;">
                                <tr>
                                    <td style="background-color: #f5f5f5; border-radius: 8px; padding: 16px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="vertical-align: top;">
                                                    <span style="font-size: 11px; color: #888888; text-transform: uppercase; letter-spacing: 0.08em;">Order</span><br>
                                                    <span style="font-size: 16px; font-weight: 700; color: #111111;">{$order.order_number|escape}</span>
                                                </td>
                                                <td style="text-align: right; vertical-align: top;">
                                                    <span style="font-size: 11px; color: #888888; text-transform: uppercase; letter-spacing: 0.08em;">Total</span><br>
                                                    <span style="font-size: 16px; font-weight: 700; color: #111111;">{$currency} {$order.amount|number_format:2}</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Customer details -->
                            <p style="margin: 0 0 10px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: #888888;">Customer</p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 28px;">
                                <tr>
                                    <td style="padding: 4px 0; font-size: 13px; color: #888888; width: 60px; vertical-align: top;">Name</td>
                                    <td style="padding: 4px 0; font-size: 13px; color: #333333;">{$order.customer_name|escape}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 4px 0; font-size: 13px; color: #888888; width: 60px; vertical-align: top;">Email</td>
                                    <td style="padding: 4px 0; font-size: 13px; color: #333333;">
                                        <a href="mailto:{$order.customer_email|escape}" style="color: #111111; text-decoration: underline;">{$order.customer_email|escape}</a>
                                    </td>
                                </tr>
                                {if !empty($order.customer_phone)}
                                <tr>
                                    <td style="padding: 4px 0; font-size: 13px; color: #888888; width: 60px; vertical-align: top;">Phone</td>
                                    <td style="padding: 4px 0; font-size: 13px; color: #333333;">{$order.customer_phone|escape}</td>
                                </tr>
                                {/if}
                            </table>

                            <!-- Items -->
                            <p style="margin: 0 0 10px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: #888888;">Items</p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 28px;">
                                {foreach $items as $item}
                                <tr>
                                    <td style="padding: 8px 0; border-bottom: 1px solid #eeeeee; font-size: 13px; color: #333333;">
                                        {$item.product_name|escape}{if !empty($item.variation)} <span style="color: #888888;">({$item.variation|escape})</span>{/if}
                                        <span style="color: #888888;">&times; {$item.quantity}</span>
                                    </td>
                                    <td style="padding: 8px 0; border-bottom: 1px solid #eeeeee; font-size: 13px; color: #333333; text-align: right; white-space: nowrap;">
                                        {$currency} {$item.total|number_format:2}
                                    </td>
                                </tr>
                                {/foreach}
                            </table>

                            {if !empty($order.notes)}
                            <!-- Customer note -->
                            <p style="margin: 0 0 10px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: #888888;">Customer Note</p>
                            <p style="margin: 0 0 28px; font-size: 13px; color: #333333; line-height: 1.5; background-color: #fffbeb; padding: 10px 12px; border-radius: 6px; border-left: 3px solid #d2e823;">{$order.notes|escape}</p>
                            {/if}

                            <!-- Divider -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="border-top: 1px solid #eeeeee; padding-top: 24px;">
                                    </td>
                                </tr>
                            </table>

                            <!-- View Orders button -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center">
                                        <!--[if mso]>
                                        <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{$dashboard_url}" style="height:46px;v-text-anchor:middle;width:200px;" arcsize="17%" stroke="f" fillcolor="#111111">
                                            <w:anchorlock/>
                                            <center style="color:#ffffff;font-family:sans-serif;font-size:14px;font-weight:bold;">View Orders</center>
                                        </v:roundrect>
                                        <![endif]-->
                                        <!--[if !mso]><!-->
                                        <a href="{$dashboard_url}" target="_blank" style="display: inline-block; background-color: #111111; color: #ffffff; font-size: 14px; font-weight: 600; text-decoration: none; padding: 13px 36px; border-radius: 8px; line-height: 1;">
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
                            <p style="margin: 0 0 4px; font-size: 12px; color: #bbbbbb; line-height: 1.5;">
                                &copy; {$smarty.now|date_format:"%Y"} {$app_name}
                            </p>
                            <p style="margin: 0; font-size: 11px; color: #cccccc; line-height: 1.5;">
                                Powered by {$app_name}
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
