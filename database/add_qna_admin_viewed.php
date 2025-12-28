<?php
/**
 * Q&A 테이블에 admin_viewed_at 컬럼 추가
 * 관리자가 Q&A를 조회했는지 추적하기 위한 컬럼
 */

require_once __DIR__ . '/../includes/data/db-config.php';

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        die("데이터베이스 연결 실패\n");
    }
    
    // 컬럼 존재 여부 확인
    $checkStmt = $pdo->query("SHOW COLUMNS FROM qna LIKE 'admin_viewed_at'");
    $columnExists = $checkStmt->fetch() !== false;
    
    if ($columnExists) {
        echo "admin_viewed_at 컬럼이 이미 존재합니다.\n";
    } else {
        // 컬럼 추가
        $pdo->exec("ALTER TABLE qna ADD COLUMN admin_viewed_at DATETIME NULL COMMENT '관리자 조회 일시' AFTER answered_by");
        echo "admin_viewed_at 컬럼이 성공적으로 추가되었습니다.\n";
    }
    
} catch (Exception $e) {
    echo "오류 발생: " . $e->getMessage() . "\n";
}






