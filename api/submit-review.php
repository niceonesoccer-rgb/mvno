<?php
/**
 * 리뷰 작성 API
 * 통신사폰(MNO), 알뜰폰(MVNO), 인터넷(Internet) 상품 리뷰 작성
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/data/product-functions.php';
require_once __DIR__ . '/../includes/data/auth-functions.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
    exit;
}

// 로그인 확인
$userId = getCurrentUserId();
if (!$userId) {
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

// 입력 데이터 받기
$productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$productType = isset($_POST['product_type']) ? trim($_POST['product_type']) : '';
$rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
$content = isset($_POST['content']) ? trim($_POST['content']) : '';
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$reviewId = isset($_POST['review_id']) ? intval($_POST['review_id']) : 0; // 수정 모드일 때 리뷰 ID
$kindnessRating = isset($_POST['kindness_rating']) ? intval($_POST['kindness_rating']) : null; // 인터넷 리뷰용
$speedRating = isset($_POST['speed_rating']) ? intval($_POST['speed_rating']) : null; // 인터넷 리뷰용
$applicationId = isset($_POST['application_id']) ? intval($_POST['application_id']) : null; // 신청 ID

error_log("submit-review.php: Received - kindness_rating=" . ($kindnessRating ?? 'null') . ", speed_rating=" . ($speedRating ?? 'null') . ", review_id=$reviewId, application_id=" . ($applicationId ?? 'null'));

// 유효성 검사
if ($productId <= 0) {
    echo json_encode(['success' => false, 'message' => '상품 ID가 올바르지 않습니다.']);
    exit;
}

if (!in_array($productType, ['mvno', 'mno', 'internet'])) {
    echo json_encode(['success' => false, 'message' => '상품 타입이 올바르지 않습니다. (mvno, mno, internet만 가능)']);
    exit;
}

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => '별점은 1~5 사이의 값이어야 합니다.']);
    exit;
}

if (empty($content)) {
    echo json_encode(['success' => false, 'message' => '리뷰 내용을 입력해주세요.']);
    exit;
}

if (mb_strlen($content) > 1000) {
    echo json_encode(['success' => false, 'message' => '리뷰 내용은 1000자 이하여야 합니다.']);
    exit;
}

// 리뷰 수정 모드인지 확인
if ($reviewId > 0) {
    // 기존 리뷰 수정
    error_log("submit-review.php: Updating review $reviewId with kindness_rating=$kindnessRating, speed_rating=$speedRating");
    $success = updateProductReview($reviewId, $userId, $rating, $content, $title, $kindnessRating, $speedRating);
    
    if ($success) {
        error_log("submit-review.php: Review updated successfully");
        echo json_encode([
            'success' => true,
            'message' => '리뷰가 수정되었습니다.',
            'review_id' => $reviewId,
            'is_update' => true
        ]);
    } else {
        error_log("submit-review.php: Review update failed");
        echo json_encode(['success' => false, 'message' => '리뷰 수정에 실패했습니다.']);
    }
} else {
    // 새 리뷰 작성
    error_log("submit-review.php: Creating new review with kindness_rating=$kindnessRating, speed_rating=$speedRating, application_id=" . ($applicationId ?? 'null'));
    $newReviewId = addProductReview($productId, $userId, $productType, $rating, $content, $title, $kindnessRating, $speedRating, $applicationId);
    
    if ($newReviewId === false) {
        error_log("submit-review.php: Review creation failed");
        echo json_encode(['success' => false, 'message' => '리뷰 작성에 실패했습니다.']);
        exit;
    }
    
    error_log("submit-review.php: Review created successfully with ID $newReviewId");
    echo json_encode([
        'success' => true,
        'message' => '리뷰가 작성되었습니다.',
        'review_id' => $newReviewId,
        'is_update' => false
    ]);
}























