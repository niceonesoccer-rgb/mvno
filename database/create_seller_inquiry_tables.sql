-- 판매자 1:1 문의 시스템 테이블 생성
USE `mvno_db`;

-- 판매자 1:1 문의 테이블
CREATE TABLE IF NOT EXISTS `seller_inquiries` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `seller_id` VARCHAR(50) NOT NULL COMMENT '판매자 user_id',
    `title` VARCHAR(255) NOT NULL COMMENT '제목',
    `content` TEXT NOT NULL COMMENT '내용',
    `status` ENUM('pending', 'answered', 'closed') NOT NULL DEFAULT 'pending' COMMENT '상태: pending=답변대기, answered=답변완료, closed=확인완료',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '작성일시',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    `answered_at` DATETIME DEFAULT NULL COMMENT '답변 작성 시간',
    `answered_by` VARCHAR(50) DEFAULT NULL COMMENT '답변한 관리자 user_id',
    PRIMARY KEY (`id`),
    KEY `idx_seller_id` (`seller_id`),
    KEY `idx_status` (`status`),
    KEY `idx_created_at` (`created_at`),
    CONSTRAINT `fk_seller_inquiry_seller` FOREIGN KEY (`seller_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='판매자 1:1 문의';

-- 판매자 1:1 문의 답변 테이블
CREATE TABLE IF NOT EXISTS `seller_inquiry_replies` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `inquiry_id` INT(11) UNSIGNED NOT NULL COMMENT '문의 ID',
    `reply_type` ENUM('seller', 'admin') NOT NULL COMMENT '작성자 구분: seller=판매자, admin=관리자',
    `content` TEXT NOT NULL COMMENT '답변 내용',
    `created_by` VARCHAR(50) NOT NULL COMMENT '작성자 user_id',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '작성일시',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    PRIMARY KEY (`id`),
    KEY `idx_inquiry_id` (`inquiry_id`),
    KEY `idx_created_at` (`created_at`),
    CONSTRAINT `fk_seller_inquiry_reply` FOREIGN KEY (`inquiry_id`) REFERENCES `seller_inquiries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='판매자 1:1 문의 답변';

-- 판매자 1:1 문의 첨부파일 테이블
CREATE TABLE IF NOT EXISTS `seller_inquiry_attachments` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `inquiry_id` INT(11) UNSIGNED NOT NULL COMMENT '문의 ID',
    `reply_id` INT(11) UNSIGNED DEFAULT NULL COMMENT '답변 ID (NULL이면 문의에 첨부, 있으면 답변에 첨부)',
    `file_name` VARCHAR(255) NOT NULL COMMENT '원본 파일명',
    `file_path` VARCHAR(500) NOT NULL COMMENT '저장 경로',
    `file_size` INT(11) UNSIGNED NOT NULL COMMENT '파일 크기 (bytes)',
    `file_type` VARCHAR(100) NOT NULL COMMENT 'MIME 타입',
    `uploaded_by` VARCHAR(50) NOT NULL COMMENT '업로드한 사용자 user_id',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '업로드일시',
    PRIMARY KEY (`id`),
    KEY `idx_inquiry_id` (`inquiry_id`),
    KEY `idx_reply_id` (`reply_id`),
    CONSTRAINT `fk_seller_inquiry_attachment_inquiry` FOREIGN KEY (`inquiry_id`) REFERENCES `seller_inquiries` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_seller_inquiry_attachment_reply` FOREIGN KEY (`reply_id`) REFERENCES `seller_inquiry_replies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='판매자 1:1 문의 첨부파일';



