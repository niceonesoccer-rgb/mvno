<?php
/**
 * 단말기 테이블 자동 설치 스크립트
 * 브라우저에서 실행하면 테이블이 자동으로 생성됩니다.
 */

require_once __DIR__ . '/../includes/data/db-config.php';

$pdo = getDBConnection();

if (!$pdo) {
    die('데이터베이스 연결에 실패했습니다.');
}

$errors = [];
$success = [];

// SQL 파일 읽기
$sqlFile = __DIR__ . '/device_tables.sql';
if (!file_exists($sqlFile)) {
    die('device_tables.sql 파일을 찾을 수 없습니다.');
}

// SQL을 직접 정의 (더 안정적)
$createManufacturersTable = "
CREATE TABLE IF NOT EXISTS `device_manufacturers` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL COMMENT '제조사명',
    `name_en` VARCHAR(100) DEFAULT NULL COMMENT '제조사명(영문)',
    `logo_url` VARCHAR(255) DEFAULT NULL COMMENT '로고 이미지 URL',
    `display_order` INT(11) DEFAULT 0 COMMENT '표시 순서',
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active' COMMENT '상태',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_name` (`name`),
    KEY `idx_status` (`status`),
    KEY `idx_display_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='단말기 제조사';
";

$createDevicesTable = "
CREATE TABLE IF NOT EXISTS `devices` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `manufacturer_id` INT(11) UNSIGNED NOT NULL COMMENT '제조사 ID',
    `name` VARCHAR(200) NOT NULL COMMENT '단말기명',
    `storage` VARCHAR(50) DEFAULT NULL COMMENT '용량 (예: 128GB, 256GB)',
    `release_price` DECIMAL(10,2) DEFAULT NULL COMMENT '출고가',
    `color` TEXT DEFAULT NULL COMMENT '색상 (쉼표로 구분 또는 JSON)',
    `color_values` TEXT DEFAULT NULL COMMENT '색상값 (JSON 형태: [{\"name\":\"블랙\",\"value\":\"#000000\"}]',
    `model_code` VARCHAR(100) DEFAULT NULL COMMENT '모델 코드',
    `release_date` DATE DEFAULT NULL COMMENT '출시일',
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active' COMMENT '상태',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_manufacturer_id` (`manufacturer_id`),
    KEY `idx_status` (`status`),
    KEY `idx_name` (`name`),
    CONSTRAINT `fk_device_manufacturer` FOREIGN KEY (`manufacturer_id`) REFERENCES `device_manufacturers` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='단말기';
";

$statements = [
    ['sql' => $createManufacturersTable, 'name' => 'device_manufacturers 테이블'],
    ['sql' => $createDevicesTable, 'name' => 'devices 테이블']
];

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>단말기 테이블 설치</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1f2937;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #6b7280;
            margin-bottom: 30px;
        }
        .status {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .status.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }
        .status.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }
        .status.info {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #3b82f6;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin-top: 20px;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            background: #2563eb;
        }
        .btn-secondary {
            background: #6b7280;
        }
        .btn-secondary:hover {
            background: #4b5563;
        }
        pre {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>단말기 테이블 설치</h1>
        <p class="subtitle">데이터베이스에 단말기 관련 테이블을 생성합니다.</p>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
            echo '<div class="status info">테이블 생성 중...</div>';
            
            // CREATE TABLE은 DDL이므로 트랜잭션 없이 직접 실행
            try {
                
                // 각 SQL 문 실행
                foreach ($statements as $index => $stmtInfo) {
                    $statement = trim($stmtInfo['sql']);
                    $stmtName = $stmtInfo['name'];
                    
                    if (empty($statement)) continue;
                    
                    try {
                        // SQL 문 실행
                        $pdo->exec($statement);
                        $success[] = "✅ " . $stmtName . " 생성 완료";
                    } catch (PDOException $e) {
                        $errorMsg = $e->getMessage();
                        
                        // 테이블이 이미 존재하는 경우는 무시
                        if (strpos($errorMsg, 'already exists') !== false || 
                            strpos($errorMsg, 'Duplicate') !== false ||
                            (strpos($errorMsg, 'Table') !== false && strpos($errorMsg, 'already exists') !== false)) {
                            $success[] = "ℹ️ " . $stmtName . "이(가) 이미 존재합니다";
                        } else {
                            $errors[] = "❌ " . $stmtName . " 생성 오류: " . htmlspecialchars($errorMsg);
                        }
                    }
                }
                
                // 기본 제조사 데이터 삽입 (테이블이 존재하는 경우에만)
                try {
                    // 테이블 존재 확인
                    $stmt = $pdo->query("SHOW TABLES LIKE 'device_manufacturers'");
                    $tableExists = $stmt->fetch();
                    
                    if ($tableExists) {
                        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM device_manufacturers");
                        $count = $stmt->fetch();
                        
                        if ($count['cnt'] == 0) {
                            $pdo->exec("
                                INSERT INTO `device_manufacturers` (`name`, `name_en`, `display_order`, `status`) VALUES
                                ('삼성', 'Samsung', 1, 'active'),
                                ('애플', 'Apple', 2, 'active'),
                                ('샤오미', 'Xiaomi', 3, 'active'),
                                ('LG', 'LG', 4, 'active'),
                                ('구글', 'Google', 5, 'active'),
                                ('화웨이', 'Huawei', 6, 'active'),
                                ('OPPO', 'OPPO', 7, 'active'),
                                ('vivo', 'vivo', 8, 'active'),
                                ('원플러스', 'OnePlus', 9, 'active'),
                                ('노키아', 'Nokia', 10, 'active')
                                ON DUPLICATE KEY UPDATE `name` = VALUES(`name`)
                            ");
                            $success[] = "✅ 기본 제조사 데이터 삽입 완료 (10개)";
                        } else {
                            $success[] = "ℹ️ 제조사 데이터가 이미 존재합니다 (" . $count['cnt'] . "개)";
                        }
                    } else {
                        $errors[] = "❌ device_manufacturers 테이블이 생성되지 않아 데이터를 삽입할 수 없습니다.";
                    }
                } catch (PDOException $e) {
                    $errors[] = "❌ 제조사 데이터 삽입 오류: " . htmlspecialchars($e->getMessage());
                }
                
                // 테이블 확인
                $manufacturersTableExists = false;
                $devicesTableExists = false;
                
                try {
                    $stmt = $pdo->query("SHOW TABLES LIKE 'device_manufacturers'");
                    $manufacturersTableExists = $stmt->fetch() !== false;
                } catch (PDOException $e) {
                    // 무시
                }
                
                try {
                    $stmt = $pdo->query("SHOW TABLES LIKE 'devices'");
                    $devicesTableExists = $stmt->fetch() !== false;
                } catch (PDOException $e) {
                    // 무시
                }
                
                if ($manufacturersTableExists && $devicesTableExists) {
                    echo '<div class="status success">';
                    echo '<strong>✅ 설치 완료!</strong><br>';
                    echo 'devices 테이블과 device_manufacturers 테이블이 생성되었습니다.';
                    echo '</div>';
                    
                    // 성공 메시지 표시
                    if (!empty($success)) {
                        echo '<div class="status info" style="margin-top: 15px;">';
                        echo '<strong>실행 결과:</strong><br>';
                        foreach ($success as $msg) {
                            echo '• ' . $msg . '<br>';
                        }
                        echo '</div>';
                    }
                    
                    // 단말기 개수 확인
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM devices");
                        $deviceCount = $stmt->fetch();
                        echo '<div class="status info" style="margin-top: 15px;">';
                        echo '현재 등록된 단말기: <strong>' . $deviceCount['cnt'] . '개</strong>';
                        echo '</div>';
                    } catch (PDOException $e) {
                        // 테이블은 있지만 데이터가 없을 수 있음
                    }
                    
                    echo '<div style="margin-top: 30px;">';
                    echo '<a href="/MVNO/admin/settings/device-settings.php" class="btn">단말기 설정 페이지로 이동</a>';
                    echo '<a href="/MVNO/database/check_devices.php" class="btn btn-secondary" style="margin-left: 10px;">데이터베이스 상태 확인</a>';
                    echo '</div>';
                } else {
                    echo '<div class="status error">';
                    echo '<strong>❌ 설치 실패</strong><br>';
                    if (!$manufacturersTableExists) {
                        echo '• device_manufacturers 테이블이 생성되지 않았습니다.<br>';
                    }
                    if (!$devicesTableExists) {
                        echo '• devices 테이블이 생성되지 않았습니다.<br>';
                    }
                    echo '</div>';
                }
                
            } catch (PDOException $e) {
                echo '<div class="status error">';
                echo '<strong>❌ 오류 발생</strong><br>';
                echo htmlspecialchars($e->getMessage());
                echo '</div>';
            } catch (Exception $e) {
                echo '<div class="status error">';
                echo '<strong>❌ 오류 발생</strong><br>';
                echo htmlspecialchars($e->getMessage());
                echo '</div>';
            }
            
            if (!empty($errors)) {
                echo '<div class="status error">';
                echo '<strong>오류 목록:</strong><br>';
                foreach ($errors as $error) {
                    echo $error . '<br>';
                }
                echo '</div>';
            }
            
        } else {
            // 설치 전 확인
            $stmt = $pdo->query("SHOW TABLES LIKE 'devices'");
            $tableExists = $stmt->fetch();
            
            if ($tableExists) {
                echo '<div class="status info">';
                echo '<strong>ℹ️ devices 테이블이 이미 존재합니다.</strong><br>';
                echo '재설치하면 기존 데이터에 영향을 줄 수 있습니다.';
                echo '</div>';
                
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM devices");
                    $deviceCount = $stmt->fetch();
                    echo '<div class="status success">';
                    echo '현재 등록된 단말기: <strong>' . $deviceCount['cnt'] . '개</strong>';
                    echo '</div>';
                } catch (PDOException $e) {
                    // 무시
                }
                
                echo '<form method="POST" style="margin-top: 20px;">';
                echo '<button type="submit" name="install" value="1" class="btn btn-secondary">그래도 재설치</button>';
                echo '<a href="/MVNO/admin/settings/device-settings.php" class="btn" style="margin-left: 10px;">단말기 설정 페이지로 이동</a>';
                echo '</form>';
            } else {
                echo '<div class="status info">';
                echo '<strong>설치 준비 완료</strong><br>';
                echo '다음 테이블들이 생성됩니다:<br>';
                echo '• device_manufacturers (제조사 테이블)<br>';
                echo '• devices (단말기 테이블)<br>';
                echo '• 기본 제조사 데이터 (삼성, 애플, 샤오미 등)';
                echo '</div>';
                
                echo '<form method="POST" style="margin-top: 20px;">';
                echo '<button type="submit" name="install" value="1" class="btn">설치 시작</button>';
                echo '</form>';
            }
        }
        ?>
    </div>
</body>
</html>

