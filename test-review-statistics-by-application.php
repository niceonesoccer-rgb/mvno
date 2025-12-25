<?php
/**
 * 주문건별 리뷰 통계 확인 스크립트
 * 같은 사용자가 같은 상품에 대해 다른 주문건으로 리뷰를 작성했을 때 통계 반영 확인
 */

require_once __DIR__ . '/includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

$pdo = getDBConnection();
if (!$pdo) {
    die('데이터베이스 연결 실패');
}

echo "<h1>주문건별 리뷰 통계 확인</h1>";

try {
    // 1. 같은 사용자가 같은 상품에 대해 여러 주문건으로 리뷰를 작성한 경우 확인
    echo "<h2>1. 같은 사용자 + 같은 상품 + 다른 주문건 리뷰</h2>";
    
    $stmt = $pdo->query("
        SELECT 
            r.product_id,
            r.user_id,
            r.application_id,
            r.id as review_id,
            r.rating,
            r.kindness_rating,
            r.speed_rating,
            r.status,
            r.created_at,
            COUNT(DISTINCT r.application_id) as application_count,
            COUNT(*) as review_count
        FROM product_reviews r
        WHERE r.application_id IS NOT NULL
        AND r.status = 'approved'
        GROUP BY r.product_id, r.user_id
        HAVING application_count > 1
        ORDER BY r.product_id, r.user_id, r.created_at DESC
    ");
    $multiApplicationReviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($multiApplicationReviews)) {
        echo "<p>같은 사용자가 같은 상품에 대해 여러 주문건으로 리뷰를 작성한 경우가 없습니다.</p>";
    } else {
        // 그룹화
        $grouped = [];
        foreach ($multiApplicationReviews as $review) {
            $key = $review['product_id'] . '_' . $review['user_id'];
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'product_id' => $review['product_id'],
                    'user_id' => $review['user_id'],
                    'application_count' => $review['application_count'],
                    'review_count' => $review['review_count'],
                    'reviews' => []
                ];
            }
            $grouped[$key]['reviews'][] = $review;
        }
        
        echo "<p>" . count($grouped) . "개의 상품에서 같은 사용자가 여러 주문건으로 리뷰를 작성했습니다.</p>";
        
        foreach ($grouped as $key => $group) {
            echo "<h3>상품 ID: {$group['product_id']}, 사용자: {$group['user_id']}</h3>";
            echo "<p>주문건 수: {$group['application_count']}, 리뷰 수: {$group['review_count']}</p>";
            
            // 통계 테이블 확인
            $statsStmt = $pdo->prepare("
                SELECT 
                    total_rating_sum,
                    total_review_count,
                    kindness_rating_sum,
                    kindness_review_count,
                    speed_rating_sum,
                    speed_review_count
                FROM product_review_statistics
                WHERE product_id = :product_id
            ");
            $statsStmt->execute([':product_id' => $group['product_id']]);
            $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
            
            // 실제 리뷰 합계 계산
            $calcStmt = $pdo->prepare("
                SELECT 
                    SUM(rating) as total_rating_sum,
                    COUNT(*) as total_review_count,
                    SUM(kindness_rating) as kindness_rating_sum,
                    COUNT(kindness_rating) as kindness_review_count,
                    SUM(speed_rating) as speed_rating_sum,
                    COUNT(speed_rating) as speed_review_count
                FROM product_reviews
                WHERE product_id = :product_id
                AND status = 'approved'
            ");
            $calcStmt->execute([':product_id' => $group['product_id']]);
            $calculated = $calcStmt->fetch(PDO::FETCH_ASSOC);
            
            echo "<table border='1' cellpadding='10' style='border-collapse: collapse; margin-bottom: 20px;'>";
            echo "<tr><th>주문건 ID</th><th>리뷰 ID</th><th>별점</th><th>친절해요</th><th>개통빨라요</th><th>상태</th><th>작성일</th></tr>";
            foreach ($group['reviews'] as $review) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($review['application_id']) . "</td>";
                echo "<td>" . htmlspecialchars($review['review_id']) . "</td>";
                echo "<td>" . htmlspecialchars($review['rating']) . "</td>";
                echo "<td>" . htmlspecialchars($review['kindness_rating'] ?? '-') . "</td>";
                echo "<td>" . htmlspecialchars($review['speed_rating'] ?? '-') . "</td>";
                echo "<td>" . htmlspecialchars($review['status']) . "</td>";
                echo "<td>" . htmlspecialchars($review['created_at']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // 통계 비교
            echo "<h4>통계 비교</h4>";
            echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
            echo "<tr><th>항목</th><th>통계 테이블</th><th>실제 계산값</th><th>차이</th></tr>";
            
            $totalStats = $stats ? ($stats['total_rating_sum'] / $stats['total_review_count']) : 0;
            $totalCalc = $calculated ? ($calculated['total_rating_sum'] / $calculated['total_review_count']) : 0;
            $totalDiff = abs($totalStats - $totalCalc);
            $totalColor = $totalDiff > 0.1 ? 'red' : 'green';
            
            echo "<tr>";
            echo "<td>총 평균 별점</td>";
            echo "<td>" . ($stats ? round($totalStats, 1) : 'NULL') . " (합계: " . ($stats['total_rating_sum'] ?? 0) . " / 개수: " . ($stats['total_review_count'] ?? 0) . ")</td>";
            echo "<td>" . ($calculated ? round($totalCalc, 1) : 'NULL') . " (합계: " . ($calculated['total_rating_sum'] ?? 0) . " / 개수: " . ($calculated['total_review_count'] ?? 0) . ")</td>";
            echo "<td style='color: $totalColor; font-weight: bold;'>" . round($totalDiff, 1) . "</td>";
            echo "</tr>";
            echo "</table>";
            
            // 문제 확인
            if ($stats && $calculated) {
                if ($stats['total_review_count'] != $calculated['total_review_count']) {
                    echo "<p style='color: red;'><strong>문제 발견:</strong> 통계 테이블의 리뷰 개수(" . $stats['total_review_count'] . ")와 실제 리뷰 개수(" . $calculated['total_review_count'] . ")가 다릅니다!</p>";
                    echo "<p style='color: red;'>다른 주문건의 리뷰가 통계에 반영되지 않았을 수 있습니다.</p>";
                } else {
                    echo "<p style='color: green;'>✓ 통계가 정상적으로 반영되었습니다.</p>";
                }
            }
        }
    }
    
    // 2. 삭제된 리뷰가 있는 경우 확인
    echo "<h2>2. 삭제된 리뷰 확인</h2>";
    $deletedStmt = $pdo->query("
        SELECT 
            product_id,
            user_id,
            application_id,
            COUNT(*) as deleted_count
        FROM product_reviews
        WHERE status = 'deleted'
        AND application_id IS NOT NULL
        GROUP BY product_id, user_id, application_id
        ORDER BY product_id, user_id
    ");
    $deletedReviews = $deletedStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($deletedReviews)) {
        echo "<p>삭제된 리뷰가 없습니다.</p>";
    } else {
        echo "<p>" . count($deletedReviews) . "개의 주문건에서 리뷰가 삭제되었습니다.</p>";
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr><th>상품 ID</th><th>사용자</th><th>주문건 ID</th><th>삭제된 리뷰 수</th></tr>";
        foreach ($deletedReviews as $deleted) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($deleted['product_id']) . "</td>";
            echo "<td>" . htmlspecialchars($deleted['user_id']) . "</td>";
            echo "<td>" . htmlspecialchars($deleted['application_id']) . "</td>";
            echo "<td>" . htmlspecialchars($deleted['deleted_count']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p style='color: orange;'><strong>주의:</strong> 같은 주문건에서 삭제 후 다시 작성한 리뷰는 통계에 반영되지 않아야 합니다.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>오류 발생: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}





