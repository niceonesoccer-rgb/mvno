<?php
/**
 * 리뷰 수정 시 통계 변경 테스트 스크립트
 * 리뷰를 수정했을 때 통계 테이블이 변경되지 않는지 확인
 */

require_once __DIR__ . '/includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

$pdo = getDBConnection();
if (!$pdo) {
    die('데이터베이스 연결 실패');
}

echo "<h1>리뷰 수정 시 통계 변경 테스트</h1>";

try {
    // 1. 최근 수정된 리뷰 확인
    echo "<h2>1. 최근 수정된 리뷰 확인</h2>";
    $stmt = $pdo->query("
        SELECT 
            r.id,
            r.product_id,
            r.user_id,
            r.application_id,
            r.rating,
            r.kindness_rating,
            r.speed_rating,
            r.status,
            r.created_at,
            r.updated_at,
            TIMESTAMPDIFF(SECOND, r.created_at, r.updated_at) as seconds_diff
        FROM product_reviews r
        WHERE r.updated_at != r.created_at
        AND r.status = 'approved'
        ORDER BY r.updated_at DESC
        LIMIT 10
    ");
    $updatedReviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($updatedReviews)) {
        echo "<p>최근 수정된 리뷰가 없습니다.</p>";
    } else {
        echo "<p>" . count($updatedReviews) . "개의 최근 수정된 리뷰를 확인합니다.</p>";
        
        foreach ($updatedReviews as $review) {
            $productId = $review['product_id'];
            $reviewId = $review['id'];
            
            echo "<h3>리뷰 ID: {$reviewId} (상품 ID: {$productId})</h3>";
            echo "<p>작성일: {$review['created_at']}, 수정일: {$review['updated_at']}</p>";
            echo "<p>현재 별점: {$review['rating']}, 친절해요: " . ($review['kindness_rating'] ?? '-') . ", 개통빨라요: " . ($review['speed_rating'] ?? '-') . "</p>";
            
            // 통계 테이블 확인
            $statsStmt = $pdo->prepare("
                SELECT 
                    total_rating_sum,
                    total_review_count,
                    kindness_rating_sum,
                    kindness_review_count,
                    speed_rating_sum,
                    speed_review_count,
                    updated_at as stats_updated_at
                FROM product_review_statistics
                WHERE product_id = :product_id
            ");
            $statsStmt->execute([':product_id' => $productId]);
            $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
            
            // 실제 리뷰 합계 계산 (처음 작성 시점의 값 추정)
            // 리뷰가 수정되었으므로, 통계 테이블의 값이 처음 작성 시점의 값이어야 함
            $actualStmt = $pdo->prepare("
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
            $actualStmt->execute([':product_id' => $productId]);
            $actual = $actualStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($stats) {
                $statsAvg = $stats['total_review_count'] > 0 ? round($stats['total_rating_sum'] / $stats['total_review_count'], 1) : 0;
                $actualAvg = $actual['total_review_count'] > 0 ? round($actual['total_rating_sum'] / $actual['total_review_count'], 1) : 0;
                
                echo "<table border='1' cellpadding='10' style='border-collapse: collapse; margin: 10px 0;'>";
                echo "<tr><th>항목</th><th>통계 테이블 (처음 값)</th><th>실제 계산값 (현재 리뷰)</th><th>상태</th></tr>";
                
                // 총별점 비교
                $totalMatch = abs($stats['total_rating_sum'] - $actual['total_rating_sum']) < 0.01;
                $totalColor = $totalMatch ? 'green' : 'red';
                $totalStatus = $totalMatch ? '✓ 정상 (변경 없음)' : '✗ 문제 (변경됨)';
                
                echo "<tr>";
                echo "<td>총별점 합계</td>";
                echo "<td>" . $stats['total_rating_sum'] . "</td>";
                echo "<td>" . ($actual['total_rating_sum'] ?? 0) . "</td>";
                echo "<td style='color: $totalColor; font-weight: bold;'>$totalStatus</td>";
                echo "</tr>";
                
                echo "<tr>";
                echo "<td>리뷰 개수</td>";
                echo "<td>" . $stats['total_review_count'] . "</td>";
                echo "<td>" . ($actual['total_review_count'] ?? 0) . "</td>";
                echo "<td>" . ($stats['total_review_count'] == $actual['total_review_count'] ? '<span style="color: green;">✓</span>' : '<span style="color: red;">✗</span>') . "</td>";
                echo "</tr>";
                
                echo "<tr>";
                echo "<td>평균 별점</td>";
                echo "<td>" . $statsAvg . "</td>";
                echo "<td>" . $actualAvg . "</td>";
                echo "<td style='color: $totalColor; font-weight: bold;'>" . ($totalMatch ? '✓ 정상' : '✗ 변경됨') . "</td>";
                echo "</tr>";
                
                // 항목별 비교
                if ($stats['kindness_review_count'] > 0) {
                    $kindnessStatsAvg = round($stats['kindness_rating_sum'] / $stats['kindness_review_count'], 1);
                    $kindnessActualAvg = $actual['kindness_review_count'] > 0 ? round($actual['kindness_rating_sum'] / $actual['kindness_review_count'], 1) : 0;
                    $kindnessMatch = abs($stats['kindness_rating_sum'] - ($actual['kindness_rating_sum'] ?? 0)) < 0.01;
                    $kindnessColor = $kindnessMatch ? 'green' : 'red';
                    
                    echo "<tr>";
                    echo "<td>친절해요 합계</td>";
                    echo "<td>" . $stats['kindness_rating_sum'] . "</td>";
                    echo "<td>" . ($actual['kindness_rating_sum'] ?? 0) . "</td>";
                    echo "<td style='color: $kindnessColor; font-weight: bold;'>" . ($kindnessMatch ? '✓ 정상' : '✗ 변경됨') . "</td>";
                    echo "</tr>";
                }
                
                if ($stats['speed_review_count'] > 0) {
                    $speedStatsAvg = round($stats['speed_rating_sum'] / $stats['speed_review_count'], 1);
                    $speedActualAvg = $actual['speed_review_count'] > 0 ? round($actual['speed_rating_sum'] / $actual['speed_review_count'], 1) : 0;
                    $speedMatch = abs($stats['speed_rating_sum'] - ($actual['speed_rating_sum'] ?? 0)) < 0.01;
                    $speedColor = $speedMatch ? 'green' : 'red';
                    
                    echo "<tr>";
                    echo "<td>개통빨라요 합계</td>";
                    echo "<td>" . $stats['speed_rating_sum'] . "</td>";
                    echo "<td>" . ($actual['speed_rating_sum'] ?? 0) . "</td>";
                    echo "<td style='color: $speedColor; font-weight: bold;'>" . ($speedMatch ? '✓ 정상' : '✗ 변경됨') . "</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
                
                // 통계 테이블 업데이트 시간 확인
                if ($stats['stats_updated_at']) {
                    $statsUpdated = new DateTime($stats['stats_updated_at']);
                    $reviewUpdated = new DateTime($review['updated_at']);
                    
                    if ($statsUpdated < $reviewUpdated) {
                        echo "<p style='color: green;'>✓ 통계 테이블이 리뷰 수정 전에 업데이트되었습니다. (정상)</p>";
                    } else {
                        echo "<p style='color: red;'>✗ 통계 테이블이 리뷰 수정 후에 업데이트되었습니다! (문제)</p>";
                        echo "<p>통계 업데이트 시간: {$stats['stats_updated_at']}</p>";
                        echo "<p>리뷰 수정 시간: {$review['updated_at']}</p>";
                    }
                }
                
                if (!$totalMatch) {
                    echo "<p style='color: red; background: #fee; padding: 10px; border-radius: 5px;'><strong>경고:</strong> 리뷰를 수정했는데 통계 테이블의 총별점이 변경되었습니다!</p>";
                    echo "<p>이것은 정상 동작이 아닙니다. 통계 테이블은 처음 작성 시점의 값을 유지해야 합니다.</p>";
                }
            } else {
                echo "<p style='color: orange;'>통계 테이블에 데이터가 없습니다.</p>";
            }
            
            echo "<hr>";
        }
    }
    
    // 2. 서버 로그 확인 안내
    echo "<h2>2. 서버 로그 확인</h2>";
    echo "<p>리뷰를 수정한 후 서버 로그 파일(error_log)에서 다음 로그를 확인하세요:</p>";
    echo "<ul>";
    echo "<li><strong>DEBUG updateProductReview: 수정 전 통계</strong> - 리뷰 수정 전 통계 값</li>";
    echo "<li><strong>DEBUG updateProductReview: 수정 후 통계</strong> - 리뷰 수정 후 통계 값</li>";
    echo "<li><strong>ERROR updateProductReview: 통계 테이블이 변경되었습니다!</strong> - 통계가 변경된 경우 (빨간색 경고)</li>";
    echo "</ul>";
    
    // 3. 테스트 방법
    echo "<h2>3. 테스트 방법</h2>";
    echo "<ol>";
    echo "<li>이 페이지를 열어두세요.</li>";
    echo "<li>다른 탭에서 리뷰를 수정하세요 (별점 변경).</li>";
    echo "<li>이 페이지를 새로고침하여 통계가 변경되었는지 확인하세요.</li>";
    echo "<li>서버 로그를 확인하여 'ERROR updateProductReview' 메시지가 나타나는지 확인하세요.</li>";
    echo "</ol>";
    
    // 4. 자동 새로고침
    echo "<script>";
    echo "setTimeout(function() { location.reload(); }, 30000);";
    echo "document.write('<p style=\"color: blue;\">30초 후 자동 새로고침됩니다...</p>');";
    echo "</script>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>오류 발생: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}







