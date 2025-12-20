<?php
/**
 * 중복 리뷰 확인 API (디버깅용)
 * 같은 상품에 대해 여러 리뷰가 있는지 확인
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/data/db-config.php';
require_once __DIR__ . '/../includes/data/auth-functions.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 로그인 확인
$userId = getCurrentUserId();
if (!$userId) {
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

$productId = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

$pdo = getDBConnection();
if (!$pdo) {
    echo json_encode(['success' => false, 'message' => '데이터베이스 연결 실패']);
    exit;
}

$result = [
    'product_id' => $productId,
    'user_id' => $userId,
    'reviews' => []
];

// 해당 사용자의 같은 상품에 대한 모든 리뷰 조회
$stmt = $pdo->prepare("
    SELECT 
        id,
        product_id,
        user_id,
        product_type,
        rating,
        kindness_rating,
        speed_rating,
        content,
        status,
        created_at,
        updated_at
    FROM product_reviews
    WHERE product_id = ? 
    AND user_id = ? 
    AND product_type = 'internet'
    ORDER BY created_at DESC
");
$stmt->execute([$productId, $userId]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

$result['reviews'] = $reviews;
$result['count'] = count($reviews);

// 각 리뷰의 내용 비교
if (count($reviews) > 1) {
    $result['has_duplicates'] = true;
    $result['comparison'] = [];
    
    for ($i = 0; $i < count($reviews) - 1; $i++) {
        $current = $reviews[$i];
        $next = $reviews[$i + 1];
        
        $result['comparison'][] = [
            'review_' . ($i + 1) . '_vs_' . ($i + 2) => [
                'same_content' => $current['content'] === $next['content'],
                'same_rating' => $current['rating'] == $next['rating'],
                'same_kindness' => ($current['kindness_rating'] ?? null) == ($next['kindness_rating'] ?? null),
                'same_speed' => ($current['speed_rating'] ?? null) == ($next['speed_rating'] ?? null),
                'review_1_id' => $current['id'],
                'review_1_content' => substr($current['content'], 0, 50),
                'review_2_id' => $next['id'],
                'review_2_content' => substr($next['content'], 0, 50)
            ]
        ];
    }
} else {
    $result['has_duplicates'] = false;
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
