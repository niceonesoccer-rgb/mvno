<?php
/**
 * 예치금 내역 페이지 (판매자)
 * 경로: /seller/deposit/history.php
 */

require_once __DIR__ . '/../../includes/data/db-config.php';
require_once __DIR__ . '/../../includes/data/auth-functions.php';

$currentUser = getCurrentUser();
$sellerId = $currentUser['user_id'] ?? '';

if (empty($sellerId)) {
    header('Location: /MVNO/seller/login.php');
    exit;
}

$pdo = getDBConnection();

if (!$pdo) {
    die('데이터베이스 연결에 실패했습니다.');
}

// 예치금 잔액 조회
$stmt = $pdo->prepare("SELECT balance FROM seller_deposit_accounts WHERE seller_id = :seller_id");
$stmt->execute([':seller_id' => $sellerId]);
$balanceResult = $stmt->fetch(PDO::FETCH_ASSOC);
$balance = floatval($balanceResult['balance'] ?? 0);

// 필터 처리
$statusFilter = $_GET['status'] ?? '';
$requestPage = max(1, intval($_GET['request_page'] ?? 1));
$historyPage = max(1, intval($_GET['history_page'] ?? 1));
$perPage = 10;
$requestOffset = ($requestPage - 1) * $perPage;
$historyOffset = ($historyPage - 1) * $perPage;

// 예치금 거래 내역 조회 (필터 없음 - 전체 조회)
$whereConditions = ["seller_id = :seller_id"];
$params = [':seller_id' => $sellerId];
$whereClause = implode(' AND ', $whereConditions);

// 전체 개수 조회
$countStmt = $pdo->prepare("
    SELECT COUNT(*) FROM seller_deposit_ledger 
    WHERE $whereClause
");
$countStmt->execute($params);
$totalCount = $countStmt->fetchColumn();
$totalPages = ceil($totalCount / $perPage);

// 입금 신청 내역 조회 (페이지네이션 적용, 상태 필터 적용)
$depositRequestWhereConditions = ["seller_id = :seller_id"];
$depositRequestParams = [':seller_id' => $sellerId];

if ($statusFilter && in_array($statusFilter, ['pending', 'confirmed', 'unpaid'])) {
    $depositRequestWhereConditions[] = "status = :status";
    $depositRequestParams[':status'] = $statusFilter;
}

$depositRequestWhereClause = implode(' AND ', $depositRequestWhereConditions);

$depositRequestCountStmt = $pdo->prepare("
    SELECT COUNT(*) FROM deposit_requests 
    WHERE $depositRequestWhereClause
");
$depositRequestCountStmt->execute($depositRequestParams);
$depositRequestTotalCount = $depositRequestCountStmt->fetchColumn();
$depositRequestTotalPages = ceil($depositRequestTotalCount / $perPage);

$depositRequestStmt = $pdo->prepare("
    SELECT dr.*, ba.bank_name, ba.account_number, ba.account_holder
    FROM deposit_requests dr
    LEFT JOIN bank_accounts ba ON dr.bank_account_id = ba.id
    WHERE $depositRequestWhereClause
    ORDER BY dr.created_at DESC
    LIMIT :limit OFFSET :offset
");

foreach ($depositRequestParams as $key => $value) {
    $depositRequestStmt->bindValue($key, $value);
}
$depositRequestStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$depositRequestStmt->bindValue(':offset', $requestOffset, PDO::PARAM_INT);
$depositRequestStmt->execute();
$depositRequests = $depositRequestStmt->fetchAll(PDO::FETCH_ASSOC);

// 예치금 거래 내역 조회 (페이지네이션 적용)
$stmt = $pdo->prepare("
    SELECT * FROM seller_deposit_ledger 
    WHERE $whereClause
    ORDER BY created_at DESC
    LIMIT :limit OFFSET :offset
");

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $historyOffset, PDO::PARAM_INT);
$stmt->execute();
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

$typeLabels = [
    'deposit' => ['label' => '충전', 'color' => '#10b981'],
    'withdraw' => ['label' => '차감', 'color' => '#ef4444'],
    'refund' => ['label' => '환불', 'color' => '#3b82f6']
];

$depositStatusLabels = [
    'pending' => ['label' => '대기중', 'color' => '#f59e0b'],
    'confirmed' => ['label' => '입금', 'color' => '#10b981'],
    'unpaid' => ['label' => '미입금', 'color' => '#6b7280']
];

require_once __DIR__ . '/../includes/seller-header.php';
?>

<div class="seller-center-container">
    <div class="page-header" style="margin-bottom: 32px;">
        <h1 style="font-size: 28px; font-weight: 800; color: #0f172a; margin-bottom: 8px;">예치금 내역</h1>
        <p style="font-size: 16px; color: #64748b;">예치금 충전 및 사용 내역을 조회합니다.</p>
    </div>
    
    <!-- 예치금 잔액 -->
    <div class="content-box" style="background: #fff; border-radius: 12px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 24px;">
        <h2 style="margin: 0; font-size: 18px; font-weight: 600;">
            예치금 잔액: <span style="color: #6366f1; font-size: 24px;"><?= number_format($balance, 0) ?>원</span>
            <span style="font-size: 14px; color: #64748b; font-weight: 400; margin-left: 8px;">(부가세 포함)</span>
        </h2>
        
    </div>
    
    <!-- 입금 신청 내역 -->
    <div class="content-box" style="background: #fff; border-radius: 12px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 24px;">
        <div style="margin-bottom: 24px;">
            <h2 style="margin: 0 0 16px 0; font-size: 20px; font-weight: 600;">입금 신청 내역</h2>
            
            <div style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap;">
                <form method="GET" style="display: flex; gap: 16px; align-items: center;">
                    <input type="hidden" name="request_page" value="1">
                    <input type="hidden" name="history_page" value="<?= $historyPage ?>">
                    <select name="status" style="padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; width: 200px;">
                        <option value="">전체</option>
                        <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>대기중</option>
                        <option value="confirmed" <?= $statusFilter === 'confirmed' ? 'selected' : '' ?>>입금</option>
                        <option value="unpaid" <?= $statusFilter === 'unpaid' ? 'selected' : '' ?>>미입금</option>
                    </select>
                    
                    <button type="submit" style="padding: 10px 20px; background: #6366f1; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                        조회
                    </button>
                </form>
            </div>
        </div>
        
        <?php if (empty($depositRequests)): ?>
            <div style="text-align: center; padding: 60px 20px; color: #64748b;">
                <div style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;">💳</div>
                <div style="font-size: 18px; font-weight: 600; margin-bottom: 8px; color: #374151;">입금 신청 내역이 없습니다</div>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden;">
                    <thead>
                        <tr style="background: #f1f5f9;">
                            <th style="padding: 12px; text-align: center; font-weight: 600; border-bottom: 2px solid #e2e8f0;">순서</th>
                            <th style="padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0;">신청일시</th>
                            <th style="padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0;">입금자명</th>
                            <th style="padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0;">입금계좌</th>
                            <th style="padding: 12px; text-align: right; font-weight: 600; border-bottom: 2px solid #e2e8f0;">공급가액</th>
                            <th style="padding: 12px; text-align: right; font-weight: 600; border-bottom: 2px solid #e2e8f0;">부가세</th>
                            <th style="padding: 12px; text-align: right; font-weight: 600; border-bottom: 2px solid #e2e8f0;">입금금액<br><span style="font-size: 11px; font-weight: 400; color: #64748b;">(부가세 포함)</span></th>
                            <th style="padding: 12px; text-align: center; font-weight: 600; border-bottom: 2px solid #e2e8f0;">상태</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // 역순 번호 계산 (최신 항목이 큰 번호)
                        $orderNumber = $depositRequestTotalCount - ($requestPage - 1) * $perPage;
                        foreach ($depositRequests as $request): ?>
                            <tr style="border-bottom: 1px solid #e2e8f0;">
                                <td style="padding: 12px; text-align: center;">
                                    <?= $orderNumber-- ?>
                                </td>
                                <td style="padding: 12px;">
                                    <?= date('Y-m-d H:i', strtotime($request['created_at'])) ?>
                                </td>
                                <td style="padding: 12px;"><?= htmlspecialchars($request['depositor_name']) ?></td>
                                <td style="padding: 12px; font-size: 13px; color: #64748b;">
                                    <?= htmlspecialchars($request['bank_name'] ?? '-') ?><br>
                                    <?= htmlspecialchars($request['account_number'] ?? '-') ?><br>
                                    <?= htmlspecialchars($request['account_holder'] ?? '-') ?>
                                </td>
                                <td style="padding: 12px; text-align: right;"><?= number_format(floatval($request['supply_amount'] ?? 0), 0) ?>원</td>
                                <td style="padding: 12px; text-align: right;"><?= number_format(floatval($request['tax_amount'] ?? 0), 0) ?>원</td>
                                <td style="padding: 12px; text-align: right; font-weight: 600;"><?= number_format(floatval($request['amount'] ?? 0), 0) ?>원</td>
                                <td style="padding: 12px; text-align: center;">
                                    <?php
                                    $statusInfo = $depositStatusLabels[$request['status']] ?? ['label' => $request['status'], 'color' => '#64748b'];
                                    ?>
                                    <span style="padding: 4px 12px; background: <?= $statusInfo['color'] ?>20; color: <?= $statusInfo['color'] ?>; border-radius: 4px; font-size: 14px; font-weight: 500;">
                                        <?= $statusInfo['label'] ?>
                                    </span>
                                    <?php if ($request['confirmed_at']): ?>
                                        <div style="font-size: 12px; color: #64748b; margin-top: 4px;">
                                            <?= date('Y-m-d', strtotime($request['confirmed_at'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- 페이지네이션 -->
            <?php if ($depositRequestTotalPages > 1): ?>
                <div style="margin-top: 24px; display: flex; justify-content: center; align-items: center; gap: 8px;">
                    <?php if ($requestPage > 1): ?>
                        <a href="?request_page=<?= $requestPage - 1 ?><?= $historyPage > 1 ? '&history_page=' . $historyPage : '' ?><?= $statusFilter ? '&status=' . htmlspecialchars($statusFilter) : '' ?>" 
                           style="padding: 8px 16px; background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; color: #374151; text-decoration: none; font-weight: 500;">
                            이전
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    $startPage = max(1, $requestPage - 2);
                    $endPage = min($depositRequestTotalPages, $requestPage + 2);
                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                        <a href="?request_page=<?= $i ?><?= $historyPage > 1 ? '&history_page=' . $historyPage : '' ?><?= $statusFilter ? '&status=' . htmlspecialchars($statusFilter) : '' ?>" 
                           style="padding: 8px 16px; background: <?= $i == $requestPage ? '#6366f1' : '#fff' ?>; border: 1px solid #e2e8f0; border-radius: 6px; color: <?= $i == $requestPage ? '#fff' : '#374151' ?>; text-decoration: none; font-weight: 500;">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($requestPage < $depositRequestTotalPages): ?>
                        <a href="?request_page=<?= $requestPage + 1 ?><?= $historyPage > 1 ? '&history_page=' . $historyPage : '' ?><?= $statusFilter ? '&status=' . htmlspecialchars($statusFilter) : '' ?>" 
                           style="padding: 8px 16px; background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; color: #374151; text-decoration: none; font-weight: 500;">
                            다음
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/seller-footer.php'; ?>
