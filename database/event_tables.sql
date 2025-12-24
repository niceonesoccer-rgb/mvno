-- 이벤트 관련 테이블 생성
USE `mvno_db`;

-- 이벤트 기본 테이블 확장 (기존 events 테이블에 필드 추가)
-- 주의: 이미 컬럼이 존재하면 오류가 발생할 수 있으므로, 필요시 수동으로 확인 후 실행하세요.

-- event_type 컬럼 추가
SET @dbname = DATABASE();
SET @tablename = 'events';
SET @columnname = 'event_type';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' ENUM(\'plan\', \'promotion\', \'card\') NOT NULL DEFAULT \'promotion\' COMMENT \'이벤트 타입 (요금제/프로모션/제휴카드)\' AFTER category')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- main_image 컬럼 추가
SET @columnname = 'main_image';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' VARCHAR(1000) DEFAULT NULL COMMENT \'메인 이미지 (16:9 비율)\' AFTER image_url')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- description 컬럼 추가
SET @columnname = 'description';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' TEXT DEFAULT NULL COMMENT \'이벤트 설명\' AFTER title')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- image_url 컬럼 수정 (이미 존재하는 경우에만)
ALTER TABLE `events` 
MODIFY COLUMN `image_url` VARCHAR(1000) DEFAULT NULL COMMENT '레거시 이미지 URL (호환성 유지)';

-- 이벤트 상세 이미지 테이블
CREATE TABLE IF NOT EXISTS `event_detail_images` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_id` VARCHAR(64) NOT NULL COMMENT '이벤트 ID',
  `image_path` VARCHAR(1000) NOT NULL COMMENT '이미지 경로',
  `display_order` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '표시 순서',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_event_id_order` (`event_id`, `display_order`),
  CONSTRAINT `fk_event_detail_images_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='이벤트 상세 이미지';

-- 이벤트 상품 연결 테이블
CREATE TABLE IF NOT EXISTS `event_products` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_id` VARCHAR(64) NOT NULL COMMENT '이벤트 ID',
  `product_id` INT(11) UNSIGNED NOT NULL COMMENT '상품 ID',
  `display_order` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '표시 순서 (드래그로 변경 가능)',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_event_product` (`event_id`, `product_id`),
  KEY `idx_event_order` (`event_id`, `display_order`),
  KEY `idx_product_id` (`product_id`),
  CONSTRAINT `fk_event_products_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_event_products_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='이벤트 연결 상품';

