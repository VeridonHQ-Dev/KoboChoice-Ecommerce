-- ============================================================================
-- Kobo Choice eCommerce - Enhanced Database Schema
-- Import this file into MySQL/MariaDB before using the site.
-- ============================================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;

CREATE DATABASE IF NOT EXISTS `kobo_stores`
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `kobo_stores`;

-- ---------------------------------------------------------------------------
-- Admin users
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `role` ENUM('super_admin','admin') NOT NULL DEFAULT 'admin',
  `password_hash` VARCHAR(255) NOT NULL,
  `avatar` VARCHAR(255) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login` TIMESTAMP NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Super Admin: admin@kobostores.shop / Password2$
INSERT INTO `admins` (`name`, `email`, `role`, `password_hash`) VALUES
('Super Admin', 'admin@kobostores.shop', 'super_admin', '$2y$10$7UqUKl00/X4cNxEPumRAm.FbsNcWXfRRCSSoA9L6qOMDQH3rJCue2');

-- ---------------------------------------------------------------------------
-- Admin sessions
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `admin_sessions`;
CREATE TABLE `admin_sessions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` INT UNSIGNED NOT NULL,
  `token` VARCHAR(64) NOT NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(255) NULL,
  `expires_at` TIMESTAMP NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_session_token` (`token`),
  KEY `admin_session_admin` (`admin_id`),
  CONSTRAINT `fk_admin_session` FOREIGN KEY (`admin_id`)
      REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Admin activity log
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `admin_logs`;
CREATE TABLE `admin_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` INT UNSIGNED NOT NULL,
  `action` VARCHAR(60) NOT NULL,
  `description` TEXT NULL,
  `ip_address` VARCHAR(45) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `log_admin` (`admin_id`),
  CONSTRAINT `fk_log_admin` FOREIGN KEY (`admin_id`)
      REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Categories (supports parent/child for navbar + mega menu)
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id` INT UNSIGNED NULL DEFAULT NULL,
  `name` VARCHAR(160) NOT NULL,
  `slug` VARCHAR(190) NOT NULL,
  `description` TEXT NULL,
  `image` VARCHAR(255) NULL,
  `show_in_menu` TINYINT(1) NOT NULL DEFAULT 1,
  `show_in_sidebar` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_slug` (`slug`),
  KEY `category_parent` (`parent_id`),
  CONSTRAINT `fk_category_parent` FOREIGN KEY (`parent_id`)
      REFERENCES `categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categories` (`name`, `slug`, `description`, `sort_order`) VALUES
('Smartphone', 'smartphone', 'Smartphones and mobile devices', 1),
('Laptops', 'laptops', 'Laptops and computers', 2),
('TV & Audio', 'tv-audio', 'Television and audio equipment', 3),
('Cameras', 'cameras', 'Cameras and photography', 4),
('Headphone', 'headphone', 'Headphones and audio', 5),
('Smartwatch', 'smartwatch', 'Smart watches', 6),
('Accessories', 'accessories', 'General accessories', 7);

-- ---------------------------------------------------------------------------
-- Products
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NULL DEFAULT NULL,
  -- Homepage placement slot: '', fashion, electronics, powders, roots_bark,
  -- supplements. At most one per product; see product_sections() in
  -- includes/functions.php for the canonical list.
  `section` VARCHAR(32) NOT NULL DEFAULT '',
  `name` VARCHAR(190) NOT NULL,
  `slug` VARCHAR(190) NOT NULL,
  `reference` VARCHAR(120) NULL,
  `manufacturer` VARCHAR(160) NULL,
  `short_description` VARCHAR(255) NULL,
  `description` TEXT NULL,
  `price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `old_price` DECIMAL(12,2) NULL DEFAULT NULL,
  `cost_price` DECIMAL(12,2) NULL DEFAULT NULL,
  `stock` INT NOT NULL DEFAULT 0,
  `sku` VARCHAR(120) NULL,
  `image` VARCHAR(255) NULL,
  `is_new` TINYINT(1) NOT NULL DEFAULT 1,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `is_bestseller` TINYINT(1) NOT NULL DEFAULT 0,
  `is_deal` TINYINT(1) NOT NULL DEFAULT 0,
  `is_powder` TINYINT(1) NOT NULL DEFAULT 0,
  `is_roots_bark` TINYINT(1) NOT NULL DEFAULT 0,
  `is_supplement` TINYINT(1) NOT NULL DEFAULT 0,
  `status` ENUM('active','draft') NOT NULL DEFAULT 'active',
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_slug` (`slug`),
  KEY `product_category` (`category_id`),
  KEY `product_section` (`section`),
  KEY `product_status` (`status`),
  CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`)
      REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Product gallery images
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `product_images`;
CREATE TABLE `product_images` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `image` VARCHAR(255) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `pi_product` (`product_id`),
  CONSTRAINT `fk_pi_product` FOREIGN KEY (`product_id`)
      REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Customers
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(160) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `password_hash` VARCHAR(255) NULL,
  `phone` VARCHAR(40) NULL,
  `date_of_birth` DATE NULL,
  `gender` ENUM('male','female','other') NULL,
  `google_id` VARCHAR(64) NULL,
  `profile_image` VARCHAR(255) NULL,
  `address` TEXT NULL,
  `shipping_name` VARCHAR(160) NULL,
  `shipping_phone` VARCHAR(40) NULL,
  `shipping_address` TEXT NULL,
  `shipping_city` VARCHAR(120) NULL,
  `shipping_state` VARCHAR(120) NULL,
  `shipping_country` VARCHAR(120) NULL DEFAULT 'Nigeria',
  `billing_name` VARCHAR(160) NULL,
  `billing_phone` VARCHAR(40) NULL,
  `billing_address` TEXT NULL,
  `billing_city` VARCHAR(120) NULL,
  `billing_state` VARCHAR(120) NULL,
  `billing_country` VARCHAR(120) NULL DEFAULT 'Nigeria',
  `status` ENUM('active','disabled') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_email` (`email`),
  UNIQUE KEY `customer_google` (`google_id`),
  KEY `customer_phone` (`phone`),
  KEY `customer_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Customer sessions
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `customer_sessions`;
CREATE TABLE `customer_sessions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id` INT UNSIGNED NOT NULL,
  `token` VARCHAR(64) NOT NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(255) NULL,
  `expires_at` TIMESTAMP NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_token` (`token`),
  KEY `session_customer` (`customer_id`),
  CONSTRAINT `fk_session_customer` FOREIGN KEY (`customer_id`)
      REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Password resets (6-digit email codes, 10-minute expiry)
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id` INT UNSIGNED NOT NULL,
  `code_hash` VARCHAR(64) NOT NULL,
  `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `expires_at` TIMESTAMP NOT NULL DEFAULT '2000-01-01 00:00:00',
  `used_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `reset_customer` (`customer_id`),
  CONSTRAINT `fk_reset_customer` FOREIGN KEY (`customer_id`)
      REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Orders
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id` INT UNSIGNED NULL DEFAULT NULL,
  `guest_name` VARCHAR(160) NULL,
  `email` VARCHAR(190) NULL,
  `phone` VARCHAR(40) NULL,
  `shipping_address` TEXT NULL,
  `city` VARCHAR(120) NULL,
  `state` VARCHAR(120) NULL,
  `country` VARCHAR(120) NULL,
  `note` TEXT NULL,
  `payment_method` VARCHAR(50) NULL,
  `payment_receipt` VARCHAR(255) NULL,
  `payment_reference` VARCHAR(100) NULL,
  `paid_at` DATETIME NULL,
  `tax` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('pending','paid','processing','shipped','delivered','completed','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  -- When an admin first opened this order. NULL = new, drives the sidebar's
  -- Orders badge. Left out of the INSERT in api/OrderAPI.php on purpose so a
  -- freshly placed order always arrives unseen.
  `seen_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_customer` (`customer_id`),
  KEY `order_seen_at` (`seen_at`),
  UNIQUE KEY `order_payment_reference` (`payment_reference`),
  CONSTRAINT `fk_order_customer` FOREIGN KEY (`customer_id`)
      REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NULL DEFAULT NULL,
  `name` VARCHAR(190) NOT NULL,
  `price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `quantity` INT NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `oi_order` (`order_id`),
  CONSTRAINT `fk_oi_order` FOREIGN KEY (`order_id`)
      REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Wishlists
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `wishlists`;
CREATE TABLE `wishlists` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wishlist_unique` (`customer_id`, `product_id`),
  KEY `wishlist_product` (`product_id`),
  CONSTRAINT `fk_wishlist_customer` FOREIGN KEY (`customer_id`)
      REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wishlist_product` FOREIGN KEY (`product_id`)
      REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Product reviews / ratings
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `reviews`;
CREATE TABLE `reviews` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `customer_id` INT UNSIGNED NOT NULL,
  `rating` TINYINT UNSIGNED NOT NULL,
  `title` VARCHAR(190) NULL,
  `comment` TEXT NULL,
  `is_approved` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `review_product` (`product_id`),
  KEY `review_customer` (`customer_id`),
  CONSTRAINT `fk_review_product` FOREIGN KEY (`product_id`)
      REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_review_customer` FOREIGN KEY (`customer_id`)
      REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Coupons
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `coupons`;
CREATE TABLE `coupons` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(60) NOT NULL,
  `type` ENUM('percentage','fixed') NOT NULL DEFAULT 'percentage',
  `value` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `min_order` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `max_uses` INT UNSIGNED NULL DEFAULT NULL,
  `used_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `expires_at` TIMESTAMP NULL DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `coupon_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Newsletter subscribers
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `subscribers`;
CREATE TABLE `subscribers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(190) NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscriber_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Contact messages
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `messages`;
CREATE TABLE `messages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(160) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `subject` VARCHAR(190) NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('open','ongoing','resolved') NOT NULL DEFAULT 'open',
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- ---------------------------------------------------------------------------
-- Homepage banners / picture ads, managed from Admin → CMS.
-- `section` groups them: slider | ads_grid | promo_pair | featured
-- `image` is a path relative to the site root, so it can point either at a
-- bundled theme asset (images/banner/1.png) or an admin upload
-- (public/uploads/cms/...).
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `cms_banners`;
CREATE TABLE `cms_banners` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `section` VARCHAR(30) NOT NULL,
  `image` VARCHAR(255) NOT NULL,
  `caption` VARCHAR(160) NULL,
  `subheading` VARCHAR(190) NULL,
  `heading` VARCHAR(190) NULL,
  `price_text` VARCHAR(120) NULL,
  `button_text` VARCHAR(60) NULL,
  `link_url` VARCHAR(255) NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `section_order` (`section`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `cms_banners` (`section`,`image`,`caption`,`subheading`,`heading`,`price_text`,`button_text`,`link_url`,`sort_order`) VALUES
('slider','images/slider/4.jpg',NULL,'Sale Offer <span> -20% Off</span> This Week','Chamcham Galaxy S9 | S9+','Starting at <span>$589.00</span>','Order Now','/shop',1),
('slider','images/slider/5.jpg',NULL,'Sale Offer <span>Black Friday</span> This Week','Work Desk Surface Studio 2018','Starting at <span>$1599.00</span>','Order Now','/shop',2),
('slider','images/slider/6.jpg',NULL,'Sale Offer <span>-10% Off</span> This Week','Phantom 4 Pro+ Obsidian','Starting at <span>$809.00</span>','Order Now','/shop',3),
('ads_grid','images/banner/1.png','Call for deals',NULL,NULL,NULL,NULL,'/shop',1),
('ads_grid','images/banner/2.jpg','$5,000 Store',NULL,NULL,NULL,NULL,'/shop',2),
('ads_grid','images/banner/3.gif','Awoof Deals',NULL,NULL,NULL,NULL,'/shop',3),
('ads_grid','images/banner/4.png','Up to 70% Off',NULL,NULL,NULL,NULL,'/shop',4),
('ads_grid','images/banner/5.png','Television deals',NULL,NULL,NULL,NULL,'/shop',5),
('ads_grid','images/banner/6.png','Banger deals',NULL,NULL,NULL,NULL,'/shop',6),
('ads_grid','images/banner/7.gif','Up to 50% Off',NULL,NULL,NULL,NULL,'/shop',7),
('ads_grid','images/banner/9.png','Shoes on a budget',NULL,NULL,NULL,NULL,'/shop',8),
('promo_pair','images/banner/2_1.jpg','Promo banner left',NULL,NULL,NULL,NULL,'/shop',1),
('promo_pair','images/banner/2_2.jpg','Promo banner right',NULL,NULL,NULL,NULL,'/shop',2),
('static_home','images/bg-banner/1.jpg','Apple Accessories 2026','Sale Offer <span>-20% Off</span>This Week','Featured Product','Starting at <span> $1209.00</span>','Order Now','/shop',1),
('featured','images/banner/featured-banner.jpg','Featured products banner',NULL,NULL,NULL,NULL,'/shop',1);

-- ---------------------------------------------------------------------------
-- Site settings (key/value)
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `key` VARCHAR(120) NOT NULL,
  `value` TEXT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`key`, `value`) VALUES
('site_name', 'Kobo Choice'),
('site_tagline', 'Original African Stuffs'),
('currency', 'USD'),
('currency_symbol', '$'),
('phone', '(+234) 123 321 345'),
('email', 'info@kobostores.shop'),
('address', '6688 Princess Road, London, Greater London BAS 23JK, UK'),
('footer_about', 'We are a team of designers and developers that create high quality HTML Template & Woocommerce, Shopify Theme.'),
('coupon_code', 'KoboStoresSaleoff'),
('admin_path', 'admin'),
-- Regional offices shown on the contact page (placeholders — edit in Admin → Settings)
('office_us_address', '1234 Market Street, Suite 500, San Francisco, CA 94103, United States'),
('office_us_phone', '+1 (415) 555-0142'),
('office_ng_address', '12 Adeola Odeku Street, Victoria Island, Lagos, Nigeria'),
('office_ng_phone', '(+234) 803 516 7873');

SET foreign_key_checks = 1;
