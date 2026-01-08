-- ============================================
-- 프로덕션 DB 전체 재구성 스크립트
-- ganadamobile.co.kr 배포용
-- 실행 전 반드시 백업하세요!
-- ============================================

-- 주의: 이 스크립트는 모든 데이터를 삭제하고 테이블을 재생성합니다!
-- 실행 전 반드시 백업을 받으세요!

-- 데이터베이스 선택 (프로덕션: dbdanora)
USE `dbdanora`;

-- ============================================
-- 1단계: 외래키 제약조건 제거 (테이블 삭제를 위해)
-- ============================================
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- 2단계: 기존 테이블 삭제 (모든 데이터 삭제)
-- ============================================
DROP TABLE IF EXISTS `product_shares`;
DROP TABLE IF EXISTS `product_favorites`;
DROP TABLE IF EXISTS `product_reviews`;
DROP TABLE IF EXISTS `product_applications`;
DROP TABLE IF EXISTS `application_customers`;
DROP TABLE IF EXISTS `product_mno_sim_details`;
DROP TABLE IF EXISTS `product_internet_details`;
DROP TABLE IF EXISTS `product_mno_details`;
DROP TABLE IF EXISTS `product_mvno_details`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `user_point_ledger`;
DROP TABLE IF EXISTS `user_point_accounts`;
DROP TABLE IF EXISTS `forbidden_ids`;
DROP TABLE IF EXISTS `events`;
DROP TABLE IF EXISTS `notices`;
DROP TABLE IF EXISTS `qna`;
DROP TABLE IF EXISTS `seller_inquiries`;
DROP TABLE IF EXISTS `seller_notices`;
DROP TABLE IF EXISTS `rotation_advertisements`;
DROP TABLE IF EXISTS `advertisement_analytics`;
DROP TABLE IF EXISTS `terms_versions`;
DROP TABLE IF EXISTS `system_settings`;
DROP TABLE IF EXISTS `product_review_statistics`;
DROP TABLE IF EXISTS `devices`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `sellers`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `profiles`;

-- ============================================
-- 3단계: 기본 테이블 생성
-- ============================================

-- 사용자 테이블
CREATE TABLE IF NOT EXISTS `users` (
    `user_id` VARCHAR(50) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `role` ENUM('user', 'seller', 'admin') NOT NULL DEFAULT 'user',
    `status` ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    `email_verified` TINYINT(1) NOT NULL DEFAULT 0,
    `email_verification_token` VARCHAR(255) DEFAULT NULL,
    `password_reset_token` VARCHAR(255) DEFAULT NULL,
    `password_reset_expires` DATETIME DEFAULT NULL,
    `last_login` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `service_notice_opt_in` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '서비스 이용 및 혜택 안내 알림(필수)',
    `marketing_opt_in` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '광고성 정보 수신동의(선택) 전체',
    `marketing_email_opt_in` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '광고성: 이메일 수신동의',
    `marketing_sms_sns_opt_in` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '광고성: SMS,SNS 수신동의',
    `marketing_push_opt_in` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '광고성: 앱 푸시 수신동의',
    `alarm_settings_updated_at` DATETIME DEFAULT NULL COMMENT '알림 설정 업데이트일',
    `chat_consultation_url` VARCHAR(500) DEFAULT NULL COMMENT '채팅상담 URL',
    PRIMARY KEY (`user_id`),
    UNIQUE KEY `uk_email` (`email`),
    KEY `idx_role` (`role`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='사용자';

-- 판매자 테이블
CREATE TABLE IF NOT EXISTS `sellers` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` VARCHAR(50) NOT NULL,
    `company_name` VARCHAR(255) NOT NULL,
    `business_number` VARCHAR(50) DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `status` ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_id` (`user_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='판매자';

-- ============================================
-- 4단계: 상품 테이블 생성 (최신 스키마)
-- ============================================

-- 상품 기본 테이블 (point_setting, point_benefit_description 포함)
CREATE TABLE IF NOT EXISTS `products` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `seller_id` INT(11) UNSIGNED NOT NULL COMMENT '판매자 ID',
    `product_type` ENUM('mvno', 'mno', 'internet', 'mno-sim') NOT NULL COMMENT '상품 타입',
    `status` ENUM('active', 'inactive', 'deleted') NOT NULL DEFAULT 'active' COMMENT '상품 상태',
    `view_count` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '조회수',
    `favorite_count` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '찜 수',
    `review_count` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '리뷰 수',
    `share_count` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '공유 수',
    `application_count` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '신청 수',
    `point_setting` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '포인트 설정 (0이면 포인트 사용 불가, 1000원 단위)',
    `point_benefit_description` TEXT DEFAULT NULL COMMENT '포인트 사용 시 할인 혜택 내용',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    PRIMARY KEY (`id`),
    KEY `idx_seller_id` (`seller_id`),
    KEY `idx_product_type` (`product_type`),
    KEY `idx_status` (`status`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_point_setting` (`point_setting`),
    CONSTRAINT `fk_product_seller` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='상품 기본 정보';

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='MVNO 상품 상세';

-- MNO 상품 상세 테이블
CREATE TABLE IF NOT EXISTS `product_mno_details` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` INT(11) UNSIGNED NOT NULL,
    `device_name` VARCHAR(255) NOT NULL,
    `device_price` DECIMAL(10,2) DEFAULT NULL,
    `device_capacity` VARCHAR(50) DEFAULT NULL,
    `device_colors` TEXT DEFAULT NULL,
    `common_provider` TEXT DEFAULT NULL,
    `common_plan` TEXT DEFAULT NULL,
    `common_discount_new` TEXT DEFAULT NULL,
    `common_discount_port` TEXT DEFAULT NULL,
    `common_discount_change` TEXT DEFAULT NULL,
    `contract_provider` TEXT DEFAULT NULL,
    `contract_plan` TEXT DEFAULT NULL,
    `contract_discount_new` TEXT DEFAULT NULL,
    `contract_discount_port` TEXT DEFAULT NULL,
    `contract_discount_change` TEXT DEFAULT NULL,
    `service_type` VARCHAR(50) DEFAULT NULL,
    `contract_period` VARCHAR(50) DEFAULT NULL,
    `contract_period_value` VARCHAR(20) DEFAULT NULL,
    `price_main` INT(11) DEFAULT 0,
    `data_amount` VARCHAR(50) DEFAULT NULL,
    `data_amount_value` VARCHAR(20) DEFAULT NULL,
    `data_unit` VARCHAR(10) DEFAULT NULL,
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
    `delivery_method` VARCHAR(50) DEFAULT 'delivery',
    `visit_region` VARCHAR(255) DEFAULT NULL,
    `redirect_url` VARCHAR(500) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_product_id` (`product_id`),
    CONSTRAINT `fk_mno_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='MNO 상품 상세';

-- Internet 상품 상세 테이블
CREATE TABLE IF NOT EXISTS `product_internet_details` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` INT(11) UNSIGNED NOT NULL,
    `provider` VARCHAR(50) NOT NULL,
    `service_type` VARCHAR(50) DEFAULT NULL,
    `speed_option` VARCHAR(100) DEFAULT NULL,
    `monthly_fee` VARCHAR(50) DEFAULT NULL,
    `cash_payment_names` TEXT DEFAULT NULL,
    `cash_payment_prices` TEXT DEFAULT NULL,
    `gift_card_names` TEXT DEFAULT NULL,
    `gift_card_prices` TEXT DEFAULT NULL,
    `equipment_names` TEXT DEFAULT NULL,
    `equipment_prices` TEXT DEFAULT NULL,
    `installation_names` TEXT DEFAULT NULL,
    `installation_prices` TEXT DEFAULT NULL,
    `registration_place` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_product_id` (`product_id`),
    CONSTRAINT `fk_internet_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Internet 상품 상세';

-- MNO-SIM 상품 상세 테이블
CREATE TABLE IF NOT EXISTS `product_mno_sim_details` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` INT(11) UNSIGNED NOT NULL,
    `provider` VARCHAR(50) NOT NULL,
    `plan_name` VARCHAR(100) NOT NULL,
    `price_main` INT(11) DEFAULT 0,
    `data_amount` VARCHAR(50) DEFAULT NULL,
    `call_type` VARCHAR(50) DEFAULT NULL,
    `call_amount` VARCHAR(20) DEFAULT NULL,
    `sms_type` VARCHAR(50) DEFAULT NULL,
    `sms_amount` VARCHAR(20) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_product_id` (`product_id`),
    CONSTRAINT `fk_mno_sim_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='MNO-SIM 상품 상세';

-- ============================================
-- 5단계: 신청 관련 테이블 생성
-- ============================================

-- 상품 신청 테이블 (order_number, user_id, mno-sim 포함)
CREATE TABLE IF NOT EXISTS `product_applications` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_number` VARCHAR(20) DEFAULT NULL COMMENT '주문번호',
    `product_id` INT(11) UNSIGNED NOT NULL COMMENT '상품 ID',
    `seller_id` INT(11) UNSIGNED NOT NULL COMMENT '판매자 ID',
    `product_type` ENUM('mvno', 'mno', 'internet', 'mno-sim') NOT NULL COMMENT '상품 타입',
    `user_id` VARCHAR(50) DEFAULT NULL COMMENT '신청자 user_id (users.user_id)',
    `application_status` ENUM('pending', 'processing', 'completed', 'cancelled', 'rejected', 'closed') NOT NULL DEFAULT 'pending' COMMENT '신청 상태',
    `status_changed_at` DATETIME DEFAULT NULL COMMENT '상태 변경일시',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '신청일시',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_order_number` (`order_number`),
    KEY `idx_product_id` (`product_id`),
    KEY `idx_seller_id` (`seller_id`),
    KEY `idx_product_type` (`product_type`),
    KEY `idx_application_status` (`application_status`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_user_id` (`user_id`),
    CONSTRAINT `fk_application_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='상품 신청';

-- 신청 고객 정보 테이블
CREATE TABLE IF NOT EXISTS `application_customers` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `application_id` INT(11) UNSIGNED NOT NULL,
    `user_id` VARCHAR(50) DEFAULT NULL COMMENT '회원 user_id (비회원 신청 가능)',
    `name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `address_detail` VARCHAR(255) DEFAULT NULL,
    `birth_date` DATE DEFAULT NULL,
    `gender` ENUM('male', 'female', 'other') DEFAULT NULL,
    `additional_info` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_application_id` (`application_id`),
    KEY `idx_user_id` (`user_id`),
    CONSTRAINT `fk_customer_application` FOREIGN KEY (`application_id`) REFERENCES `product_applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='신청 고객 정보';

-- ============================================
-- 6단계: 포인트 관련 테이블 생성
-- ============================================

-- 포인트 계정 테이블
CREATE TABLE IF NOT EXISTS `user_point_accounts` (
    `user_id` VARCHAR(50) NOT NULL,
    `balance` INT(11) NOT NULL DEFAULT 0,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='사용자 포인트 잔액';

-- 포인트 원장 테이블
CREATE TABLE IF NOT EXISTS `user_point_ledger` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` VARCHAR(50) NOT NULL,
    `delta` INT(11) NOT NULL,
    `type` VARCHAR(20) NOT NULL,
    `item_id` VARCHAR(64) DEFAULT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `balance_after` INT(11) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_points_user_created` (`user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='포인트 원장';

-- ============================================
-- 7단계: 기타 테이블 생성
-- ============================================

-- 상품 찜 테이블
CREATE TABLE IF NOT EXISTS `product_favorites` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` VARCHAR(50) NOT NULL,
    `product_id` INT(11) UNSIGNED NOT NULL,
    `product_type` ENUM('mvno', 'mno', 'internet', 'mno-sim') NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_product` (`user_id`, `product_id`, `product_type`),
    KEY `idx_product_id` (`product_id`),
    CONSTRAINT `fk_favorite_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='상품 찜';

-- 상품 리뷰 테이블
CREATE TABLE IF NOT EXISTS `product_reviews` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` INT(11) UNSIGNED NOT NULL,
    `product_type` ENUM('mvno', 'mno', 'internet', 'mno-sim') NOT NULL,
    `user_id` VARCHAR(50) NOT NULL,
    `application_id` INT(11) UNSIGNED DEFAULT NULL COMMENT '신청 ID',
    `rating` TINYINT(1) UNSIGNED NOT NULL DEFAULT 5,
    `kindness_rating` TINYINT(1) UNSIGNED DEFAULT NULL COMMENT '친절해요 평점 (1-5)',
    `speed_rating` TINYINT(1) UNSIGNED DEFAULT NULL COMMENT '개통/설치 빨라요 평점 (1-5)',
    `content` TEXT NOT NULL,
    `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_product_id_type_status` (`product_id`, `product_type`, `status`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_application_id` (`application_id`),
    CONSTRAINT `fk_review_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='상품 리뷰';

-- 상품 공유 테이블
CREATE TABLE IF NOT EXISTS `product_shares` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` INT(11) UNSIGNED NOT NULL,
    `user_id` VARCHAR(50) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_product_id` (`product_id`),
    CONSTRAINT `fk_share_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='상품 공유';

-- ============================================
-- 8단계: 외래키 제약조건 재활성화
-- ============================================
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- 완료 메시지
-- ============================================
SELECT 'Database recreation completed successfully!' AS message;
