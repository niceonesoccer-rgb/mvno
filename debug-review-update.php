<?php
/**
 * 리뷰 수정 시 통계 변경 실시간 확인 스크립트
 * 리뷰를 수정하기 전후의 통계 값을 비교
 */

require_once __DIR__ . '/includes/data/db-config.php';
require_once __DIR__ . '/includes/data/product-functions.php';

header('Content-Type: text/html; charset=utf-8');

$pdo = getDBConnection();
if (!$pdo) {
    die('데이터베이스 연결 실패');
}

// 최근 수정된 리뷰와 해당 상품의 통계 확인
echo "<h1>리뷰 수정 시 통계 변경 확인</h1>";

try {
    // 1. 최근 수정된 리뷰 목록 (수정일이 작성일과 다른 리뷰)
    echo "<h2>1. 최근 수정된 리뷰 (최근 10개)</h2>";
    $stmt = $pdo->query("
        SELECT 
            r.id,
            r.product_id,
            r.user_id,
            r.product_type,
            r.rating,
            r.kindness_rating,
            r.speed_rating,
            r.status,
            r.created_at,
            r.updated_at,
            p.product_type as product_type_from_products,
            (SELECT COUNT(*) FROM product_reviews WHERE product_id = r.product_id AND status = 'approved') as approved_review_count
        FROM product_reviews r
        LEFT JOIN products p ON r.product_id = p.id
        WHERE r.updated_at != r.created_at
        AND r.status != 'deleted'
        ORDER BY r.updated_at DESC
        LIMIT 10
    ");
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($reviews)) {
        echo "<p>수정된 리뷰가 없습니다.</p>";
    } else {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr>";
        echo "<th>리뷰 ID</th>";
        echo "<th>상품 ID</th>";
        echo "<th>사용자</th>";
        echo "<th>타입</th>";
        echo "<th>별점</th>";
        echo "<th>친절해요</th>";
        echo "<th>개통빨라요</th>";
        echo "<th>상태</th>";
        echo "<th>작성일</th>";
        echo "<th>수정일</th>";
        echo "<th>승인 리뷰 수</th>";
        echo "</tr>";
        
        foreach ($reviews as $review) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($review['id']) . "</td>";
            echo "<td>" . htmlspecialchars($review['product_id']) . "</td>";
            echo "<td>" . htmlspecialchars($review['user_id']) . "</td>";
            echo "<td>" . htmlspecialchars($review['product_type']) . "</td>";
            echo "<td>" . htmlspecialchars($review['rating']) . "</td>";
            echo "<td>" . htmlspecialchars($review['kindness_rating'] ?? '-') . "</td>";
            echo "<td>" . htmlspecialchars($review['speed_rating'] ?? '-') . "</td>";
            echo "<td>" . htmlspecialchars($review['status']) . "</td>";
            echo "<td>" . htmlspecialchars($review['created_at']) . "</td>";
            echo "<td>" . htmlspecialchars($review['updated_at']) . "</td>";
            echo "<td>" . htmlspecialchars($review['approved_review_count']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 2. 각 상품의 통계와 실제 리뷰 계산값 비교
    echo "<h2>2. 상품별 통계 비교 (최근 수정된 리뷰가 있는 상품)</h2>";
    
    if (!empty($reviews)) {
        $productIds = array_unique(array_column($reviews, 'product_id'));
        
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr>";
        echo "<th>상품 ID</th>";
        echo "<th>통계 테이블</th>";
        echo "<th>실제 계산값</th>";
        echo "<th>차이</th>";
        echo "<th>항목별 통계</th>";
        echo "<th>항목별 계산값</th>";
        echo "<th>차이</th>";
        echo "</tr>";
        
        foreach ($productIds as $productId) {
            // 통계 테이블 값
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
            $statsStmt->execute([':product_id' => $productId]);
            $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
            
            // 실제 리뷰에서 계산한 값
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
            $calcStmt->execute([':product_id' => $productId]);
            $calculated = $calcStmt->fetch(PDO::FETCH_ASSOC);
            
            // 평균 계산
            $statsAvg = $stats && $stats['total_review_count'] > 0 
                ? round($stats['total_rating_sum'] / $stats['total_review_count'], 1) 
                : 0;
            $calcAvg = $calculated && $calculated['total_review_count'] > 0 
                ? round($calculated['total_rating_sum'] / $calculated['total_review_count'], 1) 
                : 0;
            
            $kindnessStatsAvg = $stats && $stats['kindness_review_count'] > 0 
                ? round($stats['kindness_rating_sum'] / $stats['kindness_review_count'], 1) 
                : 0;
            $kindnessCalcAvg = $calculated && $calculated['kindness_review_count'] > 0 
                ? round($calculated['kindness_rating_sum'] / $calculated['kindness_review_count'], 1) 
                : 0;
            
            $speedStatsAvg = $stats && $stats['speed_review_count'] > 0 
                ? round($stats['speed_rating_sum'] / $stats['speed_review_count'], 1) 
                : 0;
            $speedCalcAvg = $calculated && $calculated['speed_review_count'] > 0 
                ? round($calculated['speed_rating_sum'] / $calculated['speed_review_count'], 1) 
                : 0;
            
            $totalDiff = abs($statsAvg - $calcAvg);
            $kindnessDiff = abs($kindnessStatsAvg - $kindnessCalcAvg);
            $speedDiff = abs($speedStatsAvg - $speedCalcAvg);
            
            $totalColor = $totalDiff > 0.1 ? 'red' : 'green';
            $kindnessColor = $kindnessDiff > 0.1 ? 'red' : 'green';
            $speedColor = $speedDiff > 0.1 ? 'red' : 'green';
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($productId) . "</td>";
            echo "<td>합계: " . ($stats['total_rating_sum'] ?? 0) . " / 개수: " . ($stats['total_review_count'] ?? 0) . " / 평균: " . $statsAvg . "</td>";
            echo "<td>합계: " . ($calculated['total_rating_sum'] ?? 0) . " / 개수: " . ($calculated['total_review_count'] ?? 0) . " / 평균: " . $calcAvg . "</td>";
            echo "<td style='color: $totalColor; font-weight: bold;'>차이: " . $totalDiff . "</td>";
            echo "<td>친절: " . ($stats['kindness_rating_sum'] ?? 0) . "/" . ($stats['kindness_review_count'] ?? 0) . "=" . $kindnessStatsAvg . "<br>";
            echo "개통: " . ($stats['speed_rating_sum'] ?? 0) . "/" . ($stats['speed_review_count'] ?? 0) . "=" . $speedStatsAvg . "</td>";
            echo "<td>친절: " . ($calculated['kindness_rating_sum'] ?? 0) . "/" . ($calculated['kindness_review_count'] ?? 0) . "=" . $kindnessCalcAvg . "<br>";
            echo "개통: " . ($calculated['speed_rating_sum'] ?? 0) . "/" . ($calculated['speed_review_count'] ?? 0) . "=" . $speedCalcAvg . "</td>";
            echo "<td style='color: " . ($kindnessDiff > 0.1 || $speedDiff > 0.1 ? 'red' : 'green') . "; font-weight: bold;'>";
            echo "친절 차이: " . $kindnessDiff . "<br>";
            echo "개통 차이: " . $speedDiff;
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 3. 서버 로그 확인 안내
    echo "<h2>3. 서버 로그 확인</h2>";
    echo "<p>리뷰를 수정한 후 서버 로그 파일(error_log)에서 다음 로그를 확인하세요:</p>";
    echo "<ul>";
    echo "<li><strong>DEBUG updateProductReview:</strong> 리뷰 수정 전후 통계 값</li>";
    echo "<li><strong>ERROR updateProductReview:</strong> 통계가 변경된 경우 (빨간색으로 표시)</li>";
    echo "<li><strong>DEBUG updateReviewStatistics:</strong> 통계 업데이트 함수가 호출된 경우 (이것이 문제일 수 있음)</li>";
    echo "</ul>";
    
    // 4. 테스트 방법
    echo "<h2>4. 테스트 방법</h2>";
    echo "<ol>";
    echo "<li>이 페이지를 열어두세요.</li>";
    echo "<li>다른 탭에서 리뷰를 수정하세요.</li>";
    echo "<li>이 페이지를 새로고침하여 통계가 변경되었는지 확인하세요.</li>";
    echo "<li>서버 로그를 확인하여 원인을 파악하세요.</li>";
    echo "</ol>";
    
    // 5. 자동 새로고침 (30초마다)
    echo "<script>";
    echo "setTimeout(function() { location.reload(); }, 30000);";
    echo "document.write('<p style=\"color: blue;\">30초 후 자동 새로고침됩니다...</p>');";
    echo "</script>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>오류 발생: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}




