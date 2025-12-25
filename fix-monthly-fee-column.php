<?php
/**
 * monthly_fee 컬럼을 VARCHAR로 변경하고 기존 데이터를 변환하는 스크립트
 * 브라우저에서 실행: http://localhost/MVNO/fix-monthly-fee-column.php
 */

require_once __DIR__ . '/includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>monthly_fee 컬럼 수정</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { color: green; font-weight: bold; padding: 10px; background: #d1fae5; border-radius: 5px; margin: 10px 0; }
        .error { color: red; font-weight: bold; padding: 10px; background: #fee2e2; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; padding: 10px; background: #dbeafe; border-radius: 5px; margin: 10px 0; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #10b981; color: white; }
        button { padding: 10px 20px; background: #10b981; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        button:hover { background: #059669; }
    </style>
</head>
<body>
<div class='container'>";

try {
    $pdo = getDBConnection();
    
    if (!$pdo) {
        echo "<p class='error'>✗ 데이터베이스 연결 실패</p>";
        exit;
    }
    
    echo "<h1>monthly_fee 컬럼 수정</h1>";
    
    // 현재 컬럼 타입 확인
    $stmt = $pdo->query("
        SELECT DATA_TYPE, COLUMN_TYPE
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'product_internet_details'
        AND COLUMN_NAME = 'monthly_fee'
    ");
    $columnInfo = $stmt->fetch();
    
    if (!$columnInfo) {
        echo "<p class='error'>컬럼을 찾을 수 없습니다.</p>";
        exit;
    }
    
    $currentType = strtoupper($columnInfo['DATA_TYPE']);
    echo "<p class='info'>현재 컬럼 타입: <strong>{$columnInfo['COLUMN_TYPE']}</strong></p>";
    
    if ($currentType === 'VARCHAR' || $currentType === 'CHAR' || $currentType === 'TEXT') {
        echo "<p class='success'>✓ 컬럼이 이미 VARCHAR 형식입니다. 수정이 필요하지 않습니다.</p>";
        
        // 샘플 데이터 확인
        echo "<h2>현재 저장된 데이터 샘플</h2>";
        $stmt = $pdo->query("SELECT monthly_fee FROM product_internet_details WHERE monthly_fee != '' LIMIT 5");
        $samples = $stmt->fetchAll();
        if (count($samples) > 0) {
            echo "<table>";
            echo "<tr><th>monthly_fee 값</th></tr>";
            foreach ($samples as $sample) {
                echo "<tr><td>" . htmlspecialchars($sample['monthly_fee']) . "</td></tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p class='error'>⚠ 컬럼이 DECIMAL 형식입니다. VARCHAR로 변경이 필요합니다.</p>";
        
        if (isset($_POST['action']) && $_POST['action'] === 'fix') {
            try {
                $pdo->beginTransaction();
                
                // 1. 기존 데이터를 텍스트 형식으로 변환
                echo "<p class='info'>1단계: 기존 데이터를 텍스트 형식으로 변환 중...</p>";
                
                // DECIMAL 형식의 데이터를 "숫자원" 형식으로 변환
                $pdo->exec("
                    UPDATE product_internet_details 
                    SET monthly_fee = CASE 
                        WHEN monthly_fee = '' OR monthly_fee IS NULL THEN ''
                        WHEN CAST(monthly_fee AS CHAR) LIKE '%.%' THEN CONCAT(CAST(monthly_fee AS UNSIGNED), '원')
                        ELSE CONCAT(CAST(monthly_fee AS UNSIGNED), '원')
                    END
                    WHERE monthly_fee != '' AND monthly_fee IS NOT NULL
                ");
                
                echo "<p class='success'>✓ 데이터 변환 완료</p>";
                
                // 2. 컬럼 타입 변경
                echo "<p class='info'>2단계: 컬럼 타입을 VARCHAR(50)로 변경 중...</p>";
                
                $pdo->exec("
                    ALTER TABLE product_internet_details 
                    MODIFY COLUMN monthly_fee VARCHAR(50) NOT NULL DEFAULT '' COMMENT '월 요금제 (텍스트 형식)'
                ");
                
                echo "<p class='success'>✓ 컬럼 타입 변경 완료</p>";
                
                $pdo->commit();
                
                echo "<p class='success' style='font-size: 18px;'>✓✓✓ 성공적으로 완료되었습니다! ✓✓✓</p>";
                echo "<p><a href='fix-monthly-fee-column.php'>페이지 새로고침하여 확인</a></p>";
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                echo "<p class='error'>오류 발생: " . htmlspecialchars($e->getMessage()) . "</p>";
                echo "<p>트랜잭션이 롤백되었습니다.</p>";
            }
        } else {
            // 변경 전 샘플 데이터 확인
            echo "<h2>변경 전 데이터 샘플</h2>";
            $stmt = $pdo->query("SELECT monthly_fee FROM product_internet_details WHERE monthly_fee != '' LIMIT 5");
            $samples = $stmt->fetchAll();
            if (count($samples) > 0) {
                echo "<table>";
                echo "<tr><th>현재 monthly_fee 값 (DECIMAL)</th></tr>";
                foreach ($samples as $sample) {
                    echo "<tr><td>" . htmlspecialchars($sample['monthly_fee']) . "</td></tr>";
                }
                echo "</table>";
            }
            
            echo "<form method='POST'>";
            echo "<input type='hidden' name='action' value='fix'>";
            echo "<p><strong>주의:</strong> 이 작업은 기존 데이터를 '숫자원' 형식으로 변환하고 컬럼 타입을 VARCHAR로 변경합니다.</p>";
            echo "<button type='submit'>컬럼 타입 변경 실행</button>";
            echo "</form>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>데이터베이스 오류: " . htmlspecialchars($e->getMessage()) . "</p>";
} catch (Exception $e) {
    echo "<p class='error'>오류: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div></body></html>";
?>







