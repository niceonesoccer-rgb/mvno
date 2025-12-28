<?php
/**
 * 이메일 인증번호 검증 API
 * 
 * POST 파라미터:
 * - email: 인증할 이메일 주소
 * - verification_code: 인증번호
 * - type: 인증 타입 ('email_change' 또는 'password_change')
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
$email = trim($input['email'] ?? '');
$verificationCode = trim($input['verification_code'] ?? '');
$type = trim($input['type'] ?? 'email_change');

// 유효성 검사
if (empty($email) || empty($verificationCode)) {
    echo json_encode([
        'success' => false,
        'message' => '이메일 주소와 인증번호를 입력해주세요.'
    ]);
    exit;
}

if (!in_array($type, ['email_change', 'password_change'])) {
    echo json_encode([
        'success' => false,
        'message' => '잘못된 인증 타입입니다.'
    ]);
    exit;
}

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('데이터베이스 연결에 실패했습니다.');
    }
    
    // 인증 정보 조회
    $stmt = $pdo->prepare("
        SELECT id, user_id, email, verification_code, verification_token, type, status, expires_at
        FROM email_verifications
        WHERE user_id = :user_id
          AND email = :email
          AND verification_code = :verification_code
          AND type = :type
          AND status = 'pending'
        ORDER BY created_at DESC
        LIMIT 1
    ");
    
    $stmt->execute([
        ':user_id' => $currentUser['user_id'],
        ':email' => $email,
        ':verification_code' => $verificationCode,
        ':type' => $type
    ]);
    
    $verification = $stmt->fetch();
    
    if (!$verification) {
        echo json_encode([
            'success' => false,
            'message' => '인증번호가 일치하지 않거나 만료되었습니다.'
        ]);
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
        
        echo json_encode([
            'success' => false,
            'message' => '인증번호가 만료되었습니다. 다시 발송해주세요.'
        ]);
        exit;
    }
    
    // 인증 완료 처리
    $pdo->prepare("
        UPDATE email_verifications
        SET status = 'verified',
            verified_at = NOW()
        WHERE id = :id
    ")->execute([':id' => $verification['id']]);
    
    echo json_encode([
        'success' => true,
        'message' => '인증이 완료되었습니다.',
        'verification_token' => $verification['verification_token'],
        'email' => $email
    ]);
    
} catch (Exception $e) {
    error_log("이메일 인증번호 검증 오류: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '인증 처리 중 오류가 발생했습니다.'
    ]);
}






