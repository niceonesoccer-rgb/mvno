<?php
/**
 * 리뷰 통계 테이블 수정 스크립트
 * 통계 테이블이 비어있거나 잘못된 경우 수정
 */

require_once __DIR__ . '/includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

$pdo = getDBConnection();
if (!$pdo) {
    die('데이터베이스 연결 실패');
}

echo "<h1>리뷰 통계 테이블 수정</h1>";

try {
    // 1. 통계 테이블이 비어있거나 잘못된 상품 찾기
    echo "<h2>1. 통계 테이블 문제 확인</h2>";
    
    $stmt = $pdo->query("
        SELECT 
            p.id as product_id,
            p.product_type,
            (SELECT COUNT(*) FROM product_reviews WHERE product_id = p.id AND status = 'approved') as approved_count,
            (SELECT SUM(rating) FROM product_reviews WHERE product_id = p.id AND status = 'approved') as actual_total_sum,
            s.total_rating_sum as stats_total_sum,
            s.total_review_count as stats_count
        FROM products p
        LEFT JOIN product_review_statistics s ON p.id = s.product_id
        WHERE p.product_type IN ('mvno', 'mno', 'internet')
        AND (
            s.product_id IS NULL 
            OR s.total_review_count = 0
            OR s.total_review_count != (SELECT COUNT(*) FROM product_reviews WHERE product_id = p.id AND status = 'approved')
        )
        AND (SELECT COUNT(*) FROM product_reviews WHERE product_id = p.id AND status = 'approved') > 0
        ORDER BY p.id
    ");
    $problems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($problems)) {
        echo "<p style='color: green;'>✓ 모든 통계가 정상입니다.</p>";
    } else {
        echo "<p style='color: orange;'>" . count($problems) . "개의 상품에서 문제가 발견되었습니다.</p>";
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr>";
        echo "<th>상품 ID</th>";
        echo "<th>타입</th>";
        echo "<th>실제 승인 리뷰 수</th>";
        echo "<th>통계 테이블 리뷰 수</th>";
        echo "<th>실제 합계</th>";
        echo "<th>통계 테이블 합계</th>";
        echo "<th>상태</th>";
        echo "</tr>";
        
        foreach ($problems as $problem) {
            $status = '문제 있음';
            if ($problem['stats_count'] == null || $problem['stats_count'] == 0) {
                $status = '통계 없음';
            } elseif ($problem['stats_count'] != $problem['approved_count']) {
                $status = '개수 불일치';
            }
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($problem['product_id']) . "</td>";
            echo "<td>" . htmlspecialchars($problem['product_type']) . "</td>";
            echo "<td>" . htmlspecialchars($problem['approved_count']) . "</td>";
            echo "<td>" . htmlspecialchars($problem['stats_count'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($problem['actual_total_sum'] ?? '0') . "</td>";
            echo "<td>" . htmlspecialchars($problem['stats_total_sum'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($status) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 2. 통계 재계산 및 수정
    if (!empty($problems) && isset($_GET['fix']) && $_GET['fix'] === 'yes') {
        echo "<h2>2. 통계 재계산 및 수정</h2>";
        
        $fixed = 0;
        $errors = 0;
        
        foreach ($problems as $problem) {
            $productId = $problem['product_id'];
            $productType = $problem['product_type'];
            
            try {
                // 실제 리뷰 데이터에서 통계 계산
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
                    AND status = 'approved'
                ");
                $calcStmt->execute([':product_id' => $productId]);
                $calculated = $calcStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($calculated && $calculated['total_review_count'] > 0) {
                    // 통계 테이블 업데이트 또는 삽입
                    $updateStmt = $pdo->prepare("
                        INSERT INTO product_review_statistics 
                        (product_id, total_rating_sum, total_review_count, kindness_rating_sum, kindness_review_count, speed_rating_sum, speed_review_count, updated_at)
                        VALUES (:product_id, :total_rating_sum, :total_review_count, :kindness_rating_sum, :kindness_review_count, :speed_rating_sum, :speed_review_count, NOW())
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
                        ':total_rating_sum' => $calculated['total_rating_sum'] ?? 0,
                        ':total_review_count' => $calculated['total_review_count'],
                        ':kindness_rating_sum' => $calculated['kindness_rating_sum'] ?? 0,
                        ':kindness_review_count' => $calculated['kindness_review_count'] ?? 0,
                        ':speed_rating_sum' => $calculated['speed_rating_sum'] ?? 0,
                        ':speed_review_count' => $calculated['speed_review_count'] ?? 0,
                        ':total_rating_sum2' => $calculated['total_rating_sum'] ?? 0,
                        ':total_review_count2' => $calculated['total_review_count'],
                        ':kindness_rating_sum2' => $calculated['kindness_rating_sum'] ?? 0,
                        ':kindness_review_count2' => $calculated['kindness_review_count'] ?? 0,
                        ':speed_rating_sum2' => $calculated['speed_rating_sum'] ?? 0,
                        ':speed_review_count2' => $calculated['speed_review_count'] ?? 0
                    ]);
                    
                    $fixed++;
                    echo "<p style='color: green;'>✓ 상품 ID $productId 통계 수정 완료</p>";
                } else {
                    echo "<p style='color: orange;'>- 상품 ID $productId: 승인된 리뷰가 없음</p>";
                }
            } catch (PDOException $e) {
                $errors++;
                echo "<p style='color: red;'>✗ 상품 ID $productId 오류: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
        
        echo "<hr>";
        echo "<p><strong>수정 완료:</strong> " . $fixed . "개 성공, " . $errors . "개 실패</p>";
        echo "<p><a href='?'>다시 확인하기</a></p>";
    } else if (!empty($problems)) {
        echo "<h2>2. 통계 수정</h2>";
        echo "<p>위의 문제를 수정하시겠습니까?</p>";
        echo "<p><a href='?fix=yes' style='background: #EF4444; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>통계 재계산 및 수정</a></p>";
    }
    
    // 3. 리뷰 작성 시 통계 업데이트 확인
    echo "<h2>3. 리뷰 작성 시 통계 업데이트 확인</h2>";
    echo "<p>리뷰 작성 시 통계가 자동으로 업데이트되는지 확인:</p>";
    
    // 최근 작성된 리뷰 중 통계가 업데이트되지 않은 것 찾기
    $recentStmt = $pdo->query("
        SELECT 
            r.id,
            r.product_id,
            r.status,
            r.created_at,
            s.total_review_count
        FROM product_reviews r
        LEFT JOIN product_review_statistics s ON r.product_id = s.product_id
        WHERE r.status = 'approved'
        AND r.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
        AND (s.total_review_count IS NULL OR s.total_review_count = 0)
        ORDER BY r.created_at DESC
        LIMIT 10
    ");
    $recentProblems = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($recentProblems)) {
        echo "<p style='color: green;'>✓ 최근 작성된 리뷰의 통계가 모두 정상입니다.</p>";
    } else {
        echo "<p style='color: orange;'>최근 작성된 리뷰 중 통계가 업데이트되지 않은 것: " . count($recentProblems) . "개</p>";
        echo "<ul>";
        foreach ($recentProblems as $rp) {
            echo "<li>리뷰 ID " . htmlspecialchars($rp['id']) . " (상품 ID " . htmlspecialchars($rp['product_id']) . ") - " . htmlspecialchars($rp['created_at']) . "</li>";
        }
        echo "</ul>";
        echo "<p style='color: red;'><strong>주의:</strong> 트리거가 제대로 작동하지 않거나, 리뷰가 'approved' 상태로 변경되지 않았을 수 있습니다.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>오류 발생: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
