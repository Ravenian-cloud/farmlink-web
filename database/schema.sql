-- ============================================================================
-- FarmLink Database Schema (MySQL 8.0+ / MariaDB 10.4+)
-- ============================================================================

CREATE DATABASE IF NOT EXISTS farmlink_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE farmlink_db;

SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- 1. USERS & KYC VERIFICATION
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL UNIQUE COMMENT 'Format: 2547XXXXXXXX for M-Pesa',
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('farmer', 'buyer', 'supplier', 'admin') NOT NULL DEFAULT 'farmer',
    county VARCHAR(80) NOT NULL,
    sub_county VARCHAR(80) NULL,
    latitude DECIMAL(10, 8) NULL,
    longitude DECIMAL(11, 8) NULL,
    kyc_status ENUM('unverified', 'pending', 'verified', 'rejected') NOT NULL DEFAULT 'unverified',
    kyc_document_path VARCHAR(255) NULL,
    is_premium TINYINT(1) NOT NULL DEFAULT 0,
    preferred_lang ENUM('en', 'sw') NOT NULL DEFAULT 'en',
    rating_avg DECIMAL(3, 2) NOT NULL DEFAULT 0.00,
    rating_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_role_county (role, county),
    INDEX idx_users_geo (latitude, longitude)
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- 2. CATEGORIES (PRODUCE & AGRIBUSINESS INPUTS)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS categories;
CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    type ENUM('produce', 'input') NOT NULL,
    icon VARCHAR(60) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- 3. MARKETPLACE LISTINGS (PRODUCE & FARM INPUTS)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS listings;
CREATE TABLE listings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    seller_id BIGINT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    listing_type ENUM('produce', 'input') NOT NULL,
    title VARCHAR(160) NOT NULL,
    description TEXT NOT NULL,
    unit VARCHAR(30) NOT NULL COMMENT 'e.g., kg, 90kg bag, crate, litre, packet',
    unit_price DECIMAL(12, 2) NOT NULL,
    quantity_available DECIMAL(12, 2) NOT NULL,
    min_order_qty DECIMAL(12, 2) NOT NULL DEFAULT 1.00,
    is_negotiable TINYINT(1) NOT NULL DEFAULT 1,
    county VARCHAR(80) NOT NULL,
    latitude DECIMAL(10, 8) NULL,
    longitude DECIMAL(11, 8) NULL,
    harvest_or_expiry_date DATE NULL,
    image_path VARCHAR(255) NULL,
    status ENUM('active', 'sold_out', 'archived') NOT NULL DEFAULT 'active',
    offline_sync_uuid VARCHAR(64) NULL UNIQUE COMMENT 'Prevents duplicate inserts during offline sync',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_listings_seller FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_listings_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    INDEX idx_listings_filter (listing_type, status, county),
    INDEX idx_listings_geo (latitude, longitude)
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- 4. BIDS & PRICE NEGOTIATION THREADS
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS bids;
CREATE TABLE bids (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    listing_id BIGINT UNSIGNED NOT NULL,
    buyer_id BIGINT UNSIGNED NOT NULL,
    seller_id BIGINT UNSIGNED NOT NULL,
    offered_unit_price DECIMAL(12, 2) NOT NULL,
    requested_qty DECIMAL(12, 2) NOT NULL,
    counter_unit_price DECIMAL(12, 2) NULL,
    buyer_note VARCHAR(255) NULL,
    seller_note VARCHAR(255) NULL,
    status ENUM('pending', 'countered', 'accepted', 'rejected', 'expired') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_bids_listing FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
    CONSTRAINT fk_bids_buyer FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_bids_seller FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_bids_participants (buyer_id, seller_id, status)
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- 5. ORDERS & LOGISTICS TRACKING
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS orders;
CREATE TABLE orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_reference VARCHAR(32) NOT NULL UNIQUE,
    listing_id BIGINT UNSIGNED NOT NULL,
    bid_id BIGINT UNSIGNED NULL,
    buyer_id BIGINT UNSIGNED NOT NULL,
    seller_id BIGINT UNSIGNED NOT NULL,
    agreed_unit_price DECIMAL(12, 2) NOT NULL,
    quantity DECIMAL(12, 2) NOT NULL,
    total_amount DECIMAL(12, 2) NOT NULL,
    delivery_county VARCHAR(80) NOT NULL,
    delivery_address VARCHAR(255) NOT NULL,
    logistics_provider VARCHAR(120) NULL,
    logistics_contact VARCHAR(30) NULL,
    status ENUM('awaiting_payment', 'in_escrow', 'in_transit', 'delivered', 'cancelled', 'disputed') NOT NULL DEFAULT 'awaiting_payment',
    delivered_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_listing FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE RESTRICT,
    CONSTRAINT fk_orders_bid FOREIGN KEY (bid_id) REFERENCES bids(id) ON DELETE SET NULL,
    CONSTRAINT fk_orders_buyer FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_orders_seller FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_orders_status (status, buyer_id, seller_id)
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- 6. ESCROW LEDGER (FUND PROTECTION & COMMISSIONS)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS escrow_ledger;
CREATE TABLE escrow_ledger (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL UNIQUE,
    gross_amount DECIMAL(12, 2) NOT NULL,
    commission_fee DECIMAL(12, 2) NOT NULL,
    net_seller_payout DECIMAL(12, 2) NOT NULL,
    mpesa_receipt_number VARCHAR(40) NULL,
    payout_reference VARCHAR(40) NULL,
    status ENUM('held', 'released', 'refunded', 'disputed') NOT NULL DEFAULT 'held',
    held_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    released_at TIMESTAMP NULL,
    CONSTRAINT fk_escrow_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- 7. M-PESA DARAJA TRANSACTION LOGS
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS mpesa_logs;
CREATE TABLE mpesa_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NULL,
    merchant_request_id VARCHAR(80) NULL,
    checkout_request_id VARCHAR(80) NULL UNIQUE,
    phone_number VARCHAR(20) NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    mpesa_receipt_number VARCHAR(40) NULL,
    result_code INT NULL,
    result_desc VARCHAR(255) NULL,
    raw_callback_json JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_mpesa_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- 8. RATINGS & REVIEWS
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS reviews;
CREATE TABLE reviews (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL UNIQUE,
    reviewer_id BIGINT UNSIGNED NOT NULL,
    reviewee_id BIGINT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reviews_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_reviewer FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_reviewee FOREIGN KEY (reviewee_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- 9. WEATHER, PEST & DISEASE ALERTS
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS alerts;
CREATE TABLE alerts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    county VARCHAR(80) NOT NULL,
    alert_type ENUM('weather', 'pest', 'disease', 'market_price') NOT NULL,
    severity ENUM('info', 'warning', 'critical') NOT NULL DEFAULT 'info',
    title VARCHAR(160) NOT NULL,
    message TEXT NOT NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_alerts_county (county, expires_at)
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- 10. KNOWLEDGE HUB & COMMUNITY FORUM
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS forum_posts;
CREATE TABLE forum_posts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    author_id BIGINT UNSIGNED NOT NULL,
    post_type ENUM('knowledge_article', 'community_question') NOT NULL DEFAULT 'community_question',
    category_tag VARCHAR(60) NOT NULL,
    title VARCHAR(180) NOT NULL,
    body TEXT NOT NULL,
    offline_sync_uuid VARCHAR(64) NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_posts_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

DROP TABLE IF EXISTS forum_comments;
CREATE TABLE forum_comments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT UNSIGNED NOT NULL,
    author_id BIGINT UNSIGNED NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_comments_post FOREIGN KEY (post_id) REFERENCES forum_posts(id) ON DELETE CASCADE,
    CONSTRAINT fk_comments_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- 11. MARKET PRICE HISTORY (FOR AI ANALYTICS & DEMAND FORECASTING)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS market_price_snapshots;
CREATE TABLE market_price_snapshots (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    county VARCHAR(80) NOT NULL,
    crop_name VARCHAR(100) NOT NULL,
    avg_unit_price DECIMAL(12, 2) NOT NULL,
    total_volume_traded DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    recorded_date DATE NOT NULL,
    CONSTRAINT fk_snapshots_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    UNIQUE KEY uq_snapshot_daily (crop_name, county, recorded_date)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
