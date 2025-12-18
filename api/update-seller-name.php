<?php
/**
 * 판매자명 업데이트 API
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/data/auth-functions.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 판매자 인증 체크
$currentUser = getCurrentUser();
if (!$currentUser || $currentUser['role'] !== 'seller') {
    echo json_encode(['success' => false, 'message' => '인증이 필요합니다.']);
    exit;
}

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
    exit;
}

// 판매자명 가져오기
$sellerName = trim($_POST['seller_name'] ?? '');

// 판매자명 검증
if (empty($sellerName)) {
    echo json_encode(['success' => false, 'message' => '판매자명을 입력해주세요.']);
    exit;
}

if (mb_strlen($sellerName) < 2) {
    echo json_encode(['success' => false, 'message' => '판매자명은 최소 2자 이상 입력해주세요.']);
    exit;
}

if (mb_strlen($sellerName) > 50) {
    echo json_encode(['success' => false, 'message' => '판매자명은 최대 50자까지 입력 가능합니다.']);
    exit;
}

// DB-only: 중복 검사 + 업데이트
$userId = $currentUser['user_id'];
$pdo = getDBConnection();
if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'DB 연결에 실패했습니다.']);
    exit;
}

try {
    // 중복 검사 (자기 자신 제외)
    // DB-only 스키마 기준: seller_name 컬럼이 없을 수 있어 company_name 기준으로 검사한다.
    // (환경에 따라 seller_profiles.company_name도 존재하므로 JOIN으로 보조)
    $stmt = $pdo->prepare("
        SELECT 1
        FROM users u
        LEFT JOIN seller_profiles sp ON sp.user_id = u.user_id
        WHERE u.role = 'seller'
          AND u.user_id <> :user_id
          AND LOWER(COALESCE(NULLIF(u.company_name, ''), NULLIF(sp.company_name, ''))) = LOWER(:seller_name)
        LIMIT 1
    ");
    $stmt->execute([
        ':user_id' => $userId,
        ':seller_name' => $sellerName
    ]);
    if ($stmt->fetchColumn()) {
        echo json_encode(['success' => false, 'message' => '이미 사용 중인 판매자명입니다.']);
        exit;
    }

    // 업데이트 (users + seller_profiles 동기화)
    $pdo->beginTransaction();

    $u = $pdo->prepare("
        UPDATE users
        SET company_name = :seller_name,
            updated_at = NOW()
        WHERE user_id = :user_id
          AND role = 'seller'
        LIMIT 1
    ");
    $u->execute([
        ':seller_name' => $sellerName,
        ':user_id' => $userId
    ]);

    $sp = $pdo->prepare("
        UPDATE seller_profiles
        SET company_name = :seller_name,
            updated_at = NOW()
        WHERE user_id = :user_id
        LIMIT 1
    ");
    $sp->execute([
        ':seller_name' => $sellerName,
        ':user_id' => $userId
    ]);

    $pdo->commit();

    if ($u->rowCount() < 1) {
        echo json_encode(['success' => false, 'message' => '판매자 정보를 찾을 수 없습니다.']);
        exit;
    }

    echo json_encode(['success' => true, 'message' => '판매자명이 저장되었습니다.']);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('update-seller-name DB error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '판매자명 저장에 실패했습니다.']);
}

