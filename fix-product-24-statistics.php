<?php
/**
 * 상품 ID 24 통계 수정 (하이브리드 방식)
 */

require_once __DIR__ . '/includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

$pdo = getDBConnection();
if (!$pdo) {
    die('데이터베이스 연결 실패');
}

$productId = 24;
$productType = 'mvno';

echo "<h1>상품 ID $productId 통계 수정</h1>";

try {
    // 1. 실제 리뷰 데이터에서 통계 계산
    echo "<h2>1. 실제 리뷰 데이터에서 통계 계산</h2>";
    
    $calcStmt = $pdo->prepare("
        SELECT 
            SUM(rating) as total_rating_sum,
            COUNT(*) as total_review_count,
            SUM(kindness_rating) as kindness_rating_sum,
            COUNT(kindness_rating) as kindness_review_count,
            SUM(speed_rating) as speed_rating_sum,
            COUNT(speed_rating) as speed_review_count
        FROM product_reviews
        WHERE product_id = :product_id
        AND product_type = :product_type
        AND status = 'approved'
    ");
    $calcStmt->execute([':product_id' => $productId, ':product_type' => $productType]);
    $calculated = $calcStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$calculated || $calculated['total_review_count'] == 0) {
        echo "<p style='color: red;'>승인된 리뷰가 없습니다.</p>";
        exit;
    }
    
    $totalSum = (float)($calculated['total_rating_sum'] ?? 0);
    $totalCount = (int)$calculated['total_review_count'];
    $kindnessSum = (float)($calculated['kindness_rating_sum'] ?? 0);
    $kindnessCount = (int)$calculated['kindness_review_count'];
    $speedSum = (float)($calculated['speed_rating_sum'] ?? 0);
    $speedCount = (int)$calculated['speed_review_count'];
    
    echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
    echo "<h3>계산된 통계:</h3>";
    echo "<ul>";
    echo "<li><strong>총 평균:</strong> $totalSum / $totalCount = " . number_format($totalSum / $totalCount, 2) . "</li>";
    if ($kindnessCount > 0) {
        echo "<li><strong>친절해요:</strong> $kindnessSum / $kindnessCount = " . number_format($kindnessSum / $kindnessCount, 2) . "</li>";
    }
    if ($speedCount > 0) {
        echo "<li><strong>개통빨라요:</strong> $speedSum / $speedCount = " . number_format($speedSum / $speedCount, 2) . "</li>";
    }
    echo "</ul>";
    echo "</div>";
    
    // 2. 통계 테이블 업데이트
    if (isset($_GET['fix']) && $_GET['fix'] === 'yes') {
        echo "<h2>2. 통계 테이블 업데이트</h2>";
        
        // 통계 테이블에 레코드가 있는지 확인
        $checkStmt = $pdo->prepare("SELECT product_id FROM product_review_statistics WHERE product_id = :product_id");
        $checkStmt->execute([':product_id' => $productId]);
        $exists = $checkStmt->fetch();
        
        if ($exists) {
            // 컬럼 존재 여부 확인
            $hasInitialColumns = false;
            try {
                $checkColStmt = $pdo->query("SHOW COLUMNS FROM product_review_statistics LIKE 'initial_total_rating_sum'");
                $hasInitialColumns = $checkColStmt->rowCount() > 0;
            } catch (PDOException $e) {}
            
            if ($hasInitialColumns) {
                // 하이브리드 방식: initial_* 컬럼도 업데이트
                $updateStmt = $pdo->prepare("
                    UPDATE product_review_statistics 
                    SET 
                        total_rating_sum = :total_sum,
                        total_review_count = :total_count,
                        kindness_rating_sum = :kindness_sum,
                        kindness_review_count = :kindness_count,
                        speed_rating_sum = :speed_sum,
                        speed_review_count = :speed_count,
                        initial_total_rating_sum = :total_sum,
                        initial_total_review_count = :total_count,
                        initial_kindness_rating_sum = :kindness_sum,
                        initial_kindness_review_count = :kindness_count,
                        initial_speed_rating_sum = :speed_sum,
                        initial_speed_review_count = :speed_count,
                        updated_at = NOW()
                    WHERE product_id = :product_id
                ");
            } else {
                // 기존 방식: initial_* 컬럼 없음
                $updateStmt = $pdo->prepare("
                    UPDATE product_review_statistics 
                    SET 
                        total_rating_sum = :total_sum,
                        total_review_count = :total_count,
                        kindness_rating_sum = :kindness_sum,
                        kindness_review_count = :kindness_count,
                        speed_rating_sum = :speed_sum,
                        speed_review_count = :speed_count,
                        updated_at = NOW()
                    WHERE product_id = :product_id
                ");
            }
            
            $updateStmt->execute([
                ':product_id' => $productId,
                ':total_sum' => $totalSum,
                ':total_count' => $totalCount,
                ':kindness_sum' => $kindnessSum,
                ':kindness_count' => $kindnessCount,
                ':speed_sum' => $speedSum,
                ':speed_count' => $speedCount
            ]);
            
            echo "<p style='color: green;'>✓ 통계 테이블 업데이트 완료</p>";
        } else {
            // 컬럼 존재 여부 확인
            $hasInitialColumns = false;
            try {
                $checkColStmt = $pdo->query("SHOW COLUMNS FROM product_review_statistics LIKE 'initial_total_rating_sum'");
                $hasInitialColumns = $checkColStmt->rowCount() > 0;
            } catch (PDOException $e) {}
            
            if ($hasInitialColumns) {
                // 하이브리드 방식: initial_* 컬럼 포함
                $insertStmt = $pdo->prepare("
                    INSERT INTO product_review_statistics (
                        product_id,
                        total_rating_sum, total_review_count,
                        kindness_rating_sum, kindness_review_count,
                        speed_rating_sum, speed_review_count,
                        initial_total_rating_sum, initial_total_review_count,
                        initial_kindness_rating_sum, initial_kindness_review_count,
                        initial_speed_rating_sum, initial_speed_review_count
                    ) VALUES (
                        :product_id,
                        :total_sum, :total_count,
                        :kindness_sum, :kindness_count,
                        :speed_sum, :speed_count,
                        :total_sum, :total_count,
                        :kindness_sum, :kindness_count,
                        :speed_sum, :speed_count
                    )
                ");
            } else {
                // 기존 방식: initial_* 컬럼 없음
                $insertStmt = $pdo->prepare("
                    INSERT INTO product_review_statistics (
                        product_id,
                        total_rating_sum, total_review_count,
                        kindness_rating_sum, kindness_review_count,
                        speed_rating_sum, speed_review_count
                    ) VALUES (
                        :product_id,
                        :total_sum, :total_count,
                        :kindness_sum, :kindness_count,
                        :speed_sum, :speed_count
                    )
                ");
            }
            
            $insertStmt->execute([
                ':product_id' => $productId,
                ':total_sum' => $totalSum,
                ':total_count' => $totalCount,
                ':kindness_sum' => $kindnessSum,
                ':kindness_count' => $kindnessCount,
                ':speed_sum' => $speedSum,
                ':speed_count' => $speedCount
            ]);
            
            echo "<p style='color: green;'>✓ 통계 테이블 생성 완료</p>";
        }
        
        // 3. 업데이트 후 확인
        echo "<h2>3. 업데이트 후 확인</h2>";
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
        $verified = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($verified) {
            $verifiedAvg = $verified['total_review_count'] > 0 ? $verified['total_rating_sum'] / $verified['total_review_count'] : 0;
            echo "<div style='background: #f0fdf4; padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #16a34a;'>";
            echo "<h3>✅ 업데이트 완료!</h3>";
            echo "<ul>";
            echo "<li><strong>총 평균:</strong> {$verified['total_rating_sum']} / {$verified['total_review_count']} = <strong style='color: green; font-size: 18px;'>" . number_format($verifiedAvg, 2) . "</strong></li>";
            if ($verified['kindness_review_count'] > 0) {
                $kindnessAvg = $verified['kindness_rating_sum'] / $verified['kindness_review_count'];
                echo "<li><strong>친절해요:</strong> {$verified['kindness_rating_sum']} / {$verified['kindness_review_count']} = " . number_format($kindnessAvg, 2) . "</li>";
            }
            if ($verified['speed_review_count'] > 0) {
                $speedAvg = $verified['speed_rating_sum'] / $verified['speed_review_count'];
                echo "<li><strong>개통빨라요:</strong> {$verified['speed_rating_sum']} / {$verified['speed_review_count']} = " . number_format($speedAvg, 2) . "</li>";
            }
            echo "</ul>";
            echo "<p><a href='check-product-24-rating-simple.php' style='color: blue;'>다시 확인하기</a></p>";
            echo "</div>";
        }
    } else {
        echo "<h2>2. 통계 수정하기</h2>";
        echo "<div style='background: #fff7ed; padding: 20px; border-radius: 8px; border-left: 4px solid #f59e0b;'>";
        echo "<p>위의 계산된 통계로 업데이트하시겠습니까?</p>";
        echo "<p><a href='?fix=yes' style='background: #3b82f6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>통계 수정하기</a></p>";
        echo "</div>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>오류 발생: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

