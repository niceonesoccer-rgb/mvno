<?php
/**
 * MNO 상품 ID 33의 리뷰 통계 확인 및 수정
 */

require_once __DIR__ . '/includes/data/db-config.php';
require_once __DIR__ . '/includes/data/product-functions.php';
require_once __DIR__ . '/includes/data/plan-data.php';

header('Content-Type: text/html; charset=utf-8');

$pdo = getDBConnection();
if (!$pdo) {
    die('데이터베이스 연결 실패');
}

$productId = 33;
$productType = 'mno';

echo "<h1>MNO 상품 ID 33 리뷰 통계 확인</h1>";
echo "<style>
    table { border-collapse: collapse; margin: 10px 0; width: 100%; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    .success { color: green; }
    .error { color: red; }
    .info { background: #e7f3ff; padding: 15px; border-radius: 8px; margin: 10px 0; }
</style>";

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
            created_at
        FROM product_reviews
        WHERE product_id = :product_id
        AND product_type = :product_type
        AND status = 'approved'
        ORDER BY created_at DESC
    ");
    $reviewsStmt->execute([
        ':product_id' => $productId,
        ':product_type' => $productType
    ]);
    $reviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>승인된 리뷰 수:</strong> " . count($reviews) . "개</p>";
    
    if (count($reviews) > 0) {
        echo "<table>";
        echo "<tr><th>리뷰 ID</th><th>사용자</th><th>별점</th><th>친절해요</th><th>개통빨라요</th><th>작성일</th></tr>";
        foreach ($reviews as $review) {
            echo "<tr>";
            echo "<td>{$review['id']}</td>";
            echo "<td>{$review['user_id']}</td>";
            echo "<td><strong>{$review['rating']}</strong></td>";
            echo "<td>" . ($review['kindness_rating'] ?? '-') . "</td>";
            echo "<td>" . ($review['speed_rating'] ?? '-') . "</td>";
            echo "<td>{$review['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // 실제 계산된 평균
        $totalRating = 0;
        $kindnessTotal = 0;
        $kindnessCount = 0;
        $speedTotal = 0;
        $speedCount = 0;
        
        foreach ($reviews as $review) {
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
        
        $actualAvg = count($reviews) > 0 ? $totalRating / count($reviews) : 0;
        $actualKindness = $kindnessCount > 0 ? $kindnessTotal / $kindnessCount : 0;
        $actualSpeed = $speedCount > 0 ? $speedTotal / $speedCount : 0;
        
        echo "<div class='info'>";
        echo "<h3>실제 계산된 평균:</h3>";
        echo "<ul>";
        echo "<li><strong>총 평균:</strong> $totalRating / " . count($reviews) . " = <strong style='color: blue; font-size: 18px;'>" . number_format($actualAvg, 2) . "</strong></li>";
        if ($kindnessCount > 0) {
            echo "<li><strong>친절해요:</strong> $kindnessTotal / $kindnessCount = " . number_format($actualKindness, 2) . "</li>";
        }
        if ($speedCount > 0) {
            echo "<li><strong>개통빨라요:</strong> $speedTotal / $speedCount = " . number_format($actualSpeed, 2) . "</li>";
        }
        echo "</ul>";
        echo "</div>";
    } else {
        echo "<p style='color: orange;'>승인된 리뷰가 없습니다.</p>";
    }
    
    // 2. 통계 테이블 확인
    echo "<h2>2. 통계 테이블 확인</h2>";
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
        echo "<p style='color: red;'><strong>통계 테이블에 데이터가 없습니다!</strong></p>";
    }
    
    // 3. 함수로 계산된 평균 확인
    echo "<h2>3. 함수로 계산된 평균</h2>";
    $funcAvg = getProductAverageRating($productId, $productType);
    $funcCategory = getInternetReviewCategoryAverages($productId, $productType);
    
    echo "<div class='info'>";
    echo "<h3>getProductAverageRating 결과:</h3>";
    echo "<p><strong>총 평균:</strong> " . number_format($funcAvg, 2) . "</p>";
    echo "<h3>getInternetReviewCategoryAverages 결과:</h3>";
    echo "<p><strong>친절해요:</strong> " . number_format($funcCategory['kindness'], 2) . "</p>";
    echo "<p><strong>개통빨라요:</strong> " . number_format($funcCategory['speed'], 2) . "</p>";
    echo "</div>";
    
    // 4. 통계 수정 (필요한 경우)
    if (count($reviews) > 0) {
        echo "<h2>4. 통계 수정</h2>";
        
        // 통계 테이블에 데이터가 없거나 불일치하는 경우 수정
        $needsUpdate = false;
        if (!$stats || 
            $stats['total_review_count'] != count($reviews) ||
            abs($stats['total_rating_sum'] - $totalRating) > 0.01) {
            $needsUpdate = true;
        }
        
        if ($needsUpdate || isset($_GET['fix'])) {
            echo "<div class='info'>";
            echo "<h3>통계 테이블 업데이트 중...</h3>";
            
            // 통계 테이블 업데이트
            $updateStmt = $pdo->prepare("
                INSERT INTO product_review_statistics (
                    product_id,
                    total_rating_sum,
                    total_review_count,
                    kindness_rating_sum,
                    kindness_review_count,
                    speed_rating_sum,
                    speed_review_count,
                    updated_at
                ) VALUES (
                    :product_id,
                    :total_rating_sum,
                    :total_review_count,
                    :kindness_rating_sum,
                    :kindness_review_count,
                    :speed_rating_sum,
                    :speed_review_count,
                    NOW()
                )
                ON DUPLICATE KEY UPDATE
                    total_rating_sum = :total_rating_sum2,
                    total_review_count = :total_review_count2,
                    kindness_rating_sum = :kindness_rating_sum2,
                    kindness_review_count = :kindness_review_count2,
                    speed_rating_sum = :speed_rating_sum2,
                    speed_review_count = :speed_review_count2,
                    updated_at = NOW()
            ");
            
            $updateStmt->execute([
                ':product_id' => $productId,
                ':total_rating_sum' => $totalRating,
                ':total_review_count' => count($reviews),
                ':kindness_rating_sum' => $kindnessTotal,
                ':kindness_review_count' => $kindnessCount,
                ':speed_rating_sum' => $speedTotal,
                ':speed_review_count' => $speedCount,
                ':total_rating_sum2' => $totalRating,
                ':total_review_count2' => count($reviews),
                ':kindness_rating_sum2' => $kindnessTotal,
                ':kindness_review_count2' => $kindnessCount,
                ':speed_rating_sum2' => $speedTotal,
                ':speed_review_count2' => $speedCount
            ]);
            
            echo "<p class='success'>✅ 통계 테이블 업데이트 완료!</p>";
            echo "<p><a href='?fix=1'>페이지 새로고침</a></p>";
        } else {
            echo "<p>통계 테이블이 정상입니다.</p>";
            echo "<p><a href='?fix=1' style='color: blue;'>강제로 통계 업데이트하기</a></p>";
        }
        
        echo "</div>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>오류 발생: " . htmlspecialchars($e->getMessage()) . "</p>";
}


