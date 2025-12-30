<?php
/**
 * 빠른 찜 데이터 확인
 * 상품 ID 64가 저장되었는지 간단히 확인
 */

require_once __DIR__ . '/includes/data/db-config.php';
require_once __DIR__ . '/includes/data/auth-functions.php';

$pdo = getDBConnection();
if (!$pdo) {
    die("DB 연결 실패");
}

echo "<h2>상품 ID 64 찜 데이터 확인</h2>";

// 상품 ID 64의 찜 데이터 확인
$stmt = $pdo->prepare("
    SELECT 
        pf.id,
        pf.product_id,
        pf.user_id,
        pf.product_type,
        pf.created_at,
        p.favorite_count
    FROM product_favorites pf
    LEFT JOIN products p ON pf.product_id = p.id
    WHERE pf.product_id = :product_id AND pf.product_type = 'mno-sim'
    ORDER BY pf.created_at DESC
");
$stmt->execute([':product_id' => 64]);
$favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($favorites)) {
    echo "<p style='color:red;'>❌ 상품 ID 64에 대한 찜 데이터가 없습니다.</p>";
    echo "<p>찜 버튼을 다시 클릭해보세요.</p>";
} else {
    echo "<p style='color:green;'>✅ 찜 데이터 " . count($favorites) . "개 발견!</p>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>상품 ID</th><th>사용자 ID</th><th>타입</th><th>찜 수</th><th>찜한 일시</th></tr>";
    foreach ($favorites as $fav) {
        echo "<tr>";
        echo "<td>" . $fav['id'] . "</td>";
        echo "<td>" . $fav['product_id'] . "</td>";
        echo "<td>" . $fav['user_id'] . "</td>";
        echo "<td>" . $fav['product_type'] . "</td>";
        echo "<td>" . $fav['favorite_count'] . "</td>";
        echo "<td>" . $fav['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 현재 사용자 확인
if (function_exists('isLoggedIn') && isLoggedIn()) {
    $currentUser = getCurrentUser();
    $userId = $currentUser['user_id'] ?? null;
    
    if ($userId) {
        echo "<h2>현재 사용자 (" . htmlspecialchars($userId) . ")의 찜 데이터</h2>";
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM product_favorites 
            WHERE user_id = :user_id AND product_type = 'mno-sim'
        ");
        $stmt->execute([':user_id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>찜한 통신사유심 상품: " . $result['count'] . "개</p>";
    }
}

echo "<hr>";
echo "<p><a href='/MVNO/mypage/wishlist.php?type=mno-sim'>마이페이지 찜 목록 보기</a></p>";
?>





