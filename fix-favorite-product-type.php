<?php
/**
 * 찜 데이터의 product_type 수정 스크립트
 * product_type이 NULL인 찜 데이터를 실제 상품 타입으로 업데이트
 */

require_once __DIR__ . '/includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>찜 데이터 수정</title>";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;} .success{color:green;font-weight:bold;} .error{color:red;font-weight:bold;} .info{color:blue;} table{border-collapse:collapse;margin:20px 0;} th,td{border:1px solid #ddd;padding:8px;} th{background-color:#f2f2f2;} pre{background:#f5f5f5;padding:10px;border:1px solid #ddd;overflow-x:auto;}</style>";
echo "</head><body>";

echo "<h1>찜 데이터 product_type 수정</h1>";

$pdo = getDBConnection();
if (!$pdo) {
    echo "<p class='error'>데이터베이스 연결 실패</p>";
    exit;
}

// 1. product_type이 NULL이거나 빈 값인 찜 데이터 확인
echo "<h2>1. 수정이 필요한 찜 데이터 확인</h2>";
try {
    $stmt = $pdo->query("
        SELECT 
            pf.id,
            pf.product_id,
            pf.user_id,
            pf.product_type,
            pf.created_at,
            p.product_type as actual_product_type
        FROM product_favorites pf
        LEFT JOIN products p ON pf.product_id = p.id
        WHERE pf.product_type IS NULL 
           OR pf.product_type = ''
           OR (p.product_type IS NOT NULL AND pf.product_type != p.product_type)
        ORDER BY pf.created_at DESC
    ");
    $favoritesToFix = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($favoritesToFix)) {
        echo "<p class='success'>✓ 수정이 필요한 찜 데이터가 없습니다.</p>";
    } else {
        echo "<p class='info'>수정이 필요한 찜 데이터: " . count($favoritesToFix) . "개</p>";
        echo "<table>";
        echo "<tr><th>ID</th><th>상품 ID</th><th>사용자 ID</th><th>현재 타입</th><th>실제 타입</th><th>찜한 일시</th></tr>";
        foreach ($favoritesToFix as $fav) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($fav['id']) . "</td>";
            echo "<td>" . htmlspecialchars($fav['product_id']) . "</td>";
            echo "<td>" . htmlspecialchars($fav['user_id']) . "</td>";
            echo "<td>" . htmlspecialchars($fav['product_type'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($fav['actual_product_type'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($fav['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>에러: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// 2. 수정 실행
if (!isset($_POST['confirm'])) {
    if (!empty($favoritesToFix)) {
        echo "<h2>2. 수정 실행</h2>";
        echo "<p class='info'>다음 SQL을 실행합니다:</p>";
        echo "<pre>UPDATE product_favorites pf
INNER JOIN products p ON pf.product_id = p.id
SET pf.product_type = p.product_type
WHERE pf.product_type IS NULL 
   OR pf.product_type = ''
   OR pf.product_type != p.product_type;</pre>";
        
        echo "<form method='POST'>";
        echo "<p><button type='submit' name='confirm' value='1' style='padding:10px 20px;background:#4CAF50;color:white;border:none;border-radius:4px;cursor:pointer;font-size:16px;'>수정 실행</button></p>";
        echo "</form>";
    }
    echo "<p><a href='/MVNO/check-product-64.php'>상품 확인 페이지로 돌아가기</a></p>";
    exit;
}

// 수정 실행
echo "<h2>2. 수정 실행 중...</h2>";
try {
    $sql = "UPDATE product_favorites pf
            INNER JOIN products p ON pf.product_id = p.id
            SET pf.product_type = p.product_type
            WHERE pf.product_type IS NULL 
               OR pf.product_type = ''
               OR pf.product_type != p.product_type";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $affectedRows = $stmt->rowCount();
    
    echo "<p class='success'>✓ 수정 완료! " . $affectedRows . "개의 레코드가 업데이트되었습니다.</p>";
    
    // 수정 후 확인
    echo "<h2>3. 수정 후 확인</h2>";
    $stmt = $pdo->query("
        SELECT 
            pf.id,
            pf.product_id,
            pf.user_id,
            pf.product_type,
            pf.created_at,
            p.product_type as actual_product_type
        FROM product_favorites pf
        LEFT JOIN products p ON pf.product_id = p.id
        WHERE pf.product_id = 64
        ORDER BY pf.created_at DESC
    ");
    $fixedFavorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($fixedFavorites)) {
        echo "<table>";
        echo "<tr><th>ID</th><th>상품 ID</th><th>사용자 ID</th><th>찜 타입</th><th>실제 상품 타입</th><th>일치 여부</th><th>찜한 일시</th></tr>";
        foreach ($fixedFavorites as $fav) {
            $match = ($fav['product_type'] === $fav['actual_product_type']);
            $matchClass = $match ? 'success' : 'error';
            $matchText = $match ? '✓ 일치' : '✗ 불일치';
            echo "<tr>";
            echo "<td>" . htmlspecialchars($fav['id']) . "</td>";
            echo "<td>" . htmlspecialchars($fav['product_id']) . "</td>";
            echo "<td>" . htmlspecialchars($fav['user_id']) . "</td>";
            echo "<td>" . htmlspecialchars($fav['product_type'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($fav['actual_product_type'] ?? 'N/A') . "</td>";
            echo "<td class='{$matchClass}'>" . $matchText . "</td>";
            echo "<td>" . htmlspecialchars($fav['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<hr>";
    echo "<p><a href='/MVNO/check-favorite-sql.php'>찜 데이터 확인 페이지로 이동</a></p>";
    echo "<p><a href='/MVNO/mypage/wishlist.php?type=mno-sim'>마이페이지 찜 목록 보기</a></p>";
    
} catch (PDOException $e) {
    echo "<p class='error'>✗ 수정 실패: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p class='error'>에러 코드: " . $e->getCode() . "</p>";
    
    if ($e->errorInfo) {
        echo "<p><strong>에러 상세 정보:</strong></p>";
        echo "<pre>" . print_r($e->errorInfo, true) . "</pre>";
    }
}

echo "</body></html>";
?>


