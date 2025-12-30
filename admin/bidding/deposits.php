<?php
/**
 * 예치금 관리 페이지 (관리자)
 * 경로: /admin/bidding/deposits.php
 */

require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../../includes/data/db-config.php';

$pdo = getDBConnection();
$deposits = [];
$error = null;

try {
    if ($pdo) {
        $stmt = $pdo->query("
            SELECT 
                sd.*,
                u.company_name as seller_name,
                u.user_id as seller_id
            FROM seller_deposits sd
            LEFT JOIN users u ON sd.seller_id = u.user_id
            ORDER BY sd.updated_at DESC
        ");
        $deposits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $error = "예치금 목록을 불러오는 중 오류가 발생했습니다: " . $e->getMessage();
    error_log("Seller deposits list error: " . $e->getMessage());
}
?>

<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
    }
    
    .page-title {
        font-size: 28px;
        font-weight: 700;
        color: #1e293b;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        padding: 12px 24px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }
    
    .table-container {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
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
    
    .balance-positive {
        color: #059669;
        font-weight: 600;
    }
    
    .balance-zero {
        color: #6b7280;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #94a3b8;
    }
    
    .error-message {
        background: #fee2e2;
        color: #991b1b;
        padding: 16px;
        border-radius: 8px;
        margin-bottom: 24px;
    }
    
    .action-link {
        color: #3b82f6;
        text-decoration: none;
        font-weight: 500;
        margin-right: 12px;
    }
    
    .action-link:hover {
        text-decoration: underline;
    }
</style>

<div class="page-header">
    <h1 class="page-title">예치금 관리</h1>
</div>

<?php if ($error): ?>
    <div class="error-message">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<div class="table-container">
    <?php if (empty($deposits)): ?>
        <div class="empty-state">
            <div>💰</div>
            <h3>예치금 계정이 없습니다</h3>
        </div>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>판매자</th>
                    <th>예치금 잔액</th>
                    <th>은행명</th>
                    <th>계좌번호</th>
                    <th>예금주</th>
                    <th>최종 업데이트</th>
                    <th>관리</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($deposits as $deposit): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($deposit['seller_name'] ?? $deposit['seller_id']); ?></td>
                        <td class="<?php echo $deposit['balance'] > 0 ? 'balance-positive' : 'balance-zero'; ?>">
                            <?php echo number_format($deposit['balance']); ?>원
                        </td>
                        <td><?php echo htmlspecialchars($deposit['bank_name'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($deposit['account_number'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($deposit['account_holder'] ?? '-'); ?></td>
                        <td><?php echo $deposit['updated_at'] ? date('Y-m-d H:i:s', strtotime($deposit['updated_at'])) : '-'; ?></td>
                        <td>
                            <a href="/MVNO/admin/bidding/deposit-detail.php?seller_id=<?php echo urlencode($deposit['seller_id']); ?>" class="action-link">
                                상세보기
                            </a>
                            <a href="/MVNO/admin/bidding/deposit-transaction.php?seller_id=<?php echo urlencode($deposit['seller_id']); ?>" class="action-link">
                                거래내역
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>

