<?php
/**
 * 광고 분석 테이블 강제 생성 스크립트
 * 테이블이 존재하더라도 IF NOT EXISTS를 사용하여 안전하게 생성
 * 
 * 실행 방법:
 * http://localhost/MVNO/database/force_create_advertisement_analytics_tables.php
 */

require_once __DIR__ . '/../includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>광고 분석 테이블 강제 생성</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; }
        .success { color: #4CAF50; background: #e8f5e9; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { color: #f44336; background: #ffebee; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { color: #2196F3; background: #e3f2fd; padding: 10px; border-radius: 4px; margin: 10px 0; }
    </style>
</head>
<body>
<div class='container'>
<h1>광고 분석 테이블 강제 생성</h1>";

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception("데이터베이스 연결 실패");
    }
    
    // SQL 직접 정의 (더 안정적)
    $createTables = [
        'advertisement_impressions' => "
            CREATE TABLE IF NOT EXISTS `advertisement_impressions` (
                `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `advertisement_id` INT(11) UNSIGNED NOT NULL COMMENT '광고 ID (rotation_advertisements.id)',
                `product_id` INT(11) UNSIGNED NOT NULL COMMENT '상품 ID',
                `seller_id` VARCHAR(50) NOT NULL COMMENT '판매자 ID',
                `product_type` ENUM('mvno', 'mno', 'internet', 'mno_sim') NOT NULL COMMENT '상품 타입',
                `user_id` VARCHAR(50) DEFAULT NULL COMMENT '사용자 ID (로그인한 경우)',
                `ip_address` VARCHAR(45) DEFAULT NULL COMMENT 'IP 주소',
                `user_agent` VARCHAR(500) DEFAULT NULL COMMENT 'User Agent',
                `referrer` VARCHAR(500) DEFAULT NULL COMMENT '리퍼러 URL',
                `page_url` VARCHAR(500) DEFAULT NULL COMMENT '페이지 URL',
                `device_type` ENUM('desktop', 'mobile', 'tablet', 'unknown') DEFAULT 'unknown' COMMENT '기기 타입',
                `browser` VARCHAR(100) DEFAULT NULL COMMENT '브라우저',
                `os` VARCHAR(100) DEFAULT NULL COMMENT '운영체제',
                `session_id` VARCHAR(100) DEFAULT NULL COMMENT '세션 ID',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '노출 시간',
                PRIMARY KEY (`id`),
                KEY `idx_advertisement_id` (`advertisement_id`),
                KEY `idx_product_id` (`product_id`),
                KEY `idx_seller_id` (`seller_id`),
                KEY `idx_product_type` (`product_type`),
                KEY `idx_user_id` (`user_id`),
                KEY `idx_created_at` (`created_at`),
                KEY `idx_advertisement_created` (`advertisement_id`, `created_at`),
                CONSTRAINT `fk_impression_advertisement` FOREIGN KEY (`advertisement_id`) REFERENCES `rotation_advertisements` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_impression_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='광고 노출 추적'
        ",
        'advertisement_clicks' => "
            CREATE TABLE IF NOT EXISTS `advertisement_clicks` (
                `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `advertisement_id` INT(11) UNSIGNED NOT NULL COMMENT '광고 ID (rotation_advertisements.id)',
                `product_id` INT(11) UNSIGNED NOT NULL COMMENT '상품 ID',
                `seller_id` VARCHAR(50) NOT NULL COMMENT '판매자 ID',
                `product_type` ENUM('mvno', 'mno', 'internet', 'mno_sim') NOT NULL COMMENT '상품 타입',
                `user_id` VARCHAR(50) DEFAULT NULL COMMENT '사용자 ID (로그인한 경우)',
                `ip_address` VARCHAR(45) DEFAULT NULL COMMENT 'IP 주소',
                `user_agent` VARCHAR(500) DEFAULT NULL COMMENT 'User Agent',
                `referrer` VARCHAR(500) DEFAULT NULL COMMENT '리퍼러 URL',
                `page_url` VARCHAR(500) DEFAULT NULL COMMENT '클릭한 페이지 URL',
                `target_url` VARCHAR(500) DEFAULT NULL COMMENT '클릭한 목적지 URL',
                `device_type` ENUM('desktop', 'mobile', 'tablet', 'unknown') DEFAULT 'unknown' COMMENT '기기 타입',
                `browser` VARCHAR(100) DEFAULT NULL COMMENT '브라우저',
                `os` VARCHAR(100) DEFAULT NULL COMMENT '운영체제',
                `session_id` VARCHAR(100) DEFAULT NULL COMMENT '세션 ID',
                `click_type` ENUM('direct', 'detail', 'apply', 'other') DEFAULT 'direct' COMMENT '클릭 유형',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '클릭 시간',
                PRIMARY KEY (`id`),
                KEY `idx_advertisement_id` (`advertisement_id`),
                KEY `idx_product_id` (`product_id`),
                KEY `idx_seller_id` (`seller_id`),
                KEY `idx_product_type` (`product_type`),
                KEY `idx_user_id` (`user_id`),
                KEY `idx_created_at` (`created_at`),
                KEY `idx_advertisement_created` (`advertisement_id`, `created_at`),
                KEY `idx_click_type` (`click_type`),
                CONSTRAINT `fk_click_advertisement` FOREIGN KEY (`advertisement_id`) REFERENCES `rotation_advertisements` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_click_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='광고 클릭 추적'
        ",
        'advertisement_analytics' => "
            CREATE TABLE IF NOT EXISTS `advertisement_analytics` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `advertisement_id` INT(11) UNSIGNED NOT NULL COMMENT '광고 ID (rotation_advertisements.id)',
                `product_id` INT(11) UNSIGNED NOT NULL COMMENT '상품 ID',
                `seller_id` VARCHAR(50) NOT NULL COMMENT '판매자 ID',
                `product_type` ENUM('mvno', 'mno', 'internet', 'mno_sim') NOT NULL COMMENT '상품 타입',
                `stat_date` DATE NOT NULL COMMENT '통계 날짜',
                `stat_hour` TINYINT(2) UNSIGNED DEFAULT NULL COMMENT '통계 시간 (0-23, NULL이면 일별 통계)',
                `impression_count` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '노출 횟수',
                `click_count` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '클릭 횟수',
                `unique_impressions` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '고유 노출 수 (IP 기준)',
                `unique_clicks` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '고유 클릭 수 (IP 기준)',
                `ctr` DECIMAL(5,4) DEFAULT 0.0000 COMMENT '클릭률 (Click Through Rate) = 클릭/노출',
                `desktop_impressions` INT(11) UNSIGNED DEFAULT 0 COMMENT '데스크톱 노출',
                `mobile_impressions` INT(11) UNSIGNED DEFAULT 0 COMMENT '모바일 노출',
                `tablet_impressions` INT(11) UNSIGNED DEFAULT 0 COMMENT '태블릿 노출',
                `desktop_clicks` INT(11) UNSIGNED DEFAULT 0 COMMENT '데스크톱 클릭',
                `mobile_clicks` INT(11) UNSIGNED DEFAULT 0 COMMENT '모바일 클릭',
                `tablet_clicks` INT(11) UNSIGNED DEFAULT 0 COMMENT '태블릿 클릭',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '업데이트 시간',
                PRIMARY KEY (`id`),
                UNIQUE KEY `unique_ad_stat` (`advertisement_id`, `stat_date`, `stat_hour`),
                KEY `idx_advertisement_id` (`advertisement_id`),
                KEY `idx_product_id` (`product_id`),
                KEY `idx_seller_id` (`seller_id`),
                KEY `idx_product_type` (`product_type`),
                KEY `idx_stat_date` (`stat_date`),
                KEY `idx_stat_hour` (`stat_hour`),
                CONSTRAINT `fk_analytics_advertisement` FOREIGN KEY (`advertisement_id`) REFERENCES `rotation_advertisements` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_analytics_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='광고 통계 집계'
        "
    ];
    
    $successCount = 0;
    $errorCount = 0;
    $errors = [];
    
    foreach ($createTables as $tableName => $sql) {
        try {
            // 테이블 생성 실행
            $pdo->exec($sql);
            
            echo "<div class='success'><strong>✅ 테이블 '{$tableName}' 생성 완료</strong></div>";
            $successCount++;
            
        } catch (PDOException $e) {
            $errorMsg = "테이블 '{$tableName}' 생성 실패: " . $e->getMessage();
            $errors[] = $errorMsg;
            echo "<div class='error'><strong>❌ {$errorMsg}</strong></div>";
            
            // 외래키 제약조건 오류인 경우, 외래키 없이 재시도
            if (strpos($e->getMessage(), 'foreign key constraint') !== false || 
                strpos($e->getMessage(), 'Cannot add foreign key constraint') !== false) {
                echo "<div class='info'>외래키 제약조건 오류 감지. 외래키 없이 재시도합니다...</div>";
                
                try {
                    // 외래키 제약조건 제거한 SQL
                    $sqlWithoutFK = preg_replace('/,\s*CONSTRAINT\s+`[^`]+`\s+FOREIGN\s+KEY[^,)]+\)[^,)]*\)/i', '', $sql);
                    $pdo->exec($sqlWithoutFK);
                    
                    echo "<div class='success'><strong>✅ 테이블 '{$tableName}' 생성 완료 (외래키 제약조건 없이)</strong></div>";
                    $successCount++;
                    $errorCount--; // 오류 카운트 감소
                    array_pop($errors); // 오류 목록에서 제거
                } catch (PDOException $e2) {
                    echo "<div class='error'><strong>❌ 재시도 실패: " . $e2->getMessage() . "</strong></div>";
                }
            }
            
            $errorCount++;
        }
    }
    
    // 결과 요약
    echo "<h2>생성 결과</h2>";
    echo "<div class='info'>";
    echo "<strong>성공:</strong> {$successCount}개 테이블<br>";
    echo "<strong>실패:</strong> {$errorCount}개 테이블";
    echo "</div>";
    
    if (!empty($errors)) {
        echo "<h2>오류 상세</h2>";
        foreach ($errors as $error) {
            echo "<div class='error'>{$error}</div>";
        }
    }
    
    // 테이블 존재 확인
    echo "<h2>테이블 존재 확인</h2>";
    $tableNames = ['advertisement_impressions', 'advertisement_clicks', 'advertisement_analytics'];
    foreach ($tableNames as $tableName) {
        try {
            $checkStmt = $pdo->query("SHOW TABLES LIKE '{$tableName}'");
            if ($checkStmt->rowCount() > 0) {
                echo "<div class='success'>✅ 테이블 '{$tableName}' 존재</div>";
            } else {
                echo "<div class='error'>❌ 테이블 '{$tableName}' 없음</div>";
            }
        } catch (PDOException $e) {
            echo "<div class='error'>❌ 테이블 '{$tableName}' 확인 실패: " . $e->getMessage() . "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div class='error'><strong>❌ 오류 발생: " . htmlspecialchars($e->getMessage()) . "</strong></div>";
}

echo "</div></body></html>";
