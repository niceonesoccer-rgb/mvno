<?php
/**
 * 사용자 전화번호 업데이트 API
 */

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/data/auth-functions.php';

header('Content-Type: application/json; charset=utf-8');

// 로그인 확인
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => '로그인이 필요합니다.'
    ]);
    exit;
}

$currentUser = getCurrentUser();
if (!$currentUser || $currentUser['role'] !== 'user') {
    echo json_encode([
        'success' => false,
        'message' => '일반 회원만 사용할 수 있습니다.'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => '잘못된 요청 방법입니다.'
    ]);
    exit;
}

$phone = trim($_POST['phone'] ?? '');
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');

// 필수 필드 검증
if (empty($phone) || empty($name) || empty($email)) {
    echo json_encode([
        'success' => false,
        'message' => '이름, 이메일, 전화번호는 필수 입력 항목입니다.'
    ]);
    exit;
}

// 전화번호 형식 검증 (010-XXXX-XXXX)
$phoneNumbers = preg_replace('/[^\d]/', '', $phone);
if (!preg_match('/^010\d{8}$/', $phoneNumbers)) {
    echo json_encode([
        'success' => false,
        'message' => '휴대폰 번호는 010으로 시작하는 11자리 숫자여야 합니다.'
    ]);
    exit;
}

// 전화번호 포맷팅
$formattedPhone = '010-' . substr($phoneNumbers, 3, 4) . '-' . substr($phoneNumbers, 7, 4);

// 이메일 형식 검증
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => '올바른 이메일 형식이 아닙니다.'
    ]);
    exit;
}

// 이름 길이 검증
if (mb_strlen($name) > 50) {
    echo json_encode([
        'success' => false,
        'message' => '이름은 50자 이내로 입력해주세요.'
    ]);
    exit;
}

// DB-only: users 테이블 업데이트
$pdo = getDBConnection();
if (!$pdo) {
    echo json_encode([
        'success' => false,
        'message' => 'DB 연결에 실패했습니다.'
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE users
        SET name = :name,
            email = :email,
            phone = :phone,
            updated_at = NOW()
        WHERE user_id = :user_id
          AND role = 'user'
        LIMIT 1
    ");
    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':phone' => $formattedPhone,
        ':user_id' => $currentUser['user_id']
    ]);

    if ($stmt->rowCount() < 1) {
        echo json_encode([
            'success' => false,
            'message' => '사용자를 찾을 수 없습니다.'
        ]);
        exit;
    }

    // 세션에 저장된 리다이렉트 URL 확인
    $redirectUrl = $_SESSION['redirect_url'] ?? '/MVNO/';
    $response = [
        'success' => true,
        'message' => '정보가 성공적으로 업데이트되었습니다.'
    ];

    if (isset($_SESSION['redirect_url'])) {
        $response['redirect_url'] = $redirectUrl;
        unset($_SESSION['redirect_url']);
    }

    echo json_encode($response);
} catch (PDOException $e) {
    error_log('update-user-phone DB error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '정보 업데이트에 실패했습니다.'
    ]);
}










