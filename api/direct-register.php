<?php
/**
 * 모달(또는 AJAX)용 일반 회원가입 API
 *
 * - JSON 응답만 반환
 * - 성공 시: success=true
 */

// 에러 출력 방지 (JSON만 반환)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// JSON 헤더 먼저 설정
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../includes/data/auth-functions.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '서버 오류가 발생했습니다.',
        'error' => $e->getMessage()
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
    exit;
}

$role = $_POST['role'] ?? 'user';
// 아이디 정책: 영문 소문자 + 숫자만 허용, 대문자는 소문자로 정규화
$userId = strtolower(trim($_POST['user_id'] ?? ''));
$password = $_POST['password'] ?? '';
$passwordConfirm = $_POST['password_confirm'] ?? '';
$email = trim($_POST['email'] ?? '');
$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$userIdChecked = $_POST['user_id_checked'] ?? '0';

// 모달 회원가입은 일반 회원만 허용
if ($role !== 'user') {
    echo json_encode(['success' => false, 'message' => '일반 회원만 회원가입이 가능합니다.']);
    exit;
}

// 필수 검증
if (empty($userId) || empty($phone) || empty($name) || empty($email)) {
    echo json_encode(['success' => false, 'message' => '아이디, 휴대폰번호, 이름, 이메일은 필수 입력 항목입니다.']);
    exit;
}

// 아이디 형식 검증 (영문 소문자 + 숫자, 5-20자)
if (!preg_match('/^[a-z0-9]{5,20}$/', $userId)) {
    echo json_encode(['success' => false, 'message' => '아이디는 영문 소문자와 숫자만 사용할 수 있으며 5자 이상 20자 이내여야 합니다.']);
    exit;
}

// 중복확인 강제
if ($userIdChecked !== '1') {
    echo json_encode(['success' => false, 'message' => '아이디 중복확인을 진행해주세요.']);
    exit;
}

// 이름 길이
if (mb_strlen($name) > 15) {
    echo json_encode(['success' => false, 'message' => '이름은 15자 이내로 입력해주세요.']);
    exit;
}

// 전화번호 형식 (010만)
if (!preg_match('/^010-\d{4}-\d{4}$/', $phone) || strpos($phone, '010-') !== 0) {
    echo json_encode(['success' => false, 'message' => '휴대폰번호는 010으로 시작하는 번호만 가능합니다. (010-XXXX-XXXX 형식)']);
    exit;
}

// 이메일 검증 (기존 register.php 정책과 동일하게 간단 검증 + 길이 제한)
if (strlen($email) > 50) {
    echo json_encode(['success' => false, 'message' => '이메일 주소는 50자 이내로 입력해주세요.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => '올바른 이메일 형식이 아닙니다.']);
    exit;
}

$emailParts = explode('@', $email);
if (count($emailParts) !== 2) {
    echo json_encode(['success' => false, 'message' => '올바른 이메일 형식이 아닙니다.']);
    exit;
}

$emailLocal = $emailParts[0];
if (!preg_match('/^[a-z0-9]+$/', $emailLocal)) {
    echo json_encode(['success' => false, 'message' => '이메일 아이디는 영문 소문자와 숫자만 사용할 수 있습니다.']);
    exit;
}

$emailDomain = strtolower($emailParts[1]);
if (!preg_match('/^[a-z0-9.-]+$/', $emailDomain)) {
    echo json_encode(['success' => false, 'message' => '올바른 이메일 도메인 형식이 아닙니다.']);
    exit;
}
if (strpos($emailDomain, '.') === false || strpos($emailDomain, '.') === 0 || substr($emailDomain, -1) === '.') {
    echo json_encode(['success' => false, 'message' => '올바른 이메일 도메인 형식이 아닙니다.']);
    exit;
}

// 비밀번호 검증
if ($password !== $passwordConfirm) {
    echo json_encode(['success' => false, 'message' => '비밀번호가 일치하지 않습니다.']);
    exit;
}

if (strlen($password) < 8 || strlen($password) > 20) {
    echo json_encode(['success' => false, 'message' => '비밀번호는 8자 이상 20자 이내로 입력해주세요.']);
    exit;
}

$hasLetter = preg_match('/[A-Za-z]/', $password);
$hasNumber = preg_match('/[0-9]/', $password);
$hasSpecialChar = preg_match('/[@#$%^&*!?_\-=]/', $password);
$combinationCount = ($hasLetter ? 1 : 0) + ($hasNumber ? 1 : 0) + ($hasSpecialChar ? 1 : 0);
if ($combinationCount < 2) {
    echo json_encode(['success' => false, 'message' => '비밀번호는 영문자, 숫자, 특수문자(@#$%^&*!?_-=) 중 2가지 이상 조합해야 합니다.']);
    exit;
}

// 레이스 컨디션 방지: 최종 중복 확인
if (getUserById($userId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '이미 사용 중인 아이디입니다.']);
    exit;
}

// 가입 처리
try {
    $result = registerDirectUser($userId, $password, $email, $name, 'user', ['phone' => $phone]);
    if (!($result['success'] ?? false)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $result['message'] ?? '회원가입에 실패했습니다.']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => '회원가입이 완료되었습니다. 로그인해주세요.'
    ]);
} catch (Exception $e) {
    error_log('direct-register error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '회원가입 처리 중 오류가 발생했습니다.',
        'error' => $e->getMessage()
    ]);
    exit;
} catch (Error $e) {
    error_log('direct-register fatal error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '회원가입 처리 중 오류가 발생했습니다.',
        'error' => $e->getMessage()
    ]);
    exit;
}


















