-- ============================================
-- 시스템 설정 테이블 생성
-- 리뷰 표시 방식 등 시스템 전역 설정 관리
-- ============================================

USE `mvno_db`;

CREATE TABLE IF NOT EXISTS `system_settings` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL COMMENT '설정 키',
    `setting_value` TEXT NOT NULL COMMENT '설정 값',
    `setting_type` ENUM('string', 'number', 'boolean', 'json') NOT NULL DEFAULT 'string',
    `description` VARCHAR(255) DEFAULT NULL COMMENT '설명',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='시스템 설정';

-- 리뷰 표시 방식 설정 초기값 삽입
-- 'product': 상품별 리뷰만 표시
-- 'seller_grouped': 판매자별 통합 리뷰 표시 (현재는 사용 안함)
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('review_display_mode_mvno', 'product', 'string', 'MVNO 리뷰 표시 방식: product(상품별) 또는 seller_grouped(판매자별 통합)'),
('review_display_mode_mno', 'product', 'string', 'MNO 리뷰 표시 방식'),
('review_display_mode_internet', 'product', 'string', '인터넷 리뷰 표시 방식')
ON DUPLICATE KEY UPDATE
    `setting_value` = VALUES(`setting_value`),
    `updated_at` = NOW();

SELECT '시스템 설정 테이블 생성 완료!' as message;






