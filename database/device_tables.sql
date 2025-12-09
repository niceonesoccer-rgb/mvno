-- 단말기 관련 테이블 생성
USE `mvno_db`;

-- 제조사 테이블
CREATE TABLE IF NOT EXISTS `device_manufacturers` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL COMMENT '제조사명',
    `name_en` VARCHAR(100) DEFAULT NULL COMMENT '제조사명(영문)',
    `logo_url` VARCHAR(255) DEFAULT NULL COMMENT '로고 이미지 URL',
    `display_order` INT(11) DEFAULT 0 COMMENT '표시 순서',
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active' COMMENT '상태',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_name` (`name`),
    KEY `idx_status` (`status`),
    KEY `idx_display_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='단말기 제조사';

-- 단말기 테이블
CREATE TABLE IF NOT EXISTS `devices` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `manufacturer_id` INT(11) UNSIGNED NOT NULL COMMENT '제조사 ID',
    `name` VARCHAR(200) NOT NULL COMMENT '단말기명',
    `storage` VARCHAR(50) DEFAULT NULL COMMENT '용량 (예: 128GB, 256GB)',
    `release_price` DECIMAL(10,2) DEFAULT NULL COMMENT '출고가',
    `color` TEXT DEFAULT NULL COMMENT '색상 (쉼표로 구분 또는 JSON)',
    `color_values` TEXT DEFAULT NULL COMMENT '색상값 (JSON 형태: [{"name":"블랙","value":"#000000"}]',
    `model_code` VARCHAR(100) DEFAULT NULL COMMENT '모델 코드',
    `release_date` DATE DEFAULT NULL COMMENT '출시일',
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active' COMMENT '상태',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_manufacturer_id` (`manufacturer_id`),
    KEY `idx_status` (`status`),
    KEY `idx_name` (`name`),
    CONSTRAINT `fk_device_manufacturer` FOREIGN KEY (`manufacturer_id`) REFERENCES `device_manufacturers` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='단말기';

-- 기본 제조사 데이터 삽입
INSERT INTO `device_manufacturers` (`name`, `name_en`, `display_order`, `status`) VALUES
('삼성', 'Samsung', 1, 'active'),
('애플', 'Apple', 2, 'active'),
('샤오미', 'Xiaomi', 3, 'active'),
('LG', 'LG', 4, 'active'),
('구글', 'Google', 5, 'active'),
('화웨이', 'Huawei', 6, 'active'),
('OPPO', 'OPPO', 7, 'active'),
('vivo', 'vivo', 8, 'active'),
('원플러스', 'OnePlus', 9, 'active'),
('노키아', 'Nokia', 10, 'active')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

