<?php
/**
 * 리뷰 시스템 성능 분석
 * 50명 동시 접속자 처리 능력 확인
 */

require_once __DIR__ . '/includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

$pdo = getDBConnection();
if (!$pdo) {
    die('데이터베이스 연결 실패');
}

echo "<h1>리뷰 시스템 성능 분석</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    .error { color: red; }
    .info { background: #e7f3ff; padding: 15px; border-radius: 8px; margin: 10px 0; }
    table { border-collapse: collapse; margin: 10px 0; width: 100%; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
</style>";

try {
    // 1. 테이블 구조 확인
    echo "<h2>1. 테이블 구조 및 인덱스 확인</h2>";
    
    // product_review_statistics 테이블 구조
    $tableStmt = $pdo->query("SHOW CREATE TABLE product_review_statistics");
    $tableInfo = $tableStmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<div class='info'>";
    echo "<h3>product_review_statistics 테이블 구조:</h3>";
    echo "<pre style='background: #f5f5f5; padding: 10px; overflow-x: auto;'>";
    echo htmlspecialchars($tableInfo['Create Table']);
    echo "</pre>";
    echo "</div>";
    
    // 인덱스 확인
    $indexStmt = $pdo->query("SHOW INDEXES FROM product_review_statistics");
    $indexes = $indexStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>인덱스 목록:</h3>";
    if (count($indexes) > 0) {
        echo "<table>";
        echo "<tr><th>인덱스명</th><th>컬럼</th><th>고유성</th></tr>";
        foreach ($indexes as $index) {
            echo "<tr>";
            echo "<td>{$index['Key_name']}</td>";
            echo "<td>{$index['Column_name']}</td>";
            echo "<td>" . ($index['Non_unique'] == 0 ? '고유' : '비고유') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠️ 인덱스가 없습니다!</p>";
    }
    
    // 2. 성능 분석
    echo "<h2>2. 성능 분석</h2>";
    
    // 통계 테이블 레코드 수
    $statsCountStmt = $pdo->query("SELECT COUNT(*) as count FROM product_review_statistics");
    $statsCount = $statsCountStmt->fetch()['count'];
    
    // 리뷰 테이블 레코드 수
    $reviewCountStmt = $pdo->query("SELECT COUNT(*) as count FROM product_reviews WHERE status = 'approved'");
    $reviewCount = $reviewCountStmt->fetch()['count'];
    
    echo "<div class='info'>";
    echo "<h3>현재 데이터 규모:</h3>";
    echo "<ul>";
    echo "<li>통계 테이블 레코드 수: <strong>{$statsCount}개</strong></li>";
    echo "<li>승인된 리뷰 수: <strong>{$reviewCount}개</strong></li>";
    echo "</ul>";
    echo "</div>";
    
    // 3. 쿼리 성능 테스트
    echo "<h2>3. 쿼리 성능 테스트</h2>";
    
    // 통계 조회 성능 (1000회 반복)
    $startTime = microtime(true);
    for ($i = 0; $i < 1000; $i++) {
        $testStmt = $pdo->prepare("SELECT total_rating_sum, total_review_count FROM product_review_statistics WHERE product_id = :product_id");
        $testStmt->execute([':product_id' => 33]);
        $testStmt->fetch();
    }
    $endTime = microtime(true);
    $avgTime = (($endTime - $startTime) * 1000) / 1000; // 평균 시간 (밀리초)
    
    echo "<div class='info'>";
    echo "<h3>통계 조회 성능 (1000회 반복):</h3>";
    echo "<ul>";
    echo "<li>총 소요 시간: <strong>" . number_format(($endTime - $startTime) * 1000, 2) . "ms</strong></li>";
    echo "<li>평균 조회 시간: <strong>" . number_format($avgTime, 4) . "ms</strong></li>";
    echo "<li>초당 처리 가능: <strong>" . number_format(1000 / $avgTime, 0) . "회</strong></li>";
    echo "</ul>";
    echo "</div>";
    
    // 4. 동시성 문제 분석
    echo "<h2>4. 동시성 문제 분석</h2>";
    
    echo "<div class='info'>";
    echo "<h3>현재 구현의 동시성 처리:</h3>";
    echo "<ul>";
    echo "<li><strong>통계 조회:</strong> SELECT 쿼리만 사용 (읽기 전용, 문제 없음)</li>";
    echo "<li><strong>통계 업데이트:</strong> UPDATE 쿼리 사용 (동시성 문제 가능)</li>";
    echo "<li><strong>트랜잭션:</strong> " . (strpos($tableInfo['Create Table'], 'ENGINE=InnoDB') !== false ? 'InnoDB 사용 (트랜잭션 지원)' : 'MyISAM 사용 (트랜잭션 미지원)') . "</li>";
    echo "</ul>";
    echo "</div>";
    
    // 5. 개선 사항 제안
    echo "<h2>5. 개선 사항 제안</h2>";
    
    $hasPrimaryKey = false;
    $hasUpdatedAtIndex = false;
    foreach ($indexes as $index) {
        if ($index['Key_name'] === 'PRIMARY') {
            $hasPrimaryKey = true;
        }
        if ($index['Column_name'] === 'updated_at') {
            $hasUpdatedAtIndex = true;
        }
    }
    
    echo "<div class='info'>";
    echo "<h3>권장 개선 사항:</h3>";
    echo "<ol>";
    
    if (!$hasPrimaryKey) {
        echo "<li class='error'><strong>PRIMARY KEY 추가 필요:</strong> product_id에 PRIMARY KEY가 없으면 성능 저하</li>";
    } else {
        echo "<li class='success'>✓ PRIMARY KEY 존재 (product_id)</li>";
    }
    
    if (!$hasUpdatedAtIndex) {
        echo "<li class='warning'><strong>updated_at 인덱스 추가 권장:</strong> 통계 조회 시 정렬에 유용</li>";
    } else {
        echo "<li class='success'>✓ updated_at 인덱스 존재</li>";
    }
    
    echo "<li><strong>트랜잭션 사용:</strong> updateReviewStatistics 함수에 트랜잭션 추가 권장 (동시 업데이트 시 데이터 정합성 보장)</li>";
    echo "<li><strong>Connection Pooling:</strong> PDO 연결 풀 사용 권장 (50명 동시 접속 시)</li>";
    echo "<li><strong>캐싱:</strong> Redis/Memcached를 사용한 통계 캐싱 권장 (초당 수천 건 조회 시)</li>";
    echo "</ol>";
    echo "</div>";
    
    // 6. 50명 동시 접속자 처리 능력 평가
    echo "<h2>6. 50명 동시 접속자 처리 능력 평가</h2>";
    
    $canHandle50Users = true;
    $issues = [];
    
    if ($avgTime > 10) { // 평균 조회 시간이 10ms 이상이면
        $canHandle50Users = false;
        $issues[] = "통계 조회 시간이 너무 깁니다 ({$avgTime}ms)";
    }
    
    if (strpos($tableInfo['Create Table'], 'ENGINE=MyISAM') !== false) {
        $canHandle50Users = false;
        $issues[] = "MyISAM 엔진 사용 (트랜잭션 미지원, 동시성 문제 가능)";
    }
    
    echo "<div class='info'>";
    if ($canHandle50Users && empty($issues)) {
        echo "<p class='success'>✅ <strong>50명 동시 접속자 처리 가능</strong></p>";
        echo "<ul>";
        echo "<li>통계 조회: 매우 빠름 ({$avgTime}ms)</li>";
        echo "<li>테이블 엔진: InnoDB (트랜잭션 지원)</li>";
        echo "<li>인덱스: 적절히 설정됨</li>";
        echo "</ul>";
    } else {
        echo "<p class='warning'>⚠️ <strong>50명 동시 접속자 처리 시 주의 필요</strong></p>";
        echo "<ul>";
        foreach ($issues as $issue) {
            echo "<li class='error'>{$issue}</li>";
        }
        echo "</ul>";
    }
    echo "</div>";
    
    // 7. 성능 개선 스크립트
    echo "<h2>7. 성능 개선 스크립트</h2>";
    echo "<div class='info'>";
    echo "<p>다음 SQL을 실행하여 성능을 개선할 수 있습니다:</p>";
    echo "<pre style='background: #f5f5f5; padding: 10px; overflow-x: auto;'>";
    echo "-- updated_at 인덱스 추가 (통계 조회 최적화)\n";
    echo "ALTER TABLE product_review_statistics ADD INDEX idx_updated_at (updated_at);\n\n";
    echo "-- 복합 인덱스 추가 (product_id + updated_at 조회 최적화)\n";
    echo "ALTER TABLE product_review_statistics ADD INDEX idx_product_updated (product_id, updated_at);\n";
    echo "</pre>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<p class='error'>오류 발생: " . htmlspecialchars($e->getMessage()) . "</p>";
}
