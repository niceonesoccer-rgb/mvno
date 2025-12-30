<?php
/**
 * 예치금 관리 페이지 (판매자)
 * 경로: /seller/bidding/deposits.php
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/db-config.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUser = getCurrentUser();

// 판매자 로그인 체크
if (!$currentUser || $currentUser['role'] !== 'seller') {
    header('Location: /MVNO/seller/login.php');
    exit;
}

// 판매자 승인 체크
$approvalStatus = $currentUser['approval_status'] ?? 'pending';
if ($approvalStatus !== 'approved') {
    header('Location: /MVNO/seller/waiting.php');
    exit;
}

// 탈퇴 요청 상태 확인
if (isset($currentUser['withdrawal_requested']) && $currentUser['withdrawal_requested'] === true) {
    header('Location: /MVNO/seller/waiting.php');
    exit;
}

require_once __DIR__ . '/../includes/seller-header.php';

$pdo = getDBConnection();
$deposit = null;
$transactions = [];
$error = null;
$sellerId = (string)$currentUser['user_id'];

try {
    if ($pdo) {
        // 예치금 계정 조회
        $stmt = $pdo->prepare("SELECT * FROM seller_deposits WHERE seller_id = :seller_id");
        $stmt->execute([':seller_id' => $sellerId]);
        $deposit = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 예치금이 없으면 초기 계정 생성
        if (!$deposit) {
            $insertStmt = $pdo->prepare("
                INSERT INTO seller_deposits (seller_id, balance, updated_at)
                VALUES (:seller_id, 0, NOW())
            ");
            $insertStmt->execute([':seller_id' => $sellerId]);
            
            $stmt->execute([':seller_id' => $sellerId]);
            $deposit = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // 거래 내역 조회
        $transStmt = $pdo->prepare("
            SELECT * FROM seller_deposit_transactions
            WHERE seller_id = :seller_id
            ORDER BY created_at DESC
            LIMIT 50
        ");
        $transStmt->execute([':seller_id' => $sellerId]);
        $transactions = $transStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $error = "예치금 정보를 불러오는 중 오류가 발생했습니다: " . $e->getMessage();
    error_log("Seller deposit detail error: " . $e->getMessage());
}

// 거래 유형 라벨
$transactionTypeLabels = [
    'deposit' => '입금',
    'withdrawal' => '출금',
    'bid_deduction' => '입찰 차감',
    'refund' => '환불'
];
?>

<style>
    .page-header {
        margin-bottom: 32px;
    }
    
    .page-title {
        font-size: 32px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 8px;
    }
    
    .page-description {
        color: #64748b;
        font-size: 15px;
    }
    
    .content-card {
        background: white;
        border-radius: 16px;
        padding: 32px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
        margin-bottom: 24px;
    }
    
    .balance-section {
        text-align: center;
        padding: 40px 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 16px;
        color: white;
        margin-bottom: 32px;
    }
    
    .balance-label {
        font-size: 14px;
        opacity: 0.9;
        margin-bottom: 12px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .balance-amount {
        font-size: 48px;
        font-weight: 700;
        margin-bottom: 8px;
    }
    
    .info-section {
        margin-bottom: 32px;
    }
    
    .info-title {
        font-size: 18px;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid #e2e8f0;
    }
    
    .info-row {
        display: flex;
        padding: 16px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    
    .info-row:last-child {
        border-bottom: none;
    }
    
    .info-label {
        width: 150px;
        font-weight: 600;
        color: #64748b;
    }
    
    .info-value {
        flex: 1;
        color: #1e293b;
    }
    
    .table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .table th {
        background: #f8fafc;
        padding: 12px 16px;
        text-align: left;
        font-weight: 600;
        color: #475569;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #e2e8f0;
    }
    
    .table td {
        padding: 16px;
        border-bottom: 1px solid #e2e8f0;
        color: #1e293b;
    }
    
    .table tr:hover {
        background: #f8fafc;
    }
    
    .amount-positive {
        color: #059669;
        font-weight: 600;
    }
    
    .amount-negative {
        color: #dc2626;
        font-weight: 600;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #94a3b8;
    }
    
    .error-message {
        background: #fee2e2;
        color: #991b1b;
        padding: 16px 20px;
        border-radius: 12px;
        margin-bottom: 24px;
        border-left: 4px solid #dc2626;
    }
    
    .btn-edit {
        background: #3b82f6;
        color: white;
        padding: 10px 20px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        display: inline-block;
        margin-top: 16px;
    }
    
    .btn-edit:hover {
        background: #2563eb;
    }
</style>

<div class="page-header">
    <h1 class="page-title">예치금 관리</h1>
    <p class="page-description">예치금 잔액을 확인하고 환불 계좌 정보를 관리할 수 있습니다.</p>
</div>

<?php if ($error): ?>
    <div class="error-message">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<?php if ($deposit): ?>
    <div class="content-card">
        <div class="balance-section">
            <div class="balance-label">예치금 잔액</div>
            <div class="balance-amount"><?php echo number_format($deposit['balance']); ?>원</div>
        </div>
        
        <div class="info-section">
            <h2 class="info-title">환불 계좌 정보</h2>
            <div class="info-row">
                <div class="info-label">은행명</div>
                <div class="info-value"><?php echo htmlspecialchars($deposit['bank_name'] ?? '미등록'); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">계좌번호</div>
                <div class="info-value"><?php echo htmlspecialchars($deposit['account_number'] ?? '미등록'); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">예금주</div>
                <div class="info-value"><?php echo htmlspecialchars($deposit['account_holder'] ?? '미등록'); ?></div>
            </div>
            <a href="/MVNO/seller/bidding/deposit-edit.php" class="btn-edit">계좌 정보 수정</a>
        </div>
    </div>
    
    <div class="content-card">
        <h2 class="info-title">거래 내역</h2>
        <?php if (empty($transactions)): ?>
            <div class="empty-state">
                <div>📋</div>
                <p>거래 내역이 없습니다</p>
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>날짜</th>
                        <th>유형</th>
                        <th>금액</th>
                        <th>잔액</th>
                        <th>설명</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td><?php echo date('Y-m-d H:i:s', strtotime($transaction['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($transactionTypeLabels[$transaction['transaction_type']] ?? $transaction['transaction_type']); ?></td>
                            <td class="<?php echo $transaction['amount'] > 0 ? 'amount-positive' : 'amount-negative'; ?>">
                                <?php echo $transaction['amount'] > 0 ? '+' : ''; ?><?php echo number_format($transaction['amount']); ?>원
                            </td>
                            <td><?php echo number_format($transaction['balance_after']); ?>원</td>
                            <td><?php echo htmlspecialchars($transaction['description'] ?? '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/seller-footer.php'; ?>


