<?php
/**
 * 기존 탈퇴 요청한 판매자들의 상품을 판매종료 처리
 */

require_once __DIR__ . '/includes/data/auth-functions.php';

$pdo = getDBConnection();
if (!$pdo) {
    die("DB 연결 실패\n");
}

try {
    // 탈퇴 요청한 판매자 목록 조회
    $stmt = $pdo->query("
        SELECT DISTINCT u.user_id
        FROM users u
        LEFT JOIN seller_profiles sp ON sp.user_id = u.user_id
        WHERE u.role = 'seller'
        AND (u.withdrawal_requested = 1 OR sp.withdrawal_requested = 1)
        AND (u.withdrawal_completed = 0 OR u.withdrawal_completed IS NULL)
        AND (sp.withdrawal_completed = 0 OR sp.withdrawal_completed IS NULL)
    ");
    
    $sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalSellers = count($sellers);
    
    echo "=== 탈퇴 요청한 판매자 상품 판매종료 처리 ===\n\n";
    echo "탈퇴 요청한 판매자 수: {$totalSellers}\n\n";
    
    if ($totalSellers === 0) {
        echo "처리할 판매자가 없습니다.\n";
        exit;
    }
    
    $pdo->beginTransaction();
    
    $totalProducts = 0;
    foreach ($sellers as $seller) {
        $sellerId = $seller['user_id'];
        
        // 해당 판매자의 활성 상품 개수 확인
        $countStmt = $pdo->prepare("
            SELECT COUNT(*) as cnt
            FROM products
            WHERE seller_id = :seller_id
            AND status = 'active'
        ");
        $countStmt->execute([':seller_id' => $sellerId]);
        $activeCount = $countStmt->fetch(PDO::FETCH_ASSOC)['cnt'];
        
        if ($activeCount > 0) {
            // 상품 상태를 inactive로 변경
            $updateStmt = $pdo->prepare("
                UPDATE products
                SET status = 'inactive',
                    updated_at = NOW()
                WHERE seller_id = :seller_id
                AND status = 'active'
            ");
            $updateStmt->execute([':seller_id' => $sellerId]);
            $updatedCount = $updateStmt->rowCount();
            
            echo "판매자 {$sellerId}: {$updatedCount}개 상품 판매종료 처리\n";
            $totalProducts += $updatedCount;
        } else {
            echo "판매자 {$sellerId}: 활성 상품 없음\n";
        }
    }
    
    $pdo->commit();
    
    echo "\n=== 처리 완료 ===\n";
    echo "총 {$totalSellers}명의 판매자 중 {$totalProducts}개 상품이 판매종료 처리되었습니다.\n";
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "오류 발생: " . $e->getMessage() . "\n";
    exit(1);
}
