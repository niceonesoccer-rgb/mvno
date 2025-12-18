<?php
/**
 * 판매자 계정 삭제 API
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/data/auth-functions.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$currentUser = getCurrentUser();

// 판매자 로그인 체크
if (!$currentUser || $currentUser['role'] !== 'seller') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '인증이 필요합니다.']);
    exit;
}

// 승인되지 않은 상태(pending, on_hold, rejected)에서만 삭제 가능
$approvalStatus = $currentUser['approval_status'] ?? 'pending';
$allowedStatuses = ['pending', 'on_hold', 'rejected'];
if (!in_array($approvalStatus, $allowedStatuses)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => '승인 대기 중인 상태에서만 계정을 삭제할 수 있습니다.']);
    exit;
}

// 탈퇴 요청이 이미 진행 중인 경우 삭제 불가
if (isset($currentUser['withdrawal_requested']) && $currentUser['withdrawal_requested'] === true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => '이미 탈퇴 요청이 진행 중입니다.']);
    exit;
}

$userId = $currentUser['user_id'];

// DB-only: 판매자 삭제 (pending/on_hold/rejected 상태에서만)
$pdo = getDBConnection();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB 연결에 실패했습니다.']);
    exit;
}

try {
    // 삭제 전 업로드 파일 경로 조회
    $stmt = $pdo->prepare("
        SELECT business_license_image
        FROM seller_profiles
        WHERE user_id = :user_id
        LIMIT 1
    ");
    $stmt->execute([':user_id' => $userId]);
    $businessLicenseImage = $stmt->fetchColumn();

    $pdo->beginTransaction();

    // seller_profiles 먼저 삭제
    $pdo->prepare("DELETE FROM seller_profiles WHERE user_id = :user_id")
        ->execute([':user_id' => $userId]);

    // users 삭제 (판매자만)
    $u = $pdo->prepare("DELETE FROM users WHERE user_id = :user_id AND role = 'seller' LIMIT 1");
    $u->execute([':user_id' => $userId]);

    if ($u->rowCount() < 1) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => '판매자 정보를 찾을 수 없습니다.']);
        exit;
    }

    $pdo->commit();

    // 업로드된 파일 삭제 (DB 커밋 후)
    if (!empty($businessLicenseImage)) {
        $imagePath = $_SERVER['DOCUMENT_ROOT'] . $businessLicenseImage;
        if (file_exists($imagePath)) {
            @unlink($imagePath);
        }
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('delete-seller-account DB error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '계정 삭제에 실패했습니다.']);
    exit;
}

// 세션 삭제
logoutUser();

echo json_encode(['success' => true, 'message' => '계정이 성공적으로 삭제되었습니다.']);

