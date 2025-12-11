<?php
/**
 * 알뜰폰 상품 데이터 확인 스크립트
 * 브라우저에서 실행: http://localhost/MVNO/admin/products/check-products.php
 */

require_once __DIR__ . '/../../includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>알뜰폰 상품 데이터 확인</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    .success { color: green; }
    .error { color: red; }
    .info { color: blue; }
</style>";

try {
    $pdo = getDBConnection();
    
    if (!$pdo) {
        echo "<p class='error'>✗ 데이터베이스 연결 실패</p>";
        exit;
    }
    
    echo "<p class='success'>✓ 데이터베이스 연결 성공</p>";
    
    // 1. products 테이블의 MVNO 상품 개수 확인
    echo "<h2>1. products 테이블 - MVNO 상품 개수</h2>";
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive,
            SUM(CASE WHEN status = 'deleted' THEN 1 ELSE 0 END) as deleted
        FROM products 
        WHERE product_type = 'mvno'
    ");
    $counts = $stmt->fetch();
    echo "<table>";
    echo "<tr><th>전체</th><th>판매중 (active)</th><th>판매종료 (inactive)</th><th>삭제됨 (deleted)</th></tr>";
    echo "<tr><td>{$counts['total']}</td><td>{$counts['active']}</td><td>{$counts['inactive']}</td><td>{$counts['deleted']}</td></tr>";
    echo "</table>";
    
    // 2. product_mvno_details 테이블 개수 확인
    echo "<h2>2. product_mvno_details 테이블 - 상세 정보 개수</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM product_mvno_details");
    $detailCount = $stmt->fetch()['total'];
    echo "<p>상세 정보 레코드 수: <strong>{$detailCount}</strong></p>";
    
    // 3. JOIN 결과 확인 (현재 쿼리와 동일)
    echo "<h2>3. JOIN 결과 확인 (현재 사용 중인 쿼리)</h2>";
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT p.id) as total
        FROM products p
        INNER JOIN product_mvno_details mvno ON p.id = mvno.product_id
        WHERE p.product_type = 'mvno' AND p.status != 'deleted'
    ");
    $stmt->execute();
    $joinCount = $stmt->fetch()['total'];
    echo "<p>JOIN 결과 개수: <strong>{$joinCount}</strong></p>";
    
    // 4. products에 있지만 product_mvno_details에 없는 상품 확인
    echo "<h2>4. 상세 정보가 없는 상품 확인</h2>";
    $stmt = $pdo->query("
        SELECT p.id, p.seller_id, p.status, p.created_at
        FROM products p
        LEFT JOIN product_mvno_details mvno ON p.id = mvno.product_id
        WHERE p.product_type = 'mvno' 
        AND p.status != 'deleted'
        AND mvno.product_id IS NULL
        LIMIT 10
    ");
    $missingDetails = $stmt->fetchAll();
    if (count($missingDetails) > 0) {
        echo "<p class='error'>⚠ 상세 정보가 없는 상품이 " . count($missingDetails) . "개 있습니다:</p>";
        echo "<table>";
        echo "<tr><th>상품 ID</th><th>판매자 ID</th><th>상태</th><th>등록일</th></tr>";
        foreach ($missingDetails as $product) {
            echo "<tr>";
            echo "<td>{$product['id']}</td>";
            echo "<td>{$product['seller_id']}</td>";
            echo "<td>{$product['status']}</td>";
            echo "<td>{$product['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='success'>✓ 모든 상품에 상세 정보가 있습니다.</p>";
    }
    
    // 5. 실제 상품 목록 샘플 (최대 10개)
    echo "<h2>5. 실제 상품 목록 샘플 (최대 10개)</h2>";
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.seller_id,
            p.status,
            mvno.plan_name AS product_name,
            mvno.provider,
            mvno.price_after AS monthly_fee,
            p.created_at
        FROM products p
        INNER JOIN product_mvno_details mvno ON p.id = mvno.product_id
        WHERE p.product_type = 'mvno' AND p.status != 'deleted'
        ORDER BY p.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $products = $stmt->fetchAll();
    
    if (count($products) > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>요금제명</th><th>통신사</th><th>판매자 ID</th><th>할인 후 요금</th><th>상태</th><th>등록일</th></tr>";
        foreach ($products as $product) {
            echo "<tr>";
            echo "<td>{$product['id']}</td>";
            echo "<td>" . htmlspecialchars($product['product_name'] ?? '-') . "</td>";
            echo "<td>" . htmlspecialchars($product['provider'] ?? '-') . "</td>";
            echo "<td>{$product['seller_id']}</td>";
            echo "<td>" . ($product['monthly_fee'] ? number_format($product['monthly_fee']) . '원' : '공짜') . "</td>";
            echo "<td>{$product['status']}</td>";
            echo "<td>{$product['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>✗ 조회된 상품이 없습니다.</p>";
    }
    
    // 6. 판매자 정보 확인
    echo "<h2>6. 판매자 정보 확인</h2>";
    $stmt = $pdo->query("
        SELECT DISTINCT p.seller_id, COUNT(*) as product_count
        FROM products p
        INNER JOIN product_mvno_details mvno ON p.id = mvno.product_id
        WHERE p.product_type = 'mvno' AND p.status != 'deleted'
        GROUP BY p.seller_id
        ORDER BY product_count DESC
        LIMIT 10
    ");
    $sellers = $stmt->fetchAll();
    if (count($sellers) > 0) {
        echo "<table>";
        echo "<tr><th>판매자 ID</th><th>상품 개수</th></tr>";
        foreach ($sellers as $seller) {
            echo "<tr>";
            echo "<td>{$seller['seller_id']}</td>";
            echo "<td>{$seller['product_count']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='info'>판매자 정보가 없습니다.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>✗ 오류 발생: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>SQL Error Info: " . htmlspecialchars(json_encode($e->errorInfo ?? [])) . "</p>";
}

echo "<hr>";
echo "<p><a href='mvno-list.php'>← 상품 목록 페이지로 돌아가기</a></p>";

