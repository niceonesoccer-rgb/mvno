<?php
/**
 * 인터넷 상품 등록 API
 * 인터넷 전용 - 다른 상품 타입과 완전히 분리됨
 */

require_once __DIR__ . '/../includes/data/auth-functions.php';

header('Content-Type: application/json');

// 로그인 체크
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

$currentUser = getCurrentUser();
if (!$currentUser || $currentUser['role'] !== 'seller') {
    echo json_encode(['success' => false, 'message' => '판매자만 상품을 등록할 수 있습니다.']);
    exit;
}

// 판매자 승인 체크
if (!isSellerApproved()) {
    echo json_encode(['success' => false, 'message' => '판매자 승인이 필요합니다.']);
    exit;
}

// 인터넷 권한 체크 (Internet만)
if (!hasSellerPermission($currentUser['user_id'], 'internet')) {
    echo json_encode(['success' => false, 'message' => '인터넷 등록 권한이 없습니다. 관리자에게 권한을 요청하세요.']);
    exit;
}

// 인터넷 상품 데이터 수집
$productData = [
    'seller_id' => $currentUser['user_id'],
    'board_type' => 'internet', // 고정값
    'provider' => $_POST['provider'] ?? '',
    'plan_name' => $_POST['plan_name'] ?? '',
    'speed' => $_POST['speed'] ?? '',
    'tv_channels' => $_POST['tv_channels'] ?? '',
    'price_main' => $_POST['price_main'] ?? 0,
    'installation_fee' => $_POST['installation_fee'] ?? 0,
    'discount_period' => $_POST['discount_period'] ?? '',
    'features' => $_POST['features'] ?? [],
    'gifts' => $_POST['gifts'] ?? [],
    'description' => $_POST['description'] ?? '',
    'created_at' => date('Y-m-d H:i:s')
];

// TODO: 실제 인터넷 상품 데이터 저장 로직 구현
// 예: saveInternetProductData($productData);

echo json_encode([
    'success' => true, 
    'message' => '인터넷 상품이 등록되었습니다.',
    'product' => $productData
]);


