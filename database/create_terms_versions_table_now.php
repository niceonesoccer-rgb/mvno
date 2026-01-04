<?php
/**
 * terms_versions 테이블 생성 스크립트
 * 브라우저에서 실행: http://localhost/MVNO/database/create_terms_versions_table_now.php
 */

require_once __DIR__ . '/../includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>terms_versions 테이블 생성</h1>";

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        die("<p style='color: red;'>❌ 데이터베이스 연결 실패</p>");
    }

    echo "<p style='color: green;'>✅ 데이터베이스 연결 성공</p>";

    $sql = "
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
    ";

    $pdo->exec($sql);
    echo "<p style='color: green;'>✅ terms_versions 테이블이 성공적으로 생성되었습니다.</p>";
    
    // 테이블 확인
    $stmt = $pdo->query("SHOW TABLES LIKE 'terms_versions'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ 테이블 생성 확인 완료</p>";
        echo "<p><a href='/MVNO/admin/settings/site-settings.php?type=privacy'>개인정보처리방침 관리 페이지로 이동</a></p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ 에러 발생: " . htmlspecialchars($e->getMessage()) . "</p>";
}
