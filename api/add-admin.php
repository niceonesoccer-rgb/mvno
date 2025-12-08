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
    // 기존 관리자 확인 (admins.json에서만)
    $adminsFile = getAdminsFilePath();
    $admins = [];
    
    if (file_exists($adminsFile)) {
        $data = json_decode(file_get_contents($adminsFile), true) ?: ['admins' => []];
        $admins = $data['admins'] ?? [];
    }
    
    // 아이디 중복 확인
    $isDuplicate = false;
    foreach ($admins as $admin) {
        if (isset($admin['user_id']) && $admin['user_id'] === $userId) {
            $isDuplicate = true;
            header('Location: /MVNO/admin/users/member-list.php?tab=admins&error=duplicate_id');
            exit;
        }
    }
    
    if (!$isDuplicate) {
        // 관리자 추가
        $newAdmin = [
            'user_id' => $userId,
            'phone' => $phone,
            'name' => $name,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $currentUser['user_id'] ?? 'system'
        ];
        
        $admins[] = $newAdmin;
        $data = ['admins' => $admins];
        
        if (file_put_contents($adminsFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            // 추가 후 목록으로 리다이렉트
            header('Location: /MVNO/admin/users/member-list.php?tab=admins&success=add');
            exit;
        } else {
            header('Location: /MVNO/admin/users/member-list.php?tab=admins&error=save_failed');
            exit;
        }
    }
}





