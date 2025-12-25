<?php
/**
 * 통신사유심 찜 기능 확인 스크립트
 * 데이터베이스 마이그레이션이 제대로 실행되었는지 확인
 */

require_once __DIR__ . '/includes/data/db-config.php';
require_once __DIR__ . '/includes/data/auth-functions.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>통신사유심 찜 기능 확인</h1>";

$pdo = getDBConnection();
if (!$pdo) {
    echo "<p style='color: red;'>데이터베이스 연결 실패</p>";
    exit;
}

// 1. product_favorites 테이블의 product_type 컬럼 확인
echo "<h2>1. product_favorites 테이블 구조 확인</h2>";
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM product_favorites LIKE 'product_type'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($column) {
        echo "<p><strong>product_type 컬럼 타입:</strong> " . htmlspecialchars($column['Type']) . "</p>";
        
        // mno-sim이 포함되어 있는지 확인
        if (strpos($column['Type'], 'mno-sim') !== false) {
            echo "<p style='color: green;'>✓ mno-sim 타입이 지원됩니다.</p>";
        } else {
            echo "<p style='color: red;'>✗ mno-sim 타입이 지원되지 않습니다. 마이그레이션이 필요합니다.</p>";
            echo "<p>다음 SQL을 실행하세요:</p>";
            echo "<pre>ALTER TABLE `product_favorites` MODIFY COLUMN `product_type` ENUM('mvno', 'mno', 'internet', 'mno-sim') NOT NULL COMMENT '상품 타입';</pre>";
        }
    } else {
        echo "<p style='color: red;'>product_type 컬럼을 찾을 수 없습니다.</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>에러: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// 2. 실제 찜 데이터 확인
echo "<h2>2. 찜한 통신사유심 상품 확인</h2>";
try {
    $stmt = $pdo->query("
        SELECT 
            pf.id,
            pf.product_id,
            pf.user_id,
            pf.product_type,
            pf.created_at,
            p.status as product_status
        FROM product_favorites pf
        LEFT JOIN products p ON pf.product_id = p.id
        WHERE pf.product_type = 'mno-sim'
        ORDER BY pf.created_at DESC
        LIMIT 10
    ");
    $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($favorites)) {
        echo "<p>찜한 통신사유심 상품이 없습니다.</p>";
    } else {
        echo "<p><strong>찜한 통신사유심 상품 개수:</strong> " . count($favorites) . "개</p>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>상품 ID</th><th>사용자 ID</th><th>타입</th><th>상품 상태</th><th>찜한 일시</th></tr>";
        foreach ($favorites as $fav) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($fav['id']) . "</td>";
            echo "<td>" . htmlspecialchars($fav['product_id']) . "</td>";
            echo "<td>" . htmlspecialchars($fav['user_id']) . "</td>";
            echo "<td>" . htmlspecialchars($fav['product_type']) . "</td>";
            echo "<td>" . htmlspecialchars($fav['product_status'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($fav['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>에러: " . htmlspecialchars($e->getMessage()) . "</p>";
    if (strpos($e->getMessage(), 'mno-sim') !== false || strpos($e->getMessage(), 'ENUM') !== false) {
        echo "<p style='color: red;'><strong>데이터베이스 마이그레이션이 필요합니다!</strong></p>";
    }
}

// 3. 로그인한 사용자의 찜 목록 확인
echo "<h2>3. 현재 로그인한 사용자의 찜 목록</h2>";
if (function_exists('isLoggedIn') && isLoggedIn()) {
    $currentUser = getCurrentUser();
    $userId = $currentUser['user_id'] ?? null;
    
    if ($userId) {
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    pf.product_id,
                    pf.product_type,
                    pf.created_at,
                    p.status as product_status
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
                echo "<p><strong>찜한 통신사유심 상품 개수:</strong> " . count($userFavorites) . "개</p>";
                echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
                echo "<tr><th>상품 ID</th><th>타입</th><th>상품 상태</th><th>찜한 일시</th></tr>";
                foreach ($userFavorites as $fav) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($fav['product_id']) . "</td>";
                    echo "<td>" . htmlspecialchars($fav['product_type']) . "</td>";
                    echo "<td>" . htmlspecialchars($fav['product_status'] ?? 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($fav['created_at']) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        } catch (PDOException $e) {
            echo "<p style='color: red;'>에러: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p>사용자 ID를 가져올 수 없습니다.</p>";
    }
} else {
    echo "<p>로그인이 필요합니다.</p>";
}

echo "<hr>";
echo "<p><a href='/MVNO/mypage/wishlist.php?type=mno-sim'>찜한 통신사유심 내역 페이지로 이동</a></p>";
?>

