<?php
/**
 * 리뷰 통계 재계산 스크립트
 * 브라우저에서 실행: http://localhost/MVNO/fix-review-statistics.php?product_id=24
 * 특정 상품의 리뷰 통계를 실제 리뷰 데이터로 재계산합니다.
 */

require_once __DIR__ . '/includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

$productId = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
$productType = isset($_GET['product_type']) ? trim($_GET['product_type']) : 'mvno';

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>리뷰 통계 재계산</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
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
            background: #10b981; 
            color: white; 
            text-decoration: none; 
            border-radius: 4px; 
            cursor: pointer;
            border: none;
            font-size: 16px;
        }
        .btn:hover { background: #059669; }
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
    
    echo "<h1>리뷰 통계 재계산</h1>";
    echo "<p class='success'>✓ 데이터베이스 연결 성공</p>";
    
    if ($productId > 0) {
        echo "<div class='info'>";
        echo "<h2>상품 ID: {$productId} (타입: {$productType})</h2>";
        echo "</div>";
        
        // 실제 리뷰 데이터 확인
        $reviewStmt = $pdo->prepare("
            SELECT 
                id,
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
        $reviewStmt->execute([
            ':product_id' => $productId,
            ':product_type' => $productType
        ]);
        $reviews = $reviewStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<div class='info'>";
        echo "<h3>실제 리뷰 데이터 (총 " . count($reviews) . "개)</h3>";
        if (!empty($reviews)) {
            echo "<table>";
            echo "<tr>
                <th>ID</th>
                <th>기본 별점</th>
                <th>친절해요</th>
                <th>설치빨라요</th>
                <th>상태</th>
                <th>작성일시</th>
            </tr>";
            
            $totalRatingSum = 0;
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
                
                echo "<tr>";
                echo "<td>{$review['id']}</td>";
                echo "<td>{$review['rating']}</td>";
                echo "<td>" . ($review['kindness_rating'] ?? '-') . "</td>";
                echo "<td>" . ($review['speed_rating'] ?? '-') . "</td>";
                echo "<td>{$review['status']}</td>";
                echo "<td>{$review['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            $reviewCount = count($reviews);
            // 평균 계산: 전체 리뷰 수에 대한 총합계의 평균
            $averageRating = $reviewCount > 0 ? $totalRatingSum / $reviewCount : 0;
            $kindnessAverage = $kindnessCount > 0 ? $kindnessSum / $kindnessCount : 0;
            $speedAverage = $speedCount > 0 ? $speedSum / $speedCount : 0;
            
            echo "<h3>계산된 통계 (실제 리뷰 데이터 기반)</h3>";
            echo "<p><strong>기본 별점:</strong></p>";
            echo "<ul>";
            echo "<li>합계: <strong>{$totalRatingSum}</strong></li>";
            echo "<li>리뷰 개수: <strong>{$reviewCount}</strong></li>";
            echo "<li>평균: <strong>" . number_format($averageRating, 2) . "</strong> (합계 {$totalRatingSum} ÷ 개수 {$reviewCount})</li>";
            echo "</ul>";
            
            if ($kindnessCount > 0) {
                echo "<p><strong>친절해요:</strong></p>";
                echo "<ul>";
                echo "<li>합계: <strong>{$kindnessSum}</strong></li>";
                echo "<li>리뷰 개수: <strong>{$kindnessCount}</strong></li>";
                echo "<li>평균: <strong>" . number_format($kindnessAverage, 2) . "</strong> (합계 {$kindnessSum} ÷ 개수 {$kindnessCount})</li>";
                echo "</ul>";
            }
            
            if ($speedCount > 0) {
                echo "<p><strong>설치빨라요:</strong></p>";
                echo "<ul>";
                echo "<li>합계: <strong>{$speedSum}</strong></li>";
                echo "<li>리뷰 개수: <strong>{$speedCount}</strong></li>";
                echo "<li>평균: <strong>" . number_format($speedAverage, 2) . "</strong> (합계 {$speedSum} ÷ 개수 {$speedCount})</li>";
                echo "</ul>";
            }
        } else {
            echo "<p>리뷰가 없습니다.</p>";
        }
        echo "</div>";
        
        // 현재 통계 테이블 데이터 확인
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
        
        echo "<div class='info'>";
        echo "<h3>현재 통계 테이블 데이터</h3>";
        if ($currentStats) {
            // 평균 계산: 전체 리뷰 수에 대한 총합계의 평균
            $currentAverage = $currentStats['total_review_count'] > 0 
                ? $currentStats['total_rating_sum'] / $currentStats['total_review_count'] 
                : 0;
            $currentKindnessAverage = $currentStats['kindness_review_count'] > 0 
                ? $currentStats['kindness_rating_sum'] / $currentStats['kindness_review_count'] 
                : 0;
            $currentSpeedAverage = $currentStats['speed_review_count'] > 0 
                ? $currentStats['speed_rating_sum'] / $currentStats['speed_review_count'] 
                : 0;
            
            echo "<p><strong>기본 별점:</strong></p>";
            echo "<ul>";
            echo "<li>합계: <strong>{$currentStats['total_rating_sum']}</strong></li>";
            echo "<li>리뷰 개수: <strong>{$currentStats['total_review_count']}</strong></li>";
            echo "<li>평균: <strong>" . number_format($currentAverage, 2) . "</strong> (합계 {$currentStats['total_rating_sum']} ÷ 개수 {$currentStats['total_review_count']})</li>";
            echo "</ul>";
            
            if ($currentStats['kindness_review_count'] > 0) {
                echo "<p><strong>친절해요:</strong></p>";
                echo "<ul>";
                echo "<li>합계: <strong>{$currentStats['kindness_rating_sum']}</strong></li>";
                echo "<li>리뷰 개수: <strong>{$currentStats['kindness_review_count']}</strong></li>";
                echo "<li>평균: <strong>" . number_format($currentKindnessAverage, 2) . "</strong> (합계 {$currentStats['kindness_rating_sum']} ÷ 개수 {$currentStats['kindness_review_count']})</li>";
                echo "</ul>";
            }
            
            if ($currentStats['speed_review_count'] > 0) {
                echo "<p><strong>설치빨라요:</strong></p>";
                echo "<ul>";
                echo "<li>합계: <strong>{$currentStats['speed_rating_sum']}</strong></li>";
                echo "<li>리뷰 개수: <strong>{$currentStats['speed_review_count']}</strong></li>";
                echo "<li>평균: <strong>" . number_format($currentSpeedAverage, 2) . "</strong> (합계 {$currentStats['speed_rating_sum']} ÷ 개수 {$currentStats['speed_review_count']})</li>";
                echo "</ul>";
            }
            
            // 불일치 확인
            $mismatch = false;
            $mismatchMessages = [];
            if ($currentStats['total_review_count'] != $reviewCount) {
                $mismatch = true;
                $mismatchMessages[] = "리뷰 개수 불일치: 통계 테이블({$currentStats['total_review_count']}) vs 실제({$reviewCount})";
            }
            if (abs($currentStats['total_rating_sum'] - $totalRatingSum) > 0.01) {
                $mismatch = true;
                $mismatchMessages[] = "기본 별점 합계 불일치: 통계 테이블({$currentStats['total_rating_sum']}) vs 실제({$totalRatingSum})";
            }
            
            if ($mismatch) {
                echo "<div style='background: #fff3cd; border: 2px solid #ffc107; padding: 15px; border-radius: 8px; margin: 15px 0; color: #856404;'>";
                echo "<h4>⚠️ 데이터 불일치 발견</h4>";
                foreach ($mismatchMessages as $msg) {
                    echo "<p>• {$msg}</p>";
                }
                echo "<p><strong>통계 재계산이 필요합니다!</strong></p>";
                echo "</div>";
            } else {
                echo "<div style='background: #d4edda; border: 2px solid #28a745; padding: 15px; border-radius: 8px; margin: 15px 0; color: #155724;'>";
                echo "<p>✓ 통계 테이블 데이터가 실제 리뷰 데이터와 일치합니다.</p>";
                echo "</div>";
            }
        } else {
            echo "<p>통계 테이블에 데이터가 없습니다.</p>";
        }
        echo "</div>";
        
        // 통계 재계산 실행
        if (isset($_POST['recalculate']) && $_POST['recalculate'] === 'yes') {
            echo "<div class='info'>";
            echo "<h2>통계 재계산 진행 중...</h2>";
            echo "</div>";
            
            $pdo->beginTransaction();
            
            try {
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
                
                // 실제 리뷰 데이터를 기반으로 통계 재계산
                // 평균 = 전체 리뷰 수에 대한 총합계의 평균
                $updateStmt->execute([
                    ':product_id' => $productId,
                    ':total_rating_sum' => $totalRatingSum,  // 실제 리뷰의 rating 합계
                    ':total_review_count' => $reviewCount,    // 실제 리뷰 개수
                    ':kindness_rating_sum' => $kindnessSum,   // 실제 리뷰의 kindness_rating 합계
                    ':kindness_review_count' => $kindnessCount, // kindness_rating이 있는 리뷰 개수
                    ':speed_rating_sum' => $speedSum,         // 실제 리뷰의 speed_rating 합계
                    ':speed_review_count' => $speedCount,      // speed_rating이 있는 리뷰 개수
                    ':total_rating_sum2' => $totalRatingSum,
                    ':total_review_count2' => $reviewCount,
                    ':kindness_rating_sum2' => $kindnessSum,
                    ':kindness_review_count2' => $kindnessCount,
                    ':speed_rating_sum2' => $speedSum,
                    ':speed_review_count2' => $speedCount
                ]);
                
                $pdo->commit();
                
                // 재계산 후 결과 확인
                $verifyStmt = $pdo->prepare("
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
                $verifyStmt->execute([':product_id' => $productId]);
                $verifiedStats = $verifyStmt->fetch(PDO::FETCH_ASSOC);
                
                $verifiedAverage = $verifiedStats['total_review_count'] > 0 
                    ? $verifiedStats['total_rating_sum'] / $verifiedStats['total_review_count'] 
                    : 0;
                
                echo "<div class='success'>";
                echo "<h2>✓ 통계 재계산 완료</h2>";
                echo "<p>통계 테이블이 실제 리뷰 데이터로 업데이트되었습니다.</p>";
                echo "<hr style='margin: 15px 0; border: none; border-top: 1px solid #ddd;'>";
                echo "<h3>재계산된 통계</h3>";
                echo "<p>기본 별점 합계: <strong>{$verifiedStats['total_rating_sum']}</strong></p>";
                echo "<p>리뷰 개수: <strong>{$verifiedStats['total_review_count']}</strong></p>";
                echo "<p>기본 별점 평균: <strong>" . number_format($verifiedAverage, 2) . "</strong> (합계 {$verifiedStats['total_rating_sum']} ÷ 개수 {$verifiedStats['total_review_count']})</p>";
                echo "<p><a href='?product_id={$productId}&product_type={$productType}' class='btn'>새로고침</a></p>";
                echo "</div>";
            } catch (Exception $e) {
                $pdo->rollBack();
                echo "<div class='error'>";
                echo "<h2>✗ 재계산 실패</h2>";
                echo "<p>오류: " . htmlspecialchars($e->getMessage()) . "</p>";
                echo "</div>";
            }
        } else {
            // 재계산 버튼 표시
            if (!empty($reviews)) {
                echo "<form method='POST' onsubmit='return confirm(\"정말로 통계를 재계산하시겠습니까?\");'>";
                echo "<input type='hidden' name='recalculate' value='yes'>";
                echo "<button type='submit' class='btn'>통계 재계산</button>";
                echo "</form>";
            }
        }
    } else {
        echo "<div class='info'>";
        echo "<p>URL에 product_id 파라미터를 추가하세요.</p>";
        echo "<p>예: ?product_id=24&product_type=mvno</p>";
        echo "</div>";
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>";
    echo "<h2>✗ 오류 발생</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</div></body></html>";
?>








