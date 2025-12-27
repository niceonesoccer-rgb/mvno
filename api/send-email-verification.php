<?php
/**
 * 이메일 인증번호 발송 API
 * 
 * POST 파라미터:
 * - email: 인증할 이메일 주소
 * - type: 인증 타입 ('email_change' 또는 'password_change')
 */

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../includes/data/auth-functions.php';
require_once __DIR__ . '/../includes/data/mail-helper.php';

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
$type = trim($input['type'] ?? 'email_change');

// 유효성 검사
if (empty($email)) {
    echo json_encode([
        'success' => false,
        'message' => '이메일 주소를 입력해주세요.'
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => '올바른 이메일 형식이 아닙니다.'
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

// 비밀번호 변경인 경우 현재 이메일로만 발송 가능
if ($type === 'password_change') {
    $currentEmail = $currentUser['email'] ?? '';
    if (empty($currentEmail) || $email !== $currentEmail) {
        echo json_encode([
            'success' => false,
            'message' => '비밀번호 변경은 현재 등록된 이메일로만 가능합니다.'
        ]);
        exit;
    }
}

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('데이터베이스 연결에 실패했습니다.');
    }
    
    // 기존 미인증 코드가 있으면 만료 처리
    $pdo->prepare("
        UPDATE email_verifications
        SET status = 'expired'
        WHERE user_id = :user_id
          AND type = :type
          AND status = 'pending'
          AND expires_at > NOW()
    ")->execute([
        ':user_id' => $currentUser['user_id'],
        ':type' => $type
    ]);
    
    // 인증번호 생성 (6자리)
    $verificationCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // 인증 토큰 생성 (링크용)
    $verificationToken = bin2hex(random_bytes(32));
    
    // 만료 시간 (30분 후)
    $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes'));
    
    // DB에 저장
    $stmt = $pdo->prepare("
        INSERT INTO email_verifications 
        (user_id, email, verification_code, verification_token, type, status, expires_at)
        VALUES (:user_id, :email, :verification_code, :verification_token, :type, 'pending', :expires_at)
    ");
    
    $stmt->execute([
        ':user_id' => $currentUser['user_id'],
        ':email' => $email,
        ':verification_code' => $verificationCode,
        ':verification_token' => $verificationToken,
        ':type' => $type,
        ':expires_at' => $expiresAt
    ]);
    
    // 이메일 발송 (개발 환경에서는 실패해도 계속 진행)
    $userName = $currentUser['name'] ?? '';
    $emailSent = false;
    
    try {
        $emailSent = sendVerificationEmail($email, $verificationCode, $type, $userName);
    } catch (Exception $emailException) {
        error_log("이메일 발송 오류 (무시됨): " . $emailException->getMessage());
    }
    
    // 개발 환경에서는 이메일 발송 실패해도 DB에는 저장되어 있으므로 성공으로 처리
    // 인증번호는 DB에서 확인 가능
    if (!$emailSent) {
        error_log("이메일 발송 실패했지만 DB에는 저장됨 - 인증번호: {$verificationCode}, 이메일: {$email}");
    }
    
    // 개발 환경 감지 (XAMPP 등)
    $isDevelopment = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || 
                      strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
    
    $message = '인증번호가 발송되었습니다.';
    if (!$emailSent && $isDevelopment) {
        $message = '인증번호가 생성되었습니다. (개발 환경: DB에서 확인 가능)';
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'email' => $email,
        'expires_at' => $expiresAt,
        'development_mode' => $isDevelopment && !$emailSent,
        'verification_code' => ($isDevelopment && !$emailSent) ? $verificationCode : null // 개발 환경에서만 코드 반환
    ]);
    
} catch (Exception $e) {
    error_log("이메일 인증번호 발송 오류: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '인증번호 발송에 실패했습니다: ' . $e->getMessage()
    ]);
}





