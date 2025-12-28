<?php
/**
 * 리뷰 통계 UPDATE/DELETE 트리거 추가 스크립트
 * 리뷰 수정/삭제 시 통계 자동 업데이트
 */

require_once __DIR__ . '/includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>리뷰 통계 트리거 추가</title>
    <style>
        body {
            font-family: 'Malgun Gothic', sans-serif;
            max-width: 1200px;
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
        .info {
            color: #3b82f6;
            background: #dbeafe;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        pre {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
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
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #6366f1;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 10px 5px;
        }
        .btn:hover {
            background: #4f46e5;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>리뷰 통계 UPDATE/DELETE 트리거 추가</h1>
        
        <?php
        $pdo = getDBConnection();
        if (!$pdo) {
            echo '<div class="error">데이터베이스 연결 실패</div>';
            exit;
        }
        
        // 1. 기존 트리거 확인
        echo "<h2>1. 기존 트리거 확인</h2>";
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
                echo '<div class="info">기존 트리거가 없습니다.</div>';
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
            }
        } catch (PDOException $e) {
            echo '<div class="error">트리거 확인 실패: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        
        // 2. UPDATE 트리거 생성
        echo "<h2>2. UPDATE 트리거 생성</h2>";
        try {
            $pdo->exec("DROP TRIGGER IF EXISTS `trg_update_review_statistics_on_update`");
            
            // PDO는 DELIMITER를 지원하지 않으므로, 트리거를 여러 문으로 나누어 실행
            // 또는 exec() 대신 prepare()를 사용하여 실행
            $updateTriggerSql = "CREATE TRIGGER `trg_update_review_statistics_on_update`
            AFTER UPDATE ON `product_reviews`
            FOR EACH ROW
            BEGIN
                -- 기존 리뷰가 승인된 상태였다면 통계에서 제거
                IF OLD.status = 'approved' THEN
                    -- 기본 별점 통계 제거
                    UPDATE `product_review_statistics`
                    SET 
                        `total_rating_sum` = GREATEST(`total_rating_sum` - OLD.rating, 0),
                        `total_review_count` = GREATEST(`total_review_count` - 1, 0),
                        `updated_at` = NOW()
                    WHERE product_id = OLD.product_id;
                    
                    -- 항목별 통계 제거 (인터넷, MVNO, MNO)
                    IF OLD.product_type IN ('internet', 'mvno', 'mno') THEN
                        IF OLD.kindness_rating IS NOT NULL THEN
                            UPDATE `product_review_statistics`
                            SET 
                                `kindness_rating_sum` = GREATEST(`kindness_rating_sum` - OLD.kindness_rating, 0),
                                `kindness_review_count` = GREATEST(`kindness_review_count` - 1, 0),
                                `updated_at` = NOW()
                            WHERE product_id = OLD.product_id;
                        END IF;
                        
                        IF OLD.speed_rating IS NOT NULL THEN
                            UPDATE `product_review_statistics`
                            SET 
                                `speed_rating_sum` = GREATEST(`speed_rating_sum` - OLD.speed_rating, 0),
                                `speed_review_count` = GREATEST(`speed_review_count` - 1, 0),
                                `updated_at` = NOW()
                            WHERE product_id = OLD.product_id;
                        END IF;
                    END IF;
                END IF;
                
                -- 새 리뷰가 승인된 상태라면 통계에 추가
                IF NEW.status = 'approved' THEN
                    -- 기본 별점 통계 추가
                    INSERT INTO `product_review_statistics` 
                        (`product_id`, `total_rating_sum`, `total_review_count`)
                    VALUES (NEW.product_id, NEW.rating, 1)
                    ON DUPLICATE KEY UPDATE
                        `total_rating_sum` = `total_rating_sum` + NEW.rating,
                        `total_review_count` = `total_review_count` + 1,
                        `updated_at` = NOW();
                    
                    -- 항목별 통계 추가 (인터넷, MVNO, MNO)
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
            
            $pdo->exec($updateTriggerSql);
            echo '<div class="success">✓ UPDATE 트리거 생성 완료</div>';
        } catch (PDOException $e) {
            echo '<div class="error">UPDATE 트리거 생성 실패: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        
        // 3. DELETE 트리거 생성
        echo "<h2>3. DELETE 트리거 생성</h2>";
        try {
            $pdo->exec("DROP TRIGGER IF EXISTS `trg_update_review_statistics_on_delete`");
            
            $deleteTriggerSql = "
            CREATE TRIGGER `trg_update_review_statistics_on_delete`
            AFTER DELETE ON `product_reviews`
            FOR EACH ROW
            BEGIN
                -- 삭제된 리뷰가 승인된 상태였다면 통계에서 제거
                IF OLD.status = 'approved' THEN
                    -- 기본 별점 통계 제거
                    UPDATE `product_review_statistics`
                    SET 
                        `total_rating_sum` = GREATEST(`total_rating_sum` - OLD.rating, 0),
                        `total_review_count` = GREATEST(`total_review_count` - 1, 0),
                        `updated_at` = NOW()
                    WHERE product_id = OLD.product_id;
                    
                    -- 항목별 통계 제거 (인터넷, MVNO, MNO)
                    IF OLD.product_type IN ('internet', 'mvno', 'mno') THEN
                        IF OLD.kindness_rating IS NOT NULL THEN
                            UPDATE `product_review_statistics`
                            SET 
                                `kindness_rating_sum` = GREATEST(`kindness_rating_sum` - OLD.kindness_rating, 0),
                                `kindness_review_count` = GREATEST(`kindness_review_count` - 1, 0),
                                `updated_at` = NOW()
                            WHERE product_id = OLD.product_id;
                        END IF;
                        
                        IF OLD.speed_rating IS NOT NULL THEN
                            UPDATE `product_review_statistics`
                            SET 
                                `speed_rating_sum` = GREATEST(`speed_rating_sum` - OLD.speed_rating, 0),
                                `speed_review_count` = GREATEST(`speed_review_count` - 1, 0),
                                `updated_at` = NOW()
                            WHERE product_id = OLD.product_id;
                        END IF;
                    END IF;
                END IF;
            END
            ";
            
            $pdo->exec($deleteTriggerSql);
            echo '<div class="success">✓ DELETE 트리거 생성 완료</div>';
        } catch (PDOException $e) {
            echo '<div class="error">DELETE 트리거 생성 실패: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        
        // 4. 최종 트리거 확인
        echo "<h2>4. 최종 트리거 확인</h2>";
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
            $finalTriggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($finalTriggers)) {
                echo '<div class="error">트리거가 생성되지 않았습니다.</div>';
            } else {
                echo '<table>';
                echo '<tr><th>트리거명</th><th>이벤트</th><th>테이블</th><th>타이밍</th></tr>';
                foreach ($finalTriggers as $trigger) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($trigger['TRIGGER_NAME']) . '</td>';
                    echo '<td>' . htmlspecialchars($trigger['EVENT_MANIPULATION']) . '</td>';
                    echo '<td>' . htmlspecialchars($trigger['EVENT_OBJECT_TABLE']) . '</td>';
                    echo '<td>' . htmlspecialchars($trigger['ACTION_TIMING']) . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
                
                echo '<div class="success">✓ 모든 트리거가 정상적으로 생성되었습니다.</div>';
                echo '<div class="info">이제 리뷰를 수정하거나 삭제하면 통계가 자동으로 업데이트됩니다.</div>';
            }
        } catch (PDOException $e) {
            echo '<div class="error">트리거 확인 실패: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
        
        <h2>5. 다음 단계</h2>
        <div class="info">
            <p><strong>추가 작업:</strong></p>
            <ul>
                <li>기존 리뷰 데이터의 통계 정합성 검증</li>
                <li>성능 모니터링 (트리거 실행 시간 확인)</li>
                <li>테스트: 리뷰 수정/삭제 후 통계 확인</li>
            </ul>
        </div>
    </div>
</body>
</html>






