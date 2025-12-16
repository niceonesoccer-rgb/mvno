<?php
/**
 * product_internet_details 테이블 구조 확인 스크립트
 * 
 * 실행 방법: http://localhost/MVNO/database/check_internet_table.php
 */

require_once __DIR__ . '/../includes/data/db-config.php';

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('데이터베이스 연결에 실패했습니다.');
    }
    
    // 테이블 존재 확인
    $tableExists = $pdo->query("SHOW TABLES LIKE 'product_internet_details'")->fetch();
    
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>테이블 구조 확인</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 40px; max-width: 1000px; margin: 0 auto; }
            .success { background: #d1fae5; border: 2px solid #10b981; padding: 20px; border-radius: 8px; margin: 20px 0; }
            .error { background: #fee2e2; border: 2px solid #ef4444; padding: 20px; border-radius: 8px; margin: 20px 0; }
            .info { background: #dbeafe; border: 2px solid #3b82f6; padding: 20px; border-radius: 8px; margin: 20px 0; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
            th { background-color: #f3f4f6; font-weight: bold; }
            .missing { color: #ef4444; font-weight: bold; }
            .exists { color: #10b981; font-weight: bold; }
        </style>
    </head>
    <body>
        <h1>📊 product_internet_details 테이블 구조 확인</h1>";
    
    if (!$tableExists) {
        echo "<div class='error'>
            <h2>❌ 테이블이 존재하지 않습니다</h2>
            <p>product_internet_details 테이블이 데이터베이스에 없습니다.</p>
            <p>상품 등록 시 자동으로 생성됩니다.</p>
        </div>";
    } else {
        echo "<div class='success'>
            <h2>✅ 테이블이 존재합니다</h2>
        </div>";
        
        // 테이블 구조 확인
        $columns = $pdo->query("SHOW COLUMNS FROM product_internet_details")->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<div class='info'>
            <h2>컬럼 목록</h2>
            <table>
                <thead>
                    <tr>
                        <th>컬럼명</th>
                        <th>타입</th>
                        <th>Null</th>
                        <th>기본값</th>
                        <th>상태</th>
                    </tr>
                </thead>
                <tbody>";
        
        $requiredColumns = [
            'id' => true,
            'product_id' => true,
            'registration_place' => true,
            'service_type' => true,  // 필수 컬럼
            'speed_option' => false,
            'monthly_fee' => true,
            'cash_payment_names' => false,
            'cash_payment_prices' => false,
            'gift_card_names' => false,
            'gift_card_prices' => false,
            'equipment_names' => false,
            'equipment_prices' => false,
            'installation_names' => false,
            'installation_prices' => false,
        ];
        
        $foundColumns = [];
        foreach ($columns as $column) {
            $foundColumns[$column['Field']] = true;
            $isRequired = isset($requiredColumns[$column['Field']]) && $requiredColumns[$column['Field']];
            $status = $isRequired ? '<span class="exists">필수</span>' : '<span>선택</span>';
            
            echo "<tr>
                <td><strong>" . htmlspecialchars($column['Field']) . "</strong></td>
                <td>" . htmlspecialchars($column['Type']) . "</td>
                <td>" . htmlspecialchars($column['Null']) . "</td>
                <td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>
                <td>" . $status . "</td>
            </tr>";
        }
        
        echo "</tbody></table></div>";
        
        // 필수 컬럼 확인
        $missingColumns = [];
        foreach ($requiredColumns as $colName => $isRequired) {
            if ($isRequired && !isset($foundColumns[$colName])) {
                $missingColumns[] = $colName;
            }
        }
        
        if (!empty($missingColumns)) {
            echo "<div class='error'>
                <h2>❌ 누락된 필수 컬럼</h2>
                <ul>";
            foreach ($missingColumns as $col) {
                echo "<li class='missing'>" . htmlspecialchars($col) . "</li>";
            }
            echo "</ul>
                <p>다음 스크립트를 실행하여 컬럼을 추가하세요:</p>
                <p><a href='add_service_type_column.php' style='color: #3b82f6;'>add_service_type_column.php 실행</a></p>
            </div>";
        } else {
            echo "<div class='success'>
                <h2>✅ 모든 필수 컬럼이 존재합니다</h2>
                <p>테이블 구조가 정상입니다.</p>
            </div>";
        }
        
        // service_type 컬럼 상세 확인
        $serviceTypeColumn = null;
        foreach ($columns as $column) {
            if ($column['Field'] === 'service_type') {
                $serviceTypeColumn = $column;
                break;
            }
        }
        
        if ($serviceTypeColumn) {
            echo "<div class='info'>
                <h2>service_type 컬럼 상세</h2>
                <ul>
                    <li><strong>타입:</strong> " . htmlspecialchars($serviceTypeColumn['Type']) . "</li>
                    <li><strong>Null 허용:</strong> " . htmlspecialchars($serviceTypeColumn['Null']) . "</li>
                    <li><strong>기본값:</strong> " . htmlspecialchars($serviceTypeColumn['Default'] ?? 'NULL') . "</li>
                </ul>
            </div>";
        } else {
            echo "<div class='error'>
                <h2>❌ service_type 컬럼이 없습니다</h2>
                <p>다음 스크립트를 실행하여 컬럼을 추가하세요:</p>
                <p><a href='add_service_type_column.php' style='color: #3b82f6; font-weight: bold;'>add_service_type_column.php 실행</a></p>
            </div>";
        }
    }
    
    echo "<p><a href='../'>홈으로 돌아가기</a></p>
    </body>
    </html>";
    
} catch (Exception $e) {
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>오류</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 40px; max-width: 800px; margin: 0 auto; }
            .error { background: #fee2e2; border: 2px solid #ef4444; padding: 20px; border-radius: 8px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <h1>❌ 오류</h1>
        <div class='error'>
            <p>오류가 발생했습니다: " . htmlspecialchars($e->getMessage()) . "</p>
        </div>
    </body>
    </html>";
    
    error_log("Check internet table error: " . $e->getMessage());
}
