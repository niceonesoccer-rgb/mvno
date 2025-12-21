<?php
/**
 * 리뷰 조회 API
 * 사용자가 작성한 리뷰 데이터 가져오기
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/data/product-functions.php';
require_once __DIR__ . '/../includes/data/auth-functions.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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
$productId = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
$productType = isset($_GET['product_type']) ? trim($_GET['product_type']) : 'internet';
$applicationId = isset($_GET['application_id']) ? intval($_GET['application_id']) : null;

// 유효성 검사
if ($productId <= 0) {
    echo json_encode(['success' => false, 'message' => '상품 ID가 올바르지 않습니다.']);
    exit;
}

if (!in_array($productType, ['mvno', 'mno', 'internet'])) {
    echo json_encode(['success' => false, 'message' => '상품 타입이 올바르지 않습니다.']);
    exit;
}

// 리뷰 가져오기
error_log("get-review.php: Request - product_id=$productId, user_id=$userId, product_type=$productType, application_id=" . ($applicationId ?? 'null'));

// product_id와 user_id를 명시적으로 타입 변환
$productId = (int)$productId;
$userId = (string)$userId;

// getUserReview 함수 사용 (application_id 포함)
$review = getUserReview($productId, $userId, $productType, $applicationId);

if ($review !== false) {
    error_log("get-review.php: Review found - id=" . ($review['id'] ?? 'N/A') . ", rating=" . ($review['rating'] ?? 'N/A'));
    echo json_encode([
        'success' => true,
        'has_review' => true,
        'review' => $review
    ]);
    exit;
}

$pdo = getDBConnection();
if (!$pdo) {
    echo json_encode(['success' => false, 'message' => '데이터베이스 연결 실패']);
    exit;
}

// getUserReview로 찾지 못한 경우 직접 쿼리로 조회 (fallback)
// 직접 쿼리로 리뷰 조회
try {
    // kindness_rating과 speed_rating 컬럼 존재 여부 확인
    $hasKindnessRating = false;
    $hasSpeedRating = false;
    try {
        $checkStmt = $pdo->query("SHOW COLUMNS FROM product_reviews LIKE 'kindness_rating'");
        $hasKindnessRating = $checkStmt->rowCount() > 0;
    } catch (PDOException $e) {
        // 컬럼이 없으면 false
    }
    
    try {
        $checkStmt = $pdo->query("SHOW COLUMNS FROM product_reviews LIKE 'speed_rating'");
        $hasSpeedRating = $checkStmt->rowCount() > 0;
    } catch (PDOException $e) {
        // 컬럼이 없으면 false
    }
    
    // 컬럼 존재 여부에 따라 SELECT 쿼리 동적 생성
    $selectFields = "id, product_id, user_id, product_type, rating, title, content, status, created_at, updated_at";
    if ($hasKindnessRating && $hasSpeedRating) {
        $selectFields = "id, product_id, user_id, product_type, rating, kindness_rating, speed_rating, title, content, status, created_at, updated_at";
    } elseif ($hasKindnessRating) {
        $selectFields = "id, product_id, user_id, product_type, rating, kindness_rating, title, content, status, created_at, updated_at";
    } elseif ($hasSpeedRating) {
        $selectFields = "id, product_id, user_id, product_type, rating, speed_rating, title, content, status, created_at, updated_at";
    }
    
    $stmt = $pdo->prepare("
        SELECT 
            $selectFields
        FROM product_reviews
        WHERE product_id = :product_id 
        AND user_id = :user_id 
        AND product_type = :product_type
        ORDER BY 
            CASE WHEN status = 'approved' THEN 0 ELSE 1 END,
            created_at DESC
        LIMIT 1
    ");
    $stmt->execute([
        ':product_id' => $productId,
        ':user_id' => $userId,
        ':product_type' => $productType
    ]);
    
    $review = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$review) {
        // 같은 판매자의 같은 타입 상품 리뷰에서 찾기
        $productStmt = $pdo->prepare("
            SELECT seller_id 
            FROM products 
            WHERE id = :product_id 
            AND product_type = :product_type
            LIMIT 1
        ");
        $productStmt->execute([
            ':product_id' => $productId,
            ':product_type' => $productType
        ]);
        $product = $productStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product && !empty($product['seller_id'])) {
            $sellerId = $product['seller_id'];
            
            $productsStmt = $pdo->prepare("
                SELECT id 
                FROM products 
                WHERE seller_id = :seller_id 
                AND product_type = :product_type
                AND status = 'active'
            ");
            $productsStmt->execute([
                ':seller_id' => $sellerId,
                ':product_type' => $productType
            ]);
            $productIds = $productsStmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($productIds)) {
                $placeholders = implode(',', array_fill(0, count($productIds), '?'));
                $stmt = $pdo->prepare("
                    SELECT 
                        $selectFields
                    FROM product_reviews
                    WHERE product_id IN ($placeholders)
                    AND user_id = ?
                    AND product_type = ?
                    ORDER BY 
                        CASE WHEN status = 'approved' THEN 0 ELSE 1 END,
                        created_at DESC
                    LIMIT 1
                ");
                $params = array_merge($productIds, [$userId, $productType]);
                $stmt->execute($params);
                $review = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        }
    }
} catch (PDOException $e) {
    error_log("get-review.php: Database error - " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '리뷰 조회 중 오류가 발생했습니다: ' . $e->getMessage(),
        'has_review' => false
    ]);
    exit;
}

if (!$review) {
    error_log("get-review.php: Review not found for product_id=$productId, user_id=$userId, product_type=$productType");
    echo json_encode([
        'success' => false,
        'message' => '리뷰를 찾을 수 없습니다.',
        'has_review' => false,
        'debug' => [
            'product_id' => $productId,
            'user_id' => $userId,
            'product_type' => $productType
        ]
    ]);
    exit;
}

error_log("get-review.php: Review found - id=" . ($review['id'] ?? 'N/A') . ", rating=" . ($review['rating'] ?? 'N/A'));
echo json_encode([
    'success' => true,
    'has_review' => true,
    'review' => $review
]);



