<?php
/**
 * 판매자 예치금 잔액 조회 API
 * 경로: /api/get-seller-balance.php
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/data/db-config.php';
require_once __DIR__ . '/../includes/data/auth-functions.php';

// 관리자 인증 체크
$currentUser = getCurrentUser();
if (!$currentUser || !isAdmin($currentUser['user_id'])) {
    echo json_encode(['success' => false, 'error' => '권한이 없습니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = getDBConnection();

if (!$pdo) {
    echo json_encode(['success' => false, 'error' => '데이터베이스 연결에 실패했습니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$sellerId = trim($_GET['seller_id'] ?? '');

if (empty($sellerId)) {
    echo json_encode(['success' => false, 'error' => '판매자 아이디를 입력해주세요.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // 판매자 존재 확인 및 정보 조회
    $stmt = $pdo->prepare("SELECT user_id, name, company_name FROM users WHERE user_id = :seller_id AND role = 'seller'");
    $stmt->execute([':seller_id' => $sellerId]);
    $seller = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$seller) {
        echo json_encode(['success' => false, 'error' => '판매자를 찾을 수 없습니다.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 예치금 잔액 조회
    $stmt = $pdo->prepare("SELECT balance FROM seller_deposit_accounts WHERE seller_id = :seller_id");
    $stmt->execute([':seller_id' => $sellerId]);
    $balanceData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $balance = floatval($balanceData['balance'] ?? 0);
    
    echo json_encode([
        'success' => true,
        'balance' => $balance,
        'seller' => [
            'user_id' => $seller['user_id'],
            'name' => $seller['name'] ?? '',
            'company_name' => $seller['company_name'] ?? ''
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    error_log('Get seller balance error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => '잔액 조회 중 오류가 발생했습니다.'], JSON_UNESCAPED_UNICODE);
}
?>
