-- 사용자 테이블 생성
USE `mvno_db`;

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` VARCHAR(50) NOT NULL COMMENT '사용자 아이디',
    `email` VARCHAR(100) DEFAULT NULL COMMENT '이메일',
    `name` VARCHAR(50) NOT NULL COMMENT '이름',
    `password` VARCHAR(255) DEFAULT NULL COMMENT '비밀번호 해시 (직접 가입 시)',
    `phone` VARCHAR(20) DEFAULT NULL COMMENT '전화번호',
    `mobile` VARCHAR(20) DEFAULT NULL COMMENT '휴대폰번호',
    `role` ENUM('user', 'admin', 'sub_admin', 'seller') NOT NULL DEFAULT 'user' COMMENT '역할',
    `sns_provider` VARCHAR(20) DEFAULT NULL COMMENT 'SNS 제공자 (naver, kakao, google)',
    `sns_id` VARCHAR(100) DEFAULT NULL COMMENT 'SNS ID',
    `seller_approved` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '판매자 승인 여부',
    `approval_status` ENUM('pending', 'approved', 'on_hold', 'rejected', 'withdrawal_requested', 'withdrawn') DEFAULT NULL COMMENT '승인 상태 (판매자)',
    `approved_at` DATETIME DEFAULT NULL COMMENT '승인일',
    `held_at` DATETIME DEFAULT NULL COMMENT '승인보류일',
    `withdrawal_requested` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '탈퇴 요청 여부',
    `withdrawal_requested_at` DATETIME DEFAULT NULL COMMENT '탈퇴 요청일',
    `withdrawal_reason` TEXT DEFAULT NULL COMMENT '탈퇴 사유',
    `withdrawal_completed` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '탈퇴 완료 여부',
    `withdrawal_completed_at` DATETIME DEFAULT NULL COMMENT '탈퇴 완료일',
    `scheduled_delete_date` DATE DEFAULT NULL COMMENT '삭제 예정일',
    `scheduled_delete_processed` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '삭제 처리 완료 여부',
    `scheduled_delete_processed_at` DATETIME DEFAULT NULL COMMENT '삭제 처리 완료일',
    `postal_code` VARCHAR(10) DEFAULT NULL COMMENT '우편번호',
    `address` VARCHAR(200) DEFAULT NULL COMMENT '주소',
    `address_detail` VARCHAR(200) DEFAULT NULL COMMENT '상세주소',
    `business_number` VARCHAR(50) DEFAULT NULL COMMENT '사업자등록번호',
    `company_name` VARCHAR(100) DEFAULT NULL COMMENT '회사명',
    `company_representative` VARCHAR(50) DEFAULT NULL COMMENT '대표자명',
    `business_type` VARCHAR(100) DEFAULT NULL COMMENT '업종',
    `business_item` VARCHAR(100) DEFAULT NULL COMMENT '업태',
    `business_license_image` VARCHAR(500) DEFAULT NULL COMMENT '사업자등록증 이미지 경로',
    `permissions` JSON DEFAULT NULL COMMENT '판매자 권한 (mvno, mno, internet)',
    `permissions_updated_at` DATETIME DEFAULT NULL COMMENT '권한 업데이트일',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '가입일',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '정보수정일',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_id` (`user_id`),
    UNIQUE KEY `uk_email` (`email`),
    KEY `idx_role` (`role`),
    KEY `idx_seller_approved` (`seller_approved`),
    KEY `idx_approval_status` (`approval_status`),
    KEY `idx_sns_provider_sns_id` (`sns_provider`, `sns_id`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='사용자 테이블';























