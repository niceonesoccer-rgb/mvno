-- 약관/개인정보처리방침 버전 관리 테이블 생성
-- 시행일자별 버전 관리 및 5년 경과 시 자동 삭제 지원

USE `mvno_db`;

CREATE TABLE IF NOT EXISTS `terms_versions` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` ENUM('terms_of_service', 'privacy_policy') NOT NULL COMMENT '약관 타입',
  `version` VARCHAR(20) NOT NULL COMMENT '버전 (예: v3.8)',
  `effective_date` DATE NOT NULL COMMENT '시행일자',
  `announcement_date` DATE DEFAULT NULL COMMENT '공고일자',
  `title` VARCHAR(255) NOT NULL COMMENT '제목',
  `content` MEDIUMTEXT NOT NULL COMMENT 'HTML 내용',
  `is_active` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '현재 활성 버전 (1: 활성, 0: 비활성)',
  `created_by` VARCHAR(50) DEFAULT NULL COMMENT '생성자',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_type_version` (`type`, `version`),
  KEY `idx_type_effective_date` (`type`, `effective_date`),
  KEY `idx_type_active` (`type`, `is_active`),
  KEY `idx_effective_date_cleanup` (`effective_date`) COMMENT '5년 경과 삭제용 인덱스'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='약관/개인정보처리방침 버전 관리 (5년 경과 시 자동 삭제)';
