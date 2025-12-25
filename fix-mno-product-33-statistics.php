<?php
/**
 * MNO 상품 ID 33의 리뷰 통계 수정
 * 실제 리뷰 데이터를 기반으로 통계 테이블을 재계산하여 업데이트
 */

require_once __DIR__ . '/includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

$pdo = getDBConnection();
if (!$pdo) {
    die('데이터베이스 연결 실패');
}

$productId = 33;
$productType = 'mno';

echo "<h1>MNO 상품 ID 33 통계 수정</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; }
    .info { background: #e7f3ff; padding: 15px; border-radius: 8px; margin: 10px 0; }
    table { border-collapse: collapse; margin: 10px 0; width: 100%; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
</style>";

try {
    // 1. 실제 리뷰 데이터 가져오기
    $reviewsStmt = $pdo->prepare("
        SELECT 
            id,
            user_id,
            rating,
            kindness_rating,
            speed_rating,
            status,
            application_id,
            created_at
        FROM product_reviews
        WHERE product_id = :product_id
        AND product_type = :product_type
        AND status = 'approved'
        ORDER BY created_at ASC
    ");
    $reviewsStmt->execute([
        ':product_id' => $productId,
        ':product_type' => $productType
    ]);
    $reviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='info'>";
    echo "<h2>실제 리뷰 데이터</h2>";
    echo "<p><strong>승인된 리뷰 수:</strong> " . count($reviews) . "개</p>";
    
    if (count($reviews) > 0) {
        echo "<table>";
        echo "<tr><th>리뷰 ID</th><th>사용자</th><th>주문건 ID</th><th>별점</th><th>친절해요</th><th>개통빨라요</th><th>작성일</th></tr>";
        foreach ($reviews as $review) {
            echo "<tr>";
            echo "<td>{$review['id']}</td>";
            echo "<td>{$review['user_id']}</td>";
            echo "<td>" . ($review['application_id'] ?? '<em>없음</em>') . "</td>";
            echo "<td><strong>{$review['rating']}</strong></td>";
            echo "<td>" . ($review['kindness_rating'] ?? '-') . "</td>";
            echo "<td>" . ($review['speed_rating'] ?? '-') . "</td>";
            echo "<td>{$review['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // 통계 계산
        $totalRatingSum = 0;
        $kindnessSum = 0;
        $kindnessCount = 0;
        $speedSum = 0;
        $speedCount = 0;
        
        foreach ($reviews as $review) {
            $totalRatingSum += $review['rating'];
            if ($review['kindness_rating'] !== null) {
                $kindnessSum += $review['kindness_rating'];
                $kindnessCount++;
            }
            if ($review['speed_rating'] !== null) {
                $speedSum += $review['speed_rating'];
                $speedCount++;
            }
        }
        
        $avgRating = count($reviews) > 0 ? $totalRatingSum / count($reviews) : 0;
        $avgKindness = $kindnessCount > 0 ? $kindnessSum / $kindnessCount : 0;
        $avgSpeed = $speedCount > 0 ? $speedSum / $speedCount : 0;
        
        echo "<div class='info'>";
        echo "<h3>계산된 통계:</h3>";
        echo "<ul>";
        echo "<li><strong>총 평균:</strong> $totalRatingSum / " . count($reviews) . " = " . number_format($avgRating, 2) . "</li>";
        if ($kindnessCount > 0) {
            echo "<li><strong>친절해요:</strong> $kindnessSum / $kindnessCount = " . number_format($avgKindness, 2) . "</li>";
        }
        if ($speedCount > 0) {
            echo "<li><strong>개통빨라요:</strong> $speedSum / $speedCount = " . number_format($avgSpeed, 2) . "</li>";
        }
        echo "</ul>";
        echo "</div>";
        
        // 2. 통계 테이블 업데이트
        echo "<h2>통계 테이블 업데이트</h2>";
        
        // initial_* 컬럼 존재 여부 확인
        $hasInitialColumns = false;
        try {
            $checkStmt = $pdo->query("SHOW COLUMNS FROM product_review_statistics LIKE 'initial_total_rating_sum'");
            $hasInitialColumns = $checkStmt->rowCount() > 0;
        } catch (PDOException $e) {}
        
        if ($hasInitialColumns) {
            // 하이브리드 방식: initial_* 컬럼도 함께 업데이트
            $updateStmt = $pdo->prepare("
                INSERT INTO product_review_statistics (
                    product_id,
                    total_rating_sum,
                    total_review_count,
                    initial_total_rating_sum,
                    initial_total_review_count,
                    kindness_rating_sum,
                    kindness_review_count,
                    initial_kindness_rating_sum,
                    initial_kindness_review_count,
                    speed_rating_sum,
                    speed_review_count,
                    initial_speed_rating_sum,
                    initial_speed_review_count,
                    updated_at
                ) VALUES (
                    :product_id,
                    :total_rating_sum,
                    :total_review_count,
                    :initial_total_rating_sum,
                    :initial_total_review_count,
                    :kindness_rating_sum,
                    :kindness_review_count,
                    :initial_kindness_rating_sum,
                    :initial_kindness_review_count,
                    :speed_rating_sum,
                    :speed_review_count,
                    :initial_speed_rating_sum,
                    :initial_speed_review_count,
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
                ':total_rating_sum' => $totalRatingSum,
                ':total_review_count' => count($reviews),
                ':initial_total_rating_sum' => $totalRatingSum,
                ':initial_total_review_count' => count($reviews),
                ':kindness_rating_sum' => $kindnessSum,
                ':kindness_review_count' => $kindnessCount,
                ':initial_kindness_rating_sum' => $kindnessSum,
                ':initial_kindness_review_count' => $kindnessCount,
                ':speed_rating_sum' => $speedSum,
                ':speed_review_count' => $speedCount,
                ':initial_speed_rating_sum' => $speedSum,
                ':initial_speed_review_count' => $speedCount,
                ':total_rating_sum2' => $totalRatingSum,
                ':total_review_count2' => count($reviews),
                ':kindness_rating_sum2' => $kindnessSum,
                ':kindness_review_count2' => $kindnessCount,
                ':speed_rating_sum2' => $speedSum,
                ':speed_review_count2' => $speedCount
            ]);
        } else {
            // 기본 방식: initial_* 컬럼 없음
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
                ':total_rating_sum' => $totalRatingSum,
                ':total_review_count' => count($reviews),
                ':kindness_rating_sum' => $kindnessSum,
                ':kindness_review_count' => $kindnessCount,
                ':speed_rating_sum' => $speedSum,
                ':speed_review_count' => $speedCount,
                ':total_rating_sum2' => $totalRatingSum,
                ':total_review_count2' => count($reviews),
                ':kindness_rating_sum2' => $kindnessSum,
                ':kindness_review_count2' => $kindnessCount,
                ':speed_rating_sum2' => $speedSum,
                ':speed_review_count2' => $speedCount
            ]);
        }
        
        echo "<p class='success'>✅ 통계 테이블 업데이트 완료!</p>";
        echo "<ul>";
        echo "<li><strong>total_rating_sum:</strong> $totalRatingSum (이전: 5.00)</li>";
        echo "<li><strong>total_review_count:</strong> " . count($reviews) . " (이전: 1)</li>";
        if ($kindnessCount > 0) {
            echo "<li><strong>kindness_rating_sum:</strong> $kindnessSum (이전: 5.00)</li>";
            echo "<li><strong>kindness_review_count:</strong> $kindnessCount (이전: 1)</li>";
        }
        if ($speedCount > 0) {
            echo "<li><strong>speed_rating_sum:</strong> $speedSum (이전: 5.00)</li>";
            echo "<li><strong>speed_review_count:</strong> $speedCount (이전: 1)</li>";
        }
        echo "</ul>";
        
        // 3. 업데이트 후 확인
        echo "<h2>업데이트 후 통계 테이블 확인</h2>";
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
            
            // 검증
            if (abs($stats['total_rating_sum'] - $totalRatingSum) < 0.01 && 
                $stats['total_review_count'] == count($reviews)) {
                echo "<p class='success'>✅ 통계가 정확하게 업데이트되었습니다!</p>";
            } else {
                echo "<p class='error'>⚠️ 통계가 일치하지 않습니다. 다시 확인해주세요.</p>";
            }
        }
        
    } else {
        echo "<p style='color: orange;'>승인된 리뷰가 없습니다.</p>";
    }
    
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<p class='error'>오류 발생: " . htmlspecialchars($e->getMessage()) . "</p>";
}





