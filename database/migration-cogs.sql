ALTER TABLE `products` ADD COLUMN `cost_price` DECIMAL(10,2) NULL AFTER `price`;
ALTER TABLE `product_variants` ADD COLUMN `cost_price` DECIMAL(10,2) NULL AFTER `price_override`;
ALTER TABLE `order_items` ADD COLUMN `cost_price` DECIMAL(10,2) NULL AFTER `price`;
