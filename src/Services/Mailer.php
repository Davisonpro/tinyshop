<?php

declare(strict_types=1);

namespace TinyShop\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use Smarty\Smarty;
use TinyShop\Models\Setting;

/**
 * Email service.
 *
 * @since 1.0.0
 */
final class Mailer
{
    private const DEFAULT_SMTP_PORT = 587;

    private readonly string $appName;
    private readonly string $appUrl;
    private readonly Smarty $smarty;
    private readonly Setting $settings;

    public function __construct(Config $config, Setting $settings)
    {
        $this->appName  = $config->name();
        $this->appUrl   = $config->url();
        $this->settings = $settings;

        $this->smarty = new Smarty();
        $this->smarty->setTemplateDir($config->templatesDir());
        $this->smarty->setCompileDir($config->compileDir());
        $this->smarty->setCacheDir($config->cacheDir());
        $this->smarty->setCaching(Smarty::CACHING_OFF);
    }

    /**
     * Send the welcome email to a new seller.
     *
     * @since 1.0.0
     *
     * @param  string $email Recipient email.
     * @param  string $name  Recipient name.
     * @return bool
     */
    public function sendWelcome(string $email, string $name): bool
    {
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $this->smarty->assign('app_name', $this->appName);
        $this->smarty->assign('app_url', $this->appUrl);
        $this->smarty->assign('user_name', $name);

        $html = $this->smarty->fetch('emails/welcome.tpl');

        return $this->send($email, 'Welcome to ' . $this->appName . '!', $html) === null;
    }

    /**
     * Send a password reset email.
     *
     * @since 1.0.0
     *
     * @param  string $email    Recipient email.
     * @param  string $name     Recipient name.
     * @param  string $resetUrl Password reset link.
     * @return bool
     */
    public function sendPasswordReset(string $email, string $name, string $resetUrl): bool
    {
        $this->smarty->assign('app_name', $this->appName);
        $this->smarty->assign('app_url', $this->appUrl);
        $this->smarty->assign('user_name', $name);
        $this->smarty->assign('reset_url', $resetUrl);

        $html = $this->smarty->fetch('emails/password_reset.tpl');

        return $this->send($email, $this->appName . ' - Reset Your Password', $html) === null;
    }

    /**
     * Send an order confirmation to the customer.
     *
     * @since 1.0.0
     *
     * @param  string $customerEmail Customer email.
     * @param  string $customerName  Customer name.
     * @param  array  $order         Order data.
     * @param  array  $items         Line items.
     * @param  array  $shop          Shop data.
     * @return bool
     */
    public function sendOrderConfirmation(
        string $customerEmail,
        string $customerName,
        array $order,
        array $items,
        array $shop
    ): bool {
        if (empty($customerEmail) || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $storeName = $shop['store_name'] ?? 'Shop';
        $currency = $shop['currency'] ?? 'USD';

        $this->smarty->assign('store_name', $storeName);
        $this->smarty->assign('customer_name', $customerName);
        $this->smarty->assign('order', $order);
        $this->smarty->assign('items', $items);
        $this->smarty->assign('currency', $currency);

        $html = $this->smarty->fetch('emails/order_confirmation.tpl');
        $subject = 'Order Confirmed — #' . ($order['order_number'] ?? '');

        return $this->send($customerEmail, $subject, $html) === null;
    }

    /**
     * Notify a seller about a new order.
     *
     * @since 1.0.0
     *
     * @param  string $sellerEmail Seller email.
     * @param  string $sellerName  Seller name.
     * @param  array  $order       Order data.
     * @param  array  $items       Line items.
     * @param  array  $shop        Shop data.
     * @return bool
     */
    public function sendNewOrderNotification(
        string $sellerEmail,
        string $sellerName,
        array $order,
        array $items,
        array $shop
    ): bool {
        if (empty($sellerEmail) || !filter_var($sellerEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $currency = $shop['currency'] ?? 'USD';

        $this->smarty->assign('app_name', $this->appName);
        $this->smarty->assign('seller_name', $sellerName);
        $this->smarty->assign('order', $order);
        $this->smarty->assign('items', $items);
        $this->smarty->assign('currency', $currency);
        $this->smarty->assign('dashboard_url', $this->appUrl . '/dashboard/orders');

        $html = $this->smarty->fetch('emails/new_order.tpl');
        $subject = 'New Order #' . ($order['order_number'] ?? '') . ' — ' . $currency . ' ' . number_format((float) ($order['amount'] ?? 0), 2);

        return $this->send($sellerEmail, $subject, $html) === null;
    }

    /**
     * Send an HTML email.
     *
     * @since 1.0.0
     *
     * @param  string $to       Recipient.
     * @param  string $subject  Subject line.
     * @param  string $htmlBody HTML body.
     * @return string|null Null on success, error message on failure.
     */
    public function send(string $to, string $subject, string $htmlBody): ?string
    {
        try {
            $mail = $this->buildMailer();
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->CharSet  = 'UTF-8';
            $mail->Subject  = $subject;
            $mail->Body     = $htmlBody;
            $mail->AltBody  = self::htmlToPlainText($htmlBody);

            $mail->send();

            return null;
        } catch (PHPMailerException $e) {
            return $e->getMessage();
        }
    }

    /** Build a PHPMailer instance with SMTP settings from the database. */
    private function buildMailer(): PHPMailer
    {
        $allSettings = $this->settings->all();

        $smtpHost  = $allSettings['smtp_host'] ?? '';
        $fromEmail = trim($allSettings['mail_from_email'] ?? '');
        $fromName  = $allSettings['mail_from_name'] ?? $this->appName;

        if ($fromEmail === '') {
            $baseDomain = $allSettings['base_domain'] ?? '';
            if ($baseDomain !== '' && str_contains($baseDomain, '.')) {
                $fromEmail = 'noreply@' . $baseDomain;
            } else {
                throw new PHPMailerException('No "From" email configured. Set a Mail From Email in Settings.');
            }
        }

        $mail = new PHPMailer(true);

        if ($smtpHost !== '') {
            $mail->isSMTP();
            $mail->Host       = $smtpHost;
            $mail->Port       = (int) ($allSettings['smtp_port'] ?? self::DEFAULT_SMTP_PORT);
            $mail->SMTPSecure = ($allSettings['smtp_encryption'] ?? 'tls') ?: '';

            $smtpUser = $allSettings['smtp_username'] ?? '';
            if ($smtpUser !== '') {
                $mail->SMTPAuth = true;
                $mail->Username = $smtpUser;
                $mail->Password = $allSettings['smtp_password'] ?? '';
            }
        }

        $mail->setFrom($fromEmail, $fromName);

        return $mail;
    }

    /** Convert HTML to plain text for the email alt body. */
    private static function htmlToPlainText(string $html): string
    {
        $text = str_replace(
            ['<br>', '<br/>', '<br />', '</p>', '</div>', '</tr>'],
            "\n",
            $html
        );
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8');

        return trim(preg_replace('/\n{3,}/', "\n\n", $text));
    }
}
