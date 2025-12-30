<?php
/**
 * 입찰 시스템 테이블 설치 페이지
 * 경로: /database/install_bidding_system_tables.php
 */

require_once __DIR__ . '/../includes/data/db-config.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = getDBConnection();
$message = '';
$error = '';
$tables = [];

if ($pdo) {
    // 기존 테이블 확인
    $existingTables = [];
    $tableNames = ['bidding_rounds', 'bidding_participations', 'bidding_product_assignments', 'seller_deposits', 'seller_deposit_transactions'];
    
    foreach ($tableNames as $tableName) {
        $checkStmt = $pdo->query("SHOW TABLES LIKE '$tableName'");
        $existingTables[$tableName] = $checkStmt->fetch() !== false;
    }
    
    // 설치 버튼 클릭 시
    if (isset($_POST['install']) && $_POST['install'] === 'yes') {
        try {
            $pdo->beginTransaction();
            
            // 1. bidding_rounds 테이블
            if (!$existingTables['bidding_rounds']) {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS `bidding_rounds` (
                        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                        `category` ENUM('mno', 'mvno', 'mno_sim') NOT NULL COMMENT '카테고리',
                        `bidding_start_at` DATETIME NOT NULL COMMENT '입찰 시작일시',
                        `bidding_end_at` DATETIME NOT NULL COMMENT '입찰 종료일시',
                        `display_start_at` DATETIME NOT NULL COMMENT '게시 시작일시',
                        `display_end_at` DATETIME NOT NULL COMMENT '게시 종료일시',
                        `max_display_count` INT(11) UNSIGNED NOT NULL DEFAULT 20 COMMENT '최대 노출 개수',
                        `min_bid_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '최소 입찰 금액',
                        `max_bid_amount` DECIMAL(12,2) NOT NULL DEFAULT 100000.00 COMMENT '최대 입찰 금액',
                        `rotation_type` ENUM('fixed', 'rotating') NOT NULL DEFAULT 'fixed' COMMENT '운용 방식',
                        `rotation_interval_minutes` INT(11) UNSIGNED DEFAULT NULL COMMENT '순환 간격 (분)',
                        `status` ENUM('upcoming', 'bidding', 'closed', 'displaying', 'finished') NOT NULL DEFAULT 'upcoming' COMMENT '입찰 상태',
                        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        `created_by` VARCHAR(50) DEFAULT NULL COMMENT '생성자 user_id',
                        PRIMARY KEY (`id`),
                        KEY `idx_category` (`category`),
                        KEY `idx_status` (`status`),
                        KEY `idx_bidding_period` (`bidding_start_at`, `bidding_end_at`),
                        KEY `idx_display_period` (`display_start_at`, `display_end_at`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='입찰 라운드'
                ");
                $message .= "✅ bidding_rounds 테이블이 생성되었습니다.<br>";
            }
            
            // 2. bidding_participations 테이블
            if (!$existingTables['bidding_participations']) {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS `bidding_participations` (
                        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                        `bidding_round_id` INT(11) UNSIGNED NOT NULL COMMENT '입찰 라운드 ID',
                        `seller_id` VARCHAR(50) NOT NULL COMMENT '판매자 user_id',
                        `bid_amount` DECIMAL(12,2) NOT NULL COMMENT '입찰 금액',
                        `status` ENUM('pending', 'won', 'lost', 'cancelled') NOT NULL DEFAULT 'pending' COMMENT '입찰 상태',
                        `rank` INT(11) UNSIGNED DEFAULT NULL COMMENT '낙찰 순위 (NULL=미낙찰, 낙찰 시 1~20)',
                        `deposit_used` DECIMAL(12,2) NOT NULL COMMENT '사용된 예치금',
                        `deposit_refunded` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '환불된 예치금',
                        `bid_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '입찰 시간',
                        `cancelled_at` DATETIME DEFAULT NULL COMMENT '취소 시간',
                        `won_at` DATETIME DEFAULT NULL COMMENT '낙찰 확정 시간',
                        PRIMARY KEY (`id`),
                        UNIQUE KEY `uk_round_seller` (`bidding_round_id`, `seller_id`),
                        KEY `idx_bidding_round_id` (`bidding_round_id`),
                        KEY `idx_seller_id` (`seller_id`),
                        KEY `idx_status` (`status`),
                        KEY `idx_bid_amount` (`bid_amount`),
                        KEY `idx_rank` (`rank`),
                        KEY `idx_bid_at` (`bid_at`),
                        CONSTRAINT `fk_bidding_participation_round` FOREIGN KEY (`bidding_round_id`) REFERENCES `bidding_rounds` (`id`) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='입찰 참여'
                ");
                $message .= "✅ bidding_participations 테이블이 생성되었습니다.<br>";
            }
            
            // 3. bidding_product_assignments 테이블
            if (!$existingTables['bidding_product_assignments']) {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS `bidding_product_assignments` (
                        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                        `bidding_round_id` INT(11) UNSIGNED NOT NULL COMMENT '입찰 라운드 ID',
                        `bidding_participation_id` INT(11) UNSIGNED NOT NULL COMMENT '입찰 참여 ID',
                        `product_id` INT(11) UNSIGNED NOT NULL COMMENT '게시물(상품) ID',
                        `display_order` INT(11) UNSIGNED NOT NULL COMMENT '노출 순서 (1~20)',
                        `bid_amount` DECIMAL(12,2) NOT NULL COMMENT '입찰 금액 (참고용)',
                        `assigned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '배정 시간',
                        `last_rotated_at` DATETIME DEFAULT NULL COMMENT '마지막 순환 시간 (순환 모드일 때)',
                        PRIMARY KEY (`id`),
                        UNIQUE KEY `uk_round_order` (`bidding_round_id`, `display_order`),
                        KEY `idx_bidding_round_id` (`bidding_round_id`),
                        KEY `idx_bidding_participation_id` (`bidding_participation_id`),
                        KEY `idx_product_id` (`product_id`),
                        KEY `idx_display_order` (`display_order`),
                        CONSTRAINT `fk_bidding_assignment_round` FOREIGN KEY (`bidding_round_id`) REFERENCES `bidding_rounds` (`id`) ON DELETE CASCADE,
                        CONSTRAINT `fk_bidding_assignment_participation` FOREIGN KEY (`bidding_participation_id`) REFERENCES `bidding_participations` (`id`) ON DELETE CASCADE,
                        CONSTRAINT `fk_bidding_assignment_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='낙찰자 게시물 배정'
                ");
                $message .= "✅ bidding_product_assignments 테이블이 생성되었습니다.<br>";
            }
            
            // 4. seller_deposits 테이블
            if (!$existingTables['seller_deposits']) {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS `seller_deposits` (
                        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                        `seller_id` VARCHAR(50) NOT NULL COMMENT '판매자 user_id',
                        `balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '예치금 잔액',
                        `bank_name` VARCHAR(100) DEFAULT NULL COMMENT '환불 계좌 은행명',
                        `account_number` VARCHAR(50) DEFAULT NULL COMMENT '환불 계좌 번호',
                        `account_holder` VARCHAR(100) DEFAULT NULL COMMENT '환불 계좌 예금주',
                        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY (`id`),
                        UNIQUE KEY `uk_seller_id` (`seller_id`),
                        KEY `idx_balance` (`balance`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='판매자 예치금 계정'
                ");
                $message .= "✅ seller_deposits 테이블이 생성되었습니다.<br>";
            }
            
            // 5. seller_deposit_transactions 테이블
            if (!$existingTables['seller_deposit_transactions']) {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS `seller_deposit_transactions` (
                        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                        `seller_id` VARCHAR(50) NOT NULL COMMENT '판매자 user_id',
                        `transaction_type` ENUM('deposit', 'bid', 'refund', 'withdrawal') NOT NULL COMMENT '거래 유형',
                        `amount` DECIMAL(12,2) NOT NULL COMMENT '금액',
                        `balance_before` DECIMAL(12,2) NOT NULL COMMENT '거래 전 잔액',
                        `balance_after` DECIMAL(12,2) NOT NULL COMMENT '거래 후 잔액',
                        `reference_id` INT(11) UNSIGNED DEFAULT NULL COMMENT '참조 ID (bidding_participation_id 등)',
                        `reference_type` VARCHAR(50) DEFAULT NULL COMMENT '참조 타입 (bidding_participation 등)',
                        `description` TEXT DEFAULT NULL COMMENT '설명',
                        `processed_by` VARCHAR(50) DEFAULT NULL COMMENT '처리자 user_id (관리자)',
                        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (`id`),
                        KEY `idx_seller_id` (`seller_id`),
                        KEY `idx_transaction_type` (`transaction_type`),
                        KEY `idx_created_at` (`created_at`),
                        KEY `idx_reference` (`reference_type`, `reference_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='예치금 거래 내역'
                ");
                $message .= "✅ seller_deposit_transactions 테이블이 생성되었습니다.<br>";
            }
            
            $pdo->commit();
            
            if (empty($message)) {
                $message = "모든 테이블이 이미 존재합니다.";
            }
            
            // 페이지 새로고침하여 상태 업데이트
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "오류 발생: " . $e->getMessage();
            error_log("Bidding system table installation error: " . $e->getMessage());
        }
    }
    
    // 현재 테이블 상태 다시 확인
    foreach ($tableNames as $tableName) {
        $checkStmt = $pdo->query("SHOW TABLES LIKE '$tableName'");
        $tables[$tableName] = $checkStmt->fetch() !== false;
    }
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>입찰 시스템 테이블 설치</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .status-box {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .table-item {
            display: flex;
            align-items: center;
            padding: 12px;
            margin: 8px 0;
            background: white;
            border-radius: 6px;
            border-left: 4px solid #ddd;
        }
        
        .table-item.exists {
            border-left-color: #28a745;
        }
        
        .table-item.missing {
            border-left-color: #dc3545;
        }
        
        .table-icon {
            font-size: 20px;
            margin-right: 12px;
            width: 30px;
            text-align: center;
        }
        
        .table-info {
            flex: 1;
        }
        
        .table-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 4px;
        }
        
        .table-desc {
            font-size: 13px;
            color: #666;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-badge.exists {
            background: #d4edda;
            color: #155724;
        }
        
        .status-badge.missing {
            background: #f8d7da;
            color: #721c24;
        }
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .install-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            width: 100%;
            margin-top: 20px;
        }
        
        .install-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .install-button:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
        }
        
        .info-box h3 {
            color: #1976D2;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .info-box ul {
            margin-left: 20px;
            color: #555;
        }
        
        .info-box li {
            margin: 5px 0;
        }
        
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
        }
        
        .warning-box h3 {
            color: #856404;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .warning-box p {
            color: #856404;
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📦 입찰 시스템 테이블 설치</h1>
        <p class="subtitle">입찰 시스템을 사용하기 위해 필요한 데이터베이스 테이블을 생성합니다.</p>
        
        <?php if ($message): ?>
            <div class="message success">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="status-box">
            <h2 style="margin-bottom: 20px; color: #333;">테이블 상태</h2>
            
            <?php
            $tableDescriptions = [
                'bidding_rounds' => '입찰 라운드 정보',
                'bidding_participations' => '입찰 참여 정보',
                'bidding_product_assignments' => '낙찰자 게시물 배정',
                'seller_deposits' => '판매자 예치금 계정',
                'seller_deposit_transactions' => '예치금 거래 내역'
            ];
            
            $allExist = true;
            foreach ($tables as $tableName => $exists) {
                if (!$exists) $allExist = false;
                $statusClass = $exists ? 'exists' : 'missing';
                $statusText = $exists ? '존재함' : '없음';
                ?>
                <div class="table-item <?php echo $statusClass; ?>">
                    <div class="table-icon">
                        <?php echo $exists ? '✅' : '❌'; ?>
                    </div>
                    <div class="table-info">
                        <div class="table-name"><?php echo htmlspecialchars($tableName); ?></div>
                        <div class="table-desc"><?php echo $tableDescriptions[$tableName]; ?></div>
                    </div>
                    <span class="status-badge <?php echo $statusClass; ?>">
                        <?php echo $statusText; ?>
                    </span>
                </div>
                <?php
            }
            ?>
        </div>
        
        <form method="POST">
            <button 
                type="submit" 
                name="install" 
                value="yes" 
                class="install-button"
                <?php echo $allExist ? 'disabled' : ''; ?>
            >
                <?php echo $allExist ? '✅ 모든 테이블이 설치되어 있습니다' : '🚀 테이블 설치하기'; ?>
            </button>
        </form>
        
        <div class="info-box">
            <h3>📋 생성되는 테이블</h3>
            <ul>
                <li><strong>bidding_rounds</strong> - 입찰 라운드 정보</li>
                <li><strong>bidding_participations</strong> - 입찰 참여 정보</li>
                <li><strong>bidding_product_assignments</strong> - 낙찰자 게시물 배정</li>
                <li><strong>seller_deposits</strong> - 판매자 예치금 계정</li>
                <li><strong>seller_deposit_transactions</strong> - 예치금 거래 내역</li>
            </ul>
        </div>
        
        <div class="warning-box">
            <h3>⚠️ 주의사항</h3>
            <p>• 기존에 테이블이 있으면 건너뜁니다 (CREATE TABLE IF NOT EXISTS 사용)</p>
            <p>• products 테이블이 먼저 존재해야 bidding_product_assignments 테이블이 생성됩니다</p>
            <p>• seller_id 타입: products 테이블은 INT, bidding_participations는 VARCHAR(50)로 정의되어 있으나, MySQL 자동 타입 변환으로 작동합니다</p>
        </div>
    </div>
</body>
</html>

