<?php
/**
 * 리뷰 테이블에 application_id 컬럼 추가 스크립트
 * 
 * 이 스크립트는 다음을 수행합니다:
 * 1. product_reviews 테이블에 application_id 컬럼 추가
 * 2. 기존 리뷰에 application_id 연결 (선택적)
 * 
 * 실행 방법: 브라우저에서 http://localhost/MVNO/database/update_add_application_id.php 접속
 */

require_once __DIR__ . '/../includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>리뷰 테이블에 application_id 추가</title>
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
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        .step {
            margin: 20px 0;
            padding: 15px;
            background: #f9fafb;
            border-radius: 8px;
            border-left: 4px solid #6366f1;
        }
        .step-title {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 10px;
        }
        .success {
            color: #10b981;
            font-weight: 600;
        }
        .error {
            color: #ef4444;
            font-weight: 600;
        }
        .info {
            color: #6366f1;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>리뷰 테이블에 application_id 컬럼 추가</h1>
        
        <?php
        $pdo = getDBConnection();
        
        if (!$pdo) {
            echo '<div class="step"><div class="step-title error">❌ 데이터베이스 연결 실패</div>';
            echo '<p>데이터베이스에 연결할 수 없습니다. db-config.php 파일을 확인해주세요.</p></div>';
            exit;
        }
        
        echo '<div class="step"><div class="step-title info">✓ 데이터베이스 연결 성공</div></div>';
        
        $errors = [];
        $success = [];
        
        // 1. application_id 컬럼 추가
        echo '<div class="step">';
        echo '<div class="step-title">1. application_id 컬럼 추가</div>';
        
        try {
            $stmt = $pdo->prepare("
                ALTER TABLE `product_reviews` 
                ADD COLUMN `application_id` INT(11) UNSIGNED DEFAULT NULL COMMENT '신청 ID (application별 리뷰 구분용)' AFTER `product_id`
            ");
            $stmt->execute();
            $success[] = "application_id 컬럼 추가 완료";
            echo '<p class="success">✓ 성공: application_id 컬럼이 추가되었습니다.</p>';
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                $success[] = "application_id 컬럼이 이미 존재합니다.";
                echo '<p class="info">ℹ application_id 컬럼이 이미 존재합니다.</p>';
            } else {
                $errors[] = "application_id 컬럼 추가 실패: " . $e->getMessage();
                echo '<p class="error">❌ 오류: ' . htmlspecialchars($e->getMessage()) . '</p>';
            }
        }
        
        // 2. 인덱스 추가
        try {
            $stmt = $pdo->prepare("
                ALTER TABLE `product_reviews` 
                ADD INDEX `idx_application_id` (`application_id`)
            ");
            $stmt->execute();
            $success[] = "idx_application_id 인덱스 추가 완료";
            echo '<p class="success">✓ 성공: idx_application_id 인덱스가 추가되었습니다.</p>';
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key') !== false || strpos($e->getMessage(), 'already exists') !== false) {
                $success[] = "idx_application_id 인덱스가 이미 존재합니다.";
                echo '<p class="info">ℹ idx_application_id 인덱스가 이미 존재합니다.</p>';
            } else {
                $errors[] = "인덱스 추가 실패: " . $e->getMessage();
                echo '<p class="error">❌ 오류: ' . htmlspecialchars($e->getMessage()) . '</p>';
            }
        }
        
        echo '</div>';
        
        // 최종 결과
        echo '<div class="step">';
        echo '<div class="step-title">최종 결과</div>';
        
        if (empty($errors)) {
            echo '<p class="success">✓ 모든 작업이 성공적으로 완료되었습니다!</p>';
            echo '<ul>';
            foreach ($success as $msg) {
                echo '<li class="success">' . htmlspecialchars($msg) . '</li>';
            }
            echo '</ul>';
            echo '<p style="margin-top: 20px;"><strong>이제 각 신청(application)별로 별도의 리뷰를 작성할 수 있습니다!</strong></p>';
        } else {
            echo '<p class="error">❌ 일부 작업에서 오류가 발생했습니다:</p>';
            echo '<ul>';
            foreach ($errors as $error) {
                echo '<li class="error">' . htmlspecialchars($error) . '</li>';
            }
            echo '</ul>';
        }
        echo '</div>';
        ?>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
            <a href="/MVNO/mypage/internet-order.php" class="button" style="display: inline-block; padding: 12px 24px; background: #6366f1; color: white; text-decoration: none; border-radius: 8px; font-weight: 600;">인터넷 주문 페이지로 이동</a>
        </div>
    </div>
</body>
</html>







