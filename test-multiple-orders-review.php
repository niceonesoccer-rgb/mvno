<?php
/**
 * 같은 상품에 여러 주문건 리뷰 테스트
 */

require_once __DIR__ . '/includes/data/db-config.php';
require_once __DIR__ . '/includes/data/product-functions.php';
require_once __DIR__ . '/includes/data/plan-data.php';

header('Content-Type: text/html; charset=utf-8');

$pdo = getDBConnection();
if (!$pdo) {
    die('데이터베이스 연결 실패');
}

$productId = 24;
$userId = 'q2222222';
$productType = 'mvno';

echo "<h1>같은 상품에 여러 주문건 리뷰 테스트</h1>";
echo "<style>
    table { border-collapse: collapse; margin: 10px 0; width: 100%; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    .success { color: green; }
    .error { color: red; }
</style>";

try {
    // 1. 현재 리뷰 상태 확인
    echo "<h2>1. 현재 리뷰 상태</h2>";
    $reviewsStmt = $pdo->prepare("
        SELECT 
            id,
            application_id,
            rating,
            kindness_rating,
            speed_rating,
            status,
            created_at
        FROM product_reviews
        WHERE product_id = :product_id
        AND product_type = :product_type
        AND user_id = :user_id
        ORDER BY created_at DESC
    ");
    $reviewsStmt->execute([
        ':product_id' => $productId,
        ':product_type' => $productType,
        ':user_id' => $userId
    ]);
    $reviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>총 리뷰 수:</strong> " . count($reviews) . "개</p>";
    
    if (count($reviews) > 0) {
        echo "<table>";
        echo "<tr><th>리뷰 ID</th><th>주문건 ID</th><th>별점</th><th>친절해요</th><th>개통빨라요</th><th>상태</th><th>작성일</th></tr>";
        foreach ($reviews as $review) {
            $statusColor = $review['status'] === 'approved' ? 'green' : ($review['status'] === 'deleted' ? 'red' : 'orange');
            echo "<tr>";
            echo "<td>{$review['id']}</td>";
            echo "<td>" . ($review['application_id'] ?? '-') . "</td>";
            echo "<td><strong>{$review['rating']}</strong></td>";
            echo "<td>" . ($review['kindness_rating'] ?? '-') . "</td>";
            echo "<td>" . ($review['speed_rating'] ?? '-') . "</td>";
            echo "<td style='color: $statusColor;'><strong>{$review['status']}</strong></td>";
            echo "<td>{$review['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 2. 주문건별 리뷰 그룹화
    echo "<h2>2. 주문건별 리뷰 그룹화</h2>";
    $reviewsByApplication = [];
    foreach ($reviews as $review) {
        $appId = $review['application_id'] ?? 'NULL';
        if (!isset($reviewsByApplication[$appId])) {
            $reviewsByApplication[$appId] = [];
        }
        $reviewsByApplication[$appId][] = $review;
    }
    
    echo "<table>";
    echo "<tr><th>주문건 ID</th><th>리뷰 개수</th><th>리뷰 ID 목록</th></tr>";
    foreach ($reviewsByApplication as $appId => $appReviews) {
        $reviewIds = array_map(function($r) { return $r['id']; }, $appReviews);
        echo "<tr>";
        echo "<td>" . ($appId === 'NULL' ? '<em>없음</em>' : $appId) . "</td>";
        echo "<td><strong>" . count($appReviews) . "</strong></td>";
        echo "<td>" . implode(', ', $reviewIds) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 3. 통계 테이블 확인
    echo "<h2>3. 통계 테이블 확인</h2>";
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
        echo "<tr>";
        echo "<td><strong>총 평균</strong></td>";
        echo "<td>{$stats['total_rating_sum']}</td>";
        echo "<td>{$stats['total_review_count']}</td>";
        echo "<td><strong style='color: green; font-size: 18px;'>" . number_format($realtimeAvg, 2) . "</strong></td>";
        echo "</tr>";
        if ($stats['kindness_review_count'] > 0) {
            $kindnessAvg = $stats['kindness_rating_sum'] / $stats['kindness_review_count'];
            echo "<tr>";
            echo "<td>친절해요</td>";
            echo "<td>{$stats['kindness_rating_sum']}</td>";
            echo "<td>{$stats['kindness_review_count']}</td>";
            echo "<td>" . number_format($kindnessAvg, 2) . "</td>";
            echo "</tr>";
        }
        if ($stats['speed_review_count'] > 0) {
            $speedAvg = $stats['speed_rating_sum'] / $stats['speed_review_count'];
            echo "<tr>";
            echo "<td>개통빨라요</td>";
            echo "<td>{$stats['speed_rating_sum']}</td>";
            echo "<td>{$stats['speed_review_count']}</td>";
            echo "<td>" . number_format($speedAvg, 2) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>통계 테이블에 데이터가 없습니다.</p>";
    }
    
    // 4. 실제 계산된 평균 (승인된 리뷰만)
    echo "<h2>4. 실제 계산된 평균 (승인된 리뷰만)</h2>";
    $approvedReviews = array_filter($reviews, function($r) { return $r['status'] === 'approved'; });
    $totalRating = 0;
    $kindnessTotal = 0;
    $kindnessCount = 0;
    $speedTotal = 0;
    $speedCount = 0;
    
    foreach ($approvedReviews as $review) {
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
    
    $actualAvg = count($approvedReviews) > 0 ? $totalRating / count($approvedReviews) : 0;
    $actualKindness = $kindnessCount > 0 ? $kindnessTotal / $kindnessCount : 0;
    $actualSpeed = $speedCount > 0 ? $speedTotal / $speedCount : 0;
    
    echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
    echo "<h3>실제 계산된 평균:</h3>";
    echo "<ul>";
    echo "<li><strong>총 평균:</strong> $totalRating / " . count($approvedReviews) . " = <strong style='color: blue; font-size: 18px;'>" . number_format($actualAvg, 2) . "</strong></li>";
    if ($kindnessCount > 0) {
        echo "<li><strong>친절해요:</strong> $kindnessTotal / $kindnessCount = " . number_format($actualKindness, 2) . "</li>";
    }
    if ($speedCount > 0) {
        echo "<li><strong>개통빨라요:</strong> $speedTotal / $speedCount = " . number_format($actualSpeed, 2) . "</li>";
    }
    echo "</ul>";
    echo "</div>";
    
    // 5. 주문건별 통계 반영 확인
    echo "<h2>5. 주문건별 통계 반영 확인</h2>";
    $applicationStats = [];
    foreach ($reviewsByApplication as $appId => $appReviews) {
        $approvedAppReviews = array_filter($appReviews, function($r) { return $r['status'] === 'approved'; });
        if (count($approvedAppReviews) > 0) {
            $appTotal = 0;
            $appCount = 0;
            foreach ($approvedAppReviews as $review) {
                $appTotal += $review['rating'];
                $appCount++;
            }
            $applicationStats[$appId] = [
                'count' => $appCount,
                'total' => $appTotal,
                'avg' => $appTotal / $appCount
            ];
        }
    }
    
    echo "<table>";
    echo "<tr><th>주문건 ID</th><th>리뷰 개수</th><th>별점 합계</th><th>평균</th><th>통계 반영 여부</th></tr>";
    foreach ($applicationStats as $appId => $appStat) {
        $shouldReflect = $appStat['count'] > 0;
        $status = $shouldReflect ? '<span class="success">✓ 반영됨</span>' : '<span class="error">✗ 미반영</span>';
        echo "<tr>";
        echo "<td>" . ($appId === 'NULL' ? '<em>없음</em>' : $appId) . "</td>";
        echo "<td>{$appStat['count']}</td>";
        echo "<td>{$appStat['total']}</td>";
        echo "<td>" . number_format($appStat['avg'], 2) . "</td>";
        echo "<td>$status</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 6. 분석
    echo "<h2>6. 분석</h2>";
    echo "<div style='background: #f0fdf4; padding: 20px; border-radius: 8px; border-left: 4px solid #16a34a;'>";
    
    $expectedTotal = array_sum(array_column($applicationStats, 'total'));
    $expectedCount = array_sum(array_column($applicationStats, 'count'));
    $expectedAvg = $expectedCount > 0 ? $expectedTotal / $expectedCount : 0;
    
    echo "<h3>예상 통계 (모든 주문건 합산):</h3>";
    echo "<ul>";
    echo "<li><strong>별점 합계:</strong> $expectedTotal</li>";
    echo "<li><strong>리뷰 개수:</strong> $expectedCount</li>";
    echo "<li><strong>평균:</strong> " . number_format($expectedAvg, 2) . "</li>";
    echo "</ul>";
    
    if ($stats) {
        $actualTotal = $stats['total_rating_sum'];
        $actualCount = $stats['total_review_count'];
        
        if (abs($expectedTotal - $actualTotal) < 0.01 && $expectedCount == $actualCount) {
            echo "<p class='success'>✅ 통계가 정확합니다! 모든 주문건의 리뷰가 통계에 반영되었습니다.</p>";
        } else {
            echo "<p class='error'>⚠️ 통계가 일치하지 않습니다!</p>";
            echo "<ul>";
            echo "<li>예상 합계: $expectedTotal, 실제 합계: $actualTotal (차이: " . number_format(abs($expectedTotal - $actualTotal), 2) . ")</li>";
            echo "<li>예상 개수: $expectedCount, 실제 개수: $actualCount (차이: " . abs($expectedCount - $actualCount) . ")</li>";
            echo "</ul>";
        }
    }
    
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>오류 발생: " . htmlspecialchars($e->getMessage()) . "</p>";
}

