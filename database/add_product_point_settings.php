<?php
/**
 * products 테이블에 포인트 설정 컬럼 추가 스크립트
 * 
 * 실행 방법: http://localhost/MVNO/database/add_product_point_settings.php
 */

require_once __DIR__ . '/../includes/data/db-config.php';

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('데이터베이스 연결에 실패했습니다.');
    }
    
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>포인트 설정 컬럼 추가</title>
        <meta charset='UTF-8'>
        <style>
            body { font-family: 'Nanum Gothic', Arial, sans-serif; padding: 40px; max-width: 800px; margin: 0 auto; background: #f9fafb; }
            .container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
            h1 { color: #1f2937; margin-bottom: 24px; }
            .success { background: #d1fae5; border: 2px solid #10b981; padding: 16px; border-radius: 8px; margin: 16px 0; color: #065f46; }
            .info { background: #dbeafe; border: 2px solid #3b82f6; padding: 16px; border-radius: 8px; margin: 16px 0; color: #1e40af; }
            .error { background: #fee2e2; border: 2px solid #ef4444; padding: 16px; border-radius: 8px; margin: 16px 0; color: #991b1b; }
            pre { background: #f3f4f6; padding: 16px; border-radius: 8px; overflow-x: auto; font-size: 13px; margin: 16px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>포인트 설정 컬럼 추가</h1>";
    
    // 테이블 존재 확인
    $tableExists = $pdo->query("SHOW TABLES LIKE 'products'")->fetch();
    if (!$tableExists) {
        throw new Exception('products 테이블이 존재하지 않습니다.');
    }
    
    $results = [];
    
    // 1. point_setting 컬럼 확인 및 추가
    $checkColumn1 = $pdo->query("SHOW COLUMNS FROM products WHERE Field = 'point_setting'")->fetch();
    if ($checkColumn1) {
        $results[] = ['field' => 'point_setting', 'status' => 'exists', 'message' => 'point_setting 컬럼이 이미 존재합니다.'];
    } else {
        try {
            $pdo->exec("ALTER TABLE products ADD COLUMN point_setting INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '포인트 설정 (0이면 포인트 사용 불가, 1000원 단위)' AFTER application_count");
            $results[] = ['field' => 'point_setting', 'status' => 'added', 'message' => 'point_setting 컬럼이 성공적으로 추가되었습니다.'];
        } catch (PDOException $e) {
            throw new Exception('point_setting 컬럼 추가 실패: ' . $e->getMessage());
        }
    }
    
    // 2. point_benefit_description 컬럼 확인 및 추가
    $checkColumn2 = $pdo->query("SHOW COLUMNS FROM products WHERE Field = 'point_benefit_description'")->fetch();
    if ($checkColumn2) {
        $results[] = ['field' => 'point_benefit_description', 'status' => 'exists', 'message' => 'point_benefit_description 컬럼이 이미 존재합니다.'];
    } else {
        try {
            $pdo->exec("ALTER TABLE products ADD COLUMN point_benefit_description TEXT DEFAULT NULL COMMENT '포인트 사용 시 할인 혜택 내용' AFTER point_setting");
            $results[] = ['field' => 'point_benefit_description', 'status' => 'added', 'message' => 'point_benefit_description 컬럼이 성공적으로 추가되었습니다.'];
        } catch (PDOException $e) {
            throw new Exception('point_benefit_description 컬럼 추가 실패: ' . $e->getMessage());
        }
    }
    
    // 3. 인덱스 추가 (선택사항)
    try {
        $checkIndex = $pdo->query("SHOW INDEX FROM products WHERE Key_name = 'idx_point_setting'")->fetch();
        if (!$checkIndex) {
            $pdo->exec("ALTER TABLE products ADD INDEX idx_point_setting (point_setting)");
            $results[] = ['field' => 'index', 'status' => 'added', 'message' => 'idx_point_setting 인덱스가 추가되었습니다.'];
        }
    } catch (PDOException $e) {
        // 인덱스 추가 실패는 치명적이지 않으므로 무시
    }
    
    // 결과 출력
    foreach ($results as $result) {
        $class = $result['status'] === 'exists' ? 'info' : 'success';
        echo "<div class='{$class}'>{$result['message']}</div>";
    }
    
    // 확인 쿼리
    echo "<div class='info'><strong>확인:</strong> 다음 쿼리로 컬럼이 추가되었는지 확인할 수 있습니다:</div>";
    echo "<pre>SHOW COLUMNS FROM products WHERE Field IN ('point_setting', 'point_benefit_description');</pre>";
    
    echo "<div class='success'><strong>✅ 완료:</strong> 모든 작업이 성공적으로 완료되었습니다.</div>";
    echo "</div></body></html>";
    
} catch (Exception $e) {
    echo "<div class='error'><strong>❌ 오류:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "</div></body></html>";
}
