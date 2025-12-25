-- ============================================
-- 1:1 Q&A 테이블 생성
-- 생성일: 2025-01-XX
-- ============================================

USE `mvno_db`;

-- Q&A 테이블 생성
CREATE TABLE IF NOT EXISTS `qna` (
    `id` VARCHAR(50) NOT NULL COMMENT 'Q&A ID',
    `user_id` VARCHAR(50) NOT NULL COMMENT '사용자 ID',
    `title` VARCHAR(255) NOT NULL COMMENT '질문 제목',
    `content` TEXT NOT NULL COMMENT '질문 내용',
    `answer` TEXT NULL COMMENT '답변 내용',
    `answered_at` DATETIME NULL COMMENT '답변 일시',
    `answered_by` VARCHAR(50) NULL COMMENT '답변자 ID',
    `status` ENUM('pending', 'answered') NOT NULL DEFAULT 'pending' COMMENT '상태 (대기중, 답변완료)',
    `created_at` DATETIME NOT NULL COMMENT '생성일시',
    `updated_at` DATETIME NOT NULL COMMENT '수정일시',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_status` (`status`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='1:1 Q&A';




