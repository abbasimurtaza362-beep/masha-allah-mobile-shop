USE masha_allah_shop;

-- Repairs legacy seeded records that still point to PNG filenames.
-- The supplied product assets are stored as WebP files.
UPDATE products
SET image_path = REPLACE(image_path, '.png', '.webp')
WHERE image_path LIKE 'assets/images/%.png';
