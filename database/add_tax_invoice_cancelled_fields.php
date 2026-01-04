<?php
/**
 * 세금계산서 취소 이력 필드 추가 마이그레이션
 * 경로: /database/add_tax_invoice_cancelled_fields.php
 * 
 * deposit_requests 테이블에 세금계산서 취소 이력 필드 추가
 */

require_once __DIR__ . '/../includes/data/db-config.php';

$pdo = getDBConnection();

if (!$pdo) {
    die('데이터베이스 연결에 실패했습니다.');
}

echo "<h2>세금계산서 취소 이력 필드 추가</h2>";
echo "<pre>";

try {
    // 기존 컬럼 확인
    $stmt = $pdo->query("SHOW COLUMNS FROM deposit_requests");
    $existingColumns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existingColumns[] = $row['Field'];
    }
    
    // 1. tax_invoice_cancelled_at 필드 추가
    if (!in_array('tax_invoice_cancelled_at', $existingColumns)) {
        echo "1. tax_invoice_cancelled_at 필드 추가 중...\n";
        $pdo->exec("
            ALTER TABLE deposit_requests 
            ADD COLUMN tax_invoice_cancelled_at DATETIME DEFAULT NULL 
            COMMENT '세금계산서 취소 일시' 
            AFTER tax_invoice_issued_by
        ");
        echo "   ✓ tax_invoice_cancelled_at 필드 추가 완료\n";
    } else {
        echo "1. tax_invoice_cancelled_at 필드가 이미 존재합니다.\n";
    }
    
    // 2. tax_invoice_cancelled_by 필드 추가
    if (!in_array('tax_invoice_cancelled_by', $existingColumns)) {
        echo "2. tax_invoice_cancelled_by 필드 추가 중...\n";
        $pdo->exec("
            ALTER TABLE deposit_requests 
            ADD COLUMN tax_invoice_cancelled_by VARCHAR(50) DEFAULT NULL 
            COMMENT '세금계산서 취소 처리한 관리자 ID' 
            AFTER tax_invoice_cancelled_at
        ");
        echo "   ✓ tax_invoice_cancelled_by 필드 추가 완료\n";
    } else {
        echo "2. tax_invoice_cancelled_by 필드가 이미 존재합니다.\n";
    }
    
    // 3. 인덱스 추가
    $stmt = $pdo->query("SHOW INDEXES FROM deposit_requests WHERE Key_name = 'idx_tax_invoice_cancelled_at'");
    if ($stmt->rowCount() === 0) {
        echo "3. tax_invoice_cancelled_at 인덱스 추가 중...\n";
        $pdo->exec("ALTER TABLE deposit_requests ADD INDEX idx_tax_invoice_cancelled_at (tax_invoice_cancelled_at)");
        echo "   ✓ 인덱스 추가 완료\n";
    } else {
        echo "3. tax_invoice_cancelled_at 인덱스가 이미 존재합니다.\n";
    }
    
    echo "\n✅ 모든 작업이 완료되었습니다.\n";
    
} catch (PDOException $e) {
    echo "\n❌ 오류 발생: " . $e->getMessage() . "\n";
    error_log('Tax invoice cancelled fields migration error: ' . $e->getMessage());
}

echo "</pre>";
?>
