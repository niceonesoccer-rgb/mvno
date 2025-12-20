<?php
/**
 * product_reviews 테이블에 application_id 컬럼 추가 스크립트
 * 브라우저에서 실행: http://localhost/MVNO/add-application-id-to-reviews.php
 */

require_once __DIR__ . '/includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>product_reviews 테이블에 application_id 컬럼 추가</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; }
</style>";

try {
    $pdo = getDBConnection();
    
    if (!$pdo) {
        echo "<p class='error'>✗ 데이터베이스 연결 실패</p>";
        exit;
    }
    
    echo "<p class='success'>✓ 데이터베이스 연결 성공</p>";
    
    // 1. 현재 컬럼 상태 확인
    echo "<h2>1. 현재 컬럼 상태 확인</h2>";
    $stmt = $pdo->query("SHOW COLUMNS FROM product_reviews LIKE 'application_id'");
    $columnExists = $stmt->rowCount() > 0;
    
    if ($columnExists) {
        echo "<p class='success'>✓ application_id 컬럼이 이미 존재합니다.</p>";
        
        // 컬럼 정보 확인
        $stmt = $pdo->query("SHOW COLUMNS FROM product_reviews WHERE Field = 'application_id'");
        $columnInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($columnInfo) {
            echo "<p>Type: " . htmlspecialchars($columnInfo['Type']) . "</p>";
            echo "<p>Null: " . htmlspecialchars($columnInfo['Null']) . "</p>";
            echo "<p>Key: " . htmlspecialchars($columnInfo['Key']) . "</p>";
        }
    } else {
        echo "<p class='error'>✗ application_id 컬럼이 없습니다.</p>";
        echo "<p class='info'>→ application_id 컬럼을 추가하겠습니다...</p>";
        
        // 2. application_id 컬럼 추가
        echo "<h2>2. application_id 컬럼 추가</h2>";
        try {
            // 컬럼 추가
            $pdo->exec("
                ALTER TABLE `product_reviews` 
                ADD COLUMN `application_id` INT(11) UNSIGNED NULL COMMENT '신청 ID (주문별 리뷰 구분용)' AFTER `product_id`
            ");
            
            echo "<p class='success'>✓ application_id 컬럼 추가 성공!</p>";
            
            // 인덱스 추가
            try {
                $pdo->exec("ALTER TABLE `product_reviews` ADD INDEX `idx_application_id` (`application_id`)");
                echo "<p class='success'>✓ application_id 인덱스 추가 성공!</p>";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                    echo "<p class='info'>→ application_id 인덱스가 이미 존재합니다.</p>";
                } else {
                    throw $e;
                }
            }
            
        } catch (PDOException $e) {
            echo "<p class='error'>✗ 컬럼 추가 실패: " . htmlspecialchars($e->getMessage()) . "</p>";
            exit;
        }
    }
    
    // 3. 최종 확인
    echo "<h2>3. 최종 확인</h2>";
    $stmt = $pdo->query("SHOW COLUMNS FROM product_reviews WHERE Field = 'application_id'");
    $finalColumn = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($finalColumn) {
        echo "<p class='success'>✓ application_id 컬럼이 정상적으로 존재합니다.</p>";
        echo "<p>Type: " . htmlspecialchars($finalColumn['Type']) . "</p>";
        echo "<p>Null: " . htmlspecialchars($finalColumn['Null']) . "</p>";
        echo "<p class='info'>이제 같은 상품을 여러 번 주문했을 때 각 주문별로 별도의 리뷰를 작성할 수 있습니다.</p>";
    } else {
        echo "<p class='error'>✗ application_id 컬럼 추가가 제대로 되지 않았습니다.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>오류: " . htmlspecialchars($e->getMessage()) . "</p>";
}
