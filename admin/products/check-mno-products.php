<?php
/**
 * MNO 상품 확인 스크립트
 * 디버깅용 - 실제 데이터베이스에 상품이 있는지 확인
 */

require_once __DIR__ . '/../../includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>MNO 상품 확인</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .count { font-size: 24px; color: #10b981; font-weight: bold; margin: 20px 0; }
        .error { color: #ef4444; }
        .success { color: #10b981; }
    </style>
</head>
<body>
    <h1>MNO 상품 데이터베이스 확인</h1>
    
    <?php
    try {
        $pdo = getDBConnection();
        if (!$pdo) {
            echo '<p class="error">데이터베이스 연결 실패</p>';
            exit;
        }
        
        // 1. products 테이블의 모든 MNO 상품 확인
        echo '<h2>1. products 테이블 - MNO 상품 전체</h2>';
        $stmt = $pdo->query("
            SELECT COUNT(*) as cnt 
            FROM products 
            WHERE product_type = 'mno' AND status != 'deleted'
        ");
        $result = $stmt->fetch();
        $totalProducts = $result['cnt'];
        echo '<p class="count">총 MNO 상품 수: ' . $totalProducts . '개</p>';
        
        if ($totalProducts > 0) {
            $stmt = $pdo->query("
                SELECT id, seller_id, product_type, status, created_at 
                FROM products 
                WHERE product_type = 'mno' AND status != 'deleted'
                ORDER BY created_at DESC
                LIMIT 10
            ");
            $products = $stmt->fetchAll();
            
            echo '<table>';
            echo '<tr><th>ID</th><th>Seller ID</th><th>Type</th><th>Status</th><th>Created At</th></tr>';
            foreach ($products as $product) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($product['id']) . '</td>';
                echo '<td>' . htmlspecialchars($product['seller_id']) . '</td>';
                echo '<td>' . htmlspecialchars($product['product_type']) . '</td>';
                echo '<td>' . htmlspecialchars($product['status']) . '</td>';
                echo '<td>' . htmlspecialchars($product['created_at']) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
        
        // 2. product_mno_details 테이블 확인
        echo '<h2>2. product_mno_details 테이블</h2>';
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM product_mno_details");
        $result = $stmt->fetch();
        $totalDetails = $result['cnt'];
        echo '<p class="count">총 MNO 상세 정보 수: ' . $totalDetails . '개</p>';
        
        if ($totalDetails > 0) {
            $stmt = $pdo->query("
                SELECT product_id, device_name, provider, price_main 
                FROM product_mno_details 
                ORDER BY product_id DESC
                LIMIT 10
            ");
            $details = $stmt->fetchAll();
            
            echo '<table>';
            echo '<tr><th>Product ID</th><th>Device Name</th><th>Provider</th><th>Price</th></tr>';
            foreach ($details as $detail) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($detail['product_id']) . '</td>';
                echo '<td>' . htmlspecialchars($detail['device_name'] ?? '-') . '</td>';
                echo '<td>' . htmlspecialchars($detail['provider'] ?? '-') . '</td>';
                echo '<td>' . htmlspecialchars($detail['price_main'] ?? '-') . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
        
        // 3. JOIN 결과 확인
        echo '<h2>3. JOIN 결과 (products + product_mno_details)</h2>';
        $stmt = $pdo->query("
            SELECT COUNT(DISTINCT p.id) as cnt
            FROM products p
            INNER JOIN product_mno_details mno ON p.id = mno.product_id
            WHERE p.product_type = 'mno' AND p.status != 'deleted'
        ");
        $result = $stmt->fetch();
        $joinedCount = $result['cnt'];
        echo '<p class="count">JOIN 후 상품 수: ' . $joinedCount . '개</p>';
        
        if ($joinedCount > 0) {
            $stmt = $pdo->query("
                SELECT 
                    p.id,
                    p.seller_id,
                    p.status,
                    mno.device_name,
                    mno.provider,
                    mno.price_main,
                    p.created_at
                FROM products p
                INNER JOIN product_mno_details mno ON p.id = mno.product_id
                WHERE p.product_type = 'mno' AND p.status != 'deleted'
                ORDER BY p.created_at DESC
                LIMIT 10
            ");
            $joinedProducts = $stmt->fetchAll();
            
            echo '<table>';
            echo '<tr><th>ID</th><th>Seller ID</th><th>Status</th><th>Device Name</th><th>Provider</th><th>Price</th><th>Created At</th></tr>';
            foreach ($joinedProducts as $product) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($product['id']) . '</td>';
                echo '<td>' . htmlspecialchars($product['seller_id']) . '</td>';
                echo '<td>' . htmlspecialchars($product['status']) . '</td>';
                echo '<td>' . htmlspecialchars($product['device_name'] ?? '-') . '</td>';
                echo '<td>' . htmlspecialchars($product['provider'] ?? '-') . '</td>';
                echo '<td>' . htmlspecialchars($product['price_main'] ?? '-') . '</td>';
                echo '<td>' . htmlspecialchars($product['created_at']) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<p class="error">JOIN 결과가 없습니다. products 테이블에는 있지만 product_mno_details 테이블에 해당 상품의 상세 정보가 없는 것 같습니다.</p>';
        }
        
        // 4. seller_id 타입 확인
        echo '<h2>4. Seller ID 타입 확인</h2>';
        $stmt = $pdo->query("
            SELECT DISTINCT 
                p.seller_id,
                CAST(p.seller_id AS CHAR) as seller_id_str,
                u.user_id,
                CAST(u.user_id AS CHAR) as user_id_str
            FROM products p
            LEFT JOIN users u ON CAST(p.seller_id AS CHAR) = CAST(u.user_id AS CHAR)
            WHERE p.product_type = 'mno' AND p.status != 'deleted'
            LIMIT 5
        ");
        $sellerInfo = $stmt->fetchAll();
        
        if (!empty($sellerInfo)) {
            echo '<table>';
            echo '<tr><th>products.seller_id</th><th>seller_id (문자열)</th><th>users.user_id</th><th>user_id (문자열)</th></tr>';
            foreach ($sellerInfo as $info) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($info['seller_id'] ?? '-') . '</td>';
                echo '<td>' . htmlspecialchars($info['seller_id_str'] ?? '-') . '</td>';
                echo '<td>' . htmlspecialchars($info['user_id'] ?? '-') . '</td>';
                echo '<td>' . htmlspecialchars($info['user_id_str'] ?? '-') . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
        
        // 5. 실제 mno-list.php에서 사용하는 쿼리 테스트
        echo '<h2>5. 실제 페이지 쿼리 테스트</h2>';
        $whereConditions = ["p.product_type = 'mno'", "p.status != 'deleted'"];
        $whereClause = implode(' AND ', $whereConditions);
        
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT p.id) as total
            FROM products p
            INNER JOIN product_mno_details mno ON p.id = mno.product_id
            WHERE {$whereClause}
        ");
        $stmt->execute();
        $testResult = $stmt->fetch();
        $testCount = $testResult['total'];
        
        echo '<p class="count">실제 페이지 쿼리 결과: ' . $testCount . '개</p>';
        
        if ($testCount > 0) {
            echo '<p class="success">✓ 데이터가 있습니다! 페이지에 표시되어야 합니다.</p>';
        } else {
            echo '<p class="error">✗ 데이터가 없습니다. 위의 항목들을 확인해보세요.</p>';
        }
        
    } catch (PDOException $e) {
        echo '<p class="error">오류: ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    }
    ?>
    
    <p style="margin-top: 40px;">
        <a href="mno-list.php">← 목록으로 돌아가기</a>
    </p>
</body>
</html>







