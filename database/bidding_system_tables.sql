-- ============================================
-- 입찰 시스템 데이터베이스 테이블
-- 생성일: 2025-01-XX
-- ============================================

-- 데이터베이스 선택
USE `mvno_db`;

-- ============================================
-- 1. 입찰 라운드 테이블 (bidding_rounds)
-- ============================================
CREATE TABLE IF NOT EXISTS `bidding_rounds` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `category` ENUM('mno', 'mvno', 'mno_sim') NOT NULL COMMENT '카테고리',
    `bidding_start_at` DATETIME NOT NULL COMMENT '입찰 시작일시',
    `bidding_end_at` DATETIME NOT NULL COMMENT '입찰 종료일시',
    `display_start_at` DATETIME NOT NULL COMMENT '게시 시작일시',
    `display_end_at` DATETIME NOT NULL COMMENT '게시 종료일시',
    `max_display_count` INT(11) UNSIGNED NOT NULL DEFAULT 20 COMMENT '최대 노출 개수',
    `min_bid_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '최소 입찰 금액',
    `max_bid_amount` DECIMAL(12,2) NOT NULL DEFAULT 100000.00 COMMENT '최대 입찰 금액',
    `rotation_type` ENUM('fixed', 'rotating') NOT NULL DEFAULT 'fixed' COMMENT '운용 방식',
    `rotation_interval_minutes` INT(11) UNSIGNED DEFAULT NULL COMMENT '순환 간격 (분)',
    `status` ENUM('upcoming', 'bidding', 'closed', 'displaying', 'finished') NOT NULL DEFAULT 'upcoming' COMMENT '입찰 상태',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` VARCHAR(50) DEFAULT NULL COMMENT '생성자 user_id',
    PRIMARY KEY (`id`),
    KEY `idx_category` (`category`),
    KEY `idx_status` (`status`),
    KEY `idx_bidding_period` (`bidding_start_at`, `bidding_end_at`),
    KEY `idx_display_period` (`display_start_at`, `display_end_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='입찰 라운드';

-- ============================================
-- 2. 입찰 참여 테이블 (bidding_participations)
-- ============================================
CREATE TABLE IF NOT EXISTS `bidding_participations` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `bidding_round_id` INT(11) UNSIGNED NOT NULL COMMENT '입찰 라운드 ID',
    `seller_id` VARCHAR(50) NOT NULL COMMENT '판매자 user_id',
    `bid_amount` DECIMAL(12,2) NOT NULL COMMENT '입찰 금액',
    `status` ENUM('pending', 'won', 'lost', 'cancelled') NOT NULL DEFAULT 'pending' COMMENT '입찰 상태',
    `rank` INT(11) UNSIGNED DEFAULT NULL COMMENT '낙찰 순위 (NULL=미낙찰, 낙찰 시 1~20)',
    `deposit_used` DECIMAL(12,2) NOT NULL COMMENT '사용된 예치금',
    `deposit_refunded` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '환불된 예치금',
    `bid_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '입찰 시간',
    `cancelled_at` DATETIME DEFAULT NULL COMMENT '취소 시간',
    `won_at` DATETIME DEFAULT NULL COMMENT '낙찰 확정 시간',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_round_seller` (`bidding_round_id`, `seller_id`),
    KEY `idx_bidding_round_id` (`bidding_round_id`),
    KEY `idx_seller_id` (`seller_id`),
    KEY `idx_status` (`status`),
    KEY `idx_bid_amount` (`bid_amount`),
    KEY `idx_rank` (`rank`),
    KEY `idx_bid_at` (`bid_at`),
    CONSTRAINT `fk_bidding_participation_round` FOREIGN KEY (`bidding_round_id`) REFERENCES `bidding_rounds` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='입찰 참여';

-- ============================================
-- 3. 낙찰자 게시물 배정 테이블 (bidding_product_assignments)
-- ============================================
CREATE TABLE IF NOT EXISTS `bidding_product_assignments` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `bidding_round_id` INT(11) UNSIGNED NOT NULL COMMENT '입찰 라운드 ID',
    `bidding_participation_id` INT(11) UNSIGNED NOT NULL COMMENT '입찰 참여 ID',
    `product_id` INT(11) UNSIGNED NOT NULL COMMENT '게시물(상품) ID',
    `display_order` INT(11) UNSIGNED NOT NULL COMMENT '노출 순서 (1~20)',
    `bid_amount` DECIMAL(12,2) NOT NULL COMMENT '입찰 금액 (참고용)',
    `assigned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '배정 시간',
    `last_rotated_at` DATETIME DEFAULT NULL COMMENT '마지막 순환 시간 (순환 모드일 때)',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_round_order` (`bidding_round_id`, `display_order`),
    KEY `idx_bidding_round_id` (`bidding_round_id`),
    KEY `idx_bidding_participation_id` (`bidding_participation_id`),
    KEY `idx_product_id` (`product_id`),
    KEY `idx_display_order` (`display_order`),
    CONSTRAINT `fk_bidding_assignment_round` FOREIGN KEY (`bidding_round_id`) REFERENCES `bidding_rounds` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_bidding_assignment_participation` FOREIGN KEY (`bidding_participation_id`) REFERENCES `bidding_participations` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_bidding_assignment_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='낙찰자 게시물 배정';

-- ============================================
-- 4. 판매자 예치금 테이블 (seller_deposits)
-- ============================================
CREATE TABLE IF NOT EXISTS `seller_deposits` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `seller_id` VARCHAR(50) NOT NULL COMMENT '판매자 user_id',
    `balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '예치금 잔액',
    `bank_name` VARCHAR(100) DEFAULT NULL COMMENT '환불 계좌 은행명',
    `account_number` VARCHAR(50) DEFAULT NULL COMMENT '환불 계좌 번호',
    `account_holder` VARCHAR(100) DEFAULT NULL COMMENT '환불 계좌 예금주',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_seller_id` (`seller_id`),
    KEY `idx_balance` (`balance`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='판매자 예치금 계정';

-- ============================================
-- 5. 예치금 거래 내역 테이블 (seller_deposit_transactions)
-- ============================================
CREATE TABLE IF NOT EXISTS `seller_deposit_transactions` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `seller_id` VARCHAR(50) NOT NULL COMMENT '판매자 user_id',
    `transaction_type` ENUM('deposit', 'bid', 'refund', 'withdrawal') NOT NULL COMMENT '거래 유형',
    `amount` DECIMAL(12,2) NOT NULL COMMENT '금액',
    `balance_before` DECIMAL(12,2) NOT NULL COMMENT '거래 전 잔액',
    `balance_after` DECIMAL(12,2) NOT NULL COMMENT '거래 후 잔액',
    `reference_id` INT(11) UNSIGNED DEFAULT NULL COMMENT '참조 ID (bidding_participation_id 등)',
    `reference_type` VARCHAR(50) DEFAULT NULL COMMENT '참조 타입 (bidding_participation 등)',
    `description` TEXT DEFAULT NULL COMMENT '설명',
    `processed_by` VARCHAR(50) DEFAULT NULL COMMENT '처리자 user_id (관리자)',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_seller_id` (`seller_id`),
    KEY `idx_transaction_type` (`transaction_type`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_reference` (`reference_type`, `reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='예치금 거래 내역';

