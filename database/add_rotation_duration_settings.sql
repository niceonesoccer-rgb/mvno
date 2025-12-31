-- ============================================
-- 로테이션 시간 설정 테이블 추가
-- ============================================

USE `mvno_db`;

-- 로테이션 시간 설정 테이블 생성
CREATE TABLE IF NOT EXISTS `rotation_duration_settings` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `duration_seconds` INT(11) NOT NULL COMMENT '로테이션 시간(초)',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '활성화 여부',
    `display_order` INT(11) NOT NULL DEFAULT 0 COMMENT '표시 순서',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_duration` (`duration_seconds`),
    KEY `idx_is_active` (`is_active`),
    KEY `idx_display_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='로테이션 시간 설정';

-- 초기 데이터 (예시: 30초)
INSERT INTO `rotation_duration_settings` (`duration_seconds`, `is_active`, `display_order`) 
VALUES (30, 1, 1)
ON DUPLICATE KEY UPDATE `is_active` = 1;
