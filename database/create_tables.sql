-- MVNO 데이터베이스 테이블 생성
USE `mvno_db`;

-- 상품 기본 테이블
CREATE TABLE IF NOT EXISTS `products` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `seller_id` INT(11) UNSIGNED NOT NULL,
    `product_type` ENUM('mvno', 'mno', 'internet') NOT NULL,
    `status` ENUM('active', 'inactive', 'deleted') NOT NULL DEFAULT 'active',
    `view_count` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `favorite_count` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `review_count` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `share_count` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `application_count` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_seller_id` (`seller_id`),
    KEY `idx_product_type` (`product_type`),
    KEY `idx_status` (`status`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- MVNO 상품 상세 테이블
CREATE TABLE IF NOT EXISTS `product_mvno_details` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` INT(11) UNSIGNED NOT NULL,
    `provider` VARCHAR(50) NOT NULL,
    `service_type` VARCHAR(50) DEFAULT NULL,
    `plan_name` VARCHAR(100) NOT NULL,
    `contract_period` VARCHAR(50) DEFAULT NULL,
    `contract_period_days` INT(11) DEFAULT NULL,
    `discount_period` VARCHAR(50) DEFAULT NULL,
    `price_main` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `price_after` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `data_amount` VARCHAR(50) DEFAULT NULL,
    `data_amount_value` VARCHAR(20) DEFAULT NULL,
    `data_unit` VARCHAR(10) DEFAULT NULL,
    `data_additional` VARCHAR(50) DEFAULT NULL,
    `data_additional_value` VARCHAR(50) DEFAULT NULL,
    `data_exhausted` VARCHAR(50) DEFAULT NULL,
    `data_exhausted_value` VARCHAR(50) DEFAULT NULL,
    `call_type` VARCHAR(50) DEFAULT NULL,
    `call_amount` VARCHAR(20) DEFAULT NULL,
    `additional_call_type` VARCHAR(50) DEFAULT NULL,
    `additional_call` VARCHAR(20) DEFAULT NULL,
    `sms_type` VARCHAR(50) DEFAULT NULL,
    `sms_amount` VARCHAR(20) DEFAULT NULL,
    `mobile_hotspot` VARCHAR(50) DEFAULT NULL,
    `mobile_hotspot_value` VARCHAR(20) DEFAULT NULL,
    `regular_sim_available` VARCHAR(10) DEFAULT NULL,
    `regular_sim_price` VARCHAR(20) DEFAULT NULL,
    `nfc_sim_available` VARCHAR(10) DEFAULT NULL,
    `nfc_sim_price` VARCHAR(20) DEFAULT NULL,
    `esim_available` VARCHAR(10) DEFAULT NULL,
    `esim_price` VARCHAR(20) DEFAULT NULL,
    `over_data_price` VARCHAR(20) DEFAULT NULL,
    `over_voice_price` VARCHAR(20) DEFAULT NULL,
    `over_video_price` VARCHAR(20) DEFAULT NULL,
    `over_sms_price` VARCHAR(20) DEFAULT NULL,
    `over_lms_price` VARCHAR(20) DEFAULT NULL,
    `over_mms_price` VARCHAR(20) DEFAULT NULL,
    `promotion_title` VARCHAR(200) DEFAULT NULL,
    `promotions` TEXT DEFAULT NULL,
    `benefits` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_product_id` (`product_id`),
    KEY `idx_provider` (`provider`),
    CONSTRAINT `fk_mvno_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


