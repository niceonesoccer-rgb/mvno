<?php
/**
 * Q&A 테이블에 소프트 삭제 기능 추가
 * deleted_at 컬럼 자동 추가
 */

require_once __DIR__ . '/../includes/data/db-config.php';

$pdo = getDBConnection();
if (!$pdo) {
    die('DB 연결 실패');
}

echo "<h1>Q&A 소프트 삭제 기능 추가</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    .success { color: green; padding: 10px; background: #d1fae5; border-left: 4px solid #10b981; margin: 10px 0; }
    .error { color: red; padding: 10px; background: #fee2e2; border-left: 4px solid #ef4444; margin: 10px 0; }
    .info { color: blue; padding: 10px; background: #dbeafe; border-left: 4px solid #3b82f6; margin: 10px 0; }
</style>";

try {
    // deleted_at 컬럼이 이미 있는지 확인
    $stmt = $pdo->query("SHOW COLUMNS FROM qna LIKE 'deleted_at'");
    $columnExists = $stmt->fetch();
    
    if ($columnExists) {
        echo "<div class='info'>";
        echo "<p>✓ deleted_at 컬럼이 이미 존재합니다.</p>";
        echo "</div>";
    } else {
        echo "<div class='info'>";
        echo "<p>deleted_at 컬럼을 추가하는 중...</p>";
        echo "</div>";
        
        // deleted_at 컬럼 추가
        $pdo->exec("ALTER TABLE qna ADD COLUMN deleted_at DATETIME NULL COMMENT '삭제 일시 (소프트 삭제)' AFTER updated_at");
        
        // 인덱스 추가
        try {
            $pdo->exec("ALTER TABLE qna ADD INDEX idx_deleted_at (deleted_at)");
            echo "<div class='success'>";
            echo "<p>✓ 인덱스도 추가되었습니다.</p>";
            echo "</div>";
        } catch (Exception $e) {
            echo "<div class='info'>";
            echo "<p>인덱스 추가 중 오류 (이미 존재할 수 있음): " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
        }
        
        echo "<div class='success'>";
        echo "<p>✓ deleted_at 컬럼이 성공적으로 추가되었습니다!</p>";
        echo "</div>";
    }
    
    // 테이블 구조 확인
    echo "<h2>테이블 구조 확인</h2>";
    $stmt = $pdo->query("DESCRIBE qna");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
    echo "<tr><th>필드명</th><th>타입</th><th>NULL</th><th>기본값</th></tr>";
    foreach ($columns as $col) {
        $highlight = ($col['Field'] === 'deleted_at') ? " style='background-color: #d1fae5;'" : '';
        echo "<tr$highlight>";
        echo "<td><strong>" . htmlspecialchars($col['Field']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div class='success'>";
    echo "<p><strong>완료!</strong> 이제 소프트 삭제 기능을 사용할 수 있습니다.</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<p>오류 발생: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='/MVNO/admin/content/qna-manage.php'>관리자 페이지로</a> | ";
echo "<a href='/MVNO/admin/test-qna-status.php?force=true'>상태 확인</a></p>";
?>






