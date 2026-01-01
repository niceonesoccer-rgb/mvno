<?php
/**
 * 광고 신청 API
 * 경로: /api/advertisement-apply.php
 */

// 에러 출력 방지 (JSON 응답이므로)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/data/db-config.php';
require_once __DIR__ . '/../includes/data/auth-functions.php';

session_start();

$currentUser = getCurrentUser();
$sellerId = $currentUser['user_id'] ?? '';

if (empty($sellerId) || ($currentUser['role'] ?? '') !== 'seller') {
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$productId = intval($_POST['product_id'] ?? 0);
$advertisementDays = intval($_POST['advertisement_days'] ?? 0);

$pdo = getDBConnection();
if (!$pdo) {
    echo json_encode(['success' => false, 'message' => '데이터베이스 연결에 실패했습니다.']);
    exit;
}

if ($productId <= 0 || $advertisementDays <= 0) {
    echo json_encode(['success' => false, 'message' => '모든 필드를 올바르게 선택해주세요.']);
    exit;
}

try {
    // system_settings에서 현재 로테이션 시간 가져오기
    $rotationDuration = 30; // 기본값
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'advertisement_rotation_duration'");
        $stmt->execute();
        $durationValue = $stmt->fetchColumn();
        if ($durationValue) {
            $rotationDuration = intval($durationValue);
        }
    } catch (PDOException $e) {
        error_log('Rotation duration 조회 오류: ' . $e->getMessage());
    }
    
    $pdo->beginTransaction();
    
    // 상품 정보 조회
    $stmt = $pdo->prepare("SELECT id, seller_id, product_type, status FROM products WHERE id = :id AND seller_id = :seller_id");
    $stmt->execute([':id' => $productId, ':seller_id' => $sellerId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        throw new Exception('상품을 찾을 수 없습니다.');
    }
    
    if ($product['status'] !== 'active') {
        throw new Exception('판매중인 상품만 광고할 수 있습니다.');
    }
    
    // 같은 상품의 활성화된 광고 중복 체크 (동시성 문제 방지를 위해 FOR UPDATE 사용)
    $stmt = $pdo->prepare("
        SELECT id, start_datetime, end_datetime, status 
        FROM rotation_advertisements 
        WHERE product_id = :product_id 
        AND status = 'active' 
        AND end_datetime > NOW()
        FOR UPDATE
    ");
    $stmt->execute([':product_id' => $productId]);
    $existingAd = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($existingAd) {
        // 기존 광고 정보를 포함한 상세 메시지
        $startDate = date('Y-m-d H:i', strtotime($existingAd['start_datetime']));
        $endDate = date('Y-m-d H:i', strtotime($existingAd['end_datetime']));
        error_log("중복 광고 신청 시도 감지 - 판매자 ID: {$sellerId}, 상품 ID: {$productId}, 기존 광고 ID: {$existingAd['id']}, 시작일: {$existingAd['start_datetime']}, 종료일: {$existingAd['end_datetime']}");
        throw new Exception("이미 광고 중인 상품입니다.\n광고 기간: {$startDate} ~ {$endDate}\n광고가 종료된 후 다시 신청해주세요.");
    }
    
    // 가격 조회
    // rotation_advertisement_prices 테이블은 mno_sim (언더스코어)를 사용하므로 변환
    $priceProductType = $product['product_type'];
    if ($priceProductType === 'mno-sim') {
        $priceProductType = 'mno_sim';
    }
    
    $stmt = $pdo->prepare("
        SELECT price FROM rotation_advertisement_prices 
        WHERE product_type = :product_type 
        AND advertisement_days = :advertisement_days 
        AND is_active = 1
    ");
    $stmt->execute([
        ':product_type' => $priceProductType,
        ':advertisement_days' => $advertisementDays
    ]);
    $priceData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$priceData) {
        throw new Exception('선택한 조건의 가격 정보를 찾을 수 없습니다.');
    }
    
    $supplyAmount = floatval($priceData['price']); // 공급가액
    $taxAmount = $supplyAmount * 0.1; // 부가세 (10%)
    $totalAmount = $supplyAmount + $taxAmount; // 부가세 포함 총액
    
    // 예치금 잔액 확인 (부가세 포함 금액으로 확인)
    $stmt = $pdo->prepare("SELECT balance FROM seller_deposit_accounts WHERE seller_id = :seller_id FOR UPDATE");
    $stmt->execute([':seller_id' => $sellerId]);
    $balanceData = $stmt->fetch(PDO::FETCH_ASSOC);
    $currentBalance = floatval($balanceData['balance'] ?? 0);
    
    if ($currentBalance < $totalAmount) {
        throw new Exception('예치금 잔액이 부족합니다. 예치금을 충전해주세요.');
    }
    
    // 광고 등록
    $startDatetime = date('Y-m-d H:i:s');
    $endDatetime = date('Y-m-d H:i:s', strtotime($startDatetime) + ($advertisementDays * 86400));
    
    // rotation_advertisements 테이블도 mno_sim (언더스코어)를 사용하므로 변환
    $adProductType = $product['product_type'];
    if ($adProductType === 'mno-sim') {
        $adProductType = 'mno_sim';
    }
    
    // INSERT 전에 다시 한 번 중복 체크 (이중 체크 - 레이스 컨디션 방지)
    $stmt = $pdo->prepare("
        SELECT id, start_datetime, end_datetime 
        FROM rotation_advertisements 
        WHERE product_id = :product_id 
        AND status = 'active' 
        AND end_datetime > NOW()
        FOR UPDATE
    ");
    $stmt->execute([':product_id' => $productId]);
    $duplicateAd = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($duplicateAd) {
        error_log("INSERT 직전 중복 체크 실패 - 판매자 ID: {$sellerId}, 상품 ID: {$productId}, 기존 광고 ID: {$duplicateAd['id']}, 종료일: {$duplicateAd['end_datetime']}");
        throw new Exception('이미 광고 중인 상품입니다. 잠시 후 다시 시도해주세요.');
    }
    
    error_log("광고 신청 처리 시작 - 판매자 ID: {$sellerId}, 상품 ID: {$productId}, 광고 기간: {$advertisementDays}일, 가격: {$supplyAmount}원");
    
    $stmt = $pdo->prepare("
        INSERT INTO rotation_advertisements 
        (product_id, seller_id, product_type, rotation_duration, advertisement_days, price, start_datetime, end_datetime, status)
        VALUES (:product_id, :seller_id, :product_type, :rotation_duration, :advertisement_days, :price, :start_datetime, :end_datetime, 'active')
    ");
    $stmt->execute([
        ':product_id' => $productId,
        ':seller_id' => $sellerId,
        ':product_type' => $adProductType,
        ':rotation_duration' => $rotationDuration,
        ':advertisement_days' => $advertisementDays,
        ':price' => $supplyAmount, // 광고 테이블에는 공급가액 저장
        ':start_datetime' => $startDatetime,
        ':end_datetime' => $endDatetime
    ]);
    
    $adId = $pdo->lastInsertId();
    
    // 예치금 차감 (부가세 포함 총액 차감)
    $newBalance = $currentBalance - $totalAmount;
    $pdo->prepare("UPDATE seller_deposit_accounts SET balance = :balance, updated_at = NOW() WHERE seller_id = :seller_id")
        ->execute([':balance' => $newBalance, ':seller_id' => $sellerId]);
    
    // 예치금 내역 기록 (부가세 포함 총액 차감)
    $pdo->prepare("
        INSERT INTO seller_deposit_ledger 
        (seller_id, transaction_type, amount, balance_before, balance_after, advertisement_id, description, created_at)
        VALUES (:seller_id, 'withdraw', :amount, :balance_before, :balance_after, :advertisement_id, :description, NOW())
    ")->execute([
        ':seller_id' => $sellerId,
        ':amount' => -$totalAmount,
        ':balance_before' => $currentBalance,
        ':balance_after' => $newBalance,
        ':advertisement_id' => $adId,
        ':description' => '광고 신청 차감'
    ]);
    
    $pdo->commit();
    error_log("광고 신청 성공 - 판매자 ID: {$sellerId}, 상품 ID: {$productId}, 광고 ID: {$adId}, 시작일: {$startDatetime}, 종료일: {$endDatetime}");
    echo json_encode(['success' => true, 'message' => '광고 신청이 완료되었습니다. 광고가 즉시 시작됩니다.'], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Advertisement apply error - 판매자 ID: ' . ($sellerId ?? 'unknown') . ', 상품 ID: ' . ($productId ?? 'unknown') . ', 오류: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Error $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Advertisement apply fatal error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '서버 오류가 발생했습니다: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}
