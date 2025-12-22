<?php
/**
 * 이메일 변경 완료 API
 * 
 * POST 파라미터:
 * - email: 변경할 이메일 주소
 * - verification_token: 인증 토큰 (인증번호 검증 후 받은 토큰)
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
$verificationToken = trim($input['verification_token'] ?? '');

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

if (empty($verificationToken)) {
    echo json_encode([
        'success' => false,
        'message' => '인증이 필요합니다.'
    ]);
    exit;
}

// 현재 이메일과 동일한지 확인
$currentEmail = $currentUser['email'] ?? '';
if ($email === $currentEmail) {
    echo json_encode([
        'success' => false,
        'message' => '현재 사용 중인 이메일과 동일합니다.'
    ]);
    exit;
}

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('데이터베이스 연결에 실패했습니다.');
    }
    
    // 인증 토큰 검증
    $stmt = $pdo->prepare("
        SELECT id, user_id, email, type, status, expires_at, verified_at
        FROM email_verifications
        WHERE user_id = :user_id
          AND email = :email
          AND verification_token = :verification_token
          AND type = 'email_change'
          AND status = 'verified'
        ORDER BY verified_at DESC
        LIMIT 1
    ");
    
    $stmt->execute([
        ':user_id' => $currentUser['user_id'],
        ':email' => $email,
        ':verification_token' => $verificationToken
    ]);
    
    $verification = $stmt->fetch();
    
    if (!$verification) {
        echo json_encode([
            'success' => false,
            'message' => '인증 정보가 유효하지 않습니다. 다시 인증해주세요.'
        ]);
        exit;
    }
    
    // 인증 후 30분 이내인지 확인
    $verifiedAt = strtotime($verification['verified_at']);
    if ($verifiedAt < time() - 1800) { // 30분 = 1800초
        echo json_encode([
            'success' => false,
            'message' => '인증이 만료되었습니다. 다시 인증해주세요.'
        ]);
        exit;
    }
    
    // 이메일 중복 확인
    $existingUser = getUserByEmail($email);
    if ($existingUser && $existingUser['user_id'] !== $currentUser['user_id']) {
        echo json_encode([
            'success' => false,
            'message' => '이미 사용 중인 이메일 주소입니다.'
        ]);
        exit;
    }
    
    // 이메일 변경
    $pdo->beginTransaction();
    
    try {
        $updateStmt = $pdo->prepare("
            UPDATE users
            SET email = :email,
                updated_at = NOW()
            WHERE user_id = :user_id
            LIMIT 1
        ");
        
        $updateStmt->execute([
            ':email' => $email,
            ':user_id' => $currentUser['user_id']
        ]);
        
        // 인증 정보 사용 완료 처리 (중복 사용 방지)
        $pdo->prepare("
            UPDATE email_verifications
            SET status = 'expired'
            WHERE id = :id
        ")->execute([':id' => $verification['id']]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => '이메일 주소가 변경되었습니다.',
            'email' => $email
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("이메일 변경 오류: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '이메일 변경 중 오류가 발생했습니다.'
    ]);
}
