<?php
/**
 * 상품 등록 API
 * 판매자 권한 체크 후 상품 등록 처리
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

// 게시판 타입 확인
$boardType = $_POST['board_type'] ?? '';
$allowedTypes = ['mvno', 'mno', 'internet', 'mno-sim'];

if (!in_array($boardType, $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => '유효하지 않은 게시판 타입입니다.']);
    exit;
}

// 권한 체크
if (!hasSellerPermission($currentUser['user_id'], $boardType)) {
    echo json_encode(['success' => false, 'message' => '해당 게시판에 등록할 권한이 없습니다. 관리자에게 권한을 요청하세요.']);
    exit;
}

// 여기서 실제 상품 등록 로직을 구현하세요
// 예시:
$productData = [
    'seller_id' => $currentUser['user_id'],
    'board_type' => $boardType,
    'provider' => $_POST['provider'] ?? '',
    'plan_name' => $_POST['plan_name'] ?? '',
    'monthly_fee' => $_POST['monthly_fee'] ?? 0,
    'speed' => $_POST['speed'] ?? '',
    'created_at' => date('Y-m-d H:i:s')
];

// TODO: 실제 상품 데이터 저장 로직 구현
// 예: saveProductData($productData);

echo json_encode([
    'success' => true, 
    'message' => '상품이 등록되었습니다.',
    'product' => $productData
]);


















