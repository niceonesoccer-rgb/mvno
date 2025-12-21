<?php
/**
 * 인터넷 리뷰 문제 수정 스크립트
 * 브라우저에서 실행: http://localhost/MVNO/fix-internet-reviews.php
 */

require_once __DIR__ . '/includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>인터넷 리뷰 문제 수정</h1>";
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
    
    // 1. 현재 product_type 컬럼 확인
    echo "<h2>1. 현재 product_type 컬럼 상태 확인</h2>";
    $stmt = $pdo->query("SHOW COLUMNS FROM product_reviews WHERE Field = 'product_type'");
    $columnInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($columnInfo) {
        $currentType = $columnInfo['Type'];
        echo "<p><strong>현재 Type:</strong> " . htmlspecialchars($currentType) . "</p>";
        
        // ENUM 값에 'internet'이 이미 포함되어 있는지 확인
        if (strpos($currentType, 'internet') !== false) {
            echo "<p class='success'>✓ 'internet' 타입이 이미 ENUM에 포함되어 있습니다.</p>";
            echo "<p class='info'>→ 추가 작업이 필요하지 않습니다.</p>";
        } else {
            echo "<p class='error'>✗ 'internet' 타입이 ENUM에 포함되어 있지 않습니다.</p>";
            echo "<p class='info'>→ ENUM에 'internet'을 추가하겠습니다...</p>";
            
            // 2. ALTER TABLE 실행
            echo "<h2>2. product_reviews 테이블 수정</h2>";
            try {
                $alterSql = "ALTER TABLE `product_reviews` MODIFY COLUMN `product_type` ENUM('mvno', 'mno', 'internet') NOT NULL COMMENT '상품 타입'";
                $pdo->exec($alterSql);
                
                echo "<p class='success'>✓ 테이블 수정 성공!</p>";
                echo "<pre>" . htmlspecialchars($alterSql) . "</pre>";
                
                // 수정 후 다시 확인
                echo "<h2>3. 수정 후 확인</h2>";
                $stmt = $pdo->query("SHOW COLUMNS FROM product_reviews WHERE Field = 'product_type'");
                $newColumnInfo = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($newColumnInfo && strpos($newColumnInfo['Type'], 'internet') !== false) {
                    echo "<p class='success'>✓ 수정 완료! 새로운 Type: " . htmlspecialchars($newColumnInfo['Type']) . "</p>";
                    echo "<p class='info'>이제 인터넷 상품에 대한 리뷰가 정상적으로 표시될 것입니다.</p>";
                } else {
                    echo "<p class='error'>✗ 수정이 제대로 적용되지 않았습니다.</p>";
                }
                
            } catch (PDOException $e) {
                echo "<p class='error'>✗ 테이블 수정 실패: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
    } else {
        echo "<p class='error'>✗ product_type 컬럼을 찾을 수 없습니다.</p>";
    }
    
    // 4. product_id = 29에 대한 리뷰 확인
    echo "<h2>4. product_id = 29 리뷰 확인</h2>";
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as cnt 
        FROM product_reviews 
        WHERE product_id = 29 AND product_type = 'internet' AND status = 'approved'
    ");
    $stmt->execute();
    $reviewCount = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
    
    echo "<p class='info'>product_id = 29의 승인된 인터넷 리뷰 수: " . $reviewCount . "개</p>";
    
    if ($reviewCount > 0) {
        $stmt = $pdo->prepare("
            SELECT id, product_id, user_id, rating, status, created_at 
            FROM product_reviews 
            WHERE product_id = 29 AND product_type = 'internet' AND status = 'approved'
            ORDER BY created_at DESC
        ");
        $stmt->execute();
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Product ID</th><th>User ID</th><th>Rating</th><th>Status</th><th>Created At</th></tr>";
        foreach ($reviews as $review) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($review['id']) . "</td>";
            echo "<td>" . htmlspecialchars($review['product_id']) . "</td>";
            echo "<td>" . htmlspecialchars($review['user_id']) . "</td>";
            echo "<td>" . htmlspecialchars($review['rating']) . "</td>";
            echo "<td>" . htmlspecialchars($review['status']) . "</td>";
            echo "<td>" . htmlspecialchars($review['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='info'>승인된 인터넷 리뷰가 없습니다. 같은 판매자의 다른 인터넷 상품 리뷰를 확인하겠습니다...</p>";
        
        // 같은 seller_id의 모든 인터넷 상품 확인
        $stmt = $pdo->prepare("SELECT seller_id FROM products WHERE id = 29 AND product_type = 'internet'");
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            $stmt = $pdo->prepare("
                SELECT id FROM products 
                WHERE seller_id = :seller_id AND product_type = 'internet' AND status = 'active'
            ");
            $stmt->execute([':seller_id' => $product['seller_id']]);
            $productIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($productIds)) {
                $placeholders = implode(',', array_fill(0, count($productIds), '?'));
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as cnt 
                    FROM product_reviews 
                    WHERE product_id IN ($placeholders) AND product_type = 'internet' AND status = 'approved'
                ");
                $stmt->execute($productIds);
                $totalReviews = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
                
                echo "<p class='info'>같은 판매자의 모든 인터넷 상품 (ID: " . implode(', ', $productIds) . ")의 승인된 리뷰 수: " . $totalReviews . "개</p>";
            }
        }
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>오류: " . htmlspecialchars($e->getMessage()) . "</p>";
}


