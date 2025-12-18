<?php
/**
 * 현재 로그인한 사용자 정보 가져오기 API
 */

require_once __DIR__ . '/../includes/data/auth-functions.php';

header('Content-Type: application/json; charset=utf-8');

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 로그인 확인
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => '로그인이 필요합니다.'
    ]);
    exit;
}

$currentUser = getCurrentUser();
if (!$currentUser) {
    echo json_encode([
        'success' => false,
        'message' => '사용자 정보를 찾을 수 없습니다.'
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'name' => $currentUser['name'] ?? $currentUser['user_name'] ?? '',
    'phone' => $currentUser['phone'] ?? '',
    'email' => $currentUser['email'] ?? ''
]);










