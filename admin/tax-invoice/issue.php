<?php
/**
 * 세금계산서 발행 페이지 (관리자)
 * 경로: /admin/tax-invoice/issue.php
 * 
 * 기능: 기간별 입금 금액 확인 및 입금 건별 세금계산서 발행 상태 관리
 * 실제 세금계산서 발행은 외부에서 처리하며, 여기서는 발행 상태만 관리
 */

require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../../includes/data/db-config.php';
require_once __DIR__ . '/../../includes/data/auth-functions.php';

$pdo = getDBConnection();

// 기간별 입금 내역 조회
$periodStart = $_GET['period_start'] ?? '';
$periodEnd = $_GET['period_end'] ?? '';
$depositStatusFilter = $_GET['deposit_status'] ?? ''; // 입금 상태 필터
$taxInvoiceStatusFilter = $_GET['tax_invoice_status'] ?? ''; // 세금계산서 발행 상태 필터

$deposits = [];
$summary = [
    'total_count' => 0,
    'total_supply_amount' => 0,
    'total_tax_amount' => 0,
    'total_amount' => 0
];

if ($periodStart && $periodEnd) {
    // 쿼리 조건 구성
    $whereConditions = [];
    $params = [];
    
    // 기간 조건: 입금 확인일시가 있으면 confirmed_at 기준, 없으면 created_at 기준
    $whereConditions[] = "(DATE(COALESCE(dr.confirmed_at, dr.created_at)) >= :period_start 
                          AND DATE(COALESCE(dr.confirmed_at, dr.created_at)) <= :period_end)";
    $params[':period_start'] = $periodStart;
    $params[':period_end'] = $periodEnd;
    
    // 입금 상태 필터
    if ($depositStatusFilter && in_array($depositStatusFilter, ['confirmed', 'unpaid'])) {
        $whereConditions[] = "dr.status = :deposit_status";
        $params[':deposit_status'] = $depositStatusFilter;
    }
    
    // 세금계산서 발행 상태 필터
    if ($taxInvoiceStatusFilter && in_array($taxInvoiceStatusFilter, ['issued', 'unissued', 'cancelled'])) {
        $whereConditions[] = "dr.tax_invoice_status = :tax_invoice_status";
        $params[':tax_invoice_status'] = $taxInvoiceStatusFilter;
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
        ORDER BY COALESCE(dr.confirmed_at, dr.created_at) DESC
    ");
    $stmt->execute($params);
    $deposits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 합계 계산
    foreach ($deposits as $deposit) {
        $summary['total_count']++;
        $summary['total_supply_amount'] += floatval($deposit['supply_amount']);
        $summary['total_tax_amount'] += floatval($deposit['tax_amount']);
        $summary['total_amount'] += floatval($deposit['amount']);
    }
}

// 세금계산서 상태 일괄 업데이트 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $depositIds = $_POST['deposit_ids'] ?? [];
        $newStatus = $_POST['status'] ?? '';
        
        if (!empty($depositIds) && in_array($newStatus, ['issued', 'unissued', 'cancelled'])) {
            $placeholders = implode(',', array_fill(0, count($depositIds), '?'));
            $stmt = $pdo->prepare("
                UPDATE deposit_requests 
                SET tax_invoice_status = ?,
                    tax_invoice_issued_at = CASE WHEN ? = 'issued' THEN NOW() ELSE tax_invoice_issued_at END,
                    tax_invoice_issued_by = CASE WHEN ? = 'issued' THEN ? ELSE tax_invoice_issued_by END
                WHERE id IN ($placeholders)
            ");
            
            // 관리자 ID 가져오기
            $adminId = 'system';
            if (isset($_SESSION['admin_id'])) {
                $adminId = $_SESSION['admin_id'];
            } elseif (isset($_SESSION['user_id'])) {
                $adminId = $_SESSION['user_id'];
            } elseif (function_exists('getCurrentUser')) {
                $currentUser = getCurrentUser();
                $adminId = $currentUser['user_id'] ?? 'system';
            }
            
            $params = [$newStatus, $newStatus, $newStatus, $adminId];
            $params = array_merge($params, $depositIds);
            
            try {
                $stmt->execute($params);
                
                // 필터 파라미터 유지
                $queryParams = ['period_start' => $periodStart, 'period_end' => $periodEnd];
                if ($depositStatusFilter) $queryParams['deposit_status'] = $depositStatusFilter;
                if ($taxInvoiceStatusFilter) $queryParams['tax_invoice_status'] = $taxInvoiceStatusFilter;
                $queryParams['success'] = 1;
                
                header('Location: ' . $_SERVER['PHP_SELF'] . '?' . http_build_query($queryParams));
                exit;
            } catch (PDOException $e) {
                error_log('세금계산서 발행 상태 업데이트 오류: ' . $e->getMessage());
                $errorMessage = '상태 변경 중 오류가 발생했습니다.';
            }
        }
    }
}
?>

<div class="admin-content-wrapper">
    <div class="admin-content">
        <div class="page-header">
            <h1>세금계산서 발행 관리</h1>
            <p>기간별 입금 금액을 확인하고 입금 건별 세금계산서 발행 상태를 관리합니다.</p>
        </div>
        
        <div class="content-box">
            <div style="padding: 24px;">
                <?php if (isset($_GET['success'])): ?>
                    <div style="padding: 12px; background: #d1fae5; color: #065f46; border-radius: 6px; margin-bottom: 20px;">
                        세금계산서 발행 상태가 업데이트되었습니다.
                    </div>
                <?php endif; ?>
                
                <?php if (isset($errorMessage)): ?>
                    <div style="padding: 12px; background: #fee2e2; color: #991b1b; border-radius: 6px; margin-bottom: 20px;">
                        <?= htmlspecialchars($errorMessage) ?>
                    </div>
                <?php endif; ?>
                
                <!-- 검색 및 필터 영역 -->
                <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 24px;">
                    <form method="GET" id="searchForm">
                        <div style="display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap;">
                            <!-- 입금 상태 -->
                            <div style="flex: 0 0 auto;">
                                <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px;">입금 상태</label>
                                <select name="deposit_status" 
                                        style="width: 150px; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; background: #fff;">
                                    <option value="">전체</option>
                                    <option value="confirmed" <?= $depositStatusFilter === 'confirmed' ? 'selected' : '' ?>>입금</option>
                                    <option value="unpaid" <?= $depositStatusFilter === 'unpaid' ? 'selected' : '' ?>>미입금</option>
                                </select>
                            </div>
                            
                            <!-- 세금계산서 발행 상태 -->
                            <div style="flex: 0 0 auto;">
                                <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px;">세금계산서 발행 상태</label>
                                <select name="tax_invoice_status" 
                                        style="width: 150px; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; background: #fff;">
                                    <option value="">전체</option>
                                    <option value="issued" <?= $taxInvoiceStatusFilter === 'issued' ? 'selected' : '' ?>>발행</option>
                                    <option value="unissued" <?= $taxInvoiceStatusFilter === 'unissued' ? 'selected' : '' ?>>미발행</option>
                                    <option value="cancelled" <?= $taxInvoiceStatusFilter === 'cancelled' ? 'selected' : '' ?>>취소</option>
                                </select>
                            </div>
                            
                            <!-- 기간 설정 -->
                            <div style="flex: 0 0 auto;">
                                <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px;">기간 설정</label>
                                <div style="display: flex; gap: 8px; align-items: center;">
                                    <input type="date" name="period_start" value="<?= htmlspecialchars($periodStart) ?>" 
                                           style="padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; width: 140px;" required>
                                    <span style="color: #64748b; font-weight: 500;">~</span>
                                    <input type="date" name="period_end" value="<?= htmlspecialchars($periodEnd) ?>" 
                                           style="padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; width: 140px;" required>
                                    <button type="button" id="btnThisMonth" 
                                            style="padding: 10px 16px; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; border-radius: 6px; cursor: pointer; font-weight: 500; font-size: 14px; white-space: nowrap;">
                                        이번달
                                    </button>
                                    <button type="button" id="btnLastMonth" 
                                            style="padding: 10px 16px; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; border-radius: 6px; cursor: pointer; font-weight: 500; font-size: 14px; white-space: nowrap;">
                                        지난달
                                    </button>
                                    <button type="submit" 
                                            style="padding: 10px 24px; background: #6366f1; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; white-space: nowrap;">
                                        조회
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                
                <?php if ($periodStart && $periodEnd): ?>
                    <!-- 합계 정보 -->
                    <div style="background: #f8fafc; padding: 20px; border-radius: 8px; margin-bottom: 24px;">
                        <h3 style="margin: 0 0 16px 0; font-size: 18px; font-weight: 600;">기간별 합계</h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                            <div>
                                <div style="color: #64748b; font-size: 14px; margin-bottom: 4px;">입금 건수</div>
                                <div style="font-size: 20px; font-weight: 600;"><?= number_format($summary['total_count']) ?>건</div>
                            </div>
                            <div>
                                <div style="color: #64748b; font-size: 14px; margin-bottom: 4px;">총 공급가액</div>
                                <div style="font-size: 20px; font-weight: 600;"><?= number_format($summary['total_supply_amount']) ?>원</div>
                            </div>
                            <div>
                                <div style="color: #64748b; font-size: 14px; margin-bottom: 4px;">총 부가세</div>
                                <div style="font-size: 20px; font-weight: 600;"><?= number_format($summary['total_tax_amount']) ?>원</div>
                            </div>
                            <div>
                                <div style="color: #64748b; font-size: 14px; margin-bottom: 4px;">총 합계금액</div>
                                <div style="font-size: 20px; font-weight: 600; color: #6366f1;"><?= number_format($summary['total_amount']) ?>원</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 입금 건 목록 -->
                    <form method="POST" id="statusForm">
                        <input type="hidden" name="action" value="update_status">
                        <div style="margin-bottom: 16px; display: flex; gap: 12px; align-items: center;">
                            <span style="font-weight: 600;">선택한 건의 상태를 변경:</span>
                            <select name="status" id="statusSelect" 
                                    style="padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 6px;">
                                <option value="unissued">미발행</option>
                                <option value="issued">발행</option>
                                <option value="cancelled">취소</option>
                            </select>
                            <button type="submit" 
                                    style="padding: 8px 16px; background: #6366f1; color: #fff; border: none; border-radius: 6px; cursor: pointer;">
                                적용
                            </button>
                        </div>
                        
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden;">
                                <thead>
                                    <tr style="background: #f1f5f9;">
                                        <th style="padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0;">
                                            <input type="checkbox" id="selectAll" style="cursor: pointer;">
                                        </th>
                                        <th style="padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0;">신청일시</th>
                                        <th style="padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0;">판매자</th>
                                        <th style="padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0;">입금자명</th>
                                        <th style="padding: 12px; text-align: right; font-weight: 600; border-bottom: 2px solid #e2e8f0;">공급가액</th>
                                        <th style="padding: 12px; text-align: right; font-weight: 600; border-bottom: 2px solid #e2e8f0;">부가세</th>
                                        <th style="padding: 12px; text-align: right; font-weight: 600; border-bottom: 2px solid #e2e8f0;">합계금액</th>
                                        <th style="padding: 12px; text-align: center; font-weight: 600; border-bottom: 2px solid #e2e8f0;">입금상태</th>
                                        <th style="padding: 12px; text-align: center; font-weight: 600; border-bottom: 2px solid #e2e8f0;">발행상태</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($deposits)): ?>
                                        <tr>
                                            <td colspan="9" style="padding: 40px; text-align: center; color: #64748b;">
                                                해당 조건에 맞는 입금 내역이 없습니다.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($deposits as $deposit): ?>
                                            <tr style="border-bottom: 1px solid #e2e8f0;">
                                                <td style="padding: 12px;">
                                                    <input type="checkbox" name="deposit_ids[]" value="<?= $deposit['id'] ?>" 
                                                           class="deposit-checkbox" style="cursor: pointer;">
                                                </td>
                                                <td style="padding: 12px;">
                                                    <?php
                                                    $displayDate = $deposit['confirmed_at'] ?: $deposit['created_at'];
                                                    if ($displayDate) {
                                                        echo date('Y-m-d H:i', strtotime($displayDate));
                                                    } else {
                                                        echo '-';
                                                    }
                                                    ?>
                                                </td>
                                                <td style="padding: 12px;"><?= htmlspecialchars($deposit['seller_id']) ?></td>
                                                <td style="padding: 12px;"><?= htmlspecialchars($deposit['depositor_name']) ?></td>
                                                <td style="padding: 12px; text-align: right;"><?= number_format(floatval($deposit['supply_amount'] ?? 0), 0) ?>원</td>
                                                <td style="padding: 12px; text-align: right;"><?= number_format(floatval($deposit['tax_amount'] ?? 0), 0) ?>원</td>
                                                <td style="padding: 12px; text-align: right; font-weight: 600;"><?= number_format(floatval($deposit['amount'] ?? 0), 0) ?>원</td>
                                                <td style="padding: 12px; text-align: center;">
                                                    <?php
                                                    $depositStatus = $deposit['status'];
                                                    $depositStatusLabels = [
                                                        'confirmed' => ['label' => '입금', 'color' => '#10b981'],
                                                        'unpaid' => ['label' => '미입금', 'color' => '#f59e0b']
                                                    ];
                                                    $currentDepositStatus = $depositStatusLabels[$depositStatus] ?? ['label' => $depositStatus, 'color' => '#64748b'];
                                                    ?>
                                                    <span style="padding: 4px 12px; background: <?= $currentDepositStatus['color'] ?>20; color: <?= $currentDepositStatus['color'] ?>; border-radius: 4px; font-size: 14px; font-weight: 500;">
                                                        <?= $currentDepositStatus['label'] ?>
                                                    </span>
                                                </td>
                                                <td style="padding: 12px; text-align: center;">
                                                    <?php
                                                    $status = $deposit['tax_invoice_status'] ?? 'unissued';
                                                    $statusLabels = [
                                                        'unissued' => ['label' => '미발행', 'color' => '#64748b'],
                                                        'issued' => ['label' => '발행', 'color' => '#10b981'],
                                                        'cancelled' => ['label' => '취소', 'color' => '#ef4444']
                                                    ];
                                                    $currentStatus = $statusLabels[$status] ?? $statusLabels['unissued'];
                                                    ?>
                                                    <span style="padding: 4px 12px; background: <?= $currentStatus['color'] ?>20; color: <?= $currentStatus['color'] ?>; border-radius: 4px; font-size: 14px; font-weight: 500;">
                                                        <?= $currentStatus['label'] ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </form>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: #64748b;">
                        기간을 선택하고 조회 버튼을 클릭하세요.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// 이번달 버튼 클릭 시
document.getElementById('btnThisMonth')?.addEventListener('click', function() {
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    
    document.querySelector('input[name="period_start"]').value = firstDay.toISOString().split('T')[0];
    document.querySelector('input[name="period_end"]').value = today.toISOString().split('T')[0];
});

// 지난달 버튼 클릭 시
document.getElementById('btnLastMonth')?.addEventListener('click', function() {
    const today = new Date();
    const firstDayLastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
    const lastDayLastMonth = new Date(today.getFullYear(), today.getMonth(), 0);
    
    document.querySelector('input[name="period_start"]').value = firstDayLastMonth.toISOString().split('T')[0];
    document.querySelector('input[name="period_end"]').value = lastDayLastMonth.toISOString().split('T')[0];
});

// 전체 선택 체크박스
document.getElementById('selectAll')?.addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.deposit-checkbox');
    checkboxes.forEach(cb => cb.checked = this.checked);
});

// 폼 제출 전 확인
document.getElementById('statusForm')?.addEventListener('submit', function(e) {
    const checked = document.querySelectorAll('.deposit-checkbox:checked');
    if (checked.length === 0) {
        e.preventDefault();
        alert('선택한 입금 건이 없습니다.');
        return false;
    }
    
    const status = document.getElementById('statusSelect').value;
    const statusLabel = status === 'issued' ? '발행' : (status === 'cancelled' ? '취소' : '미발행');
    
    if (!confirm(`선택한 ${checked.length}건의 상태를 "${statusLabel}"로 변경하시겠습니까?`)) {
        e.preventDefault();
        return false;
    }
});
</script>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
