<?php
/**
 * product_applications 테이블의 application_status ENUM에 'closed' 값 추가
 * 
 * 브라우저에서 이 파일을 실행하면:
 * 1. application_status ENUM에 'closed' 값이 추가됩니다
 * 
 * URL: http://localhost/MVNO/database/add_closed_status.php
 */

require_once __DIR__ . '/../includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>종료 상태 추가</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
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
            color: #1f2937;
            margin-bottom: 20px;
        }
        .success {
            background: #d1fae5;
            color: #065f46;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            border-left: 4px solid #10b981;
        }
        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            border-left: 4px solid #ef4444;
        }
        .info {
            background: #dbeafe;
            color: #1e40af;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            border-left: 4px solid #3b82f6;
        }
        pre {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 6px;
            overflow-x: auto;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>종료 상태 추가</h1>
        
        <?php
        try {
            $pdo = getDBConnection();
            if (!$pdo) {
                throw new Exception('데이터베이스 연결에 실패했습니다.');
            }
            
            // 현재 ENUM 값 확인
            $checkStmt = $pdo->query("
                SELECT COLUMN_TYPE 
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'product_applications'
                AND COLUMN_NAME = 'application_status'
            ");
            $currentEnum = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($currentEnum) {
                echo '<div class="info">';
                echo '<strong>현재 ENUM 값:</strong><br>';
                echo htmlspecialchars($currentEnum['COLUMN_TYPE']);
                echo '</div>';
                
                // 'closed'가 이미 포함되어 있는지 확인
                if (strpos($currentEnum['COLUMN_TYPE'], 'closed') !== false) {
                    echo '<div class="info">';
                    echo '<strong>알림:</strong> application_status ENUM에 이미 \'closed\' 값이 포함되어 있습니다.';
                    echo '</div>';
                } else {
                    // ENUM에 'closed' 추가
                    echo '<div class="info">';
                    echo '<strong>진행 중:</strong> application_status ENUM에 \'closed\' 값을 추가합니다...';
                    echo '</div>';
                    
                    // ENUM에 값 추가 (기존 값 유지)
                    $pdo->exec("
                        ALTER TABLE `product_applications` 
                        MODIFY COLUMN `application_status` 
                        ENUM('pending', 'processing', 'completed', 'cancelled', 'rejected', 'closed') 
                        NOT NULL DEFAULT 'pending' 
                        COMMENT '신청 상태'
                    ");
                    
                    echo '<div class="success">';
                    echo '<strong>성공:</strong> application_status ENUM에 \'closed\' 값이 추가되었습니다.';
                    echo '</div>';
                }
            } else {
                echo '<div class="error">';
                echo '<strong>오류:</strong> product_applications 테이블 또는 application_status 컬럼을 찾을 수 없습니다.';
                echo '</div>';
            }
            
            // 최종 확인
            $verifyStmt = $pdo->query("
                SELECT COLUMN_TYPE 
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'product_applications'
                AND COLUMN_NAME = 'application_status'
            ");
            $finalEnum = $verifyStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($finalEnum) {
                echo '<div class="success">';
                echo '<strong>최종 ENUM 값:</strong><br>';
                echo htmlspecialchars($finalEnum['COLUMN_TYPE']);
                echo '</div>';
            }
            
            echo '<div class="success">';
            echo '<strong>완료!</strong> 모든 작업이 성공적으로 완료되었습니다.';
            echo '</div>';
            
        } catch (PDOException $e) {
            echo '<div class="error">';
            echo '<strong>오류 발생:</strong><br>';
            echo htmlspecialchars($e->getMessage());
            echo '</div>';
        } catch (Exception $e) {
            echo '<div class="error">';
            echo '<strong>오류 발생:</strong><br>';
            echo htmlspecialchars($e->getMessage());
            echo '</div>';
        }
        ?>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
            <p><strong>실행된 SQL:</strong></p>
            <pre>ALTER TABLE `product_applications` 
MODIFY COLUMN `application_status` 
ENUM('pending', 'processing', 'completed', 'cancelled', 'rejected', 'closed') 
NOT NULL DEFAULT 'pending' 
COMMENT '신청 상태';</pre>
        </div>
    </div>
</body>
</html>









