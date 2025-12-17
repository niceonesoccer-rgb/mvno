<?php
/**
 * 판매자 아이디/이메일/판매자명 중복확인 API
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/data/auth-functions.php';

$type = $_GET['type'] ?? '';
$value = trim($_GET['value'] ?? '');
$currentUserId = $_GET['current_user_id'] ?? ''; // 현재 수정 중인 사용자 ID (자기 자신 제외용)

if (empty($type) || empty($value)) {
    echo json_encode(['success' => false, 'message' => '파라미터가 올바르지 않습니다.']);
    exit;
}

if ($type !== 'user_id' && $type !== 'email' && $type !== 'seller_name') {
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
        // 현재 수정 중인 사용자는 제외
        if (!empty($currentUserId) && isset($seller['user_id']) && $seller['user_id'] === $currentUserId) {
            continue;
        }
        if (isset($seller['email']) && strtolower($seller['email']) === strtolower($value)) {
            $isDuplicate = true;
            break;
        }
    }
} elseif ($type === 'seller_name') {
    // 판매자명 중복확인 (대소문자 구분 없이)
    // DB에서 먼저 확인
    $pdo = getDBConnection();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("
                SELECT user_id, seller_name 
                FROM users 
                WHERE role = 'seller' 
                AND seller_name IS NOT NULL 
                AND LOWER(seller_name) = LOWER(:seller_name)
            ");
            $stmt->execute([':seller_name' => $value]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($results as $row) {
                // 현재 수정 중인 사용자는 제외
                if (!empty($currentUserId) && isset($row['user_id']) && $row['user_id'] === $currentUserId) {
                    continue;
                }
                $isDuplicate = true;
                break;
            }
        } catch (PDOException $e) {
            error_log("판매자명 중복 검사 API DB 오류: " . $e->getMessage());
            // DB 오류 시 JSON 파일로 fallback
        }
    }
    
    // DB에서 확인되지 않았거나 DB 연결 실패 시 JSON 파일 확인 (fallback)
    if (!$isDuplicate) {
        $valueLower = mb_strtolower($value, 'UTF-8');
        foreach ($sellers as $seller) {
            // 현재 수정 중인 사용자는 제외
            if (!empty($currentUserId) && isset($seller['user_id']) && $seller['user_id'] === $currentUserId) {
                continue;
            }
            if (isset($seller['seller_name']) && !empty($seller['seller_name'])) {
                $sellerNameLower = mb_strtolower($seller['seller_name'], 'UTF-8');
                if ($sellerNameLower === $valueLower) {
                    $isDuplicate = true;
                    break;
                }
            }
        }
    }
}

if ($isDuplicate) {
    $messages = [
        'user_id' => '이미 사용 중인 아이디입니다.',
        'email' => '이미 사용 중인 이메일입니다.',
        'seller_name' => '이미 사용 중인 판매자명입니다.'
    ];
    echo json_encode([
        'success' => false,
        'duplicate' => true,
        'message' => $messages[$type] ?? '이미 사용 중입니다.'
    ]);
} else {
    $messages = [
        'user_id' => '사용 가능한 아이디입니다.',
        'email' => '사용 가능한 이메일입니다.',
        'seller_name' => '사용 가능한 판매자명입니다.'
    ];
    echo json_encode([
        'success' => true,
        'duplicate' => false,
        'message' => $messages[$type] ?? '사용 가능합니다.'
    ]);
}

