<?php
/**
 * 리뷰 통계 표시 확인 스크립트
 * 통계 테이블 값과 실제 표시되는 값 비교
 */

require_once __DIR__ . '/includes/data/db-config.php';
require_once __DIR__ . '/includes/data/plan-data.php';

header('Content-Type: text/html; charset=utf-8');

$pdo = getDBConnection();
if (!$pdo) {
    die('데이터베이스 연결 실패');
}

// 상품 ID를 URL 파라미터로 받기
$productId = isset($_GET['product_id']) ? intval($_GET['product_id']) : 33;

echo "<h1>리뷰 통계 표시 확인</h1>";

try {
    echo "<h2>상품 ID: $productId</h2>";
    
    // 1. 통계 테이블 값 (처음 작성 시점의 값)
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
    
    // 2. 실제 리뷰 데이터 (현재 표시되는 값)
    $reviewsStmt = $pdo->prepare("
        SELECT 
            id,
            application_id,
            rating,
            kindness_rating,
            speed_rating,
            status,
            created_at,
            updated_at
        FROM product_reviews
        WHERE product_id = :product_id
        AND status = 'approved'
        ORDER BY created_at DESC
    ");
    $reviewsStmt->execute([':product_id' => $productId]);
    $reviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 3. 함수로 가져온 값 (실제 화면에 표시되는 값)
    $displayedTotal = getProductAverageRating($productId, 'mno');
    $displayedCategory = getInternetReviewCategoryAverages($productId, 'mno');
    
    echo "<h3>1. 통계 테이블 값 (처음 작성 시점의 값 - 변경되지 않음)</h3>";
    if ($stats) {
        $statsTotalAvg = $stats['total_review_count'] > 0 
            ? round($stats['total_rating_sum'] / $stats['total_review_count'], 1) 
            : 0;
        $statsKindnessAvg = $stats['kindness_review_count'] > 0 
            ? round($stats['kindness_rating_sum'] / $stats['kindness_review_count'], 1) 
            : 0;
        $statsSpeedAvg = $stats['speed_review_count'] > 0 
            ? round($stats['speed_rating_sum'] / $stats['speed_review_count'], 1) 
            : 0;
        
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>항목</th><th>합계</th><th>개수</th><th>평균</th></tr>";
        echo "<tr>";
        echo "<td><strong>총별점</strong></td>";
        echo "<td>" . $stats['total_rating_sum'] . "</td>";
        echo "<td>" . $stats['total_review_count'] . "</td>";
        echo "<td><strong>" . $statsTotalAvg . "</strong></td>";
        echo "</tr>";
        echo "<tr>";
        echo "<td>친절해요</td>";
        echo "<td>" . ($stats['kindness_rating_sum'] ?? 0) . "</td>";
        echo "<td>" . ($stats['kindness_review_count'] ?? 0) . "</td>";
        echo "<td>" . $statsKindnessAvg . "</td>";
        echo "</tr>";
        echo "<tr>";
        echo "<td>개통빨라요</td>";
        echo "<td>" . ($stats['speed_rating_sum'] ?? 0) . "</td>";
        echo "<td>" . ($stats['speed_review_count'] ?? 0) . "</td>";
        echo "<td>" . $statsSpeedAvg . "</td>";
        echo "</tr>";
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>통계 테이블에 데이터가 없습니다.</p>";
    }
    
    echo "<h3>2. 실제 리뷰 데이터 (현재 표시되는 개별 리뷰)</h3>";
    if (!empty($reviews)) {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>리뷰 ID</th><th>주문건 ID</th><th>별점</th><th>친절해요</th><th>개통빨라요</th><th>작성일</th><th>수정일</th></tr>";
        
        $actualTotalSum = 0;
        $actualKindnessSum = 0;
        $actualSpeedSum = 0;
        $actualKindnessCount = 0;
        $actualSpeedCount = 0;
        
        foreach ($reviews as $review) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($review['id']) . "</td>";
            echo "<td>" . htmlspecialchars($review['application_id'] ?? '-') . "</td>";
            echo "<td>" . htmlspecialchars($review['rating']) . "</td>";
            echo "<td>" . htmlspecialchars($review['kindness_rating'] ?? '-') . "</td>";
            echo "<td>" . htmlspecialchars($review['speed_rating'] ?? '-') . "</td>";
            echo "<td>" . htmlspecialchars($review['created_at']) . "</td>";
            echo "<td>" . htmlspecialchars($review['updated_at']) . "</td>";
            echo "</tr>";
            
            $actualTotalSum += $review['rating'];
            if ($review['kindness_rating'] !== null) {
                $actualKindnessSum += $review['kindness_rating'];
                $actualKindnessCount++;
            }
            if ($review['speed_rating'] !== null) {
                $actualSpeedSum += $review['speed_rating'];
                $actualSpeedCount++;
            }
        }
        echo "</table>";
        
        $actualTotalAvg = count($reviews) > 0 ? round($actualTotalSum / count($reviews), 1) : 0;
        $actualKindnessAvg = $actualKindnessCount > 0 ? round($actualKindnessSum / $actualKindnessCount, 1) : 0;
        $actualSpeedAvg = $actualSpeedCount > 0 ? round($actualSpeedSum / $actualSpeedCount, 1) : 0;
        
        echo "<h4>실제 리뷰 데이터로 계산한 평균:</h4>";
        echo "<ul>";
        echo "<li><strong>총별점:</strong> " . $actualTotalAvg . " (합계: $actualTotalSum, 개수: " . count($reviews) . ")</li>";
        echo "<li><strong>친절해요:</strong> " . $actualKindnessAvg . " (합계: $actualKindnessSum, 개수: $actualKindnessCount)</li>";
        echo "<li><strong>개통빨라요:</strong> " . $actualSpeedAvg . " (합계: $actualSpeedSum, 개수: $actualSpeedCount)</li>";
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'>리뷰가 없습니다.</p>";
    }
    
    echo "<h3>3. 화면에 표시되는 값 (getProductAverageRating, getInternetReviewCategoryAverages 함수 결과)</h3>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; margin: 10px 0; background: #f0fdf4;'>";
    echo "<tr><th>항목</th><th>표시되는 값</th><th>출처</th></tr>";
    echo "<tr>";
    echo "<td><strong>총별점</strong></td>";
    echo "<td><strong style='color: #16a34a; font-size: 18px;'>" . $displayedTotal . "</strong></td>";
    echo "<td>통계 테이블 (처음 작성 시점의 값)</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td>친절해요</td>";
    echo "<td><strong style='color: #16a34a;'>" . $displayedCategory['kindness'] . "</strong></td>";
    echo "<td>통계 테이블 (처음 작성 시점의 값)</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td>개통빨라요</td>";
    echo "<td><strong style='color: #16a34a;'>" . $displayedCategory['speed'] . "</strong></td>";
    echo "<td>통계 테이블 (처음 작성 시점의 값)</td>";
    echo "</tr>";
    echo "</table>";
    
    echo "<div style='background: #fef3c7; padding: 15px; border-radius: 8px; border-left: 4px solid #f59e0b; margin-top: 20px;'>";
    echo "<h4>설명:</h4>";
    echo "<ul>";
    echo "<li><strong>통계 테이블:</strong> 처음 리뷰 작성 시점의 값으로 고정되어 있습니다. 리뷰를 수정하거나 삭제해도 변경되지 않습니다.</li>";
    echo "<li><strong>실제 리뷰 데이터:</strong> 현재 각 리뷰에 저장된 값입니다. 리뷰를 수정하면 이 값은 변경되지만, 통계 테이블은 변경되지 않습니다.</li>";
    echo "<li><strong>화면 표시:</strong> <code>getProductAverageRating</code>과 <code>getInternetReviewCategoryAverages</code> 함수는 통계 테이블에서 값을 가져와서 표시합니다. 따라서 처음 작성 시점의 값이 표시됩니다.</li>";
    echo "</ul>";
    echo "<p><strong>이것은 의도된 동작입니다.</strong> 리뷰를 수정하거나 삭제해도 총별점과 항목별 총별점은 처음 작성 시점의 값으로 유지됩니다.</p>";
    echo "</div>";
    
    // 파라미터 변경 링크
    echo "<h3>다른 상품 확인하기</h3>";
    echo "<form method='get' style='background: #f9fafb; padding: 15px; border-radius: 8px;'>";
    echo "<label>상품 ID: <input type='number' name='product_id' value='$productId' min='1' style='width: 100px;'></label> ";
    echo "<button type='submit' style='margin-left: 10px; padding: 5px 15px; background: #3b82f6; color: white; border: none; border-radius: 5px; cursor: pointer;'>확인</button>";
    echo "</form>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>오류 발생: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}






