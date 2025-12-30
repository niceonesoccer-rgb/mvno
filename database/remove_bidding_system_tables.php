<?php
/**
 * 입찰 시스템 테이블 삭제 페이지
 * 경로: /database/remove_bidding_system_tables.php
 * 
 * ⚠️ 주의: 이 스크립트는 모든 입찰 관련 테이블과 데이터를 영구적으로 삭제합니다!
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
    $tableNames = [
        'bidding_rounds', 
        'bidding_participations', 
        'bidding_product_assignments', 
        'seller_deposits', 
        'seller_deposit_transactions'
    ];
    
    foreach ($tableNames as $tableName) {
        $checkStmt = $pdo->query("SHOW TABLES LIKE '$tableName'");
        $existingTables[$tableName] = $checkStmt->fetch() !== false;
        
        // 데이터 개수 확인
        if ($existingTables[$tableName]) {
            $countStmt = $pdo->query("SELECT COUNT(*) as cnt FROM `$tableName`");
            $count = $countStmt->fetch(PDO::FETCH_ASSOC)['cnt'];
            $tables[$tableName] = [
                'exists' => true,
                'count' => $count
            ];
        } else {
            $tables[$tableName] = [
                'exists' => false,
                'count' => 0
            ];
        }
    }
    
    // 삭제 버튼 클릭 시
    if (isset($_POST['remove']) && $_POST['remove'] === 'yes') {
        // 확인 단어 입력 체크
        $confirmWord = $_POST['confirm_word'] ?? '';
        if ($confirmWord !== 'DELETE') {
            $error = '확인 단어가 일치하지 않습니다. "DELETE"를 정확히 입력해주세요.';
        } else {
            try {
                $pdo->beginTransaction();
                
                // 외래키 제약조건 때문에 삭제 순서가 중요합니다
                // 자식 테이블부터 먼저 삭제해야 합니다
                
                // 1. 예치금 거래 내역 테이블 삭제 (외래키 없음)
                if ($existingTables['seller_deposit_transactions']) {
                    $pdo->exec("DROP TABLE IF EXISTS `seller_deposit_transactions`");
                    $message .= "✅ seller_deposit_transactions 테이블이 삭제되었습니다.<br>";
                }
                
                // 2. 판매자 예치금 계정 테이블 삭제 (외래키 없음)
                if ($existingTables['seller_deposits']) {
                    $pdo->exec("DROP TABLE IF EXISTS `seller_deposits`");
                    $message .= "✅ seller_deposits 테이블이 삭제되었습니다.<br>";
                }
                
                // 3. 낙찰자 게시물 배정 테이블 삭제
                if ($existingTables['bidding_product_assignments']) {
                    $pdo->exec("DROP TABLE IF EXISTS `bidding_product_assignments`");
                    $message .= "✅ bidding_product_assignments 테이블이 삭제되었습니다.<br>";
                }
                
                // 4. 입찰 참여 테이블 삭제
                if ($existingTables['bidding_participations']) {
                    $pdo->exec("DROP TABLE IF EXISTS `bidding_participations`");
                    $message .= "✅ bidding_participations 테이블이 삭제되었습니다.<br>";
                }
                
                // 5. 입찰 라운드 테이블 삭제 (최상위 테이블)
                if ($existingTables['bidding_rounds']) {
                    $pdo->exec("DROP TABLE IF EXISTS `bidding_rounds`");
                    $message .= "✅ bidding_rounds 테이블이 삭제되었습니다.<br>";
                }
                
                $pdo->commit();
                
                if (empty($message)) {
                    $message = "삭제할 테이블이 없습니다.";
                } else {
                    $message = "<strong>⚠️ 모든 입찰 관련 테이블이 삭제되었습니다!</strong><br><br>" . $message;
                }
                
                // 페이지 새로고침하여 상태 업데이트
                header("Location: " . $_SERVER['PHP_SELF'] . "?removed=1");
                exit;
                
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = "오류 발생: " . $e->getMessage();
                error_log("Bidding system table removal error: " . $e->getMessage());
            }
        }
    }
    
    // 삭제 후 상태 다시 확인
    if (isset($_GET['removed'])) {
        foreach ($tableNames as $tableName) {
            $checkStmt = $pdo->query("SHOW TABLES LIKE '$tableName'");
            $tables[$tableName] = [
                'exists' => $checkStmt->fetch() !== false,
                'count' => 0
            ];
        }
    }
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>입찰 시스템 테이블 삭제</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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
        
        .warning-banner {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .warning-banner h2 {
            color: #856404;
            margin-bottom: 10px;
            font-size: 20px;
        }
        
        .warning-banner ul {
            margin-left: 20px;
            color: #856404;
        }
        
        .warning-banner li {
            margin: 5px 0;
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
            border-left-color: #dc3545;
        }
        
        .table-item.missing {
            border-left-color: #28a745;
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
        
        .table-count {
            font-size: 12px;
            color: #dc3545;
            font-weight: 600;
            margin-left: 10px;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-badge.exists {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-badge.missing {
            background: #d4edda;
            color: #155724;
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
        
        .remove-form {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .remove-form h3 {
            color: #856404;
            margin-bottom: 15px;
        }
        
        .confirm-input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ffc107;
            border-radius: 6px;
            font-size: 16px;
            margin-bottom: 15px;
            font-weight: 600;
            text-align: center;
        }
        
        .remove-button {
            background: linear-gradient(135deg, #f5576c 0%, #f093fb 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            width: 100%;
        }
        
        .remove-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(245, 87, 108, 0.4);
        }
        
        .remove-button:disabled {
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
    </style>
</head>
<body>
    <div class="container">
        <h1>🗑️ 입찰 시스템 테이블 삭제</h1>
        <p class="subtitle">입찰 시스템 관련 모든 테이블과 데이터를 삭제합니다. <strong>이 작업은 되돌릴 수 없습니다!</strong></p>
        
        <div class="warning-banner">
            <h2>⚠️ 경고</h2>
            <ul>
                <li><strong>이 작업은 되돌릴 수 없습니다!</strong></li>
                <li>모든 입찰 라운드, 참여 내역, 예치금 정보가 영구적으로 삭제됩니다.</li>
                <li>삭제 전에 반드시 데이터를 백업하세요.</li>
                <li>광고 시스템을 구축하기 전에 입찰 시스템을 완전히 제거하려는 경우에만 사용하세요.</li>
            </ul>
        </div>
        
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
            
            $hasTables = false;
            foreach ($tables as $tableName => $info) {
                if ($info['exists']) {
                    $hasTables = true;
                    break;
                }
            }
            
            foreach ($tables as $tableName => $info) {
                $statusClass = $info['exists'] ? 'exists' : 'missing';
                $statusText = $info['exists'] ? '존재함' : '삭제됨';
                ?>
                <div class="table-item <?php echo $statusClass; ?>">
                    <div class="table-icon">
                        <?php echo $info['exists'] ? '⚠️' : '✅'; ?>
                    </div>
                    <div class="table-info">
                        <div class="table-name">
                            <?php echo htmlspecialchars($tableName); ?>
                            <?php if ($info['exists'] && $info['count'] > 0): ?>
                                <span class="table-count">(데이터: <?php echo number_format($info['count']); ?>건)</span>
                            <?php endif; ?>
                        </div>
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
        
        <?php if ($hasTables): ?>
            <form method="POST" class="remove-form">
                <h3>⚠️ 삭제 확인</h3>
                <p style="color: #856404; margin-bottom: 15px;">
                    모든 입찰 관련 테이블을 삭제하려면 아래에 <strong>"DELETE"</strong>를 정확히 입력하세요.
                </p>
                <input 
                    type="text" 
                    name="confirm_word" 
                    class="confirm-input" 
                    placeholder="DELETE 입력"
                    required
                    autocomplete="off"
                >
                <button 
                    type="submit" 
                    name="remove" 
                    value="yes" 
                    class="remove-button"
                    onclick="return confirm('정말로 모든 입찰 관련 테이블을 삭제하시겠습니까?\n\n이 작업은 되돌릴 수 없습니다!');"
                >
                    🗑️ 모든 테이블 삭제하기
                </button>
            </form>
        <?php else: ?>
            <div class="message success">
                ✅ 모든 입찰 관련 테이블이 이미 삭제되었습니다.
            </div>
        <?php endif; ?>
        
        <div class="info-box">
            <h3>📋 삭제되는 테이블</h3>
            <ul>
                <li><strong>bidding_rounds</strong> - 입찰 라운드 정보</li>
                <li><strong>bidding_participations</strong> - 입찰 참여 정보</li>
                <li><strong>bidding_product_assignments</strong> - 낙찰자 게시물 배정</li>
                <li><strong>seller_deposits</strong> - 판매자 예치금 계정</li>
                <li><strong>seller_deposit_transactions</strong> - 예치금 거래 내역</li>
            </ul>
            <p style="margin-top: 10px; color: #555;">
                <strong>참고:</strong> 외래키 제약조건 때문에 자식 테이블부터 순서대로 삭제됩니다.
            </p>
        </div>
    </div>
</body>
</html>
