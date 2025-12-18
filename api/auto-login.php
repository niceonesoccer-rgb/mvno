<?php
/**
 * 자동 로그인 API (테스트용)
 * 회원가입되어 있다고 가정하고 기본 사용자로 자동 로그인
 */

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/data/auth-functions.php';

header('Content-Type: application/json');

// 이미 로그인되어 있으면 현재 사용자 정보 반환
if (isLoggedIn()) {
    $currentUser = getCurrentUser();
    echo json_encode([
        'success' => true,
        'message' => '이미 로그인되어 있습니다.',
        'user' => [
            'user_id' => $currentUser['user_id'] ?? '',
            'name' => $currentUser['name'] ?? '사용자',
            'role' => $currentUser['role'] ?? 'user'
        ]
    ]);
    exit;
}

// 기존 회원 목록에서 회원 찾기
$pdo = getDBConnection();
if (!$pdo) {
    echo json_encode([
        'success' => false,
        'message' => 'DB 연결에 실패했습니다.'
    ]);
    exit;
}

try {
    // 테스트용: user 역할만 순환 로그인 (SNS/일반 포함)
    $stmt = $pdo->query("
        SELECT user_id, name, role
        FROM users
        WHERE role = 'user'
        ORDER BY created_at DESC, user_id DESC
        LIMIT 200
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('auto-login DB error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '사용자 목록을 불러오지 못했습니다.'
    ]);
    exit;
}

if (empty($users)) {
    echo json_encode([
        'success' => false,
        'message' => '등록된 회원이 없습니다.'
    ]);
    exit;
}

$totalUsers = count($users);

// 마지막 로그인한 회원 인덱스를 세션에서 가져오기
$lastUserIndex = isset($_SESSION['last_auto_login_index']) ? intval($_SESSION['last_auto_login_index']) : -1;

// 다음 회원 인덱스 (순환)
$nextUserIndex = ($lastUserIndex + 1) % $totalUsers;

// 다음 회원으로 로그인
$user = $users[$nextUserIndex];

// 세션에 현재 로그인한 회원 인덱스 저장
$_SESSION['last_auto_login_index'] = $nextUserIndex;

// 로그인 처리
loginUser($user['user_id']);

echo json_encode([
    'success' => true,
    'message' => '로그인되었습니다.',
    'user' => [
        'user_id' => $user['user_id'],
        'name' => $user['name'] ?? '사용자',
        'role' => $user['role'] ?? 'user'
    ]
]);















