<?php
/**
 * 광고 취소 처리 API (관리자 전용)
 * 경로: /api/cancel-advertisement.php
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/data/db-config.php';
require_once __DIR__ . '/../includes/data/auth-functions.php';

// 관리자 권한 확인
$currentUser = getCurrentUser();
if (!$currentUser || !in_array($currentUser['role'], ['admin', 'sub_admin'])) {
    echo json_encode([
        'success' => false,
        'message' => '관리자 권한이 필요합니다.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => '잘못된 요청 방법입니다.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = getDBConnection();

if (!$pdo) {
    echo json_encode([
        'success' => false,
        'message' => '데이터베이스 연결에 실패했습니다.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$advertisementId = intval($_POST['advertisement_id'] ?? 0);

if ($advertisementId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => '유효하지 않은 광고 ID입니다.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // 광고 정보 조회
    $stmt = $pdo->prepare("
        SELECT 
            ra.*,
            p.status as product_status
        FROM rotation_advertisements ra
        LEFT JOIN products p ON ra.product_id = p.id
        WHERE ra.id = :id
        FOR UPDATE
    ");
    $stmt->execute([':id' => $advertisementId]);
    $ad = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ad) {
        throw new Exception('광고를 찾을 수 없습니다.');
    }
    
    // 이미 취소되었거나 종료된 광고는 취소 불가
    if ($ad['status'] === 'cancelled') {
        throw new Exception('이미 취소된 광고입니다.');
    }
    
    if ($ad['status'] === 'expired') {
        throw new Exception('이미 종료된 광고입니다.');
    }
    
    // 광고 취소 처리 (status를 cancelled로 변경)
    $stmt = $pdo->prepare("
        UPDATE rotation_advertisements 
        SET status = 'cancelled', 
            updated_at = NOW()
        WHERE id = :id
    ");
    $stmt->execute([':id' => $advertisementId]);
    
    // 환불 금액 계산 (부가세 포함 총액)
    // rotation_advertisements.price는 공급가액만 저장되어 있으므로 부가세를 추가
    $supplyAmount = floatval($ad['price'] ?? 0);
    $taxAmount = $supplyAmount * 0.1;
    $totalRefundAmount = $supplyAmount + $taxAmount;
    
    // 예치금 잔액 조회 및 업데이트
    $stmt = $pdo->prepare("
        SELECT balance FROM seller_deposit_accounts 
        WHERE seller_id = :seller_id 
        FOR UPDATE
    ");
    $stmt->execute([':seller_id' => $ad['seller_id']]);
    $balanceData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$balanceData) {
        // 예치금 계정이 없으면 생성
        $stmt = $pdo->prepare("
            INSERT INTO seller_deposit_accounts (seller_id, balance, created_at, updated_at)
            VALUES (:seller_id, 0, NOW(), NOW())
        ");
        $stmt->execute([':seller_id' => $ad['seller_id']]);
        $currentBalance = 0;
    } else {
        $currentBalance = floatval($balanceData['balance'] ?? 0);
    }
    
    // 예치금 환불 (부가세 포함 총액 환불)
    $newBalance = $currentBalance + $totalRefundAmount;
    $stmt = $pdo->prepare("
        UPDATE seller_deposit_accounts 
        SET balance = :balance, 
            updated_at = NOW() 
        WHERE seller_id = :seller_id
    ");
    $stmt->execute([
        ':balance' => $newBalance,
        ':seller_id' => $ad['seller_id']
    ]);
    
    // 예치금 내역 기록 (환불)
    $stmt = $pdo->prepare("
        INSERT INTO seller_deposit_ledger 
        (seller_id, transaction_type, amount, balance_before, balance_after, advertisement_id, description, created_at)
        VALUES (:seller_id, 'refund', :amount, :balance_before, :balance_after, :advertisement_id, :description, NOW())
    ");
    $stmt->execute([
        ':seller_id' => $ad['seller_id'],
        ':amount' => $totalRefundAmount,
        ':balance_before' => $currentBalance,
        ':balance_after' => $newBalance,
        ':advertisement_id' => $advertisementId,
        ':description' => '광고 취소 환불'
    ]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => '광고가 취소되었고 환불이 완료되었습니다.',
        'refund_amount' => $totalRefundAmount
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Cancel advertisement error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
