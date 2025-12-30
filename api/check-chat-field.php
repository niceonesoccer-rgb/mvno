<?php
/**
 * 채팅상담 필드 확인 스크립트
 * 브라우저에서 실행: http://localhost/MVNO/api/check-chat-field.php
 */

require_once __DIR__ . '/../includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>채팅상담 필드 확인</h1>";

$pdo = getDBConnection();
if (!$pdo) {
    die('<p style="color: red;">데이터베이스 연결에 실패했습니다.</p>');
}

// 필드 존재 여부 확인
try {
    $stmt = $pdo->query("
        SELECT COUNT(*) as cnt
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'users' 
        AND COLUMN_NAME = 'chat_consultation_url'
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['cnt'] > 0) {
        echo "<p style='color: green;'>✓ chat_consultation_url 필드가 존재합니다.</p>";
        
        // 판매자들의 채팅상담 URL 확인
        $stmt = $pdo->query("
            SELECT user_id, name, seller_name, chat_consultation_url
            FROM users
            WHERE role = 'seller'
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($sellers) > 0) {
            echo "<h2>판매자 채팅상담 URL 목록 (최근 10명)</h2>";
            echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
            echo "<tr style='background: #f0f0f0;'>";
            echo "<th>아이디</th><th>이름</th><th>판매자명</th><th>채팅상담 URL</th>";
            echo "</tr>";
            
            foreach ($sellers as $seller) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($seller['user_id']) . "</td>";
                echo "<td>" . htmlspecialchars($seller['name'] ?? '-') . "</td>";
                echo "<td>" . htmlspecialchars($seller['seller_name'] ?? '-') . "</td>";
                $chatUrl = $seller['chat_consultation_url'] ?? '';
                if (empty($chatUrl)) {
                    echo "<td style='color: #999;'>미설정</td>";
                } else {
                    echo "<td><a href='" . htmlspecialchars($chatUrl) . "' target='_blank'>" . htmlspecialchars($chatUrl) . "</a></td>";
                }
                echo "</tr>";
            }
            
            echo "</table>";
        }
    } else {
        echo "<p style='color: red;'>✗ chat_consultation_url 필드가 존재하지 않습니다.</p>";
        echo "<p>다음 SQL을 실행하여 필드를 추가하세요:</p>";
        echo "<pre style='background: #f5f5f5; padding: 15px; border-radius: 5px;'>";
        echo "USE mvno_db;\n";
        echo "ALTER TABLE `users` ADD COLUMN `chat_consultation_url` VARCHAR(500) DEFAULT NULL COMMENT '채팅상담 URL (카카오톡 채널, 네이버톡톡 등)';\n";
        echo "</pre>";
        echo "<p>또는 <code>database/add_chat_consultation_field.sql</code> 파일을 실행하세요.</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>오류: " . htmlspecialchars($e->getMessage()) . "</p>";
}









