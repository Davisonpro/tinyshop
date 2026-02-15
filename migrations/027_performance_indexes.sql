-- Performance indexes for common queries

-- Products: composite index for shop page queries (WHERE user_id = ? AND is_active = 1)
ALTER TABLE products ADD INDEX idx_user_active (user_id, is_active);

-- Orders: composite index for order listing (WHERE user_id = ? ORDER BY created_at DESC)
ALTER TABLE orders ADD INDEX idx_user_created (user_id, created_at);
