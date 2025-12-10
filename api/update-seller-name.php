<?php
/**
 * 판매자명 업데이트 API
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/data/auth-functions.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 판매자 인증 체크
$currentUser = getCurrentUser();
if (!$currentUser || $currentUser['role'] !== 'seller') {
    echo json_encode(['success' => false, 'message' => '인증이 필요합니다.']);
    exit;
}

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
    exit;
}

// 판매자명 가져오기
$sellerName = trim($_POST['seller_name'] ?? '');

// 판매자명 검증
if (empty($sellerName)) {
    echo json_encode(['success' => false, 'message' => '판매자명을 입력해주세요.']);
    exit;
}

if (mb_strlen($sellerName) < 2) {
    echo json_encode(['success' => false, 'message' => '판매자명은 최소 2자 이상 입력해주세요.']);
    exit;
}

if (mb_strlen($sellerName) > 50) {
    echo json_encode(['success' => false, 'message' => '판매자명은 최대 50자까지 입력 가능합니다.']);
    exit;
}

// 중복 검사
$userId = $currentUser['user_id'];
$sellersFile = getSellersFilePath();
$allSellers = [];

if (file_exists($sellersFile)) {
    $data = json_decode(file_get_contents($sellersFile), true) ?: ['sellers' => []];
    $allSellers = $data['sellers'] ?? [];
}

$sellerNameLower = mb_strtolower($sellerName, 'UTF-8');
foreach ($allSellers as $otherSeller) {
    if (isset($otherSeller['user_id']) && $otherSeller['user_id'] === $userId) {
        continue; // 자기 자신은 제외
    }
    if (isset($otherSeller['seller_name']) && !empty($otherSeller['seller_name'])) {
        $otherSellerNameLower = mb_strtolower($otherSeller['seller_name'], 'UTF-8');
        if ($otherSellerNameLower === $sellerNameLower) {
            echo json_encode(['success' => false, 'message' => '이미 사용 중인 판매자명입니다.']);
            exit;
        }
    }
}

// 판매자명 업데이트
$updated = false;
foreach ($allSellers as &$seller) {
    if (isset($seller['user_id']) && $seller['user_id'] === $userId) {
        $seller['seller_name'] = $sellerName;
        $seller['updated_at'] = date('Y-m-d H:i:s');
        $updated = true;
        break;
    }
}

if ($updated) {
    file_put_contents($sellersFile, json_encode(['sellers' => $allSellers], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo json_encode(['success' => true, 'message' => '판매자명이 저장되었습니다.']);
} else {
    echo json_encode(['success' => false, 'message' => '판매자 정보를 찾을 수 없습니다.']);
}

