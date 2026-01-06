<?php
/**
 * 관리자용 사용자 정보 가져오기 API
 * 관리자 권한이 있는 경우에만 사용자 정보를 반환합니다.
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';

header('Content-Type: application/json; charset=utf-8');

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 관리자 권한 체크
if (!isAdmin()) {
    echo json_encode([
        'success' => false,
        'message' => '관리자 권한이 필요합니다.'
    ]);
    exit;
}

// 사용자 ID 가져오기
$userId = $_GET['user_id'] ?? '';

if (empty($userId)) {
    echo json_encode([
        'success' => false,
        'message' => '사용자 ID가 필요합니다.'
    ]);
    exit;
}

// 사용자 정보 가져오기
$user = getUserById($userId);

if (!$user) {
    echo json_encode([
        'success' => false,
        'message' => '사용자를 찾을 수 없습니다.'
    ]);
    exit;
}

// 민감한 정보는 제외하고 필요한 정보만 반환
echo json_encode([
    'success' => true,
    'user' => [
        'user_id' => $user['user_id'] ?? '',
        'name' => $user['name'] ?? '',
        'email' => $user['email'] ?? '',
        'phone' => $user['phone'] ?? '',
        'role' => $user['role'] ?? 'user',
        'address' => $user['address'] ?? '',
        'address_detail' => $user['address_detail'] ?? '',
        'birth_date' => $user['birth_date'] ?? '',
        'gender' => $user['gender'] ?? '',
        'created_at' => $user['created_at'] ?? ''
    ]
]);
