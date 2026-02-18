<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
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
</head>
<body style="margin: 0; padding: 0; background-color: #f5f5f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%;">

    <!-- Preheader -->
    <div style="display: none; max-height: 0; overflow: hidden; mso-hide: all;">
        Order #{$order.order_number|escape} confirmed — thank you for your purchase!
    </div>

    <!-- Outer wrapper -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f5f5f5;">
        <tr>
            <td align="center" style="padding: 40px 16px;">

                <!-- Main container -->
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px; width: 100%;">

                    <!-- Store name header -->
                    <tr>
                        <td align="center" style="padding-bottom: 24px;">
                            <span style="font-size: 22px; font-weight: 700; color: #111111; letter-spacing: -0.5px; text-decoration: none;">{$store_name|escape}</span>
                        </td>
                    </tr>

                    <!-- Card -->
                    <tr>
                        <td style="background-color: #ffffff; border-radius: 12px; padding: 40px 36px;">

                            <!-- Checkmark icon -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center" style="padding-bottom: 20px;">
                                        <!--[if mso]>
                                        <v:oval style="width:52px;height:52px;" fillcolor="#d2e823" stroked="f">
                                            <v:textbox inset="0,0,0,0" style="mso-fit-shape-to-text:false;">
                                                <center style="font-size:24px;color:#111111;line-height:52px;">&#10003;</center>
                                            </v:textbox>
                                        </v:oval>
                                        <![endif]-->
                                        <!--[if !mso]><!-->
                                        <div style="display: inline-block; width: 52px; height: 52px; border-radius: 50%; background-color: #d2e823; line-height: 52px; text-align: center; font-size: 24px; color: #111111;">&#10003;</div>
                                        <!--<![endif]-->
                                    </td>
                                </tr>
                            </table>

                            <!-- Heading -->
                            <h1 style="margin: 0 0 12px; font-size: 24px; font-weight: 700; color: #111111; line-height: 1.3; text-align: center;">
                                Order Confirmed
                            </h1>

                            <p style="margin: 0 0 28px; font-size: 15px; color: #555555; line-height: 1.6; text-align: center;">
                                Hi {$customer_name|escape}, thank you for your order!
                            </p>

                            <!-- Order number + date box -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 28px;">
                                <tr>
                                    <td style="background-color: #f5f5f5; border-radius: 8px; padding: 16px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="vertical-align: top;">
                                                    <span style="font-size: 11px; color: #888888; text-transform: uppercase; letter-spacing: 0.08em;">Order Number</span><br>
                                                    <span style="font-size: 16px; font-weight: 700; color: #111111; letter-spacing: 0.02em;">{$order.order_number|escape}</span>
                                                </td>
                                                <td style="text-align: right; vertical-align: top;">
                                                    <span style="font-size: 11px; color: #888888; text-transform: uppercase; letter-spacing: 0.08em;">Date</span><br>
                                                    <span style="font-size: 14px; font-weight: 600; color: #111111;">{$order.created_at|date_format:"%b %e, %Y"}</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Items heading -->
                            <p style="margin: 0 0 10px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: #888888;">Items</p>

                            <!-- Items list -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                {foreach $items as $item}
                                <tr>
                                    <td style="padding: 10px 0; border-bottom: 1px solid #eeeeee; font-size: 14px; color: #333333;">
                                        {$item.product_name|escape}{if !empty($item.variation)} <span style="color: #888888;">({$item.variation|escape})</span>{/if}
                                        <span style="color: #888888;">&times; {$item.quantity}</span>
                                    </td>
                                    <td style="padding: 10px 0; border-bottom: 1px solid #eeeeee; font-size: 14px; color: #333333; text-align: right; white-space: nowrap;">
                                        {$currency} {$item.total|number_format:2}
                                    </td>
                                </tr>
                                {/foreach}

                                <!-- Total row -->
                                <tr>
                                    <td style="padding: 16px 0 0; font-size: 16px; font-weight: 700; color: #111111;">
                                        Total
                                    </td>
                                    <td style="padding: 16px 0 0; font-size: 16px; font-weight: 700; color: #111111; text-align: right; white-space: nowrap;">
                                        {$currency} {$order.amount|number_format:2}
                                    </td>
                                </tr>
                            </table>

                            {if !empty($order.customer_name) || !empty($order.customer_email)}
                            <!-- Customer details -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top: 28px;">
                                <tr>
                                    <td style="border-top: 1px solid #eeeeee; padding-top: 20px;">
                                        <p style="margin: 0 0 10px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: #888888;">Customer Details</p>
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                            {if !empty($order.customer_name)}
                                            <tr>
                                                <td style="padding: 3px 0; font-size: 13px; color: #888888; width: 60px; vertical-align: top;">Name</td>
                                                <td style="padding: 3px 0; font-size: 13px; color: #333333;">{$order.customer_name|escape}</td>
                                            </tr>
                                            {/if}
                                            {if !empty($order.customer_email)}
                                            <tr>
                                                <td style="padding: 3px 0; font-size: 13px; color: #888888; width: 60px; vertical-align: top;">Email</td>
                                                <td style="padding: 3px 0; font-size: 13px; color: #333333;">{$order.customer_email|escape}</td>
                                            </tr>
                                            {/if}
                                            {if !empty($order.customer_phone)}
                                            <tr>
                                                <td style="padding: 3px 0; font-size: 13px; color: #888888; width: 60px; vertical-align: top;">Phone</td>
                                                <td style="padding: 3px 0; font-size: 13px; color: #333333;">{$order.customer_phone|escape}</td>
                                            </tr>
                                            {/if}
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            {/if}

                            {if !empty($order.notes)}
                            <!-- Customer note -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top: 20px;">
                                <tr>
                                    <td style="border-top: 1px solid #eeeeee; padding-top: 20px;">
                                        <p style="margin: 0 0 8px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: #888888;">Your Note</p>
                                        <p style="margin: 0; font-size: 13px; color: #555555; line-height: 1.5; background-color: #fafafa; padding: 10px 12px; border-radius: 6px;">{$order.notes|escape}</p>
                                    </td>
                                </tr>
                            </table>
                            {/if}

                            <!-- Bottom message -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top: 28px;">
                                <tr>
                                    <td style="border-top: 1px solid #eeeeee; padding-top: 20px;">
                                        <p style="margin: 0; font-size: 13px; color: #888888; line-height: 1.5; text-align: center;">
                                            Your order is being processed. If you have any questions, contact the shop directly.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="padding: 28px 16px 0;">
                            <p style="margin: 0 0 4px; font-size: 12px; color: #bbbbbb; line-height: 1.5;">
                                &copy; {$smarty.now|date_format:"%Y"} {$store_name|escape}
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
