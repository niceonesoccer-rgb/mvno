<?php
/**
 * 리뷰 관련 트리거 확인 스크립트
 * 리뷰 수정 시 통계가 변경되는 원인 확인
 */

require_once __DIR__ . '/includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

$pdo = getDBConnection();
if (!$pdo) {
    die('데이터베이스 연결 실패');
}

echo "<h1>리뷰 관련 트리거 확인</h1>";

// 1. product_reviews 테이블에 대한 모든 트리거 확인
echo "<h2>1. product_reviews 테이블 트리거</h2>";
try {
    $stmt = $pdo->query("
        SELECT 
            TRIGGER_NAME,
            EVENT_MANIPULATION,
            EVENT_OBJECT_TABLE,
            ACTION_STATEMENT
        FROM information_schema.TRIGGERS
        WHERE EVENT_OBJECT_TABLE = 'product_reviews'
        AND TRIGGER_SCHEMA = DATABASE()
    ");
    $triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($triggers)) {
        echo "<p>트리거가 없습니다.</p>";
    } else {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr><th>트리거 이름</th><th>이벤트</th><th>테이블</th><th>동작</th></tr>";
        foreach ($triggers as $trigger) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($trigger['TRIGGER_NAME']) . "</td>";
            echo "<td>" . htmlspecialchars($trigger['EVENT_MANIPULATION']) . "</td>";
            echo "<td>" . htmlspecialchars($trigger['EVENT_OBJECT_TABLE']) . "</td>";
            echo "<td><pre style='max-width: 600px; overflow: auto;'>" . htmlspecialchars($trigger['ACTION_STATEMENT']) . "</pre></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>오류: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// 2. product_review_statistics 테이블 구조 확인
echo "<h2>2. product_review_statistics 테이블 구조</h2>";
try {
    $stmt = $pdo->query("DESCRIBE product_review_statistics");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>컬럼명</th><th>타입</th><th>NULL</th><th>키</th><th>기본값</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>테이블이 없거나 오류: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// 3. 샘플 상품의 통계 확인
echo "<h2>3. 샘플 상품 통계 (최근 5개)</h2>";
try {
    $stmt = $pdo->query("
        SELECT 
            s.product_id,
            s.total_rating_sum,
            s.total_review_count,
            s.kindness_rating_sum,
            s.kindness_review_count,
            s.speed_rating_sum,
            s.speed_review_count,
            CASE 
                WHEN s.total_review_count > 0 THEN ROUND(s.total_rating_sum / s.total_review_count, 1)
                ELSE 0
            END as avg_rating,
            CASE 
                WHEN s.kindness_review_count > 0 THEN ROUND(s.kindness_rating_sum / s.kindness_review_count, 1)
                ELSE 0
            END as avg_kindness,
            CASE 
                WHEN s.speed_review_count > 0 THEN ROUND(s.speed_rating_sum / s.speed_review_count, 1)
                ELSE 0
            END as avg_speed
        FROM product_review_statistics s
        ORDER BY s.updated_at DESC
        LIMIT 5
    ");
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($stats)) {
        echo "<p>통계 데이터가 없습니다.</p>";
    } else {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr>";
        echo "<th>상품 ID</th>";
        echo "<th>총별점 합계</th>";
        echo "<th>리뷰 개수</th>";
        echo "<th>평균 별점</th>";
        echo "<th>친절해요 합계</th>";
        echo "<th>친절해요 개수</th>";
        echo "<th>친절해요 평균</th>";
        echo "<th>개통 빨라요 합계</th>";
        echo "<th>개통 빨라요 개수</th>";
        echo "<th>개통 빨라요 평균</th>";
        echo "</tr>";
        foreach ($stats as $stat) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($stat['product_id']) . "</td>";
            echo "<td>" . htmlspecialchars($stat['total_rating_sum']) . "</td>";
            echo "<td>" . htmlspecialchars($stat['total_review_count']) . "</td>";
            echo "<td>" . htmlspecialchars($stat['avg_rating']) . "</td>";
            echo "<td>" . htmlspecialchars($stat['kindness_rating_sum'] ?? '0') . "</td>";
            echo "<td>" . htmlspecialchars($stat['kindness_review_count'] ?? '0') . "</td>";
            echo "<td>" . htmlspecialchars($stat['avg_kindness']) . "</td>";
            echo "<td>" . htmlspecialchars($stat['speed_rating_sum'] ?? '0') . "</td>";
            echo "<td>" . htmlspecialchars($stat['speed_review_count'] ?? '0') . "</td>";
            echo "<td>" . htmlspecialchars($stat['avg_speed']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>오류: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// 4. 최근 리뷰 수정 이력 확인
echo "<h2>4. 최근 리뷰 수정 이력 (최근 10개)</h2>";
try {
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
            created_at,
            updated_at
        FROM product_reviews
        WHERE updated_at != created_at
        ORDER BY updated_at DESC
        LIMIT 10
    ");
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($reviews)) {
        echo "<p>수정된 리뷰가 없습니다.</p>";
    } else {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr>";
        echo "<th>리뷰 ID</th>";
        echo "<th>상품 ID</th>";
        echo "<th>사용자 ID</th>";
        echo "<th>타입</th>";
        echo "<th>별점</th>";
        echo "<th>친절해요</th>";
        echo "<th>개통 빨라요</th>";
        echo "<th>상태</th>";
        echo "<th>작성일</th>";
        echo "<th>수정일</th>";
        echo "</tr>";
        foreach ($reviews as $review) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($review['id']) . "</td>";
            echo "<td>" . htmlspecialchars($review['product_id']) . "</td>";
            echo "<td>" . htmlspecialchars($review['user_id']) . "</td>";
            echo "<td>" . htmlspecialchars($review['product_type']) . "</td>";
            echo "<td>" . htmlspecialchars($review['rating']) . "</td>";
            echo "<td>" . htmlspecialchars($review['kindness_rating'] ?? '-') . "</td>";
            echo "<td>" . htmlspecialchars($review['speed_rating'] ?? '-') . "</td>";
            echo "<td>" . htmlspecialchars($review['status']) . "</td>";
            echo "<td>" . htmlspecialchars($review['created_at']) . "</td>";
            echo "<td>" . htmlspecialchars($review['updated_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>오류: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<p><strong>디버깅 방법:</strong></p>";
echo "<ol>";
echo "<li>리뷰를 수정한 후 이 페이지를 새로고침하세요.</li>";
echo "<li>서버 로그 파일(error_log)에서 'DEBUG updateProductReview'로 시작하는 로그를 확인하세요.</li>";
echo "<li>통계 테이블 값이 변경되었다면 'ERROR updateProductReview' 로그를 확인하세요.</li>";
echo "<li>트리거가 UPDATE 시에도 작동하는지 확인하세요.</li>";
echo "</ol>";







