<?php
/**
 * 리뷰 통계 계산 예측 스크립트
 * 새로운 리뷰를 작성했을 때 통계가 어떻게 변경될지 계산
 */

require_once __DIR__ . '/includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

$pdo = getDBConnection();
if (!$pdo) {
    die('데이터베이스 연결 실패');
}

// 상품 ID를 URL 파라미터로 받기
$productId = isset($_GET['product_id']) ? intval($_GET['product_id']) : 33;
$newRating = isset($_GET['new_rating']) ? intval($_GET['new_rating']) : 1;
$newKindness = isset($_GET['new_kindness']) ? intval($_GET['new_kindness']) : 1;
$newSpeed = isset($_GET['new_speed']) ? intval($_GET['new_speed']) : 1;

echo "<h1>리뷰 통계 계산 예측</h1>";

try {
    echo "<h2>상품 ID: $productId</h2>";
    
    // 현재 통계 테이블 값
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
    $currentStats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    // 현재 리뷰 목록
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
        AND status = 'approved'
        ORDER BY created_at DESC
    ");
    $reviewsStmt->execute([':product_id' => $productId]);
    $currentReviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($currentStats) {
        echo "<h3>현재 통계 (처음 작성 시점의 값)</h3>";
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>항목</th><th>합계</th><th>개수</th><th>평균</th></tr>";
        
        $currentTotalAvg = $currentStats['total_review_count'] > 0 
            ? round($currentStats['total_rating_sum'] / $currentStats['total_review_count'], 1) 
            : 0;
        echo "<tr>";
        echo "<td><strong>총별점</strong></td>";
        echo "<td>" . $currentStats['total_rating_sum'] . "</td>";
        echo "<td>" . $currentStats['total_review_count'] . "</td>";
        echo "<td><strong>" . $currentTotalAvg . "</strong></td>";
        echo "</tr>";
        
        if ($currentStats['kindness_review_count'] > 0) {
            $currentKindnessAvg = round($currentStats['kindness_rating_sum'] / $currentStats['kindness_review_count'], 1);
            echo "<tr>";
            echo "<td>친절해요</td>";
            echo "<td>" . $currentStats['kindness_rating_sum'] . "</td>";
            echo "<td>" . $currentStats['kindness_review_count'] . "</td>";
            echo "<td>" . $currentKindnessAvg . "</td>";
            echo "</tr>";
        }
        
        if ($currentStats['speed_review_count'] > 0) {
            $currentSpeedAvg = round($currentStats['speed_rating_sum'] / $currentStats['speed_review_count'], 1);
            echo "<tr>";
            echo "<td>개통빨라요</td>";
            echo "<td>" . $currentStats['speed_rating_sum'] . "</td>";
            echo "<td>" . $currentStats['speed_review_count'] . "</td>";
            echo "<td>" . $currentSpeedAvg . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // 현재 리뷰 목록
        echo "<h3>현재 리뷰 목록</h3>";
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>리뷰 ID</th><th>주문건 ID</th><th>별점</th><th>친절해요</th><th>개통빨라요</th><th>작성일</th></tr>";
        foreach ($currentReviews as $review) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($review['id']) . "</td>";
            echo "<td>" . htmlspecialchars($review['application_id'] ?? '-') . "</td>";
            echo "<td>" . htmlspecialchars($review['rating']) . "</td>";
            echo "<td>" . htmlspecialchars($review['kindness_rating'] ?? '-') . "</td>";
            echo "<td>" . htmlspecialchars($review['speed_rating'] ?? '-') . "</td>";
            echo "<td>" . htmlspecialchars($review['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // 새로운 리뷰 추가 시 예상 통계
        echo "<h3>새로운 리뷰 추가 시 예상 통계</h3>";
        echo "<p><strong>새 리뷰:</strong> 별점 $newRating, 친절해요 $newKindness, 개통빨라요 $newSpeed</p>";
        
        // 새로운 통계 계산
        $newTotalSum = $currentStats['total_rating_sum'] + $newRating;
        $newTotalCount = $currentStats['total_review_count'] + 1;
        $newTotalAvg = round($newTotalSum / $newTotalCount, 1);
        
        $newKindnessSum = ($currentStats['kindness_rating_sum'] ?? 0) + $newKindness;
        $newKindnessCount = ($currentStats['kindness_review_count'] ?? 0) + 1;
        $newKindnessAvg = round($newKindnessSum / $newKindnessCount, 1);
        
        $newSpeedSum = ($currentStats['speed_rating_sum'] ?? 0) + $newSpeed;
        $newSpeedCount = ($currentStats['speed_review_count'] ?? 0) + 1;
        $newSpeedAvg = round($newSpeedSum / $newSpeedCount, 1);
        
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; margin: 10px 0; background: #f0fdf4;'>";
        echo "<tr><th>항목</th><th>현재 합계</th><th>새 리뷰</th><th>예상 합계</th><th>예상 개수</th><th><strong>예상 평균</strong></th></tr>";
        
        echo "<tr>";
        echo "<td><strong>총별점</strong></td>";
        echo "<td>" . $currentStats['total_rating_sum'] . "</td>";
        echo "<td>+ $newRating</td>";
        echo "<td>" . $newTotalSum . "</td>";
        echo "<td>" . $newTotalCount . "</td>";
        echo "<td><strong style='color: #16a34a; font-size: 18px;'>" . $newTotalAvg . "</strong></td>";
        echo "</tr>";
        
        if ($currentStats['kindness_review_count'] > 0 || $newKindness > 0) {
            echo "<tr>";
            echo "<td>친절해요</td>";
            echo "<td>" . ($currentStats['kindness_rating_sum'] ?? 0) . "</td>";
            echo "<td>+ $newKindness</td>";
            echo "<td>" . $newKindnessSum . "</td>";
            echo "<td>" . $newKindnessCount . "</td>";
            echo "<td><strong style='color: #16a34a;'>" . $newKindnessAvg . "</strong></td>";
            echo "</tr>";
        }
        
        if ($currentStats['speed_review_count'] > 0 || $newSpeed > 0) {
            echo "<tr>";
            echo "<td>개통빨라요</td>";
            echo "<td>" . ($currentStats['speed_rating_sum'] ?? 0) . "</td>";
            echo "<td>+ $newSpeed</td>";
            echo "<td>" . $newSpeedSum . "</td>";
            echo "<td>" . $newSpeedCount . "</td>";
            echo "<td><strong style='color: #16a34a;'>" . $newSpeedAvg . "</strong></td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // 계산식 설명
        echo "<div style='background: #fef3c7; padding: 15px; border-radius: 8px; border-left: 4px solid #f59e0b; margin-top: 20px;'>";
        echo "<h4>계산식:</h4>";
        echo "<ul>";
        echo "<li><strong>총별점 평균:</strong> (" . $currentStats['total_rating_sum'] . " + $newRating) ÷ " . $newTotalCount . " = <strong>" . $newTotalAvg . "</strong></li>";
        if ($currentStats['kindness_review_count'] > 0 || $newKindness > 0) {
            echo "<li><strong>친절해요 평균:</strong> (" . ($currentStats['kindness_rating_sum'] ?? 0) . " + $newKindness) ÷ " . $newKindnessCount . " = <strong>" . $newKindnessAvg . "</strong></li>";
        }
        if ($currentStats['speed_review_count'] > 0 || $newSpeed > 0) {
            echo "<li><strong>개통빨라요 평균:</strong> (" . ($currentStats['speed_rating_sum'] ?? 0) . " + $newSpeed) ÷ " . $newSpeedCount . " = <strong>" . $newSpeedAvg . "</strong></li>";
        }
        echo "</ul>";
        echo "<p><strong>주의:</strong> 이것은 새로운 주문건으로 리뷰를 작성하는 경우입니다. 같은 주문건에서 삭제 후 다시 작성하는 경우는 통계에 반영되지 않습니다.</p>";
        echo "</div>";
        
        // 파라미터 변경 링크
        echo "<h3>다른 값으로 계산하기</h3>";
        echo "<form method='get' style='background: #f9fafb; padding: 15px; border-radius: 8px;'>";
        echo "<input type='hidden' name='product_id' value='$productId'>";
        echo "<label>별점: <input type='number' name='new_rating' value='$newRating' min='1' max='5' style='width: 60px;'></label> ";
        echo "<label>친절해요: <input type='number' name='new_kindness' value='$newKindness' min='1' max='5' style='width: 60px;'></label> ";
        echo "<label>개통빨라요: <input type='number' name='new_speed' value='$newSpeed' min='1' max='5' style='width: 60px;'></label> ";
        echo "<button type='submit' style='margin-left: 10px; padding: 5px 15px; background: #3b82f6; color: white; border: none; border-radius: 5px; cursor: pointer;'>계산</button>";
        echo "</form>";
        
    } else {
        echo "<p style='color: orange;'>통계 테이블에 데이터가 없습니다.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>오류 발생: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}




