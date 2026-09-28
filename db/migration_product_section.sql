-- ---------------------------------------------------------------------------
-- Adds products.section — the single placement slot chosen on the admin
-- product form (Fashion / Electronics / Powders / Roots & Bark / Supplements).
--
-- Replaces the is_powder / is_roots_bark / is_supplement booleans, which could
-- contradict each other; a product now belongs to at most one section. The old
-- columns are left in place so this migration is reversible, but nothing reads
-- them any more.
--
-- Safe to re-run: the ALTER fails harmlessly if the column already exists.
-- ---------------------------------------------------------------------------

ALTER TABLE `products`
    ADD COLUMN `section` VARCHAR(32) NOT NULL DEFAULT '' AFTER `category_id`,
    ADD KEY `product_section` (`section`);

-- Carry over anything already flagged under the old booleans.
UPDATE `products` SET `section` = 'powders'     WHERE `is_powder` = 1     AND `section` = '';
UPDATE `products` SET `section` = 'roots_bark'  WHERE `is_roots_bark` = 1 AND `section` = '';
UPDATE `products` SET `section` = 'supplements' WHERE `is_supplement` = 1 AND `section` = '';
