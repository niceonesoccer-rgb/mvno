<?php
/**
 * 알뜰폰 상품 등록 API
 * 알뜰폰(MVNO) 전용 - 다른 상품 타입과 완전히 분리됨
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

// 알뜰폰 권한 체크 (MVNO만)
if (!hasSellerPermission($currentUser['user_id'], 'mvno')) {
    echo json_encode(['success' => false, 'message' => '알뜰폰 등록 권한이 없습니다. 관리자에게 권한을 요청하세요.']);
    exit;
}

// 알뜰폰 상품 데이터 수집
$productData = [
    'seller_id' => $currentUser['user_id'],
    'board_type' => 'mvno', // 고정값
    'provider' => $_POST['provider'] ?? '',
    'service_type' => $_POST['service_type'] ?? '',
    'plan_name' => $_POST['plan_name'] ?? '',
    'contract_period' => $_POST['contract_period'] ?? '',
    'contract_period_days' => $_POST['contract_period_days'] ?? '',
    'discount_period' => $_POST['discount_period'] ?? '',
    'price_main' => $_POST['price_main'] ?? 0,
    'price_after' => $_POST['price_after'] ?? 0,
    'data_amount' => $_POST['data_amount'] ?? '',
    'data_amount_value' => $_POST['data_amount_value'] ?? '',
    'data_unit' => $_POST['data_unit'] ?? '',
    'data_exhausted' => $_POST['data_exhausted'] ?? '',
    'data_exhausted_value' => $_POST['data_exhausted_value'] ?? '',
    'call_type' => $_POST['call_type'] ?? '',
    'call_amount' => $_POST['call_amount'] ?? '',
    'additional_call_type' => $_POST['additional_call_type'] ?? '',
    'additional_call' => $_POST['additional_call'] ?? '',
    'sms_type' => $_POST['sms_type'] ?? '',
    'sms_amount' => $_POST['sms_amount'] ?? '',
    'mobile_hotspot' => $_POST['mobile_hotspot'] ?? '',
    'mobile_hotspot_value' => $_POST['mobile_hotspot_value'] ?? '',
    'regular_sim_available' => $_POST['regular_sim_available'] ?? '',
    'regular_sim_price' => $_POST['regular_sim_price'] ?? '',
    'nfc_sim_available' => $_POST['nfc_sim_available'] ?? '',
    'nfc_sim_price' => $_POST['nfc_sim_price'] ?? '',
    'esim_available' => $_POST['esim_available'] ?? '',
    'esim_price' => $_POST['esim_price'] ?? '',
    'over_data_price' => $_POST['over_data_price'] ?? '',
    'over_voice_price' => $_POST['over_voice_price'] ?? '',
    'over_video_price' => $_POST['over_video_price'] ?? '',
    'over_sms_price' => $_POST['over_sms_price'] ?? '',
    'over_lms_price' => $_POST['over_lms_price'] ?? '',
    'over_mms_price' => $_POST['over_mms_price'] ?? '',
    'promotion_title' => $_POST['promotion_title'] ?? '',
    'promotions' => $_POST['promotions'] ?? [],
    'benefits' => $_POST['benefits'] ?? [],
    'created_at' => date('Y-m-d H:i:s')
];

// TODO: 실제 알뜰폰 상품 데이터 저장 로직 구현
// 예: saveMvnoProductData($productData);

echo json_encode([
    'success' => true, 
    'message' => '알뜰폰 상품이 등록되었습니다.',
    'product' => $productData
]);
