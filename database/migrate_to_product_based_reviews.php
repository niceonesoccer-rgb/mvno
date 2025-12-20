<?php
/**
 * 상품별 리뷰 시스템으로 마이그레이션
 * 기존 통합 리뷰 → 상품별 리뷰 전환
 * 
 * 실행 방법:
 * 1. 브라우저에서 접속: http://localhost/MVNO/database/migrate_to_product_based_reviews.php
 * 2. 또는 CLI에서 실행: php migrate_to_product_based_reviews.php
 */

require_once __DIR__ . '/../includes/data/db-config.php';

$pdo = getDBConnection();

if (!$pdo) {
    die('데이터베이스 연결에 실패했습니다.');
}

// HTML 출력 시작
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>상품별 리뷰 시스템 마이그레이션</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 10px;
        }
        .step {
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border-left: 4px solid #4CAF50;
        }
        .step h2 {
            margin-top: 0;
            color: #4CAF50;
        }
        .success {
            color: #4CAF50;
            font-weight: bold;
        }
        .error {
            color: #f44336;
            font-weight: bold;
        }
        .warning {
            color: #ff9800;
            font-weight: bold;
        }
        .info {
            color: #2196F3;
        }
        button {
            background: #4CAF50;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 5px;
        }
        button:hover {
            background: #45a049;
        }
        button.danger {
            background: #f44336;
        }
        button.danger:hover {
            background: #da190b;
        }
        pre {
            background: #f4f4f4;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>상품별 리뷰 시스템 마이그레이션</h1>
        
        <?php
        $errors = [];
        $success = [];
        
        // POST 요청 처리
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['execute_migration'])) {
            try {
                $pdo->beginTransaction();
                
                // 1. 기존 트리거 삭제
                echo "<div class='step'><h2>1단계: 기존 트리거 삭제</h2>";
                $triggers = [
                    'trg_review_insert',
                    'trg_review_delete',
                    'trg_update_review_statistics_on_insert',
                    'trg_invalidate_rating_cache'
                ];
                
                foreach ($triggers as $trigger) {
                    try {
                        $pdo->exec("DROP TRIGGER IF EXISTS `{$trigger}`");
                        echo "<p class='success'>✓ 트리거 삭제: {$trigger}</p>";
                    } catch (PDOException $e) {
                        echo "<p class='info'>ℹ 트리거 없음: {$trigger}</p>";
                    }
                }
                echo "</div>";
                
                // 2. 기존 테이블 삭제
                echo "<div class='step'><h2>2단계: 기존 테이블 삭제</h2>";
                
                // 기존 리뷰 데이터 확인
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM product_reviews");
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $reviewCount = $result['count'] ?? 0;
                    echo "<p class='warning'>⚠ 기존 리뷰 개수: " . number_format($reviewCount) . "개 (삭제됩니다)</p>";
                } catch (PDOException $e) {
                    echo "<p class='info'>ℹ 기존 리뷰 테이블 없음</p>";
                }
                
                // 테이블 삭제
                $tables = ['product_reviews', 'product_review_statistics'];
                foreach ($tables as $table) {
                    try {
                        $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
                        echo "<p class='success'>✓ 테이블 삭제: {$table}</p>";
                    } catch (PDOException $e) {
                        echo "<p class='error'>✗ 테이블 삭제 실패: {$table} - " . $e->getMessage() . "</p>";
                        $errors[] = $e->getMessage();
                    }
                }
                echo "</div>";
                
                // 3. 새로운 상품별 리뷰 테이블 생성
                echo "<div class='step'><h2>3단계: 새로운 상품별 리뷰 테이블 생성</h2>";
                
                $createReviewsTable = "
                CREATE TABLE `product_reviews` (
                    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `product_id` INT(11) UNSIGNED NOT NULL COMMENT '상품 ID (상품별 리뷰)',
                    `user_id` VARCHAR(50) NOT NULL COMMENT '작성자 user_id (users.user_id)',
                    `product_type` ENUM('mvno', 'mno', 'internet') NOT NULL COMMENT '상품 타입',
                    `rating` TINYINT(1) UNSIGNED NOT NULL COMMENT '평점 (1-5)',
                    `kindness_rating` TINYINT(1) UNSIGNED DEFAULT NULL COMMENT '친절해요 평점 (1-5, 인터넷용)',
                    `speed_rating` TINYINT(1) UNSIGNED DEFAULT NULL COMMENT '설치 빨라요 평점 (1-5, 인터넷용)',
                    `title` VARCHAR(200) DEFAULT NULL COMMENT '리뷰 제목',
                    `content` TEXT NOT NULL COMMENT '리뷰 내용',
                    `application_id` INT(11) UNSIGNED DEFAULT NULL COMMENT '신청 ID (구매 인증용)',
                    `order_number` VARCHAR(50) DEFAULT NULL COMMENT '주문번호',
                    `is_verified` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '구매 인증 여부',
                    `helpful_count` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '도움됨 수',
                    `status` ENUM('pending', 'approved', 'rejected', 'deleted') NOT NULL DEFAULT 'pending' COMMENT '리뷰 상태',
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '작성일시',
                    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
                    PRIMARY KEY (`id`),
                    KEY `idx_product_id` (`product_id`),
                    KEY `idx_user_id` (`user_id`),
                    KEY `idx_product_type` (`product_type`),
                    KEY `idx_status` (`status`),
                    KEY `idx_created_at` (`created_at`),
                    KEY `idx_product_status` (`product_id`, `status`),
                    KEY `idx_product_status_created` (`product_id`, `status`, `created_at`),
                    KEY `idx_product_rating` (`product_id`, `rating`),
                    KEY `idx_application_id` (`application_id`),
                    CONSTRAINT `fk_review_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='상품별 리뷰 (상품별로 독립적으로 관리)';
                ";
                
                $pdo->exec($createReviewsTable);
                echo "<p class='success'>✓ 상품별 리뷰 테이블 생성 완료</p>";
                echo "</div>";
                
                // 4. 통계 테이블 생성
                echo "<div class='step'><h2>4단계: 리뷰 통계 테이블 생성</h2>";
                
                $createStatsTable = "
                CREATE TABLE `product_review_statistics` (
                    `product_id` INT(11) UNSIGNED NOT NULL,
                    `total_rating_sum` DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '별점 합계',
                    `total_review_count` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '리뷰 개수',
                    `kindness_rating_sum` DECIMAL(12,2) DEFAULT 0 COMMENT '친절해요 합계',
                    `kindness_review_count` INT(11) UNSIGNED DEFAULT 0 COMMENT '친절해요 리뷰 개수',
                    `speed_rating_sum` DECIMAL(12,2) DEFAULT 0 COMMENT '설치 빨라요 합계',
                    `speed_review_count` INT(11) UNSIGNED DEFAULT 0 COMMENT '설치 빨라요 리뷰 개수',
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`product_id`),
                    KEY `idx_updated_at` (`updated_at`),
                    CONSTRAINT `fk_product_statistics` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='상품별 리뷰 통계 (Immutable: 추가만, 수정/삭제 반영 안함)';
                ";
                
                $pdo->exec($createStatsTable);
                echo "<p class='success'>✓ 리뷰 통계 테이블 생성 완료</p>";
                echo "</div>";
                
                // 5. 트리거 생성
                echo "<div class='step'><h2>5단계: 통계 자동 업데이트 트리거 생성</h2>";
                
                $createTrigger = "
                DELIMITER $$
                CREATE TRIGGER `trg_update_review_statistics_on_insert`
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
                        
                        IF NEW.product_type = 'internet' THEN
                            IF NEW.kindness_rating IS NOT NULL THEN
                                UPDATE `product_review_statistics`
                                SET 
                                    `kindness_rating_sum` = `kindness_rating_sum` + NEW.kindness_rating,
                                    `kindness_review_count` = `kindness_review_count` + 1,
                                    `updated_at` = NOW()
                                WHERE product_id = NEW.product_id;
                            END IF;
                            
                            IF NEW.speed_rating IS NOT NULL THEN
                                UPDATE `product_review_statistics`
                                SET 
                                    `speed_rating_sum` = `speed_rating_sum` + NEW.speed_rating,
                                    `speed_review_count` = `speed_review_count` + 1,
                                    `updated_at` = NOW()
                                WHERE product_id = NEW.product_id;
                            END IF;
                        END IF;
                    END IF;
                END$$
                DELIMITER ;
                ";
                
                // DELIMITER는 PDO에서 직접 사용할 수 없으므로 분리
                $pdo->exec("
                CREATE TRIGGER `trg_update_review_statistics_on_insert`
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
                        
                        IF NEW.product_type = 'internet' THEN
                            IF NEW.kindness_rating IS NOT NULL THEN
                                UPDATE `product_review_statistics`
                                SET 
                                    `kindness_rating_sum` = `kindness_rating_sum` + NEW.kindness_rating,
                                    `kindness_review_count` = `kindness_review_count` + 1,
                                    `updated_at` = NOW()
                                WHERE product_id = NEW.product_id;
                            END IF;
                            
                            IF NEW.speed_rating IS NOT NULL THEN
                                UPDATE `product_review_statistics`
                                SET 
                                    `speed_rating_sum` = `speed_rating_sum` + NEW.speed_rating,
                                    `speed_review_count` = `speed_review_count` + 1,
                                    `updated_at` = NOW()
                                WHERE product_id = NEW.product_id;
                            END IF;
                        END IF;
                    END IF;
                END
                ");
                
                echo "<p class='success'>✓ 통계 자동 업데이트 트리거 생성 완료</p>";
                echo "</div>";
                
                // 6. 시스템 설정 테이블 생성
                echo "<div class='step'><h2>6단계: 시스템 설정 테이블 생성</h2>";
                
                $createSettingsTable = "
                CREATE TABLE IF NOT EXISTS `system_settings` (
                    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `setting_key` VARCHAR(100) NOT NULL COMMENT '설정 키',
                    `setting_value` TEXT NOT NULL COMMENT '설정 값',
                    `setting_type` ENUM('string', 'number', 'boolean', 'json') NOT NULL DEFAULT 'string',
                    `description` VARCHAR(255) DEFAULT NULL COMMENT '설명',
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `idx_setting_key` (`setting_key`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='시스템 설정';
                ";
                
                $pdo->exec($createSettingsTable);
                
                // 초기 설정값 삽입
                $settings = [
                    ['review_display_mode_mvno', 'product', 'MVNO 리뷰 표시 방식: product(상품별) 또는 seller_grouped(판매자별 통합)'],
                    ['review_display_mode_mno', 'product', 'MNO 리뷰 표시 방식'],
                    ['review_display_mode_internet', 'product', '인터넷 리뷰 표시 방식']
                ];
                
                $stmt = $pdo->prepare("
                    INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) 
                    VALUES (?, ?, 'string', ?)
                    ON DUPLICATE KEY UPDATE
                        `setting_value` = VALUES(`setting_value`),
                        `updated_at` = NOW()
                ");
                
                foreach ($settings as $setting) {
                    $stmt->execute($setting);
                }
                
                echo "<p class='success'>✓ 시스템 설정 테이블 생성 및 초기값 설정 완료</p>";
                echo "</div>";
                
                $pdo->commit();
                
                echo "<div class='step' style='background: #e8f5e9; border-left-color: #4CAF50;'>";
                echo "<h2 style='color: #4CAF50;'>✅ 마이그레이션 완료!</h2>";
                echo "<p class='success'>상품별 리뷰 시스템으로 성공적으로 전환되었습니다.</p>";
                echo "<ul>";
                echo "<li>✓ 기존 리뷰 데이터 삭제 완료</li>";
                echo "<li>✓ 새로운 상품별 리뷰 테이블 생성 완료</li>";
                echo "<li>✓ 리뷰 통계 테이블 생성 완료</li>";
                echo "<li>✓ 자동 통계 업데이트 트리거 생성 완료</li>";
                echo "<li>✓ 시스템 설정 테이블 생성 완료</li>";
                echo "</ul>";
                echo "</div>";
                
            } catch (PDOException $e) {
                // 트랜잭션이 활성화되어 있는 경우에만 롤백
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                echo "<div class='step' style='background: #ffebee; border-left-color: #f44336;'>";
                echo "<h2 style='color: #f44336;'>❌ 마이그레이션 실패</h2>";
                echo "<p class='error'>오류: " . htmlspecialchars($e->getMessage()) . "</p>";
                echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
                echo "</div>";
            } catch (Exception $e) {
                // 트랜잭션이 활성화되어 있는 경우에만 롤백
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                echo "<div class='step' style='background: #ffebee; border-left-color: #f44336;'>";
                echo "<h2 style='color: #f44336;'>❌ 마이그레이션 실패</h2>";
                echo "<p class='error'>오류: " . htmlspecialchars($e->getMessage()) . "</p>";
                echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
                echo "</div>";
            }
        } else {
            // 마이그레이션 전 확인
            ?>
            <div class="step">
                <h2>⚠️ 주의사항</h2>
                <ul>
                    <li class="warning">기존 리뷰 데이터가 <strong>모두 삭제</strong>됩니다.</li>
                    <li>이 작업은 되돌릴 수 없습니다.</li>
                    <li>마이그레이션 전에 데이터베이스 백업을 권장합니다.</li>
                </ul>
            </div>
            
            <div class="step">
                <h2>마이그레이션 내용</h2>
                <ul>
                    <li>기존 통합 리뷰 테이블 삭제</li>
                    <li>새로운 상품별 리뷰 테이블 생성</li>
                    <li>리뷰 통계 테이블 생성 (Immutable 방식)</li>
                    <li>자동 통계 업데이트 트리거 생성</li>
                    <li>시스템 설정 테이블 생성</li>
                </ul>
            </div>
            
            <form method="POST" onsubmit="return confirm('정말로 마이그레이션을 실행하시겠습니까?\\n\\n기존 리뷰 데이터가 모두 삭제됩니다!');">
                <button type="submit" name="execute_migration" class="danger">
                    마이그레이션 실행
                </button>
            </form>
            <?php
        }
        ?>
    </div>
</body>
</html>

