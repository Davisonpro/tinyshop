-- Add customizable fields to plans for pricing page
ALTER TABLE plans
    ADD COLUMN features TEXT NULL DEFAULT NULL AFTER coupons_allowed,
    ADD COLUMN cta_text VARCHAR(100) NULL DEFAULT NULL AFTER features,
    ADD COLUMN badge_text VARCHAR(50) NULL DEFAULT NULL AFTER cta_text,
    ADD COLUMN is_featured TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER badge_text;

-- Update existing seed plans with features
UPDATE plans SET
    features = '["Up to 10 products","1 shop design","Order notifications","Basic analytics","Free forever"]',
    cta_text = 'Start Free',
    is_featured = 0
WHERE slug = 'free';

UPDATE plans SET
    features = '["Unlimited products","All shop designs","Custom domain","Discount codes","Priority support","Advanced analytics","M-Pesa payments"]',
    cta_text = 'Upgrade to Pro',
    badge_text = 'Most popular',
    is_featured = 1
WHERE slug = 'pro';
