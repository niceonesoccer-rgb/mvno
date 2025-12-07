<?php
/**
 * 찜 추적 API
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/data/analytics-functions.php';
require_once __DIR__ . '/../../includes/data/auth-functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
    exit;
}

$productType = $_POST['product_type'] ?? '';
$productId = $_POST['product_id'] ?? '';
$action = $_POST['action'] ?? 'add'; // 'add' or 'remove'
$sellerId = $_POST['seller_id'] ?? null;

if (empty($productType) || empty($productId)) {
    echo json_encode(['success' => false, 'message' => '필수 파라미터가 없습니다.']);
    exit;
}

// 판매자 ID가 없으면 상품에서 찾기 (실제 구현 시)
// $sellerId = getSellerIdByProduct($productType, $productId);

trackFavorite($productType, $productId, $sellerId, $action);

echo json_encode(['success' => true]);










