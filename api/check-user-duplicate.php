<?php
/**
 * 일반 회원 아이디 중복확인 API
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/data/auth-functions.php';

$type = $_GET['type'] ?? '';
$value = trim($_GET['value'] ?? '');

if (empty($type) || empty($value)) {
    echo json_encode(['success' => false, 'message' => '파라미터가 올바르지 않습니다.']);
    exit;
}

if ($type !== 'user_id') {
    echo json_encode(['success' => false, 'message' => '잘못된 타입입니다.']);
    exit;
}

// 아이디 형식 검증 (영문자와 숫자만, 5-20자)
if (!preg_match('/^[A-Za-z0-9]{5,20}$/', $value)) {
    echo json_encode([
        'success' => false,
        'duplicate' => false,
        'available' => false,
        'message' => '아이디는 영문과 숫자만 사용할 수 있으며 5자 이상 20자 이내여야 합니다.'
    ]);
    exit;
}

// 모든 역할에서 아이디 중복 확인 (getUserById 사용)
$existingUser = getUserById($value);

if ($existingUser) {
    echo json_encode([
        'success' => true,
        'duplicate' => true,
        'available' => false,
        'message' => '이미 사용 중인 아이디입니다.'
    ]);
} else {
    echo json_encode([
        'success' => true,
        'duplicate' => false,
        'available' => true,
        'message' => '사용 가능한 아이디입니다.'
    ]);
}










