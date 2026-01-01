<?php
/**
 * 통신사폰 페이지 광고 로테이션 헬퍼 함수
 * 광고 상품 ID만 반환 (데이터 변환은 getPhonesData 재사용)
 */

require_once __DIR__ . '/../includes/data/db-config.php';

/**
 * 통신사폰 광고 상품 ID 조회 및 로테이션 처리
 * @return array [advertisementProductIds, rotationDuration]
 */
function getMnoAdvertisementProductIds() {
    $advertisementProductIds = [];
    $rotationDuration = null;
    
    try {
        $pdo = getDBConnection();
        if (!$pdo) {
            return [[], null];
        }
        
        // 로테이션 시간 가져오기
        try {
            $durationStmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'advertisement_rotation_duration'");
            $durationStmt->execute();
            $durationValue = $durationStmt->fetchColumn();
            if ($durationValue) {
                $rotationDuration = intval($durationValue);
            }
        } catch (PDOException $e) {
            error_log('Rotation duration 조회 오류: ' . $e->getMessage());
        }
        
        // 광고중인 상품 ID 조회 (product_type = 'mno')
        $adStmt = $pdo->prepare("
            SELECT ra.product_id, ra.display_order, ra.created_at
            FROM rotation_advertisements ra
            INNER JOIN products p ON ra.product_id = p.id
            WHERE ra.product_type = 'mno'
            AND ra.status = 'active'
            AND p.status = 'active'
            AND ra.end_datetime > NOW()
            ORDER BY ra.display_order ASC, ra.created_at ASC
        ");
        $adStmt->execute();
        $adProducts = $adStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 서버 사이드 로테이션
        if (count($adProducts) > 1 && $rotationDuration !== null && $rotationDuration > 0) {
            date_default_timezone_set('Asia/Seoul');
            $today = date('Y-m-d');
            $baseTimestamp = strtotime($today . ' 00:00:00');
            $currentTimestamp = time();
            $elapsedSeconds = $currentTimestamp - $baseTimestamp;
            $rotationCycles = floor($elapsedSeconds / $rotationDuration);
            $adCount = count($adProducts);
            $rotationOffset = $rotationCycles % $adCount;
            
            if ($rotationOffset > 0) {
                $adProducts = array_merge(
                    array_slice($adProducts, $rotationOffset),
                    array_slice($adProducts, 0, $rotationOffset)
                );
            }
        }
        
        // 광고 상품 ID 목록 추출
        foreach ($adProducts as $adProduct) {
            $productId = (int)($adProduct['product_id'] ?? 0);
            if ($productId > 0) {
                $advertisementProductIds[] = $productId;
            }
        }
        
    } catch (PDOException $e) {
        error_log("[MNO 광고] Error fetching MNO advertisement product IDs: " . $e->getMessage());
    }
    
    return [$advertisementProductIds, $rotationDuration];
}
