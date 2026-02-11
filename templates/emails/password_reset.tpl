<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
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
            .email-bg {
                background-color: #1a1a2e !important;
            }
            .email-card {
                background-color: #16213e !important;
            }
            .email-heading {
                color: #e0e0e0 !important;
            }
            .email-text {
                color: #b0b0c0 !important;
            }
            .email-text-muted {
                color: #808090 !important;
            }
            .email-footer-text {
                color: #606070 !important;
            }
            .email-divider {
                border-color: #2a2a4a !important;
            }
        }
        @media only screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
                padding: 12px !important;
            }
            .email-card {
                padding: 28px 20px !important;
            }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">

    <!-- Preheader (hidden text for inbox preview) -->
    <div style="display: none; max-height: 0; overflow: hidden; mso-hide: all;">
        Reset your {$app_name} password. This link expires in 1 hour.
    </div>

    <!-- Outer wrapper -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="email-bg" style="background-color: #f4f4f7;">
        <tr>
            <td align="center" style="padding: 40px 16px;">

                <!-- Main container -->
                <table role="presentation" class="email-container" width="560" cellpadding="0" cellspacing="0" border="0" style="max-width: 560px; width: 100%;">

                    <!-- Logo / Brand -->
                    <tr>
                        <td align="center" style="padding-bottom: 24px;">
                            <span style="font-size: 26px; font-weight: 700; color: #6c5ce7; letter-spacing: -0.5px; text-decoration: none;">{$app_name}</span>
                        </td>
                    </tr>

                    <!-- Card -->
                    <tr>
                        <td class="email-card" style="background-color: #ffffff; border-radius: 12px; padding: 40px 36px; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">

                            <!-- Greeting -->
                            <h1 class="email-heading" style="margin: 0 0 8px; font-size: 22px; font-weight: 700; color: #1a1a2e; line-height: 1.3;">
                                Hi {$user_name},
                            </h1>

                            <!-- Message -->
                            <p class="email-text" style="margin: 0 0 24px; font-size: 15px; color: #555770; line-height: 1.6;">
                                We received a request to reset the password for your {$app_name} account. Click the button below to choose a new password.
                            </p>

                            <!-- CTA Button -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center" style="padding: 4px 0 28px;">
                                        <!--[if mso]>
                                        <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{$reset_url}" style="height:48px;v-text-anchor:middle;width:260px;" arcsize="17%" stroke="f" fillcolor="#6c5ce7">
                                            <w:anchorlock/>
                                            <center style="color:#ffffff;font-family:sans-serif;font-size:16px;font-weight:bold;">Reset Password</center>
                                        </v:roundrect>
                                        <![endif]-->
                                        <!--[if !mso]><!-->
                                        <a href="{$reset_url}" target="_blank" style="display: inline-block; background-color: #6c5ce7; color: #ffffff; font-size: 16px; font-weight: 600; text-decoration: none; padding: 14px 40px; border-radius: 8px; line-height: 1; mso-hide: all;">
                                            Reset Password
                                        </a>
                                        <!--<![endif]-->
                                    </td>
                                </tr>
                            </table>

                            <!-- Expiry notice -->
                            <p class="email-text-muted" style="margin: 0 0 20px; font-size: 13px; color: #888898; line-height: 1.5;">
                                This link will expire in <strong>1 hour</strong>. After that, you will need to request a new password reset.
                            </p>

                            <!-- Divider -->
                            <hr class="email-divider" style="border: none; border-top: 1px solid #e8e8f0; margin: 20px 0;">

                            <!-- Fallback URL -->
                            <p class="email-text-muted" style="margin: 0; font-size: 12px; color: #888898; line-height: 1.5; word-break: break-all;">
                                If the button above doesn't work, copy and paste this link into your browser:<br>
                                <a href="{$reset_url}" style="color: #6c5ce7; text-decoration: underline;">{$reset_url}</a>
                            </p>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="padding: 28px 16px 0;">
                            <p class="email-footer-text" style="margin: 0 0 6px; font-size: 12px; color: #999; line-height: 1.5;">
                                If you didn't request a password reset, you can safely ignore this email. Your password will remain unchanged.
                            </p>
                            <p class="email-footer-text" style="margin: 0; font-size: 12px; color: #bbb; line-height: 1.5;">
                                &copy; {$smarty.now|date_format:"%Y"} {$app_name}. All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>
                <!-- /Main container -->

            </td>
        </tr>
    </table>
    <!-- /Outer wrapper -->

</body>
</html>
