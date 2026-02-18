<?php

declare(strict_types=1);

namespace TinyShop\Services;

final class Validation
{
    private const PASSWORD_MIN_LENGTH = 6;
    private const PASSWORD_MAX_LENGTH = 72; // bcrypt truncates at 72 bytes
    private const SUBDOMAIN_MIN_LENGTH = 3;
    private const SUBDOMAIN_MAX_LENGTH = 63;

    /** Max character lengths for text inputs (aligned with DB column sizes). */
    private const INPUT_LIMITS = [
        'name'             => 100,
        'email'            => 255,
        'store_name'       => 150,
        'description'      => 10_000,
        'meta_title'       => 200,
        'meta_description' => 500,
        'customer_name'    => 200,
        'category_name'    => 100,
        'shop_tagline'     => 300,
    ];

    /** @var array<string, true> O(1) hash set — keys are reserved names */
    private const RESERVED_SUBDOMAINS = [
        // Platform routes
        'admin' => true, 'api' => true, 'dashboard' => true, 'login' => true,
        'register' => true, 'auth' => true, 'oauth' => true, 'logout' => true,
        'callback' => true, 'webhook' => true, 'webhooks' => true,

        // Infrastructure / DNS
        'www' => true, 'mail' => true, 'ftp' => true, 'smtp' => true,
        'imap' => true, 'pop' => true, 'ns1' => true, 'ns2' => true,
        'ns3' => true, 'ns4' => true, 'mx' => true, 'localhost' => true,
        'vpn' => true, 'proxy' => true, 'gateway' => true,

        // Static / CDN
        'static' => true, 'cdn' => true, 'assets' => true, 'media' => true,
        'img' => true, 'images' => true, 'css' => true, 'js' => true,
        'fonts' => true, 'uploads' => true, 'files' => true,

        // Common product paths
        'app' => true, 'shop' => true, 'store' => true, 'marketplace' => true,
        'checkout' => true, 'cart' => true, 'pay' => true, 'billing' => true,
        'payments' => true, 'invoice' => true, 'pricing' => true,

        // User-facing pages
        'help' => true, 'support' => true, 'docs' => true, 'status' => true,
        'blog' => true, 'news' => true, 'forum' => true, 'community' => true,
        'account' => true, 'settings' => true, 'profile' => true,
        'search' => true, 'explore' => true, 'discover' => true,
        'about' => true, 'contact' => true, 'careers' => true, 'jobs' => true,

        // Legal / trust
        'terms' => true, 'privacy' => true, 'legal' => true, 'security' => true,
        'abuse' => true, 'dmca' => true, 'report' => true,

        // Email standards (RFC 2142)
        'postmaster' => true, 'webmaster' => true, 'hostmaster' => true,
        'info' => true, 'noreply' => true, 'no-reply' => true,

        // Environments
        'dev' => true, 'staging' => true, 'test' => true, 'beta' => true,
        'alpha' => true, 'demo' => true, 'sandbox' => true, 'preview' => true,

        // API versioning
        'v1' => true, 'v2' => true, 'v3' => true,
        'graphql' => true, 'rest' => true, 'ws' => true, 'wss' => true,

        // Brand protection
        'tinyshop' => true, 'tiny-shop' => true,
    ];

    /**
     * Check a value against the max character limit for a field.
     * Returns null if valid, or an error message string.
     */
    public function maxLength(string $value, string $field): ?string
    {
        $limit = self::INPUT_LIMITS[$field] ?? null;
        if ($limit !== null && mb_strlen($value) > $limit) {
            $label = ucfirst(str_replace('_', ' ', $field));
            return "{$label} must not exceed {$limit} characters";
        }
        return null;
    }

    /** Returns null if valid, or an error message string. */
    public function password(string $password): ?string
    {
        if (strlen($password) < self::PASSWORD_MIN_LENGTH) {
            return 'Password must be at least ' . self::PASSWORD_MIN_LENGTH . ' characters';
        }
        if (strlen($password) > self::PASSWORD_MAX_LENGTH) {
            return 'Password must not exceed ' . self::PASSWORD_MAX_LENGTH . ' characters';
        }

        return null;
    }

    /**
     * Sanitize and validate a phone number.
     * @return array{0: string, 1: ?string} [cleanNumber, errorOrNull]
     */
    public function phone(string $phone): array
    {
        $clean = preg_replace('/[^0-9+\s\-]/', '', $phone);
        $digits = preg_replace('/\D/', '', $clean);

        if (strlen($digits) < 7) {
            return [$clean, 'Phone number is too short'];
        }

        return [$clean, null];
    }

    /**
     * Sanitize HTML for product descriptions.
     * Allows safe tags but strips dangerous attributes (javascript: URIs, event handlers).
     */
    public function sanitizeHtml(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $html = strip_tags($html, '<p><br><b><strong><i><em><ul><ol><li><h2><h3><a>');

        // Remove all event handler attributes (onclick, onerror, onload, etc.)
        $html = preg_replace('/\s+on\w+\s*=\s*["\'][^"\']*["\']/i', '', $html);
        $html = preg_replace('/\s+on\w+\s*=\s*[^\s>]*/i', '', $html);

        // Remove style attributes (prevents expression() and CSS injection)
        $html = preg_replace('/\s+style\s*=\s*["\'][^"\']*["\']/i', '', $html);
        $html = preg_replace('/\s+style\s*=\s*[^\s>]*/i', '', $html);

        // Only allow href with http:// or https:// — strips javascript:, data:, vbscript:
        // Also catches HTML-entity-encoded variants (&#106;avascript:)
        $html = preg_replace_callback(
            '/(href\s*=\s*["\'])([^"\']*?)(["\'])/i',
            function ($m) {
                $url = html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $url = trim($url);
                if ($url === '' || preg_match('#^https?://#i', $url) || str_starts_with($url, '/') || str_starts_with($url, '#')) {
                    return $m[0]; // safe
                }
                return $m[1] . '#' . $m[3]; // replace dangerous URLs
            },
            $html
        );

        return $html;
    }

    /**
     * Validate and sanitize variations JSON structure.
     * Expected: array of {name: string, options: string[]}
     * Returns JSON string or null if invalid.
     */
    public function sanitizeVariations(mixed $variations): ?string
    {
        if (is_string($variations)) {
            $variations = json_decode($variations, true);
        }

        if (!is_array($variations)) {
            return null;
        }

        $clean = [];
        foreach ($variations as $group) {
            if (!is_array($group)) {
                continue;
            }
            $name = trim((string) ($group['name'] ?? ''));
            if ($name === '' || mb_strlen($name) > 100) {
                continue;
            }
            $options = $group['options'] ?? [];
            if (!is_array($options)) {
                continue;
            }
            $cleanOptions = [];
            foreach ($options as $opt) {
                // Support object format {value, price} and plain string format
                if (is_array($opt)) {
                    $val = trim((string) ($opt['value'] ?? ''));
                    if ($val === '' || mb_strlen($val) > 100) {
                        continue;
                    }
                    $entry = ['value' => $val];
                    if (isset($opt['price']) && is_numeric($opt['price'])) {
                        $entry['price'] = (float) $opt['price'];
                    }
                    $cleanOptions[] = $entry;
                } else {
                    $val = trim((string) $opt);
                    if ($val !== '' && mb_strlen($val) <= 100) {
                        $cleanOptions[] = $val;
                    }
                }
            }
            if (!empty($cleanOptions)) {
                $clean[] = ['name' => $name, 'options' => $cleanOptions];
            }
        }

        return !empty($clean) ? json_encode($clean, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) : null;
    }

    /** Generate a URL-safe slug from a name. */
    public function slug(string $name): string
    {
        $slug = mb_strtolower($name);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);

        return trim($slug, '-');
    }

    /**
     * Validate and sanitize a subdomain.
     * @return array{0: string, 1: ?string} [sanitizedSubdomain, errorOrNull]
     */
    public function subdomain(string $subdomain): array
    {
        $subdomain = preg_replace('/[^a-z0-9\-]/', '', strtolower($subdomain));
        $subdomain = preg_replace('/--+/', '-', $subdomain); // collapse consecutive hyphens
        $subdomain = trim($subdomain, '-');                  // strip leading/trailing hyphens

        if (strlen($subdomain) < self::SUBDOMAIN_MIN_LENGTH) {
            return [$subdomain, 'Shop URL must be at least ' . self::SUBDOMAIN_MIN_LENGTH . ' characters'];
        }

        if (strlen($subdomain) > self::SUBDOMAIN_MAX_LENGTH) {
            return [$subdomain, 'Shop URL must not exceed ' . self::SUBDOMAIN_MAX_LENGTH . ' characters'];
        }

        if (preg_match('/^\d+$/', $subdomain)) {
            return [$subdomain, 'Shop URL cannot be only numbers'];
        }

        if (isset(self::RESERVED_SUBDOMAINS[$subdomain])) {
            return [$subdomain, 'This shop URL is reserved'];
        }

        return [$subdomain, null];
    }
}
