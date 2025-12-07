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

// 관리자 데이터 확인
$adminsFile = getAdminsFilePath();
$admins = [];

if (file_exists($adminsFile)) {
    $data = json_decode(file_get_contents($adminsFile), true) ?: ['admins' => []];
    $admins = $data['admins'] ?? [];
}

$isDuplicate = false;

// 아이디 중복확인 (관리자)
foreach ($admins as $admin) {
    if (isset($admin['user_id']) && strtolower($admin['user_id']) === $lowerValue) {
        $isDuplicate = true;
        break;
    }
}

// 판매자 데이터도 확인 (관리자와 판매자는 같은 아이디 사용 불가)
if (!$isDuplicate) {
    $sellersFile = getSellersFilePath();
    $sellers = [];
    
    if (file_exists($sellersFile)) {
        $data = json_decode(file_get_contents($sellersFile), true) ?: ['sellers' => []];
        $sellers = $data['sellers'] ?? [];
    }
    
    foreach ($sellers as $seller) {
        if (isset($seller['user_id']) && strtolower($seller['user_id']) === $lowerValue) {
            $isDuplicate = true;
            break;
        }
    }
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
