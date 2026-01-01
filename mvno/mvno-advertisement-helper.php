<?php
/**
 * 알뜰폰 페이지 광고 로테이션 헬퍼 함수
 * 통신사단독유심과 동일한 로직을 알뜰폰에 적용
 */

require_once __DIR__ . '/../includes/data/db-config.php';
require_once __DIR__ . '/../includes/data/plan-data.php';
require_once __DIR__ . '/../includes/data/product-functions.php';

/**
 * 알뜰폰 광고 상품 조회 및 로테이션 처리
 * @return array [advertisementProducts, rotationDuration, advertisementProductIds]
 */
function getMvnoAdvertisementProducts() {
    $advertisementProducts = [];
    $advertisementProductIds = [];
    $rotationDuration = null;
    
    try {
        $pdo = getDBConnection();
        if (!$pdo) {
            return [$advertisementProducts, $rotationDuration, $advertisementProductIds];
        }
        
        // 로테이션 시간 가져오기
        try {
            $durationStmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'advertisement_rotation_duration'");
            $durationStmt->execute();
            $durationValue = $durationStmt->fetchColumn();
            if ($durationValue) {
                $rotationDuration = intval($durationValue);
            } else {
                error_log('경고: advertisement_rotation_duration 설정이 DB에 없습니다.');
                $rotationDuration = 0;
            }
        } catch (PDOException $e) {
            error_log('Rotation duration 조회 오류: ' . $e->getMessage());
            $rotationDuration = 0;
        }
        
        // 광고중인 상품 조회 (product_type = 'mvno')
        // getPlansDataFromDB와 동일한 컬럼 사용
        $adStmt = $pdo->prepare("
            SELECT 
                p.id,
                p.seller_id,
                p.status,
                p.view_count,
                p.favorite_count,
                p.review_count,
                p.share_count,
                p.application_count,
                mvno.provider,
                mvno.service_type,
                mvno.plan_name,
                mvno.contract_period,
                mvno.contract_period_days,
                mvno.discount_period,
                mvno.price_main,
                mvno.price_after,
                mvno.data_amount,
                mvno.data_amount_value,
                mvno.data_unit,
                mvno.data_additional,
                mvno.data_additional_value,
                mvno.data_exhausted,
                mvno.data_exhausted_value,
                mvno.call_type,
                mvno.call_amount,
                mvno.additional_call_type,
                mvno.additional_call,
                mvno.sms_type,
                mvno.sms_amount,
                mvno.promotion_title,
                mvno.promotions,
                mvno.benefits
            FROM rotation_advertisements ra
            INNER JOIN products p ON ra.product_id = p.id
            INNER JOIN product_mvno_details mvno ON p.id = mvno.product_id
            WHERE ra.product_type = 'mvno'
            AND ra.status = 'active'
            AND p.status = 'active'
            AND ra.end_datetime > NOW()
            ORDER BY ra.display_order ASC, ra.created_at ASC
        ");
        $adStmt->execute();
        $advertisementProductsRaw = $adStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 디버깅: 조회된 광고 상품 개수 확인
        error_log("[MVNO 광고] 조회된 raw 광고 상품 개수: " . count($advertisementProductsRaw));
        if (count($advertisementProductsRaw) > 0) {
            error_log("[MVNO 광고] 첫 번째 광고 상품 product_id: " . ($advertisementProductsRaw[0]['product_id'] ?? '없음'));
        }
        
        // 서버 사이드 로테이션
        if (count($advertisementProductsRaw) > 1 && $rotationDuration !== null && $rotationDuration > 0) {
            date_default_timezone_set('Asia/Seoul');
            $today = date('Y-m-d');
            $baseTimestamp = strtotime($today . ' 00:00:00');
            $currentTimestamp = time();
            $elapsedSeconds = $currentTimestamp - $baseTimestamp;
            $rotationCycles = floor($elapsedSeconds / $rotationDuration);
            $adCount = count($advertisementProductsRaw);
            $rotationOffset = $rotationCycles % $adCount;
            
            if ($rotationOffset > 0) {
                $advertisementProductsRaw = array_merge(
                    array_slice($advertisementProductsRaw, $rotationOffset),
                    array_slice($advertisementProductsRaw, 0, $rotationOffset)
                );
            }
        }
        
        // 판매자 정보 배치 조회
        $sellerIds = [];
        foreach ($advertisementProductsRaw as $adProduct) {
            $sellerId = (string)($adProduct['seller_id'] ?? '');
            if ($sellerId !== '') {
                $sellerIds[$sellerId] = true;
            }
        }
        
        $sellerMap = [];
        if (!empty($sellerIds)) {
            $idList = array_keys($sellerIds);
            $placeholders = implode(',', array_fill(0, count($idList), '?'));
            $sellerStmt = $pdo->prepare("
                SELECT u.user_id, u.seller_name, u.company_name, u.name
                FROM users u
                WHERE u.role = 'seller' AND u.user_id IN ($placeholders)
            ");
            $sellerStmt->execute($idList);
            foreach ($sellerStmt->fetchAll(PDO::FETCH_ASSOC) as $s) {
                $sellerMap[(string)$s['user_id']] = $s;
            }
        }
        
        // 광고 상품 데이터 구성
        foreach ($advertisementProductsRaw as $adProduct) {
            $sellerId = (string)($adProduct['seller_id'] ?? '');
            $sellerName = '';
            if ($sellerId && isset($sellerMap[$sellerId])) {
                $seller = $sellerMap[$sellerId];
                $sellerName = !empty($seller['seller_name']) 
                    ? $seller['seller_name'] 
                    : (!empty($seller['company_name']) 
                        ? $seller['company_name'] 
                        : (!empty($seller['name']) ? $seller['name'] : $sellerId));
            }
            
            // p.id가 이미 조회되므로 product_id 필드 추가 (convertMvnoProductToPlanCard 호환)
            if (!isset($adProduct['product_id']) && isset($adProduct['id'])) {
                $adProduct['product_id'] = $adProduct['id'];
            }
            
            // plan 배열 구성 (convertMvnoProductToPlanCard와 유사)
            $adProduct['seller_name'] = $sellerName;
            $advertisementProducts[] = $adProduct;
            
            // product_id 필드 확인 및 설정
            $productId = $adProduct['id'] ?? $adProduct['product_id'] ?? null;
            if ($productId) {
                $advertisementProductIds[] = (int)$productId;
            } else {
                error_log("[MVNO 광고] 경고: 광고 상품에 product_id 또는 id가 없습니다: " . print_r(array_keys($adProduct), true));
            }
        }
        
        // 디버깅 정보는 필요시에만 기록
    } catch (PDOException $e) {
        error_log("[MVNO 광고] Error fetching MVNO advertisement products: " . $e->getMessage());
        error_log("[MVNO 광고] Error trace: " . $e->getTraceAsString());
    }
    
    error_log("[MVNO 광고] 함수 반환 전 - 광고 상품 개수: " . count($advertisementProducts) . ", ID 목록: " . implode(', ', $advertisementProductIds));
    
    return [$advertisementProducts, $rotationDuration, $advertisementProductIds];
}
