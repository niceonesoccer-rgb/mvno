<?php
/**
 * 비밀번호 변경 API (이메일 인증 포함)
 * 
 * POST 파라미터:
 * - current_password: 현재 비밀번호
 * - new_password: 새 비밀번호
 * - verification_token: 이메일 인증 토큰 (선택, 이메일이 있는 경우 필수)
 */

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../includes/data/auth-functions.php';

// 로그인 체크
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => '로그인이 필요합니다.'
    ]);
    exit;
}

$currentUser = getCurrentUser();
if (!$currentUser) {
    echo json_encode([
        'success' => false,
        'message' => '사용자 정보를 찾을 수 없습니다.'
    ]);
    exit;
}

// POST 데이터 받기
$input = json_decode(file_get_contents('php://input'), true);
$currentPassword = $input['current_password'] ?? '';
$newPassword = $input['new_password'] ?? '';
$verificationToken = trim($input['verification_token'] ?? '');

// 유효성 검사
if (empty($currentPassword)) {
    echo json_encode([
        'success' => false,
        'field' => 'current_password',
        'message' => '현재 비밀번호를 입력해주세요.'
    ]);
    exit;
}

if (empty($newPassword) || strlen($newPassword) < 8) {
    echo json_encode([
        'success' => false,
        'field' => 'new_password',
        'message' => '새 비밀번호는 8자 이상이어야 합니다.'
    ]);
    exit;
}

// 현재 비밀번호 확인
$pdo = getDBConnection();
if (!$pdo) {
    echo json_encode([
        'success' => false,
        'message' => '데이터베이스 연결에 실패했습니다.'
    ]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT password
    FROM users
    WHERE user_id = :user_id
    LIMIT 1
");

$stmt->execute([':user_id' => $currentUser['user_id']]);
$user = $stmt->fetch();

if (!$user || !password_verify($currentPassword, $user['password'])) {
    echo json_encode([
        'success' => false,
        'field' => 'current_password',
        'message' => '현재 비밀번호가 일치하지 않습니다.'
    ]);
    exit;
}

// 이메일이 있는 경우 이메일 인증 확인
$currentEmail = $currentUser['email'] ?? '';
if (!empty($currentEmail)) {
    if (empty($verificationToken)) {
        echo json_encode([
            'success' => false,
            'message' => '이메일 인증이 필요합니다.',
            'requires_email_verification' => true
        ]);
        exit;
    }
    
    // 이메일 인증 토큰 검증
    $verifyStmt = $pdo->prepare("
        SELECT id, user_id, email, type, status, expires_at, verified_at
        FROM email_verifications
        WHERE user_id = :user_id
          AND email = :email
          AND verification_token = :verification_token
          AND type = 'password_change'
          AND status = 'verified'
        ORDER BY verified_at DESC
        LIMIT 1
    ");
    
    $verifyStmt->execute([
        ':user_id' => $currentUser['user_id'],
        ':email' => $currentEmail,
        ':verification_token' => $verificationToken
    ]);
    
    $verification = $verifyStmt->fetch();
    
    if (!$verification) {
        echo json_encode([
            'success' => false,
            'message' => '이메일 인증이 필요합니다.',
            'requires_email_verification' => true
        ]);
        exit;
    }
    
    // 인증 후 30분 이내인지 확인
    $verifiedAt = strtotime($verification['verified_at']);
    if ($verifiedAt < time() - 1800) { // 30분 = 1800초
        echo json_encode([
            'success' => false,
            'message' => '이메일 인증이 만료되었습니다. 다시 인증해주세요.',
            'requires_email_verification' => true
        ]);
        exit;
    }
}

try {
    // 비밀번호 변경
    $pdo->beginTransaction();
    
    try {
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $updateStmt = $pdo->prepare("
            UPDATE users
            SET password = :password,
                updated_at = NOW()
            WHERE user_id = :user_id
            LIMIT 1
        ");
        
        $updateStmt->execute([
            ':password' => $newPasswordHash,
            ':user_id' => $currentUser['user_id']
        ]);
        
        // 인증 정보 사용 완료 처리 (이메일이 있는 경우)
        if (!empty($verificationToken) && isset($verification['id'])) {
            $pdo->prepare("
                UPDATE email_verifications
                SET status = 'expired'
                WHERE id = :id
            ")->execute([':id' => $verification['id']]);
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => '비밀번호가 변경되었습니다.'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("비밀번호 변경 오류: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '비밀번호 변경 중 오류가 발생했습니다.'
    ]);
}








