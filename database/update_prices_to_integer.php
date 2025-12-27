<?php
/**
 * 기존 DB에 저장된 소수점 금액을 정수로 변경하는 스크립트
 * 
 * 실행 방법:
 * 1. 브라우저에서 http://localhost/MVNO/database/update_prices_to_integer.php 접속
 * 2. 또는 CLI에서: php database/update_prices_to_integer.php
 */

require_once __DIR__ . '/../includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>금액 소수점 제거 스크립트</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #10b981;
            padding-bottom: 10px;
        }
        .step {
            margin: 20px 0;
            padding: 15px;
            background: #f9fafb;
            border-left: 4px solid #10b981;
            border-radius: 4px;
        }
        .step-title {
            font-weight: bold;
            color: #10b981;
            margin-bottom: 10px;
        }
        .success {
            color: #059669;
            font-weight: bold;
            margin: 10px 0;
        }
        .error {
            color: #dc2626;
            font-weight: bold;
            margin: 10px 0;
        }
        .info {
            color: #6b7280;
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #10b981;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 10px 5px;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            background: #059669;
        }
        .btn-danger {
            background: #ef4444;
        }
        .btn-danger:hover {
            background: #dc2626;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>💰 금액 소수점 제거 스크립트</h1>
        <p>기존 DB에 저장된 소수점 금액을 정수로 변경합니다.</p>
        
        <?php
        try {
            $pdo = getDBConnection();
            if (!$pdo) {
                throw new Exception('데이터베이스 연결에 실패했습니다.');
            }
            
            echo '<div class="step">';
            echo '<div class="step-title">1. 데이터베이스 연결 확인</div>';
            echo '<div class="success">✅ 데이터베이스 연결 성공</div>';
            echo '</div>';
            
            // 실행 여부 확인
            $execute = isset($_GET['execute']) && $_GET['execute'] === 'yes';
            
            if (!$execute) {
                // 미리보기 모드
                echo '<div class="step">';
                echo '<div class="step-title">2. 변경될 데이터 미리보기</div>';
                
                $tables = [
                    'product_mvno_details' => ['price_main', 'price_after'],
                    'product_mno_sim_details' => ['price_main', 'price_after'],
                    'product_mno_details' => ['price_main'],
                    'product_internet_details' => ['monthly_fee']
                ];
                
                $totalAffected = 0;
                $previewData = [];
                
                foreach ($tables as $table => $columns) {
                    foreach ($columns as $column) {
                        // 소수점이 있는 레코드 찾기
                        $stmt = $pdo->prepare("
                            SELECT id, product_id, {$column}
                            FROM {$table}
                            WHERE {$column} IS NOT NULL 
                            AND ({$column} % 1) != 0
                            LIMIT 100
                        ");
                        $stmt->execute();
                        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (count($rows) > 0) {
                            $previewData[$table][$column] = $rows;
                            $totalAffected += count($rows);
                            
                            // 전체 개수 확인
                            $countStmt = $pdo->prepare("
                                SELECT COUNT(*) as total
                                FROM {$table}
                                WHERE {$column} IS NOT NULL 
                                AND ({$column} % 1) != 0
                            ");
                            $countStmt->execute();
                            $totalCount = $countStmt->fetch()['total'];
                            
                            echo "<div class='info'>📊 {$table}.{$column}: 총 {$totalCount}개 레코드 (미리보기: " . count($rows) . "개)</div>";
                            
                            if (count($rows) > 0) {
                                echo "<table>";
                                echo "<thead><tr><th>ID</th><th>Product ID</th><th>현재 값</th><th>변경될 값</th></tr></thead>";
                                echo "<tbody>";
                                foreach ($rows as $row) {
                                    $currentValue = $row[$column];
                                    $newValue = intval($currentValue);
                                    echo "<tr>";
                                    echo "<td>{$row['id']}</td>";
                                    echo "<td>{$row['product_id']}</td>";
                                    echo "<td>{$currentValue}</td>";
                                    echo "<td><strong>{$newValue}</strong></td>";
                                    echo "</tr>";
                                }
                                echo "</tbody></table>";
                            }
                        }
                    }
                }
                
                if ($totalAffected === 0) {
                    echo '<div class="success">✅ 변경할 데이터가 없습니다. 모든 금액이 이미 정수로 저장되어 있습니다.</div>';
                } else {
                    echo "<div class='info'>총 {$totalAffected}개 이상의 레코드가 변경됩니다.</div>";
                    echo '<a href="?execute=yes" class="btn btn-danger" onclick="return confirm(\'정말로 실행하시겠습니까? 이 작업은 되돌릴 수 없습니다.\');">실행하기</a>';
                }
                
                echo '</div>';
            } else {
                // 실행 모드
                echo '<div class="step">';
                echo '<div class="step-title">2. 데이터 업데이트 실행</div>';
                
                $pdo->beginTransaction();
                
                $tables = [
                    'product_mvno_details' => ['price_main', 'price_after'],
                    'product_mno_sim_details' => ['price_main', 'price_after'],
                    'product_mno_details' => ['price_main'],
                    'product_internet_details' => ['monthly_fee']
                ];
                
                $totalUpdated = 0;
                
                foreach ($tables as $table => $columns) {
                    foreach ($columns as $column) {
                        // 소수점이 있는 레코드를 정수로 업데이트
                        $updateStmt = $pdo->prepare("
                            UPDATE {$table}
                            SET {$column} = CAST({$column} AS UNSIGNED)
                            WHERE {$column} IS NOT NULL 
                            AND ({$column} % 1) != 0
                        ");
                        $updateStmt->execute();
                        $affected = $updateStmt->rowCount();
                        
                        if ($affected > 0) {
                            echo "<div class='success'>✅ {$table}.{$column}: {$affected}개 레코드 업데이트 완료</div>";
                            $totalUpdated += $affected;
                        }
                    }
                }
                
                $pdo->commit();
                
                echo "<div class='success' style='font-size: 18px; margin-top: 20px;'>✅ 총 {$totalUpdated}개 레코드가 정수로 변경되었습니다!</div>";
                echo '</div>';
                
                echo '<div class="step">';
                echo '<div class="step-title">3. 변경 결과 확인</div>';
                
                // 변경 후 확인
                foreach ($tables as $table => $columns) {
                    foreach ($columns as $column) {
                        $checkStmt = $pdo->prepare("
                            SELECT COUNT(*) as total
                            FROM {$table}
                            WHERE {$column} IS NOT NULL 
                            AND ({$column} % 1) != 0
                        ");
                        $checkStmt->execute();
                        $remaining = $checkStmt->fetch()['total'];
                        
                        if ($remaining > 0) {
                            echo "<div class='error'>⚠️ {$table}.{$column}: 아직 {$remaining}개 레코드에 소수점이 남아있습니다.</div>";
                        } else {
                            echo "<div class='success'>✅ {$table}.{$column}: 모든 값이 정수입니다.</div>";
                        }
                    }
                }
                
                echo '</div>';
            }
            
        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            
            echo '<div class="error">❌ 오류 발생: ' . htmlspecialchars($e->getMessage()) . '</div>';
            echo '<div class="info">스택 트레이스: <pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre></div>';
        }
        ?>
        
        <div class="step">
            <div class="step-title">참고사항</div>
            <ul>
                <li>이 스크립트는 소수점이 있는 금액을 정수로 반올림합니다 (예: 34000.50 → 34000)</li>
                <li>변경된 데이터는 되돌릴 수 없으므로 실행 전에 데이터베이스 백업을 권장합니다.</li>
                <li>앞으로 새로 등록/수정되는 상품은 자동으로 정수로 저장됩니다.</li>
            </ul>
        </div>
    </div>
</body>
</html>

