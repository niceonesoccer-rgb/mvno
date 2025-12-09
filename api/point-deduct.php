<?php
/**
 * 포인트 차감 API
 * POST 요청으로 포인트 차감 처리
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/data/point-settings.php';

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// 입력 데이터 받기
$input = json_decode(file_get_contents('php://input'), true);

$user_id = $input['user_id'] ?? 'default'; // 실제로는 세션에서 가져옴
$type = $input['type'] ?? ''; // 'mvno', 'mno', 'internet'
$item_id = $input['item_id'] ?? 0;
$amount = intval($input['amount'] ?? 0);
$description = $input['description'] ?? '';

// 유효성 검사
if (!in_array($type, ['mvno', 'mno', 'internet'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid type']);
    exit;
}

if ($amount <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid amount']);
    exit;
}

// 포인트 차감
$result = deductPoint($user_id, $amount, $type, $item_id, $description);

if ($result['success']) {
    echo json_encode([
        'success' => true,
        'balance' => $result['balance'],
        'history_item' => $result['history_item']
    ]);
} else {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $result['message']
    ]);
}

















