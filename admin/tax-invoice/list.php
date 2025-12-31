<?php
/**
 * 세금계산서 발행 내역 페이지 (관리자)
 * 경로: /admin/tax-invoice/list.php
 */

require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../../includes/data/db-config.php';
require_once __DIR__ . '/../../includes/data/auth-functions.php';

$pdo = getDBConnection();

if (!$pdo) {
    die('데이터베이스 연결에 실패했습니다.');
}

// 필터 처리
$statusFilter = $_GET['status'] ?? '';

// 입금 내역 조회 (세금계산서 발행 상태 기준)
$whereConditions = [];
$params = [];

if ($statusFilter && in_array($statusFilter, ['issued', 'unissued', 'cancelled'])) {
    $whereConditions[] = "dr.tax_invoice_status = :status";
    $params[':status'] = $statusFilter;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

$stmt = $pdo->prepare("
    SELECT 
        dr.*,
        ba.bank_name,
        ba.account_number,
        ba.account_holder
    FROM deposit_requests dr
    LEFT JOIN bank_accounts ba ON dr.bank_account_id = ba.id
    $whereClause
    ORDER BY dr.tax_invoice_issued_at DESC, dr.created_at DESC
");

$stmt->execute($params);
$deposits = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="admin-content-wrapper">
    <div class="admin-content">
        <div class="page-header">
            <h1>세금계산서 발행 내역</h1>
            <p>발행된 세금계산서 내역을 조회합니다.</p>
        </div>
        
        <div class="content-box">
            <div style="padding: 24px;">
                <!-- 필터 -->
                <div style="margin-bottom: 24px;">
                    <form method="GET" style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap;">
                        <select name="status" style="padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; background: #fff; width: 200px;">
                            <option value="">전체</option>
                            <option value="issued" <?= $statusFilter === 'issued' ? 'selected' : '' ?>>발행</option>
                            <option value="unissued" <?= $statusFilter === 'unissued' ? 'selected' : '' ?>>미발행</option>
                            <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>취소</option>
                        </select>
                        
                        <button type="submit" style="padding: 10px 24px; background: #6366f1; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px;">
                            조회
                        </button>
                    </form>
                </div>
                
                <!-- 목록 -->
                <?php if (empty($deposits)): ?>
                    <div style="text-align: center; padding: 60px 20px; color: #64748b;">
                        <div style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;">📄</div>
                        <div style="font-size: 18px; font-weight: 600; margin-bottom: 8px; color: #374151;">세금계산서 발행 내역이 없습니다</div>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden;">
                            <thead>
                                <tr style="background: #f1f5f9;">
                                    <th style="padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0;">신청일시</th>
                                    <th style="padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0;">판매자</th>
                                    <th style="padding: 12px; text-align: right; font-weight: 600; border-bottom: 2px solid #e2e8f0;">공급가액</th>
                                    <th style="padding: 12px; text-align: right; font-weight: 600; border-bottom: 2px solid #e2e8f0;">부가세</th>
                                    <th style="padding: 12px; text-align: right; font-weight: 600; border-bottom: 2px solid #e2e8f0;">합계금액</th>
                                    <th style="padding: 12px; text-align: center; font-weight: 600; border-bottom: 2px solid #e2e8f0;">발행상태</th>
                                    <th style="padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0;">발행일시</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($deposits as $deposit): ?>
                                    <tr style="border-bottom: 1px solid #e2e8f0;">
                                        <td style="padding: 12px;">
                                            <?= date('Y-m-d H:i', strtotime($deposit['created_at'])) ?>
                                        </td>
                                        <td style="padding: 12px; font-weight: 500;"><?= htmlspecialchars($deposit['seller_id']) ?></td>
                                        <td style="padding: 12px; text-align: right;"><?= number_format(floatval($deposit['supply_amount'] ?? 0), 0) ?>원</td>
                                        <td style="padding: 12px; text-align: right;"><?= number_format(floatval($deposit['tax_amount'] ?? 0), 0) ?>원</td>
                                        <td style="padding: 12px; text-align: right; font-weight: 600;"><?= number_format(floatval($deposit['amount'] ?? 0), 0) ?>원</td>
                                        <td style="padding: 12px; text-align: center;">
                                            <?php
                                            $status = $deposit['tax_invoice_status'] ?? 'unissued';
                                            $statusLabels = [
                                                'unissued' => ['label' => '미발행', 'color' => '#64748b'],
                                                'issued' => ['label' => '발행', 'color' => '#10b981'],
                                                'cancelled' => ['label' => '취소', 'color' => '#ef4444']
                                            ];
                                            $statusInfo = $statusLabels[$status] ?? $statusLabels['unissued'];
                                            ?>
                                            <span style="padding: 4px 12px; background: <?= $statusInfo['color'] ?>20; color: <?= $statusInfo['color'] ?>; border-radius: 4px; font-size: 14px; font-weight: 500;">
                                                <?= $statusInfo['label'] ?>
                                            </span>
                                        </td>
                                        <td style="padding: 12px;">
                                            <?php
                                            if ($deposit['tax_invoice_issued_at']) {
                                                echo date('Y-m-d H:i', strtotime($deposit['tax_invoice_issued_at']));
                                                if ($deposit['tax_invoice_issued_by']) {
                                                    echo '<br><span style="font-size: 12px; color: #64748b;">처리자: ' . htmlspecialchars($deposit['tax_invoice_issued_by']) . '</span>';
                                                }
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
