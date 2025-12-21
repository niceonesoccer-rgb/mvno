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
    $rating = (int)$review['rating'];
    $kindnessRating = $review['kindness_rating'] !== null ? (int)$review['kindness_rating'] : null;
    $speedRating = $review['speed_rating'] !== null ? (int)$review['speed_rating'] : null;
    
    // 리뷰 삭제 (status를 'deleted'로 변경)
    $updateStmt = $pdo->prepare("
        UPDATE product_reviews
        SET status = 'deleted',
            updated_at = NOW()
        WHERE id = :review_id
    ");
    $updateStmt->execute([':review_id' => $reviewId]);
    
    // 하이브리드 방식: 실시간 통계에서 삭제된 리뷰의 평점 제거 (처음 작성 시점 값은 변경하지 않음)
    try {
        $updateStatsSql = "
            UPDATE product_review_statistics 
            SET 
                total_rating_sum = total_rating_sum - :rating,
                total_review_count = GREATEST(total_review_count - 1, 0)";
        
        $updateStatsParams = [
            ':product_id' => $productId,
            ':rating' => $rating
        ];
        
        if ($kindnessRating !== null) {
            $updateStatsSql .= ",
                kindness_rating_sum = kindness_rating_sum - :kindness_rating,
                kindness_review_count = GREATEST(kindness_review_count - 1, 0)";
            $updateStatsParams[':kindness_rating'] = $kindnessRating;
        }
        
        if ($speedRating !== null) {
            $updateStatsSql .= ",
                speed_rating_sum = speed_rating_sum - :speed_rating,
                speed_review_count = GREATEST(speed_review_count - 1, 0)";
            $updateStatsParams[':speed_rating'] = $speedRating;
        }
        
        $updateStatsSql .= " WHERE product_id = :product_id";
        
        $updateStatsStmt = $pdo->prepare($updateStatsSql);
        $updateStatsStmt->execute($updateStatsParams);
    } catch (PDOException $e) {
        error_log("delete-review.php: 실시간 통계 업데이트 실패 - " . $e->getMessage());
        // 통계 업데이트 실패는 치명적이지 않으므로 계속 진행
    }
    
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


