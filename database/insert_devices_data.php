<?php
/**
 * 단말기 데이터 자동 삽입 스크립트
 * 브라우저에서 실행하면 단말기 데이터가 자동으로 추가됩니다.
 */

require_once __DIR__ . '/../includes/data/db-config.php';

$pdo = getDBConnection();

if (!$pdo) {
    die('데이터베이스 연결에 실패했습니다.');
}

$errors = [];
$success = [];

// 제조사 ID 가져오기
$manufacturerIds = [];
try {
    $stmt = $pdo->query("SELECT id, name FROM device_manufacturers ORDER BY id");
    $manufacturers = $stmt->fetchAll();
    foreach ($manufacturers as $m) {
        $manufacturerIds[$m['name']] = $m['id'];
    }
} catch (PDOException $e) {
    die('제조사 데이터를 가져올 수 없습니다: ' . $e->getMessage());
}

// 단말기 데이터 정의
$devicesData = [
    // Galaxy S23 Series (2023 출시)
    ['manufacturer' => '삼성', 'name' => 'Galaxy S23', 'storage' => '256GB', 'release_price' => 1155000, 'color' => '팬텀 블랙, 그린, 라벤더, 크림', 'release_date' => '2023-02-01'],
    ['manufacturer' => '삼성', 'name' => 'Galaxy S23', 'storage' => '512GB', 'release_price' => 1353000, 'color' => '팬텀 블랙, 그린, 라벤더, 크림', 'release_date' => '2023-02-01'],
    ['manufacturer' => '삼성', 'name' => 'Galaxy S23+', 'storage' => '256GB', 'release_price' => 1397000, 'color' => '팬텀 블랙, 그린, 라벤더, 크림', 'release_date' => '2023-02-01'],
    ['manufacturer' => '삼성', 'name' => 'Galaxy S23+', 'storage' => '512GB', 'release_price' => 1595000, 'color' => '팬텀 블랙, 그린, 라벤더, 크림', 'release_date' => '2023-02-01'],
    ['manufacturer' => '삼성', 'name' => 'Galaxy S23 Ultra', 'storage' => '256GB', 'release_price' => 1599400, 'color' => '팬텀 블랙, 그린, 라벤더, 크림', 'release_date' => '2023-02-01'],
    ['manufacturer' => '삼성', 'name' => 'Galaxy S23 Ultra', 'storage' => '512GB', 'release_price' => 1720400, 'color' => '팬텀 블랙, 그린, 라벤더, 크림', 'release_date' => '2023-02-01'],
    ['manufacturer' => '삼성', 'name' => 'Galaxy S23 Ultra', 'storage' => '1TB', 'release_price' => 1984400, 'color' => '팬텀 블랙, 그린, 라벤더, 크림', 'release_date' => '2023-02-01'],
    
    // iPhone 16 Series (2024 출시)
    ['manufacturer' => '애플', 'name' => 'iPhone 16', 'storage' => '128GB', 'release_price' => 1250000, 'color' => '블랙, 화이트, 블루, 핑크, 그린', 'release_date' => '2024-09-01'],
    ['manufacturer' => '애플', 'name' => 'iPhone 16', 'storage' => '256GB', 'release_price' => 1390000, 'color' => '블랙, 화이트, 블루, 핑크, 그린', 'release_date' => '2024-09-01'],
    ['manufacturer' => '애플', 'name' => 'iPhone 16', 'storage' => '512GB', 'release_price' => 1640000, 'color' => '블랙, 화이트, 블루, 핑크, 그린', 'release_date' => '2024-09-01'],
    ['manufacturer' => '애플', 'name' => 'iPhone 16 Plus', 'storage' => '128GB', 'release_price' => 1350000, 'color' => '블랙, 화이트, 블루, 핑크, 그린', 'release_date' => '2024-09-01'],
    ['manufacturer' => '애플', 'name' => 'iPhone 16 Plus', 'storage' => '256GB', 'release_price' => 1490000, 'color' => '블랙, 화이트, 블루, 핑크, 그린', 'release_date' => '2024-09-01'],
    ['manufacturer' => '애플', 'name' => 'iPhone 16 Plus', 'storage' => '512GB', 'release_price' => 1740000, 'color' => '블랙, 화이트, 블루, 핑크, 그린', 'release_date' => '2024-09-01'],
    ['manufacturer' => '애플', 'name' => 'iPhone 16 Pro', 'storage' => '128GB', 'release_price' => 1550000, 'color' => '티타늄 블랙, 티타늄 화이트, 티타늄 블루, 내추럴 티타늄', 'release_date' => '2024-09-01'],
    ['manufacturer' => '애플', 'name' => 'iPhone 16 Pro', 'storage' => '256GB', 'release_price' => 1690000, 'color' => '티타늄 블랙, 티타늄 화이트, 티타늄 블루, 내추럴 티타늄', 'release_date' => '2024-09-01'],
    ['manufacturer' => '애플', 'name' => 'iPhone 16 Pro', 'storage' => '512GB', 'release_price' => 1940000, 'color' => '티타늄 블랙, 티타늄 화이트, 티타늄 블루, 내추럴 티타늄', 'release_date' => '2024-09-01'],
    ['manufacturer' => '애플', 'name' => 'iPhone 16 Pro', 'storage' => '1TB', 'release_price' => 2190000, 'color' => '티타늄 블랙, 티타늄 화이트, 티타늄 블루, 내추럴 티타늄', 'release_date' => '2024-09-01'],
    ['manufacturer' => '애플', 'name' => 'iPhone 16 Pro Max', 'storage' => '256GB', 'release_price' => 1900000, 'color' => '티타늄 블랙, 티타늄 화이트, 티타늄 블루, 내추럴 티타늄', 'release_date' => '2024-09-01'],
    ['manufacturer' => '애플', 'name' => 'iPhone 16 Pro Max', 'storage' => '512GB', 'release_price' => 2150000, 'color' => '티타늄 블랙, 티타늄 화이트, 티타늄 블루, 내추럴 티타늄', 'release_date' => '2024-09-01'],
    ['manufacturer' => '애플', 'name' => 'iPhone 16 Pro Max', 'storage' => '1TB', 'release_price' => 2400000, 'color' => '티타늄 블랙, 티타늄 화이트, 티타늄 블루, 내추럴 티타늄', 'release_date' => '2024-09-01'],
    
    // iPhone 15 Series (2023 출시)
    ['manufacturer' => '애플', 'name' => 'iPhone 15', 'storage' => '128GB', 'release_price' => 1250000, 'color' => '블루, 핑크, 옐로우, 그린, 블랙', 'release_date' => '2023-09-01'],
    ['manufacturer' => '애플', 'name' => 'iPhone 15', 'storage' => '256GB', 'release_price' => 1390000, 'color' => '블루, 핑크, 옐로우, 그린, 블랙', 'release_date' => '2023-09-01'],
    ['manufacturer' => '애플', 'name' => 'iPhone 15', 'storage' => '512GB', 'release_price' => 1640000, 'color' => '블루, 핑크, 옐로우, 그린, 블랙', 'release_date' => '2023-09-01'],
    
    // Xiaomi 13 Series (2023 한국 출시)
    ['manufacturer' => '샤오미', 'name' => 'Xiaomi 13', 'storage' => '256GB', 'release_price' => 1099000, 'color' => '화이트, 블랙', 'release_date' => '2023-01-01'],
    ['manufacturer' => '샤오미', 'name' => 'Xiaomi 13 Pro', 'storage' => '256GB', 'release_price' => 1399000, 'color' => '세라믹 화이트, 세라믹 블랙', 'release_date' => '2023-01-01'],
    
    // Galaxy Z Fold5 (2023년)
    ['manufacturer' => '삼성', 'name' => 'Galaxy Z Fold5', 'storage' => '256GB', 'release_price' => 2097700, 'color' => '아이스 블루, 팬텀 블랙, 크림', 'release_date' => '2023-08-11'],
    ['manufacturer' => '삼성', 'name' => 'Galaxy Z Fold5', 'storage' => '512GB', 'release_price' => 2218700, 'color' => '아이스 블루, 팬텀 블랙, 크림', 'release_date' => '2023-08-11'],
    ['manufacturer' => '삼성', 'name' => 'Galaxy Z Fold5', 'storage' => '1TB', 'release_price' => 2460700, 'color' => '아이스 블루, 팬텀 블랙, 크림', 'release_date' => '2023-08-11'],
    
    // Galaxy Z Flip5 (2023년)
    ['manufacturer' => '삼성', 'name' => 'Galaxy Z Flip5', 'storage' => '256GB', 'release_price' => 1399200, 'color' => '민트, 그라파이트, 크림, 라벤더', 'release_date' => '2023-08-11'],
    ['manufacturer' => '삼성', 'name' => 'Galaxy Z Flip5', 'storage' => '512GB', 'release_price' => 1522000, 'color' => '민트, 그라파이트, 크림, 라벤더', 'release_date' => '2023-08-11'],
    
    // Galaxy Z Fold4 (2022년)
    ['manufacturer' => '삼성', 'name' => 'Galaxy Z Fold4', 'storage' => '256GB', 'release_price' => 1998700, 'color' => '팬텀 블랙, 베이지, 그레이 그린, 버건디', 'release_date' => '2022-08-10'],
    ['manufacturer' => '삼성', 'name' => 'Galaxy Z Fold4', 'storage' => '512GB', 'release_price' => 2119700, 'color' => '팬텀 블랙, 베이지, 그레이 그린, 버건디', 'release_date' => '2022-08-10'],
    
    // Galaxy Z Flip4 (2022년)
    ['manufacturer' => '삼성', 'name' => 'Galaxy Z Flip4', 'storage' => '256GB', 'release_price' => 1353000, 'color' => 'Graphite, Pink Gold, Bora Purple, Blue', 'release_date' => '2022-08-01'],
    ['manufacturer' => '삼성', 'name' => 'Galaxy Z Flip4', 'storage' => '512GB', 'release_price' => 1474000, 'color' => 'Graphite, Pink Gold, Bora Purple, Blue', 'release_date' => '2022-08-01'],
];

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>단말기 데이터 삽입</title>
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
    </style>
</head>
<body>
    <div class="container">
        <h1>단말기 데이터 삽입</h1>
        <p class="subtitle">데이터베이스에 단말기 데이터를 추가합니다.</p>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['insert'])) {
            echo '<div class="status info">데이터 삽입 중...</div>';
            
            $insertedCount = 0;
            $skippedCount = 0;
            
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO devices (manufacturer_id, name, storage, release_price, color, release_date, status) 
                    VALUES (?, ?, ?, ?, ?, ?, 'active')
                ");
                
                foreach ($devicesData as $device) {
                    $manufacturerName = $device['manufacturer'];
                    
                    if (!isset($manufacturerIds[$manufacturerName])) {
                        $errors[] = "제조사를 찾을 수 없습니다: " . $manufacturerName;
                        continue;
                    }
                    
                    $manufacturerId = $manufacturerIds[$manufacturerName];
                    
                    try {
                        $stmt->execute([
                            $manufacturerId,
                            $device['name'],
                            $device['storage'],
                            $device['release_price'],
                            $device['color'],
                            $device['release_date']
                        ]);
                        $insertedCount++;
                        $success[] = "✅ " . $device['manufacturer'] . " " . $device['name'] . " (" . $device['storage'] . ") 추가 완료";
                    } catch (PDOException $e) {
                        // 중복 데이터인 경우 건너뛰기
                        if (strpos($e->getMessage(), 'Duplicate') !== false || 
                            strpos($e->getMessage(), '1062') !== false) {
                            $skippedCount++;
                        } else {
                            $errors[] = "❌ " . $device['name'] . " 추가 실패: " . htmlspecialchars($e->getMessage());
                        }
                    }
                }
                
                echo '<div class="status success">';
                echo '<strong>✅ 데이터 삽입 완료!</strong><br>';
                echo '추가된 단말기: <strong>' . $insertedCount . '개</strong><br>';
                if ($skippedCount > 0) {
                    echo '건너뛴 단말기 (중복): <strong>' . $skippedCount . '개</strong><br>';
                }
                echo '</div>';
                
                // 현재 단말기 개수 확인
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM devices");
                    $deviceCount = $stmt->fetch();
                    echo '<div class="status info">';
                    echo '현재 등록된 단말기: <strong>' . $deviceCount['cnt'] . '개</strong>';
                    echo '</div>';
                } catch (PDOException $e) {
                    // 무시
                }
                
                if (!empty($success) && count($success) <= 30) {
                    echo '<div class="status info" style="margin-top: 15px;">';
                    echo '<strong>추가된 단말기 목록:</strong><br>';
                    foreach ($success as $msg) {
                        echo '• ' . $msg . '<br>';
                    }
                    echo '</div>';
                }
                
                echo '<div style="margin-top: 30px;">';
                echo '<a href="/MVNO/admin/settings/device-settings.php" class="btn">단말기 설정 페이지로 이동</a>';
                echo '<a href="/MVNO/database/check_devices.php" class="btn btn-secondary" style="margin-left: 10px;">데이터베이스 상태 확인</a>';
                echo '</div>';
                
            } catch (Exception $e) {
                echo '<div class="status error">';
                echo '<strong>❌ 오류 발생</strong><br>';
                echo htmlspecialchars($e->getMessage());
                echo '</div>';
            }
            
            if (!empty($errors)) {
                echo '<div class="status error" style="margin-top: 15px;">';
                echo '<strong>오류 목록:</strong><br>';
                foreach ($errors as $error) {
                    echo $error . '<br>';
                }
                echo '</div>';
            }
            
        } else {
            // 삽입 전 확인
            try {
                $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM devices");
                $deviceCount = $stmt->fetch();
                
                echo '<div class="status info">';
                echo '<strong>삽입 준비 완료</strong><br>';
                echo '추가될 단말기: <strong>' . count($devicesData) . '개</strong><br>';
                echo '현재 등록된 단말기: <strong>' . $deviceCount['cnt'] . '개</strong>';
                echo '</div>';
                
                // 제조사별 통계
                $stats = [];
                foreach ($devicesData as $device) {
                    $mfg = $device['manufacturer'];
                    if (!isset($stats[$mfg])) {
                        $stats[$mfg] = 0;
                    }
                    $stats[$mfg]++;
                }
                
                echo '<div class="status info" style="margin-top: 15px;">';
                echo '<strong>제조사별 단말기:</strong><br>';
                foreach ($stats as $mfg => $count) {
                    echo '• ' . $mfg . ': ' . $count . '개<br>';
                }
                echo '</div>';
                
                if ($deviceCount['cnt'] > 0) {
                    echo '<div class="status error" style="margin-top: 15px;">';
                    echo '<strong>⚠️ 주의</strong><br>';
                    echo '이미 단말기 데이터가 있습니다. 중복된 데이터는 건너뜁니다.';
                    echo '</div>';
                }
                
                echo '<form method="POST" style="margin-top: 20px;">';
                echo '<button type="submit" name="insert" value="1" class="btn">데이터 삽입 시작</button>';
                echo '<a href="/MVNO/admin/settings/device-settings.php" class="btn btn-secondary" style="margin-left: 10px;">단말기 설정 페이지로 이동</a>';
                echo '</form>';
                
            } catch (PDOException $e) {
                echo '<div class="status error">';
                echo '<strong>❌ 오류 발생</strong><br>';
                echo htmlspecialchars($e->getMessage());
                echo '</div>';
            }
        }
        ?>
    </div>
</body>
</html>

