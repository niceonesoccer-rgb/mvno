<?php
/**
 * 직접 로그인 처리 API
 */

require_once __DIR__ . '/../includes/data/auth-functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
    exit;
}

$userId = $_POST['user_id'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($userId) || empty($password)) {
    echo json_encode(['success' => false, 'message' => '아이디와 비밀번호를 입력해주세요.']);
    exit;
}

$result = loginDirectUser($userId, $password);

if ($result['success']) {
    // 로그인 성공 후 사용자 역할 확인
    $user = getCurrentUser();
    if ($user && (isAdmin() || isSeller())) {
        // 관리자 또는 판매자는 관리자 페이지로 리다이렉트
        echo json_encode(['success' => true, 'redirect' => '/MVNO/admin/']);
    } else {
        // 일반 사용자는 메인 페이지로 리다이렉트
        echo json_encode(['success' => true, 'redirect' => '/MVNO/']);
    }
} else {
    echo json_encode(['success' => false, 'message' => $result['message']]);
}

