<?php
/**
 * 직접 로그인 API (관리자, 서브관리자, 판매자용)
 */

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
    
    // 역할에 따라 리다이렉트 URL 결정
    $redirect = '/MVNO/';
    if ($user['role'] === 'admin' || $user['role'] === 'sub_admin') {
        $redirect = '/MVNO/admin/'; // 관리자는 관리자 센터로 이동
    } elseif ($user['role'] === 'seller') {
        $redirect = '/MVNO/'; // 판매자 대시보드가 있으면 그쪽으로
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
