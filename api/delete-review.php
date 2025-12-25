<?php
/**
 * 리뷰 삭제 API
 * POST /api/delete-review.php
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/data/auth-functions.php';
require_once __DIR__ . '/../includes/data/db-config.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'POST 메서드만 허용됩니다.'
    ]);
    exit;
}

// 로그인 확인
$userId = getCurrentUserId();
if (!$userId) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => '로그인이 필요합니다.'
    ]);
    exit;
}

// 입력 데이터 받기
$reviewId = isset($_POST['review_id']) ? intval($_POST['review_id']) : 0;
$productType = isset($_POST['product_type']) ? trim($_POST['product_type']) : '';

// 유효성 검사
if ($reviewId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => '리뷰 ID가 올바르지 않습니다.'
    ]);
    exit;
}

if (!in_array($productType, ['mvno', 'mno', 'internet'])) {
    echo json_encode([
        'success' => false,
        'message' => '상품 타입이 올바르지 않습니다.'
    ]);
    exit;
}

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('데이터베이스 연결에 실패했습니다.');
    }
    
    // 리뷰 존재 여부 및 작성자 확인 (평점 정보도 함께 가져오기)
    $stmt = $pdo->prepare("
        SELECT id, user_id, product_id, product_type, application_id, rating, kindness_rating, speed_rating
        FROM product_reviews
        WHERE id = :review_id 
        AND user_id = :user_id
        AND product_type = :product_type
        AND status != 'deleted'
    ");
    $stmt->execute([
        ':review_id' => $reviewId,
        ':user_id' => $userId,
        ':product_type' => $productType
    ]);
    $review = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$review) {
        echo json_encode([
            'success' => false,
            'message' => '리뷰를 찾을 수 없거나 삭제할 권한이 없습니다.'
        ]);
        exit;
    }
    
    $productId = $review['product_id'];
    
    // 리뷰 완전 삭제 (물리적 삭제 - 데이터베이스에서 완전히 제거)
    $deleteStmt = $pdo->prepare("
        DELETE FROM product_reviews
        WHERE id = :review_id
    ");
    $deleteStmt->execute([':review_id' => $reviewId]);
    
    // 통계 업데이트는 트리거(trg_update_review_statistics_on_delete)가 자동으로 처리
    // 트리거가 삭제된 리뷰의 통계를 자동으로 제거하여 통계 업데이트
    
    echo json_encode([
        'success' => true,
        'message' => '리뷰가 삭제되었습니다.'
    ]);
    
} catch (Exception $e) {
    error_log("Error deleting review: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '리뷰 삭제 중 오류가 발생했습니다.'
    ]);
}






