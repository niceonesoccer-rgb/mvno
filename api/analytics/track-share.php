<?php
/**
 * 공유 추적 API
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
$shareMethod = $_POST['share_method'] ?? 'unknown'; // 'kakao', 'facebook', 'twitter', 'link', etc.
$sellerId = $_POST['seller_id'] ?? null;

if (empty($productType) || empty($productId)) {
    echo json_encode(['success' => false, 'message' => '필수 파라미터가 없습니다.']);
    exit;
}

trackShare($productType, $productId, $shareMethod, $sellerId);

echo json_encode(['success' => true]);











