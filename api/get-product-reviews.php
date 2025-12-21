<?php
/**
 * 상품별 리뷰 조회 API (관리자/판매자용)
 * 특정 상품의 리뷰만 가져옴 (통합하지 않음)
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/data/plan-data.php';
require_once __DIR__ . '/../includes/data/auth-functions.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 권한 확인 (관리자 또는 판매자)
$currentUser = getCurrentUser();
if (!$currentUser || !in_array($currentUser['role'], ['admin', 'seller'])) {
    echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    exit;
}

// 판매자인 경우 자신의 상품만 조회 가능
$isSeller = $currentUser['role'] === 'seller';
$sellerId = $isSeller ? $currentUser['user_id'] : null;

// 입력 데이터 받기
$productId = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
$productType = isset($_GET['product_type']) ? trim($_GET['product_type']) : '';

// 유효성 검사
if ($productId <= 0) {
    echo json_encode(['success' => false, 'message' => '상품 ID가 올바르지 않습니다.']);
    exit;
}

if (!in_array($productType, ['mvno', 'mno', 'internet'])) {
    echo json_encode(['success' => false, 'message' => '상품 타입이 올바르지 않습니다.']);
    exit;
}

// 판매자인 경우 상품 소유권 확인
if ($isSeller && $sellerId) {
    require_once __DIR__ . '/../includes/data/db-config.php';
    $pdo = getDBConnection();
    if ($pdo) {
        $stmt = $pdo->prepare("
            SELECT seller_id 
            FROM products 
            WHERE id = :product_id AND product_type = :product_type
        ");
        $stmt->execute([
            ':product_id' => $productId,
            ':product_type' => $productType
        ]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product || (string)$product['seller_id'] !== (string)$sellerId) {
            echo json_encode(['success' => false, 'message' => '해당 상품에 대한 권한이 없습니다.']);
            exit;
        }
    }
}

// 리뷰 가져오기
$reviews = getSingleProductReviews($productId, $productType, 100, 'created_desc');
$averageRating = getSingleProductAverageRating($productId, $productType);
$reviewCount = getSingleProductReviewCount($productId, $productType);

// 디버깅: 리뷰 데이터 확인 (첫 번째 리뷰만)
if (!empty($reviews)) {
    error_log("Review data sample: " . json_encode($reviews[0], JSON_UNESCAPED_UNICODE));
}

echo json_encode([
    'success' => true,
    'data' => [
        'reviews' => $reviews,
        'average_rating' => $averageRating,
        'review_count' => $reviewCount
    ]
], JSON_UNESCAPED_UNICODE);



