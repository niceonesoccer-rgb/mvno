<?php
/**
 * application_customers 테이블에 user_id 컬럼 추가 스크립트
 * 
 * 사용법: 브라우저에서 http://localhost/MVNO/database/add_user_id_column.php 접속
 * 실행 후 이 파일은 삭제하거나 보안을 위해 .htaccess로 접근을 차단하세요.
 */

require_once __DIR__ . '/../includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>user_id 컬럼 추가</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .success {
            background-color: #d1fae5;
            color: #065f46;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #10b981;
        }
        .error {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #ef4444;
        }
        .info {
            background-color: #dbeafe;
            color: #1e40af;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #3b82f6;
        }
        .warning {
            background-color: #fef3c7;
            color: #92400e;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #f59e0b;
        }
        pre {
            background-color: #f3f4f6;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>application_customers 테이블 - user_id 컬럼 추가</h1>
        
        <?php
        try {
            $pdo = getDBConnection();
            if (!$pdo) {
                throw new Exception('데이터베이스 연결에 실패했습니다.');
            }
            
            echo '<div class="info">데이터베이스 연결 성공</div>';
            
            // 1. 컬럼 존재 여부 확인
            $checkColumn = $pdo->query("SHOW COLUMNS FROM application_customers LIKE 'user_id'");
            $columnExists = (bool)$checkColumn->fetch();
            
            if ($columnExists) {
                echo '<div class="success">✓ user_id 컬럼이 이미 존재합니다.</div>';
            } else {
                echo '<div class="warning">⚠ user_id 컬럼이 없습니다. 추가합니다...</div>';
                
                // 2. 컬럼 추가
                try {
                    $pdo->exec("ALTER TABLE application_customers ADD COLUMN user_id VARCHAR(50) DEFAULT NULL COMMENT '회원 user_id (비회원 신청 가능)' AFTER application_id");
                    echo '<div class="success">✓ user_id 컬럼이 성공적으로 추가되었습니다.</div>';
                } catch (PDOException $e) {
                    throw new Exception('컬럼 추가 실패: ' . $e->getMessage());
                }
            }
            
            // 3. 인덱스 존재 여부 확인
            $checkIndex = $pdo->query("SHOW INDEX FROM application_customers WHERE Key_name = 'idx_user_id'");
            $indexExists = (bool)$checkIndex->fetch();
            
            if ($indexExists) {
                echo '<div class="info">✓ idx_user_id 인덱스가 이미 존재합니다.</div>';
            } else {
                echo '<div class="warning">⚠ idx_user_id 인덱스가 없습니다. 추가합니다...</div>';
                
                // 4. 인덱스 추가
                try {
                    $pdo->exec("ALTER TABLE application_customers ADD INDEX idx_user_id (user_id)");
                    echo '<div class="success">✓ idx_user_id 인덱스가 성공적으로 추가되었습니다.</div>';
                } catch (PDOException $e) {
                    // 인덱스 추가 실패는 치명적이지 않으므로 경고만 표시
                    echo '<div class="warning">⚠ 인덱스 추가 중 오류 (무시됨): ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
            }
            
            // 5. 최종 확인
            $finalCheck = $pdo->query("SHOW COLUMNS FROM application_customers LIKE 'user_id'");
            if ($finalCheck->fetch()) {
                echo '<div class="success"><strong>✓ 완료!</strong> application_customers 테이블에 user_id 컬럼이 준비되었습니다.</div>';
                echo '<div class="info">이제 인터넷 상담신청이 정상적으로 작동할 것입니다.</div>';
            }
            
            // 테이블 구조 확인
            echo '<h2>현재 테이블 구조</h2>';
            $columns = $pdo->query("SHOW COLUMNS FROM application_customers");
            echo '<pre>';
            echo "application_customers 테이블 컬럼:\n";
            echo str_repeat('-', 80) . "\n";
            while ($row = $columns->fetch(PDO::FETCH_ASSOC)) {
                printf("%-20s %-20s %-10s %s\n", 
                    $row['Field'], 
                    $row['Type'], 
                    $row['Null'], 
                    $row['Key'] ? '[' . $row['Key'] . ']' : ''
                );
            }
            echo '</pre>';
            
        } catch (Exception $e) {
            echo '<div class="error"><strong>오류 발생:</strong> ' . htmlspecialchars($e->getMessage()) . '</div>';
            echo '<div class="info">데이터베이스 연결 정보를 확인하거나 서버 로그를 확인하세요.</div>';
        }
        ?>
        
        <div class="info" style="margin-top: 30px;">
            <strong>보안 안내:</strong><br>
            이 스크립트 실행 후에는 보안을 위해 이 파일을 삭제하거나 .htaccess로 접근을 차단하는 것을 권장합니다.
        </div>
    </div>
</body>
</html>











