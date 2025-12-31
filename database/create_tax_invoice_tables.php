<?php
/**
 * 세금계산서 발행 관련 테이블 생성 스크립트
 * 
 * 실행 방법: 브라우저에서 http://localhost/MVNO/database/create_tax_invoice_tables.php 접속
 * 
 * 이 스크립트는 rotation_advertisement_schema.sql의 테이블이 없는 경우 자동으로 생성합니다.
 */

require_once __DIR__ . '/../includes/data/db-config.php';

$pdo = getDBConnection();

if (!$pdo) {
    die('데이터베이스 연결 실패');
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>세금계산서 발행 테이블 생성</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1e293b;
            margin-bottom: 10px;
        }
        .success {
            background: #d1fae5;
            color: #065f46;
            padding: 12px;
            border-radius: 6px;
            margin: 10px 0;
        }
        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 6px;
            margin: 10px 0;
        }
        .info {
            background: #dbeafe;
            color: #1e40af;
            padding: 12px;
            border-radius: 6px;
            margin: 10px 0;
        }
        .warning {
            background: #fef3c7;
            color: #92400e;
            padding: 12px;
            border-radius: 6px;
            margin: 10px 0;
        }
        pre {
            background: #f8fafc;
            padding: 15px;
            border-radius: 6px;
            overflow-x: auto;
            border: 1px solid #e2e8f0;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #6366f1;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 20px;
            margin-right: 10px;
        }
        .btn:hover {
            background: #4f46e5;
        }
        .btn-secondary {
            background: #64748b;
        }
        .btn-secondary:hover {
            background: #475569;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>세금계산서 발행 테이블 생성</h1>
        
        <?php
        try {
            // 1. deposit_requests 테이블 확인 및 생성
            echo "<h2>1. deposit_requests 테이블 확인</h2>";
            
            $stmt = $pdo->query("SHOW TABLES LIKE 'deposit_requests'");
            if ($stmt->rowCount() == 0) {
                echo "<div class='warning'>deposit_requests 테이블이 존재하지 않습니다. 생성합니다...</div>";
                
                // deposit_requests 테이블 생성
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS `deposit_requests` (
                        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                        `seller_id` VARCHAR(50) NOT NULL COMMENT '판매자 ID',
                        `bank_account_id` INT(11) UNSIGNED NOT NULL COMMENT '입금할 계좌 ID (bank_accounts.id)',
                        `depositor_name` VARCHAR(100) NOT NULL COMMENT '입금자명',
                        `amount` DECIMAL(12,2) NOT NULL COMMENT '입금 금액 (부가세 포함)',
                        `supply_amount` DECIMAL(12,2) NOT NULL COMMENT '공급가액 (부가세 제외)',
                        `tax_amount` DECIMAL(12,2) NOT NULL COMMENT '부가세 (공급가액의 10%)',
                        `status` ENUM('pending', 'confirmed', 'unpaid') NOT NULL DEFAULT 'pending' COMMENT '상태 (대기중, 입금, 미입금)',
                        `admin_id` VARCHAR(50) DEFAULT NULL COMMENT '처리한 관리자 ID',
                        `confirmed_at` DATETIME DEFAULT NULL COMMENT '확인 일시',
                        `rejected_reason` TEXT DEFAULT NULL COMMENT '거부 사유',
                        `tax_invoice_status` ENUM('unissued', 'issued', 'cancelled') NOT NULL DEFAULT 'unissued' COMMENT '세금계산서 발행 상태 (미발행, 발행, 취소)',
                        `tax_invoice_issued_at` DATETIME DEFAULT NULL COMMENT '세금계산서 발행 일시',
                        `tax_invoice_issued_by` VARCHAR(50) DEFAULT NULL COMMENT '세금계산서 발행 처리한 관리자 ID',
                        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY (`id`),
                        KEY `idx_seller_id` (`seller_id`),
                        KEY `idx_bank_account_id` (`bank_account_id`),
                        KEY `idx_status` (`status`),
                        KEY `idx_tax_invoice_status` (`tax_invoice_status`),
                        KEY `idx_created_at` (`created_at`),
                        KEY `idx_confirmed_at` (`confirmed_at`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
                    COMMENT='예치금 충전 신청 (무통장 입금)'
                ");
                
                echo "<div class='success'>✓ deposit_requests 테이블 생성 완료</div>";
            } else {
                echo "<div class='success'>✓ deposit_requests 테이블이 이미 존재합니다.</div>";
                
                // 컬럼 확인 및 추가
                $stmt = $pdo->query("SHOW COLUMNS FROM deposit_requests");
                $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                // tax_invoice_status 컬럼 확인
                if (!in_array('tax_invoice_status', $columns)) {
                    echo "<div class='info'>tax_invoice_status 컬럼이 없습니다. 추가합니다...</div>";
                    try {
                        $pdo->exec("
                            ALTER TABLE deposit_requests 
                            ADD COLUMN tax_invoice_status ENUM('unissued', 'issued', 'cancelled') NOT NULL DEFAULT 'unissued' 
                            COMMENT '세금계산서 발행 상태 (미발행, 발행, 취소)' 
                            AFTER rejected_reason
                        ");
                        echo "<div class='success'>✓ tax_invoice_status 컬럼 추가 완료</div>";
                    } catch (PDOException $e) {
                        if (strpos($e->getMessage(), 'Duplicate column name') === false) {
                            throw $e;
                        }
                        echo "<div class='info'>tax_invoice_status 컬럼이 이미 존재합니다.</div>";
                    }
                } else {
                    echo "<div class='success'>✓ tax_invoice_status 컬럼이 이미 존재합니다.</div>";
                }
                
                // tax_invoice_issued_at 컬럼 확인
                if (!in_array('tax_invoice_issued_at', $columns)) {
                    try {
                        $pdo->exec("
                            ALTER TABLE deposit_requests 
                            ADD COLUMN tax_invoice_issued_at DATETIME DEFAULT NULL 
                            COMMENT '세금계산서 발행 일시' 
                            AFTER tax_invoice_status
                        ");
                        echo "<div class='success'>✓ tax_invoice_issued_at 컬럼 추가 완료</div>";
                    } catch (PDOException $e) {
                        if (strpos($e->getMessage(), 'Duplicate column name') === false) {
                            throw $e;
                        }
                    }
                }
                
                // tax_invoice_issued_by 컬럼 확인
                if (!in_array('tax_invoice_issued_by', $columns)) {
                    try {
                        $pdo->exec("
                            ALTER TABLE deposit_requests 
                            ADD COLUMN tax_invoice_issued_by VARCHAR(50) DEFAULT NULL 
                            COMMENT '세금계산서 발행 처리한 관리자 ID' 
                            AFTER tax_invoice_issued_at
                        ");
                        echo "<div class='success'>✓ tax_invoice_issued_by 컬럼 추가 완료</div>";
                    } catch (PDOException $e) {
                        if (strpos($e->getMessage(), 'Duplicate column name') === false) {
                            throw $e;
                        }
                    }
                }
                
                // 기존 tax_invoice_issued 컬럼이 있으면 제거 (데이터 마이그레이션 후)
                if (in_array('tax_invoice_issued', $columns)) {
                    echo "<div class='info'>기존 tax_invoice_issued 컬럼을 제거합니다...</div>";
                    try {
                        // 데이터 마이그레이션
                        $pdo->exec("
                            UPDATE deposit_requests 
                            SET tax_invoice_status = CASE 
                                WHEN tax_invoice_issued = 1 THEN 'issued'
                                ELSE 'unissued'
                            END
                            WHERE tax_invoice_status = 'unissued' OR tax_invoice_status IS NULL
                        ");
                        $pdo->exec("ALTER TABLE deposit_requests DROP COLUMN tax_invoice_issued");
                        echo "<div class='success'>✓ tax_invoice_issued 컬럼 제거 완료</div>";
                    } catch (PDOException $e) {
                        echo "<div class='warning'>tax_invoice_issued 컬럼 제거 중 오류: " . htmlspecialchars($e->getMessage()) . "</div>";
                    }
                }
                
                // 기존 period 컬럼 제거
                if (in_array('tax_invoice_period_start', $columns)) {
                    try {
                        $pdo->exec("ALTER TABLE deposit_requests DROP COLUMN tax_invoice_period_start");
                        echo "<div class='success'>✓ tax_invoice_period_start 컬럼 제거 완료</div>";
                    } catch (PDOException $e) {
                        // 무시
                    }
                }
                if (in_array('tax_invoice_period_end', $columns)) {
                    try {
                        $pdo->exec("ALTER TABLE deposit_requests DROP COLUMN tax_invoice_period_end");
                        echo "<div class='success'>✓ tax_invoice_period_end 컬럼 제거 완료</div>";
                    } catch (PDOException $e) {
                        // 무시
                    }
                }
                
                // 인덱스 확인 및 생성
                try {
                    $stmt = $pdo->query("SHOW INDEX FROM deposit_requests WHERE Key_name = 'idx_tax_invoice_status'");
                    if ($stmt->rowCount() == 0) {
                        $pdo->exec("ALTER TABLE deposit_requests ADD INDEX idx_tax_invoice_status (tax_invoice_status)");
                        echo "<div class='success'>✓ idx_tax_invoice_status 인덱스 생성 완료</div>";
                    }
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate key name') === false) {
                        throw $e;
                    }
                }
                
                // 기존 인덱스 제거
                try {
                    $stmt = $pdo->query("SHOW INDEX FROM deposit_requests WHERE Key_name = 'idx_tax_invoice_issued'");
                    if ($stmt->rowCount() > 0) {
                        $pdo->exec("ALTER TABLE deposit_requests DROP INDEX idx_tax_invoice_issued");
                        echo "<div class='success'>✓ 기존 idx_tax_invoice_issued 인덱스 제거 완료</div>";
                    }
                } catch (PDOException $e) {
                    // 무시
                }
            }
            
            // 2. bank_accounts 테이블 확인 및 생성
            echo "<h2>2. bank_accounts 테이블 확인</h2>";
            $stmt = $pdo->query("SHOW TABLES LIKE 'bank_accounts'");
            if ($stmt->rowCount() == 0) {
                echo "<div class='warning'>bank_accounts 테이블이 존재하지 않습니다. 생성합니다...</div>";
                
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS `bank_accounts` (
                        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                        `bank_name` VARCHAR(50) NOT NULL COMMENT '은행명 (예: 국민은행, 신한은행)',
                        `account_number` VARCHAR(50) NOT NULL COMMENT '계좌번호',
                        `account_holder` VARCHAR(100) NOT NULL COMMENT '예금주',
                        `display_order` INT(11) NOT NULL DEFAULT 0 COMMENT '표시 순서',
                        `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '활성화 여부',
                        `memo` TEXT DEFAULT NULL COMMENT '메모 (관리자용)',
                        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY (`id`),
                        KEY `idx_is_active` (`is_active`),
                        KEY `idx_display_order` (`display_order`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
                    COMMENT='무통장 입금 계좌 관리'
                ");
                
                echo "<div class='success'>✓ bank_accounts 테이블 생성 완료</div>";
                
                // 외래키 제약조건 추가 (deposit_requests가 있는 경우)
                $stmt = $pdo->query("SHOW TABLES LIKE 'deposit_requests'");
                if ($stmt->rowCount() > 0) {
                    try {
                        // 기존 외래키가 있는지 확인
                        $stmt = $pdo->query("
                            SELECT CONSTRAINT_NAME 
                            FROM information_schema.KEY_COLUMN_USAGE 
                            WHERE TABLE_SCHEMA = DATABASE() 
                            AND TABLE_NAME = 'deposit_requests' 
                            AND CONSTRAINT_NAME = 'fk_deposit_request_bank_account'
                        ");
                        if ($stmt->rowCount() == 0) {
                            $pdo->exec("
                                ALTER TABLE deposit_requests 
                                ADD CONSTRAINT fk_deposit_request_bank_account 
                                FOREIGN KEY (bank_account_id) REFERENCES bank_accounts(id) ON DELETE RESTRICT
                            ");
                            echo "<div class='success'>✓ 외래키 제약조건 추가 완료</div>";
                        }
                    } catch (PDOException $e) {
                        echo "<div class='info'>외래키 제약조건은 이미 존재하거나 추가할 수 없습니다.</div>";
                    }
                }
            } else {
                echo "<div class='success'>✓ bank_accounts 테이블이 이미 존재합니다.</div>";
            }
            
            // 3. 최종 확인
            echo "<h2>3. 최종 확인</h2>";
            
            try {
                $stmt = $pdo->query("SHOW COLUMNS FROM deposit_requests WHERE Field LIKE 'tax_invoice%'");
                $taxInvoiceColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<pre>";
                echo "세금계산서 관련 컬럼:\n";
                if (empty($taxInvoiceColumns)) {
                    echo "  (없음)\n";
                } else {
                    foreach ($taxInvoiceColumns as $col) {
                        echo "  - {$col['Field']}: {$col['Type']}";
                        if ($col['Null'] == 'NO') echo " NOT NULL";
                        if ($col['Default'] !== null) echo " DEFAULT '{$col['Default']}'";
                        echo "\n";
                    }
                }
                echo "</pre>";
                
                echo "<div class='success'><strong>✅ 모든 작업이 완료되었습니다!</strong></div>";
                
            } catch (PDOException $e) {
                echo "<div class='error'><strong>❌ 테이블 확인 중 오류:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
            }
            
        } catch (Exception $e) {
            echo "<div class='error'><strong>❌ 오류 발생:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        }
        ?>
        
        <a href="../admin/tax-invoice/issue.php" class="btn">세금계산서 발행 페이지로 이동</a>
        <a href="rotation_advertisement_schema.sql" class="btn btn-secondary" target="_blank">전체 스키마 파일 보기</a>
    </div>
</body>
</html>
