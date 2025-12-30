<?php
/**
 * product_applications 테이블에 status_changed_at 필드 추가
 * 
 * 브라우저에서 이 파일을 실행하면:
 * 1. status_changed_at 필드가 추가됩니다
 * 2. 기존 데이터의 updated_at 값을 status_changed_at에 복사합니다
 * 
 * URL: http://localhost/MVNO/database/add_status_changed_at.php
 */

require_once __DIR__ . '/../includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

// 보안: 관리자만 실행 가능하도록 체크 (선택사항)
// require_once __DIR__ . '/../includes/data/auth-functions.php';
// if (!isLoggedIn() || getCurrentUser()['role'] !== 'admin') {
//     die('관리자만 실행할 수 있습니다.');
// }

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>상태 변경일시 필드 추가</title>
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
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: #6366f1;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 20px;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .button:hover {
            background: #4f46e5;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>상태 변경일시 필드 추가</h1>
        
        <?php
        try {
            $pdo = getDBConnection();
            if (!$pdo) {
                throw new Exception('데이터베이스 연결에 실패했습니다.');
            }
            
            // 1. 필드 존재 여부 확인
            $checkStmt = $pdo->query("
                SELECT COUNT(*) as count
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'product_applications'
                AND COLUMN_NAME = 'status_changed_at'
            ");
            $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);
            $fieldExists = $checkResult['count'] > 0;
            
            if ($fieldExists) {
                echo '<div class="info">';
                echo '<strong>알림:</strong> status_changed_at 필드가 이미 존재합니다.';
                echo '</div>';
            } else {
                // 2. 필드 추가
                echo '<div class="info">';
                echo '<strong>진행 중:</strong> status_changed_at 필드를 추가합니다...';
                echo '</div>';
                
                $pdo->exec("
                    ALTER TABLE `product_applications` 
                    ADD COLUMN `status_changed_at` DATETIME DEFAULT NULL COMMENT '상태 변경일시' 
                    AFTER `application_status`
                ");
                
                echo '<div class="success">';
                echo '<strong>성공:</strong> status_changed_at 필드가 추가되었습니다.';
                echo '</div>';
            }
            
            // 3. 기존 데이터 업데이트 (status_changed_at이 NULL인 경우)
            $updateStmt = $pdo->prepare("
                UPDATE `product_applications` 
                SET `status_changed_at` = `updated_at` 
                WHERE `status_changed_at` IS NULL
            ");
            $updateStmt->execute();
            $affectedRows = $updateStmt->rowCount();
            
            if ($affectedRows > 0) {
                echo '<div class="success">';
                echo '<strong>성공:</strong> ' . number_format($affectedRows) . '개의 레코드가 업데이트되었습니다.';
                echo '</div>';
            } else {
                echo '<div class="info">';
                echo '<strong>알림:</strong> 업데이트할 레코드가 없습니다. (모든 레코드에 이미 status_changed_at 값이 있습니다)';
                echo '</div>';
            }
            
            // 4. 결과 확인
            $verifyStmt = $pdo->query("
                SELECT 
                    COUNT(*) as total,
                    COUNT(status_changed_at) as with_status_changed_at,
                    COUNT(*) - COUNT(status_changed_at) as without_status_changed_at
                FROM product_applications
            ");
            $verifyResult = $verifyStmt->fetch(PDO::FETCH_ASSOC);
            
            echo '<div class="info">';
            echo '<strong>현재 상태:</strong><br>';
            echo '전체 레코드: ' . number_format($verifyResult['total']) . '개<br>';
            echo 'status_changed_at 값 있음: ' . number_format($verifyResult['with_status_changed_at']) . '개<br>';
            echo 'status_changed_at 값 없음: ' . number_format($verifyResult['without_status_changed_at']) . '개';
            echo '</div>';
            
            echo '<div class="success">';
            echo '<strong>완료!</strong> 모든 작업이 성공적으로 완료되었습니다.';
            echo '</div>';
            
        } catch (PDOException $e) {
            echo '<div class="error">';
            echo '<strong>오류 발생:</strong><br>';
            echo htmlspecialchars($e->getMessage());
            echo '</div>';
            
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo '<div class="info">';
                echo '필드가 이미 존재합니다. 이는 정상적인 상황입니다.';
                echo '</div>';
            }
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
ADD COLUMN `status_changed_at` DATETIME DEFAULT NULL COMMENT '상태 변경일시' 
AFTER `application_status`;

UPDATE `product_applications` 
SET `status_changed_at` = `updated_at` 
WHERE `status_changed_at` IS NULL;</pre>
        </div>
    </div>
</body>
</html>




















