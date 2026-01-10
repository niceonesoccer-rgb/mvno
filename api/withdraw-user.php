<?php
/**
 * 일반 사용자 회원 탈퇴 API
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/data/auth-functions.php';
require_once __DIR__ . '/../includes/data/db-config.php';

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

// 일반 사용자 로그인 체크 (member 또는 role이 없는 경우)
if (!$currentUser || ($currentUser['role'] !== 'member' && !empty($currentUser['role']) && $currentUser['role'] !== 'user')) {
    // role이 없거나 'member', 'user'인 경우 일반 사용자로 간주
    if (!empty($currentUser['role']) && !in_array($currentUser['role'], ['member', 'user', ''])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => '일반 사용자만 탈퇴할 수 있습니다.']);
        exit;
    }
}

$userId = $currentUser['user_id'];

// DB 연결
$pdo = getDBConnection();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB 연결에 실패했습니다.']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // 1. 사용자 계정 비활성화 (soft delete)
    // 개인정보는 5년 후 삭제 예정이므로 당장은 보존
    $deleteDate = date('Y-m-d', strtotime('+5 years'));
    
    $stmt = $pdo->prepare("
        UPDATE users 
        SET status = 'inactive',
            withdrawal_requested = 1,
            withdrawal_requested_at = NOW(),
            scheduled_delete_date = :scheduled_delete_date,
            updated_at = NOW()
        WHERE user_id = :user_id 
        AND (role = 'member' OR role = 'user' OR role IS NULL OR role = '')
    ");
    $stmt->execute([
        ':user_id' => $userId,
        ':scheduled_delete_date' => $deleteDate
    ]);
    
    if ($stmt->rowCount() < 1) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => '사용자 정보를 찾을 수 없습니다.']);
        exit;
    }
    
    // 2. 찜한 상품 삭제 (product_favorites)
    $pdo->prepare("DELETE FROM product_favorites WHERE user_id = :user_id")
        ->execute([':user_id' => $userId]);
    
    // 3. 포인트 내역은 보존 (거래 기록이므로)
    // 포인트 잔액은 0으로 설정
    $pdo->prepare("
        UPDATE user_points 
        SET balance = 0,
            updated_at = NOW()
        WHERE user_id = :user_id
    ")->execute([':user_id' => $userId]);
    
    // 4. 신청 내역은 보존 (거래 기록이므로)
    // 단, 진행 중인 신청은 취소 처리
    $pdo->prepare("
        UPDATE product_applications
        SET application_status = 'cancelled',
            status_changed_at = NOW(),
            updated_at = NOW()
        WHERE user_id = :user_id
        AND application_status IN ('pending', 'received', 'processing', 'activating')
    ")->execute([':user_id' => $userId]);
    
    $pdo->commit();
    
    // 세션 삭제
    logoutUser();
    
    echo json_encode([
        'success' => true, 
        'message' => '회원 탈퇴가 완료되었습니다.'
    ]);
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('withdraw-user DB error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '회원 탈퇴 처리 중 오류가 발생했습니다.']);
}
