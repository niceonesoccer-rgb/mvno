<?php
/**
 * 포인트 잔액 조회 API
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/data/point-settings.php';

$user_id = $_GET['user_id'] ?? 'default'; // 실제로는 세션에서 가져옴

$user_point = getUserPoint($user_id);

echo json_encode([
    'success' => true,
    'balance' => $user_point['balance'] ?? 0,
    'history_count' => count($user_point['history'] ?? [])
]);











