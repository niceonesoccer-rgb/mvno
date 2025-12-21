<?php
/**
 * 삭제된 리뷰 완전 제거 및 통계 재계산 스크립트
 * 브라우저에서 실행: http://localhost/MVNO/cleanup-deleted-reviews-and-recalculate-statistics.php
 * 
 * 1. status='deleted'인 모든 리뷰를 물리적으로 삭제
 * 2. 모든 상품의 통계를 실제 approved 리뷰 데이터로 재계산
 *    - 평균 = 전체 리뷰 수에 대한 총합계의 평균
 */

require_once __DIR__ . '/includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>삭제된 리뷰 정리 및 통계 재계산</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .success { background: #d4edda; border: 2px solid #28a745; padding: 15px; border-radius: 8px; margin: 20px 0; color: #155724; }
        .error { background: #f8d7da; border: 2px solid #dc3545; padding: 15px; border-radius: 8px; margin: 20px 0; color: #721c24; }
        .info { background: #d1ecf1; border: 2px solid #17a2b8; padding: 15px; border-radius: 8px; margin: 20px 0; color: #0c5460; }
        .warning { background: #fff3cd; border: 2px solid #ffc107; padding: 15px; border-radius: 8px; margin: 20px 0; color: #856404; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        .btn { 
            display: inline-block; 
            padding: 12px 24px; 
            margin: 10px 5px; 
            background: #10b981; 
            color: white; 
            text-decoration: none; 
            border-radius: 4px; 
            cursor: pointer;
            border: none;
            font-size: 16px;
        }
        .btn:hover { background: #059669; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .progress { background: #e9ecef; border-radius: 4px; height: 30px; margin: 10px 0; overflow: hidden; }
        .progress-bar { background: #28a745; height: 100%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; transition: width 0.3s; }
    </style>
</head>
<body>
<div class='container'>";

try {
    $pdo = getDBConnection();
    
    if (!$pdo) {
        echo "<p class='error'>✗ 데이터베이스 연결 실패</p>";
        exit;
    }
    
    echo "<h1>삭제된 리뷰 정리 및 통계 재계산</h1>";
    echo "<p class='success'>✓ 데이터베이스 연결 성공</p>";
    
    // 모든 리뷰 삭제
    if (isset($_POST['delete_all']) && $_POST['delete_all'] === 'yes') {
        echo "<div class='info'>";
        echo "<h2>모든 리뷰 삭제 진행 중...</h2>";
        echo "</div>";
        
        $pdo->beginTransaction();
        
        try {
            // 모든 리뷰 개수 확인
            $allCountStmt = $pdo->query("SELECT COUNT(*) as count FROM product_reviews");
            $allCount = $allCountStmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // 모든 리뷰 삭제
            $deleteAllStmt = $pdo->prepare("DELETE FROM product_reviews");
            $deleteAllStmt->execute();
            $deletedAllRows = $deleteAllStmt->rowCount();
            
            // 모든 통계 삭제
            $deleteStatsStmt = $pdo->prepare("DELETE FROM product_review_statistics");
            $deleteStatsStmt->execute();
            $deletedStatsRows = $deleteStatsStmt->rowCount();
            
            // products 테이블의 review_count 초기화
            $resetCountStmt = $pdo->prepare("UPDATE products SET review_count = 0");
            $resetCountStmt->execute();
            
            $pdo->commit();
            
            echo "<div class='success'>";
            echo "<h2>✓ 모든 리뷰 삭제 완료</h2>";
            echo "<hr style='margin: 15px 0; border: none; border-top: 1px solid #ddd;'>";
            echo "<h3>삭제 결과</h3>";
            echo "<ul>";
            echo "<li>삭제된 리뷰: <strong>{$deletedAllRows}개</strong></li>";
            echo "<li>삭제된 통계: <strong>{$deletedStatsRows}개</strong> 상품</li>";
            echo "<li>상품 review_count 초기화 완료</li>";
            echo "</ul>";
            echo "</div>";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "<div class='error'>";
            echo "<h2>✗ 삭제 실패</h2>";
            echo "<p>오류: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
        }
    }
    // 실행 확인
    else if (isset($_POST['execute']) && $_POST['execute'] === 'yes') {
        echo "<div class='info'>";
        echo "<h2>작업 진행 중...</h2>";
        echo "</div>";
        
        $pdo->beginTransaction();
        
        try {
            // 1. 삭제된 리뷰 개수 확인
            $deletedCountStmt = $pdo->query("SELECT COUNT(*) as count FROM product_reviews WHERE status = 'deleted'");
            $deletedCount = $deletedCountStmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            $deletedRows = 0; // 초기화
            
            echo "<div class='info'>";
            echo "<h3>1단계: 삭제된 리뷰 제거</h3>";
            echo "<p>삭제된 리뷰 개수: <strong>{$deletedCount}개</strong></p>";
            echo "</div>";
            
            // 2. 삭제된 리뷰 물리적 삭제
            if ($deletedCount > 0) {
                $deleteStmt = $pdo->prepare("DELETE FROM product_reviews WHERE status = 'deleted'");
                $deleteStmt->execute();
                $deletedRows = $deleteStmt->rowCount();
                echo "<div class='success'>";
                echo "<p>✓ 삭제된 리뷰 <strong>{$deletedRows}개</strong> 완전 제거 완료</p>";
                echo "</div>";
            } else {
                echo "<div class='info'>";
                echo "<p>삭제된 리뷰가 없습니다.</p>";
                echo "</div>";
            }
            
            // 3. 모든 상품의 통계 재계산
            echo "<div class='info'>";
            echo "<h3>2단계: 모든 상품 통계 재계산</h3>";
            echo "<p>실제 approved 리뷰 데이터를 기반으로 통계를 재계산합니다.</p>";
            echo "<p>평균 = 전체 리뷰 수에 대한 총합계의 평균</p>";
            echo "</div>";
            
            // 모든 상품 ID 가져오기 (리뷰가 있는 상품만)
            $productsStmt = $pdo->query("
                SELECT DISTINCT product_id, product_type 
                FROM product_reviews 
                WHERE status = 'approved'
                ORDER BY product_id
            ");
            $products = $productsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $totalProducts = count($products);
            $processedProducts = 0;
            $updatedProducts = 0;
            
            echo "<div class='info'>";
            echo "<p>총 <strong>{$totalProducts}개</strong> 상품의 통계를 재계산합니다.</p>";
            echo "</div>";
            
            require_once __DIR__ . '/includes/data/product-functions.php';
            
            foreach ($products as $product) {
                $productId = $product['product_id'];
                $productType = $product['product_type'];
                
                // 해당 상품의 모든 approved 리뷰 가져오기
                $reviewsStmt = $pdo->prepare("
                    SELECT 
                        rating,
                        kindness_rating,
                        speed_rating
                    FROM product_reviews
                    WHERE product_id = :product_id
                    AND product_type = :product_type
                    AND status = 'approved'
                ");
                $reviewsStmt->execute([
                    ':product_id' => $productId,
                    ':product_type' => $productType
                ]);
                $reviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($reviews)) {
                    continue;
                }
                
                // 통계 재계산
                $totalRatingSum = 0;
                $reviewCount = count($reviews);
                $kindnessSum = 0;
                $kindnessCount = 0;
                $speedSum = 0;
                $speedCount = 0;
                
                foreach ($reviews as $review) {
                    $totalRatingSum += (int)$review['rating'];
                    if ($review['kindness_rating'] !== null) {
                        $kindnessSum += (int)$review['kindness_rating'];
                        $kindnessCount++;
                    }
                    if ($review['speed_rating'] !== null) {
                        $speedSum += (int)$review['speed_rating'];
                        $speedCount++;
                    }
                }
                
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
                    ':total_rating_sum' => $totalRatingSum,
                    ':total_review_count' => $reviewCount,
                    ':kindness_rating_sum' => $kindnessSum,
                    ':kindness_review_count' => $kindnessCount,
                    ':speed_rating_sum' => $speedSum,
                    ':speed_review_count' => $speedCount,
                    ':total_rating_sum2' => $totalRatingSum,
                    ':total_review_count2' => $reviewCount,
                    ':kindness_rating_sum2' => $kindnessSum,
                    ':kindness_review_count2' => $kindnessCount,
                    ':speed_rating_sum2' => $speedSum,
                    ':speed_review_count2' => $speedCount
                ]);
                
                $processedProducts++;
                $updatedProducts++;
            }
            
            // 리뷰가 없는 상품의 통계는 0으로 설정
            $emptyStatsStmt = $pdo->query("
                SELECT DISTINCT prs.product_id
                FROM product_review_statistics prs
                LEFT JOIN product_reviews pr ON prs.product_id = pr.product_id AND pr.status = 'approved'
                WHERE pr.id IS NULL
            ");
            $emptyProducts = $emptyStatsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($emptyProducts as $emptyProduct) {
                $updateEmptyStmt = $pdo->prepare("
                    UPDATE product_review_statistics
                    SET total_rating_sum = 0,
                        total_review_count = 0,
                        kindness_rating_sum = 0,
                        kindness_review_count = 0,
                        speed_rating_sum = 0,
                        speed_review_count = 0,
                        updated_at = NOW()
                    WHERE product_id = :product_id
                ");
                $updateEmptyStmt->execute([':product_id' => $emptyProduct['product_id']]);
            }
            
            $pdo->commit();
            
            echo "<div class='success'>";
            echo "<h2>✓ 작업 완료</h2>";
            echo "<hr style='margin: 15px 0; border: none; border-top: 1px solid #ddd;'>";
            echo "<h3>작업 결과</h3>";
            echo "<ul>";
            echo "<li>삭제된 리뷰 제거: <strong>{$deletedRows}개</strong></li>";
            echo "<li>통계 재계산 완료: <strong>{$updatedProducts}개</strong> 상품</li>";
            echo "<li>평균 계산 방식: <strong>전체 리뷰 수에 대한 총합계의 평균</strong></li>";
            echo "</ul>";
            echo "</div>";
            
            // 최종 확인
            $finalStatsStmt = $pdo->query("
                SELECT 
                    COUNT(*) as total_reviews,
                    SUM(total_review_count) as total_count,
                    SUM(total_rating_sum) as total_sum
                FROM product_review_statistics
            ");
            $finalStats = $finalStatsStmt->fetch(PDO::FETCH_ASSOC);
            
            $finalAverage = $finalStats['total_count'] > 0 
                ? $finalStats['total_sum'] / $finalStats['total_count'] 
                : 0;
            
            echo "<div class='info'>";
            echo "<h3>최종 통계 확인</h3>";
            echo "<ul>";
            echo "<li>전체 리뷰 개수: <strong>{$finalStats['total_count']}개</strong></li>";
            echo "<li>전체 별점 합계: <strong>{$finalStats['total_sum']}</strong></li>";
            echo "<li>전체 평균 별점: <strong>" . number_format($finalAverage, 2) . "</strong> (합계 {$finalStats['total_sum']} ÷ 개수 {$finalStats['total_count']})</li>";
            echo "</ul>";
            echo "</div>";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "<div class='error'>";
            echo "<h2>✗ 작업 실패</h2>";
            echo "<p>오류: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
        }
    } else {
        // 사전 확인
        $deletedCountStmt = $pdo->query("SELECT COUNT(*) as count FROM product_reviews WHERE status = 'deleted'");
        $deletedCount = $deletedCountStmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $productsWithReviewsStmt = $pdo->query("
            SELECT COUNT(DISTINCT product_id) as count 
            FROM product_reviews 
            WHERE status = 'approved'
        ");
        $productsWithReviews = $productsWithReviewsStmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $totalReviewsStmt = $pdo->query("SELECT COUNT(*) as count FROM product_reviews WHERE status = 'approved'");
        $totalReviews = $totalReviewsStmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo "<div class='warning'>";
        echo "<h2>⚠️ 작업 전 확인</h2>";
        echo "<ul>";
        echo "<li>삭제된 리뷰(status='deleted'): <strong>{$deletedCount}개</strong> → 완전 제거됩니다</li>";
        echo "<li>리뷰가 있는 상품: <strong>{$productsWithReviews}개</strong> → 통계가 재계산됩니다</li>";
        echo "<li>전체 approved 리뷰: <strong>{$totalReviews}개</strong></li>";
        echo "</ul>";
        echo "<p><strong>주의:</strong> 이 작업은 되돌릴 수 없습니다!</p>";
        echo "</div>";
        
        echo "<div class='info'>";
        echo "<h3>작업 내용</h3>";
        echo "<ol>";
        echo "<li>status='deleted'인 모든 리뷰를 데이터베이스에서 완전히 제거</li>";
        echo "<li>모든 상품의 통계를 실제 approved 리뷰 데이터로 재계산</li>";
        echo "<li>평균 = 전체 리뷰 수에 대한 총합계의 평균</li>";
        echo "</ol>";
        echo "</div>";
        
        echo "<form method='POST' onsubmit='return confirm(\"정말로 삭제된 리뷰를 제거하고 통계를 재계산하시겠습니까?\\n\\n이 작업은 되돌릴 수 없습니다!\");'>";
        echo "<input type='hidden' name='execute' value='yes'>";
        echo "<button type='submit' class='btn btn-danger'>작업 실행</button>";
        echo "</form>";
        
        // 모든 리뷰 삭제 옵션
        $allReviewsCountStmt = $pdo->query("SELECT COUNT(*) as count FROM product_reviews");
        $allReviewsCount = $allReviewsCountStmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($allReviewsCount > 0) {
            echo "<hr style='margin: 30px 0; border: none; border-top: 2px solid #ddd;'>";
            echo "<div class='warning'>";
            echo "<h2>⚠️ 모든 리뷰 삭제</h2>";
            echo "<p>전체 리뷰 개수: <strong>{$allReviewsCount}개</strong></p>";
            echo "<p><strong>주의:</strong> 모든 리뷰와 통계가 완전히 삭제됩니다. 이 작업은 되돌릴 수 없습니다!</p>";
            echo "</div>";
            
            echo "<form method='POST' onsubmit='return confirm(\"정말로 모든 리뷰를 삭제하시겠습니까?\\n\\n전체 {$allReviewsCount}개의 리뷰가 완전히 삭제됩니다.\\n이 작업은 되돌릴 수 없습니다!\");'>";
            echo "<input type='hidden' name='delete_all' value='yes'>";
            echo "<button type='submit' class='btn btn-danger'>모든 리뷰 삭제</button>";
            echo "</form>";
        }
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>";
    echo "<h2>✗ 오류 발생</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</div></body></html>";
?>
