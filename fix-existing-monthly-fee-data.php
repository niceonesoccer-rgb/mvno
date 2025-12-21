<?php
/**
 * 기존 monthly_fee 데이터를 "숫자원" 형식으로 변환하는 스크립트
 * 브라우저에서 실행: http://localhost/MVNO/fix-existing-monthly-fee-data.php
 */

require_once __DIR__ . '/includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>기존 monthly_fee 데이터 수정</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { color: green; font-weight: bold; padding: 10px; background: #d1fae5; border-radius: 5px; margin: 10px 0; }
        .error { color: red; font-weight: bold; padding: 10px; background: #fee2e2; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; padding: 10px; background: #dbeafe; border-radius: 5px; margin: 10px 0; }
        .warning { color: orange; padding: 10px; background: #fef3c7; border-radius: 5px; margin: 10px 0; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #10b981; color: white; }
        button { padding: 10px 20px; background: #10b981; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; margin: 10px 5px; }
        button:hover { background: #059669; }
        .btn-danger { background: #ef4444; }
        .btn-danger:hover { background: #dc2626; }
        .raw-value { font-family: monospace; background: #f3f4f6; padding: 2px 6px; border-radius: 3px; }
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
    
    echo "<h1>기존 monthly_fee 데이터 수정</h1>";
    
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
    
    // 현재 데이터 확인
    echo "<h2>1. 현재 저장된 데이터 분석</h2>";
    
    // 다양한 형식의 데이터 확인
    $stmt = $pdo->query("
        SELECT 
            monthly_fee,
            CASE 
                WHEN monthly_fee = '' OR monthly_fee IS NULL THEN '빈 값'
                WHEN monthly_fee REGEXP '^[0-9]+\\.[0-9]+$' THEN '소수점 형식 (예: 30000.00)'
                WHEN monthly_fee REGEXP '^[0-9]+$' THEN '숫자만 (예: 30000)'
                WHEN monthly_fee REGEXP '^[0-9]+[가-힣]+$' THEN '숫자+한글 (예: 30000원)'
                ELSE '기타 형식'
            END as data_format
        FROM product_internet_details
        WHERE monthly_fee != '' AND monthly_fee IS NOT NULL
        ORDER BY id
    ");
    $allData = $stmt->fetchAll();
    
    if (count($allData) > 0) {
        // 형식별로 그룹화
        $formatGroups = [];
        foreach ($allData as $row) {
            $format = $row['data_format'];
            if (!isset($formatGroups[$format])) {
                $formatGroups[$format] = [];
            }
            $formatGroups[$format][] = $row['monthly_fee'];
        }
        
        echo "<table>";
        echo "<tr><th>데이터 형식</th><th>개수</th><th>샘플 데이터</th></tr>";
        foreach ($formatGroups as $format => $values) {
            $count = count($values);
            $samples = array_slice($values, 0, 5);
            $samplesStr = implode(', ', array_map(function($v) {
                return '<span class="raw-value">' . htmlspecialchars($v) . '</span>';
            }, $samples));
            if ($count > 5) {
                $samplesStr .= ' ...';
            }
            echo "<tr><td>{$format}</td><td><strong>{$count}</strong>개</td><td>{$samplesStr}</td></tr>";
        }
        echo "</table>";
        
        // 수정이 필요한 데이터 확인
        $needsFix = [];
        foreach ($allData as $row) {
            $value = $row['monthly_fee'];
            $format = $row['data_format'];
            
            // 수정이 필요한 경우: 소수점 형식, 숫자만, 또는 이미 "원"이 있지만 소수점이 포함된 경우
            if ($format === '소수점 형식 (예: 30000.00)' || 
                $format === '숫자만 (예: 30000)' ||
                (preg_match('/^[0-9]+\.[0-9]+/', $value))) {
                $needsFix[] = $value;
            }
        }
        
        if (count($needsFix) > 0) {
            echo "<p class='warning'>⚠ 수정이 필요한 데이터: <strong>" . count($needsFix) . "개</strong></p>";
            
            if (isset($_POST['action']) && $_POST['action'] === 'fix') {
                try {
                    $pdo->beginTransaction();
                    
                    $updateCount = 0;
                    
                    // 각 레코드를 업데이트
                    $stmt = $pdo->prepare("
                        UPDATE product_internet_details 
                        SET monthly_fee = :new_value
                        WHERE monthly_fee = :old_value
                    ");
                    
                    foreach ($allData as $row) {
                        $oldValue = $row['monthly_fee'];
                        $format = $row['data_format'];
                        
                        $newValue = '';
                        
                        // 소수점 형식인 경우 (예: 30000.00)
                        if (preg_match('/^([0-9]+)\.([0-9]+)$/', $oldValue, $matches)) {
                            $newValue = $matches[1] . '원';
                        }
                        // 숫자만 있는 경우 (예: 30000)
                        elseif (preg_match('/^([0-9]+)$/', $oldValue, $matches)) {
                            $newValue = $matches[1] . '원';
                        }
                        // 이미 "원"이 있지만 소수점이 포함된 경우 (예: 30000.00원)
                        elseif (preg_match('/^([0-9]+)\.([0-9]+)(.+)$/', $oldValue, $matches)) {
                            $newValue = $matches[1] . $matches[3];
                        }
                        // 이미 올바른 형식인 경우 그대로 유지
                        elseif (preg_match('/^[0-9]+[가-힣]+$/', $oldValue)) {
                            $newValue = $oldValue; // 변경 없음
                        }
                        // 빈 값인 경우
                        elseif ($oldValue === '' || $oldValue === null) {
                            $newValue = '';
                        }
                        // 기타 형식은 숫자만 추출
                        else {
                            $numericPart = preg_replace('/[^0-9]/', '', $oldValue);
                            if ($numericPart !== '') {
                                $newValue = $numericPart . '원';
                            } else {
                                $newValue = $oldValue; // 숫자가 없으면 그대로 유지
                            }
                        }
                        
                        // 값이 변경되는 경우에만 업데이트
                        if ($newValue !== $oldValue) {
                            $stmt->execute([
                                ':old_value' => $oldValue,
                                ':new_value' => $newValue
                            ]);
                            $updateCount += $stmt->rowCount();
                        }
                    }
                    
                    $pdo->commit();
                    
                    echo "<p class='success' style='font-size: 18px;'>✓✓✓ 성공적으로 완료되었습니다! ✓✓✓</p>";
                    echo "<p class='success'>총 <strong>{$updateCount}개</strong>의 레코드가 수정되었습니다.</p>";
                    echo "<p><a href='fix-existing-monthly-fee-data.php'>페이지 새로고침하여 확인</a></p>";
                    
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    echo "<p class='error'>오류 발생: " . htmlspecialchars($e->getMessage()) . "</p>";
                    echo "<p>트랜잭션이 롤백되었습니다.</p>";
                }
            } else {
                // 수정 전 미리보기
                echo "<h2>2. 수정 예정 데이터 미리보기</h2>";
                echo "<table>";
                echo "<tr><th>현재 값</th><th>변환 후 값</th></tr>";
                
                $previewCount = 0;
                foreach ($allData as $row) {
                    if ($previewCount >= 20) break; // 최대 20개만 미리보기
                    
                    $oldValue = $row['monthly_fee'];
                    $format = $row['data_format'];
                    
                    // 수정이 필요한 경우만 표시
                    if ($format === '소수점 형식 (예: 30000.00)' || 
                        $format === '숫자만 (예: 30000)' ||
                        preg_match('/^[0-9]+\.[0-9]+/', $oldValue)) {
                        
                        $newValue = '';
                        if (preg_match('/^([0-9]+)\.([0-9]+)$/', $oldValue, $matches)) {
                            $newValue = $matches[1] . '원';
                        } elseif (preg_match('/^([0-9]+)$/', $oldValue, $matches)) {
                            $newValue = $matches[1] . '원';
                        } elseif (preg_match('/^([0-9]+)\.([0-9]+)(.+)$/', $oldValue, $matches)) {
                            $newValue = $matches[1] . $matches[3];
                        } else {
                            $numericPart = preg_replace('/[^0-9]/', '', $oldValue);
                            $newValue = $numericPart !== '' ? $numericPart . '원' : $oldValue;
                        }
                        
                        if ($newValue !== $oldValue) {
                            echo "<tr>";
                            echo "<td><span class='raw-value'>" . htmlspecialchars($oldValue) . "</span></td>";
                            echo "<td><span class='raw-value' style='background: #d1fae5;'>" . htmlspecialchars($newValue) . "</span></td>";
                            echo "</tr>";
                            $previewCount++;
                        }
                    }
                }
                echo "</table>";
                
                if (count($needsFix) > 20) {
                    echo "<p class='info'>... 외 " . (count($needsFix) - 20) . "개 더 있습니다.</p>";
                }
                
                echo "<form method='POST'>";
                echo "<input type='hidden' name='action' value='fix'>";
                echo "<p class='warning'><strong>주의:</strong> 이 작업은 기존 데이터를 '숫자원' 형식으로 변환합니다. (예: 30000.00 → 30000원)</p>";
                echo "<button type='submit'>데이터 수정 실행</button>";
                echo "</form>";
            }
        } else {
            echo "<p class='success'>✓ 모든 데이터가 이미 올바른 형식입니다. 수정이 필요하지 않습니다.</p>";
        }
        
        // 수정 후 결과 확인
        if (isset($_POST['action']) && $_POST['action'] === 'fix') {
            echo "<h2>3. 수정 후 데이터 확인</h2>";
            $stmt = $pdo->query("
                SELECT monthly_fee 
                FROM product_internet_details 
                WHERE monthly_fee != '' AND monthly_fee IS NOT NULL
                ORDER BY id
                LIMIT 10
            ");
            $afterData = $stmt->fetchAll();
            
            if (count($afterData) > 0) {
                echo "<table>";
                echo "<tr><th>수정 후 monthly_fee 값</th></tr>";
                foreach ($afterData as $row) {
                    echo "<tr><td><span class='raw-value'>" . htmlspecialchars($row['monthly_fee']) . "</span></td></tr>";
                }
                echo "</table>";
            }
        }
    } else {
        echo "<p class='info'>수정할 데이터가 없습니다.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>데이터베이스 오류: " . htmlspecialchars($e->getMessage()) . "</p>";
} catch (Exception $e) {
    echo "<p class='error'>오류: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div></body></html>";
?>


