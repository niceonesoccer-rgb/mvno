<?php
/**
 * 채팅상담 필드 추가 스크립트
 * 브라우저에서 실행: http://localhost/MVNO/api/add-chat-field.php
 * 한 번만 실행하면 됩니다.
 */

require_once __DIR__ . '/../includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>채팅상담 필드 추가</h1>";

$pdo = getDBConnection();
if (!$pdo) {
    die('<p style="color: red;">데이터베이스 연결에 실패했습니다.</p>');
}

try {
    // 필드 존재 여부 확인
    $checkStmt = $pdo->query("
        SELECT COUNT(*) as cnt
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'users' 
        AND COLUMN_NAME = 'chat_consultation_url'
    ");
    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['cnt'] > 0) {
        echo "<p style='color: green; font-size: 18px;'>✓ chat_consultation_url 필드가 이미 존재합니다.</p>";
        echo "<p><a href='check-chat-field.php'>필드 확인으로 돌아가기</a></p>";
    } else {
        // 필드 추가
        $pdo->exec("
            ALTER TABLE `users` 
            ADD COLUMN `chat_consultation_url` VARCHAR(500) DEFAULT NULL 
            COMMENT '채팅상담 URL (카카오톡 채널, 네이버톡톡 등)'
        ");
        
        echo "<p style='color: green; font-size: 18px;'>✓ chat_consultation_url 필드가 성공적으로 추가되었습니다!</p>";
        echo "<p style='color: blue;'>이제 판매자 정보 수정 페이지에서 채팅상담 URL을 입력하고 저장할 수 있습니다.</p>";
        echo "<p><a href='check-chat-field.php'>필드 확인하기</a></p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red; font-size: 18px;'>✗ 오류가 발생했습니다:</p>";
    echo "<pre style='background: #fee; padding: 15px; border-radius: 5px; color: #c00;'>";
    echo htmlspecialchars($e->getMessage());
    echo "</pre>";
}




