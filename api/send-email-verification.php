<?php
/**
 * 이메일 인증번호 발송 API
 * 
 * POST 파라미터:
 * - email: 인증할 이메일 주소
 * - type: 인증 타입 ('email_change' 또는 'password_change')
 */

// 헤더 먼저 설정 (point-deduct.php와 동일한 구조)
header('Content-Type: application/json; charset=utf-8');

// OPTIONS 요청 처리 (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 필요한 파일들 로드
require_once __DIR__ . '/../includes/data/auth-functions.php';
require_once __DIR__ . '/../includes/data/mail-helper.php';

// POST 요청만 허용 (point-deduct.php와 동일한 구조)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// 로그인 체크
if (!isLoggedIn()) {
    http_response_code(401);
    error_log("send-email-verification: 로그인 필요");
    echo json_encode([
        'success' => false,
        'message' => '로그인이 필요합니다.'
    ]);
    exit;
}

$currentUser = getCurrentUser();
if (!$currentUser) {
    http_response_code(401);
    error_log("send-email-verification: 사용자 정보 없음");
    echo json_encode([
        'success' => false,
        'message' => '사용자 정보를 찾을 수 없습니다.'
    ]);
    exit;
}

// 입력 데이터 받기 (point-deduct.php와 동일한 구조)
$input = json_decode(file_get_contents('php://input'), true);

// FormData 요청도 지원
if (empty($input) && !empty($_POST)) {
    $input = $_POST;
}

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
    
    error_log("send-email-verification - 이메일 발송 시도 - 이메일: {$email}, 타입: {$type}, 인증번호: {$verificationCode}");
    
    try {
        $emailSent = sendVerificationEmail($email, $verificationCode, $type, $userName);
        error_log("send-email-verification - sendVerificationEmail 반환값: " . ($emailSent ? 'true (성공)' : 'false (실패)'));
    } catch (Exception $emailException) {
        error_log("send-email-verification - 이메일 발송 예외 발생: " . $emailException->getMessage());
        error_log("send-email-verification - 예외 트레이스: " . $emailException->getTraceAsString());
    }
    
    // 개발 환경 감지 (localhost만 개발 환경으로 간주)
    $isDevelopment = (
        strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || 
        strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false ||
        strpos($_SERVER['HTTP_HOST'] ?? '', '::1') !== false
    );
    
    error_log("send-email-verification - 환경: " . ($isDevelopment ? '개발' : '프로덕션') . ", 호스트: " . ($_SERVER['HTTP_HOST'] ?? 'unknown'));
    
    // 이메일 발송 실패 처리
    if (!$emailSent) {
        error_log("send-email-verification - 이메일 발송 실패 - 인증번호: {$verificationCode}, 이메일: {$email}, 호스트: " . ($_SERVER['HTTP_HOST'] ?? 'unknown'));
        
        // 실제 서버에서는 이메일 발송 실패 시 에러 반환
        if (!$isDevelopment) {
            echo json_encode([
                'success' => false,
                'message' => '이메일 발송에 실패했습니다. 이메일 설정을 확인해주세요. (관리자 페이지 > 설정 > 이메일 설정)',
                'email_send_failed' => true
            ]);
            exit;
        }
        
        // 개발 환경에서만 실패해도 계속 진행하고 인증번호 표시
        error_log("개발 환경: 이메일 발송 실패했지만 DB에는 저장됨 - 인증번호: {$verificationCode}");
    }
    
    $message = '인증번호가 발송되었습니다. 이메일을 확인해주세요.';
    if (!$emailSent && $isDevelopment) {
        $message = '인증번호가 생성되었습니다. (개발 환경: DB에서 확인 가능)';
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'email' => $email,
        'expires_at' => $expiresAt,
        'development_mode' => $isDevelopment && !$emailSent, // 개발 환경에서만 true
        'verification_code' => ($isDevelopment && !$emailSent) ? $verificationCode : null // 개발 환경에서만 코드 반환
    ]);
    
} catch (Exception $e) {
    error_log("이메일 인증번호 발송 오류: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '인증번호 발송에 실패했습니다: ' . $e->getMessage()
    ]);
}








