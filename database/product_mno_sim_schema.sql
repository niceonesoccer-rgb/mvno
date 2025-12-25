-- ============================================
-- 통신사유심(MNO-SIM) 상품 상세 테이블
-- 생성일: 2025-01-XX
-- ============================================

USE `mvno_db`;

-- ============================================
-- product_mno_sim_details 테이블 생성
-- ============================================
CREATE TABLE IF NOT EXISTS `product_mno_sim_details` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` INT(11) UNSIGNED NOT NULL COMMENT '상품 ID',
    
    -- 요금제 정보
    `provider` VARCHAR(50) NOT NULL COMMENT '통신사 (KT, SKT, LG U+)',
    `service_type` VARCHAR(50) NOT NULL COMMENT '데이터 속도 (LTE, 5G, 6G)',
    `registration_types` TEXT DEFAULT NULL COMMENT '가입 형태 (JSON: 신규, 번이, 기변)',
    `plan_name` VARCHAR(100) NOT NULL COMMENT '요금제명',
    
    -- 할인방법 (약정기간)
    `contract_period` VARCHAR(50) DEFAULT NULL COMMENT '할인방법 (선택약정할인, 공시지원할인)',
    `contract_period_discount_value` INT(11) DEFAULT NULL COMMENT '약정기간 값',
    `contract_period_discount_unit` VARCHAR(10) DEFAULT NULL COMMENT '약정기간 단위 (개월, 일)',
    
    -- 요금 정보
    `price_main` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '월 요금',
    `price_main_unit` VARCHAR(10) DEFAULT '원' COMMENT '월 요금 단위',
    
    -- 할인기간(프로모션기간)
    `discount_period` VARCHAR(50) DEFAULT NULL COMMENT '할인기간 (프로모션 없음, 직접입력)',
    `discount_period_value` INT(11) DEFAULT NULL COMMENT '할인기간 값',
    `discount_period_unit` VARCHAR(10) DEFAULT NULL COMMENT '할인기간 단위 (개월, 일)',
    
    -- 할인기간요금(프로모션기간요금)
    `price_after_type` VARCHAR(20) DEFAULT NULL COMMENT '할인기간요금 타입 (none, free, custom)',
    `price_after` DECIMAL(10,2) DEFAULT NULL COMMENT '할인기간요금 값 (null=프로모션 없음, 0=공짜, 숫자=직접입력)',
    `price_after_unit` VARCHAR(10) DEFAULT '원' COMMENT '할인기간요금 단위',
    
    -- 요금제 유지기간
    `plan_maintenance_period_type` VARCHAR(20) DEFAULT NULL COMMENT '요금제 유지기간 타입 (무약정, 직접입력)',
    `plan_maintenance_period_prefix` VARCHAR(10) DEFAULT NULL COMMENT '요금제 유지기간 접두사 (M, D)',
    `plan_maintenance_period_value` INT(11) DEFAULT NULL COMMENT '요금제 유지기간 값',
    `plan_maintenance_period_unit` VARCHAR(10) DEFAULT NULL COMMENT '요금제 유지기간 단위 (개월, 일)',
    
    -- 유심기변 불가기간
    `sim_change_restriction_period_type` VARCHAR(20) DEFAULT NULL COMMENT '유심기변 불가기간 타입 (무약정, 직접입력)',
    `sim_change_restriction_period_prefix` VARCHAR(10) DEFAULT NULL COMMENT '유심기변 불가기간 접두사 (M, D)',
    `sim_change_restriction_period_value` INT(11) DEFAULT NULL COMMENT '유심기변 불가기간 값',
    `sim_change_restriction_period_unit` VARCHAR(10) DEFAULT NULL COMMENT '유심기변 불가기간 단위 (개월, 일)',
    
    -- 데이터 정보
    `data_amount` VARCHAR(50) DEFAULT NULL COMMENT '데이터 제공량 (무제한, 직접입력)',
    `data_amount_value` INT(11) DEFAULT NULL COMMENT '데이터 제공량 값',
    `data_unit` VARCHAR(10) DEFAULT NULL COMMENT '데이터 단위 (GB, MB)',
    `data_additional` VARCHAR(50) DEFAULT NULL COMMENT '데이터 추가제공 (없음, 직접입력)',
    `data_additional_value` VARCHAR(50) DEFAULT NULL COMMENT '데이터 추가제공 값',
    `data_exhausted` VARCHAR(50) DEFAULT NULL COMMENT '데이터 소진시',
    `data_exhausted_value` VARCHAR(50) DEFAULT NULL COMMENT '데이터 소진시 값 (직접입력 시)',
    
    -- 통화/문자 정보
    `call_type` VARCHAR(50) DEFAULT NULL COMMENT '통화 타입 (무제한, 기본제공, 직접입력)',
    `call_amount` INT(11) DEFAULT NULL COMMENT '통화량',
    `call_amount_unit` VARCHAR(10) DEFAULT '분' COMMENT '통화량 단위',
    `additional_call_type` VARCHAR(50) DEFAULT NULL COMMENT '부가·영상통화 타입 (무제한, 기본제공, 직접입력)',
    `additional_call` INT(11) DEFAULT NULL COMMENT '부가·영상통화량',
    `additional_call_unit` VARCHAR(10) DEFAULT '분' COMMENT '부가·영상통화량 단위',
    `sms_type` VARCHAR(50) DEFAULT NULL COMMENT '문자 타입 (무제한, 기본제공, 직접입력)',
    `sms_amount` INT(11) DEFAULT NULL COMMENT '문자량',
    `sms_amount_unit` VARCHAR(10) DEFAULT '건' COMMENT '문자량 단위',
    `mobile_hotspot` VARCHAR(50) DEFAULT NULL COMMENT '테더링(핫스팟) (기본 제공량 내에서 사용, 직접입력)',
    `mobile_hotspot_value` INT(11) DEFAULT NULL COMMENT '테더링(핫스팟) 값',
    `mobile_hotspot_unit` VARCHAR(10) DEFAULT NULL COMMENT '테더링(핫스팟) 단위 (GB, TB, MB)',
    
    -- 유심 정보
    `regular_sim_available` VARCHAR(50) DEFAULT NULL COMMENT '일반유심 (배송불가, 유심비 유료, 유심비 무료, 유심·배송비 무료)',
    `regular_sim_price` DECIMAL(10,2) DEFAULT NULL COMMENT '일반유심 가격',
    `regular_sim_price_unit` VARCHAR(10) DEFAULT '원' COMMENT '일반유심 가격 단위',
    `nfc_sim_available` VARCHAR(50) DEFAULT NULL COMMENT 'NFC유심 (배송불가, 유심비 유료, 유심비 무료, 유심·배송비 무료)',
    `nfc_sim_price` DECIMAL(10,2) DEFAULT NULL COMMENT 'NFC유심 가격',
    `nfc_sim_price_unit` VARCHAR(10) DEFAULT '원' COMMENT 'NFC유심 가격 단위',
    `esim_available` VARCHAR(50) DEFAULT NULL COMMENT 'eSIM (개통불가, eSIM 유료, eSIM 무료)',
    `esim_price` DECIMAL(10,2) DEFAULT NULL COMMENT 'eSIM 가격',
    `esim_price_unit` VARCHAR(10) DEFAULT '원' COMMENT 'eSIM 가격 단위',
    
    -- 기본 제공 초과 시
    `over_data_price` DECIMAL(10,2) DEFAULT NULL COMMENT '데이터 초과 시 가격',
    `over_data_price_unit` VARCHAR(20) DEFAULT '원/MB' COMMENT '데이터 초과 시 가격 단위 (원/MB, 원/GB)',
    `over_voice_price` DECIMAL(10,2) DEFAULT NULL COMMENT '음성 초과 시 가격',
    `over_voice_price_unit` VARCHAR(20) DEFAULT '원/초' COMMENT '음성 초과 시 가격 단위 (원/초, 원/분)',
    `over_video_price` DECIMAL(10,2) DEFAULT NULL COMMENT '영상통화 초과 시 가격',
    `over_video_price_unit` VARCHAR(20) DEFAULT '원/초' COMMENT '영상통화 초과 시 가격 단위 (원/초, 원/분)',
    `over_sms_price` INT(11) DEFAULT NULL COMMENT 'SMS 초과 시 가격',
    `over_sms_price_unit` VARCHAR(20) DEFAULT '원/건' COMMENT 'SMS 초과 시 가격 단위',
    `over_lms_price` INT(11) DEFAULT NULL COMMENT 'LMS 초과 시 가격',
    `over_lms_price_unit` VARCHAR(20) DEFAULT '원/건' COMMENT 'LMS 초과 시 가격 단위',
    `over_mms_price` INT(11) DEFAULT NULL COMMENT 'MMS 초과 시 가격',
    `over_mms_price_unit` VARCHAR(20) DEFAULT '원/건' COMMENT 'MMS 초과 시 가격 단위',
    
    -- 혜택
    `promotion_title` VARCHAR(200) DEFAULT NULL COMMENT '프로모션 제목',
    `promotions` TEXT DEFAULT NULL COMMENT '프로모션 목록 (JSON)',
    `benefits` TEXT DEFAULT NULL COMMENT '혜택 목록 (JSON)',
    
    -- 기타
    `redirect_url` VARCHAR(500) DEFAULT NULL COMMENT '신청 후 리다이렉트 URL',
    
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_product_id` (`product_id`),
    KEY `idx_provider` (`provider`),
    KEY `idx_service_type` (`service_type`),
    CONSTRAINT `fk_mno_sim_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='통신사유심(MNO-SIM) 상품 상세 정보';

