<?php
/**
 * 찜 데이터 확인 SQL 쿼리
 * 상품 ID 64가 실제로 저장되었는지 확인
 */

require_once __DIR__ . '/includes/data/db-config.php';
require_once __DIR__ . '/includes/data/auth-functions.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>찜 데이터 확인</title>";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;} .success{color:green;font-weight:bold;} .error{color:red;} table{border-collapse:collapse;margin:20px 0;width:100%;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background-color:#f2f5f9;} pre{background:#f5f5f5;padding:10px;border:1px solid #ddd;overflow-x:auto;}</style>";
echo "</head><body>";

echo "<h1>찜 데이터 확인 (상품 ID: 64)</h1>";

$pdo = getDBConnection();
if (!$pdo) {
    echo "<p class='error'>데이터베이스 연결 실패</p>";
    exit;
}

// 1. 상품 ID 64의 찜 데이터 확인
echo "<h2>1. 상품 ID 64의 찜 데이터</h2>";
try {
    $stmt = $pdo->prepare("
        SELECT 
            pf.id,
            pf.product_id,
            pf.user_id,
            pf.product_type,
            pf.created_at,
            p.status as product_status,
            p.favorite_count
        FROM product_favorites pf
        LEFT JOIN products p ON pf.product_id = p.id
        WHERE pf.product_id = :product_id AND pf.product_type = 'mno-sim'
        ORDER BY pf.created_at DESC
    ");
    $stmt->execute([':product_id' => 64]);
    $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($favorites)) {
        echo "<p class='error'>상품 ID 64에 대한 찜 데이터가 없습니다.</p>";
    } else {
        echo "<p class='success'>✓ 찜 데이터 " . count($favorites) . "개 발견!</p>";
        echo "<table>";
        echo "<tr><th>ID</th><th>상품 ID</th><th>사용자 ID</th><th>타입</th><th>상품 상태</th><th>찜 수</th><th>찜한 일시</th></tr>";
        foreach ($favorites as $fav) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($fav['id']) . "</td>";
            echo "<td>" . htmlspecialchars($fav['product_id']) . "</td>";
            echo "<td>" . htmlspecialchars($fav['user_id']) . "</td>";
            echo "<td>" . htmlspecialchars($fav['product_type']) . "</td>";
            echo "<td>" . htmlspecialchars($fav['product_status'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($fav['favorite_count'] ?? '0') . "</td>";
            echo "<td>" . htmlspecialchars($fav['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>에러: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// 2. 현재 로그인한 사용자의 찜 데이터 확인
echo "<h2>2. 현재 로그인한 사용자의 찜 데이터</h2>";
if (function_exists('isLoggedIn') && isLoggedIn()) {
    $currentUser = getCurrentUser();
    $userId = $currentUser['user_id'] ?? null;
    
    if ($userId) {
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    pf.id,
                    pf.product_id,
                    pf.product_type,
                    pf.created_at,
                    p.status as product_status,
                    p.favorite_count
                FROM product_favorites pf
                LEFT JOIN products p ON pf.product_id = p.id
                WHERE pf.user_id = :user_id AND pf.product_type = 'mno-sim'
                ORDER BY pf.created_at DESC
            ");
            $stmt->execute([':user_id' => $userId]);
            $userFavorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($userFavorites)) {
                echo "<p>현재 사용자가 찜한 통신사유심 상품이 없습니다.</p>";
            } else {
                echo "<p class='success'>✓ 찜한 통신사유심 상품 " . count($userFavorites) . "개</p>";
                echo "<table>";
                echo "<tr><th>ID</th><th>상품 ID</th><th>타입</th><th>상품 상태</th><th>찜 수</th><th>찜한 일시</th></tr>";
                foreach ($userFavorites as $fav) {
                    $highlight = ($fav['product_id'] == 64) ? "style='background-color:#fff3cd;'" : "";
                    echo "<tr {$highlight}>";
                    echo "<td>" . htmlspecialchars($fav['id']) . "</td>";
                    echo "<td>" . htmlspecialchars($fav['product_id']) . "</td>";
                    echo "<td>" . htmlspecialchars($fav['product_type']) . "</td>";
                    echo "<td>" . htmlspecialchars($fav['product_status'] ?? 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($fav['favorite_count'] ?? '0') . "</td>";
                    echo "<td>" . htmlspecialchars($fav['created_at']) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        } catch (PDOException $e) {
            echo "<p class='error'>에러: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p>사용자 ID를 가져올 수 없습니다.</p>";
    }
} else {
    echo "<p>로그인이 필요합니다.</p>";
}

// 3. 모든 mno-sim 찜 데이터 확인
echo "<h2>3. 모든 mno-sim 찜 데이터</h2>";
try {
    $stmt = $pdo->query("
        SELECT 
            pf.id,
            pf.product_id,
            pf.user_id,
            pf.product_type,
            pf.created_at,
            p.status as product_status,
            p.favorite_count
        FROM product_favorites pf
        LEFT JOIN products p ON pf.product_id = p.id
        WHERE pf.product_type = 'mno-sim'
        ORDER BY pf.created_at DESC
        LIMIT 20
    ");
    $allFavorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($allFavorites)) {
        echo "<p>찜한 통신사유심 상품이 없습니다.</p>";
    } else {
        echo "<p><strong>전체 찜 데이터:</strong> " . count($allFavorites) . "개</p>";
        echo "<table>";
        echo "<tr><th>ID</th><th>상품 ID</th><th>사용자 ID</th><th>타입</th><th>상품 상태</th><th>찜 수</th><th>찜한 일시</th></tr>";
        foreach ($allFavorites as $fav) {
            $highlight = ($fav['product_id'] == 64) ? "style='background-color:#fff3cd;'" : "";
            echo "<tr {$highlight}>";
            echo "<td>" . htmlspecialchars($fav['id']) . "</td>";
            echo "<td>" . htmlspecialchars($fav['product_id']) . "</td>";
            echo "<td>" . htmlspecialchars($fav['user_id']) . "</td>";
            echo "<td>" . htmlspecialchars($fav['product_type']) . "</td>";
            echo "<td>" . htmlspecialchars($fav['product_status'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($fav['favorite_count'] ?? '0') . "</td>";
            echo "<td>" . htmlspecialchars($fav['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>에러: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// 4. SQL 쿼리 제공
echo "<h2>4. 직접 실행할 SQL 쿼리</h2>";
echo "<p>phpMyAdmin이나 MySQL 클라이언트에서 다음 쿼리를 실행할 수 있습니다:</p>";

echo "<h3>상품 ID 64의 찜 데이터 확인</h3>";
echo "<pre>SELECT 
    pf.id,
    pf.product_id,
    pf.user_id,
    pf.product_type,
    pf.created_at,
    p.status as product_status,
    p.favorite_count
FROM product_favorites pf
LEFT JOIN products p ON pf.product_id = p.id
WHERE pf.product_id = 64 AND pf.product_type = 'mno-sim'
ORDER BY pf.created_at DESC;</pre>";

echo "<h3>모든 mno-sim 찜 데이터 확인</h3>";
echo "<pre>SELECT 
    pf.id,
    pf.product_id,
    pf.user_id,
    pf.product_type,
    pf.created_at,
    p.status as product_status,
    p.favorite_count
FROM product_favorites pf
LEFT JOIN products p ON pf.product_id = p.id
WHERE pf.product_type = 'mno-sim'
ORDER BY pf.created_at DESC;</pre>";

echo "<h3>상품 ID 64의 favorite_count 확인</h3>";
echo "<pre>SELECT id, product_type, favorite_count 
FROM products 
WHERE id = 64;</pre>";

echo "<hr>";
echo "<p><a href='/MVNO/check-db-field.php'>필드 확인 페이지로 이동</a></p>";
echo "<p><a href='/MVNO/mypage/wishlist.php?type=mno-sim'>찜한 통신사유심 내역 페이지로 이동</a></p>";
echo "</body></html>";
?>



