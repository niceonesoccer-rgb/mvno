<?php
/**
 * product_applications 테이블의 application_status ENUM 업데이트
 * 
 * 기존: 'pending', 'processing', 'completed', 'cancelled', 'rejected', 'closed'
 * 추가: 'received', 'activating', 'on_hold', 'activation_completed', 'installation_completed'
 */

require_once __DIR__ . '/../includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>application_status ENUM 업데이트</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
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
            border-bottom: 2px solid #10b981;
            padding-bottom: 10px;
        }
        .success {
            color: #059669;
            background: #d1fae5;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .error {
            color: #dc2626;
            background: #fee2e2;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .info {
            color: #1e40af;
            background: #dbeafe;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .warning {
            color: #92400e;
            background: #fef3c7;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        button {
            background: #10b981;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
        }
        button:hover {
            background: #059669;
        }
        pre {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>application_status ENUM 업데이트</h1>
        
        <?php
        try {
            $pdo = getDBConnection();
            
            if (!$pdo) {
                throw new Exception('데이터베이스 연결에 실패했습니다.');
            }
            
            // 현재 ENUM 값 확인
            $stmt = $pdo->query("
                SELECT COLUMN_TYPE 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'product_applications' 
                AND COLUMN_NAME = 'application_status'
            ");
            $currentEnum = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$currentEnum) {
                throw new Exception('application_status 컬럼을 찾을 수 없습니다.');
            }
            
            $currentEnumStr = $currentEnum['COLUMN_TYPE'];
            echo '<div class="info"><strong>현재 ENUM 값:</strong> ' . htmlspecialchars($currentEnumStr) . '</div>';
            
            // 필요한 모든 상태 값
            $requiredStatuses = [
                'pending',
                'received',
                'processing',
                'activating',
                'on_hold',
                'cancelled',
                'rejected',
                'activation_completed',
                'installation_completed',
                'completed',
                'closed'
            ];
            
            // 현재 ENUM에서 값 추출
            preg_match("/ENUM\('(.*)'\)/", $currentEnumStr, $matches);
            $currentValues = $matches[1] ? explode("','", $matches[1]) : [];
            
            // 추가할 값 찾기
            $valuesToAdd = array_diff($requiredStatuses, $currentValues);
            
            if (empty($valuesToAdd)) {
                echo '<div class="success"><strong>완료:</strong> application_status ENUM에 필요한 모든 값이 이미 포함되어 있습니다.</div>';
            } else {
                echo '<div class="warning"><strong>추가할 값:</strong> ' . implode(', ', $valuesToAdd) . '</div>';
                
                // 모든 값 결합
                $allValues = array_unique(array_merge($currentValues, $requiredStatuses));
                $enumValues = "'" . implode("','", $allValues) . "'";
                
                // ALTER TABLE 실행
                $alterSql = "ALTER TABLE `product_applications` 
                            MODIFY COLUMN `application_status` 
                            ENUM($enumValues) 
                            NOT NULL DEFAULT 'pending' 
                            COMMENT '신청 상태'";
                
                echo '<div class="info"><strong>실행할 SQL:</strong><pre>' . htmlspecialchars($alterSql) . '</pre></div>';
                
                if (isset($_POST['execute'])) {
                    try {
                        $pdo->exec($alterSql);
                        
                        // 업데이트 확인
                        $stmt = $pdo->query("
                            SELECT COLUMN_TYPE 
                            FROM INFORMATION_SCHEMA.COLUMNS 
                            WHERE TABLE_SCHEMA = DATABASE() 
                            AND TABLE_NAME = 'product_applications' 
                            AND COLUMN_NAME = 'application_status'
                        ");
                        $updatedEnum = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        echo '<div class="success"><strong>성공:</strong> application_status ENUM이 업데이트되었습니다.</div>';
                        echo '<div class="info"><strong>업데이트된 ENUM 값:</strong> ' . htmlspecialchars($updatedEnum['COLUMN_TYPE']) . '</div>';
                        echo '<div class="success" style="margin-top: 20px;"><strong>다음 단계:</strong> 이제 주문 관리 페이지에서 상태 변경이 정상적으로 작동합니다.</div>';
                    } catch (PDOException $e) {
                        echo '<div class="error"><strong>오류 발생:</strong> ' . htmlspecialchars($e->getMessage()) . '</div>';
                        if (isset($pdo)) {
                            $errorInfo = $pdo->errorInfo();
                            if ($errorInfo[0] !== '00000') {
                                echo '<div class="error"><strong>데이터베이스 오류:</strong> ' . htmlspecialchars($errorInfo[2]) . '</div>';
                            }
                        }
                    }
                } else {
                    echo '<form method="POST">';
                    echo '<button type="submit" name="execute">ENUM 업데이트 실행</button>';
                    echo '</form>';
                    echo '<div class="warning" style="margin-top: 15px;">';
                    echo '<strong>주의:</strong> 이 작업은 데이터베이스 구조를 변경합니다. 실행 전에 데이터베이스 백업을 권장합니다.';
                    echo '</div>';
                }
            }
            
        } catch (Exception $e) {
            echo '<div class="error"><strong>오류:</strong> ' . htmlspecialchars($e->getMessage()) . '</div>';
            if (isset($pdo)) {
                $errorInfo = $pdo->errorInfo();
                if ($errorInfo[0] !== '00000') {
                    echo '<div class="error"><strong>데이터베이스 오류:</strong> ' . htmlspecialchars($errorInfo[2]) . '</div>';
                }
            }
        }
        ?>
    </div>
</body>
</html>


















