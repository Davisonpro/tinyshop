-- Product image fit setting (cover vs contain)
ALTER TABLE users
    ADD COLUMN product_image_fit VARCHAR(10) NOT NULL DEFAULT 'cover' AFTER logo_alignment;
