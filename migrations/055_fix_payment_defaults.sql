-- Fix: stripe_enabled and paypal_enabled should default to 0 (off)
-- Only enable when credentials are actually configured

ALTER TABLE users
    ALTER COLUMN stripe_enabled SET DEFAULT 0,
    ALTER COLUMN paypal_enabled SET DEFAULT 0;

-- Disable Stripe for users who have it enabled but no credentials
UPDATE users
SET stripe_enabled = 0
WHERE stripe_enabled = 1
  AND (stripe_public_key IS NULL OR stripe_public_key = '')
  AND (stripe_secret_key IS NULL OR stripe_secret_key = '');

-- Disable PayPal for users who have it enabled but no credentials
UPDATE users
SET paypal_enabled = 0
WHERE paypal_enabled = 1
  AND (paypal_client_id IS NULL OR paypal_client_id = '')
  AND (paypal_secret IS NULL OR paypal_secret = '');
