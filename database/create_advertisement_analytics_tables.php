<?php
/**
 * 광고 분석 테이블 생성 스크립트
 * 
 * 실행 방법:
 * php database/create_advertisement_analytics_tables.php
 * 
 * 또는 브라우저에서:
 * http://localhost/MVNO/database/create_advertisement_analytics_tables.php
 */

require_once __DIR__ . '/../includes/data/db-config.php';

// HTML 출력 모드 확인
$isWeb = isset($_SERVER['HTTP_HOST']);

if ($isWeb) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>광고 분석 테이블 생성</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
            .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            h1 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
            h2 { color: #555; margin-top: 30px; }
            .success { color: #4CAF50; background: #e8f5e9; padding: 10px; border-radius: 4px; margin: 10px 0; }
            .error { color: #f44336; background: #ffebee; padding: 10px; border-radius: 4px; margin: 10px 0; }
            .info { color: #2196F3; background: #e3f2fd; padding: 10px; border-radius: 4px; margin: 10px 0; }
            .warning { color: #ff9800; background: #fff3e0; padding: 10px; border-radius: 4px; margin: 10px 0; }
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
    
    if ($isWeb) {
        echo "<h1>광고 분석 테이블 생성</h1>";
    } else {
        echo "광고 분석 테이블 생성 시작...\n";
    }
    
    // SQL 파일 읽기
    $sqlFile = __DIR__ . '/create_advertisement_analytics_tables.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL 파일을 찾을 수 없습니다: {$sqlFile}");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // SQL 문을 세미콜론으로 분리 (주석 제거는 하지 않음)
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && 
                   !preg_match('/^--/', $stmt) && 
                   !preg_match('/^USE/', $stmt) &&
                   !preg_match('/^\/\*/', $stmt);
        }
    );
    
    $successCount = 0;
    $errorCount = 0;
    $errors = [];
    
    foreach ($statements as $index => $statement) {
        if (empty(trim($statement))) continue;
        
        // CREATE TABLE 문 추출
        if (preg_match('/CREATE TABLE.*?`(\w+)`/is', $statement, $matches)) {
            $tableName = $matches[1];
            
            try {
                // 테이블 존재 여부 확인
                $checkStmt = $pdo->query("SHOW TABLES LIKE '{$tableName}'");
                $tableExists = $checkStmt->rowCount() > 0;
                
                if ($tableExists) {
                    // 테이블이 존재하더라도 IF NOT EXISTS를 사용하므로 안전하게 재실행 가능
                    // 하지만 사용자에게 알림만 표시하고 건너뛰기
                    if ($isWeb) {
                        echo "<div class='warning'><strong>⚠️ 테이블 '{$tableName}'이 이미 존재합니다.</strong> 건너뜁니다.</div>";
                    } else {
                        echo "⚠️ 테이블 '{$tableName}'이 이미 존재합니다. 건너뜁니다.\n";
                    }
                    continue;
                }
                
                // 테이블 생성 (IF NOT EXISTS가 포함되어 있어도 안전)
                // SQL 문에 IF NOT EXISTS가 없으면 추가
                if (stripos($statement, 'IF NOT EXISTS') === false) {
                    $statement = preg_replace('/CREATE TABLE\s+/i', 'CREATE TABLE IF NOT EXISTS ', $statement, 1);
                }
                
                $pdo->exec($statement);
                
                if ($isWeb) {
                    echo "<div class='success'><strong>✅ 테이블 '{$tableName}' 생성 완료</strong></div>";
                } else {
                    echo "✅ 테이블 '{$tableName}' 생성 완료\n";
                }
                $successCount++;
                
            } catch (PDOException $e) {
                $errorMsg = "테이블 '{$tableName}' 생성 실패: " . $e->getMessage();
                $errors[] = $errorMsg;
                
                if ($isWeb) {
                    echo "<div class='error'><strong>❌ {$errorMsg}</strong></div>";
                } else {
                    echo "❌ {$errorMsg}\n";
                }
                $errorCount++;
            }
        }
    }
    
    // 결과 요약
    if ($isWeb) {
        echo "<h2>생성 결과</h2>";
        echo "<div class='info'>";
        echo "<strong>성공:</strong> {$successCount}개 테이블<br>";
        echo "<strong>실패:</strong> {$errorCount}개 테이블<br>";
        echo "<strong>건너뜀:</strong> " . (3 - $successCount - $errorCount) . "개 테이블 (이미 존재)";
        echo "</div>";
        
        if (!empty($errors)) {
            echo "<h2>오류 상세</h2>";
            foreach ($errors as $error) {
                echo "<div class='error'>{$error}</div>";
            }
        }
        
        echo "<h2>생성된 테이블</h2>";
        echo "<ul>";
        echo "<li><strong>advertisement_impressions</strong> - 광고 노출 추적</li>";
        echo "<li><strong>advertisement_clicks</strong> - 광고 클릭 추적</li>";
        echo "<li><strong>advertisement_analytics</strong> - 광고 통계 집계</li>";
        echo "</ul>";
        
        echo "<h2>다음 단계</h2>";
        echo "<div class='info'>";
        echo "1. 광고 분석 추적 함수를 구현하세요.<br>";
        echo "2. 프론트엔드에서 광고 노출/클릭 이벤트를 추적하세요.<br>";
        echo "3. 통계 집계 스크립트를 작성하세요 (선택사항).";
        echo "</div>";
        
    } else {
        echo "\n=== 생성 결과 ===\n";
        echo "성공: {$successCount}개 테이블\n";
        echo "실패: {$errorCount}개 테이블\n";
        
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
