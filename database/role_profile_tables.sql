-- 역할별 프로필 테이블 추가 (A안: users + 역할별 프로필)
USE `mvno_db`;

-- 판매자 전용 정보
CREATE TABLE IF NOT EXISTS `seller_profiles` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` VARCHAR(50) NOT NULL COMMENT 'users.user_id (FK)',

  `seller_approved` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '판매자 승인 여부',
  `approval_status` ENUM('pending', 'approved', 'on_hold', 'rejected', 'withdrawal_requested', 'withdrawn') NOT NULL DEFAULT 'pending' COMMENT '승인 상태',
  `approved_at` DATETIME DEFAULT NULL,
  `held_at` DATETIME DEFAULT NULL,

  `withdrawal_requested` TINYINT(1) NOT NULL DEFAULT 0,
  `withdrawal_requested_at` DATETIME DEFAULT NULL,
  `withdrawal_reason` TEXT DEFAULT NULL,
  `withdrawal_completed` TINYINT(1) NOT NULL DEFAULT 0,
  `withdrawal_completed_at` DATETIME DEFAULT NULL,
  `scheduled_delete_date` DATE DEFAULT NULL,
  `scheduled_delete_processed` TINYINT(1) NOT NULL DEFAULT 0,
  `scheduled_delete_processed_at` DATETIME DEFAULT NULL,

  `postal_code` VARCHAR(10) DEFAULT NULL,
  `address` VARCHAR(200) DEFAULT NULL,
  `address_detail` VARCHAR(200) DEFAULT NULL,
  `business_number` VARCHAR(50) DEFAULT NULL,
  `company_name` VARCHAR(100) DEFAULT NULL,
  `company_representative` VARCHAR(50) DEFAULT NULL,
  `business_type` VARCHAR(100) DEFAULT NULL,
  `business_item` VARCHAR(100) DEFAULT NULL,
  `business_license_image` VARCHAR(500) DEFAULT NULL,

  `permissions` JSON DEFAULT NULL COMMENT '판매자 권한 (mvno, mno, internet)',
  `permissions_updated_at` DATETIME DEFAULT NULL,

  `info_checked_by_admin` TINYINT(1) NOT NULL DEFAULT 0,
  `info_checked_at` DATETIME DEFAULT NULL,

  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_seller_profiles_user_id` (`user_id`),
  CONSTRAINT `fk_seller_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='판매자 프로필';

-- 관리자 전용 정보
CREATE TABLE IF NOT EXISTS `admin_profiles` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` VARCHAR(50) NOT NULL COMMENT 'users.user_id (FK)',
  `created_by` VARCHAR(50) DEFAULT NULL COMMENT '생성한 관리자 user_id',
  `memo` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_admin_profiles_user_id` (`user_id`),
  CONSTRAINT `fk_admin_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='관리자 프로필';


