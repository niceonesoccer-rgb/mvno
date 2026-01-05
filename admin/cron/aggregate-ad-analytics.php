<?php
/**
 * 광고 통계 집계 크론잡
 * 
 * 실행 방법:
 * - Windows 작업 스케줄러: 매일 00:00에 실행
 * - Linux Cron: 0 0 * * * php /path/to/admin/cron/aggregate-ad-analytics.php
 * 
 * 또는 브라우저에서 수동 실행:
 * http://localhost/MVNO/admin/cron/aggregate-ad-analytics.php
 */

// 한국 시간대 설정
date_default_timezone_set('Asia/Seoul');

require_once __DIR__ . '/../../includes/data/db-config.php';
require_once __DIR__ . '/../../includes/data/advertisement-analytics-functions.php';

// HTML 출력 모드 확인
$isWeb = isset($_SERVER['HTTP_HOST']);

if ($isWeb) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>광고 통계 집계</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
            .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            h1 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
            .success { color: #4CAF50; background: #e8f5e9; padding: 10px; border-radius: 4px; margin: 10px 0; }
            .error { color: #f44336; background: #ffebee; padding: 10px; border-radius: 4px; margin: 10px 0; }
            .info { color: #2196F3; background: #e3f2fd; padding: 10px; border-radius: 4px; margin: 10px 0; }
            pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
        </style>
    </head>
    <body>
    <div class='container'>";
}

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception("데이터베이스 연결 실패");
    }
    
    // 테이블 존재 여부 확인 및 자동 생성
    $requiredTables = ['advertisement_impressions', 'advertisement_clicks', 'advertisement_analytics'];
    $missingTables = [];
    
    foreach ($requiredTables as $tableName) {
        $checkStmt = $pdo->query("SHOW TABLES LIKE '{$tableName}'");
        if ($checkStmt->rowCount() === 0) {
            $missingTables[] = $tableName;
        }
    }
    
    // 테이블이 없으면 자동 생성
    if (!empty($missingTables)) {
        if ($isWeb) {
            echo "<div class='info'><strong>⚠️ 필요한 테이블이 없습니다. 자동으로 생성합니다...</strong></div>";
        } else {
            echo "⚠️ 필요한 테이블이 없습니다. 자동으로 생성합니다...\n";
        }
        
        // SQL 파일 읽기
        $sqlFile = __DIR__ . '/../../database/create_advertisement_analytics_tables.sql';
        if (!file_exists($sqlFile)) {
            throw new Exception("SQL 파일을 찾을 수 없습니다: {$sqlFile}");
        }
        
        $sql = file_get_contents($sqlFile);
        
        // USE 문 제거
        $sql = preg_replace('/USE\s+`[^`]+`;\s*/i', '', $sql);
        
        // SQL 문을 세미콜론으로 분리
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($stmt) {
                return !empty($stmt) && 
                       !preg_match('/^--/', $stmt) &&
                       !preg_match('/^\/\*/', $stmt) &&
                       !preg_match('/^\*/', $stmt);
            }
        );
        
        $createdCount = 0;
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement)) continue;
            
            // CREATE TABLE 문인지 확인
            if (preg_match('/CREATE TABLE.*?`(\w+)`/is', $statement, $matches)) {
                $tableName = $matches[1];
                
                // 필요한 테이블만 생성
                if (!in_array($tableName, $missingTables)) {
                    continue;
                }
                
                try {
                    // IF NOT EXISTS가 없으면 추가
                    if (stripos($statement, 'IF NOT EXISTS') === false) {
                        $statement = preg_replace('/CREATE TABLE\s+/i', 'CREATE TABLE IF NOT EXISTS ', $statement, 1);
                    }
                    
                    $pdo->exec($statement);
                    $createdCount++;
                    
                    if ($isWeb) {
                        echo "<div class='success'>✅ 테이블 '{$tableName}' 생성 완료</div>";
                    } else {
                        echo "✅ 테이블 '{$tableName}' 생성 완료\n";
                    }
                } catch (PDOException $e) {
                    if ($isWeb) {
                        echo "<div class='error'>❌ 테이블 '{$tableName}' 생성 실패: " . htmlspecialchars($e->getMessage()) . "</div>";
                    } else {
                        echo "❌ 테이블 '{$tableName}' 생성 실패: " . $e->getMessage() . "\n";
                    }
                    throw new Exception("테이블 생성 실패: {$tableName}");
                }
            }
        }
        
        if ($createdCount > 0) {
            if ($isWeb) {
                echo "<div class='success'><strong>✅ {$createdCount}개 테이블 생성 완료</strong></div>";
            } else {
                echo "✅ {$createdCount}개 테이블 생성 완료\n\n";
            }
        }
    }
    
    // 집계할 날짜 (기본값: 어제)
    $targetDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d', strtotime('-1 day'));
    
    if ($isWeb) {
        echo "<h1>광고 통계 집계</h1>";
        echo "<div class='info'><strong>집계 날짜:</strong> {$targetDate}</div>";
    } else {
        echo "광고 통계 집계 시작...\n";
        echo "집계 날짜: {$targetDate}\n\n";
    }
    
    // 활성 광고 목록 가져오기 (해당 날짜에 노출된 광고)
    $stmt = $pdo->prepare("
        SELECT DISTINCT ra.id, ra.product_id, ra.seller_id, ra.product_type
        FROM rotation_advertisements ra
        INNER JOIN advertisement_impressions ai ON ra.id = ai.advertisement_id
        WHERE ra.status = 'active'
        AND DATE(ai.created_at) = :target_date
    ");
    $stmt->execute([':target_date' => $targetDate]);
    $advertisements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($advertisements)) {
        // 노출 데이터가 없으면 클릭 데이터가 있는 광고도 확인
        $stmt = $pdo->prepare("
            SELECT DISTINCT ra.id, ra.product_id, ra.seller_id, ra.product_type
            FROM rotation_advertisements ra
            INNER JOIN advertisement_clicks ac ON ra.id = ac.advertisement_id
            WHERE ra.status = 'active'
            AND DATE(ac.created_at) = :target_date
        ");
        $stmt->execute([':target_date' => $targetDate]);
        $advertisements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    if (empty($advertisements)) {
        if ($isWeb) {
            echo "<div class='info'>집계할 광고가 없습니다. (해당 날짜에 노출/클릭 데이터가 없음)</div>";
        } else {
            echo "집계할 광고가 없습니다.\n";
        }
        exit(0);
    }
    
    $successCount = 0;
    $errorCount = 0;
    $errors = [];
    
    foreach ($advertisements as $ad) {
        try {
            $result = aggregateAdvertisementAnalytics($ad['id'], $targetDate);
            
            if ($result) {
                $successCount++;
                if ($isWeb) {
                    echo "<div class='success'>✅ 광고 ID {$ad['id']} 통계 집계 완료</div>";
                } else {
                    echo "✅ 광고 ID {$ad['id']} 통계 집계 완료\n";
                }
            } else {
                $errorCount++;
                $errorMsg = "광고 ID {$ad['id']} 통계 집계 실패";
                $errors[] = $errorMsg;
                if ($isWeb) {
                    echo "<div class='error'>❌ {$errorMsg}</div>";
                } else {
                    echo "❌ {$errorMsg}\n";
                }
            }
        } catch (Exception $e) {
            $errorCount++;
            $errorMsg = "광고 ID {$ad['id']} 통계 집계 오류: " . $e->getMessage();
            $errors[] = $errorMsg;
            if ($isWeb) {
                echo "<div class='error'>❌ {$errorMsg}</div>";
            } else {
                echo "❌ {$errorMsg}\n";
            }
        }
    }
    
    // 결과 요약
    if ($isWeb) {
        echo "<h2>집계 결과</h2>";
        echo "<div class='info'>";
        echo "<strong>성공:</strong> {$successCount}개 광고<br>";
        echo "<strong>실패:</strong> {$errorCount}개 광고<br>";
        echo "<strong>총 처리:</strong> " . count($advertisements) . "개 광고";
        echo "</div>";
        
        if (!empty($errors)) {
            echo "<h2>오류 상세</h2>";
            foreach ($errors as $error) {
                echo "<div class='error'>{$error}</div>";
            }
        }
        
        echo "<h2>사용 방법</h2>";
        echo "<div class='info'>";
        echo "<strong>특정 날짜 집계:</strong><br>";
        echo "<code>?date=2025-01-15</code><br><br>";
        echo "<strong>Windows 작업 스케줄러:</strong><br>";
        echo "프로그램: C:\\xampp\\php\\php.exe<br>";
        echo "인수: C:\\xampp\\htdocs\\mvno\\admin\\cron\\aggregate-ad-analytics.php<br>";
        echo "일정: 매일 00:00<br><br>";
        echo "<strong>Linux Cron:</strong><br>";
        echo "<code>0 0 * * * /usr/bin/php /path/to/mvno/admin/cron/aggregate-ad-analytics.php</code>";
        echo "</div>";
        
    } else {
        echo "\n=== 집계 결과 ===\n";
        echo "성공: {$successCount}개 광고\n";
        echo "실패: {$errorCount}개 광고\n";
        echo "총 처리: " . count($advertisements) . "개 광고\n";
        
        if (!empty($errors)) {
            echo "\n오류:\n";
            foreach ($errors as $error) {
                echo "  - {$error}\n";
            }
        }
    }
    
} catch (Exception $e) {
    $errorMsg = "오류 발생: " . $e->getMessage();
    
    if ($isWeb) {
        echo "<div class='error'><strong>❌ {$errorMsg}</strong></div>";
    } else {
        echo "❌ {$errorMsg}\n";
    }
    exit(1);
}

if ($isWeb) {
    echo "</div></body></html>";
}
