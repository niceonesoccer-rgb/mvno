-- ============================================
-- 로테이션 광고 시스템 데이터베이스 스키마
-- 생성일: 2025-01-XX
-- ============================================

-- 데이터베이스 선택
USE `mvno_db`;

-- ============================================
-- 1. 광고 가격 설정 테이블
-- 카테고리별, 시간 단위별, 기간별 가격 설정
-- ============================================
CREATE TABLE IF NOT EXISTS `rotation_advertisement_prices` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_type` ENUM('mvno', 'mno', 'internet', 'mno_sim') NOT NULL COMMENT '상품 타입',
    `rotation_duration` INT(11) NOT NULL COMMENT '로테이션 시간(초): 10, 30, 60, 300',
    `advertisement_days` INT(11) NOT NULL COMMENT '광고 기간(일): 1, 2, 3, 5, 7, 10, 14, 30 등',
    `price` DECIMAL(12,2) NOT NULL COMMENT '광고 금액',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '활성화 여부',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_type_duration_days` (`product_type`, `rotation_duration`, `advertisement_days`),
    KEY `idx_product_type` (`product_type`),
    KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='로테이션 광고 가격 설정 (카테고리별, 시간 단위별, 기간별)';

-- ============================================
-- 2. 무통장 입금 계좌 테이블
-- 관리자가 등록한 무통장 입금 계좌 정보
-- ============================================
CREATE TABLE IF NOT EXISTS `bank_accounts` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `bank_name` VARCHAR(50) NOT NULL COMMENT '은행명 (예: 국민은행, 신한은행)',
    `account_number` VARCHAR(50) NOT NULL COMMENT '계좌번호',
    `account_holder` VARCHAR(100) NOT NULL COMMENT '예금주',
    `display_order` INT(11) NOT NULL DEFAULT 0 COMMENT '표시 순서',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '활성화 여부',
    `memo` TEXT DEFAULT NULL COMMENT '메모 (관리자용)',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_is_active` (`is_active`),
    KEY `idx_display_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='무통장 입금 계좌 관리';

-- ============================================
-- 3. 예치금 계좌 테이블
-- 판매자의 예치금 잔액 관리
-- ============================================
CREATE TABLE IF NOT EXISTS `seller_deposit_accounts` (
    `seller_id` VARCHAR(50) NOT NULL COMMENT '판매자 ID',
    `balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '예치금 잔액',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`seller_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='판매자 예치금 계좌';

-- ============================================
-- 4. 예치금 내역 테이블
-- 예치금 충전/차감 내역 기록
-- ============================================
CREATE TABLE IF NOT EXISTS `seller_deposit_ledger` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `seller_id` VARCHAR(50) NOT NULL COMMENT '판매자 ID',
    `transaction_type` ENUM('deposit', 'withdraw', 'refund') NOT NULL COMMENT '거래 유형 (충전, 차감, 환불)',
    `amount` DECIMAL(12,2) NOT NULL COMMENT '금액 (충전: +, 차감: -, 환불: +)',
    `balance_before` DECIMAL(12,2) NOT NULL COMMENT '거래 전 잔액',
    `balance_after` DECIMAL(12,2) NOT NULL COMMENT '거래 후 잔액',
    `deposit_request_id` INT(11) UNSIGNED DEFAULT NULL COMMENT '예치금 충전 신청 ID (deposit_requests.id)',
    `advertisement_id` INT(11) UNSIGNED DEFAULT NULL COMMENT '광고 ID (rotation_advertisements.id, 차감 시)',
    `description` VARCHAR(500) DEFAULT NULL COMMENT '설명',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_seller_id` (`seller_id`),
    KEY `idx_transaction_type` (`transaction_type`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_deposit_request_id` (`deposit_request_id`),
    KEY `idx_advertisement_id` (`advertisement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='판매자 예치금 내역';

-- ============================================
-- 5. 예치금 충전 신청 테이블
-- 판매자가 무통장 입금을 신청한 정보
-- 부가세 10% 포함, 세금계산서 발행 관리 포함
-- ============================================
CREATE TABLE IF NOT EXISTS `deposit_requests` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `seller_id` VARCHAR(50) NOT NULL COMMENT '판매자 ID',
    `bank_account_id` INT(11) UNSIGNED NOT NULL COMMENT '입금할 계좌 ID (bank_accounts.id)',
    `depositor_name` VARCHAR(100) NOT NULL COMMENT '입금자명',
    `amount` DECIMAL(12,2) NOT NULL COMMENT '입금 금액 (부가세 포함)',
    `supply_amount` DECIMAL(12,2) NOT NULL COMMENT '공급가액 (부가세 제외)',
    `tax_amount` DECIMAL(12,2) NOT NULL COMMENT '부가세 (공급가액의 10%)',
    `status` ENUM('pending', 'confirmed', 'unpaid') NOT NULL DEFAULT 'pending' COMMENT '상태 (대기중, 입금, 미입금)',
    `admin_id` VARCHAR(50) DEFAULT NULL COMMENT '처리한 관리자 ID',
    `confirmed_at` DATETIME DEFAULT NULL COMMENT '확인 일시',
    `rejected_reason` TEXT DEFAULT NULL COMMENT '거부 사유',
    `tax_invoice_status` ENUM('unissued', 'issued', 'cancelled') NOT NULL DEFAULT 'unissued' COMMENT '세금계산서 발행 상태 (미발행, 발행, 취소)',
    `tax_invoice_issued_at` DATETIME DEFAULT NULL COMMENT '세금계산서 발행 일시',
    `tax_invoice_issued_by` VARCHAR(50) DEFAULT NULL COMMENT '세금계산서 발행 처리한 관리자 ID',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_seller_id` (`seller_id`),
    KEY `idx_bank_account_id` (`bank_account_id`),
    KEY `idx_status` (`status`),
    KEY `idx_tax_invoice_status` (`tax_invoice_status`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_confirmed_at` (`confirmed_at`),
    CONSTRAINT `fk_deposit_request_bank_account` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='예치금 충전 신청 (무통장 입금)';

-- ============================================
-- 6. 로테이션 광고 테이블
-- 광고 신청 정보
-- ============================================
CREATE TABLE IF NOT EXISTS `rotation_advertisements` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` INT(11) UNSIGNED NOT NULL COMMENT '상품 ID',
    `seller_id` VARCHAR(50) NOT NULL COMMENT '판매자 ID',
    `product_type` ENUM('mvno', 'mno', 'internet', 'mno_sim') NOT NULL COMMENT '상품 타입',
    `rotation_duration` INT(11) NOT NULL COMMENT '로테이션 시간(초): 10, 30, 60, 300',
    `advertisement_days` INT(11) NOT NULL COMMENT '광고 기간(일): 1, 2, 3, 5, 7, 10 등',
    `price` DECIMAL(12,2) NOT NULL COMMENT '광고 금액 (신청 시점 가격)',
    `start_datetime` DATETIME NOT NULL COMMENT '광고 시작 시간 (초 단위)',
    `end_datetime` DATETIME NOT NULL COMMENT '광고 종료 시간 (초 단위)',
    `status` ENUM('active', 'expired', 'cancelled') NOT NULL DEFAULT 'active' COMMENT '광고 상태',
    `display_order` INT(11) NOT NULL DEFAULT 0 COMMENT '로테이션 순서',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_product_id` (`product_id`),
    KEY `idx_seller_id` (`seller_id`),
    KEY `idx_product_type` (`product_type`),
    KEY `idx_status` (`status`),
    KEY `idx_start_end_datetime` (`start_datetime`, `end_datetime`),
    KEY `idx_display_order` (`display_order`),
    CONSTRAINT `fk_rotation_ad_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='로테이션 광고 신청';

-- ============================================
-- 초기 데이터 예시 (선택사항)
-- ============================================

-- 예시: 알뜰폰(MVNO) 광고 가격 설정
-- 30초 단위, 7일 기간 = 50,000원
-- INSERT INTO `rotation_advertisement_prices` (`product_type`, `rotation_duration`, `advertisement_days`, `price`) VALUES
-- ('mvno', 30, 7, 50000);

-- 예시: 무통장 계좌 등록
-- INSERT INTO `bank_accounts` (`bank_name`, `account_number`, `account_holder`, `display_order`) VALUES
-- ('국민은행', '123-456-789012', '홍길동', 1),
-- ('신한은행', '110-123-456789', '홍길동', 2);

-- ============================================
-- 참고사항
-- ============================================

-- 1. 같은 상품에 대해 동시에 여러 개의 광고를 진행할 수 없음
--    - 광고 신청 시 중복 체크 필요:
--      SELECT * FROM rotation_advertisements
--      WHERE product_id = :product_id
--      AND status = 'active'
--      AND end_datetime > NOW()
--    - 활성화된 광고가 있으면 신청 불가
--    - 광고 종료 후 재신청 가능

-- 2. 광고 상태 표시 (판매자 관리 페이지용):
--    - 광고중: status = 'active' AND products.status = 'active' AND end_datetime > NOW()
--    - 광고중지: status = 'active' AND products.status != 'active' AND end_datetime > NOW()
--    - 광고종료: status = 'expired'

-- 3. 프론트엔드 광고 목록 조회 시:
--    - status = 'active' AND products.status = 'active' AND end_datetime > NOW()인 것만 노출

-- 4. 광고 만료 크론잡:
--    - end_datetime < NOW()인 광고를 status = 'expired'로 변경

-- ============================================
-- 7. 세금계산서 발행 내역 테이블
-- 세금계산서 발행 정보 관리
-- ============================================
CREATE TABLE IF NOT EXISTS `tax_invoices` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `invoice_number` VARCHAR(50) NOT NULL COMMENT '세금계산서 번호',
    `seller_id` VARCHAR(50) NOT NULL COMMENT '판매자 ID (입금 업체)',
    `period_start` DATE NOT NULL COMMENT '발행 기간 시작일',
    `period_end` DATE NOT NULL COMMENT '발행 기간 종료일',
    `total_supply_amount` DECIMAL(12,2) NOT NULL COMMENT '총 공급가액',
    `total_tax_amount` DECIMAL(12,2) NOT NULL COMMENT '총 부가세',
    `total_amount` DECIMAL(12,2) NOT NULL COMMENT '총 합계금액 (공급가액 + 부가세)',
    `deposit_request_ids` TEXT NOT NULL COMMENT '포함된 입금 신청 ID 목록 (JSON 배열)',
    `status` ENUM('issued', 'unissued', 'cancelled') NOT NULL DEFAULT 'unissued' COMMENT '상태 (발행, 미발행, 취소)',
    `issued_at` DATETIME NOT NULL COMMENT '발행 일시',
    `issued_by` VARCHAR(50) NOT NULL COMMENT '발행 처리한 관리자 ID',
    `cancelled_at` DATETIME DEFAULT NULL COMMENT '취소 일시',
    `cancelled_by` VARCHAR(50) DEFAULT NULL COMMENT '취소 처리한 관리자 ID',
    `memo` TEXT DEFAULT NULL COMMENT '메모',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_invoice_number` (`invoice_number`),
    KEY `idx_seller_id` (`seller_id`),
    KEY `idx_period` (`period_start`, `period_end`),
    KEY `idx_status` (`status`),
    KEY `idx_issued_at` (`issued_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='세금계산서 발행 내역';

-- ============================================
-- 완료
-- ============================================