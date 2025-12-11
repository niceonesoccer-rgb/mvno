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

header('Content-Type: application/json; charset=utf-8');

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
    
    // 상품 정보 가져오기 (seller_id, redirect_url 확인)
    $stmt = $pdo->prepare("
        SELECT p.seller_id, m.redirect_url
        FROM products p
        LEFT JOIN product_mvno_details m ON p.id = m.product_id
        WHERE p.id = ? AND p.product_type = 'mvno' AND p.status = 'active'
    ");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        throw new Exception('상품을 찾을 수 없습니다.');
    }
    
    $sellerId = $product['seller_id'];
    $redirectUrl = !empty($product['redirect_url']) ? trim($product['redirect_url']) : null;
    
    // 고객 정보 준비
    $customerData = [
        'user_id' => null, // 비회원도 신청 가능
        'name' => $name,
        'phone' => $phone,
        'email' => $email,
        'address' => null,
        'address_detail' => null,
        'birth_date' => null,
        'gender' => null,
        'additional_info' => []
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
