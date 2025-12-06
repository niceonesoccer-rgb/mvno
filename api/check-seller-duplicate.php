<?php
/**
 * 판매자 아이디/이메일 중복확인 API
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/data/auth-functions.php';

$type = $_GET['type'] ?? '';
$value = trim($_GET['value'] ?? '');

if (empty($type) || empty($value)) {
    echo json_encode(['success' => false, 'message' => '파라미터가 올바르지 않습니다.']);
    exit;
}

if ($type !== 'user_id' && $type !== 'email') {
    echo json_encode(['success' => false, 'message' => '잘못된 타입입니다.']);
    exit;
}

// 아이디 형식 검증 (소문자 영문자와 숫자 조합 5-20자)
if ($type === 'user_id') {
    $lowerValue = strtolower($value);
    if (!preg_match('/^[a-z0-9]{5,20}$/', $lowerValue)) {
        echo json_encode([
            'success' => false,
            'duplicate' => false,
            'message' => '소문자 영문자와 숫자 조합 5-20자로 입력해주세요.'
        ]);
        exit;
    }
    // 소문자로 변환하여 저장
    $value = $lowerValue;
}

// 판매자 데이터만 확인
$sellersFile = getSellersFilePath();
$sellers = [];

if (file_exists($sellersFile)) {
    $data = json_decode(file_get_contents($sellersFile), true) ?: ['sellers' => []];
    $sellers = $data['sellers'] ?? [];
}

$isDuplicate = false;

if ($type === 'user_id') {
    // 금지된 아이디 목록 가져오기 (JSON 파일에서)
    $forbiddenIdsFile = __DIR__ . '/../includes/data/forbidden-ids.json';
    $forbiddenIds = [];
    
    if (file_exists($forbiddenIdsFile)) {
        $content = file_get_contents($forbiddenIdsFile);
        $data = json_decode($content, true) ?: ['forbidden_ids' => []];
        $forbiddenIds = $data['forbidden_ids'] ?? [];
    }
    
    // 소문자로 변환하여 비교
    $lowerValue = strtolower($value);
    
    // 금지된 아이디 체크 (정확히 일치하는 경우만)
    foreach ($forbiddenIds as $forbiddenId) {
        $forbiddenIdLower = strtolower(trim($forbiddenId));
        // 정확히 일치하는 경우만 차단
        if ($lowerValue === $forbiddenIdLower) {
            echo json_encode([
                'success' => false,
                'duplicate' => true,
                'message' => '이미 가입되어 있는 아이디입니다.'
            ]);
            exit;
        }
    }
    
    // 아이디 중복확인 (판매자만)
    foreach ($sellers as $seller) {
        if (isset($seller['user_id']) && $seller['user_id'] === $value) {
            $isDuplicate = true;
            break;
        }
    }
} elseif ($type === 'email') {
    // 이메일 중복확인 (판매자만, 일반회원과는 별도)
    foreach ($sellers as $seller) {
        if (isset($seller['email']) && strtolower($seller['email']) === strtolower($value)) {
            $isDuplicate = true;
            break;
        }
    }
}

if ($isDuplicate) {
    echo json_encode([
        'success' => false,
        'duplicate' => true,
        'message' => $type === 'user_id' ? '이미 사용 중인 아이디입니다.' : '이미 사용 중인 이메일입니다.'
    ]);
} else {
    echo json_encode([
        'success' => true,
        'duplicate' => false,
        'message' => $type === 'user_id' ? '사용 가능한 아이디입니다.' : '사용 가능한 이메일입니다.'
    ]);
}

