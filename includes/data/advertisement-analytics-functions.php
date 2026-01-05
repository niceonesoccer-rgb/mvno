<?php
/**
 * 광고 분석 추적 함수
 * 
 * 사용 예시:
 * - 광고 노출: trackAdvertisementImpression($advertisementId, $productId, $sellerId, $productType)
 * - 광고 클릭: trackAdvertisementClick($advertisementId, $productId, $sellerId, $productType, $clickType)
 */

require_once __DIR__ . '/db-config.php';

// 한국 시간대 설정
date_default_timezone_set('Asia/Seoul');

/**
 * 기기 타입 감지
 */
function detectDeviceType() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    if (preg_match('/mobile|android|iphone|ipod|blackberry|iemobile|opera mini/i', $userAgent)) {
        return 'mobile';
    } elseif (preg_match('/tablet|ipad|playbook|silk/i', $userAgent)) {
        return 'tablet';
    } elseif (preg_match('/windows|macintosh|linux/i', $userAgent)) {
        return 'desktop';
    }
    
    return 'unknown';
}

/**
 * 브라우저 감지
 */
function detectBrowser($userAgent) {
    if (preg_match('/MSIE|Trident/i', $userAgent)) {
        return 'Internet Explorer';
    } elseif (preg_match('/Edge/i', $userAgent)) {
        return 'Edge';
    } elseif (preg_match('/Chrome/i', $userAgent)) {
        return 'Chrome';
    } elseif (preg_match('/Safari/i', $userAgent)) {
        return 'Safari';
    } elseif (preg_match('/Firefox/i', $userAgent)) {
        return 'Firefox';
    } elseif (preg_match('/Opera|OPR/i', $userAgent)) {
        return 'Opera';
    }
    
    return 'Unknown';
}

/**
 * 운영체제 감지
 */
function detectOS($userAgent) {
    if (preg_match('/windows|win32|win64/i', $userAgent)) {
        return 'Windows';
    } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
        return 'macOS';
    } elseif (preg_match('/linux/i', $userAgent)) {
        return 'Linux';
    } elseif (preg_match('/android/i', $userAgent)) {
        return 'Android';
    } elseif (preg_match('/iphone|ipad|ipod/i', $userAgent)) {
        return 'iOS';
    }
    
    return 'Unknown';
}

/**
 * 광고 노출 추적
 * 
 * @param int $advertisementId 광고 ID (rotation_advertisements.id)
 * @param int $productId 상품 ID
 * @param string $sellerId 판매자 ID
 * @param string $productType 상품 타입 ('mvno', 'mno', 'internet', 'mno_sim')
 * @return bool 성공 여부
 */
function trackAdvertisementImpression($advertisementId, $productId, $sellerId, $productType) {
    try {
        $pdo = getDBConnection();
        if (!$pdo) {
            error_log("광고 노출 추적 실패: DB 연결 실패 - Advertisement ID: {$advertisementId}");
            return false;
        }
        
        // 세션에서 사용자 ID 가져오기
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $userId = $_SESSION['user_id'] ?? null;
        
        // 세션 ID 가져오기
        $sessionId = session_id();
        
        // 기기 정보 수집
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $referrer = $_SERVER['HTTP_REFERER'] ?? '';
        $pageUrl = $_SERVER['REQUEST_URI'] ?? '';
        
        $deviceType = detectDeviceType();
        $browser = detectBrowser($userAgent);
        $os = detectOS($userAgent);
        
        $stmt = $pdo->prepare("
            INSERT INTO advertisement_impressions 
            (advertisement_id, product_id, seller_id, product_type, user_id, ip_address, user_agent, referrer, page_url, device_type, browser, os, session_id, created_at)
            VALUES (:advertisement_id, :product_id, :seller_id, :product_type, :user_id, :ip_address, :user_agent, :referrer, :page_url, :device_type, :browser, :os, :session_id, NOW())
        ");
        
        $result = $stmt->execute([
            ':advertisement_id' => (int)$advertisementId,
            ':product_id' => (int)$productId,
            ':seller_id' => (string)$sellerId,
            ':product_type' => (string)$productType,
            ':user_id' => $userId,
            ':ip_address' => $ipAddress,
            ':user_agent' => substr($userAgent, 0, 500),
            ':referrer' => substr($referrer, 0, 500),
            ':page_url' => substr($pageUrl, 0, 500),
            ':device_type' => $deviceType,
            ':browser' => substr($browser, 0, 100),
            ':os' => substr($os, 0, 100),
            ':session_id' => $sessionId
        ]);
        
        if ($result) {
            return true;
        } else {
            error_log("광고 노출 추적 실패: INSERT 실패 - Advertisement ID: {$advertisementId}");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("광고 노출 추적 실패: " . $e->getMessage() . " - Advertisement ID: {$advertisementId}");
        return false;
    }
}

/**
 * 광고 클릭 추적
 * 
 * @param int $advertisementId 광고 ID (rotation_advertisements.id)
 * @param int $productId 상품 ID
 * @param string $sellerId 판매자 ID
 * @param string $productType 상품 타입 ('mvno', 'mno', 'internet', 'mno_sim')
 * @param string $clickType 클릭 유형 ('direct', 'detail', 'apply', 'other')
 * @param string|null $targetUrl 클릭한 목적지 URL (선택사항)
 * @return bool 성공 여부
 */
function trackAdvertisementClick($advertisementId, $productId, $sellerId, $productType, $clickType = 'direct', $targetUrl = null) {
    try {
        $pdo = getDBConnection();
        if (!$pdo) {
            error_log("광고 클릭 추적 실패: DB 연결 실패 - Advertisement ID: {$advertisementId}");
            return false;
        }
        
        // 세션에서 사용자 ID 가져오기
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $userId = $_SESSION['user_id'] ?? null;
        
        // 세션 ID 가져오기
        $sessionId = session_id();
        
        // 기기 정보 수집
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $referrer = $_SERVER['HTTP_REFERER'] ?? '';
        $pageUrl = $_SERVER['REQUEST_URI'] ?? '';
        
        $deviceType = detectDeviceType();
        $browser = detectBrowser($userAgent);
        $os = detectOS($userAgent);
        
        $stmt = $pdo->prepare("
            INSERT INTO advertisement_clicks 
            (advertisement_id, product_id, seller_id, product_type, user_id, ip_address, user_agent, referrer, page_url, target_url, device_type, browser, os, session_id, click_type, created_at)
            VALUES (:advertisement_id, :product_id, :seller_id, :product_type, :user_id, :ip_address, :user_agent, :referrer, :page_url, :target_url, :device_type, :browser, :os, :session_id, :click_type, NOW())
        ");
        
        $result = $stmt->execute([
            ':advertisement_id' => (int)$advertisementId,
            ':product_id' => (int)$productId,
            ':seller_id' => (string)$sellerId,
            ':product_type' => (string)$productType,
            ':user_id' => $userId,
            ':ip_address' => $ipAddress,
            ':user_agent' => substr($userAgent, 0, 500),
            ':referrer' => substr($referrer, 0, 500),
            ':page_url' => substr($pageUrl, 0, 500),
            ':target_url' => $targetUrl ? substr($targetUrl, 0, 500) : null,
            ':device_type' => $deviceType,
            ':browser' => substr($browser, 0, 100),
            ':os' => substr($os, 0, 100),
            ':session_id' => $sessionId,
            ':click_type' => $clickType
        ]);
        
        if ($result) {
            return true;
        } else {
            error_log("광고 클릭 추적 실패: INSERT 실패 - Advertisement ID: {$advertisementId}");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("광고 클릭 추적 실패: " . $e->getMessage() . " - Advertisement ID: {$advertisementId}");
        return false;
    }
}

/**
 * 광고 통계 집계 (일별)
 * 
 * @param int $advertisementId 광고 ID
 * @param string $statDate 통계 날짜 (Y-m-d 형식, null이면 오늘)
 * @return bool 성공 여부
 */
function aggregateAdvertisementAnalytics($advertisementId, $statDate = null) {
    try {
        $pdo = getDBConnection();
        if (!$pdo) {
            error_log("광고 통계 집계 실패: DB 연결 실패 - Advertisement ID: {$advertisementId}");
            return false;
        }
        
        if ($statDate === null) {
            $statDate = date('Y-m-d');
        }
        
        // 광고 정보 가져오기
        $stmt = $pdo->prepare("
            SELECT product_id, seller_id, product_type 
            FROM rotation_advertisements 
            WHERE id = :advertisement_id
        ");
        $stmt->execute([':advertisement_id' => (int)$advertisementId]);
        $ad = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$ad) {
            error_log("광고 통계 집계 실패: 광고를 찾을 수 없음 - Advertisement ID: {$advertisementId}");
            return false;
        }
        
        // 노출 통계 집계
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_impressions,
                COUNT(DISTINCT ip_address) as unique_impressions,
                SUM(CASE WHEN device_type = 'desktop' THEN 1 ELSE 0 END) as desktop_impressions,
                SUM(CASE WHEN device_type = 'mobile' THEN 1 ELSE 0 END) as mobile_impressions,
                SUM(CASE WHEN device_type = 'tablet' THEN 1 ELSE 0 END) as tablet_impressions
            FROM advertisement_impressions
            WHERE advertisement_id = :advertisement_id
            AND DATE(created_at) = :stat_date
        ");
        $stmt->execute([
            ':advertisement_id' => (int)$advertisementId,
            ':stat_date' => $statDate
        ]);
        $impressionStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 클릭 통계 집계
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_clicks,
                COUNT(DISTINCT ip_address) as unique_clicks,
                SUM(CASE WHEN device_type = 'desktop' THEN 1 ELSE 0 END) as desktop_clicks,
                SUM(CASE WHEN device_type = 'mobile' THEN 1 ELSE 0 END) as mobile_clicks,
                SUM(CASE WHEN device_type = 'tablet' THEN 1 ELSE 0 END) as tablet_clicks
            FROM advertisement_clicks
            WHERE advertisement_id = :advertisement_id
            AND DATE(created_at) = :stat_date
        ");
        $stmt->execute([
            ':advertisement_id' => (int)$advertisementId,
            ':stat_date' => $statDate
        ]);
        $clickStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // CTR 계산
        $impressionCount = (int)($impressionStats['total_impressions'] ?? 0);
        $clickCount = (int)($clickStats['total_clicks'] ?? 0);
        $ctr = $impressionCount > 0 ? ($clickCount / $impressionCount) : 0.0;
        
        // 통계 데이터 저장 또는 업데이트
        $stmt = $pdo->prepare("
            INSERT INTO advertisement_analytics 
            (advertisement_id, product_id, seller_id, product_type, stat_date, stat_hour, impression_count, click_count, unique_impressions, unique_clicks, ctr, desktop_impressions, mobile_impressions, tablet_impressions, desktop_clicks, mobile_clicks, tablet_clicks, created_at, updated_at)
            VALUES (:advertisement_id, :product_id, :seller_id, :product_type, :stat_date, NULL, :impression_count, :click_count, :unique_impressions, :unique_clicks, :ctr, :desktop_impressions, :mobile_impressions, :tablet_impressions, :desktop_clicks, :mobile_clicks, :tablet_clicks, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                impression_count = VALUES(impression_count),
                click_count = VALUES(click_count),
                unique_impressions = VALUES(unique_impressions),
                unique_clicks = VALUES(unique_clicks),
                ctr = VALUES(ctr),
                desktop_impressions = VALUES(desktop_impressions),
                mobile_impressions = VALUES(mobile_impressions),
                tablet_impressions = VALUES(tablet_impressions),
                desktop_clicks = VALUES(desktop_clicks),
                mobile_clicks = VALUES(mobile_clicks),
                tablet_clicks = VALUES(tablet_clicks),
                updated_at = NOW()
        ");
        
        $result = $stmt->execute([
            ':advertisement_id' => (int)$advertisementId,
            ':product_id' => (int)$ad['product_id'],
            ':seller_id' => $ad['seller_id'],
            ':product_type' => $ad['product_type'],
            ':stat_date' => $statDate,
            ':impression_count' => $impressionCount,
            ':click_count' => $clickCount,
            ':unique_impressions' => (int)($impressionStats['unique_impressions'] ?? 0),
            ':unique_clicks' => (int)($clickStats['unique_clicks'] ?? 0),
            ':ctr' => $ctr,
            ':desktop_impressions' => (int)($impressionStats['desktop_impressions'] ?? 0),
            ':mobile_impressions' => (int)($impressionStats['mobile_impressions'] ?? 0),
            ':tablet_impressions' => (int)($impressionStats['tablet_impressions'] ?? 0),
            ':desktop_clicks' => (int)($clickStats['desktop_clicks'] ?? 0),
            ':mobile_clicks' => (int)($clickStats['mobile_clicks'] ?? 0),
            ':tablet_clicks' => (int)($clickStats['tablet_clicks'] ?? 0)
        ]);
        
        if ($result) {
            return true;
        } else {
            error_log("광고 통계 집계 실패: INSERT/UPDATE 실패 - Advertisement ID: {$advertisementId}");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("광고 통계 집계 실패: " . $e->getMessage() . " - Advertisement ID: {$advertisementId}");
        return false;
    }
}
