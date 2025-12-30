<?php
/**
 * 리뷰 통계 트리거 수정 스크립트
 * MVNO와 MNO도 항목별 통계를 업데이트하도록 수정
 * UPDATE 트리거가 있는지 확인하고 제거
 */

require_once __DIR__ . '/includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

$pdo = getDBConnection();
if (!$pdo) {
    die('데이터베이스 연결 실패');
}

echo "<h1>리뷰 통계 트리거 수정</h1>";

try {
    // 1. 기존 트리거 확인
    echo "<h2>1. 기존 트리거 확인</h2>";
    $stmt = $pdo->query("
        SELECT 
            TRIGGER_NAME,
            EVENT_MANIPULATION
        FROM information_schema.TRIGGERS
        WHERE EVENT_OBJECT_TABLE = 'product_reviews'
        AND TRIGGER_SCHEMA = DATABASE()
    ");
    $triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<ul>";
    foreach ($triggers as $trigger) {
        echo "<li>" . htmlspecialchars($trigger['TRIGGER_NAME']) . " - " . htmlspecialchars($trigger['EVENT_MANIPULATION']) . "</li>";
    }
    echo "</ul>";
    
    // 2. UPDATE 트리거가 있는지 확인하고 제거
    echo "<h2>2. UPDATE 트리거 확인 및 제거</h2>";
    $updateTriggers = array_filter($triggers, function($t) {
        return $t['EVENT_MANIPULATION'] === 'UPDATE';
    });
    
    if (!empty($updateTriggers)) {
        echo "<p style='color: red;'><strong>경고: UPDATE 트리거가 발견되었습니다! 이것이 통계 변경의 원인일 수 있습니다.</strong></p>";
        foreach ($updateTriggers as $trigger) {
            $triggerName = $trigger['TRIGGER_NAME'];
            echo "<p>트리거 제거 중: " . htmlspecialchars($triggerName) . "</p>";
            try {
                $pdo->exec("DROP TRIGGER IF EXISTS `$triggerName`");
                echo "<p style='color: green;'>✓ 트리거 제거 완료: " . htmlspecialchars($triggerName) . "</p>";
            } catch (PDOException $e) {
                echo "<p style='color: red;'>오류: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
    } else {
        echo "<p style='color: green;'>✓ UPDATE 트리거가 없습니다. (정상)</p>";
    }
    
    // 3. 기존 INSERT 트리거 제거
    echo "<h2>3. 기존 INSERT 트리거 제거</h2>";
    $pdo->exec("DROP TRIGGER IF EXISTS `trg_update_review_statistics_on_insert`");
    echo "<p style='color: green;'>✓ 기존 트리거 제거 완료</p>";
    
    // 4. 새로운 트리거 생성 (MVNO, MNO, Internet 모두 항목별 통계 지원)
    echo "<h2>4. 새로운 트리거 생성</h2>";
    
    // DELIMITER는 PDO에서 직접 사용할 수 없으므로 분리
    $triggerSql = "
    CREATE TRIGGER `trg_update_review_statistics_on_insert`
    AFTER INSERT ON `product_reviews`
    FOR EACH ROW
    BEGIN
        -- 승인된 리뷰만 통계에 반영
        IF NEW.status = 'approved' THEN
            -- 기본 별점 통계 업데이트
            INSERT INTO `product_review_statistics` 
                (`product_id`, `total_rating_sum`, `total_review_count`)
            VALUES (NEW.product_id, NEW.rating, 1)
            ON DUPLICATE KEY UPDATE
                `total_rating_sum` = `total_rating_sum` + NEW.rating,
                `total_review_count` = `total_review_count` + 1,
                `updated_at` = NOW();
            
            -- 인터넷, MVNO, MNO 상품의 경우 항목별 통계도 업데이트
            IF NEW.product_type IN ('internet', 'mvno', 'mno') THEN
                IF NEW.kindness_rating IS NOT NULL THEN
                    UPDATE `product_review_statistics`
                    SET 
                        `kindness_rating_sum` = COALESCE(`kindness_rating_sum`, 0) + NEW.kindness_rating,
                        `kindness_review_count` = COALESCE(`kindness_review_count`, 0) + 1,
                        `updated_at` = NOW()
                    WHERE product_id = NEW.product_id;
                END IF;
                
                IF NEW.speed_rating IS NOT NULL THEN
                    UPDATE `product_review_statistics`
                    SET 
                        `speed_rating_sum` = COALESCE(`speed_rating_sum`, 0) + NEW.speed_rating,
                        `speed_review_count` = COALESCE(`speed_review_count`, 0) + 1,
                        `updated_at` = NOW()
                    WHERE product_id = NEW.product_id;
                END IF;
            END IF;
        END IF;
    END
    ";
    
    $pdo->exec($triggerSql);
    echo "<p style='color: green;'>✓ 새로운 트리거 생성 완료</p>";
    echo "<p><strong>변경 사항:</strong></p>";
    echo "<ul>";
    echo "<li>MVNO와 MNO도 항목별 통계(kindness_rating, speed_rating)를 업데이트하도록 수정</li>";
    echo "<li>UPDATE 트리거는 생성하지 않음 (리뷰 수정 시 통계 변경 방지)</li>";
    echo "</ul>";
    
    // 5. 최종 트리거 확인
    echo "<h2>5. 최종 트리거 확인</h2>";
    $stmt = $pdo->query("
        SELECT 
            TRIGGER_NAME,
            EVENT_MANIPULATION,
            ACTION_STATEMENT
        FROM information_schema.TRIGGERS
        WHERE EVENT_OBJECT_TABLE = 'product_reviews'
        AND TRIGGER_SCHEMA = DATABASE()
    ");
    $finalTriggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($finalTriggers)) {
        echo "<p style='color: red;'>트리거가 없습니다!</p>";
    } else {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr><th>트리거 이름</th><th>이벤트</th><th>동작</th></tr>";
        foreach ($finalTriggers as $trigger) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($trigger['TRIGGER_NAME']) . "</td>";
            echo "<td>" . htmlspecialchars($trigger['EVENT_MANIPULATION']) . "</td>";
            echo "<td><pre style='max-width: 600px; overflow: auto; font-size: 11px;'>" . htmlspecialchars($trigger['ACTION_STATEMENT']) . "</pre></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<hr>";
    echo "<p style='color: green;'><strong>✓ 트리거 수정 완료!</strong></p>";
    echo "<p><strong>다음 단계:</strong></p>";
    echo "<ol>";
    echo "<li>리뷰를 수정해보고 통계가 변경되지 않는지 확인하세요.</li>";
    echo "<li>서버 로그에서 'ERROR updateProductReview' 메시지가 나타나지 않는지 확인하세요.</li>";
    echo "<li>새로운 리뷰를 작성하면 MVNO/MNO도 항목별 통계가 정상적으로 업데이트되는지 확인하세요.</li>";
    echo "</ol>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>오류 발생: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}









