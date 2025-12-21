<?php
/**
 * 리뷰 쿼리 성능 최적화 스크립트
 * 복합 인덱스 추가 및 성능 분석
 */

require_once __DIR__ . '/includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

$pdo = getDBConnection();
if (!$pdo) {
    die('데이터베이스 연결 실패');
}

echo "<h1>리뷰 쿼리 성능 최적화</h1>";

try {
    // 1. 현재 인덱스 확인
    echo "<h2>1. 현재 인덱스 확인</h2>";
    $indexes = $pdo->query("SHOW INDEXES FROM product_reviews")->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>인덱스명</th><th>컬럼명</th><th>순서</th></tr>";
    foreach ($indexes as $index) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($index['Key_name']) . "</td>";
        echo "<td>" . htmlspecialchars($index['Column_name']) . "</td>";
        echo "<td>" . htmlspecialchars($index['Seq_in_index']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 2. 필요한 복합 인덱스 확인
    echo "<h2>2. 필요한 복합 인덱스 확인</h2>";
    $hasCompositeIndex = false;
    foreach ($indexes as $index) {
        if ($index['Key_name'] === 'idx_product_type_status' || 
            $index['Key_name'] === 'idx_product_id_type_status') {
            $hasCompositeIndex = true;
            break;
        }
    }
    
    if (!$hasCompositeIndex) {
        echo "<p style='color: orange;'>⚠️ 복합 인덱스가 없습니다. 성능 최적화를 위해 추가하는 것을 권장합니다.</p>";
        
        // 3. 복합 인덱스 추가
        echo "<h2>3. 복합 인덱스 추가</h2>";
        echo "<p>다음 인덱스를 추가하시겠습니까?</p>";
        echo "<ul>";
        echo "<li><code>idx_product_id_type_status</code>: (product_id, product_type, status)</li>";
        echo "<li><code>idx_product_id_type_status_kindness</code>: (product_id, product_type, status, kindness_rating)</li>";
        echo "<li><code>idx_product_id_type_status_speed</code>: (product_id, product_type, status, speed_rating)</li>";
        echo "</ul>";
        
        if (isset($_GET['add_index']) && $_GET['add_index'] === 'yes') {
            try {
                // 복합 인덱스 추가
                $pdo->exec("
                    ALTER TABLE product_reviews 
                    ADD INDEX idx_product_id_type_status (product_id, product_type, status)
                ");
                echo "<p style='color: green;'>✓ idx_product_id_type_status 인덱스 추가 완료</p>";
                
                // kindness_rating이 있는 경우
                $hasKindness = $pdo->query("SHOW COLUMNS FROM product_reviews LIKE 'kindness_rating'")->rowCount() > 0;
                if ($hasKindness) {
                    try {
                        $pdo->exec("
                            ALTER TABLE product_reviews 
                            ADD INDEX idx_product_id_type_status_kindness (product_id, product_type, status, kindness_rating)
                        ");
                        echo "<p style='color: green;'>✓ idx_product_id_type_status_kindness 인덱스 추가 완료</p>";
                    } catch (PDOException $e) {
                        echo "<p style='color: orange;'>⚠️ kindness 인덱스 추가 실패 (이미 존재할 수 있음): " . htmlspecialchars($e->getMessage()) . "</p>";
                    }
                    
                    try {
                        $pdo->exec("
                            ALTER TABLE product_reviews 
                            ADD INDEX idx_product_id_type_status_speed (product_id, product_type, status, speed_rating)
                        ");
                        echo "<p style='color: green;'>✓ idx_product_id_type_status_speed 인덱스 추가 완료</p>";
                    } catch (PDOException $e) {
                        echo "<p style='color: orange;'>⚠️ speed 인덱스 추가 실패 (이미 존재할 수 있음): " . htmlspecialchars($e->getMessage()) . "</p>";
                    }
                }
            } catch (PDOException $e) {
                echo "<p style='color: red;'>✗ 인덱스 추가 실패: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        } else {
            echo "<p><a href='?add_index=yes' style='padding: 10px 20px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px;'>인덱스 추가하기</a></p>";
        }
    } else {
        echo "<p style='color: green;'>✓ 복합 인덱스가 이미 존재합니다.</p>";
    }
    
    // 4. 성능 테스트
    echo "<h2>4. 성능 테스트</h2>";
    $testProductId = isset($_GET['product_id']) ? intval($_GET['product_id']) : 33;
    
    // 리뷰 개수 확인
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM product_reviews
        WHERE product_id = :product_id
        AND product_type = 'mno'
        AND status = 'approved'
    ");
    $countStmt->execute([':product_id' => $testProductId]);
    $reviewCount = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "<p>테스트 상품 ID: $testProductId (리뷰 개수: $reviewCount)</p>";
    
    // 쿼리 실행 시간 측정
    $iterations = 10;
    $times = [];
    
    for ($i = 0; $i < $iterations; $i++) {
        $start = microtime(true);
        
        $stmt = $pdo->prepare("
            SELECT AVG(rating) as avg_rating, COUNT(*) as count
            FROM product_reviews
            WHERE product_id = :product_id
            AND product_type = :product_type
            AND status = 'approved'
        ");
        $stmt->execute([':product_id' => $testProductId, ':product_type' => 'mno']);
        $stmt->fetch();
        
        $end = microtime(true);
        $times[] = ($end - $start) * 1000; // 밀리초
    }
    
    $avgTime = array_sum($times) / count($times);
    $maxTime = max($times);
    $minTime = min($times);
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>항목</th><th>값</th></tr>";
    echo "<tr><td>평균 실행 시간</td><td>" . number_format($avgTime, 2) . " ms</td></tr>";
    echo "<tr><td>최대 실행 시간</td><td>" . number_format($maxTime, 2) . " ms</td></tr>";
    echo "<tr><td>최소 실행 시간</td><td>" . number_format($minTime, 2) . " ms</td></tr>";
    echo "</table>";
    
    // 5. 동시 접속 시뮬레이션
    echo "<h2>5. 동시 접속 시뮬레이션 (50명)</h2>";
    echo "<p>50명이 동시에 접속할 경우:</p>";
    echo "<ul>";
    echo "<li>예상 총 쿼리 수: 50개 (각 사용자당 1개)</li>";
    echo "<li>예상 총 시간: " . number_format($avgTime * 50, 2) . " ms (" . number_format($avgTime * 50 / 1000, 2) . " 초)</li>";
    echo "<li>리뷰가 1000개 이상일 경우: 쿼리 시간이 더 길어질 수 있음</li>";
    echo "</ul>";
    
    if ($avgTime > 100) {
        echo "<p style='color: red;'>⚠️ 쿼리 실행 시간이 100ms를 초과합니다. 인덱스 최적화가 필요합니다.</p>";
    } elseif ($avgTime > 50) {
        echo "<p style='color: orange;'>⚠️ 쿼리 실행 시간이 50ms를 초과합니다. 인덱스 추가를 권장합니다.</p>";
    } else {
        echo "<p style='color: green;'>✓ 쿼리 실행 시간이 양호합니다.</p>";
    }
    
    // 6. 권장 사항
    echo "<h2>6. 권장 사항</h2>";
    echo "<ul>";
    echo "<li><strong>복합 인덱스 추가</strong>: (product_id, product_type, status) 복합 인덱스를 추가하면 쿼리 성능이 크게 향상됩니다.</li>";
    echo "<li><strong>캐싱 고려</strong>: 리뷰 평균은 자주 변경되지 않으므로, Redis나 Memcached를 사용한 캐싱을 고려할 수 있습니다.</li>";
    echo "<li><strong>통계 테이블 활용</strong>: 현재 통계 테이블이 있지만 사용하지 않고 있습니다. 실시간 업데이트 방식으로 변경하면 성능이 향상됩니다.</li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>오류 발생: " . htmlspecialchars($e->getMessage()) . "</p>";
}
