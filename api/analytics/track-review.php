<?php
/**
 * 리뷰 추적 API
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
$kindnessRating = floatval($_POST['kindness_rating'] ?? 0);
$speedRating = floatval($_POST['speed_rating'] ?? 0);
$sellerId = $_POST['seller_id'] ?? null;

if (empty($productType) || empty($productId)) {
    echo json_encode(['success' => false, 'message' => '필수 파라미터가 없습니다.']);
    exit;
}

// 평균 별점 계산
$averageRating = ($kindnessRating + $speedRating) / 2;

trackReview($productType, $productId, $averageRating, $sellerId);

echo json_encode(['success' => true]);











