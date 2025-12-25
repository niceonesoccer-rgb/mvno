<?php
/**
 * 통신사유심 찜 기능 마이그레이션 실행 스크립트
 * product_favorites 테이블에 mno-sim 타입 추가
 */

require_once __DIR__ . '/includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>마이그레이션 실행</title>";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;} .success{color:green;font-weight:bold;} .error{color:red;font-weight:bold;} .info{color:blue;} pre{background:#f5f5f5;padding:10px;border:1px solid #ddd;overflow-x:auto;}</style>";
echo "</head><body>";

echo "<h1>통신사유심 찜 기능 마이그레이션</h1>";

$pdo = getDBConnection();
if (!$pdo) {
    echo "<p class='error'>데이터베이스 연결 실패</p>";
    exit;
}

// 현재 상태 확인
echo "<h2>마이그레이션 전 상태</h2>";
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM product_favorites WHERE Field = 'product_type'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($column) {
        echo "<p><strong>현재 타입:</strong> " . htmlspecialchars($column['Type']) . "</p>";
        
        if (stripos($column['Type'], 'mno-sim') !== false) {
            echo "<p class='success'>✓ mno-sim 타입이 이미 지원됩니다. 마이그레이션이 필요하지 않습니다.</p>";
            echo "<p><a href='/MVNO/check-db-field.php'>필드 확인 페이지로 돌아가기</a></p>";
            exit;
        }
    }
} catch (PDOException $e) {
    echo "<p class='error'>에러: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// 마이그레이션 실행
echo "<h2>마이그레이션 실행</h2>";

// 확인 메시지
if (!isset($_POST['confirm'])) {
    echo "<p class='info'>다음 SQL을 실행합니다:</p>";
    echo "<pre>ALTER TABLE `product_favorites` 
MODIFY COLUMN `product_type` ENUM('mvno', 'mno', 'internet', 'mno-sim') NOT NULL COMMENT '상품 타입';</pre>";
    
    echo "<form method='POST'>";
    echo "<p><button type='submit' name='confirm' value='1' style='padding:10px 20px;background:#4CAF50;color:white;border:none;border-radius:4px;cursor:pointer;font-size:16px;'>마이그레이션 실행</button></p>";
    echo "</form>";
    echo "<p><a href='/MVNO/check-db-field.php'>취소하고 필드 확인 페이지로 돌아가기</a></p>";
    exit;
}

// 마이그레이션 실행
try {
    // ALTER TABLE은 DDL이므로 트랜잭션을 사용하지 않음
    $sql = "ALTER TABLE `product_favorites` 
            MODIFY COLUMN `product_type` ENUM('mvno', 'mno', 'internet', 'mno-sim') NOT NULL COMMENT '상품 타입'";
    
    $pdo->exec($sql);
    
    echo "<p class='success'>✓ 마이그레이션이 성공적으로 완료되었습니다!</p>";
    
    // 마이그레이션 후 상태 확인
    echo "<h2>마이그레이션 후 상태</h2>";
    $stmt = $pdo->query("SHOW COLUMNS FROM product_favorites WHERE Field = 'product_type'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($column) {
        echo "<p><strong>새로운 타입:</strong> " . htmlspecialchars($column['Type']) . "</p>";
        
        if (stripos($column['Type'], 'mno-sim') !== false) {
            echo "<p class='success'>✓ mno-sim 타입이 성공적으로 추가되었습니다!</p>";
        } else {
            echo "<p class='error'>✗ mno-sim 타입이 추가되지 않았습니다. 다시 확인해주세요.</p>";
        }
    }
    
    // ENUM 값 확인
    if (preg_match("/ENUM\s*\((.*)\)/i", $column['Type'], $matches)) {
        $enumValues = $matches[1];
        echo "<p><strong>ENUM 값:</strong> " . htmlspecialchars($enumValues) . "</p>";
        
        preg_match_all("/'([^']+)'/", $enumValues, $valueMatches);
        $values = $valueMatches[1];
        
        echo "<p><strong>지원되는 타입:</strong></p>";
        echo "<ul>";
        foreach ($values as $value) {
            $isMnoSim = ($value === 'mno-sim');
            $class = $isMnoSim ? 'success' : '';
            $mark = $isMnoSim ? ' ✓ (새로 추가됨)' : '';
            echo "<li class='{$class}'><strong>" . htmlspecialchars($value) . "</strong>{$mark}</li>";
        }
        echo "</ul>";
    }
    
    echo "<hr>";
    echo "<p><a href='/MVNO/check-db-field.php'>필드 확인 페이지로 이동</a></p>";
    echo "<p><a href='/MVNO/mypage/wishlist.php?type=mno-sim'>찜한 통신사유심 내역 페이지로 이동</a></p>";
    
} catch (PDOException $e) {
    echo "<p class='error'>✗ 마이그레이션 실패: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p class='error'>에러 코드: " . $e->getCode() . "</p>";
    
    if ($e->errorInfo) {
        echo "<p><strong>에러 상세 정보:</strong></p>";
        echo "<pre>" . print_r($e->errorInfo, true) . "</pre>";
    }
    
    // 일반적인 에러 해결 방법 제시
    echo "<h3>문제 해결 방법</h3>";
    echo "<p>다음 방법을 시도해보세요:</p>";
    echo "<ol>";
    echo "<li>phpMyAdmin에서 직접 SQL 실행</li>";
    echo "<li>MySQL 명령줄에서 실행</li>";
    echo "<li>데이터베이스 권한 확인</li>";
    echo "</ol>";
    
    echo "<p><strong>phpMyAdmin에서 실행할 SQL:</strong></p>";
    echo "<pre>ALTER TABLE `product_favorites` 
MODIFY COLUMN `product_type` ENUM('mvno', 'mno', 'internet', 'mno-sim') NOT NULL COMMENT '상품 타입';</pre>";
    
    echo "<p><a href='/MVNO/check-db-field.php'>필드 확인 페이지로 돌아가기</a></p>";
}

echo "</body></html>";
?>

