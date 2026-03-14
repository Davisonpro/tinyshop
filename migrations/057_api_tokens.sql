-- Add API token column for mobile app bearer token authentication
ALTER TABLE users ADD COLUMN api_token VARCHAR(64) DEFAULT NULL AFTER password_hash;
ALTER TABLE users ADD UNIQUE INDEX idx_users_api_token (api_token);
