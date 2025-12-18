<?php
/**
 * 상품 조회수 증가 API
 */

require_once __DIR__ . '/../includes/data/db-config.php';
require_once __DIR__ . '/../includes/data/product-functions.php';

header('Content-Type: application/json');

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST 요청만 허용됩니다.']);
    exit;
}

// JSON 데이터 읽기
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// product_id 확인
if (!isset($data['product_id']) || empty($data['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'product_id가 필요합니다.']);
    exit;
}

$productId = intval($data['product_id']);

if ($productId <= 0) {
    echo json_encode(['success' => false, 'message' => '유효하지 않은 product_id입니다.']);
    exit;
}

// 조회수 증가
$result = incrementProductView($productId);

if ($result) {
    echo json_encode(['success' => true, 'message' => '조회수가 증가되었습니다.']);
} else {
    echo json_encode(['success' => false, 'message' => '조회수 증가에 실패했습니다.']);
}






