<?php
/**
 * rotation_duration_settings 테이블 삭제 스크립트
 * 로테이션 시간이 1가지만 필요하므로 테이블 대신 system_settings에 단일 값으로 저장
 * 
 * 실행 방법: 브라우저에서 http://localhost/MVNO/database/drop_rotation_duration_settings_table.php 접속
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
    <title>rotation_duration_settings 테이블 삭제</title>
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
        .warning {
            background: #fef3c7;
            color: #92400e;
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
        <h1>rotation_duration_settings 테이블 삭제</h1>
        
        <?php
        try {
            // 1. 테이블 존재 확인
            $stmt = $pdo->query("SHOW TABLES LIKE 'rotation_duration_settings'");
            $tableExists = $stmt->rowCount() > 0;
            
            if ($tableExists) {
                // 2. 기존 로테이션 시간 값 확인 (활성화된 것 중 첫 번째)
                $stmt = $pdo->query("SELECT duration_seconds FROM rotation_duration_settings WHERE is_active = 1 ORDER BY display_order ASC, duration_seconds ASC LIMIT 1");
                $existingDuration = $stmt->fetch(PDO::FETCH_COLUMN);
                $durationValue = $existingDuration ? intval($existingDuration) : 30; // 기본값 30초
                
                echo "<div class='info'>✓ 기존 로테이션 시간 값: {$durationValue}초</div>";
                
                // 3. system_settings 테이블 확인 및 생성
                $stmt = $pdo->query("SHOW TABLES LIKE 'system_settings'");
                if ($stmt->rowCount() == 0) {
                    $pdo->exec("
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
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='시스템 설정'
                    ");
                    echo "<div class='success'>✓ system_settings 테이블 생성 완료</div>";
                }
                
                // 4. system_settings에 로테이션 시간 값 저장
                $stmt = $pdo->prepare("
                    INSERT INTO system_settings (setting_key, setting_value, setting_type, description)
                    VALUES ('advertisement_rotation_duration', :value1, 'number', '광고 로테이션 시간(초)')
                    ON DUPLICATE KEY UPDATE
                        setting_value = :value2,
                        updated_at = NOW()
                ");
                $stmt->execute([
                    ':value1' => strval($durationValue),
                    ':value2' => strval($durationValue)
                ]);
                echo "<div class='success'>✓ system_settings에 로테이션 시간({$durationValue}초) 저장 완료</div>";
                
                // 5. rotation_duration_settings 테이블 삭제
                $pdo->exec("DROP TABLE IF EXISTS `rotation_duration_settings`");
                echo "<div class='success'>✓ rotation_duration_settings 테이블 삭제 완료</div>";
                
                echo "<div class='success'><strong>✅ 모든 작업이 완료되었습니다!</strong></div>";
            } else {
                echo "<div class='info'>✓ rotation_duration_settings 테이블이 이미 존재하지 않습니다.</div>";
                
                // system_settings에 기본값 설정 (없는 경우)
                $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'advertisement_rotation_duration'");
                $stmt->execute();
                $existingValue = $stmt->fetchColumn();
                
                if (!$existingValue) {
                    // system_settings 테이블 확인 및 생성
                    $stmt = $pdo->query("SHOW TABLES LIKE 'system_settings'");
                    if ($stmt->rowCount() == 0) {
                        $pdo->exec("
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
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='시스템 설정'
                        ");
                    }
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO system_settings (setting_key, setting_value, setting_type, description)
                        VALUES ('advertisement_rotation_duration', :value, 'number', '광고 로테이션 시간(초)')
                    ");
                    $stmt->execute([':value' => '30']);
                    echo "<div class='success'>✓ system_settings에 기본 로테이션 시간(30초) 저장 완료</div>";
                }
                
                echo "<div class='success'><strong>✅ 모든 작업이 완료되었습니다!</strong></div>";
            }
            
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
