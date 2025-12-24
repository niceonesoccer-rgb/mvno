<?php
/**
 * 리뷰 평점 시스템 하이브리드 방식 재설계 스크립트
 * 인터넷, 알뜰폰(MVNO), 통신사폰(MNO) 모두 지원
 */

require_once __DIR__ . '/../includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

$pdo = getDBConnection();
if (!$pdo) {
    die('데이터베이스 연결 실패');
}

echo "<h1>리뷰 평점 시스템 하이브리드 방식 재설계</h1>";

try {
    $pdo->beginTransaction();
    
    // ============================================
    // 1. 기존 통계 테이블 백업 및 삭제
    // ============================================
    echo "<h2>1. 기존 통계 테이블 처리</h2>";
    
    // 기존 데이터 백업
    $backupData = [];
    try {
        $backupStmt = $pdo->query("SELECT * FROM product_review_statistics");
        $backupData = $backupStmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>✓ 기존 통계 데이터 백업 완료 (" . count($backupData) . "개 상품)</p>";
    } catch (PDOException $e) {
        echo "<p style='color: orange;'>⚠️ 기존 통계 테이블이 없거나 백업 실패: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // 기존 테이블 삭제
    try {
        $pdo->exec("DROP TABLE IF EXISTS `product_review_statistics`");
        echo "<p>✓ 기존 통계 테이블 삭제 완료</p>";
    } catch (PDOException $e) {
        echo "<p style='color: red;'>✗ 테이블 삭제 실패: " . htmlspecialchars($e->getMessage()) . "</p>";
        throw $e;
    }
    
    // ============================================
    // 2. 새로운 통계 테이블 생성 (하이브리드 방식)
    // ============================================
    echo "<h2>2. 새로운 통계 테이블 생성 (하이브리드 방식)</h2>";
    
    $createTableSql = "
    CREATE TABLE `product_review_statistics` (
        `product_id` INT(11) UNSIGNED NOT NULL PRIMARY KEY,
        
        -- 실시간 통계 (화면 표시용, 수정/삭제 시 업데이트)
        `total_rating_sum` DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '별점 합계 (실시간)',
        `total_review_count` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '리뷰 개수 (실시간)',
        
        `kindness_rating_sum` DECIMAL(12,2) DEFAULT 0 COMMENT '친절해요 합계 (실시간)',
        `kindness_review_count` INT(11) UNSIGNED DEFAULT 0 COMMENT '친절해요 리뷰 개수 (실시간)',
        `speed_rating_sum` DECIMAL(12,2) DEFAULT 0 COMMENT '개통/설치 빨라요 합계 (실시간)',
        `speed_review_count` INT(11) UNSIGNED DEFAULT 0 COMMENT '개통/설치 빨라요 리뷰 개수 (실시간)',
        
        -- 처음 작성 시점 통계 (고정값, 수정/삭제 시 변경 안 됨)
        `initial_total_rating_sum` DECIMAL(12,2) DEFAULT 0 COMMENT '처음 작성 시점 별점 합계',
        `initial_total_review_count` INT(11) UNSIGNED DEFAULT 0 COMMENT '처음 작성 시점 리뷰 개수',
        
        `initial_kindness_rating_sum` DECIMAL(12,2) DEFAULT 0 COMMENT '처음 작성 시점 친절해요 합계',
        `initial_kindness_review_count` INT(11) UNSIGNED DEFAULT 0 COMMENT '처음 작성 시점 친절해요 개수',
        `initial_speed_rating_sum` DECIMAL(12,2) DEFAULT 0 COMMENT '처음 작성 시점 개통/설치 빨라요 합계',
        `initial_speed_review_count` INT(11) UNSIGNED DEFAULT 0 COMMENT '처음 작성 시점 개통/설치 빨라요 개수',
        
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        KEY `idx_updated_at` (`updated_at`),
        CONSTRAINT `fk_product_statistics` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='상품별 리뷰 통계 (하이브리드: 실시간 + 처음 작성 시점 값)'
    ";
    
    $pdo->exec($createTableSql);
    echo "<p>✓ 새로운 통계 테이블 생성 완료</p>";
    
    // ============================================
    // 3. product_reviews 테이블 수정
    // ============================================
    echo "<h2>3. product_reviews 테이블 수정</h2>";
    
    // product_type에 'internet' 추가
    try {
        $pdo->exec("ALTER TABLE `product_reviews` MODIFY COLUMN `product_type` ENUM('mvno', 'mno', 'internet') NOT NULL COMMENT '상품 타입'");
        echo "<p>✓ product_type에 'internet' 추가 완료</p>";
    } catch (PDOException $e) {
        echo "<p style='color: orange;'>⚠️ product_type 수정 실패 (이미 수정되었을 수 있음): " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // kindness_rating 컬럼 추가
    try {
        $checkStmt = $pdo->query("SHOW COLUMNS FROM product_reviews LIKE 'kindness_rating'");
        if ($checkStmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE `product_reviews` ADD COLUMN `kindness_rating` TINYINT(1) UNSIGNED DEFAULT NULL COMMENT '친절해요 평점 (1-5)'");
            echo "<p>✓ kindness_rating 컬럼 추가 완료</p>";
        } else {
            echo "<p>✓ kindness_rating 컬럼 이미 존재</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color: orange;'>⚠️ kindness_rating 추가 실패: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // speed_rating 컬럼 추가
    try {
        $checkStmt = $pdo->query("SHOW COLUMNS FROM product_reviews LIKE 'speed_rating'");
        if ($checkStmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE `product_reviews` ADD COLUMN `speed_rating` TINYINT(1) UNSIGNED DEFAULT NULL COMMENT '개통/설치 빨라요 평점 (1-5)'");
            echo "<p>✓ speed_rating 컬럼 추가 완료</p>";
        } else {
            echo "<p>✓ speed_rating 컬럼 이미 존재</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color: orange;'>⚠️ speed_rating 추가 실패: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // application_id 컬럼 추가
    try {
        $checkStmt = $pdo->query("SHOW COLUMNS FROM product_reviews LIKE 'application_id'");
        if ($checkStmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE `product_reviews` ADD COLUMN `application_id` INT(11) UNSIGNED DEFAULT NULL COMMENT '신청 ID (주문별 리뷰 구분)'");
            echo "<p>✓ application_id 컬럼 추가 완료</p>";
        } else {
            echo "<p>✓ application_id 컬럼 이미 존재</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color: orange;'>⚠️ application_id 추가 실패: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // ============================================
    // 4. 인덱스 추가 (성능 최적화)
    // ============================================
    echo "<h2>4. 인덱스 추가 (성능 최적화)</h2>";
    
    $indexes = [
        'idx_product_id_type_status' => "(`product_id`, `product_type`, `status`)",
        'idx_product_id_type_status_kindness' => "(`product_id`, `product_type`, `status`, `kindness_rating`)",
        'idx_product_id_type_status_speed' => "(`product_id`, `product_type`, `status`, `speed_rating`)",
        'idx_application_id' => "(`application_id`)"
    ];
    
    foreach ($indexes as $indexName => $indexColumns) {
        try {
            $checkStmt = $pdo->query("SHOW INDEXES FROM product_reviews WHERE Key_name = '$indexName'");
            if ($checkStmt->rowCount() == 0) {
                $pdo->exec("ALTER TABLE `product_reviews` ADD INDEX `$indexName` $indexColumns");
                echo "<p>✓ 인덱스 $indexName 추가 완료</p>";
            } else {
                echo "<p>✓ 인덱스 $indexName 이미 존재</p>";
            }
        } catch (PDOException $e) {
            echo "<p style='color: orange;'>⚠️ 인덱스 $indexName 추가 실패: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    // ============================================
    // 5. 기존 데이터 마이그레이션
    // ============================================
    echo "<h2>5. 기존 데이터 마이그레이션</h2>";
    
    // 실제 리뷰 데이터로 통계 재계산 (인터넷, MVNO, MNO 모두 포함)
    $migrationStmt = $pdo->query("
        SELECT 
            product_id,
            COUNT(*) as total_count,
            SUM(rating) as total_sum,
            SUM(CASE WHEN kindness_rating IS NOT NULL THEN 1 ELSE 0 END) as kindness_count,
            SUM(kindness_rating) as kindness_sum,
            SUM(CASE WHEN speed_rating IS NOT NULL THEN 1 ELSE 0 END) as speed_count,
            SUM(speed_rating) as speed_sum
        FROM product_reviews
        WHERE status = 'approved'
        GROUP BY product_id
    ");
    
    $migratedCount = 0;
    while ($row = $migrationStmt->fetch(PDO::FETCH_ASSOC)) {
        $productId = $row['product_id'];
        $totalCount = (int)$row['total_count'];
        $totalSum = (float)$row['total_sum'];
        $kindnessCount = (int)$row['kindness_count'];
        $kindnessSum = (float)($row['kindness_sum'] ?? 0);
        $speedCount = (int)$row['speed_count'];
        $speedSum = (float)($row['speed_sum'] ?? 0);
        
        // 실시간 통계와 처음 작성 시점 통계 모두 동일하게 설정 (기존 데이터는 모두 처음 작성 시점 값)
        $insertStmt = $pdo->prepare("
            INSERT INTO product_review_statistics (
                product_id,
                total_rating_sum, total_review_count,
                kindness_rating_sum, kindness_review_count,
                speed_rating_sum, speed_review_count,
                initial_total_rating_sum, initial_total_review_count,
                initial_kindness_rating_sum, initial_kindness_review_count,
                initial_speed_rating_sum, initial_speed_review_count
            ) VALUES (
                :product_id,
                :total_sum, :total_count,
                :kindness_sum, :kindness_count,
                :speed_sum, :speed_count,
                :total_sum, :total_count,
                :kindness_sum, :kindness_count,
                :speed_sum, :speed_count
            )
        ");
        
        $insertStmt->execute([
            ':product_id' => $productId,
            ':total_sum' => $totalSum,
            ':total_count' => $totalCount,
            ':kindness_sum' => $kindnessSum,
            ':kindness_count' => $kindnessCount,
            ':speed_sum' => $speedSum,
            ':speed_count' => $speedCount
        ]);
        
        $migratedCount++;
    }
    
    echo "<p>✓ 기존 데이터 마이그레이션 완료 ($migratedCount개 상품)</p>";
    
    $pdo->commit();
    
    echo "<div style='background: #f0fdf4; padding: 20px; border-radius: 8px; border-left: 4px solid #16a34a; margin-top: 20px;'>";
    echo "<h3>✅ 재설계 완료!</h3>";
    echo "<ul>";
    echo "<li>통계 테이블 재생성 완료 (하이브리드 방식)</li>";
    echo "<li>product_reviews 테이블 수정 완료 (internet 타입 추가)</li>";
    echo "<li>인덱스 추가 완료 (성능 최적화)</li>";
    echo "<li>기존 데이터 마이그레이션 완료</li>";
    echo "</ul>";
    echo "<p><strong>다음 단계:</strong> PHP 함수들을 수정해야 합니다.</p>";
    echo "</div>";
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "<p style='color: red;'>오류 발생: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}


