<?php
/**
 * product_internet_details 테이블에 service_type 컬럼 추가 스크립트
 * 
 * 실행 방법: http://localhost/MVNO/database/add_service_type_column.php
 */

require_once __DIR__ . '/../includes/data/db-config.php';

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('데이터베이스 연결에 실패했습니다.');
    }
    
    // 테이블 존재 확인
    $tableExists = $pdo->query("SHOW TABLES LIKE 'product_internet_details'")->fetch();
    if (!$tableExists) {
        throw new Exception('product_internet_details 테이블이 존재하지 않습니다.');
    }
    
    // service_type 컬럼 확인
    $checkColumn = $pdo->query("SHOW COLUMNS FROM product_internet_details WHERE Field = 'service_type'")->fetch();
    
    if ($checkColumn) {
        echo "<!DOCTYPE html>
        <html>
        <head>
            <title>컬럼 추가 완료</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 40px; max-width: 800px; margin: 0 auto; }
                .success { background: #d1fae5; border: 2px solid #10b981; padding: 20px; border-radius: 8px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <h1>✅ 완료</h1>
            <div class='success'>
                <p>service_type 컬럼이 이미 존재합니다.</p>
            </div>
        </body>
        </html>";
        exit;
    }
    
    // 컬럼 추가
    $pdo->exec("ALTER TABLE product_internet_details ADD COLUMN service_type VARCHAR(20) NOT NULL DEFAULT '인터넷' COMMENT '서비스 타입 (인터넷 또는 인터넷+TV)' AFTER registration_place");
    
    // 인덱스 추가 시도
    try {
        $checkIndex = $pdo->query("SHOW INDEX FROM product_internet_details WHERE Key_name = 'idx_service_type'")->fetch();
        if (!$checkIndex) {
            $pdo->exec("ALTER TABLE product_internet_details ADD KEY idx_service_type (service_type)");
        }
    } catch (PDOException $e) {
        // 인덱스가 이미 있으면 무시
    }
    
    // 기존 데이터에 기본값 설정
    $pdo->exec("UPDATE product_internet_details SET service_type = '인터넷' WHERE service_type IS NULL OR service_type = ''");
    
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>컬럼 추가 완료</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 40px; max-width: 800px; margin: 0 auto; }
            .success { background: #d1fae5; border: 2px solid #10b981; padding: 20px; border-radius: 8px; margin: 20px 0; }
            .info { background: #dbeafe; border: 2px solid #3b82f6; padding: 20px; border-radius: 8px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <h1>✅ 컬럼 추가 완료</h1>
        <div class='success'>
            <h2>service_type 컬럼이 성공적으로 추가되었습니다.</h2>
            <p>이제 인터넷 상품 등록/수정이 정상적으로 작동합니다.</p>
        </div>
        <div class='info'>
            <p>추가된 컬럼:</p>
            <ul>
                <li><strong>service_type</strong>: VARCHAR(20), 기본값 '인터넷'</li>
                <li>인덱스: idx_service_type</li>
            </ul>
            <p>기존 데이터는 모두 '인터넷'으로 설정되었습니다.</p>
        </div>
        <p><a href='../'>홈으로 돌아가기</a></p>
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
    
    error_log("Add service_type column error: " . $e->getMessage());
}
