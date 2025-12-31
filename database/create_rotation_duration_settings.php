<?php
/**
 * 로테이션 시간 설정 테이블 생성 스크립트
 * 
 * 실행 방법: 브라우저에서 http://localhost/MVNO/database/create_rotation_duration_settings.php 접속
 */

require_once __DIR__ . '/../includes/data/db-config.php';

$pdo = getDBConnection();

if (!$pdo) {
    die('데이터베이스 연결 실패');
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>로테이션 시간 설정 테이블 생성</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
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
            color: #1e293b;
            margin-bottom: 10px;
        }
        .success {
            background: #d1fae5;
            color: #065f46;
            padding: 12px;
            border-radius: 6px;
            margin: 10px 0;
        }
        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 6px;
            margin: 10px 0;
        }
        .info {
            background: #dbeafe;
            color: #1e40af;
            padding: 12px;
            border-radius: 6px;
            margin: 10px 0;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #6366f1;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 20px;
            margin-right: 10px;
        }
        .btn:hover {
            background: #4f46e5;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>로테이션 시간 설정 테이블 생성</h1>
        
        <?php
        try {
            // rotation_duration_settings 테이블 생성
            echo "<h2>rotation_duration_settings 테이블 생성</h2>";
            
            $stmt = $pdo->query("SHOW TABLES LIKE 'rotation_duration_settings'");
            if ($stmt->rowCount() == 0) {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS `rotation_duration_settings` (
                        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                        `duration_seconds` INT(11) NOT NULL COMMENT '로테이션 시간(초)',
                        `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '활성화 여부',
                        `display_order` INT(11) NOT NULL DEFAULT 0 COMMENT '표시 순서',
                        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY (`id`),
                        UNIQUE KEY `unique_duration` (`duration_seconds`),
                        KEY `idx_is_active` (`is_active`),
                        KEY `idx_display_order` (`display_order`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
                    COMMENT='로테이션 시간 설정'
                ");
                
                echo "<div class='success'>✓ rotation_duration_settings 테이블 생성 완료</div>";
                
                // 초기 데이터 삽입 (30초)
                $pdo->exec("
                    INSERT INTO `rotation_duration_settings` (`duration_seconds`, `is_active`, `display_order`) 
                    VALUES (30, 1, 1)
                ");
                echo "<div class='success'>✓ 초기 로테이션 시간(30초) 추가 완료</div>";
            } else {
                echo "<div class='info'>✓ rotation_duration_settings 테이블이 이미 존재합니다.</div>";
                
                // 초기 데이터 확인
                $stmt = $pdo->query("SELECT COUNT(*) FROM rotation_duration_settings");
                $count = $stmt->fetchColumn();
                if ($count == 0) {
                    $pdo->exec("
                        INSERT INTO `rotation_duration_settings` (`duration_seconds`, `is_active`, `display_order`) 
                        VALUES (30, 1, 1)
                    ");
                    echo "<div class='success'>✓ 초기 로테이션 시간(30초) 추가 완료</div>";
                }
            }
            
            echo "<div class='success'><strong>✅ 모든 작업이 완료되었습니다!</strong></div>";
            
        } catch (Exception $e) {
            echo "<div class='error'><strong>❌ 오류 발생:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        }
        ?>
        
        <a href="../admin/advertisement/duration-settings.php" class="btn">로테이션 시간 설정 페이지로 이동</a>
        <a href="../admin/advertisement/prices.php" class="btn">광고 가격 설정 페이지로 이동</a>
    </div>
</body>
</html>
