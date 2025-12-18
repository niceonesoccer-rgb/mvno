<?php
/**
 * 관리자 추가 API
 */

require_once __DIR__ . '/../includes/data/auth-functions.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 관리자 권한 체크
if (!isAdmin()) {
    header('Location: /MVNO/admin/');
    exit;
}

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /MVNO/admin/users/member-list.php?tab=admins&error=invalid_request');
    exit;
}

if (!isset($_POST['action']) || $_POST['action'] !== 'add_admin') {
    header('Location: /MVNO/admin/users/member-list.php?tab=admins&error=invalid_action');
    exit;
}

$currentUser = getCurrentUser();
$userId = strtolower(trim($_POST['user_id'] ?? '')); // 소문자로 변환
$password = $_POST['password'] ?? '';
$phone = trim($_POST['phone'] ?? '');
$name = trim($_POST['name'] ?? '');
$role = $_POST['role'] ?? 'sub_admin';
$passwordConfirm = $_POST['password_confirm'] ?? '';

// 비밀번호 확인 검증
if ($password !== $passwordConfirm) {
    header('Location: /MVNO/admin/users/member-list.php?tab=admins&error=password_mismatch');
    exit;
}

if (empty($userId) || empty($password) || empty($phone) || empty($name)) {
    header('Location: /MVNO/admin/users/member-list.php?tab=admins&error=empty_fields');
    exit;
} elseif (!preg_match('/^[a-z0-9]{4,20}$/', $userId)) {
    header('Location: /MVNO/admin/users/member-list.php?tab=admins&error=invalid_id');
    exit;
} elseif (strlen($password) < 8) {
    header('Location: /MVNO/admin/users/member-list.php?tab=admins&error=password_length');
    exit;
} else {
    // A안: admin/sub_admin도 users(DB) + admin_profiles로 저장
    $additional = [
        'phone' => $phone,
        'created_by' => $currentUser['user_id'] ?? 'system'
    ];

    $result = registerDirectUser($userId, $password, null, $name, $role, $additional);
    if (!$result['success']) {
        $err = $result['message'] ?? 'save_failed';
        header('Location: /MVNO/admin/users/member-list.php?tab=admins&error=' . urlencode($err));
        exit;
    }

    header('Location: /MVNO/admin/users/member-list.php?tab=admins&success=add');
    exit;
}










