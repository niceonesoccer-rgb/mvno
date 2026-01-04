<?php
/**
 * 입금 환불 기능을 위한 데이터베이스 필드 추가
 * 경로: /MVNO/database/add_deposit_refund_fields.php
 */

require_once __DIR__ . '/../includes/data/db-config.php';

$pdo = getDBConnection();

if (!$pdo) {
    die('데이터베이스 연결에 실패했습니다.');
}

echo "<h2>입금 환불 기능 데이터베이스 마이그레이션</h2>";
echo "<pre>";

$errors = [];
$successCount = 0;

// MySQL의 DDL(ALTER TABLE)은 자동으로 커밋되므로 트랜잭션을 사용하지 않습니다.

// 1. status ENUM에 'refunded' 추가
echo "1. status ENUM에 'refunded' 추가 중...\n";
try {
    $pdo->exec("
        ALTER TABLE deposit_requests 
        MODIFY COLUMN status ENUM('pending', 'confirmed', 'unpaid', 'refunded') 
        NOT NULL DEFAULT 'pending' 
        COMMENT '상태 (대기중, 입금, 미입금, 환불)'
    ");
    echo "   ✓ status ENUM 업데이트 완료\n";
    $successCount++;
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), 'already exists') !== false) {
        echo "   - status ENUM은 이미 업데이트되어 있습니다.\n";
    } else {
        echo "   ✗ 오류: " . $e->getMessage() . "\n";
        $errors[] = "status ENUM 업데이트 실패: " . $e->getMessage();
    }
}

// 2. refunded_amount 필드 추가
echo "\n2. refunded_amount 필드 추가 중...\n";
try {
    $pdo->exec("
        ALTER TABLE deposit_requests 
        ADD COLUMN refunded_amount DECIMAL(12,2) DEFAULT NULL 
        COMMENT '환불 금액' 
        AFTER tax_invoice_issued_by
    ");
    echo "   ✓ refunded_amount 필드 추가 완료\n";
    $successCount++;
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), 'already exists') !== false || strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "   - refunded_amount 필드는 이미 존재합니다.\n";
    } else {
        echo "   ✗ 오류: " . $e->getMessage() . "\n";
        $errors[] = "refunded_amount 필드 추가 실패: " . $e->getMessage();
    }
}

// 3. refunded_at 필드 추가
echo "\n3. refunded_at 필드 추가 중...\n";
try {
    $pdo->exec("
        ALTER TABLE deposit_requests 
        ADD COLUMN refunded_at DATETIME DEFAULT NULL 
        COMMENT '환불 처리 일시' 
        AFTER refunded_amount
    ");
    echo "   ✓ refunded_at 필드 추가 완료\n";
    $successCount++;
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), 'already exists') !== false || strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "   - refunded_at 필드는 이미 존재합니다.\n";
    } else {
        echo "   ✗ 오류: " . $e->getMessage() . "\n";
        $errors[] = "refunded_at 필드 추가 실패: " . $e->getMessage();
    }
}

// 4. refunded_by 필드 추가
echo "\n4. refunded_by 필드 추가 중...\n";
try {
    $pdo->exec("
        ALTER TABLE deposit_requests 
        ADD COLUMN refunded_by VARCHAR(50) DEFAULT NULL 
        COMMENT '환불 처리한 관리자 ID' 
        AFTER refunded_at
    ");
    echo "   ✓ refunded_by 필드 추가 완료\n";
    $successCount++;
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), 'already exists') !== false || strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "   - refunded_by 필드는 이미 존재합니다.\n";
    } else {
        echo "   ✗ 오류: " . $e->getMessage() . "\n";
        $errors[] = "refunded_by 필드 추가 실패: " . $e->getMessage();
    }
}

// 5. refund_reason 필드 추가
echo "\n5. refund_reason 필드 추가 중...\n";
try {
    $pdo->exec("
        ALTER TABLE deposit_requests 
        ADD COLUMN refund_reason TEXT DEFAULT NULL 
        COMMENT '환불 사유' 
        AFTER refunded_by
    ");
    echo "   ✓ refund_reason 필드 추가 완료\n";
    $successCount++;
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), 'already exists') !== false || strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "   - refund_reason 필드는 이미 존재합니다.\n";
    } else {
        echo "   ✗ 오류: " . $e->getMessage() . "\n";
        $errors[] = "refund_reason 필드 추가 실패: " . $e->getMessage();
    }
}

// 6. 인덱스 추가 (선택사항)
echo "\n6. 인덱스 추가 중...\n";
try {
    $pdo->exec("ALTER TABLE deposit_requests ADD INDEX idx_refunded_at (refunded_at)");
    echo "   ✓ refunded_at 인덱스 추가 완료\n";
    $successCount++;
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), 'already exists') !== false || strpos($e->getMessage(), 'Duplicate key name') !== false) {
        echo "   - refunded_at 인덱스는 이미 존재합니다.\n";
    } else {
        echo "   - 인덱스 추가 건너뜀 (오류: " . $e->getMessage() . ")\n";
        $errors[] = "인덱스 추가 실패: " . $e->getMessage();
    }
}

echo "\n";

if (empty($errors)) {
    echo "✅ 모든 마이그레이션이 완료되었습니다!\n";
    echo "\n추가된 필드:\n";
    echo "  - refunded_amount (DECIMAL): 환불 금액\n";
    echo "  - refunded_at (DATETIME): 환불 처리 일시\n";
    echo "  - refunded_by (VARCHAR): 환불 처리한 관리자 ID\n";
    echo "  - refund_reason (TEXT): 환불 사유\n";
    echo "\n업데이트된 필드:\n";
    echo "  - status (ENUM): 'refunded' 상태 추가\n";
} else {
    echo "⚠️ 마이그레이션 중 일부 오류가 발생했습니다:\n";
    foreach ($errors as $error) {
        echo "  - " . $error . "\n";
    }
    echo "\n성공한 작업 수: " . $successCount . "\n";
}

echo "</pre>";
echo "<p><a href='/MVNO/admin/deposit/requests.php'>입금 신청 목록으로 이동</a></p>";
?>
