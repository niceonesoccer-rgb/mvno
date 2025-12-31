<?php
/**
 * 로테이션 광고 시스템 전체 테이블 생성 스크립트
 * 
 * 실행 방법: 브라우저에서 http://localhost/MVNO/database/create_rotation_advertisement_tables.php 접속
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
    <title>로테이션 광고 시스템 테이블 생성</title>
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
    </style>
</head>
<body>
    <div class="container">
        <h1>로테이션 광고 시스템 테이블 생성</h1>
        
        <?php
        try {
            $createdTables = [];
            $existingTables = [];
            
            // 1. rotation_advertisement_prices 테이블
            echo "<h2>1. rotation_advertisement_prices 테이블</h2>";
            $stmt = $pdo->query("SHOW TABLES LIKE 'rotation_advertisement_prices'");
            if ($stmt->rowCount() == 0) {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS `rotation_advertisement_prices` (
                        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                        `product_type` ENUM('mvno', 'mno', 'internet', 'mno_sim') NOT NULL COMMENT '상품 타입',
                        `rotation_duration` INT(11) NOT NULL COMMENT '로테이션 시간(초): 10, 30, 60, 300',
                        `advertisement_days` INT(11) NOT NULL COMMENT '광고 기간(일): 1, 2, 3, 5, 7, 10, 14, 30 등',
                        `price` DECIMAL(12,2) NOT NULL COMMENT '광고 금액',
                        `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '활성화 여부',
                        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY (`id`),
                        UNIQUE KEY `unique_type_duration_days` (`product_type`, `rotation_duration`, `advertisement_days`),
                        KEY `idx_product_type` (`product_type`),
                        KEY `idx_is_active` (`is_active`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
                    COMMENT='로테이션 광고 가격 설정 (카테고리별, 시간 단위별, 기간별)'
                ");
                echo "<div class='success'>✓ rotation_advertisement_prices 테이블 생성 완료</div>";
                $createdTables[] = 'rotation_advertisement_prices';
            } else {
                echo "<div class='info'>✓ rotation_advertisement_prices 테이블이 이미 존재합니다.</div>";
                $existingTables[] = 'rotation_advertisement_prices';
            }
            
            // 2. bank_accounts 테이블 (이미 create_tax_invoice_tables.php에서 생성되었을 수 있음)
            echo "<h2>2. bank_accounts 테이블</h2>";
            $stmt = $pdo->query("SHOW TABLES LIKE 'bank_accounts'");
            if ($stmt->rowCount() == 0) {
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
                $createdTables[] = 'bank_accounts';
            } else {
                echo "<div class='info'>✓ bank_accounts 테이블이 이미 존재합니다.</div>";
                $existingTables[] = 'bank_accounts';
            }
            
            // 3. seller_deposit_accounts 테이블
            echo "<h2>3. seller_deposit_accounts 테이블</h2>";
            $stmt = $pdo->query("SHOW TABLES LIKE 'seller_deposit_accounts'");
            if ($stmt->rowCount() == 0) {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS `seller_deposit_accounts` (
                        `seller_id` VARCHAR(50) NOT NULL COMMENT '판매자 ID',
                        `balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '예치금 잔액',
                        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY (`seller_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
                    COMMENT='판매자 예치금 계좌'
                ");
                echo "<div class='success'>✓ seller_deposit_accounts 테이블 생성 완료</div>";
                $createdTables[] = 'seller_deposit_accounts';
            } else {
                echo "<div class='info'>✓ seller_deposit_accounts 테이블이 이미 존재합니다.</div>";
                $existingTables[] = 'seller_deposit_accounts';
            }
            
            // 4. seller_deposit_ledger 테이블
            echo "<h2>4. seller_deposit_ledger 테이블</h2>";
            $stmt = $pdo->query("SHOW TABLES LIKE 'seller_deposit_ledger'");
            if ($stmt->rowCount() == 0) {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS `seller_deposit_ledger` (
                        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                        `seller_id` VARCHAR(50) NOT NULL COMMENT '판매자 ID',
                        `transaction_type` ENUM('deposit', 'withdraw', 'refund') NOT NULL COMMENT '거래 유형 (충전, 차감, 환불)',
                        `amount` DECIMAL(12,2) NOT NULL COMMENT '금액 (충전: +, 차감: -, 환불: +)',
                        `balance_before` DECIMAL(12,2) NOT NULL COMMENT '거래 전 잔액',
                        `balance_after` DECIMAL(12,2) NOT NULL COMMENT '거래 후 잔액',
                        `deposit_request_id` INT(11) UNSIGNED DEFAULT NULL COMMENT '예치금 충전 신청 ID (deposit_requests.id)',
                        `advertisement_id` INT(11) UNSIGNED DEFAULT NULL COMMENT '광고 ID (rotation_advertisements.id, 차감 시)',
                        `description` VARCHAR(500) DEFAULT NULL COMMENT '설명',
                        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (`id`),
                        KEY `idx_seller_id` (`seller_id`),
                        KEY `idx_transaction_type` (`transaction_type`),
                        KEY `idx_created_at` (`created_at`),
                        KEY `idx_deposit_request_id` (`deposit_request_id`),
                        KEY `idx_advertisement_id` (`advertisement_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
                    COMMENT='판매자 예치금 내역'
                ");
                echo "<div class='success'>✓ seller_deposit_ledger 테이블 생성 완료</div>";
                $createdTables[] = 'seller_deposit_ledger';
            } else {
                echo "<div class='info'>✓ seller_deposit_ledger 테이블이 이미 존재합니다.</div>";
                $existingTables[] = 'seller_deposit_ledger';
            }
            
            // 5. deposit_requests 테이블 (이미 create_tax_invoice_tables.php에서 생성되었을 수 있음)
            echo "<h2>5. deposit_requests 테이블</h2>";
            $stmt = $pdo->query("SHOW TABLES LIKE 'deposit_requests'");
            if ($stmt->rowCount() == 0) {
                // bank_accounts가 먼저 생성되어야 함
                $stmt = $pdo->query("SHOW TABLES LIKE 'bank_accounts'");
                if ($stmt->rowCount() == 0) {
                    echo "<div class='error'>bank_accounts 테이블이 먼저 필요합니다.</div>";
                } else {
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
                            KEY `idx_confirmed_at` (`confirmed_at`),
                            CONSTRAINT `fk_deposit_request_bank_account` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`) ON DELETE RESTRICT
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
                        COMMENT='예치금 충전 신청 (무통장 입금)'
                    ");
                    echo "<div class='success'>✓ deposit_requests 테이블 생성 완료</div>";
                    $createdTables[] = 'deposit_requests';
                }
            } else {
                echo "<div class='info'>✓ deposit_requests 테이블이 이미 존재합니다.</div>";
                $existingTables[] = 'deposit_requests';
            }
            
            // 6. rotation_duration_settings 테이블
            echo "<h2>6. rotation_duration_settings 테이블</h2>";
            $stmt = $pdo->query("SHOW TABLES LIKE 'rotation_duration_settings'");
            if ($stmt->rowCount() == 0) {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS `rotation_duration_settings` (
                        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                        `duration_seconds` INT(11) NOT NULL COMMENT '로테이션 시간(초)',
                        `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '활성화 여부',
                        `display_order` INT(11) NOT NULL DEFAULT 0 COMMENT '표시 순서',
                        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY (`id`),
                        UNIQUE KEY `unique_duration` (`duration_seconds`),
                        KEY `idx_is_active` (`is_active`),
                        KEY `idx_display_order` (`display_order`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
                    COMMENT='로테이션 시간 설정'
                ");
                echo "<div class='success'>✓ rotation_duration_settings 테이블 생성 완료</div>";
                $createdTables[] = 'rotation_duration_settings';
                
                // 초기 데이터 삽입
                $pdo->exec("
                    INSERT INTO `rotation_duration_settings` (`duration_seconds`, `is_active`, `display_order`) 
                    VALUES (30, 1, 1)
                ");
                echo "<div class='success'>✓ 초기 로테이션 시간(30초) 추가 완료</div>";
            } else {
                echo "<div class='info'>✓ rotation_duration_settings 테이블이 이미 존재합니다.</div>";
                $existingTables[] = 'rotation_duration_settings';
            }
            
            // 7. rotation_advertisements 테이블
            echo "<h2>7. rotation_advertisements 테이블</h2>";
            $stmt = $pdo->query("SHOW TABLES LIKE 'rotation_advertisements'");
            if ($stmt->rowCount() == 0) {
                // products 테이블이 먼저 생성되어야 함
                $stmt = $pdo->query("SHOW TABLES LIKE 'products'");
                if ($stmt->rowCount() == 0) {
                    echo "<div class='error'>products 테이블이 먼저 필요합니다. 먼저 products 테이블을 생성해주세요.</div>";
                } else {
                    $pdo->exec("
                        CREATE TABLE IF NOT EXISTS `rotation_advertisements` (
                            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                            `product_id` INT(11) UNSIGNED NOT NULL COMMENT '상품 ID',
                            `seller_id` VARCHAR(50) NOT NULL COMMENT '판매자 ID',
                            `product_type` ENUM('mvno', 'mno', 'internet', 'mno_sim') NOT NULL COMMENT '상품 타입',
                            `rotation_duration` INT(11) NOT NULL COMMENT '로테이션 시간(초): 10, 30, 60, 300',
                            `advertisement_days` INT(11) NOT NULL COMMENT '광고 기간(일): 1, 2, 3, 5, 7, 10 등',
                            `price` DECIMAL(12,2) NOT NULL COMMENT '광고 금액 (신청 시점 가격)',
                            `start_datetime` DATETIME NOT NULL COMMENT '광고 시작 시간 (초 단위)',
                            `end_datetime` DATETIME NOT NULL COMMENT '광고 종료 시간 (초 단위)',
                            `status` ENUM('active', 'expired', 'cancelled') NOT NULL DEFAULT 'active' COMMENT '광고 상태',
                            `display_order` INT(11) NOT NULL DEFAULT 0 COMMENT '로테이션 순서',
                            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            PRIMARY KEY (`id`),
                            KEY `idx_product_id` (`product_id`),
                            KEY `idx_seller_id` (`seller_id`),
                            KEY `idx_product_type` (`product_type`),
                            KEY `idx_status` (`status`),
                            KEY `idx_start_end_datetime` (`start_datetime`, `end_datetime`),
                            KEY `idx_display_order` (`display_order`),
                            CONSTRAINT `fk_rotation_ad_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
                        COMMENT='로테이션 광고 신청'
                    ");
                    echo "<div class='success'>✓ rotation_advertisements 테이블 생성 완료</div>";
                    $createdTables[] = 'rotation_advertisements';
                }
            } else {
                echo "<div class='info'>✓ rotation_advertisements 테이블이 이미 존재합니다.</div>";
                $existingTables[] = 'rotation_advertisements';
            }
            
            // 최종 요약
            echo "<h2>최종 요약</h2>";
            if (!empty($createdTables)) {
                echo "<div class='success'><strong>✅ 새로 생성된 테이블 (" . count($createdTables) . "개):</strong><br>";
                foreach ($createdTables as $table) {
                    echo "  - $table<br>";
                }
                echo "</div>";
            }
            
            if (!empty($existingTables)) {
                echo "<div class='info'><strong>ℹ️ 이미 존재하는 테이블 (" . count($existingTables) . "개):</strong><br>";
                foreach ($existingTables as $table) {
                    echo "  - $table<br>";
                }
                echo "</div>";
            }
            
            echo "<div class='success'><strong>✅ 모든 작업이 완료되었습니다!</strong></div>";
            
        } catch (Exception $e) {
            echo "<div class='error'><strong>❌ 오류 발생:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        }
        ?>
        
        <a href="../admin/advertisement/prices.php" class="btn">광고 가격 설정 페이지로 이동</a>
        <a href="rotation_advertisement_schema.sql" class="btn" target="_blank">전체 스키마 파일 보기</a>
    </div>
</body>
</html>
