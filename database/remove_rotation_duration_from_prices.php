<?php
/**
 * rotation_advertisement_prices 테이블에서 rotation_duration 컬럼 제거
 * 
 * 로테이션 시간이 하나뿐이므로 가격 테이블에서 rotation_duration 컬럼을 제거하고
 * 가격은 product_type과 advertisement_days만으로 관리
 * 
 * 실행 방법: 브라우저에서 http://localhost/MVNO/database/remove_rotation_duration_from_prices.php 접속
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
    <title>rotation_duration 컬럼 제거</title>
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
        <h1>rotation_advertisement_prices 테이블 수정</h1>
        <h2>rotation_duration 컬럼 제거</h2>
        
        <?php
        try {
            // 1. 테이블 존재 확인
            $stmt = $pdo->query("SHOW TABLES LIKE 'rotation_advertisement_prices'");
            if ($stmt->rowCount() == 0) {
                echo "<div class='error'>rotation_advertisement_prices 테이블이 존재하지 않습니다.</div>";
            } else {
                echo "<div class='info'>✓ rotation_advertisement_prices 테이블 확인</div>";
                
                // 2. 기존 데이터 백업 (중복된 경우 처리)
                // 같은 product_type, advertisement_days 조합이 여러 개 있는 경우 첫 번째 것만 유지
                echo "<div class='info'>중복 데이터 확인 및 정리 중...</div>";
                
                $stmt = $pdo->query("
                    SELECT product_type, advertisement_days, COUNT(*) as cnt
                    FROM rotation_advertisement_prices
                    GROUP BY product_type, advertisement_days
                    HAVING cnt > 1
                ");
                $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($duplicates)) {
                    foreach ($duplicates as $dup) {
                        // 같은 조합 중 가장 최근 것 하나만 남기고 나머지 삭제
                        $pdo->exec("
                            DELETE t1 FROM rotation_advertisement_prices t1
                            INNER JOIN rotation_advertisement_prices t2
                            WHERE t1.product_type = '{$dup['product_type']}'
                            AND t1.advertisement_days = {$dup['advertisement_days']}
                            AND t2.product_type = '{$dup['product_type']}'
                            AND t2.advertisement_days = {$dup['advertisement_days']}
                            AND t1.id < t2.id
                        ");
                    }
                    echo "<div class='success'>✓ 중복 데이터 정리 완료</div>";
                }
                
                // 3. 기존 UNIQUE KEY 제거
                echo "<div class='info'>기존 UNIQUE KEY 제거 중...</div>";
                try {
                    $pdo->exec("ALTER TABLE rotation_advertisement_prices DROP INDEX unique_type_duration_days");
                    echo "<div class='success'>✓ 기존 UNIQUE KEY 제거 완료</div>";
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), "doesn't exist") === false) {
                        throw $e;
                    }
                    echo "<div class='info'>기존 UNIQUE KEY가 이미 없습니다.</div>";
                }
                
                // 4. rotation_duration 컬럼 제거
                echo "<div class='info'>rotation_duration 컬럼 제거 중...</div>";
                try {
                    $pdo->exec("ALTER TABLE rotation_advertisement_prices DROP COLUMN rotation_duration");
                    echo "<div class='success'>✓ rotation_duration 컬럼 제거 완료</div>";
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), "doesn't exist") === false) {
                        throw $e;
                    }
                    echo "<div class='info'>rotation_duration 컬럼이 이미 없습니다.</div>";
                }
                
                // 5. 새로운 UNIQUE KEY 추가 (product_type, advertisement_days)
                echo "<div class='info'>새로운 UNIQUE KEY 추가 중...</div>";
                try {
                    $pdo->exec("ALTER TABLE rotation_advertisement_prices ADD UNIQUE KEY unique_type_days (product_type, advertisement_days)");
                    echo "<div class='success'>✓ 새로운 UNIQUE KEY 추가 완료</div>";
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), "Duplicate key name") !== false) {
                        echo "<div class='info'>UNIQUE KEY가 이미 존재합니다.</div>";
                    } else {
                        throw $e;
                    }
                }
                
                echo "<div class='success'><strong>✅ 모든 작업이 완료되었습니다!</strong></div>";
                echo "<div class='info'>이제 가격은 product_type과 advertisement_days만으로 관리됩니다. 로테이션 시간 변경 시에도 기존 가격이 유지됩니다.</div>";
            }
            
        } catch (Exception $e) {
            echo "<div class='error'><strong>❌ 오류 발생:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        }
        ?>
        
        <a href="../admin/advertisement/prices.php" class="btn">광고 가격 설정 페이지로 이동</a>
    </div>
</body>
</html>
