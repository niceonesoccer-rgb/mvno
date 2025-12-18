<?php
/**
 * 관리자 아이디 중복확인 API
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

// 아이디 형식 검증 (소문자 영문자와 숫자 조합 4-20자)
$lowerValue = strtolower($value);
if (!preg_match('/^[a-z0-9]{4,20}$/', $lowerValue)) {
    echo json_encode([
        'success' => false,
        'duplicate' => false,
        'message' => '소문자 영문자와 숫자 조합 4-20자로 입력해주세요.'
    ]);
    exit;
}

// DB-only: 관리자/부관리자 아이디 중복 확인 (users 테이블)
$pdo = getDBConnection();
if (!$pdo) {
    echo json_encode(['success' => false, 'duplicate' => false, 'message' => 'DB 연결에 실패했습니다.']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 1
        FROM users
        WHERE LOWER(user_id) = :user_id
          AND role IN ('admin','sub_admin')
        LIMIT 1
    ");
    $stmt->execute([':user_id' => $lowerValue]);
    $isDuplicate = (bool)$stmt->fetchColumn();
} catch (PDOException $e) {
    error_log('check-admin-duplicate DB error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'duplicate' => false, 'message' => '중복확인 중 오류가 발생했습니다.']);
    exit;
}

if ($isDuplicate) {
    echo json_encode([
        'success' => false,
        'duplicate' => true,
        'message' => '이미 사용 중인 아이디입니다.'
    ]);
} else {
    echo json_encode([
        'success' => true,
        'duplicate' => false,
        'message' => '사용 가능한 아이디입니다.'
    ]);
}

