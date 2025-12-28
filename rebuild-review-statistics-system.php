<?php
/**
 * 리뷰 통계 시스템 재구축 스크립트
 * 1. 통계 테이블 재생성
 * 2. 트리거 재생성
 * 3. 기존 리뷰 데이터로 통계 재계산
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
    <title>리뷰 통계 시스템 재구축</title>
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
    </style>
</head>
<body>
    <div class="container">
        <h1>리뷰 통계 시스템 재구축</h1>
        
        <?php
        $pdo = getDBConnection();
        if (!$pdo) {
            echo '<div class="error">데이터베이스 연결 실패</div>';
            exit;
        }
        
        $action = $_GET['action'] ?? 'info';
        
        // 1. 현재 상태 확인
        echo "<h2>1. 현재 상태 확인</h2>";
        
        // 통계 테이블 존재 확인
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'product_review_statistics'");
            $tableExists = $stmt->rowCount() > 0;
            
            if ($tableExists) {
                echo '<div class="info">✓ 통계 테이블이 존재합니다.</div>';
                
                // 통계 데이터 확인
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM product_review_statistics");
                $statsCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                echo '<div class="info">통계 데이터: ' . number_format($statsCount) . '개 상품</div>';
            } else {
                echo '<div class="warning">⚠️ 통계 테이블이 없습니다. 재생성이 필요합니다.</div>';
            }
        } catch (PDOException $e) {
            echo '<div class="error">테이블 확인 실패: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        
        // 트리거 확인
        try {
            $stmt = $pdo->query("
                SELECT TRIGGER_NAME, EVENT_MANIPULATION
                FROM information_schema.TRIGGERS
                WHERE TRIGGER_SCHEMA = DATABASE()
                AND TRIGGER_NAME LIKE 'trg_update_review_statistics%'
            ");
            $triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($triggers)) {
                echo '<div class="warning">⚠️ 트리거가 없습니다. 재생성이 필요합니다.</div>';
            } else {
                echo '<div class="success">✓ 트리거 ' . count($triggers) . '개가 존재합니다.</div>';
                foreach ($triggers as $trigger) {
                    echo '<div class="info">- ' . htmlspecialchars($trigger['TRIGGER_NAME']) . ' (' . htmlspecialchars($trigger['EVENT_MANIPULATION']) . ')</div>';
                }
            }
        } catch (PDOException $e) {
            echo '<div class="error">트리거 확인 실패: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        
        // 실제 리뷰 데이터 확인
        try {
            $stmt = $pdo->query("
                SELECT 
                    COUNT(*) as total_reviews,
                    COUNT(DISTINCT product_id) as product_count
                FROM product_reviews
                WHERE status = 'approved'
            ");
            $reviewData = $stmt->fetch(PDO::FETCH_ASSOC);
            echo '<div class="info">승인된 리뷰: ' . number_format($reviewData['total_reviews']) . '개 (상품 ' . number_format($reviewData['product_count']) . '개)</div>';
        } catch (PDOException $e) {
            echo '<div class="error">리뷰 데이터 확인 실패: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        
        // 2. 통계 테이블 재생성
        if ($action === 'rebuild_table') {
            echo "<h2>2. 통계 테이블 재생성</h2>";
            
            try {
                // 기존 트리거 삭제
                $pdo->exec("DROP TRIGGER IF EXISTS `trg_update_review_statistics_on_insert`");
                $pdo->exec("DROP TRIGGER IF EXISTS `trg_update_review_statistics_on_update`");
                $pdo->exec("DROP TRIGGER IF EXISTS `trg_update_review_statistics_on_delete`");
                echo '<div class="success">✓ 기존 트리거 삭제 완료</div>';
                
                // 통계 테이블 삭제 및 재생성
                $pdo->exec("DROP TABLE IF EXISTS `product_review_statistics`");
                echo '<div class="success">✓ 기존 통계 테이블 삭제 완료</div>';
                
                $createTableSql = "
                CREATE TABLE `product_review_statistics` (
                    `product_id` INT(11) UNSIGNED NOT NULL PRIMARY KEY,
                    `total_rating_sum` DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '전체 별점 합계',
                    `total_review_count` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '전체 리뷰 개수',
                    `kindness_rating_sum` DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '친절해요 합계',
                    `kindness_review_count` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '친절해요 리뷰 개수',
                    `speed_rating_sum` DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '개통/설치 빨라요 합계',
                    `speed_review_count` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '개통/설치 빨라요 리뷰 개수',
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    KEY `idx_updated_at` (`updated_at`),
                    CONSTRAINT `fk_product_statistics` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='상품별 리뷰 통계'
                ";
                
                $pdo->exec($createTableSql);
                echo '<div class="success">✓ 통계 테이블 재생성 완료</div>';
                
                // SQL 파일에서 트리거 생성
                $triggerSql = file_get_contents(__DIR__ . '/database/redesign_review_statistics_system.sql');
                // DELIMITER 제거하고 실행
                $triggerSql = preg_replace('/DELIMITER \$\$.*?DELIMITER ;/s', '', $triggerSql);
                $triggerSql = preg_replace('/DELIMITER \$\$/', '', $triggerSql);
                $triggerSql = preg_replace('/DELIMITER ;/', '', $triggerSql);
                
                // 트리거별로 분리하여 실행
                $triggers = [
                    'INSERT' => "CREATE TRIGGER `trg_update_review_statistics_on_insert`
                    AFTER INSERT ON `product_reviews`
                    FOR EACH ROW
                    BEGIN
                        IF NEW.status = 'approved' THEN
                            INSERT INTO `product_review_statistics` 
                                (`product_id`, `total_rating_sum`, `total_review_count`)
                            VALUES (NEW.product_id, NEW.rating, 1)
                            ON DUPLICATE KEY UPDATE
                                `total_rating_sum` = `total_rating_sum` + NEW.rating,
                                `total_review_count` = `total_review_count` + 1,
                                `updated_at` = NOW();
                            
                            IF NEW.product_type IN ('internet', 'mvno', 'mno') THEN
                                IF NEW.kindness_rating IS NOT NULL AND NEW.kindness_rating > 0 THEN
                                    UPDATE `product_review_statistics`
                                    SET 
                                        `kindness_rating_sum` = `kindness_rating_sum` + NEW.kindness_rating,
                                        `kindness_review_count` = `kindness_review_count` + 1,
                                        `updated_at` = NOW()
                                    WHERE product_id = NEW.product_id;
                                END IF;
                                
                                IF NEW.speed_rating IS NOT NULL AND NEW.speed_rating > 0 THEN
                                    UPDATE `product_review_statistics`
                                    SET 
                                        `speed_rating_sum` = `speed_rating_sum` + NEW.speed_rating,
                                        `speed_review_count` = `speed_review_count` + 1,
                                        `updated_at` = NOW()
                                    WHERE product_id = NEW.product_id;
                                END IF;
                            END IF;
                        END IF;
                    END",
                    
                    'UPDATE' => "CREATE TRIGGER `trg_update_review_statistics_on_update`
                    AFTER UPDATE ON `product_reviews`
                    FOR EACH ROW
                    BEGIN
                        IF OLD.status = 'approved' THEN
                            UPDATE `product_review_statistics`
                            SET 
                                `total_rating_sum` = GREATEST(`total_rating_sum` - OLD.rating, 0),
                                `total_review_count` = GREATEST(`total_review_count` - 1, 0),
                                `updated_at` = NOW()
                            WHERE product_id = OLD.product_id;
                            
                            IF OLD.product_type IN ('internet', 'mvno', 'mno') THEN
                                IF OLD.kindness_rating IS NOT NULL AND OLD.kindness_rating > 0 THEN
                                    UPDATE `product_review_statistics`
                                    SET 
                                        `kindness_rating_sum` = GREATEST(`kindness_rating_sum` - OLD.kindness_rating, 0),
                                        `kindness_review_count` = GREATEST(`kindness_review_count` - 1, 0),
                                        `updated_at` = NOW()
                                    WHERE product_id = OLD.product_id;
                                END IF;
                                
                                IF OLD.speed_rating IS NOT NULL AND OLD.speed_rating > 0 THEN
                                    UPDATE `product_review_statistics`
                                    SET 
                                        `speed_rating_sum` = GREATEST(`speed_rating_sum` - OLD.speed_rating, 0),
                                        `speed_review_count` = GREATEST(`speed_review_count` - 1, 0),
                                        `updated_at` = NOW()
                                    WHERE product_id = OLD.product_id;
                                END IF;
                            END IF;
                        END IF;
                        
                        IF NEW.status = 'approved' THEN
                            INSERT INTO `product_review_statistics` 
                                (`product_id`, `total_rating_sum`, `total_review_count`)
                            VALUES (NEW.product_id, NEW.rating, 1)
                            ON DUPLICATE KEY UPDATE
                                `total_rating_sum` = `total_rating_sum` + NEW.rating,
                                `total_review_count` = `total_review_count` + 1,
                                `updated_at` = NOW();
                            
                            IF NEW.product_type IN ('internet', 'mvno', 'mno') THEN
                                IF NEW.kindness_rating IS NOT NULL AND NEW.kindness_rating > 0 THEN
                                    UPDATE `product_review_statistics`
                                    SET 
                                        `kindness_rating_sum` = `kindness_rating_sum` + NEW.kindness_rating,
                                        `kindness_review_count` = `kindness_review_count` + 1,
                                        `updated_at` = NOW()
                                    WHERE product_id = NEW.product_id;
                                END IF;
                                
                                IF NEW.speed_rating IS NOT NULL AND NEW.speed_rating > 0 THEN
                                    UPDATE `product_review_statistics`
                                    SET 
                                        `speed_rating_sum` = `speed_rating_sum` + NEW.speed_rating,
                                        `speed_review_count` = `speed_review_count` + 1,
                                        `updated_at` = NOW()
                                    WHERE product_id = NEW.product_id;
                                END IF;
                            END IF;
                        END IF;
                    END",
                    
                    'DELETE' => "CREATE TRIGGER `trg_update_review_statistics_on_delete`
                    AFTER DELETE ON `product_reviews`
                    FOR EACH ROW
                    BEGIN
                        IF OLD.status = 'approved' THEN
                            UPDATE `product_review_statistics`
                            SET 
                                `total_rating_sum` = GREATEST(`total_rating_sum` - OLD.rating, 0),
                                `total_review_count` = GREATEST(`total_review_count` - 1, 0),
                                `updated_at` = NOW()
                            WHERE product_id = OLD.product_id;
                            
                            IF OLD.product_type IN ('internet', 'mvno', 'mno') THEN
                                IF OLD.kindness_rating IS NOT NULL AND OLD.kindness_rating > 0 THEN
                                    UPDATE `product_review_statistics`
                                    SET 
                                        `kindness_rating_sum` = GREATEST(`kindness_rating_sum` - OLD.kindness_rating, 0),
                                        `kindness_review_count` = GREATEST(`kindness_review_count` - 1, 0),
                                        `updated_at` = NOW()
                                    WHERE product_id = OLD.product_id;
                                END IF;
                                
                                IF OLD.speed_rating IS NOT NULL AND OLD.speed_rating > 0 THEN
                                    UPDATE `product_review_statistics`
                                    SET 
                                        `speed_rating_sum` = GREATEST(`speed_rating_sum` - OLD.speed_rating, 0),
                                        `speed_review_count` = GREATEST(`speed_review_count` - 1, 0),
                                        `updated_at` = NOW()
                                    WHERE product_id = OLD.product_id;
                                END IF;
                            END IF;
                        END IF;
                    END"
                ];
                
                foreach ($triggers as $type => $triggerSql) {
                    try {
                        $pdo->exec($triggerSql);
                        echo '<div class="success">✓ ' . $type . ' 트리거 생성 완료</div>';
                    } catch (PDOException $e) {
                        echo '<div class="error">✗ ' . $type . ' 트리거 생성 실패: ' . htmlspecialchars($e->getMessage()) . '</div>';
                    }
                }
                
                echo '<div class="info">';
                echo '<a href="?action=recalculate" class="btn">다음: 기존 리뷰 데이터로 통계 재계산</a>';
                echo '</div>';
                
            } catch (PDOException $e) {
                echo '<div class="error">재생성 실패: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
        
        // 3. 기존 리뷰 데이터로 통계 재계산
        if ($action === 'recalculate') {
            echo "<h2>3. 기존 리뷰 데이터로 통계 재계산</h2>";
            
            try {
                // 모든 상품의 리뷰 통계 재계산
                $stmt = $pdo->query("
                    SELECT DISTINCT product_id, product_type
                    FROM product_reviews
                    WHERE status = 'approved'
                ");
                $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $successCount = 0;
                $errorCount = 0;
                
                foreach ($products as $product) {
                    try {
                        // 실제 리뷰 데이터로 통계 계산
                        $reviewStmt = $pdo->prepare("
                            SELECT 
                                COUNT(*) as count,
                                SUM(rating) as total_sum,
                                SUM(kindness_rating) as kindness_sum,
                                COUNT(kindness_rating) as kindness_count,
                                SUM(speed_rating) as speed_sum,
                                COUNT(speed_rating) as speed_count
                            FROM product_reviews
                            WHERE product_id = :product_id
                            AND product_type = :product_type
                            AND status = 'approved'
                        ");
                        $reviewStmt->execute([
                            ':product_id' => $product['product_id'],
                            ':product_type' => $product['product_type']
                        ]);
                        $reviewData = $reviewStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($reviewData && $reviewData['count'] > 0) {
                            // 통계 테이블에 저장
                            $insertStmt = $pdo->prepare("
                                INSERT INTO product_review_statistics (
                                    product_id,
                                    total_rating_sum,
                                    total_review_count,
                                    kindness_rating_sum,
                                    kindness_review_count,
                                    speed_rating_sum,
                                    speed_review_count
                                ) VALUES (
                                    :product_id,
                                    :total_rating_sum,
                                    :total_review_count,
                                    :kindness_rating_sum,
                                    :kindness_review_count,
                                    :speed_rating_sum,
                                    :speed_review_count
                                )
                                ON DUPLICATE KEY UPDATE
                                    total_rating_sum = :total_rating_sum2,
                                    total_review_count = :total_review_count2,
                                    kindness_rating_sum = :kindness_rating_sum2,
                                    kindness_review_count = :kindness_review_count2,
                                    speed_rating_sum = :speed_rating_sum2,
                                    speed_review_count = :speed_review_count2,
                                    updated_at = NOW()
                            ");
                            
                            $insertStmt->execute([
                                ':product_id' => $product['product_id'],
                                ':total_rating_sum' => $reviewData['total_sum'] ?? 0,
                                ':total_review_count' => $reviewData['count'],
                                ':kindness_rating_sum' => $reviewData['kindness_sum'] ?? 0,
                                ':kindness_review_count' => $reviewData['kindness_count'] ?? 0,
                                ':speed_rating_sum' => $reviewData['speed_sum'] ?? 0,
                                ':speed_review_count' => $reviewData['speed_count'] ?? 0,
                                ':total_rating_sum2' => $reviewData['total_sum'] ?? 0,
                                ':total_review_count2' => $reviewData['count'],
                                ':kindness_rating_sum2' => $reviewData['kindness_sum'] ?? 0,
                                ':kindness_review_count2' => $reviewData['kindness_count'] ?? 0,
                                ':speed_rating_sum2' => $reviewData['speed_sum'] ?? 0,
                                ':speed_review_count2' => $reviewData['speed_count'] ?? 0
                            ]);
                            
                            $successCount++;
                        }
                    } catch (PDOException $e) {
                        $errorCount++;
                        echo '<div class="error">상품 ID ' . $product['product_id'] . ' 재계산 실패: ' . htmlspecialchars($e->getMessage()) . '</div>';
                    }
                }
                
                echo '<div class="success">';
                echo '<strong>재계산 완료:</strong> ';
                echo "성공 {$successCount}개, 실패 {$errorCount}개";
                echo '</div>';
                
                echo '<div class="info">';
                echo '<a href="?action=verify" class="btn">다음: 통계 검증</a>';
                echo '</div>';
                
            } catch (PDOException $e) {
                echo '<div class="error">재계산 실패: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
        
        // 4. 통계 검증
        if ($action === 'verify') {
            echo "<h2>4. 통계 검증</h2>";
            
            try {
                $stmt = $pdo->query("
                    SELECT 
                        p.id as product_id,
                        p.product_type,
                        COUNT(r.id) as actual_count,
                        COALESCE(SUM(r.rating), 0) as actual_sum,
                        COALESCE(AVG(r.rating), 0) as actual_avg,
                        COALESCE(SUM(r.kindness_rating), 0) as actual_kindness_sum,
                        COUNT(r.kindness_rating) as actual_kindness_count,
                        COALESCE(SUM(r.speed_rating), 0) as actual_speed_sum,
                        COUNT(r.speed_rating) as actual_speed_count,
                        s.total_review_count as stats_count,
                        s.total_rating_sum as stats_sum,
                        s.kindness_rating_sum as stats_kindness_sum,
                        s.kindness_review_count as stats_kindness_count,
                        s.speed_rating_sum as stats_speed_sum,
                        s.speed_review_count as stats_speed_count
                    FROM products p
                    LEFT JOIN product_reviews r ON p.id = r.product_id AND r.status = 'approved'
                    LEFT JOIN product_review_statistics s ON p.id = s.product_id
                    WHERE p.product_type IN ('mvno', 'mno', 'internet')
                    GROUP BY p.id
                    HAVING actual_count > 0
                    ORDER BY p.id
                    LIMIT 20
                ");
                
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($results)) {
                    echo '<div class="info">리뷰가 있는 상품이 없습니다.</div>';
                } else {
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
                    echo '<th>친절해요 평균</th>';
                    echo '<th>개통빨라요 평균</th>';
                    echo '<th>상태</th>';
                    echo '</tr>';
                    
                    foreach ($results as $row) {
                        $actualCount = (int)$row['actual_count'];
                        $statsCount = (int)($row['stats_count'] ?? 0);
                        $actualSum = (float)$row['actual_sum'];
                        $statsSum = (float)($row['stats_sum'] ?? 0);
                        $actualAvg = $actualCount > 0 ? round($actualSum / $actualCount, 1) : 0;
                        $statsAvg = $statsCount > 0 ? round($statsSum / $statsCount, 1) : 0;
                        
                        $kindnessAvg = 0;
                        if ($row['actual_kindness_count'] > 0) {
                            $kindnessAvg = round($row['actual_kindness_sum'] / $row['actual_kindness_count'], 1);
                        }
                        
                        $speedAvg = 0;
                        if ($row['actual_speed_count'] > 0) {
                            $speedAvg = round($row['actual_speed_sum'] / $row['actual_speed_count'], 1);
                        }
                        
                        $isMismatch = ($actualCount != $statsCount) || (abs($actualSum - $statsSum) > 0.01);
                        $rowClass = $isMismatch ? 'class="mismatch"' : '';
                        
                        echo "<tr $rowClass>";
                        echo '<td>' . htmlspecialchars($row['product_id']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['product_type']) . '</td>';
                        echo '<td>' . number_format($actualCount) . '</td>';
                        echo '<td>' . number_format($statsCount) . '</td>';
                        echo '<td>' . number_format($actualSum, 1) . '</td>';
                        echo '<td>' . number_format($statsSum, 1) . '</td>';
                        echo '<td>' . number_format($actualAvg, 1) . '</td>';
                        echo '<td>' . number_format($statsAvg, 1) . '</td>';
                        echo '<td>' . number_format($kindnessAvg, 1) . '</td>';
                        echo '<td>' . number_format($speedAvg, 1) . '</td>';
                        echo '<td>' . ($isMismatch ? '<span style="color: red;">❌ 불일치</span>' : '<span style="color: green;">✅ 일치</span>') . '</td>';
                        echo '</tr>';
                    }
                    
                    echo '</table>';
                }
            } catch (PDOException $e) {
                echo '<div class="error">검증 실패: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
        
        // 작업 선택
        if ($action === 'info') {
            echo '<h2>2. 작업 선택</h2>';
            echo '<div class="warning">';
            echo '<p><strong>⚠️ 주의:</strong> 통계 테이블을 재생성하면 기존 통계 데이터가 삭제됩니다.</p>';
            echo '<p>하지만 기존 리뷰 데이터로 자동 재계산되므로 안전합니다.</p>';
            echo '</div>';
            
            echo '<div class="info">';
            echo '<p><strong>작업 순서:</strong></p>';
            echo '<ol>';
            echo '<li>통계 테이블 및 트리거 재생성</li>';
            echo '<li>기존 리뷰 데이터로 통계 재계산</li>';
            echo '<li>통계 검증</li>';
            echo '</ol>';
            echo '</div>';
            
            echo '<div style="margin-top: 20px;">';
            echo '<a href="?action=rebuild_table" class="btn btn-danger">1. 통계 테이블 및 트리거 재생성</a>';
            echo '<a href="?action=recalculate" class="btn">2. 기존 리뷰 데이터로 통계 재계산</a>';
            echo '<a href="?action=verify" class="btn">3. 통계 검증</a>';
            echo '</div>';
        }
        ?>
        
        <h2>5. 예상 결과</h2>
        <div class="info">
            <p><strong>예시:</strong> 리뷰 2개 (1점, 4점)</p>
            <ul>
                <li><strong>전체 평균:</strong> (1 + 4) / 2 = <strong>2.5</strong></li>
                <li><strong>친절해요:</strong> (1 + 4) / 2 = <strong>2.5</strong></li>
                <li><strong>개통빨라요:</strong> (1 + 4) / 2 = <strong>2.5</strong></li>
            </ul>
            <p><strong>표시 형식:</strong> 소수 첫째자리까지 (예: 2.5, 4.0, 3.7)</p>
        </div>
    </div>
</body>
</html>






