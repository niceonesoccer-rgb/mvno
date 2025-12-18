-- ============================================
-- MVNO 상품 관리 데이터베이스 스키마
-- 생성일: 2025-01-XX
-- ============================================

-- 데이터베이스 생성 (없는 경우)
CREATE DATABASE IF NOT EXISTS `mvno_db` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- 데이터베이스 선택
USE `mvno_db`;

-- ============================================
-- 1. 상품 기본 테이블 (products)
-- ============================================
CREATE TABLE IF NOT EXISTS `products` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `seller_id` INT(11) UNSIGNED NOT NULL COMMENT '판매자 ID',
    `product_type` ENUM('mvno', 'mno', 'internet') NOT NULL COMMENT '상품 타입',
    `status` ENUM('active', 'inactive', 'deleted') NOT NULL DEFAULT 'active' COMMENT '상품 상태',
    `view_count` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '조회수',
    `favorite_count` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '찜 수',
    `review_count` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '리뷰 수 (MVNO, MNO만)',
    `share_count` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '공유 수',
    `application_count` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '신청 수',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    PRIMARY KEY (`id`),
    KEY `idx_seller_id` (`seller_id`),
    KEY `idx_product_type` (`product_type`),
    KEY `idx_status` (`status`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='상품 기본 정보';

-- ============================================
-- 2. MVNO 상품 상세 테이블 (product_mvno_details)
-- ============================================
CREATE TABLE IF NOT EXISTS `product_mvno_details` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` INT(11) UNSIGNED NOT NULL COMMENT '상품 ID',
    `provider` VARCHAR(50) NOT NULL COMMENT '통신사',
    `service_type` VARCHAR(50) DEFAULT NULL COMMENT '서비스 타입',
    `plan_name` VARCHAR(100) NOT NULL COMMENT '요금제명',
    `contract_period` VARCHAR(50) DEFAULT NULL COMMENT '약정기간',
    `contract_period_days` INT(11) DEFAULT NULL COMMENT '약정기간(일)',
    `discount_period` VARCHAR(50) DEFAULT NULL COMMENT '할인기간',
    `price_main` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '기본 요금',
    `price_after` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '할인 후 요금',
    `data_amount` VARCHAR(50) DEFAULT NULL COMMENT '데이터량',
    `data_amount_value` VARCHAR(20) DEFAULT NULL COMMENT '데이터량 값',
    `data_unit` VARCHAR(10) DEFAULT NULL COMMENT '데이터 단위',
    `data_additional` VARCHAR(50) DEFAULT NULL COMMENT '데이터 추가제공',
    `data_additional_value` VARCHAR(50) DEFAULT NULL COMMENT '데이터 추가제공 값',
    `data_exhausted` VARCHAR(50) DEFAULT NULL COMMENT '데이터 소진 시',
    `data_exhausted_value` VARCHAR(50) DEFAULT NULL COMMENT '데이터 소진 시 값',
    `call_type` VARCHAR(50) DEFAULT NULL COMMENT '통화 타입',
    `call_amount` VARCHAR(20) DEFAULT NULL COMMENT '통화량',
    `additional_call_type` VARCHAR(50) DEFAULT NULL COMMENT '추가 통화 타입',
    `additional_call` VARCHAR(20) DEFAULT NULL COMMENT '추가 통화량',
    `sms_type` VARCHAR(50) DEFAULT NULL COMMENT 'SMS 타입',
    `sms_amount` VARCHAR(20) DEFAULT NULL COMMENT 'SMS량',
    `mobile_hotspot` VARCHAR(50) DEFAULT NULL COMMENT '모바일 핫스팟',
    `mobile_hotspot_value` VARCHAR(20) DEFAULT NULL COMMENT '모바일 핫스팟 값',
    `regular_sim_available` VARCHAR(10) DEFAULT NULL COMMENT '일반 SIM 가능 여부',
    `regular_sim_price` VARCHAR(20) DEFAULT NULL COMMENT '일반 SIM 가격',
    `nfc_sim_available` VARCHAR(10) DEFAULT NULL COMMENT 'NFC SIM 가능 여부',
    `nfc_sim_price` VARCHAR(20) DEFAULT NULL COMMENT 'NFC SIM 가격',
    `esim_available` VARCHAR(10) DEFAULT NULL COMMENT 'eSIM 가능 여부',
    `esim_price` VARCHAR(20) DEFAULT NULL COMMENT 'eSIM 가격',
    `over_data_price` VARCHAR(20) DEFAULT NULL COMMENT '데이터 초과 시 가격',
    `over_voice_price` VARCHAR(20) DEFAULT NULL COMMENT '음성 초과 시 가격',
    `over_video_price` VARCHAR(20) DEFAULT NULL COMMENT '영상통화 초과 시 가격',
    `over_sms_price` VARCHAR(20) DEFAULT NULL COMMENT 'SMS 초과 시 가격',
    `over_lms_price` VARCHAR(20) DEFAULT NULL COMMENT 'LMS 초과 시 가격',
    `over_mms_price` VARCHAR(20) DEFAULT NULL COMMENT 'MMS 초과 시 가격',
    `promotion_title` VARCHAR(200) DEFAULT NULL COMMENT '프로모션 제목',
    `promotions` TEXT DEFAULT NULL COMMENT '프로모션 목록 (JSON)',
    `benefits` TEXT DEFAULT NULL COMMENT '혜택 목록 (JSON)',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_product_id` (`product_id`),
    KEY `idx_provider` (`provider`),
    CONSTRAINT `fk_mvno_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='MVNO 상품 상세 정보';

-- ============================================
-- 3. MNO 상품 상세 테이블 (product_mno_details)
-- ============================================
CREATE TABLE IF NOT EXISTS `product_mno_details` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` INT(11) UNSIGNED NOT NULL COMMENT '상품 ID',
    `device_name` VARCHAR(100) NOT NULL COMMENT '단말기명',
    `device_price` DECIMAL(12,2) DEFAULT NULL COMMENT '단말기 출고가',
    `device_capacity` VARCHAR(20) DEFAULT NULL COMMENT '용량',
    `device_colors` TEXT DEFAULT NULL COMMENT '단말기 색상 목록 (JSON)',
    `common_provider` TEXT DEFAULT NULL COMMENT '공통지원할인 통신사 (JSON)',
    `common_discount_new` TEXT DEFAULT NULL COMMENT '공통지원할인 신규가입 (JSON)',
    `common_discount_port` TEXT DEFAULT NULL COMMENT '공통지원할인 번호이동 (JSON)',
    `common_discount_change` TEXT DEFAULT NULL COMMENT '공통지원할인 기기변경 (JSON)',
    `contract_provider` TEXT DEFAULT NULL COMMENT '선택약정할인 통신사 (JSON)',
    `contract_discount_new` TEXT DEFAULT NULL COMMENT '선택약정할인 신규가입 (JSON)',
    `contract_discount_port` TEXT DEFAULT NULL COMMENT '선택약정할인 번호이동 (JSON)',
    `contract_discount_change` TEXT DEFAULT NULL COMMENT '선택약정할인 기기변경 (JSON)',
    `service_type` VARCHAR(50) DEFAULT NULL COMMENT '서비스 타입',
    `contract_period` VARCHAR(50) DEFAULT NULL COMMENT '약정기간',
    `contract_period_value` VARCHAR(20) DEFAULT NULL COMMENT '약정기간 값',
    `price_main` DECIMAL(10,2) DEFAULT NULL COMMENT '기본 요금',
    `data_amount` VARCHAR(50) DEFAULT NULL COMMENT '데이터량',
    `data_amount_value` VARCHAR(20) DEFAULT NULL COMMENT '데이터량 값',
    `data_unit` VARCHAR(10) DEFAULT NULL COMMENT '데이터 단위',
    `data_exhausted` VARCHAR(50) DEFAULT NULL COMMENT '데이터 소진 시',
    `data_exhausted_value` VARCHAR(50) DEFAULT NULL COMMENT '데이터 소진 시 값',
    `call_type` VARCHAR(50) DEFAULT NULL COMMENT '통화 타입',
    `call_amount` VARCHAR(20) DEFAULT NULL COMMENT '통화량',
    `additional_call_type` VARCHAR(50) DEFAULT NULL COMMENT '추가 통화 타입',
    `additional_call` VARCHAR(20) DEFAULT NULL COMMENT '추가 통화량',
    `sms_type` VARCHAR(50) DEFAULT NULL COMMENT 'SMS 타입',
    `sms_amount` VARCHAR(20) DEFAULT NULL COMMENT 'SMS량',
    `mobile_hotspot` VARCHAR(50) DEFAULT NULL COMMENT '모바일 핫스팟',
    `mobile_hotspot_value` VARCHAR(20) DEFAULT NULL COMMENT '모바일 핫스팟 값',
    `regular_sim_available` VARCHAR(10) DEFAULT NULL COMMENT '일반 SIM 가능 여부',
    `regular_sim_price` VARCHAR(20) DEFAULT NULL COMMENT '일반 SIM 가격',
    `nfc_sim_available` VARCHAR(10) DEFAULT NULL COMMENT 'NFC SIM 가능 여부',
    `nfc_sim_price` VARCHAR(20) DEFAULT NULL COMMENT 'NFC SIM 가격',
    `esim_available` VARCHAR(10) DEFAULT NULL COMMENT 'eSIM 가능 여부',
    `esim_price` VARCHAR(20) DEFAULT NULL COMMENT 'eSIM 가격',
    `over_data_price` VARCHAR(20) DEFAULT NULL COMMENT '데이터 초과 시 가격',
    `over_voice_price` VARCHAR(20) DEFAULT NULL COMMENT '음성 초과 시 가격',
    `over_video_price` VARCHAR(20) DEFAULT NULL COMMENT '영상통화 초과 시 가격',
    `over_sms_price` VARCHAR(20) DEFAULT NULL COMMENT 'SMS 초과 시 가격',
    `over_lms_price` VARCHAR(20) DEFAULT NULL COMMENT 'LMS 초과 시 가격',
    `over_mms_price` VARCHAR(20) DEFAULT NULL COMMENT 'MMS 초과 시 가격',
    `promotion_title` VARCHAR(200) DEFAULT NULL COMMENT '프로모션 제목',
    `promotions` TEXT DEFAULT NULL COMMENT '프로모션 목록 (JSON)',
    `benefits` TEXT DEFAULT NULL COMMENT '혜택 목록 (JSON)',
    `delivery_method` VARCHAR(20) DEFAULT 'delivery' COMMENT '배송 방법',
    `visit_region` VARCHAR(50) DEFAULT NULL COMMENT '방문 지역',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_product_id` (`product_id`),
    KEY `idx_device_name` (`device_name`),
    CONSTRAINT `fk_mno_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='MNO 상품 상세 정보';

-- ============================================
-- 4. Internet 상품 상세 테이블 (product_internet_details)
-- ============================================
CREATE TABLE IF NOT EXISTS `product_internet_details` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` INT(11) UNSIGNED NOT NULL COMMENT '상품 ID',
    `registration_place` VARCHAR(50) NOT NULL COMMENT '인터넷가입처',
    `speed_option` VARCHAR(20) DEFAULT NULL COMMENT '가입속도',
    `monthly_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '월 요금제',
    `cash_payment_names` TEXT DEFAULT NULL COMMENT '현금지급 항목명 (JSON)',
    `cash_payment_prices` TEXT DEFAULT NULL COMMENT '현금지급 가격 (JSON)',
    `gift_card_names` TEXT DEFAULT NULL COMMENT '상품권 지급 항목명 (JSON)',
    `gift_card_prices` TEXT DEFAULT NULL COMMENT '상품권 지급 가격 (JSON)',
    `equipment_names` TEXT DEFAULT NULL COMMENT '장비 제공 항목명 (JSON)',
    `equipment_prices` TEXT DEFAULT NULL COMMENT '장비 제공 가격 (JSON)',
    `installation_names` TEXT DEFAULT NULL COMMENT '설치 및 기타 서비스 항목명 (JSON)',
    `installation_prices` TEXT DEFAULT NULL COMMENT '설치 및 기타 서비스 가격 (JSON)',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_product_id` (`product_id`),
    KEY `idx_registration_place` (`registration_place`),
    KEY `idx_speed_option` (`speed_option`),
    CONSTRAINT `fk_internet_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Internet 상품 상세 정보';

-- ============================================
-- 5. 상품 리뷰 테이블 (product_reviews)
-- MVNO, MNO만 사용 (Internet 제외)
-- ============================================
CREATE TABLE IF NOT EXISTS `product_reviews` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` INT(11) UNSIGNED NOT NULL COMMENT '상품 ID',
    `user_id` VARCHAR(50) NOT NULL COMMENT '작성자 user_id (users.user_id)',
    `product_type` ENUM('mvno', 'mno') NOT NULL COMMENT '상품 타입',
    `rating` TINYINT(1) UNSIGNED NOT NULL COMMENT '평점 (1-5)',
    `title` VARCHAR(200) DEFAULT NULL COMMENT '리뷰 제목',
    `content` TEXT NOT NULL COMMENT '리뷰 내용',
    `is_verified` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '구매 인증 여부',
    `helpful_count` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '도움됨 수',
    `status` ENUM('pending', 'approved', 'rejected', 'deleted') NOT NULL DEFAULT 'pending' COMMENT '리뷰 상태',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '작성일시',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    PRIMARY KEY (`id`),
    KEY `idx_product_id` (`product_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_product_type` (`product_type`),
    KEY `idx_status` (`status`),
    KEY `idx_created_at` (`created_at`),
    CONSTRAINT `fk_review_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='상품 리뷰 (MVNO, MNO만)';

-- ============================================
-- 6. 상품 찜 테이블 (product_favorites)
-- ============================================
CREATE TABLE IF NOT EXISTS `product_favorites` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` INT(11) UNSIGNED NOT NULL COMMENT '상품 ID',
    `user_id` VARCHAR(50) NOT NULL COMMENT '사용자 user_id (users.user_id)',
    `product_type` ENUM('mvno', 'mno', 'internet') NOT NULL COMMENT '상품 타입',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '찜한 일시',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_product_user` (`product_id`, `user_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_product_type` (`product_type`),
    KEY `idx_created_at` (`created_at`),
    CONSTRAINT `fk_favorite_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='상품 찜';

-- ============================================
-- 7. 상품 공유 테이블 (product_shares)
-- ============================================
CREATE TABLE IF NOT EXISTS `product_shares` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` INT(11) UNSIGNED NOT NULL COMMENT '상품 ID',
    `user_id` VARCHAR(50) DEFAULT NULL COMMENT '공유한 사용자 user_id (비회원 가능)',
    `product_type` ENUM('mvno', 'mno', 'internet') NOT NULL COMMENT '상품 타입',
    `share_method` VARCHAR(20) NOT NULL COMMENT '공유 방법 (kakao, facebook, twitter, link, etc.)',
    `ip_address` VARCHAR(45) DEFAULT NULL COMMENT 'IP 주소',
    `user_agent` VARCHAR(255) DEFAULT NULL COMMENT 'User Agent',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '공유 일시',
    PRIMARY KEY (`id`),
    KEY `idx_product_id` (`product_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_product_type` (`product_type`),
    KEY `idx_share_method` (`share_method`),
    KEY `idx_created_at` (`created_at`),
    CONSTRAINT `fk_share_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='상품 공유';

-- ============================================
-- 8. 상품 신청 테이블 (product_applications)
-- ============================================
CREATE TABLE IF NOT EXISTS `product_applications` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` INT(11) UNSIGNED NOT NULL COMMENT '상품 ID',
    `seller_id` INT(11) UNSIGNED NOT NULL COMMENT '판매자 ID',
    `product_type` ENUM('mvno', 'mno', 'internet') NOT NULL COMMENT '상품 타입',
    `user_id` VARCHAR(50) DEFAULT NULL COMMENT '신청자 user_id (users.user_id)',
    `application_status` ENUM('pending', 'processing', 'completed', 'cancelled', 'rejected', 'closed') NOT NULL DEFAULT 'pending' COMMENT '신청 상태',
    `status_changed_at` DATETIME DEFAULT NULL COMMENT '상태 변경일시',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '신청일시',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    PRIMARY KEY (`id`),
    KEY `idx_product_id` (`product_id`),
    KEY `idx_seller_id` (`seller_id`),
    KEY `idx_product_type` (`product_type`),
    KEY `idx_application_status` (`application_status`),
    KEY `idx_created_at` (`created_at`),
    CONSTRAINT `fk_application_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='상품 신청';

-- ============================================
-- 9. 신청 고객 정보 테이블 (application_customers)
-- ============================================
CREATE TABLE IF NOT EXISTS `application_customers` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `application_id` INT(11) UNSIGNED NOT NULL COMMENT '신청 ID',
    `user_id` VARCHAR(50) DEFAULT NULL COMMENT '회원 user_id (비회원 신청 가능)',
    `name` VARCHAR(50) NOT NULL COMMENT '고객명',
    `phone` VARCHAR(20) NOT NULL COMMENT '전화번호',
    `email` VARCHAR(100) DEFAULT NULL COMMENT '이메일',
    `address` VARCHAR(255) DEFAULT NULL COMMENT '주소',
    `address_detail` VARCHAR(255) DEFAULT NULL COMMENT '상세주소',
    `birth_date` DATE DEFAULT NULL COMMENT '생년월일',
    `gender` ENUM('male', 'female', 'other') DEFAULT NULL COMMENT '성별',
    `additional_info` TEXT DEFAULT NULL COMMENT '추가 정보 (JSON)',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '등록일시',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    PRIMARY KEY (`id`),
    KEY `idx_application_id` (`application_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_phone` (`phone`),
    KEY `idx_created_at` (`created_at`),
    CONSTRAINT `fk_customer_application` FOREIGN KEY (`application_id`) REFERENCES `product_applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='신청 고객 정보';

-- ============================================
-- 10. 인덱스 최적화를 위한 추가 인덱스
-- ============================================

-- 상품 조회 최적화 (타입별, 상태별)
ALTER TABLE `products` ADD INDEX `idx_type_status` (`product_type`, `status`);

-- 리뷰 평점 조회 최적화
ALTER TABLE `product_reviews` ADD INDEX `idx_product_rating` (`product_id`, `rating`);

-- 찜 목록 조회 최적화
ALTER TABLE `product_favorites` ADD INDEX `idx_user_type` (`user_id`, `product_type`);

-- 신청 목록 조회 최적화
ALTER TABLE `product_applications` ADD INDEX `idx_seller_status` (`seller_id`, `application_status`);

-- ============================================
-- 11. 트리거: 리뷰 추가 시 review_count 업데이트
-- ============================================
DELIMITER $$

CREATE TRIGGER `trg_review_insert` AFTER INSERT ON `product_reviews`
FOR EACH ROW
BEGIN
    UPDATE `products` 
    SET `review_count` = `review_count` + 1 
    WHERE `id` = NEW.product_id;
END$$

CREATE TRIGGER `trg_review_delete` AFTER DELETE ON `product_reviews`
FOR EACH ROW
BEGIN
    UPDATE `products` 
    SET `review_count` = GREATEST(`review_count` - 1, 0)
    WHERE `id` = OLD.product_id;
END$$

DELIMITER ;

-- ============================================
-- 12. 트리거: 찜 추가/삭제 시 favorite_count 업데이트
-- ============================================
DELIMITER $$

CREATE TRIGGER `trg_favorite_insert` AFTER INSERT ON `product_favorites`
FOR EACH ROW
BEGIN
    UPDATE `products` 
    SET `favorite_count` = `favorite_count` + 1 
    WHERE `id` = NEW.product_id;
END$$

CREATE TRIGGER `trg_favorite_delete` AFTER DELETE ON `product_favorites`
FOR EACH ROW
BEGIN
    UPDATE `products` 
    SET `favorite_count` = GREATEST(`favorite_count` - 1, 0)
    WHERE `id` = OLD.product_id;
END$$

DELIMITER ;

-- ============================================
-- 13. 트리거: 공유 추가 시 share_count 업데이트
-- ============================================
DELIMITER $$

CREATE TRIGGER `trg_share_insert` AFTER INSERT ON `product_shares`
FOR EACH ROW
BEGIN
    UPDATE `products` 
    SET `share_count` = `share_count` + 1 
    WHERE `id` = NEW.product_id;
END$$

DELIMITER ;

-- ============================================
-- 14. 트리거: 신청 추가/삭제 시 application_count 업데이트
-- ============================================
DELIMITER $$

CREATE TRIGGER `trg_application_insert` AFTER INSERT ON `product_applications`
FOR EACH ROW
BEGIN
    UPDATE `products` 
    SET `application_count` = `application_count` + 1 
    WHERE `id` = NEW.product_id;
END$$

CREATE TRIGGER `trg_application_delete` AFTER DELETE ON `product_applications`
FOR EACH ROW
BEGIN
    UPDATE `products` 
    SET `application_count` = GREATEST(`application_count` - 1, 0)
    WHERE `id` = OLD.product_id;
END$$

DELIMITER ;

-- ============================================
-- 15. 데이터 추가제공 필드 추가 (마이그레이션)
-- ============================================
-- 기존 테이블에 데이터 추가제공 필드 추가
-- 주의: 이미 컬럼이 존재하는 경우 에러가 발생할 수 있으므로, 
-- 먼저 컬럼 존재 여부를 확인한 후 실행하세요.

-- MySQL 5.7 이상에서 사용 가능한 방법:
-- ALTER TABLE `product_mvno_details` 
-- ADD COLUMN IF NOT EXISTS `data_additional` VARCHAR(50) DEFAULT NULL COMMENT '데이터 추가제공' AFTER `data_unit`,
-- ADD COLUMN IF NOT EXISTS `data_additional_value` VARCHAR(50) DEFAULT NULL COMMENT '데이터 추가제공 값' AFTER `data_additional`;

-- MySQL 5.6 이하에서 사용 가능한 방법 (수동 실행):
-- ALTER TABLE `product_mvno_details` 
-- ADD COLUMN `data_additional` VARCHAR(50) DEFAULT NULL COMMENT '데이터 추가제공' AFTER `data_unit`;
-- ALTER TABLE `product_mvno_details` 
-- ADD COLUMN `data_additional_value` VARCHAR(50) DEFAULT NULL COMMENT '데이터 추가제공 값' AFTER `data_additional`;

-- ============================================
-- 완료
-- ============================================

