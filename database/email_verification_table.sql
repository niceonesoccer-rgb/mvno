-- 이메일 인증 테이블 생성
USE `mvno_db`;

CREATE TABLE IF NOT EXISTS `email_verifications` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` VARCHAR(50) NOT NULL COMMENT '사용자 ID',
    `email` VARCHAR(100) NOT NULL COMMENT '인증할 이메일 주소',
    `verification_code` VARCHAR(10) NOT NULL COMMENT '인증번호 (6자리)',
    `verification_token` VARCHAR(64) NOT NULL COMMENT '인증 토큰 (링크용)',
    `type` ENUM('email_change', 'password_change') NOT NULL COMMENT '인증 타입',
    `status` ENUM('pending', 'verified', 'expired') NOT NULL DEFAULT 'pending' COMMENT '인증 상태',
    `expires_at` DATETIME NOT NULL COMMENT '만료 시간 (30분)',
    `verified_at` DATETIME DEFAULT NULL COMMENT '인증 완료 시간',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_email` (`email`),
    KEY `idx_verification_code` (`verification_code`),
    KEY `idx_verification_token` (`verification_token`),
    KEY `idx_status` (`status`),
    KEY `idx_expires_at` (`expires_at`),
    KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='이메일 인증 정보';








