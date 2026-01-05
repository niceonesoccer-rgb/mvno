-- ============================================
-- 광고 분석 데이터베이스 테이블 생성
-- 생성일: 2025-01-XX
-- ============================================

USE `mvno_db`;

-- ============================================
-- 1. 광고 노출 추적 테이블 (Impression)
-- 광고가 화면에 표시될 때마다 기록
-- ============================================
CREATE TABLE IF NOT EXISTS `advertisement_impressions` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `advertisement_id` INT(11) UNSIGNED NOT NULL COMMENT '광고 ID (rotation_advertisements.id)',
    `product_id` INT(11) UNSIGNED NOT NULL COMMENT '상품 ID',
    `seller_id` VARCHAR(50) NOT NULL COMMENT '판매자 ID',
    `product_type` ENUM('mvno', 'mno', 'internet', 'mno_sim') NOT NULL COMMENT '상품 타입',
    `user_id` VARCHAR(50) DEFAULT NULL COMMENT '사용자 ID (로그인한 경우)',
    `ip_address` VARCHAR(45) DEFAULT NULL COMMENT 'IP 주소',
    `user_agent` VARCHAR(500) DEFAULT NULL COMMENT 'User Agent',
    `referrer` VARCHAR(500) DEFAULT NULL COMMENT '리퍼러 URL',
    `page_url` VARCHAR(500) DEFAULT NULL COMMENT '페이지 URL',
    `device_type` ENUM('desktop', 'mobile', 'tablet', 'unknown') DEFAULT 'unknown' COMMENT '기기 타입',
    `browser` VARCHAR(100) DEFAULT NULL COMMENT '브라우저',
    `os` VARCHAR(100) DEFAULT NULL COMMENT '운영체제',
    `session_id` VARCHAR(100) DEFAULT NULL COMMENT '세션 ID',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '노출 시간',
    PRIMARY KEY (`id`),
    KEY `idx_advertisement_id` (`advertisement_id`),
    KEY `idx_product_id` (`product_id`),
    KEY `idx_seller_id` (`seller_id`),
    KEY `idx_product_type` (`product_type`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_advertisement_created` (`advertisement_id`, `created_at`),
    CONSTRAINT `fk_impression_advertisement` FOREIGN KEY (`advertisement_id`) REFERENCES `rotation_advertisements` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_impression_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='광고 노출 추적';

-- ============================================
-- 2. 광고 클릭 추적 테이블 (Click)
-- 사용자가 광고를 클릭할 때마다 기록
-- ============================================
CREATE TABLE IF NOT EXISTS `advertisement_clicks` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `advertisement_id` INT(11) UNSIGNED NOT NULL COMMENT '광고 ID (rotation_advertisements.id)',
    `product_id` INT(11) UNSIGNED NOT NULL COMMENT '상품 ID',
    `seller_id` VARCHAR(50) NOT NULL COMMENT '판매자 ID',
    `product_type` ENUM('mvno', 'mno', 'internet', 'mno_sim') NOT NULL COMMENT '상품 타입',
    `user_id` VARCHAR(50) DEFAULT NULL COMMENT '사용자 ID (로그인한 경우)',
    `ip_address` VARCHAR(45) DEFAULT NULL COMMENT 'IP 주소',
    `user_agent` VARCHAR(500) DEFAULT NULL COMMENT 'User Agent',
    `referrer` VARCHAR(500) DEFAULT NULL COMMENT '리퍼러 URL',
    `page_url` VARCHAR(500) DEFAULT NULL COMMENT '클릭한 페이지 URL',
    `target_url` VARCHAR(500) DEFAULT NULL COMMENT '클릭한 목적지 URL',
    `device_type` ENUM('desktop', 'mobile', 'tablet', 'unknown') DEFAULT 'unknown' COMMENT '기기 타입',
    `browser` VARCHAR(100) DEFAULT NULL COMMENT '브라우저',
    `os` VARCHAR(100) DEFAULT NULL COMMENT '운영체제',
    `session_id` VARCHAR(100) DEFAULT NULL COMMENT '세션 ID',
    `click_type` ENUM('direct', 'detail', 'apply', 'other') DEFAULT 'direct' COMMENT '클릭 유형',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '클릭 시간',
    PRIMARY KEY (`id`),
    KEY `idx_advertisement_id` (`advertisement_id`),
    KEY `idx_product_id` (`product_id`),
    KEY `idx_seller_id` (`seller_id`),
    KEY `idx_product_type` (`product_type`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_advertisement_created` (`advertisement_id`, `created_at`),
    KEY `idx_click_type` (`click_type`),
    CONSTRAINT `fk_click_advertisement` FOREIGN KEY (`advertisement_id`) REFERENCES `rotation_advertisements` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_click_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='광고 클릭 추적';

-- ============================================
-- 3. 광고 통계 집계 테이블 (Analytics)
-- 일별/시간별 통계를 집계하여 저장 (성능 최적화)
-- ============================================
CREATE TABLE IF NOT EXISTS `advertisement_analytics` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `advertisement_id` INT(11) UNSIGNED NOT NULL COMMENT '광고 ID (rotation_advertisements.id)',
    `product_id` INT(11) UNSIGNED NOT NULL COMMENT '상품 ID',
    `seller_id` VARCHAR(50) NOT NULL COMMENT '판매자 ID',
    `product_type` ENUM('mvno', 'mno', 'internet', 'mno_sim') NOT NULL COMMENT '상품 타입',
    `stat_date` DATE NOT NULL COMMENT '통계 날짜',
    `stat_hour` TINYINT(2) UNSIGNED DEFAULT NULL COMMENT '통계 시간 (0-23, NULL이면 일별 통계)',
    `impression_count` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '노출 횟수',
    `click_count` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '클릭 횟수',
    `unique_impressions` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '고유 노출 수 (IP 기준)',
    `unique_clicks` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '고유 클릭 수 (IP 기준)',
    `ctr` DECIMAL(5,4) DEFAULT 0.0000 COMMENT '클릭률 (Click Through Rate) = 클릭/노출',
    `desktop_impressions` INT(11) UNSIGNED DEFAULT 0 COMMENT '데스크톱 노출',
    `mobile_impressions` INT(11) UNSIGNED DEFAULT 0 COMMENT '모바일 노출',
    `tablet_impressions` INT(11) UNSIGNED DEFAULT 0 COMMENT '태블릿 노출',
    `desktop_clicks` INT(11) UNSIGNED DEFAULT 0 COMMENT '데스크톱 클릭',
    `mobile_clicks` INT(11) UNSIGNED DEFAULT 0 COMMENT '모바일 클릭',
    `tablet_clicks` INT(11) UNSIGNED DEFAULT 0 COMMENT '태블릿 클릭',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '업데이트 시간',
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_ad_stat` (`advertisement_id`, `stat_date`, `stat_hour`),
    KEY `idx_advertisement_id` (`advertisement_id`),
    KEY `idx_product_id` (`product_id`),
    KEY `idx_seller_id` (`seller_id`),
    KEY `idx_product_type` (`product_type`),
    KEY `idx_stat_date` (`stat_date`),
    KEY `idx_stat_hour` (`stat_hour`),
    CONSTRAINT `fk_analytics_advertisement` FOREIGN KEY (`advertisement_id`) REFERENCES `rotation_advertisements` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_analytics_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='광고 통계 집계';

-- ============================================
-- 완료
-- ============================================
