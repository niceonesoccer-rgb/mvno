<?php
/**
 * 리뷰 작성 API
 * 통신사폰(MNO), 알뜰폰(MVNO), 인터넷(Internet) 상품 리뷰 작성
 */

header('Content-Type: application/json');

// 에러 출력 활성화 (개발 환경)
error_reporting(E_ALL);
ini_set('display_errors', 0); // JSON 응답이므로 화면에 출력하지 않음
ini_set('log_errors', 1);

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


// 유효성 검사
if ($productId <= 0) {
    echo json_encode(['success' => false, 'message' => '상품 ID가 올바르지 않습니다.']);
    exit;
}

if (!in_array($productType, ['mvno', 'mno', 'internet'])) {
    echo json_encode(['success' => false, 'message' => '상품 타입이 올바르지 않습니다. (mvno, mno, internet만 가능)']);
    exit;
}

// 인터넷 리뷰의 경우 kindness_rating과 speed_rating으로 rating 계산
if ($productType === 'internet') {
    if ($kindnessRating !== null && $speedRating !== null) {
        // 평균 별점 계산 (반올림)
        $rating = round(($kindnessRating + $speedRating) / 2);
    } elseif ($rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => '인터넷 리뷰는 친절해요와 설치 빨라요 별점을 모두 선택해주세요.']);
        exit;
    }
} else {
    // MVNO/MNO 리뷰의 경우 rating 필수
    if ($rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => '별점은 1~5 사이의 값이어야 합니다.']);
        exit;
    }
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
try {
    if ($reviewId > 0) {
        // 기존 리뷰 수정
        $success = updateProductReview($reviewId, $userId, $rating, $content, $title, $kindnessRating, $speedRating);
        
        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => '리뷰가 수정되었습니다.',
                'review_id' => $reviewId,
                'is_update' => true
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => '리뷰 수정에 실패했습니다.']);
        }
    } else {
        // 새 리뷰 작성
        error_log("submit-review.php: 리뷰 작성 시도 - product_id=$productId, user_id=$userId, application_id=" . ($applicationId ?? 'null') . ", kindness=$kindnessRating, speed=$speedRating");
        $newReviewId = addProductReview($productId, $userId, $productType, $rating, $content, $title, $kindnessRating, $speedRating, $applicationId);
        error_log("submit-review.php: addProductReview 결과 - " . ($newReviewId === false ? 'false' : "ID: $newReviewId"));
        
        if ($newReviewId === false) {
            // 중복 리뷰 체크
            $pdo = getDBConnection();
            if ($pdo) {
                $hasApplicationId = false;
                try {
                    $checkStmt = $pdo->query("SHOW COLUMNS FROM product_reviews LIKE 'application_id'");
                    $hasApplicationId = $checkStmt->rowCount() > 0;
                } catch (PDOException $e) {}
                
                $duplicateCheck = null;
                if ($hasApplicationId && $applicationId !== null && $productType === 'internet') {
                    // 인터넷 리뷰: 같은 application_id에 대한 리뷰가 있는지 확인 (주문별 리뷰)
                    $duplicateCheck = $pdo->prepare("
                        SELECT id FROM product_reviews 
                        WHERE application_id = :application_id 
                        AND user_id = :user_id 
                        AND product_type = :product_type
                        AND status != 'deleted'
                        LIMIT 1
                    ");
                    $duplicateCheck->execute([
                        ':application_id' => $applicationId,
                        ':user_id' => $userId,
                        ':product_type' => $productType
                    ]);
                } else {
                    $duplicateCheck = $pdo->prepare("
                        SELECT id FROM product_reviews 
                        WHERE product_id = :product_id 
                        AND user_id = :user_id 
                        AND product_type = :product_type
                        AND status != 'deleted'
                        LIMIT 1
                    ");
                    $duplicateCheck->execute([
                        ':product_id' => $productId,
                        ':user_id' => $userId,
                        ':product_type' => $productType
                    ]);
                }
                
                if ($duplicateCheck->fetch()) {
                    echo json_encode(['success' => false, 'message' => '이미 리뷰를 작성하셨습니다.']);
                } else {
                    // 서버 로그 확인 필요 - PDO 예외 또는 기타 오류
                    error_log("submit-review.php: addProductReview 실패 - 중복 아님, PDO 예외 가능성");
                    echo json_encode([
                        'success' => false, 
                        'message' => '리뷰 작성에 실패했습니다. 서버 로그를 확인해주세요.',
                        'error' => 'Database error - check server logs'
                    ]);
                }
            } else {
                error_log("submit-review.php: 데이터베이스 연결 실패");
                echo json_encode([
                    'success' => false, 
                    'message' => '데이터베이스 연결에 실패했습니다.',
                    'error' => 'Database connection failed'
                ]);
            }
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'message' => '리뷰가 작성되었습니다.',
            'review_id' => $newReviewId,
            'is_update' => false
        ]);
    }
} catch (Exception $e) {
    error_log("submit-review.php: 예외 발생 - " . $e->getMessage());
    error_log("submit-review.php: Stack trace - " . $e->getTraceAsString());
    echo json_encode([
        'success' => false, 
        'message' => '리뷰 처리 중 오류가 발생했습니다.',
        'error' => $e->getMessage()
    ]);
} catch (Error $e) {
    error_log("submit-review.php: 치명적 오류 - " . $e->getMessage());
    error_log("submit-review.php: Stack trace - " . $e->getTraceAsString());
    echo json_encode([
        'success' => false, 
        'message' => '리뷰 처리 중 오류가 발생했습니다.',
        'error' => $e->getMessage()
    ]);
}
























