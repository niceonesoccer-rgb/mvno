<?php
/**
 * 찜 추적 API
 * 실제 DB에 찜 정보 저장 및 favorite_count 업데이트
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/data/analytics-functions.php';
require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/product-functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
    exit;
}

// 로그인 체크
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

$productType = $_POST['product_type'] ?? '';
$productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$action = $_POST['action'] ?? 'add'; // 'add' or 'remove'
$sellerId = $_POST['seller_id'] ?? null;

if (empty($productType) || empty($productId)) {
    echo json_encode(['success' => false, 'message' => '필수 파라미터가 없습니다.']);
    exit;
}

$currentUser = getCurrentUser();
$userId = $currentUser['user_id'] ?? null;

if (!$userId) {
    echo json_encode(['success' => false, 'message' => '로그인 정보를 확인할 수 없습니다.']);
    exit;
}

// 실제 DB에 찜 정보 저장/삭제
$isFavorite = ($action === 'add');
$result = toggleProductFavorite($productId, $userId, $productType, $isFavorite);

if ($result) {
    // 분석 추적도 함께 수행
    trackFavorite($productType, $productId, $sellerId, $action);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => '찜 처리에 실패했습니다.']);
}


















