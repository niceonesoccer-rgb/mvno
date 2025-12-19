<?php
/**
 * 기존 주문번호를 5자리에서 4자리로 되돌리는 스크립트
 * 형식: YYMMDDHH-00012 -> YYMMDDHH-0012
 */

require_once __DIR__ . '/../includes/data/db-config.php';

echo "주문번호 업데이트 시작...\n\n";

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('데이터베이스 연결에 실패했습니다.');
    }
    
    // 트랜잭션 시작
    $pdo->beginTransaction();
    
    // 5자리 형식의 주문번호를 모두 가져오기
    $stmt = $pdo->query("
        SELECT id, order_number 
        FROM product_applications 
        WHERE order_number IS NOT NULL 
        AND order_number REGEXP '^[0-9]{8}-[0-9]{5}$'
        ORDER BY id
    ");
    
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalCount = count($orders);
    $updatedCount = 0;
    $errorCount = 0;
    
    echo "총 {$totalCount}개의 주문번호를 업데이트합니다.\n\n";
    
    foreach ($orders as $order) {
        $oldOrderNumber = $order['order_number'];
        $id = $order['id'];
        
        // 주문번호 파싱: YYMMDDHH-00012 형식
        if (preg_match('/^(\d{8})-(\d{5})$/', $oldOrderNumber, $matches)) {
            $timePrefix = $matches[1]; // YYMMDDHH
            $sequence = $matches[2];   // 00012
            
            // 순번을 4자리로 변환 (앞의 0 제거)
            $newSequence = str_pad(intval($sequence), 4, '0', STR_PAD_LEFT);
            $newOrderNumber = $timePrefix . '-' . $newSequence;
            
            // 새 주문번호가 이미 존재하는지 확인
            $checkStmt = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM product_applications 
                WHERE order_number = :order_number AND id != :id
            ");
            $checkStmt->execute([
                ':order_number' => $newOrderNumber,
                ':id' => $id
            ]);
            $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (($checkResult['count'] ?? 0) > 0) {
                echo "[경고] 주문번호 {$newOrderNumber}가 이미 존재합니다. ID: {$id}, 기존: {$oldOrderNumber}\n";
                $errorCount++;
                continue;
            }
            
            // 주문번호 업데이트
            $updateStmt = $pdo->prepare("
                UPDATE product_applications 
                SET order_number = :new_order_number 
                WHERE id = :id
            ");
            $updateStmt->execute([
                ':new_order_number' => $newOrderNumber,
                ':id' => $id
            ]);
            
            $updatedCount++;
            
            if ($updatedCount % 100 == 0) {
                echo "진행 중... {$updatedCount}/{$totalCount}\n";
            }
        } else {
            echo "[오류] 주문번호 형식이 올바르지 않습니다. ID: {$id}, 주문번호: {$oldOrderNumber}\n";
            $errorCount++;
        }
    }
    
    // 트랜잭션 커밋
    $pdo->commit();
    
    echo "\n========================================\n";
    echo "업데이트 완료!\n";
    echo "총 처리: {$totalCount}개\n";
    echo "성공: {$updatedCount}개\n";
    echo "실패/건너뜀: {$errorCount}개\n";
    echo "========================================\n";
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\n[오류] 업데이트 중 오류가 발생했습니다: " . $e->getMessage() . "\n";
    exit(1);
}








