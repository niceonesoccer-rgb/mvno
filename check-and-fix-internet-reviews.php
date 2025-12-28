<?php
/**
 * 인터넷 리뷰 확인 및 자동 수정 스크립트
 * 브라우저에서 실행: http://localhost/MVNO/check-and-fix-internet-reviews.php
 * 
 * 이 스크립트는:
 * 1. 모든 상태의 인터넷 리뷰를 확인합니다
 * 2. pending 상태의 리뷰를 자동으로 approved로 변경합니다
 */

require_once __DIR__ . '/includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>인터넷 리뷰 확인 및 자동 수정</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    .warning { color: orange; }
    pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; }
</style>";

try {
    $pdo = getDBConnection();
    
    if (!$pdo) {
        echo "<p class='error'>✗ 데이터베이스 연결 실패</p>";
        exit;
    }
    
    echo "<p class='success'>✓ 데이터베이스 연결 성공</p>";
    
    // product_id = 29 확인
    $productId = 29;
    
    echo "<h2>1. product_id = {$productId} 상품 정보 확인</h2>";
    $stmt = $pdo->prepare("SELECT id, seller_id, product_type, status FROM products WHERE id = :product_id");
    $stmt->execute([':product_id' => $productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo "<p class='error'>✗ product_id = {$productId}인 상품을 찾을 수 없습니다.</p>";
        exit;
    }
    
    echo "<p>상품 ID: {$product['id']}, 판매자 ID: {$product['seller_id']}, 타입: {$product['product_type']}, 상태: {$product['status']}</p>";
    
    // 같은 판매자의 모든 인터넷 상품 ID 가져오기
    echo "<h2>2. 같은 판매자의 모든 인터넷 상품 확인</h2>";
    $stmt = $pdo->prepare("
        SELECT id FROM products 
        WHERE seller_id = :seller_id AND product_type = 'internet' AND status = 'active'
    ");
    $stmt->execute([':seller_id' => $product['seller_id']]);
    $productIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($productIds)) {
        echo "<p class='info'>같은 판매자의 활성 인터넷 상품이 없습니다.</p>";
        exit;
    }
    
    echo "<p class='info'>같은 판매자의 인터넷 상품 ID: " . implode(', ', $productIds) . "</p>";
    
    // 모든 상태의 리뷰 확인
    echo "<h2>3. 모든 상태의 리뷰 확인 (product_type = 'internet')</h2>";
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    
    // 상태별 리뷰 개수 확인
    $stmt = $pdo->prepare("
        SELECT status, COUNT(*) as cnt 
        FROM product_reviews 
        WHERE product_id IN ($placeholders) AND product_type = ?
        GROUP BY status
    ");
    $params = array_merge($productIds, ['internet']);
    $stmt->execute($params);
    $statusCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($statusCounts)) {
        echo "<p class='info'>이 상품들에 대한 인터넷 리뷰가 전혀 없습니다.</p>";
        
        // product_type 없이 저장된 리뷰가 있는지 확인
        echo "<h2>4. product_type이 없는 리뷰 확인</h2>";
        $stmt = $pdo->prepare("
            SELECT id, product_id, user_id, product_type, rating, status, created_at 
            FROM product_reviews 
            WHERE product_id IN ($placeholders)
            ORDER BY created_at DESC
            LIMIT 20
        ");
        $stmt->execute($productIds);
        $reviewsWithoutType = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($reviewsWithoutType)) {
            echo "<p class='warning'>⚠ product_type이 설정되지 않은 리뷰가 " . count($reviewsWithoutType) . "개 발견되었습니다.</p>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Product ID</th><th>User ID</th><th>Product Type</th><th>Rating</th><th>Status</th><th>Created At</th></tr>";
            foreach ($reviewsWithoutType as $review) {
                $typeDisplay = $review['product_type'] ?? '(NULL)';
                $typeClass = ($review['product_type'] === null || $review['product_type'] === '') ? 'warning' : '';
                echo "<tr class='{$typeClass}'>";
                echo "<td>" . htmlspecialchars($review['id']) . "</td>";
                echo "<td>" . htmlspecialchars($review['product_id']) . "</td>";
                echo "<td>" . htmlspecialchars($review['user_id']) . "</td>";
                echo "<td>" . htmlspecialchars($typeDisplay) . "</td>";
                echo "<td>" . htmlspecialchars($review['rating']) . "</td>";
                echo "<td>" . htmlspecialchars($review['status']) . "</td>";
                echo "<td>" . htmlspecialchars($review['created_at']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // 자동 수정 옵션 제공
            echo "<h2>5. 자동 수정</h2>";
            if (isset($_GET['fix']) && $_GET['fix'] === 'yes') {
                echo "<p class='info'>리뷰를 수정하는 중...</p>";
                
                $fixedCount = 0;
                foreach ($reviewsWithoutType as $review) {
                    // product_type이 null이거나 빈 문자열인 경우 'internet'으로 수정
                    if ($review['product_type'] === null || $review['product_type'] === '') {
                        $updateStmt = $pdo->prepare("
                            UPDATE product_reviews 
                            SET product_type = 'internet' 
                            WHERE id = :review_id
                        ");
                        $updateStmt->execute([':review_id' => $review['id']]);
                        $fixedCount++;
                    }
                }
                
                echo "<p class='success'>✓ {$fixedCount}개의 리뷰 product_type을 'internet'으로 수정했습니다.</p>";
                
                // pending 상태를 approved로 변경
                $approveStmt = $pdo->prepare("
                    UPDATE product_reviews 
                    SET status = 'approved' 
                    WHERE product_id IN ($placeholders) 
                    AND product_type = ?
                    AND status = 'pending'
                ");
                $approveParams = array_merge($productIds, ['internet']);
                $approveStmt->execute($approveParams);
                $approvedCount = $approveStmt->rowCount();
                
                if ($approvedCount > 0) {
                    echo "<p class='success'>✓ {$approvedCount}개의 리뷰를 'approved' 상태로 변경했습니다.</p>";
                }
                
                echo "<p class='info'><a href='?'>페이지 새로고침하여 확인</a></p>";
            } else {
                echo "<p class='warning'>⚠ 수정하려면 다음 링크를 클릭하세요:</p>";
                echo "<p><a href='?fix=yes' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>리뷰 자동 수정 실행</a></p>";
                echo "<p class='info'>이 작업은 다음을 수행합니다:</p>";
                echo "<ul>";
                echo "<li>product_type이 없는 리뷰를 'internet'으로 설정</li>";
                echo "<li>pending 상태의 리뷰를 'approved'로 변경</li>";
                echo "</ul>";
            }
        } else {
            echo "<p class='info'>product_type이 없는 리뷰도 없습니다.</p>";
        }
        
        exit;
    }
    
    // 상태별 개수 표시
    echo "<table>";
    echo "<tr><th>Status</th><th>개수</th></tr>";
    foreach ($statusCounts as $row) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['status']) . "</td>";
        echo "<td>" . htmlspecialchars($row['cnt']) . "개</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 모든 리뷰 상세 정보 표시
    echo "<h2>4. 모든 리뷰 상세 정보</h2>";
    $stmt = $pdo->prepare("
        SELECT id, product_id, user_id, product_type, rating, title, content, status, created_at 
        FROM product_reviews 
        WHERE product_id IN ($placeholders) AND product_type = ?
        ORDER BY created_at DESC
    ");
    $reviewParams = array_merge($productIds, ['internet']);
    $stmt->execute($reviewParams);
    $allReviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($allReviews)) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Product ID</th><th>User ID</th><th>Product Type</th><th>Rating</th><th>Status</th><th>Created At</th></tr>";
        foreach ($allReviews as $review) {
            $statusClass = $review['status'] === 'approved' ? 'success' : ($review['status'] === 'pending' ? 'warning' : '');
            echo "<tr class='{$statusClass}'>";
            echo "<td>" . htmlspecialchars($review['id']) . "</td>";
            echo "<td>" . htmlspecialchars($review['product_id']) . "</td>";
            echo "<td>" . htmlspecialchars($review['user_id']) . "</td>";
            echo "<td>" . htmlspecialchars($review['product_type']) . "</td>";
            echo "<td>" . htmlspecialchars($review['rating']) . "</td>";
            echo "<td>" . htmlspecialchars($review['status']) . "</td>";
            echo "<td>" . htmlspecialchars($review['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // pending 리뷰가 있으면 자동 승인 옵션 제공
        $pendingCount = 0;
        foreach ($statusCounts as $row) {
            if ($row['status'] === 'pending') {
                $pendingCount = $row['cnt'];
                break;
            }
        }
        
        if ($pendingCount > 0) {
            echo "<h2>5. 자동 승인</h2>";
            if (isset($_GET['approve']) && $_GET['approve'] === 'yes') {
                echo "<p class='info'>pending 상태의 리뷰를 approved로 변경하는 중...</p>";
                
                $approveStmt = $pdo->prepare("
                    UPDATE product_reviews 
                    SET status = 'approved' 
                    WHERE product_id IN ($placeholders) 
                    AND product_type = ?
                    AND status = 'pending'
                ");
                $approveParams2 = array_merge($productIds, ['internet']);
                $approveStmt->execute($approveParams2);
                $approvedCount = $approveStmt->rowCount();
                
                echo "<p class='success'>✓ {$approvedCount}개의 리뷰를 'approved' 상태로 변경했습니다.</p>";
                echo "<p class='info'><a href='?'>페이지 새로고침하여 확인</a></p>";
            } else {
                echo "<p class='warning'>⚠ pending 상태의 리뷰가 {$pendingCount}개 있습니다. 자동으로 승인하시겠습니까?</p>";
                echo "<p><a href='?approve=yes' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>리뷰 자동 승인</a></p>";
            }
        }
    }
    
    // 최종 승인된 리뷰 개수 확인
    echo "<h2>6. 최종 확인</h2>";
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as cnt 
        FROM product_reviews 
        WHERE product_id IN ($placeholders) AND product_type = ? AND status = 'approved'
    ");
    $finalParams = array_merge($productIds, ['internet']);
    $stmt->execute($finalParams);
    $finalCount = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
    
    echo "<p class='info'>현재 승인된 인터넷 리뷰 수: <strong>{$finalCount}개</strong></p>";
    
    if ($finalCount > 0) {
        echo "<p class='success'>✓ 이제 인터넷 상품 상세 페이지에서 리뷰가 표시될 것입니다.</p>";
        echo "<p class='info'><a href='/MVNO/internets/internet-detail.php?id={$productId}' target='_blank'>상품 상세 페이지 확인</a></p>";
    } else {
        echo "<p class='warning'>⚠ 아직 승인된 리뷰가 없습니다. 위의 '리뷰 자동 승인' 버튼을 클릭하여 pending 리뷰를 승인하세요.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>오류: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}








