<?php
/**
 * 모든 리뷰 삭제 스크립트
 * 브라우저에서 실행: http://localhost/MVNO/delete-all-reviews.php
 * 주의: 이 스크립트는 모든 리뷰를 영구적으로 삭제합니다!
 */

require_once __DIR__ . '/includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>모든 리뷰 삭제</title>
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
    
    echo "<h1>모든 리뷰 삭제</h1>";
    echo "<p class='success'>✓ 데이터베이스 연결 성공</p>";
    
    // 삭제 전 리뷰 개수 확인
    $countStmt = $pdo->query("SELECT COUNT(*) as total FROM product_reviews");
    $totalCount = $countStmt->fetch()['total'];
    
    // 통계 데이터 개수 확인
    $statsCount = 0;
    $statsSum = 0;
    try {
        $statsStmt = $pdo->query("SELECT COUNT(*) as total, COALESCE(SUM(total_review_count), 0) as sum FROM product_review_statistics");
        $statsData = $statsStmt->fetch();
        $statsCount = $statsData['total'];
        $statsSum = $statsData['sum'];
    } catch (PDOException $e) {
        // 테이블이 없을 수 있음
    }
    
    // 상품별 review_count 합계 확인
    $productReviewCount = 0;
    try {
        $productCountStmt = $pdo->query("SELECT COALESCE(SUM(review_count), 0) as total FROM products");
        $productReviewCount = $productCountStmt->fetch()['total'];
    } catch (PDOException $e) {
        // 오류 무시
    }
    
    echo "<div class='info'>";
    echo "<h2>현재 상태</h2>";
    echo "<p>총 리뷰 개수 (product_reviews): <strong>{$totalCount}개</strong></p>";
    echo "<p>통계 데이터 개수 (product_review_statistics): <strong>{$statsCount}개</strong></p>";
    echo "<p>통계에 등록된 총 리뷰 수: <strong>{$statsSum}개</strong></p>";
    echo "<p>상품 테이블의 review_count 합계: <strong>{$productReviewCount}개</strong></p>";
    echo "</div>";
    
    // 삭제 확인
    if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'yes') {
        echo "<div class='warning'>";
        echo "<h2>⚠️ 리뷰 삭제 진행 중...</h2>";
        echo "</div>";
        
        $pdo->beginTransaction();
        
        try {
            // 1. product_review_statistics 테이블 초기화 (통계 데이터 삭제)
            $deletedStats = 0;
            try {
                $deleteStatsStmt = $pdo->prepare("DELETE FROM product_review_statistics");
                $deleteStatsStmt->execute();
                $deletedStats = $deleteStatsStmt->rowCount();
                echo "<p>✓ 통계 테이블 초기화 완료: {$deletedStats}개 삭제</p>";
            } catch (PDOException $e) {
                // 테이블이 없을 수 있음
                error_log("product_review_statistics table not found or error: " . $e->getMessage());
                echo "<p>⚠ 통계 테이블이 없거나 오류 발생: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
            
            // 2. product_reviews 테이블의 모든 리뷰 삭제
            $deleteStmt = $pdo->prepare("DELETE FROM product_reviews");
            $deleteStmt->execute();
            $deletedReviews = $deleteStmt->rowCount();
            echo "<p>✓ 리뷰 테이블 삭제 완료: {$deletedReviews}개 삭제</p>";
            
            // 3. products 테이블의 review_count 초기화 (트리거로 자동 업데이트되지만 확실하게)
            try {
                $resetCountStmt = $pdo->prepare("UPDATE products SET review_count = 0");
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
            echo "<p>모든 리뷰가 성공적으로 삭제되었습니다.</p>";
            echo "</div>";
            
            // 삭제 후 상태 확인
            $countStmt = $pdo->query("SELECT COUNT(*) as total FROM product_reviews");
            $remainingCount = $countStmt->fetch()['total'];
            
            $remainingStats = 0;
            $remainingStatsSum = 0;
            try {
                $statsStmt = $pdo->query("SELECT COUNT(*) as total, COALESCE(SUM(total_review_count), 0) as sum FROM product_review_statistics");
                $statsData = $statsStmt->fetch();
                $remainingStats = $statsData['total'];
                $remainingStatsSum = $statsData['sum'];
            } catch (PDOException $e) {
                // 테이블이 없을 수 있음
            }
            
            $remainingProductCount = 0;
            try {
                $productCountStmt = $pdo->query("SELECT COALESCE(SUM(review_count), 0) as total FROM products");
                $remainingProductCount = $productCountStmt->fetch()['total'];
            } catch (PDOException $e) {
                // 오류 무시
            }
            
            echo "<div class='info'>";
            echo "<h2>삭제 후 상태</h2>";
            echo "<p>남은 리뷰 개수 (product_reviews): <strong>{$remainingCount}개</strong></p>";
            echo "<p>남은 통계 데이터 개수 (product_review_statistics): <strong>{$remainingStats}개</strong></p>";
            echo "<p>남은 통계 리뷰 수 합계: <strong>{$remainingStatsSum}개</strong></p>";
            echo "<p>상품 테이블의 review_count 합계: <strong>{$remainingProductCount}개</strong></p>";
            
            if ($remainingCount == 0 && $remainingStats == 0 && $remainingProductCount == 0) {
                echo "<p style='color: green; font-weight: bold; margin-top: 10px;'>✅ 모든 리뷰와 평점이 완전히 삭제되었습니다!</p>";
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
            echo "<p>이 작업은 <strong>모든 리뷰를 영구적으로 삭제</strong>합니다.</p>";
            echo "<p>삭제된 데이터는 복구할 수 없습니다.</p>";
            echo "<p>계속하시겠습니까?</p>";
            echo "</div>";
            
            echo "<form method='POST' onsubmit='return confirm(\"정말로 모든 리뷰를 삭제하시겠습니까? 이 작업은 되돌릴 수 없습니다.\");'>";
            echo "<input type='hidden' name='confirm_delete' value='yes'>";
            echo "<button type='submit' class='btn'>모든 리뷰 삭제</button>";
            echo "</form>";
        } else {
            echo "<div class='info'>";
            echo "<p>삭제할 리뷰가 없습니다.</p>";
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
                echo "<tr>";
                echo "<td>{$review['id']}</td>";
                echo "<td>{$review['product_id']}</td>";
                echo "<td>{$review['user_id']}</td>";
                echo "<td>{$review['product_type']}</td>";
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


