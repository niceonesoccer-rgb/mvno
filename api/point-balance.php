<?php
/**
 * 포인트 잔액 조회 API
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/data/point-settings.php';
require_once __DIR__ . '/../includes/data/auth-functions.php';

// 실서비스: 세션 기반 사용자
$currentUser = getCurrentUser();
$user_id = $currentUser['user_id'] ?? null;
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

$user_point = getUserPoint($user_id);

echo json_encode([
    'success' => true,
    'balance' => $user_point['balance'] ?? 0,
    'history_count' => count($user_point['history'] ?? [])
]);


















