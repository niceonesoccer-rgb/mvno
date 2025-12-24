<?php
/**
 * 리뷰 통계 검증 및 재계산 스크립트
 * 실제 리뷰 데이터와 통계 테이블을 비교하여 불일치 시 재계산
 */

require_once __DIR__ . '/includes/data/db-config.php';
require_once __DIR__ . '/includes/data/product-functions.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>리뷰 통계 검증 및 재계산</title>
    <style>
        body {
            font-family: 'Malgun Gothic', sans-serif;
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #6366f1;
            padding-bottom: 10px;
        }
        h2 {
            color: #555;
            margin-top: 30px;
            padding: 10px;
            background: #f0f0f0;
            border-left: 4px solid #6366f1;
        }
        .success {
            color: #10b981;
            background: #d1fae5;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .error {
            color: #ef4444;
            background: #fee2e2;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .warning {
            color: #f59e0b;
            background: #fef3c7;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .info {
            color: #3b82f6;
            background: #dbeafe;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 14px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f9fafb;
            font-weight: bold;
        }
        tr:hover {
            background: #f9fafb;
        }
        .mismatch {
            background: #fee2e2;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #6366f1;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 10px 5px;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            background: #4f46e5;
        }
        .btn-danger {
            background: #ef4444;
        }
        .btn-danger:hover {
            background: #dc2626;
        }
        .stats {
            display: inline-block;
            margin: 5px 10px;
            padding: 8px 12px;
            background: #f0f0f0;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>리뷰 통계 검증 및 재계산</h1>
        
        <?php
        $pdo = getDBConnection();
        if (!$pdo) {
            echo '<div class="error">데이터베이스 연결 실패</div>';
            exit;
        }
        
        $action = $_GET['action'] ?? 'verify';
        $productId = isset($_GET['product_id']) ? intval($_GET['product_id']) : null;
        
        // 1. 통계 검증
        echo "<h2>1. 통계 검증</h2>";
        echo "<p>실제 리뷰 데이터와 통계 테이블을 비교합니다.</p>";
        
        try {
            $query = "
                SELECT 
                    p.id as product_id,
                    p.product_type,
                    COUNT(r.id) as actual_count,
                    COALESCE(SUM(r.rating), 0) as actual_sum,
                    COALESCE(AVG(r.rating), 0) as actual_avg,
                    COALESCE(SUM(r.kindness_rating), 0) as actual_kindness_sum,
                    COUNT(CASE WHEN r.kindness_rating IS NOT NULL THEN 1 END) as actual_kindness_count,
                    COALESCE(SUM(r.speed_rating), 0) as actual_speed_sum,
                    COUNT(CASE WHEN r.speed_rating IS NOT NULL THEN 1 END) as actual_speed_count,
                    s.total_review_count as stats_count,
                    s.total_rating_sum as stats_sum,
                    CASE 
                        WHEN s.total_review_count > 0 THEN s.total_rating_sum / s.total_review_count
                        ELSE 0
                    END as stats_avg,
                    s.kindness_rating_sum as stats_kindness_sum,
                    s.kindness_review_count as stats_kindness_count,
                    s.speed_rating_sum as stats_speed_sum,
                    s.speed_review_count as stats_speed_count
                FROM products p
                LEFT JOIN product_reviews r ON p.id = r.product_id AND r.status = 'approved'
                LEFT JOIN product_review_statistics s ON p.id = s.product_id
                WHERE p.product_type IN ('mvno', 'mno', 'internet')
            ";
            
            if ($productId) {
                $query .= " AND p.id = :product_id";
                $stmt = $pdo->prepare($query);
                $stmt->execute([':product_id' => $productId]);
            } else {
                $query .= " GROUP BY p.id";
                $stmt = $pdo->prepare($query);
                $stmt->execute();
            }
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($results)) {
                echo '<div class="info">리뷰가 있는 상품이 없습니다.</div>';
            } else {
                $mismatchCount = 0;
                $totalCount = 0;
                
                echo '<table>';
                echo '<tr>';
                echo '<th>상품 ID</th>';
                echo '<th>타입</th>';
                echo '<th>실제 리뷰 수</th>';
                echo '<th>통계 리뷰 수</th>';
                echo '<th>실제 합계</th>';
                echo '<th>통계 합계</th>';
                echo '<th>실제 평균</th>';
                echo '<th>통계 평균</th>';
                echo '<th>상태</th>';
                echo '</tr>';
                
                foreach ($results as $row) {
                    $totalCount++;
                    $actualCount = (int)$row['actual_count'];
                    $statsCount = (int)($row['stats_count'] ?? 0);
                    $actualSum = (float)$row['actual_sum'];
                    $statsSum = (float)($row['stats_sum'] ?? 0);
                    $actualAvg = $actualCount > 0 ? round($actualSum / $actualCount, 1) : 0;
                    $statsAvg = $statsCount > 0 ? round($statsSum / $statsCount, 1) : 0;
                    
                    $isMismatch = ($actualCount != $statsCount) || (abs($actualSum - $statsSum) > 0.01);
                    $rowClass = $isMismatch ? 'class="mismatch"' : '';
                    
                    if ($isMismatch) {
                        $mismatchCount++;
                    }
                    
                    echo "<tr $rowClass>";
                    echo '<td>' . htmlspecialchars($row['product_id']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['product_type']) . '</td>';
                    echo '<td>' . number_format($actualCount) . '</td>';
                    echo '<td>' . number_format($statsCount) . '</td>';
                    echo '<td>' . number_format($actualSum, 1) . '</td>';
                    echo '<td>' . number_format($statsSum, 1) . '</td>';
                    echo '<td>' . number_format($actualAvg, 1) . '</td>';
                    echo '<td>' . number_format($statsAvg, 1) . '</td>';
                    echo '<td>' . ($isMismatch ? '<span style="color: red;">❌ 불일치</span>' : '<span style="color: green;">✅ 일치</span>') . '</td>';
                    echo '</tr>';
                }
                
                echo '</table>';
                
                echo '<div class="info">';
                echo '<strong>검증 결과:</strong> ';
                echo "총 {$totalCount}개 상품 중 {$mismatchCount}개 불일치";
                echo '</div>';
                
                if ($mismatchCount > 0) {
                    echo '<div class="warning">';
                    echo '<strong>⚠️ 불일치 발견:</strong> 통계를 재계산해야 합니다.';
                    echo '</div>';
                }
            }
        } catch (PDOException $e) {
            echo '<div class="error">검증 실패: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        
        // 2. 통계 재계산
        if ($action === 'fix' || $action === 'fix_all') {
            echo "<h2>2. 통계 재계산</h2>";
            
            try {
                $fixedCount = 0;
                $errorCount = 0;
                
                if ($action === 'fix' && $productId) {
                    // 특정 상품만 재계산
                    $products = [['id' => $productId, 'type' => $results[0]['product_type'] ?? 'mvno']];
                } else {
                    // 모든 상품 재계산
                    $stmt = $pdo->query("
                        SELECT DISTINCT p.id, p.product_type
                        FROM products p
                        INNER JOIN product_reviews r ON p.id = r.product_id
                        WHERE p.product_type IN ('mvno', 'mno', 'internet')
                        AND r.status = 'approved'
                    ");
                    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
                
                foreach ($products as $product) {
                    try {
                        updateReviewStatistics($product['id'], null, null, null, $product['product_type']);
                        $fixedCount++;
                        echo '<div class="success">✓ 상품 ID ' . $product['id'] . ' (' . $product['product_type'] . ') 통계 재계산 완료</div>';
                    } catch (Exception $e) {
                        $errorCount++;
                        echo '<div class="error">✗ 상품 ID ' . $product['id'] . ' 재계산 실패: ' . htmlspecialchars($e->getMessage()) . '</div>';
                    }
                }
                
                echo '<div class="success">';
                echo '<strong>재계산 완료:</strong> ';
                echo "성공 {$fixedCount}개, 실패 {$errorCount}개";
                echo '</div>';
                
                if ($fixedCount > 0) {
                    echo '<div class="info">';
                    echo '<a href="?action=verify" class="btn">다시 검증하기</a>';
                    echo '</div>';
                }
                
            } catch (PDOException $e) {
                echo '<div class="error">재계산 실패: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        } else {
            // 재계산 버튼
            if ($mismatchCount > 0) {
                echo '<h2>2. 통계 재계산</h2>';
                echo '<div class="warning">';
                echo '<p>불일치가 발견되었습니다. 통계를 재계산하시겠습니까?</p>';
                if ($productId) {
                    echo '<a href="?action=fix&product_id=' . $productId . '" class="btn btn-danger">이 상품만 재계산</a>';
                } else {
                    echo '<a href="?action=fix_all" class="btn btn-danger">모든 상품 재계산</a>';
                }
                echo '</div>';
            }
        }
        
        // 3. 트리거 확인
        echo "<h2>3. 트리거 확인</h2>";
        try {
            $stmt = $pdo->query("
                SELECT 
                    TRIGGER_NAME,
                    EVENT_MANIPULATION,
                    EVENT_OBJECT_TABLE,
                    ACTION_TIMING
                FROM information_schema.TRIGGERS
                WHERE TRIGGER_SCHEMA = DATABASE()
                AND TRIGGER_NAME LIKE 'trg_update_review_statistics%'
                ORDER BY TRIGGER_NAME
            ");
            $triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($triggers)) {
                echo '<div class="error">⚠️ 트리거가 없습니다! <a href="add-review-statistics-triggers.php">트리거 생성하기</a></div>';
            } else {
                echo '<table>';
                echo '<tr><th>트리거명</th><th>이벤트</th><th>테이블</th><th>타이밍</th></tr>';
                foreach ($triggers as $trigger) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($trigger['TRIGGER_NAME']) . '</td>';
                    echo '<td>' . htmlspecialchars($trigger['EVENT_MANIPULATION']) . '</td>';
                    echo '<td>' . htmlspecialchars($trigger['EVENT_OBJECT_TABLE']) . '</td>';
                    echo '<td>' . htmlspecialchars($trigger['ACTION_TIMING']) . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
                echo '<div class="success">✓ 모든 트리거가 존재합니다.</div>';
            }
        } catch (PDOException $e) {
            echo '<div class="error">트리거 확인 실패: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        
        // 4. 테스트 가이드
        echo "<h2>4. 테스트 가이드</h2>";
        echo '<div class="info">';
        echo '<p><strong>통계가 올바르게 누적되는지 테스트:</strong></p>';
        echo '<ol>';
        echo '<li>테스트 상품에 리뷰 1개 작성 (rating=5) → 통계 확인: 평균 = 5.0</li>';
        echo '<li>같은 상품에 리뷰 1개 더 작성 (rating=4) → 통계 확인: 평균 = 4.5</li>';
        echo '<li>같은 상품에 리뷰 1개 더 작성 (rating=3) → 통계 확인: 평균 = 4.0</li>';
        echo '</ol>';
        echo '<p><strong>확인 방법:</strong></p>';
        echo '<pre>';
        echo "SELECT * FROM product_review_statistics WHERE product_id = ?;\n";
        echo "SELECT COUNT(*), SUM(rating), AVG(rating) FROM product_reviews WHERE product_id = ? AND status = 'approved';";
        echo '</pre>';
        echo '</div>';
        ?>
    </div>
</body>
</html>

