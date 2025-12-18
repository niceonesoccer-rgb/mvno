<?php
/**
 * 판매자 아이디/이메일/판매자명 중복확인 API
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/data/auth-functions.php';
require_once __DIR__ . '/../includes/data/db-config.php';

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

$isDuplicate = false;

if ($type === 'user_id') {
    // 소문자로 변환하여 비교
    $lowerValue = strtolower($value);

    $pdo = getDBConnection();
    if ($pdo) {
        // 금지 아이디 체크(DB)
        $stmt = $pdo->prepare("SELECT 1 FROM forbidden_ids WHERE id_value = :id LIMIT 1");
        $stmt->execute([':id' => $lowerValue]);
        if ($stmt->fetchColumn()) {
            echo json_encode([
                'success' => false,
                'duplicate' => true,
                'message' => '이미 가입되어 있는 아이디입니다.'
            ]);
            exit;
        }

        // 판매자 아이디 중복(DB)
        $stmt = $pdo->prepare("SELECT 1 FROM users WHERE user_id = :user_id AND role = 'seller' LIMIT 1");
        $stmt->execute([':user_id' => $value]);
        $isDuplicate = (bool)$stmt->fetchColumn();
    }
} elseif ($type === 'email') {
    $pdo = getDBConnection();
    if ($pdo) {
        $sql = "SELECT 1 FROM users WHERE role = 'seller' AND LOWER(email) = LOWER(:email)";
        if (!empty($currentUserId)) {
            $sql .= " AND user_id != :current";
        }
        $sql .= " LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $params = [':email' => $value];
        if (!empty($currentUserId)) $params[':current'] = $currentUserId;
        $stmt->execute($params);
        $isDuplicate = (bool)$stmt->fetchColumn();
    }
} elseif ($type === 'seller_name') {
    // 판매자명 중복확인 (대소문자 구분 없이)
    $pdo = getDBConnection();
    if ($pdo) {
        try {
            // DB-only 스키마 기준: users에 seller_name 컬럼이 없을 수 있어 company_name 기준으로 검사
            // (환경에 따라 seller_profiles.company_name을 쓰는 경우도 있어 JOIN으로 보조)
            $sql = "
                SELECT 1
                FROM users u
                LEFT JOIN seller_profiles sp ON sp.user_id = u.user_id
                WHERE u.role = 'seller'
                  AND LOWER(COALESCE(NULLIF(u.company_name, ''), NULLIF(sp.company_name, ''))) = LOWER(:seller_name)
            ";
            if (!empty($currentUserId)) {
                $sql .= " AND u.user_id != :current";
            }
            $sql .= " LIMIT 1";

            $stmt = $pdo->prepare($sql);
            $params = [':seller_name' => $value];
            if (!empty($currentUserId)) $params[':current'] = $currentUserId;
            $stmt->execute($params);
            $isDuplicate = (bool)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("판매자명 중복 검사 API DB 오류: " . $e->getMessage());
            // DB-only: fallback 없음
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

