-- Add stock quantity tracking to products
-- NULL = unlimited/not tracked, 0 = sold out, > 0 = available
ALTER TABLE products ADD COLUMN stock_quantity INT UNSIGNED NULL DEFAULT NULL AFTER is_sold;
