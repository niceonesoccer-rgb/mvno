<?php
/**
 * 상품 ID 24 별점 확인
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

try {
    // 1. 실제 리뷰 데이터 확인
    echo "<h2>1. 실제 리뷰 데이터</h2>";
    $reviewsStmt = $pdo->prepare("
        SELECT 
            id,
            user_id,
            rating,
            kindness_rating,
            speed_rating,
            status,
            created_at,
            updated_at,
            application_id
        FROM product_reviews
        WHERE product_id = :product_id
        AND product_type = :product_type
        ORDER BY created_at DESC
    ");
    $reviewsStmt->execute([':product_id' => $productId, ':product_type' => $productType]);
    $reviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>총 리뷰 수:</strong> " . count($reviews) . "개</p>";
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>리뷰 ID</th><th>사용자</th><th>별점</th><th>친절해요</th><th>개통빨라요</th><th>상태</th><th>신청ID</th><th>작성일</th></tr>";
    
    $approvedCount = 0;
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
        echo "<td>" . ($review['application_id'] ?? '-') . "</td>";
        echo "<td>{$review['created_at']}</td>";
        echo "</tr>";
        
        if ($review['status'] === 'approved') {
            $approvedCount++;
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
    $actualAverage = $approvedCount > 0 ? $totalRating / $approvedCount : 0;
    $actualKindness = $kindnessCount > 0 ? $kindnessTotal / $kindnessCount : 0;
    $actualSpeed = $speedCount > 0 ? $speedTotal / $speedCount : 0;
    
    echo "<div style='background: #f0f9ff; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
    echo "<h3>실제 계산된 평균 (approved 리뷰만):</h3>";
    echo "<ul>";
    echo "<li><strong>총 평균:</strong> $totalRating / $approvedCount = <strong style='color: blue; font-size: 18px;'>" . number_format($actualAverage, 2) . "</strong></li>";
    if ($kindnessCount > 0) {
        echo "<li><strong>친절해요 평균:</strong> $kindnessTotal / $kindnessCount = <strong style='color: blue;'>" . number_format($actualKindness, 2) . "</strong></li>";
    }
    if ($speedCount > 0) {
        echo "<li><strong>개통빨라요 평균:</strong> $speedTotal / $speedCount = <strong style='color: blue;'>" . number_format($actualSpeed, 2) . "</strong></li>";
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
            speed_review_count,
            initial_total_rating_sum,
            initial_total_review_count,
            initial_kindness_rating_sum,
            initial_kindness_review_count,
            initial_speed_rating_sum,
            initial_speed_review_count
        FROM product_review_statistics
        WHERE product_id = :product_id
    ");
    $statsStmt->execute([':product_id' => $productId]);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($stats) {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>항목</th><th>실시간 통계</th><th>처음 작성 시점 통계</th></tr>";
        
        // 총 평균
        $realtimeAvg = $stats['total_review_count'] > 0 ? $stats['total_rating_sum'] / $stats['total_review_count'] : 0;
        $initialAvg = $stats['initial_total_review_count'] > 0 ? $stats['initial_total_rating_sum'] / $stats['initial_total_review_count'] : 0;
        
        echo "<tr>";
        echo "<td><strong>총 평균</strong></td>";
        echo "<td>{$stats['total_rating_sum']} / {$stats['total_review_count']} = <strong style='color: green;'>" . number_format($realtimeAvg, 2) . "</strong></td>";
        echo "<td>{$stats['initial_total_rating_sum']} / {$stats['initial_total_review_count']} = <strong style='color: orange;'>" . number_format($initialAvg, 2) . "</strong></td>";
        echo "</tr>";
        
        // 친절해요
        if ($stats['kindness_review_count'] > 0) {
            $realtimeKindness = $stats['kindness_rating_sum'] / $stats['kindness_review_count'];
            $initialKindness = $stats['initial_kindness_review_count'] > 0 ? $stats['initial_kindness_rating_sum'] / $stats['initial_kindness_review_count'] : 0;
            
            echo "<tr>";
            echo "<td><strong>친절해요</strong></td>";
            echo "<td>{$stats['kindness_rating_sum']} / {$stats['kindness_review_count']} = <strong style='color: green;'>" . number_format($realtimeKindness, 2) . "</strong></td>";
            echo "<td>{$stats['initial_kindness_rating_sum']} / {$stats['initial_kindness_review_count']} = <strong style='color: orange;'>" . number_format($initialKindness, 2) . "</strong></td>";
            echo "</tr>";
        }
        
        // 개통빨라요
        if ($stats['speed_review_count'] > 0) {
            $realtimeSpeed = $stats['speed_rating_sum'] / $stats['speed_review_count'];
            $initialSpeed = $stats['initial_speed_review_count'] > 0 ? $stats['initial_speed_rating_sum'] / $stats['initial_speed_review_count'] : 0;
            
            echo "<tr>";
            echo "<td><strong>개통빨라요</strong></td>";
            echo "<td>{$stats['speed_rating_sum']} / {$stats['speed_review_count']} = <strong style='color: green;'>" . number_format($realtimeSpeed, 2) . "</strong></td>";
            echo "<td>{$stats['initial_speed_rating_sum']} / {$stats['initial_speed_review_count']} = <strong style='color: orange;'>" . number_format($initialSpeed, 2) . "</strong></td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p style='color: red;'>통계 테이블에 데이터가 없습니다.</p>";
    }
    
    // 3. 함수로 가져온 평균 확인
    echo "<h2>3. getProductAverageRating() 함수 결과</h2>";
    $functionAverage = getProductAverageRating($productId, $productType);
    echo "<p><strong>함수 결과:</strong> <span style='color: blue; font-size: 20px;'><strong>$functionAverage</strong></span></p>";
    
    // 표시 방식 확인
    $displayedWithCeil = ceil($actualAverage * 10) / 10;
    $displayedWithRound = round($actualAverage * 10) / 10;
    
    echo "<div style='background: #fff7ed; padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #f59e0b;'>";
    echo "<h3>표시 방식 비교:</h3>";
    echo "<ul>";
    echo "<li><strong>실제 평균:</strong> " . number_format($actualAverage, 10) . "</li>";
    echo "<li><strong>올림 (ceil):</strong> " . number_format($displayedWithCeil, 1) . "</li>";
    echo "<li><strong>반올림 (round):</strong> " . number_format($displayedWithRound, 1) . "</li>";
    echo "<li><strong>함수 결과:</strong> <strong style='color: red; font-size: 18px;'>$functionAverage</strong></li>";
    echo "</ul>";
    echo "</div>";
    
    // 4. 비교 및 분석
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
    }
    
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>오류 발생: " . htmlspecialchars($e->getMessage()) . "</p>";
}
