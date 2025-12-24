<?php
/**
 * 이메일 인증 링크 검증 API
 * 
 * GET 파라미터:
 * - token: 인증 토큰
 * - type: 인증 타입 ('email_change' 또는 'password_change')
 */

require_once __DIR__ . '/../includes/data/auth-functions.php';

$token = $_GET['token'] ?? '';
$type = $_GET['type'] ?? 'email_change';

if (empty($token)) {
    header('Location: /MVNO/mypage/account-management.php?error=invalid_token');
    exit;
}

if (!in_array($type, ['email_change', 'password_change'])) {
    header('Location: /MVNO/mypage/account-management.php?error=invalid_type');
    exit;
}

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        header('Location: /MVNO/mypage/account-management.php?error=db_error');
        exit;
    }
    
    // 인증 토큰 검증
    $stmt = $pdo->prepare("
        SELECT id, user_id, email, type, status, expires_at
        FROM email_verifications
        WHERE verification_token = :token
          AND type = :type
          AND status = 'pending'
        ORDER BY created_at DESC
        LIMIT 1
    ");
    
    $stmt->execute([
        ':token' => $token,
        ':type' => $type
    ]);
    
    $verification = $stmt->fetch();
    
    if (!$verification) {
        header('Location: /MVNO/mypage/account-management.php?error=invalid_token');
        exit;
    }
    
    // 만료 시간 확인
    if (strtotime($verification['expires_at']) < time()) {
        // 만료 처리
        $pdo->prepare("
            UPDATE email_verifications
            SET status = 'expired'
            WHERE id = :id
        ")->execute([':id' => $verification['id']]);
        
        header('Location: /MVNO/mypage/account-management.php?error=expired');
        exit;
    }
    
    // 인증 완료 처리
    $pdo->prepare("
        UPDATE email_verifications
        SET status = 'verified',
            verified_at = NOW()
        WHERE id = :id
    ")->execute([':id' => $verification['id']]);
    
    // 로그인 상태 확인
    if (!isLoggedIn()) {
        // 로그인 페이지로 리다이렉트 (인증 토큰과 함께)
        $_SESSION['email_verification_token'] = $token;
        $_SESSION['email_verification_type'] = $type;
        $_SESSION['email_verification_email'] = $verification['email'];
        header('Location: /MVNO/?show_login=1&email_verified=1');
        exit;
    }
    
    $currentUser = getCurrentUser();
    if (!$currentUser || $currentUser['user_id'] !== $verification['user_id']) {
        header('Location: /MVNO/mypage/account-management.php?error=user_mismatch');
        exit;
    }
    
    // 인증 완료 후 계정 설정 페이지로 리다이렉트
    if ($type === 'email_change') {
        header('Location: /MVNO/mypage/account-management.php?email_verified=1&token=' . urlencode($token) . '&email=' . urlencode($verification['email']));
    } else {
        header('Location: /MVNO/mypage/account-management.php?password_verified=1&token=' . urlencode($token));
    }
    exit;
    
} catch (Exception $e) {
    error_log("이메일 인증 링크 검증 오류: " . $e->getMessage());
    header('Location: /MVNO/mypage/account-management.php?error=server_error');
    exit;
}

