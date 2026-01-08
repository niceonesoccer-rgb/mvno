<?php
/**
 * 직접 로그인 API (관리자, 서브관리자, 판매자용)
 */

// auth-functions.php에서 세션 설정과 함께 세션을 시작함
require_once __DIR__ . '/../includes/data/path-config.php';
require_once __DIR__ . '/../includes/data/auth-functions.php';

header('Content-Type: application/json');

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
    exit;
}

// 입력값 받기
$userId = trim($_POST['user_id'] ?? '');
$password = $_POST['password'] ?? '';

// 입력값 검증
if (empty($userId) || empty($password)) {
    echo json_encode(['success' => false, 'message' => '아이디와 비밀번호를 입력해주세요.']);
    exit;
}

// 직접 로그인 처리
$result = loginDirectUser($userId, $password);

if ($result['success']) {
    $user = $result['user'];
    
    // 세션에 저장된 리다이렉트 URL 확인
    $redirect = $_SESSION['redirect_url'] ?? null;
    if (isset($_SESSION['redirect_url'])) {
        unset($_SESSION['redirect_url']);
    }
    
    // 리다이렉트 URL이 없으면 역할에 따라 기본 리다이렉트 URL 결정
    if (empty($redirect)) {
        if ($user['role'] === 'admin' || $user['role'] === 'sub_admin') {
            $redirect = getAssetPath('/admin/'); // 관리자는 관리자 센터로 이동
        } elseif ($user['role'] === 'seller') {
            $redirect = getAssetPath('/seller/'); // 판매자는 판매자 센터로 이동
        } else {
            $redirect = getAssetPath('/'); // 일반 사용자는 홈으로
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => '로그인되었습니다.',
        'redirect' => $redirect,
        'user' => [
            'user_id' => $user['user_id'],
            'name' => $user['name'],
            'role' => $user['role']
        ]
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => $result['message'] ?? '로그인에 실패했습니다.'
    ]);
}
