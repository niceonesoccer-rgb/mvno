<?php
/**
 * deposit_requests 테이블의 세금계산서 관련 컬럼 마이그레이션
 * 
 * 변경사항:
 * - tax_invoice_issued (TINYINT) → tax_invoice_status (ENUM)
 * - tax_invoice_period_start, tax_invoice_period_end 컬럼 제거
 */

require_once __DIR__ . '/../includes/data/db-config.php';

$pdo = getDBConnection();

if (!$pdo) {
    die('데이터베이스 연결 실패');
}

try {
    $pdo->beginTransaction();
    
    // 1. 기존 컬럼 확인
    $stmt = $pdo->query("SHOW COLUMNS FROM deposit_requests LIKE 'tax_invoice%'");
    $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>세금계산서 발행 상태 마이그레이션</h2>";
    echo "<pre>";
    
    // 2. tax_invoice_issued 컬럼이 있으면 데이터 백업 후 제거
    if (in_array('tax_invoice_issued', $existingColumns)) {
        echo "1. 기존 tax_invoice_issued 컬럼 데이터 확인 중...\n";
        
        // 기존 데이터 확인
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM deposit_requests WHERE tax_invoice_issued = 1");
        $issuedCount = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
        echo "   - 발행 완료로 표시된 건수: {$issuedCount}건\n";
        
        // tax_invoice_status 컬럼이 없으면 생성
        if (!in_array('tax_invoice_status', $existingColumns)) {
            echo "2. tax_invoice_status 컬럼 생성 중...\n";
            $pdo->exec("
                ALTER TABLE deposit_requests 
                ADD COLUMN tax_invoice_status ENUM('unissued', 'issued', 'cancelled') NOT NULL DEFAULT 'unissued' 
                COMMENT '세금계산서 발행 상태 (미발행, 발행, 취소)' 
                AFTER rejected_reason
            ");
            echo "   ✓ tax_invoice_status 컬럼 생성 완료\n";
        }
        
        // 기존 데이터 마이그레이션
        echo "3. 기존 데이터 마이그레이션 중...\n";
        $pdo->exec("
            UPDATE deposit_requests 
            SET tax_invoice_status = CASE 
                WHEN tax_invoice_issued = 1 THEN 'issued'
                ELSE 'unissued'
            END
        ");
        echo "   ✓ 데이터 마이그레이션 완료\n";
        
        // 기존 컬럼 제거
        echo "4. 기존 컬럼 제거 중...\n";
        $pdo->exec("ALTER TABLE deposit_requests DROP COLUMN tax_invoice_issued");
        echo "   ✓ tax_invoice_issued 컬럼 제거 완료\n";
    } else {
        // tax_invoice_status 컬럼이 없으면 생성
        if (!in_array('tax_invoice_status', $existingColumns)) {
            echo "1. tax_invoice_status 컬럼 생성 중...\n";
            $pdo->exec("
                ALTER TABLE deposit_requests 
                ADD COLUMN tax_invoice_status ENUM('unissued', 'issued', 'cancelled') NOT NULL DEFAULT 'unissued' 
                COMMENT '세금계산서 발행 상태 (미발행, 발행, 취소)' 
                AFTER rejected_reason
            ");
            echo "   ✓ tax_invoice_status 컬럼 생성 완료\n";
        } else {
            echo "1. tax_invoice_status 컬럼이 이미 존재합니다.\n";
        }
    }
    
    // 3. tax_invoice_period_start, tax_invoice_period_end 컬럼 제거 (있는 경우)
    if (in_array('tax_invoice_period_start', $existingColumns)) {
        echo "5. tax_invoice_period_start 컬럼 제거 중...\n";
        $pdo->exec("ALTER TABLE deposit_requests DROP COLUMN tax_invoice_period_start");
        echo "   ✓ tax_invoice_period_start 컬럼 제거 완료\n";
    }
    
    if (in_array('tax_invoice_period_end', $existingColumns)) {
        echo "6. tax_invoice_period_end 컬럼 제거 중...\n";
        $pdo->exec("ALTER TABLE deposit_requests DROP COLUMN tax_invoice_period_end");
        echo "   ✓ tax_invoice_period_end 컬럼 제거 완료\n";
    }
    
    // 4. tax_invoice_issued_at, tax_invoice_issued_by 컬럼 확인 및 생성
    if (!in_array('tax_invoice_issued_at', $existingColumns)) {
        echo "7. tax_invoice_issued_at 컬럼 생성 중...\n";
        $pdo->exec("
            ALTER TABLE deposit_requests 
            ADD COLUMN tax_invoice_issued_at DATETIME DEFAULT NULL 
            COMMENT '세금계산서 발행 일시' 
            AFTER tax_invoice_status
        ");
        echo "   ✓ tax_invoice_issued_at 컬럼 생성 완료\n";
    }
    
    if (!in_array('tax_invoice_issued_by', $existingColumns)) {
        echo "8. tax_invoice_issued_by 컬럼 생성 중...\n";
        $pdo->exec("
            ALTER TABLE deposit_requests 
            ADD COLUMN tax_invoice_issued_by VARCHAR(50) DEFAULT NULL 
            COMMENT '세금계산서 발행 처리한 관리자 ID' 
            AFTER tax_invoice_issued_at
        ");
        echo "   ✓ tax_invoice_issued_by 컬럼 생성 완료\n";
    }
    
    // 5. 인덱스 확인 및 생성
    $stmt = $pdo->query("SHOW INDEX FROM deposit_requests WHERE Key_name = 'idx_tax_invoice_status'");
    if ($stmt->rowCount() == 0) {
        echo "9. idx_tax_invoice_status 인덱스 생성 중...\n";
        $pdo->exec("ALTER TABLE deposit_requests ADD INDEX idx_tax_invoice_status (tax_invoice_status)");
        echo "   ✓ 인덱스 생성 완료\n";
    }
    
    // 6. 기존 idx_tax_invoice_issued 인덱스 제거 (있는 경우)
    $stmt = $pdo->query("SHOW INDEX FROM deposit_requests WHERE Key_name = 'idx_tax_invoice_issued'");
    if ($stmt->rowCount() > 0) {
        echo "10. 기존 idx_tax_invoice_issued 인덱스 제거 중...\n";
        $pdo->exec("ALTER TABLE deposit_requests DROP INDEX idx_tax_invoice_issued");
        echo "   ✓ 인덱스 제거 완료\n";
    }
    
    $pdo->commit();
    
    echo "\n✅ 마이그레이션 완료!\n";
    echo "\n현재 deposit_requests 테이블 구조:\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM deposit_requests");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        if (strpos($col['Field'], 'tax_invoice') !== false) {
            echo "  - {$col['Field']}: {$col['Type']}\n";
        }
    }
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "\n❌ 오류 발생: " . $e->getMessage() . "\n";
    echo "롤백 완료.\n";
}

echo "</pre>";
?>
