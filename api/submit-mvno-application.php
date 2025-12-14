<?php
/**
 * MVNO 상품 고객 신청 처리 API
 * 
 * 고객이 신청정보를 제출하면:
 * 1. 판매자에게 신청정보 저장
 * 2. redirect_url이 있으면 해당 URL로 리다이렉트
 * 3. redirect_url이 없으면 창 닫기 응답
 */

require_once __DIR__ . '/../includes/data/db-config.php';
require_once __DIR__ . '/../includes/data/product-functions.php';
require_once __DIR__ . '/../includes/data/auth-functions.php';

header('Content-Type: application/json; charset=utf-8');

// 로그인 체크 (비회원 주문 불가)
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => '로그인이 필요합니다. 회원가입 후 주문 신청이 가능합니다.'
    ]);
    exit;
}

// POST 데이터 확인
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'POST 요청만 허용됩니다.'
    ]);
    exit;
}

// 필수 필드 확인
$productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$subscriptionType = isset($_POST['subscription_type']) ? trim($_POST['subscription_type']) : '';

if (empty($productId) || empty($name) || empty($phone)) {
    echo json_encode([
        'success' => false,
        'message' => '필수 정보가 누락되었습니다.'
    ]);
    exit;
}

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('데이터베이스 연결에 실패했습니다.');
    }
    
    // 상품 정보 전체 가져오기 (신청 시점의 상품 정보 전체를 저장하기 위해)
    $stmt = $pdo->prepare("
        SELECT p.seller_id, mvno.*
        FROM products p
        LEFT JOIN product_mvno_details mvno ON p.id = mvno.product_id
        WHERE p.id = ? AND p.product_type = 'mvno' AND p.status = 'active'
        LIMIT 1
    ");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        throw new Exception('상품을 찾을 수 없습니다.');
    }
    
    $sellerId = $product['seller_id'];
    $redirectUrl = !empty($product['redirect_url']) ? trim($product['redirect_url']) : null;
    
    // 로그인한 사용자 정보 가져오기 (이미 로그인 체크 완료)
    $currentUser = getCurrentUser();
    $userId = $currentUser['user_id'] ?? null;
    
    if (!$userId) {
        throw new Exception('로그인 정보를 확인할 수 없습니다.');
    }
    
    // 상품 정보 전체를 배열로 구성 (product_id, id 제외)
    $productSnapshot = [];
    foreach ($product as $key => $value) {
        if ($key !== 'seller_id' && $key !== 'product_id' && $key !== 'id') {
            $productSnapshot[$key] = $value;
        }
    }
    
    // 고객 정보 준비
    $customerData = [
        'user_id' => $userId, // 로그인한 사용자 ID
        'name' => $name,
        'phone' => $phone,
        'email' => $email,
        'address' => null,
        'address_detail' => null,
        'birth_date' => null,
        'gender' => null,
        'additional_info' => [
            'subscription_type' => $subscriptionType, // 가입 형태 저장
            'product_snapshot' => $productSnapshot // 신청 당시 상품 정보 전체 저장 (클레임 처리용)
        ]
    ];
    
    // 신청정보 저장
    $applicationId = addProductApplication($productId, $sellerId, 'mvno', $customerData);
    
    if ($applicationId === false) {
        throw new Exception('신청정보 저장에 실패했습니다.');
    }
    
    // 응답 반환
    echo json_encode([
        'success' => true,
        'message' => '신청이 완료되었습니다.',
        'application_id' => $applicationId,
        'redirect_url' => $redirectUrl
    ]);
    
} catch (Exception $e) {
    error_log("MVNO Application Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}









