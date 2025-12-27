<?php
/**
 * 리뷰 존재 여부 확인 API (디버깅용)
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
$applicationId = isset($_GET['application_id']) ? intval($_GET['application_id']) : 0;

$pdo = getDBConnection();
if (!$pdo) {
    echo json_encode(['success' => false, 'message' => '데이터베이스 연결 실패']);
    exit;
}

$result = [
    'product_id' => $productId,
    'application_id' => $applicationId,
    'user_id' => $userId,
    'checks' => []
];

// 1. application_id로 실제 product_id 확인
if ($applicationId > 0) {
    $stmt = $pdo->prepare("SELECT product_id FROM product_applications WHERE id = ?");
    $stmt->execute([$applicationId]);
    $app = $stmt->fetch(PDO::FETCH_ASSOC);
    $result['checks']['application_product_id'] = $app['product_id'] ?? null;
}

// 2. product_id로 리뷰 확인 (status 무관)
$stmt = $pdo->prepare("
    SELECT id, product_id, user_id, product_type, rating, status, created_at 
    FROM product_reviews 
    WHERE product_id = ? AND user_id = ? AND product_type = 'internet'
    ORDER BY created_at DESC
");
$stmt->execute([$productId, $userId]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
$result['checks']['reviews_by_product_id'] = $reviews;

// 3. user_id로 모든 인터넷 리뷰 확인
$stmt = $pdo->prepare("
    SELECT id, product_id, user_id, product_type, rating, status, created_at 
    FROM product_reviews 
    WHERE user_id = ? AND product_type = 'internet'
    ORDER BY created_at DESC
");
$stmt->execute([$userId]);
$allReviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
$result['checks']['all_user_reviews'] = $allReviews;

// 4. product_id로 모든 리뷰 확인 (user_id 무관)
$stmt = $pdo->prepare("
    SELECT id, product_id, user_id, product_type, rating, status, created_at 
    FROM product_reviews 
    WHERE product_id = ? AND product_type = 'internet'
    ORDER BY created_at DESC
");
$stmt->execute([$productId]);
$productReviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
$result['checks']['all_product_reviews'] = $productReviews;

// 5. has_review 확인 로직과 동일한 조건으로 확인 (status = 'approved')
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM product_reviews 
    WHERE product_id = ? AND user_id = ? AND product_type = 'internet' AND status = 'approved'
");
$stmt->execute([$productId, $userId]);
$hasReviewCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
$result['checks']['has_review_count'] = $hasReviewCount;
$result['checks']['has_review'] = $hasReviewCount > 0;

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);








