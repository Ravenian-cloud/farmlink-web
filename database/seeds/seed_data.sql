USE farmlink_db;

-- Categories
INSERT INTO categories (name, slug, type, icon) VALUES
('Cereals & Grains', 'cereals-grains', 'produce', 'wheat'),
('Fresh Vegetables', 'fresh-vegetables', 'produce', 'carrot'),
('Fruits & Avocados', 'fruits-avocados', 'produce', 'apple'),
('Dairy & Poultry', 'dairy-poultry', 'produce', 'egg'),
('Certified Seeds', 'certified-seeds', 'input', 'sprout'),
('Fertilizers & Soil Enhancers', 'fertilizers', 'input', 'flask'),
('Herbicides & Pesticides', 'crop-protection', 'input', 'shield'),
('Farm Tools & Irrigation', 'farm-tools', 'input', 'wrench');

-- Test Accounts (Password for all accounts: Password123!)
INSERT INTO users (full_name, email, phone, password_hash, role, county, sub_county, latitude, longitude, kyc_status, preferred_lang) VALUES
('Kamau Njoroge', 'farmer@farmlink.co.ke', '254711111111', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'farmer', 'Nakuru', 'Rongai', -0.17330000, 35.86360000, 'verified', 'en'),
('Amina Hassan', 'buyer@farmlink.co.ke', '254722222222', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'buyer', 'Nairobi', 'Westlands', -1.26830000, 36.81110000, 'verified', 'en'),
('Rift Valley Agrovet Ltd', 'supplier@farmlink.co.ke', '254733333333', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'supplier', 'Nakuru', 'Nakuru Town East', -0.28330000, 36.06670000, 'verified', 'en'),
('System Admin', 'admin@farmlink.co.ke', '254700000000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Nakuru', 'Rongai', -0.17330000, 35.86360000, 'verified', 'en');

-- Sample Listings
INSERT INTO listings (seller_id, category_id, listing_type, title, description, unit, unit_price, quantity_available, min_order_qty, is_negotiable, county, latitude, longitude, harvest_or_expiry_date, image_path) VALUES
(1, 1, 'produce', 'Grade 1 Dry White Maize', 'Sun-dried, well-sorted white maize harvested this season. Moisture content below 13%.', '90kg bag', 4200.00, 120.00, 5.00, 1, 'Nakuru', -0.17330000, 35.86360000, '2026-10-28', 'assets/img/maize.svg'),
(1, 2, 'produce', 'Red Creole Onions', 'Freshly cured Red Creole onions ready for wholesale transport.', 'kg', 75.00, 1500.00, 50.00, 1, 'Nakuru', -0.17330000, 35.86360000, '2026-11-05', 'assets/img/onions.svg'),
(3, 6, 'input', 'DAP Planting Fertilizer (50kg)', 'High-phosphorus diammonium phosphate fertilizer for strong root establishment.', '50kg bag', 5800.00, 300.00, 1.00, 0, 'Nakuru', -0.28330000, 36.06670000, '2028-06-30', 'assets/img/fertilizer.svg'),
(3, 5, 'input', 'H614D Hybrid Maize Seed (10kg)', 'High-altitude certified hybrid maize seed resistant to grey leaf spot.', '10kg packet', 2650.00, 85.00, 1.00, 0, 'Nakuru', -0.28330000, 36.06670000, '2027-09-15', 'assets/img/seed.svg');

-- Sample Weather & Pest Alerts
INSERT INTO alerts (county, alert_type, severity, title, message, expires_at) VALUES
('Nakuru', 'weather', 'warning', 'Moderate Short Rains Expected', 'Above-average rainfall forecast across Rongai and Njoro over the next 72 hours. Ensure proper drainage for vegetable plots.', DATE_ADD(NOW(), INTERVAL 5 DAY)),
('Nakuru', 'pest', 'critical', 'Fall Armyworm Early Scouting Alert', 'Farmers with early-stage maize are advised to inspect whorls weekly and apply recommended biological control.', DATE_ADD(NOW(), INTERVAL 10 DAY));

-- Sample Historical Price Snapshots (for Analytics Charts)
INSERT INTO market_price_snapshots (category_id, county, crop_name, avg_unit_price, total_volume_traded, recorded_date) VALUES
(1, 'Nakuru', 'Dry White Maize (90kg)', 3950.00, 420, DATE_SUB(CURDATE(), INTERVAL 21 DAY)),
(1, 'Nakuru', 'Dry White Maize (90kg)', 4050.00, 510, DATE_SUB(CURDATE(), INTERVAL 14 DAY)),
(1, 'Nakuru', 'Dry White Maize (90kg)', 4150.00, 610, DATE_SUB(CURDATE(), INTERVAL 7 DAY)),
(1, 'Nakuru', 'Dry White Maize (90kg)', 4200.00, 680, CURDATE());
