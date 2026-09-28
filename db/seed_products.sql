-- ============================================================================
-- Kobo Choice - Sample Product Data
-- Run this AFTER importing kobo_stores.sql
-- ============================================================================

USE `kobo_stores`;

-- Sample Products (Nigerian/African themed)
INSERT INTO `products` (`category_id`, `name`, `slug`, `reference`, `manufacturer`, `short_description`, `description`, `price`, `old_price`, `cost_price`, `stock`, `sku`, `image`, `is_new`, `is_featured`, `is_bestseller`, `is_deal`, `status`, `sort_order`) VALUES
-- Smartphones (category 1)
(1, 'Samsung Galaxy S24 Ultra', 'samsung-galaxy-s24-ultra', 'SM-S928B', 'Samsung', '6.8" Dynamic AMOLED, 200MP Camera', 'The Samsung Galaxy S24 Ultra features a stunning 6.8-inch Dynamic AMOLED display, powerful Snapdragon 8 Gen 3 processor, 200MP main camera, and built-in S Pen. 5G enabled with 5000mAh battery.', 899000.00, 999000.00, 750000.00, 25, 'SAM-S24U-BLK', NULL, 1, 1, 1, 1, 'active', 1),
(1, 'iPhone 15 Pro Max 256GB', 'iphone-15-pro-max', 'A2848', 'Apple', '6.7" Super Retina XDR, A17 Pro Chip', 'iPhone 15 Pro Max with A17 Pro chip, 48MP camera system, titanium design, USB-C, and all-day battery life. The most powerful iPhone ever.', 1250000.00, 1350000.00, 1050000.00, 15, 'APL-15PM-256', NULL, 1, 1, 0, 0, 'active', 2),
(1, 'Tecno Spark 20 Pro+', 'tecno-spark-20-pro', 'KC8', 'Tecno', '6.78" FHD+, 108MP Camera, 5000mAh', 'Tecno Spark 20 Pro+ with 108MP ultra-clear camera, 6.78 inch FHD+ dot-in display, MediaTek Helio G99 processor, and 5000mAh battery with 33W fast charging.', 285000.00, 320000.00, 220000.00, 40, 'TEC-SP20P-BK', NULL, 1, 0, 1, 1, 'active', 3),
(1, 'Infinix Hot 40 Pro', 'infinix-hot-40-pro', 'X6925', 'Infinix', '6.78" FHD+, 108MP, Helio G99', 'Infinix Hot 40 Pro features a 108MP AI camera, 6.78-inch FHD+ display, MediaTek Helio G99, 8GB RAM, and 5000mAh battery with 45W fast charge.', 195000.00, NULL, 155000.00, 50, 'INX-H40P-BL', NULL, 1, 0, 1, 0, 'active', 4),

-- Laptops (category 2)
(2, 'HP Pavilion 15 Intel Core i7', 'hp-pavilion-15-i7', 'HP-15EG', 'HP', '15.6" FHD, Intel i7-1355U, 16GB RAM, 512GB SSD', 'HP Pavilion 15 with 13th Gen Intel Core i7 processor, 15.6-inch Full HD display, 16GB DDR4 RAM, 512GB NVMe SSD, and Windows 11. Perfect for work and entertainment.', 585000.00, 650000.00, 480000.00, 12, 'HP-PAV15-I7', NULL, 1, 1, 0, 1, 'active', 5),
(2, 'Lenovo IdeaPad 3 Intel Core i5', 'lenovo-ideapad-3-i5', 'LP-15ITL', 'Lenovo', '15.6" FHD, Intel i5-1235U, 8GB RAM, 512GB SSD', 'Lenovo IdeaPad 3 with 12th Gen Intel Core i5, 15.6-inch FHD IPS display, 8GB RAM, 512GB SSD, and Dolby Audio speakers.', 425000.00, 475000.00, 360000.00, 18, 'LEN-IP3-I5', NULL, 1, 0, 1, 0, 'active', 6),
(2, 'MacBook Air M2 13-inch', 'macbook-air-m2', 'MLX13', 'Apple', 'M2 Chip, 8GB RAM, 256GB SSD, Liquid Retina', 'Apple MacBook Air with M2 chip, 13.6-inch Liquid Retina display, 8GB unified memory, 256GB SSD, 18-hour battery life. Fanless design in stunning Midnight color.', 1450000.00, NULL, 1200000.00, 8, 'APL-MBA-M2', NULL, 1, 1, 1, 0, 'active', 7),

-- TV & Audio (category 3)
(3, 'Samsung 55" Crystal UHD Smart TV', 'samsung-55-crystal-uhd', 'UA55CU7000', 'Samsung', '55" 4K UHD, Crystal Processor, Smart TV', 'Samsung 55-inch Crystal UHD 4K Smart TV with Crystal Processor 4K, HDR, Adaptive Sound, and Tizen Smart TV platform. Voice control with Bixby and Alexa.', 485000.00, 550000.00, 390000.00, 10, 'SAM-55CU7', NULL, 1, 1, 0, 1, 'active', 8),
(3, 'JBL Charge 5 Portable Speaker', 'jbl-charge-5', 'JBLCH5BLK', 'JBL', 'Portable Bluetooth Speaker, IP67, 20Hr Battery', 'JBL Charge 5 portable Bluetooth speaker with IP67 waterproof and dustproof rating, 20 hours of playtime, built-in powerbank, and JBL PartyBoost for stereo sound.', 125000.00, 145000.00, 95000.00, 30, 'JBL-CHG5-BK', NULL, 1, 0, 1, 0, 'active', 9),
(3, 'Sony WH-1000XM5 Headphones', 'sony-wh1000xm5', 'WH1000XM5', 'Sony', 'Wireless NC, 30Hr Battery, Hi-Res Audio', 'Sony WH-1000XM5 industry-leading noise cancelling headphones with Auto NC Optimizer, 30-hour battery, multipoint connection, and LDAC Hi-Res Audio support.', 285000.00, 320000.00, 220000.00, 20, 'SNY-WH1K5-BK', NULL, 1, 1, 1, 0, 'active', 10),

-- Cameras (category 4)
(4, 'Canon EOS R50 Mirrorless Camera', 'canon-eos-r50', 'EOSR50', 'Canon', '24.2MP APS-C, 4K Video, Dual Pixel AF', 'Canon EOS R50 mirrorless camera with 24.2MP APS-C CMOS sensor, 4K 30p video, Dual Pixel CMOS AF II, 15fps continuous shooting, and vari-angle touchscreen.', 595000.00, 650000.00, 480000.00, 8, 'CAN-R50-BK', NULL, 1, 1, 0, 0, 'active', 11),
(4, 'GoPro Hero 12 Black', 'gopro-hero-12', 'CHDHX-121', 'GoPro', '5.3K Video, HyperSmooth 6.0, Waterproof', 'GoPro HERO12 Black action camera with 5.3K video, HyperSmooth 6.0 stabilization, 27MP photos, 10-bit color, and waterproof to 33ft without housing.', 265000.00, 295000.00, 210000.00, 15, 'GPR-H12-BK', NULL, 1, 0, 1, 1, 'active', 12),

-- Headphones (category 5)
(5, 'Apple AirPods Pro 2nd Gen', 'airpods-pro-2', 'MTJV3', 'Apple', 'Active Noise Cancellation, USB-C, Adaptive Audio', 'Apple AirPods Pro 2nd generation with H2 chip, Active Noise Cancellation, Adaptive Audio, Personalized Spatial Audio, USB-C MagSafe case, and 6hr battery.', 320000.00, 350000.00, 260000.00, 25, 'APL-APP2-USB', NULL, 1, 1, 1, 0, 'active', 13),
(5, 'Samsung Galaxy Buds2 Pro', 'galaxy-buds2-pro', 'SM-R510', 'Samsung', 'ANC, 24bit Hi-Fi, 360 Audio, IPX7', 'Samsung Galaxy Buds2 Pro with intelligent ANC, 24-bit Hi-Fi sound, 360 Audio, IPX7 water resistance, and up to 5 hours of continuous listening.', 165000.00, 195000.00, 130000.00, 22, 'SAM-GB2P-GY', NULL, 1, 0, 0, 1, 'active', 14),

-- Smartwatch (category 6)
(6, 'Samsung Galaxy Watch 6 Classic', 'galaxy-watch-6-classic', 'SM-R960', 'Samsung', 'Rotating Bezel, BioActive Sensor, WearOS', 'Samsung Galaxy Watch 6 Classic with iconic rotating bezel, advanced BioActive Sensor for health tracking, sapphire crystal glass, Wear OS powered by Samsung.', 320000.00, 375000.00, 260000.00, 12, 'SAM-GW6C-BK', NULL, 1, 1, 0, 1, 'active', 15),
(6, 'Apple Watch Series 9 GPS 45mm', 'apple-watch-series-9', 'MRY53', 'Apple', 'S9 Chip, Always-On Retina, Blood Oxygen', 'Apple Watch Series 9 with S9 chip, Always-On Retina display, blood oxygen measurement, ECG app, temperature sensing, and 18-hour battery life.', 485000.00, NULL, 400000.00, 10, 'APL-AW9-45', NULL, 1, 1, 1, 0, 'active', 16),

-- Accessories (category 7)
(7, 'Anker PowerCore 20000mAh Power Bank', 'anker-powercore-20000', 'A1289', 'Anker', '20000mAh, Dual USB, PowerIQ Technology', 'Anker PowerCore 20000mAh portable charger with dual USB ports, PowerIQ fast charging technology, voltage boost, and multiProtect safety system.', 35000.00, 42000.00, 25000.00, 60, 'ANK-PC20K-BK', NULL, 1, 0, 1, 0, 'active', 17),
(7, 'Logitech MX Keys Wireless Keyboard', 'logitech-mx-keys', '920-009294', 'Logitech', 'Wireless, Backlit, Smart Illumination', 'Logitech MX Keys wireless keyboard with smart backlighting, Perfect Stroke keys, USB-C rechargeable, and multi-device connectivity for up to 3 devices.', 75000.00, 85000.00, 55000.00, 25, 'LOG-MXK-BK', NULL, 1, 0, 0, 0, 'active', 18),
(7, 'Apple 20W USB-C Power Adapter', 'apple-20w-usb-c', 'MHJE3', 'Apple', 'Fast Charging for iPhone/iPad', 'Apple 20W USB-C Power Adapter for fast charging iPhone, iPad, and other USB-C devices. Compatible with iPhone 8 and later.', 22000.00, NULL, 16000.00, 80, 'APL-20W-ADP', NULL, 1, 0, 1, 0, 'active', 19),
(7, 'Spigen Tough Armor Case iPhone 15 Pro', 'spigen-tough-armor-15pro', 'TG00310', 'Spigen', 'Military-Grade Protection, Kickstand', 'Spigen Tough Armor case for iPhone 15 Pro with military-grade drop protection, built-in kickstand, air cushion technology, and wireless charging compatibility.', 18000.00, 22000.00, 12000.00, 45, 'SPG-TA15P-BK', NULL, 1, 0, 0, 1, 'active', 20);
