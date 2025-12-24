<?php
/**
 * 알뜰폰(MVNO) 및 통신사폰(MNO) 리뷰 삭제 스크립트
 * 브라우저에서 실행: http://localhost/MVNO/delete-mvno-mno-reviews.php
 * 주의: 이 스크립트는 알뜰폰과 통신사폰 리뷰를 영구적으로 삭제합니다!
 */

require_once __DIR__ . '/includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>알뜰폰/통신사폰 리뷰 삭제</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .warning { background: #fff3cd; border: 2px solid #ffc107; padding: 15px; border-radius: 8px; margin: 20px 0; }
        .success { background: #d4edda; border: 2px solid #28a745; padding: 15px; border-radius: 8px; margin: 20px 0; color: #155724; }
        .error { background: #f8d7da; border: 2px solid #dc3545; padding: 15px; border-radius: 8px; margin: 20px 0; color: #721c24; }
        .info { background: #d1ecf1; border: 2px solid #17a2b8; padding: 15px; border-radius: 8px; margin: 20px 0; color: #0c5460; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        .btn { 
            display: inline-block; 
            padding: 12px 24px; 
            margin: 10px 5px; 
            background: #dc3545; 
            color: white; 
            text-decoration: none; 
            border-radius: 4px; 
            cursor: pointer;
            border: none;
            font-size: 16px;
        }
        .btn:hover { background: #c82333; }
        .btn:disabled { background: #ccc; cursor: not-allowed; }
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
    
    echo "<h1>알뜰폰(MVNO) 및 통신사폰(MNO) 리뷰 삭제</h1>";
    echo "<p class='success'>✓ 데이터베이스 연결 성공</p>";
    
    // 삭제 전 리뷰 개수 확인 (MVNO, MNO만)
    $countStmt = $pdo->query("SELECT COUNT(*) as total FROM product_reviews WHERE product_type IN ('mvno', 'mno')");
    $totalCount = $countStmt->fetch()['total'];
    
    // 타입별 개수 확인
    $mvnoCount = 0;
    $mnoCount = 0;
    try {
        $typeStmt = $pdo->query("
            SELECT 
                product_type,
                COUNT(*) as count
            FROM product_reviews 
            WHERE product_type IN ('mvno', 'mno')
            GROUP BY product_type
        ");
        $typeData = $typeStmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($typeData as $row) {
            if ($row['product_type'] === 'mvno') {
                $mvnoCount = $row['count'];
            } elseif ($row['product_type'] === 'mno') {
                $mnoCount = $row['count'];
            }
        }
    } catch (PDOException $e) {
        // 오류 무시
    }
    
    // 통계 데이터 확인 (MVNO, MNO 상품만)
    $statsCount = 0;
    $statsSum = 0;
    try {
        $statsStmt = $pdo->query("
            SELECT COUNT(*) as total, COALESCE(SUM(total_review_count), 0) as sum 
            FROM product_review_statistics prs
            INNER JOIN products p ON prs.product_id = p.id
            WHERE p.product_type IN ('mvno', 'mno')
        ");
        $statsData = $statsStmt->fetch();
        $statsCount = $statsData['total'];
        $statsSum = $statsData['sum'];
    } catch (PDOException $e) {
        // 테이블이 없을 수 있음
    }
    
    // 별점 통계 확인 (MVNO, MNO만)
    $totalRatingSum = 0;
    $kindnessRatingSum = 0;
    $speedRatingSum = 0;
    try {
        $ratingStmt = $pdo->query("
            SELECT 
                COALESCE(SUM(prs.total_rating_sum), 0) as total_rating,
                COALESCE(SUM(prs.kindness_rating_sum), 0) as kindness_rating,
                COALESCE(SUM(prs.speed_rating_sum), 0) as speed_rating
            FROM product_review_statistics prs
            INNER JOIN products p ON prs.product_id = p.id
            WHERE p.product_type IN ('mvno', 'mno')
        ");
        $ratingData = $ratingStmt->fetch();
        $totalRatingSum = $ratingData['total_rating'] ?? 0;
        $kindnessRatingSum = $ratingData['kindness_rating'] ?? 0;
        $speedRatingSum = $ratingData['speed_rating'] ?? 0;
    } catch (PDOException $e) {
        // 테이블이 없을 수 있음
    }
    
    echo "<div class='info'>";
    echo "<h2>현재 상태</h2>";
    echo "<p>알뜰폰(MVNO) 리뷰 개수: <strong>{$mvnoCount}개</strong></p>";
    echo "<p>통신사폰(MNO) 리뷰 개수: <strong>{$mnoCount}개</strong></p>";
    echo "<p>총 리뷰 개수 (MVNO + MNO): <strong>{$totalCount}개</strong></p>";
    echo "<p>통계 데이터 개수 (MVNO + MNO): <strong>{$statsCount}개</strong></p>";
    echo "<p>통계에 등록된 총 리뷰 수: <strong>{$statsSum}개</strong></p>";
    echo "<hr style='margin: 15px 0; border: none; border-top: 1px solid #ddd;'>";
    echo "<h3>별점 통계 (MVNO + MNO)</h3>";
    echo "<p>기본 별점 합계: <strong>" . number_format($totalRatingSum, 2) . "</strong></p>";
    echo "<p>친절해요 별점 합계: <strong>" . number_format($kindnessRatingSum, 2) . "</strong></p>";
    echo "<p>설치빨라요 별점 합계: <strong>" . number_format($speedRatingSum, 2) . "</strong></p>";
    echo "</div>";
    
    // 삭제 확인
    if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'yes') {
        echo "<div class='warning'>";
        echo "<h2>⚠️ 리뷰 삭제 진행 중...</h2>";
        echo "</div>";
        
        $pdo->beginTransaction();
        
        try {
            // 1. MVNO, MNO 상품의 통계 데이터 삭제
            $deletedStats = 0;
            try {
                $deleteStatsStmt = $pdo->prepare("
                    DELETE prs FROM product_review_statistics prs
                    INNER JOIN products p ON prs.product_id = p.id
                    WHERE p.product_type IN ('mvno', 'mno')
                ");
                $deleteStatsStmt->execute();
                $deletedStats = $deleteStatsStmt->rowCount();
                echo "<p>✓ 통계 테이블 초기화 완료: {$deletedStats}개 삭제</p>";
            } catch (PDOException $e) {
                error_log("product_review_statistics delete error: " . $e->getMessage());
                echo "<p>⚠ 통계 테이블 삭제 중 오류: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
            
            // 2. MVNO, MNO 리뷰 삭제
            $deleteStmt = $pdo->prepare("DELETE FROM product_reviews WHERE product_type IN ('mvno', 'mno')");
            $deleteStmt->execute();
            $deletedReviews = $deleteStmt->rowCount();
            echo "<p>✓ 리뷰 테이블 삭제 완료: {$deletedReviews}개 삭제</p>";
            
            // 3. MVNO, MNO 상품의 review_count 초기화
            try {
                $resetCountStmt = $pdo->prepare("UPDATE products SET review_count = 0 WHERE product_type IN ('mvno', 'mno')");
                $resetCountStmt->execute();
                $resetCount = $resetCountStmt->rowCount();
                echo "<p>✓ 상품 테이블의 review_count 초기화 완료: {$resetCount}개 상품</p>";
            } catch (PDOException $e) {
                error_log("Failed to reset review_count: " . $e->getMessage());
                echo "<p>⚠ review_count 초기화 중 오류: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
            
            $pdo->commit();
            
            echo "<div class='success'>";
            echo "<h2>✓ 삭제 완료</h2>";
            echo "<p>삭제된 리뷰: <strong>{$deletedReviews}개</strong></p>";
            echo "<p>삭제된 통계: <strong>{$deletedStats}개</strong></p>";
            echo "<p>삭제된 별점 데이터: <strong>모든 별점 통계 삭제 완료</strong></p>";
            echo "<p>알뜰폰(MVNO)과 통신사폰(MNO)의 모든 리뷰와 별점 데이터가 성공적으로 삭제되었습니다.</p>";
            echo "</div>";
            
            // 삭제 후 상태 확인
            $countStmt = $pdo->query("SELECT COUNT(*) as total FROM product_reviews WHERE product_type IN ('mvno', 'mno')");
            $remainingCount = $countStmt->fetch()['total'];
            
            $remainingStats = 0;
            $remainingStatsSum = 0;
            try {
                $statsStmt = $pdo->query("
                    SELECT COUNT(*) as total, COALESCE(SUM(total_review_count), 0) as sum 
                    FROM product_review_statistics prs
                    INNER JOIN products p ON prs.product_id = p.id
                    WHERE p.product_type IN ('mvno', 'mno')
                ");
                $statsData = $statsStmt->fetch();
                $remainingStats = $statsData['total'];
                $remainingStatsSum = $statsData['sum'];
            } catch (PDOException $e) {
                // 테이블이 없을 수 있음
            }
            
            $remainingProductCount = 0;
            try {
                $productCountStmt = $pdo->query("SELECT COALESCE(SUM(review_count), 0) as total FROM products WHERE product_type IN ('mvno', 'mno')");
                $remainingProductCount = $productCountStmt->fetch()['total'];
            } catch (PDOException $e) {
                // 오류 무시
            }
            
            // 삭제 후 별점 통계 확인
            $remainingTotalRating = 0;
            $remainingKindnessRating = 0;
            $remainingSpeedRating = 0;
            try {
                $remainingRatingStmt = $pdo->query("
                    SELECT 
                        COALESCE(SUM(prs.total_rating_sum), 0) as total_rating,
                        COALESCE(SUM(prs.kindness_rating_sum), 0) as kindness_rating,
                        COALESCE(SUM(prs.speed_rating_sum), 0) as speed_rating
                    FROM product_review_statistics prs
                    INNER JOIN products p ON prs.product_id = p.id
                    WHERE p.product_type IN ('mvno', 'mno')
                ");
                $remainingRatingData = $remainingRatingStmt->fetch();
                $remainingTotalRating = $remainingRatingData['total_rating'] ?? 0;
                $remainingKindnessRating = $remainingRatingData['kindness_rating'] ?? 0;
                $remainingSpeedRating = $remainingRatingData['speed_rating'] ?? 0;
            } catch (PDOException $e) {
                // 테이블이 없을 수 있음
            }
            
            echo "<div class='info'>";
            echo "<h2>삭제 후 상태</h2>";
            echo "<p>남은 리뷰 개수 (MVNO + MNO): <strong>{$remainingCount}개</strong></p>";
            echo "<p>남은 통계 데이터 개수: <strong>{$remainingStats}개</strong></p>";
            echo "<p>남은 통계 리뷰 수 합계: <strong>{$remainingStatsSum}개</strong></p>";
            echo "<p>상품 테이블의 review_count 합계 (MVNO + MNO): <strong>{$remainingProductCount}개</strong></p>";
            echo "<hr style='margin: 15px 0; border: none; border-top: 1px solid #ddd;'>";
            echo "<h3>남은 별점 통계</h3>";
            echo "<p>기본 별점 합계: <strong>" . number_format($remainingTotalRating, 2) . "</strong></p>";
            echo "<p>친절해요 별점 합계: <strong>" . number_format($remainingKindnessRating, 2) . "</strong></p>";
            echo "<p>설치빨라요 별점 합계: <strong>" . number_format($remainingSpeedRating, 2) . "</strong></p>";
            
            if ($remainingCount == 0 && $remainingStats == 0 && $remainingProductCount == 0 && 
                $remainingTotalRating == 0 && $remainingKindnessRating == 0 && $remainingSpeedRating == 0) {
                echo "<p style='color: green; font-weight: bold; margin-top: 10px;'>✅ 알뜰폰(MVNO)과 통신사폰(MNO)의 모든 리뷰와 별점 데이터가 완전히 삭제되었습니다!</p>";
            } else {
                echo "<p style='color: orange; font-weight: bold; margin-top: 10px;'>⚠️ 일부 데이터가 남아있습니다. 확인이 필요합니다.</p>";
            }
            echo "</div>";
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            echo "<div class='error'>";
            echo "<h2>✗ 삭제 실패</h2>";
            echo "<p>오류: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
        }
        
    } else {
        // 삭제 확인 폼
        if ($totalCount > 0) {
            echo "<div class='warning'>";
            echo "<h2>⚠️ 경고</h2>";
            echo "<p>이 작업은 다음을 <strong>영구적으로 삭제</strong>합니다:</p>";
            echo "<ul style='margin: 10px 0; padding-left: 20px;'>";
            echo "<li>모든 알뜰폰(MVNO) 리뷰</li>";
            echo "<li>모든 통신사폰(MNO) 리뷰</li>";
            echo "<li>모든 별점 데이터 (기본 별점, 친절해요, 설치빨라요)</li>";
            echo "<li>모든 리뷰 통계 데이터</li>";
            echo "<li>상품별 리뷰 카운트</li>";
            echo "</ul>";
            echo "<p style='color: #dc3545; font-weight: bold;'>삭제된 데이터는 복구할 수 없습니다.</p>";
            echo "<p>계속하시겠습니까?</p>";
            echo "</div>";
            
            echo "<form method='POST' onsubmit='return confirm(\"정말로 알뜰폰(MVNO)과 통신사폰(MNO)의 모든 리뷰와 별점 데이터를 삭제하시겠습니까?\\n\\n- 모든 알뜰폰 리뷰\\n- 모든 통신사폰 리뷰\\n- 모든 별점 통계\\n- 모든 리뷰 카운트\\n\\n이 작업은 되돌릴 수 없습니다.\");'>";
            echo "<input type='hidden' name='confirm_delete' value='yes'>";
            echo "<button type='submit' class='btn'>알뜰폰/통신사폰 리뷰 및 별점 삭제</button>";
            echo "</form>";
        } else {
            echo "<div class='info'>";
            echo "<p>삭제할 알뜰폰(MVNO) 또는 통신사폰(MNO) 리뷰가 없습니다.</p>";
            echo "</div>";
        }
    }
    
    // 리뷰 목록 표시 (삭제 전에만)
    if (!isset($_POST['confirm_delete']) && $totalCount > 0) {
        echo "<h2>리뷰 목록 (최근 10개)</h2>";
        $stmt = $pdo->query("
            SELECT 
                id,
                product_id,
                user_id,
                product_type,
                rating,
                kindness_rating,
                speed_rating,
                status,
                created_at
            FROM product_reviews 
            WHERE product_type IN ('mvno', 'mno')
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($reviews)) {
            echo "<table>";
            echo "<tr>
                <th>ID</th>
                <th>상품 ID</th>
                <th>사용자 ID</th>
                <th>타입</th>
                <th>별점</th>
                <th>친절해요</th>
                <th>설치빨라요</th>
                <th>상태</th>
                <th>작성일시</th>
            </tr>";
            
            foreach ($reviews as $review) {
                $typeName = $review['product_type'] === 'mvno' ? '알뜰폰' : '통신사폰';
                echo "<tr>";
                echo "<td>{$review['id']}</td>";
                echo "<td>{$review['product_id']}</td>";
                echo "<td>{$review['user_id']}</td>";
                echo "<td>{$typeName}</td>";
                echo "<td>{$review['rating']}</td>";
                echo "<td>" . ($review['kindness_rating'] ?? '-') . "</td>";
                echo "<td>" . ($review['speed_rating'] ?? '-') . "</td>";
                echo "<td>{$review['status']}</td>";
                echo "<td>{$review['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
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

