<?php
/**
 * 리뷰 수정 시 통계 변경 실시간 모니터링
 * 리뷰를 수정하기 전후의 통계 값을 실시간으로 비교
 */

require_once __DIR__ . '/includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

$pdo = getDBConnection();
if (!$pdo) {
    die('데이터베이스 연결 실패');
}

// 특정 상품 ID를 URL 파라미터로 받기
$productId = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

echo "<h1>리뷰 수정 시 통계 변경 실시간 모니터링</h1>";

if ($productId > 0) {
    echo "<p><strong>모니터링 중인 상품 ID: $productId</strong></p>";
    echo "<p><a href='?'>전체 상품 보기</a></p>";
} else {
    echo "<p>상품 ID를 URL 파라미터로 지정하세요: ?product_id=33</p>";
}

try {
    if ($productId > 0) {
        // 특정 상품의 통계 모니터링
        echo "<h2>상품 ID: $productId 통계 모니터링</h2>";
        
        // 통계 테이블 값
        $statsStmt = $pdo->prepare("
            SELECT 
                total_rating_sum,
                total_review_count,
                kindness_rating_sum,
                kindness_review_count,
                speed_rating_sum,
                speed_review_count,
                updated_at
            FROM product_review_statistics
            WHERE product_id = :product_id
        ");
        $statsStmt->execute([':product_id' => $productId]);
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
        
        // 실제 리뷰 값
        $reviewsStmt = $pdo->prepare("
            SELECT 
                id,
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
        
        // 실제 계산값
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
            
            echo "<div style='background: #f0f9ff; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
            echo "<h3>통계 비교</h3>";
            echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>항목</th><th>통계 테이블 (처음 값)</th><th>실제 계산값 (현재 리뷰)</th><th>차이</th><th>상태</th></tr>";
            
            $totalMatch = abs($stats['total_rating_sum'] - ($actual['total_rating_sum'] ?? 0)) < 0.01;
            $totalColor = $totalMatch ? 'green' : 'orange';
            $totalStatus = $totalMatch ? '✓ 정상 (변경 없음)' : '⚠ 다름 (정상 - 처음 값 유지)';
            
            echo "<tr>";
            echo "<td><strong>총별점 합계</strong></td>";
            echo "<td>" . $stats['total_rating_sum'] . "</td>";
            echo "<td>" . ($actual['total_rating_sum'] ?? 0) . "</td>";
            echo "<td>" . abs($stats['total_rating_sum'] - ($actual['total_rating_sum'] ?? 0)) . "</td>";
            echo "<td style='color: $totalColor; font-weight: bold;'>$totalStatus</td>";
            echo "</tr>";
            
            echo "<tr>";
            echo "<td>리뷰 개수</td>";
            echo "<td>" . $stats['total_review_count'] . "</td>";
            echo "<td>" . ($actual['total_review_count'] ?? 0) . "</td>";
            echo "<td>" . abs($stats['total_review_count'] - ($actual['total_review_count'] ?? 0)) . "</td>";
            echo "<td>" . ($stats['total_review_count'] == ($actual['total_review_count'] ?? 0) ? '<span style="color: green;">✓</span>' : '<span style="color: orange;">⚠</span>') . "</td>";
            echo "</tr>";
            
            echo "<tr>";
            echo "<td><strong>평균 별점</strong></td>";
            echo "<td><strong>" . $statsAvg . "</strong></td>";
            echo "<td>" . $actualAvg . "</td>";
            echo "<td>" . abs($statsAvg - $actualAvg) . "</td>";
            echo "<td style='color: $totalColor; font-weight: bold;'>" . ($totalMatch ? '✓ 정상' : '⚠ 다름 (정상)') . "</td>";
            echo "</tr>";
            
            echo "</table>";
            echo "<p style='margin-top: 10px;'><strong>통계 테이블 업데이트 시간:</strong> " . ($stats['updated_at'] ?? 'NULL') . "</p>";
            echo "</div>";
            
            // 리뷰 목록
            echo "<h3>리뷰 목록</h3>";
            echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>리뷰 ID</th><th>별점</th><th>친절해요</th><th>개통빨라요</th><th>작성일</th><th>수정일</th><th>수정 여부</th></tr>";
            foreach ($reviews as $review) {
                $isModified = $review['created_at'] != $review['updated_at'];
                $modifiedColor = $isModified ? 'orange' : 'green';
                $modifiedText = $isModified ? '수정됨' : '수정 안됨';
                
                echo "<tr>";
                echo "<td>" . htmlspecialchars($review['id']) . "</td>";
                echo "<td>" . htmlspecialchars($review['rating']) . "</td>";
                echo "<td>" . htmlspecialchars($review['kindness_rating'] ?? '-') . "</td>";
                echo "<td>" . htmlspecialchars($review['speed_rating'] ?? '-') . "</td>";
                echo "<td>" . htmlspecialchars($review['created_at']) . "</td>";
                echo "<td>" . htmlspecialchars($review['updated_at']) . "</td>";
                echo "<td style='color: $modifiedColor;'>$modifiedText</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // 설명
            echo "<div style='background: #fff7ed; padding: 15px; border-radius: 8px; border-left: 4px solid #f59e0b; margin-top: 20px;'>";
            echo "<h4>설명:</h4>";
            echo "<ul>";
            echo "<li><strong>통계 테이블 (처음 값):</strong> 리뷰를 처음 작성했을 때의 별점 합계입니다. 리뷰를 수정해도 변경되지 않아야 합니다.</li>";
            echo "<li><strong>실제 계산값 (현재 리뷰):</strong> 현재 리뷰 테이블의 별점을 합산한 값입니다. 리뷰를 수정하면 이 값은 변경됩니다.</li>";
            echo "<li><strong>차이:</strong> 두 값이 다르면 리뷰가 수정되었다는 의미입니다. 이것은 정상입니다!</li>";
            echo "<li><strong>화면에 표시되는 별점:</strong> 통계 테이블의 값(처음 값)을 사용합니다.</li>";
            echo "</ul>";
            echo "</div>";
        } else {
            echo "<p style='color: orange;'>통계 테이블에 데이터가 없습니다.</p>";
        }
        
        // 자동 새로고침
        echo "<script>";
        echo "setTimeout(function() { location.reload(); }, 5000);";
        echo "document.write('<p style=\"color: blue; margin-top: 20px;\">5초 후 자동 새로고침됩니다...</p>');";
        echo "</script>";
        
    } else {
        // 전체 상품 목록
        echo "<h2>리뷰가 있는 상품 목록</h2>";
        $stmt = $pdo->query("
            SELECT 
                p.id,
                p.product_type,
                COUNT(r.id) as review_count
            FROM products p
            INNER JOIN product_reviews r ON p.id = r.product_id
            WHERE r.status = 'approved'
            GROUP BY p.id, p.product_type
            ORDER BY p.id
        ");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($products)) {
            echo "<p>리뷰가 있는 상품이 없습니다.</p>";
        } else {
            echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
            echo "<tr><th>상품 ID</th><th>타입</th><th>리뷰 수</th><th>모니터링</th></tr>";
            foreach ($products as $product) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($product['id']) . "</td>";
                echo "<td>" . htmlspecialchars($product['product_type']) . "</td>";
                echo "<td>" . htmlspecialchars($product['review_count']) . "</td>";
                echo "<td><a href='?product_id=" . htmlspecialchars($product['id']) . "'>모니터링</a></td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>오류 발생: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}







