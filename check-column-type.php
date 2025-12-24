<?php
/**
 * monthly_fee 컬럼 타입 확인 및 수정 스크립트
 */

require_once __DIR__ . '/includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>monthly_fee 컬럼 타입 확인</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #10b981; color: white; }
    </style>
</head>
<body>";

try {
    $pdo = getDBConnection();
    
    if (!$pdo) {
        echo "<p class='error'>데이터베이스 연결 실패</p>";
        exit;
    }
    
    echo "<h1>monthly_fee 컬럼 타입 확인</h1>";
    
    // 현재 컬럼 타입 확인
    $stmt = $pdo->query("
        SELECT 
            COLUMN_NAME,
            DATA_TYPE,
            COLUMN_TYPE,
            COLUMN_DEFAULT,
            IS_NULLABLE,
            COLUMN_COMMENT
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'product_internet_details'
        AND COLUMN_NAME = 'monthly_fee'
    ");
    $columnInfo = $stmt->fetch();
    
    if ($columnInfo) {
        echo "<h2>현재 컬럼 정보</h2>";
        echo "<table>";
        echo "<tr><th>항목</th><th>값</th></tr>";
        echo "<tr><td>컬럼명</td><td>{$columnInfo['COLUMN_NAME']}</td></tr>";
        echo "<tr><td>데이터 타입</td><td><strong>{$columnInfo['DATA_TYPE']}</strong></td></tr>";
        echo "<tr><td>컬럼 타입</td><td>{$columnInfo['COLUMN_TYPE']}</td></tr>";
        echo "<tr><td>기본값</td><td>" . ($columnInfo['COLUMN_DEFAULT'] ?? 'NULL') . "</td></tr>";
        echo "<tr><td>NULL 허용</td><td>{$columnInfo['IS_NULLABLE']}</td></tr>";
        echo "<tr><td>주석</td><td>{$columnInfo['COLUMN_COMMENT']}</td></tr>";
        echo "</table>";
        
        if (strtoupper($columnInfo['DATA_TYPE']) === 'DECIMAL' || strtoupper($columnInfo['DATA_TYPE']) === 'NUMERIC') {
            echo "<p class='error'>⚠ 문제 발견: 컬럼이 DECIMAL 형식입니다. VARCHAR로 변경이 필요합니다.</p>";
            
            // 샘플 데이터 확인
            echo "<h2>현재 저장된 데이터 샘플</h2>";
            $stmt = $pdo->query("SELECT monthly_fee FROM product_internet_details LIMIT 5");
            $samples = $stmt->fetchAll();
            echo "<table>";
            echo "<tr><th>monthly_fee 값</th><th>타입</th></tr>";
            foreach ($samples as $sample) {
                $value = $sample['monthly_fee'];
                $type = gettype($value);
                echo "<tr><td>" . htmlspecialchars($value) . "</td><td>{$type}</td></tr>";
            }
            echo "</table>";
            
            // ALTER TABLE 실행
            echo "<h2>컬럼 타입 변경</h2>";
            echo "<p>VARCHAR(50)로 변경하시겠습니까?</p>";
            echo "<form method='POST'>";
            echo "<input type='hidden' name='action' value='alter'>";
            echo "<button type='submit' style='padding: 10px 20px; background: #10b981; color: white; border: none; border-radius: 5px; cursor: pointer;'>컬럼 타입 변경 실행</button>";
            echo "</form>";
            
            if (isset($_POST['action']) && $_POST['action'] === 'alter') {
                try {
                    // 기존 데이터를 VARCHAR 형식으로 변환하여 백업
                    $pdo->beginTransaction();
                    
                    // 먼저 기존 데이터를 텍스트 형식으로 변환
                    $pdo->exec("
                        UPDATE product_internet_details 
                        SET monthly_fee = CASE 
                            WHEN monthly_fee = '' OR monthly_fee IS NULL THEN ''
                            WHEN CAST(monthly_fee AS CHAR) LIKE '%.%' THEN CONCAT(CAST(monthly_fee AS UNSIGNED), '원')
                            ELSE CONCAT(CAST(monthly_fee AS UNSIGNED), '원')
                        END
                    ");
                    
                    // 컬럼 타입 변경
                    $pdo->exec("
                        ALTER TABLE product_internet_details 
                        MODIFY COLUMN monthly_fee VARCHAR(50) NOT NULL DEFAULT '' COMMENT '월 요금제 (텍스트 형식)'
                    ");
                    
                    $pdo->commit();
                    
                    echo "<p class='success'>✓ 컬럼 타입이 VARCHAR(50)로 성공적으로 변경되었습니다!</p>";
                    echo "<p><a href='check-column-type.php'>페이지 새로고침하여 확인</a></p>";
                    
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    echo "<p class='error'>오류 발생: " . htmlspecialchars($e->getMessage()) . "</p>";
                }
            }
        } else {
            echo "<p class='success'>✓ 컬럼이 이미 VARCHAR 형식입니다.</p>";
            
            // 샘플 데이터 확인
            echo "<h2>현재 저장된 데이터 샘플</h2>";
            $stmt = $pdo->query("SELECT monthly_fee FROM product_internet_details LIMIT 5");
            $samples = $stmt->fetchAll();
            echo "<table>";
            echo "<tr><th>monthly_fee 값</th><th>타입</th></tr>";
            foreach ($samples as $sample) {
                $value = $sample['monthly_fee'];
                $type = gettype($value);
                echo "<tr><td>" . htmlspecialchars($value) . "</td><td>{$type}</td></tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p class='error'>컬럼을 찾을 수 없습니다.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>오류: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?>




