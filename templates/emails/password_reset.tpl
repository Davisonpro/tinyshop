<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Reset Your Password</title>
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
        Reset your {$app_name} password. This link expires in 1 hour.
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

                            <!-- Lock icon -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center" style="padding-bottom: 20px;">
                                        <!--[if mso]>
                                        <v:oval style="width:52px;height:52px;" fillcolor="#f5f5f5" stroked="f">
                                            <v:textbox inset="0,0,0,0" style="mso-fit-shape-to-text:false;">
                                                <center style="font-size:24px;color:#111111;line-height:52px;">&#128274;</center>
                                            </v:textbox>
                                        </v:oval>
                                        <![endif]-->
                                        <!--[if !mso]><!-->
                                        <div style="display: inline-block; width: 52px; height: 52px; border-radius: 50%; background-color: #f5f5f5; line-height: 52px; text-align: center; font-size: 24px;">&#128274;</div>
                                        <!--<![endif]-->
                                    </td>
                                </tr>
                            </table>

                            <!-- Heading -->
                            <h1 style="margin: 0 0 8px; font-size: 24px; font-weight: 700; color: #111111; line-height: 1.3; text-align: center;">
                                Reset Your Password
                            </h1>

                            <p style="margin: 0 0 8px; font-size: 15px; color: #555555; line-height: 1.6; text-align: center;">
                                Hi {$user_name|escape},
                            </p>

                            <p style="margin: 0 0 28px; font-size: 15px; color: #555555; line-height: 1.6; text-align: center;">
                                We received a request to reset the password for your {$app_name} account. Click the button below to choose a new password.
                            </p>

                            <!-- Reset Password button -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center" style="padding: 4px 0 28px;">
                                        <!--[if mso]>
                                        <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{$reset_url}" style="height:46px;v-text-anchor:middle;width:240px;" arcsize="17%" stroke="f" fillcolor="#111111">
                                            <w:anchorlock/>
                                            <center style="color:#ffffff;font-family:sans-serif;font-size:15px;font-weight:bold;">Reset Password</center>
                                        </v:roundrect>
                                        <![endif]-->
                                        <!--[if !mso]><!-->
                                        <a href="{$reset_url}" target="_blank" style="display: inline-block; background-color: #111111; color: #ffffff; font-size: 15px; font-weight: 600; text-decoration: none; padding: 14px 40px; border-radius: 8px; line-height: 1;">
                                            Reset Password
                                        </a>
                                        <!--<![endif]-->
                                    </td>
                                </tr>
                            </table>

                            <!-- Expiry notice -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="background-color: #f5f5f5; border-radius: 8px; padding: 14px 16px; text-align: center;">
                                        <p style="margin: 0; font-size: 13px; color: #888888; line-height: 1.5;">
                                            This link will expire in <strong style="color: #555555;">1 hour</strong>. After that, you will need to request a new password reset.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Divider -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top: 24px;">
                                <tr>
                                    <td style="border-top: 1px solid #eeeeee; padding-top: 20px;">
                                    </td>
                                </tr>
                            </table>

                            <!-- Fallback URL -->
                            <p style="margin: 0; font-size: 12px; color: #888888; line-height: 1.5; word-break: break-all; text-align: center;">
                                If the button doesn't work, copy and paste this link into your browser:<br>
                                <a href="{$reset_url}" style="color: #111111; text-decoration: underline;">{$reset_url}</a>
                            </p>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="padding: 28px 16px 0;">
                            <p style="margin: 0 0 6px; font-size: 12px; color: #999999; line-height: 1.5;">
                                If you didn't request a password reset, you can safely ignore this email. Your password will remain unchanged.
                            </p>
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
