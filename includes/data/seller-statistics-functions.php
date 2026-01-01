<?php
/**
 * 판매자 통계 관련 함수
 */

require_once __DIR__ . '/db-config.php';

/**
 * 판매자 통계 조회 (기간별)
 * @param string $sellerId 판매자 ID
 * @param int $days 기간 (일수)
 * @return array 통계 데이터
 */
function getSellerStatistics($sellerId, $days = 30) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return [];
    }
    
    $startDate = date('Y-m-d', strtotime("-{$days} days"));
    $sellerIdStr = (string)$sellerId;
    
    try {
        // 전체 통계
        $stats = [
            'total_products' => 0,
            'active_products' => 0,
            'total_favorites' => 0,
            'total_applications' => 0,
            'total_shares' => 0,
            'total_views' => 0,
            'total_reviews' => 0,
            'average_rating' => 0,
            'products' => []
        ];
        
        // 전체 상품 수
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM products WHERE seller_id = :seller_id AND status != 'deleted'");
        $stmt->execute([':seller_id' => $sellerIdStr]);
        $stats['total_products'] = (int)($stmt->fetch()['total'] ?? 0);
        
        // 판매 중인 상품 수
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM products WHERE seller_id = :seller_id AND status = 'active'");
        $stmt->execute([':seller_id' => $sellerIdStr]);
        $stats['active_products'] = (int)($stmt->fetch()['total'] ?? 0);
        
        // 전체 통계 합산 (모든 상품의 카운트 합산)
        $stmt = $pdo->prepare("
            SELECT 
                SUM(view_count) as total_views,
                SUM(favorite_count) as total_favorites,
                SUM(share_count) as total_shares,
                SUM(application_count) as total_applications,
                SUM(review_count) as total_reviews
            FROM products 
            WHERE seller_id = :seller_id AND status != 'deleted'
        ");
        $stmt->execute([':seller_id' => $sellerIdStr]);
        $sumResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_views'] = (int)($sumResult['total_views'] ?? 0);
        $stats['total_favorites'] = (int)($sumResult['total_favorites'] ?? 0);
        $stats['total_shares'] = (int)($sumResult['total_shares'] ?? 0);
        $stats['total_applications'] = (int)($sumResult['total_applications'] ?? 0);
        $stats['total_reviews'] = (int)($sumResult['total_reviews'] ?? 0);
        
        // 평균 별점 계산 (MVNO, MNO만)
        $stmt = $pdo->prepare("
            SELECT 
                AVG(r.rating) as avg_rating,
                COUNT(r.id) as review_count
            FROM products p
            INNER JOIN product_reviews r ON p.id = r.product_id
            WHERE p.seller_id = :seller_id 
            AND p.product_type IN ('mvno', 'mno')
            AND p.status != 'deleted'
            AND r.status = 'approved'
        ");
        $stmt->execute([':seller_id' => $sellerIdStr]);
        $ratingResult = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($ratingResult && $ratingResult['review_count'] > 0) {
            $stats['average_rating'] = round((float)$ratingResult['avg_rating'], 1);
        }
        
        // 상품별 상세 통계 (admin 페이지와 동일한 기준: INNER JOIN 사용)
        $stmt = $pdo->prepare("
            SELECT 
                p.id,
                p.product_type,
                p.status,
                p.view_count,
                p.favorite_count,
                p.share_count,
                p.application_count,
                p.review_count,
                p.created_at,
                CASE p.product_type
                    WHEN 'mvno' THEN mvno.plan_name
                    WHEN 'mno' THEN mno.device_name
                    WHEN 'mno-sim' THEN mno_sim.plan_name
                    WHEN 'internet' THEN i.registration_place
                END AS product_name
            FROM products p
            LEFT JOIN product_mvno_details mvno ON p.id = mvno.product_id AND p.product_type = 'mvno'
            LEFT JOIN product_mno_details mno ON p.id = mno.product_id AND p.product_type = 'mno'
            LEFT JOIN product_mno_sim_details mno_sim ON p.id = mno_sim.product_id AND p.product_type = 'mno-sim'
            LEFT JOIN product_internet_details i ON p.id = i.product_id AND p.product_type = 'internet'
            WHERE p.seller_id = :seller_id 
            AND p.status != 'deleted'
            AND (
                (p.product_type = 'mvno' AND mvno.product_id IS NOT NULL) OR
                (p.product_type = 'mno' AND mno.product_id IS NOT NULL) OR
                (p.product_type = 'mno-sim' AND mno_sim.product_id IS NOT NULL) OR
                (p.product_type = 'internet' AND i.product_id IS NOT NULL)
            )
            ORDER BY p.id DESC
        ");
        $stmt->execute([':seller_id' => $sellerIdStr]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($products as $product) {
            // 각 상품의 평균 별점 (MVNO, MNO, MNO-SIM만)
            $avgRating = 0;
            if (in_array($product['product_type'], ['mvno', 'mno', 'mno-sim'])) {
                $ratingStmt = $pdo->prepare("
                    SELECT AVG(rating) as avg_rating
                    FROM product_reviews
                    WHERE product_id = :product_id AND status = 'approved'
                ");
                $ratingStmt->execute([':product_id' => $product['id']]);
                $ratingResult = $ratingStmt->fetch(PDO::FETCH_ASSOC);
                if ($ratingResult && $ratingResult['avg_rating']) {
                    $avgRating = round((float)$ratingResult['avg_rating'], 1);
                }
            }
            
            $stats['products'][] = [
                'id' => $product['id'],
                'type' => $product['product_type'],
                'name' => $product['product_name'] ?? '',
                'status' => $product['status'],
                'views' => (int)$product['view_count'],
                'favorites' => (int)$product['favorite_count'],
                'shares' => (int)$product['share_count'],
                'applications' => (int)$product['application_count'],
                'reviews' => (int)$product['review_count'],
                'average_rating' => $avgRating,
                'created_at' => $product['created_at']
            ];
        }
        
        // 기간별 통계 (최근 N일)
        $periodStats = [
            'favorites' => 0,
            'applications' => 0,
            'shares' => 0,
            'views' => 0,
            'reviews' => 0
        ];
        
        // 기간 내 찜 추가 수
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM product_favorites f
            INNER JOIN products p ON f.product_id = p.id
            WHERE p.seller_id = :seller_id
            AND f.created_at >= :start_date
        ");
        $stmt->execute([
            ':seller_id' => $sellerIdStr,
            ':start_date' => $startDate
        ]);
        $periodStats['favorites'] = (int)($stmt->fetch()['total'] ?? 0);
        
        // 기간 내 신청 수
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM product_applications a
            WHERE a.seller_id = :seller_id
            AND a.created_at >= :start_date
        ");
        $stmt->execute([
            ':seller_id' => $sellerIdStr,
            ':start_date' => $startDate
        ]);
        $periodStats['applications'] = (int)($stmt->fetch()['total'] ?? 0);
        
        // 기간 내 공유 수
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM product_shares s
            INNER JOIN products p ON s.product_id = p.id
            WHERE p.seller_id = :seller_id
            AND s.created_at >= :start_date
        ");
        $stmt->execute([
            ':seller_id' => $sellerIdStr,
            ':start_date' => $startDate
        ]);
        $periodStats['shares'] = (int)($stmt->fetch()['total'] ?? 0);
        
        // 기간 내 리뷰 수
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM product_reviews r
            INNER JOIN products p ON r.product_id = p.id
            WHERE p.seller_id = :seller_id
            AND p.product_type IN ('mvno', 'mno')
            AND r.created_at >= :start_date
            AND r.status = 'approved'
        ");
        $stmt->execute([
            ':seller_id' => $sellerIdStr,
            ':start_date' => $startDate
        ]);
        $periodStats['reviews'] = (int)($stmt->fetch()['total'] ?? 0);
        
        // 조회수는 products 테이블의 view_count를 사용하므로 기간별 추적이 어려움
        // 전체 조회수만 제공
        
        $stats['period'] = $periodStats;
        
        return $stats;
    } catch (PDOException $e) {
        error_log("getSellerStatistics error: " . $e->getMessage());
        return [];
    }
}

/**
 * 판매자 타입별 통계 조회
 * @param string $sellerId 판매자 ID
 * @return array 타입별 통계
 */
function getSellerStatisticsByType($sellerId) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return [];
    }
    
    $sellerIdStr = (string)$sellerId;
    
    try {
        $typeStats = [
            'mvno' => [
                'count' => 0,
                'views' => 0,
                'favorites' => 0,
                'shares' => 0,
                'applications' => 0,
                'reviews' => 0,
                'average_rating' => 0
            ],
            'mno' => [
                'count' => 0,
                'views' => 0,
                'favorites' => 0,
                'shares' => 0,
                'applications' => 0,
                'reviews' => 0,
                'average_rating' => 0
            ],
            'mno-sim' => [
                'count' => 0,
                'views' => 0,
                'favorites' => 0,
                'shares' => 0,
                'applications' => 0,
                'reviews' => 0,
                'average_rating' => 0
            ],
            'internet' => [
                'count' => 0,
                'views' => 0,
                'favorites' => 0,
                'shares' => 0,
                'applications' => 0,
                'reviews' => 0,
                'average_rating' => 0
            ]
        ];
        
        foreach (['mvno', 'mno', 'mno-sim', 'internet'] as $type) {
            // 상품 수
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total
                FROM products
                WHERE seller_id = :seller_id AND product_type = :type AND status != 'deleted'
            ");
            $stmt->execute([
                ':seller_id' => $sellerIdStr,
                ':type' => $type
            ]);
            $typeStats[$type]['count'] = (int)($stmt->fetch()['total'] ?? 0);
            
            // 통계 합산
            $stmt = $pdo->prepare("
                SELECT 
                    SUM(view_count) as total_views,
                    SUM(favorite_count) as total_favorites,
                    SUM(share_count) as total_shares,
                    SUM(application_count) as total_applications,
                    SUM(review_count) as total_reviews
                FROM products
                WHERE seller_id = :seller_id AND product_type = :type AND status != 'deleted'
            ");
            $stmt->execute([
                ':seller_id' => $sellerIdStr,
                ':type' => $type
            ]);
            $sumResult = $stmt->fetch(PDO::FETCH_ASSOC);
            $typeStats[$type]['views'] = (int)($sumResult['total_views'] ?? 0);
            $typeStats[$type]['favorites'] = (int)($sumResult['total_favorites'] ?? 0);
            $typeStats[$type]['shares'] = (int)($sumResult['total_shares'] ?? 0);
            $typeStats[$type]['applications'] = (int)($sumResult['total_applications'] ?? 0);
            $typeStats[$type]['reviews'] = (int)($sumResult['total_reviews'] ?? 0);
            
            // 평균 별점 (MVNO, MNO, MNO-SIM만)
            if (in_array($type, ['mvno', 'mno', 'mno-sim'])) {
                $stmt = $pdo->prepare("
                    SELECT AVG(r.rating) as avg_rating
                    FROM products p
                    INNER JOIN product_reviews r ON p.id = r.product_id
                    WHERE p.seller_id = :seller_id 
                    AND p.product_type = :type
                    AND p.status != 'deleted'
                    AND r.status = 'approved'
                ");
                $stmt->execute([
                    ':seller_id' => $sellerIdStr,
                    ':type' => $type
                ]);
                $ratingResult = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($ratingResult && $ratingResult['avg_rating']) {
                    $typeStats[$type]['average_rating'] = round((float)$ratingResult['avg_rating'], 1);
                }
            }
        }
        
        return $typeStats;
    } catch (PDOException $e) {
        error_log("getSellerStatisticsByType error: " . $e->getMessage());
        return [];
    }
}
