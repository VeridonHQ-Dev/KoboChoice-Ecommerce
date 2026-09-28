-- ---------------------------------------------------------------------------
-- Adds orders.seen_at — the moment an admin first opened the order's detail
-- page. NULL means "new, nobody has looked at it yet".
--
-- Replaces status = 'pending' as the source for the sidebar's Orders badge.
-- Status was the wrong signal: an order awaiting a bank transfer stays
-- 'pending' indefinitely and legitimately so, which left the badge stuck on a
-- number that never moved no matter how many times the admin reviewed it.
-- "Has an admin looked at this?" is a different question from "has it been
-- paid?", and only the first one belongs on a notification badge.
--
-- Existing orders are backfilled as already seen. They predate the feature and
-- have all been through the admin already, so the badge starts at zero and
-- counts only genuinely new orders from here on.
--
-- Safe to re-run: the ALTER fails harmlessly if the column already exists.
-- ---------------------------------------------------------------------------

ALTER TABLE `orders`
    ADD COLUMN `seen_at` DATETIME NULL DEFAULT NULL AFTER `created_at`,
    ADD KEY `order_seen_at` (`seen_at`);

-- Treat everything that already exists as attended to.
UPDATE `orders` SET `seen_at` = `created_at` WHERE `seen_at` IS NULL;
