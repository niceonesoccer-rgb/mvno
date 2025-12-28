<?php
/**
 * 상품 ID 24 별점 확인 (간단 버전)
 */

require_once __DIR__ . '/includes/data/db-config.php';
require_once __DIR__ . '/includes/data/plan-data.php';

header('Content-Type: text/html; charset=utf-8');

$pdo = getDBConnection();
if (!$pdo) {
    die('데이터베이스 연결 실패');
}

$productId = 24;
$productType = 'mvno';

echo "<h1>상품 ID $productId 별점 확인</h1>";
echo "<style>
    table { border-collapse: collapse; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    .highlight { background-color: #fff3cd; font-weight: bold; }
</style>";

try {
    // 1. 실제 리뷰 데이터 확인
    echo "<h2>1. 실제 리뷰 데이터 (총 개수 확인)</h2>";
    $reviewsStmt = $pdo->prepare("
        SELECT 
            id,
            user_id,
            rating,
            kindness_rating,
            speed_rating,
            status,
            created_at,
            application_id
        FROM product_reviews
        WHERE product_id = :product_id
        AND product_type = :product_type
        ORDER BY created_at DESC
    ");
    $reviewsStmt->execute([':product_id' => $productId, ':product_type' => $productType]);
    $reviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>총 리뷰 수:</strong> " . count($reviews) . "개</p>";
    
    $approvedReviews = array_filter($reviews, function($r) { return $r['status'] === 'approved'; });
    echo "<p><strong>승인된 리뷰 수:</strong> " . count($approvedReviews) . "개</p>";
    
    echo "<table>";
    echo "<tr><th>리뷰 ID</th><th>사용자</th><th>별점</th><th>친절해요</th><th>개통빨라요</th><th>상태</th><th>작성일</th></tr>";
    
    $totalRating = 0;
    $kindnessTotal = 0;
    $kindnessCount = 0;
    $speedTotal = 0;
    $speedCount = 0;
    
    foreach ($reviews as $review) {
        $statusColor = $review['status'] === 'approved' ? 'green' : ($review['status'] === 'deleted' ? 'red' : 'orange');
        echo "<tr>";
        echo "<td>{$review['id']}</td>";
        echo "<td>{$review['user_id']}</td>";
        echo "<td><strong>{$review['rating']}</strong></td>";
        echo "<td>" . ($review['kindness_rating'] ?? '-') . "</td>";
        echo "<td>" . ($review['speed_rating'] ?? '-') . "</td>";
        echo "<td style='color: $statusColor;'><strong>{$review['status']}</strong></td>";
        echo "<td>{$review['created_at']}</td>";
        echo "</tr>";
        
        if ($review['status'] === 'approved') {
            $totalRating += $review['rating'];
            if ($review['kindness_rating'] !== null) {
                $kindnessTotal += $review['kindness_rating'];
                $kindnessCount++;
            }
            if ($review['speed_rating'] !== null) {
                $speedTotal += $review['speed_rating'];
                $speedCount++;
            }
        }
    }
    echo "</table>";
    
    // 실제 계산된 평균
    $approvedCount = count($approvedReviews);
    $actualAverage = $approvedCount > 0 ? $totalRating / $approvedCount : 0;
    $actualKindness = $kindnessCount > 0 ? $kindnessTotal / $kindnessCount : 0;
    $actualSpeed = $speedCount > 0 ? $speedTotal / $speedCount : 0;
    
    echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
    echo "<h3>실제 계산된 평균 (approved 리뷰만):</h3>";
    echo "<ul>";
    echo "<li><strong>총 평균:</strong> $totalRating / $approvedCount = <strong style='color: blue; font-size: 18px;'>" . number_format($actualAverage, 10) . "</strong></li>";
    if ($kindnessCount > 0) {
        echo "<li><strong>친절해요 평균:</strong> $kindnessTotal / $kindnessCount = <strong style='color: blue;'>" . number_format($actualKindness, 10) . "</strong></li>";
    }
    if ($speedCount > 0) {
        echo "<li><strong>개통빨라요 평균:</strong> $speedTotal / $speedCount = <strong style='color: blue;'>" . number_format($actualSpeed, 10) . "</strong></li>";
    }
    echo "</ul>";
    echo "</div>";
    
    // 2. 통계 테이블 확인
    echo "<h2>2. 통계 테이블 (product_review_statistics)</h2>";
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
    
    if ($stats) {
        $realtimeAvg = $stats['total_review_count'] > 0 ? $stats['total_rating_sum'] / $stats['total_review_count'] : 0;
        
        echo "<table>";
        echo "<tr><th>항목</th><th>합계</th><th>개수</th><th>평균</th></tr>";
        echo "<tr class='highlight'>";
        echo "<td><strong>총 평균</strong></td>";
        echo "<td>{$stats['total_rating_sum']}</td>";
        echo "<td>{$stats['total_review_count']}</td>";
        echo "<td><strong style='color: green; font-size: 18px;'>" . number_format($realtimeAvg, 10) . "</strong></td>";
        echo "</tr>";
        
        if ($stats['kindness_review_count'] > 0) {
            $realtimeKindness = $stats['kindness_rating_sum'] / $stats['kindness_review_count'];
            echo "<tr>";
            echo "<td>친절해요</td>";
            echo "<td>{$stats['kindness_rating_sum']}</td>";
            echo "<td>{$stats['kindness_review_count']}</td>";
            echo "<td>" . number_format($realtimeKindness, 10) . "</td>";
            echo "</tr>";
        }
        
        if ($stats['speed_review_count'] > 0) {
            $realtimeSpeed = $stats['speed_rating_sum'] / $stats['speed_review_count'];
            echo "<tr>";
            echo "<td>개통빨라요</td>";
            echo "<td>{$stats['speed_rating_sum']}</td>";
            echo "<td>{$stats['speed_review_count']}</td>";
            echo "<td>" . number_format($realtimeSpeed, 10) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>통계 테이블에 데이터가 없습니다.</p>";
    }
    
    // 3. 함수로 가져온 평균 확인
    echo "<h2>3. getProductAverageRating() 함수 결과</h2>";
    $functionAverage = getProductAverageRating($productId, $productType);
    
    // 표시 방식 확인
    $displayedWithCeil = ceil($actualAverage * 10) / 10;
    $displayedWithRound = round($actualAverage * 10) / 10;
    
    echo "<div style='background: #fff7ed; padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #f59e0b;'>";
    echo "<h3>표시 방식 비교:</h3>";
    echo "<ul>";
    echo "<li><strong>실제 평균:</strong> " . number_format($actualAverage, 10) . "</li>";
    echo "<li><strong>올림 (ceil):</strong> " . number_format($displayedWithCeil, 1) . "</li>";
    echo "<li><strong>반올림 (round):</strong> " . number_format($displayedWithRound, 1) . "</li>";
    echo "<li><strong>함수 결과:</strong> <strong style='color: red; font-size: 20px;'>$functionAverage</strong></li>";
    echo "<li><strong>화면 표시 (number_format):</strong> <strong style='color: blue; font-size: 20px;'>" . number_format($functionAverage, 1) . "</strong></li>";
    echo "</ul>";
    echo "</div>";
    
    // 4. 분석
    echo "<h2>4. 분석</h2>";
    echo "<div style='background: #f0fdf4; padding: 20px; border-radius: 8px; border-left: 4px solid #16a34a;'>";
    
    if ($functionAverage == $displayedWithCeil) {
        echo "<p>✅ 함수가 올림(ceil) 방식을 사용하고 있습니다.</p>";
    } else {
        echo "<p>⚠️ 함수 결과와 예상 결과가 다릅니다.</p>";
    }
    
    if (abs($functionAverage - $actualAverage) < 0.1) {
        echo "<p>✅ 함수 결과가 실제 평균과 거의 일치합니다.</p>";
    } else {
        echo "<p>⚠️ 함수 결과와 실제 평균에 차이가 있습니다.</p>";
        echo "<p>차이: " . number_format(abs($functionAverage - $actualAverage), 10) . "</p>";
    }
    
    if ($stats && $stats['total_review_count'] != $approvedCount) {
        echo "<p style='color: red;'>⚠️ 통계 테이블의 리뷰 개수({$stats['total_review_count']})와 실제 승인된 리뷰 개수($approvedCount)가 다릅니다!</p>";
    }
    
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>오류 발생: " . htmlspecialchars($e->getMessage()) . "</p>";
}







