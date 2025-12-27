<?php
/**
 * 상품 ID 64 정보 확인
 * products 테이블의 product_type 확인
 */

require_once __DIR__ . '/includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>상품 64 확인</title>";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;} .success{color:green;font-weight:bold;} .error{color:red;font-weight:bold;} table{border-collapse:collapse;margin:20px 0;} th,td{border:1px solid #ddd;padding:8px;} th{background-color:#f2f2f2;}</style>";
echo "</head><body>";

echo "<h1>상품 ID 64 정보 확인</h1>";

$pdo = getDBConnection();
if (!$pdo) {
    echo "<p class='error'>데이터베이스 연결 실패</p>";
    exit;
}

// 1. products 테이블에서 상품 ID 64 확인
echo "<h2>1. products 테이블의 상품 정보</h2>";
try {
    $stmt = $pdo->prepare("
        SELECT 
            id,
            seller_id,
            product_type,
            status,
            view_count,
            favorite_count,
            application_count,
            created_at
        FROM products
        WHERE id = :product_id
    ");
    $stmt->execute([':product_id' => 64]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo "<p class='error'>상품 ID 64가 존재하지 않습니다.</p>";
    } else {
        echo "<table>";
        echo "<tr><th>필드</th><th>값</th></tr>";
        foreach ($product as $key => $value) {
            $highlight = ($key === 'product_type' && $value !== 'mno-sim') ? "style='background-color:#ffebee;'" : "";
            echo "<tr {$highlight}>";
            echo "<td><strong>" . htmlspecialchars($key) . "</strong></td>";
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        if ($product['product_type'] !== 'mno-sim') {
            echo "<p class='error'><strong>⚠️ 문제 발견!</strong></p>";
            echo "<p>상품 ID 64의 product_type이 '<strong>" . htmlspecialchars($product['product_type']) . "</strong>'입니다.</p>";
            echo "<p>찜 기능을 사용하려면 product_type이 '<strong>mno-sim</strong>'이어야 합니다.</p>";
            echo "<p>이것이 찜 데이터가 저장되지 않는 이유입니다.</p>";
        } else {
            echo "<p class='success'>✓ product_type이 'mno-sim'으로 올바르게 설정되어 있습니다.</p>";
        }
    }
} catch (PDOException $e) {
    echo "<p class='error'>에러: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// 2. product_mno_sim_details 테이블 확인
echo "<h2>2. product_mno_sim_details 테이블 확인</h2>";
try {
    $stmt = $pdo->prepare("
        SELECT *
        FROM product_mno_sim_details
        WHERE product_id = :product_id
    ");
    $stmt->execute([':product_id' => 64]);
    $detail = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$detail) {
        echo "<p class='error'>상품 ID 64에 대한 통신사유심 상세 정보가 없습니다.</p>";
    } else {
        echo "<p class='success'>✓ 통신사유심 상세 정보가 있습니다.</p>";
        echo "<p><strong>요금제명:</strong> " . htmlspecialchars($detail['plan_name'] ?? 'N/A') . "</p>";
        echo "<p><strong>통신사:</strong> " . htmlspecialchars($detail['provider'] ?? 'N/A') . "</p>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>에러: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// 3. product_favorites 테이블에서 시도한 기록 확인 (타입 불일치로 실패했을 수 있음)
echo "<h2>3. 찜 시도 기록 확인</h2>";
try {
    // 모든 타입으로 시도한 찜 데이터 확인
    $stmt = $pdo->prepare("
        SELECT 
            pf.id,
            pf.product_id,
            pf.user_id,
            pf.product_type,
            pf.created_at,
            p.product_type as actual_product_type
        FROM product_favorites pf
        LEFT JOIN products p ON pf.product_id = p.id
        WHERE pf.product_id = :product_id
        ORDER BY pf.created_at DESC
    ");
    $stmt->execute([':product_id' => 64]);
    $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($favorites)) {
        echo "<p>상품 ID 64에 대한 찜 데이터가 전혀 없습니다.</p>";
    } else {
        echo "<p><strong>찜 데이터 " . count($favorites) . "개 발견:</strong></p>";
        echo "<table>";
        echo "<tr><th>ID</th><th>상품 ID</th><th>사용자 ID</th><th>찜 타입</th><th>실제 상품 타입</th><th>일치 여부</th><th>찜한 일시</th></tr>";
        foreach ($favorites as $fav) {
            $match = ($fav['product_type'] === $fav['actual_product_type']);
            $matchClass = $match ? 'success' : 'error';
            $matchText = $match ? '✓ 일치' : '✗ 불일치';
            echo "<tr>";
            echo "<td>" . htmlspecialchars($fav['id']) . "</td>";
            echo "<td>" . htmlspecialchars($fav['product_id']) . "</td>";
            echo "<td>" . htmlspecialchars($fav['user_id']) . "</td>";
            echo "<td>" . htmlspecialchars($fav['product_type']) . "</td>";
            echo "<td>" . htmlspecialchars($fav['actual_product_type'] ?? 'N/A') . "</td>";
            echo "<td class='{$matchClass}'>" . $matchText . "</td>";
            echo "<td>" . htmlspecialchars($fav['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>에러: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// 4. 해결 방법 제시
echo "<h2>4. 해결 방법</h2>";
if (isset($product) && $product['product_type'] !== 'mno-sim') {
    echo "<p class='error'><strong>문제:</strong> products 테이블의 product_type이 'mno-sim'이 아닙니다.</p>";
    echo "<p><strong>해결:</strong> products 테이블의 product_type을 'mno-sim'으로 수정하세요.</p>";
    echo "<pre>UPDATE products SET product_type = 'mno-sim' WHERE id = 64;</pre>";
    echo "<p><strong>주의:</strong> products 테이블의 product_type ENUM에 'mno-sim'이 포함되어 있어야 합니다.</p>";
    
    // products 테이블의 product_type ENUM 확인
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM products WHERE Field = 'product_type'");
        $column = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($column) {
            echo "<p><strong>현재 products.product_type ENUM:</strong> " . htmlspecialchars($column['Type']) . "</p>";
            if (stripos($column['Type'], 'mno-sim') === false) {
                echo "<p class='error'>⚠️ products 테이블의 product_type ENUM에 'mno-sim'이 없습니다!</p>";
                echo "<p>다음 SQL을 실행하세요:</p>";
                echo "<pre>ALTER TABLE `products` 
MODIFY COLUMN `product_type` ENUM('mvno', 'mno', 'internet', 'mno-sim') NOT NULL COMMENT '상품 타입';</pre>";
            }
        }
    } catch (PDOException $e) {
        echo "<p class='error'>ENUM 확인 에러: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p class='success'>상품 타입이 올바르게 설정되어 있습니다.</p>";
    echo "<p>다른 문제일 수 있습니다. API 로그를 확인하세요.</p>";
}

echo "<hr>";
echo "<p><a href='/MVNO/check-favorite-sql.php'>찜 데이터 확인 페이지로 돌아가기</a></p>";
echo "</body></html>";
?>


