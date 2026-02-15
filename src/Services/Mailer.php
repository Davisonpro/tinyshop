<?php

declare(strict_types=1);

namespace TinyShop\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use Smarty\Smarty;
use TinyShop\Models\Setting;

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

    public function sendPasswordReset(string $email, string $name, string $resetUrl): bool
    {
        $this->smarty->assign('app_name', $this->appName);
        $this->smarty->assign('app_url', $this->appUrl);
        $this->smarty->assign('user_name', $name);
        $this->smarty->assign('reset_url', $resetUrl);

        $html = $this->smarty->fetch('emails/password_reset.tpl');

        return $this->send($email, $this->appName . ' - Reset Your Password', $html) === null;
    }

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
     * Send an email. Returns null on success, error message string on failure.
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

    /**
     * Build a PHPMailer instance configured with current SMTP settings.
     */
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
