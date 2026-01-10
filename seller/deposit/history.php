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
$activeTab = $_GET['tab'] ?? 'requests'; // 'requests' 또는 'history'
$requestPage = max(1, intval($_GET['request_page'] ?? 1));
$historyPage = max(1, intval($_GET['history_page'] ?? 1));

// 페이지네이션 옵션 (10, 50, 100)
$requestPerPage = max(10, min(100, intval($_GET['request_per_page'] ?? 10)));
if (!in_array($requestPerPage, [10, 50, 100])) {
    $requestPerPage = 10;
}

$historyPerPage = max(10, min(100, intval($_GET['history_per_page'] ?? 10)));
if (!in_array($historyPerPage, [10, 50, 100])) {
    $historyPerPage = 10;
}

$requestOffset = ($requestPage - 1) * $requestPerPage;
$historyOffset = ($historyPage - 1) * $historyPerPage;

// 예치금 거래 내역 조회 (필터 없음 - 전체 조회)
$whereConditions = ["sdl.seller_id = :seller_id"];
$params = [':seller_id' => $sellerId];
$whereClause = implode(' AND ', $whereConditions);

// 전체 개수 조회 (예치금 거래 내역)
$countStmt = $pdo->prepare("
    SELECT COUNT(*) FROM seller_deposit_ledger sdl
    WHERE $whereClause
");
$countStmt->execute($params);
$totalCount = $countStmt->fetchColumn();
$totalPages = ceil($totalCount / $historyPerPage);

// 입금 신청 내역 조회 (페이지네이션 적용, 상태 필터 적용)
$depositRequestWhereConditions = ["seller_id = :seller_id"];
$depositRequestParams = [':seller_id' => $sellerId];

if ($statusFilter && in_array($statusFilter, ['pending', 'confirmed', 'unpaid', 'refunded'])) {
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
$depositRequestTotalPages = ceil($depositRequestTotalCount / $requestPerPage);

$depositRequestStmt = $pdo->prepare("
    SELECT 
        dr.*,
        COALESCE(dr.bank_name, ba.bank_name) as bank_name,
        COALESCE(dr.account_number, ba.account_number) as account_number,
        COALESCE(dr.account_holder, ba.account_holder) as account_holder
    FROM deposit_requests dr
    LEFT JOIN bank_accounts ba ON dr.bank_account_id = ba.id
    WHERE $depositRequestWhereClause
    ORDER BY dr.created_at DESC
    LIMIT :limit OFFSET :offset
");

foreach ($depositRequestParams as $key => $value) {
    $depositRequestStmt->bindValue($key, $value);
}
$depositRequestStmt->bindValue(':limit', $requestPerPage, PDO::PARAM_INT);
$depositRequestStmt->bindValue(':offset', $requestOffset, PDO::PARAM_INT);
$depositRequestStmt->execute();
$depositRequests = $depositRequestStmt->fetchAll(PDO::FETCH_ASSOC);

// 예치금 거래 내역 조회 (페이지네이션 적용, 상품명 및 카테고리 포함)
$stmt = $pdo->prepare("
    SELECT 
        sdl.*,
        ra.product_type,
        ra.product_id,
        CASE ra.product_type
            WHEN 'mno_sim' THEN mno_sim.plan_name
            WHEN 'mvno' THEN mvno.plan_name
            WHEN 'mno' THEN mno.device_name
            WHEN 'internet' THEN CONCAT(COALESCE(inet.registration_place, ''), ' ', COALESCE(inet.speed_option, ''))
            ELSE NULL
        END AS product_name
    FROM seller_deposit_ledger sdl
    LEFT JOIN rotation_advertisements ra ON sdl.advertisement_id = ra.id
    LEFT JOIN product_mno_sim_details mno_sim ON ra.product_id = mno_sim.product_id AND ra.product_type = 'mno_sim'
    LEFT JOIN product_mvno_details mvno ON ra.product_id = mvno.product_id AND ra.product_type = 'mvno'
    LEFT JOIN product_mno_details mno ON ra.product_id = mno.product_id AND ra.product_type = 'mno'
    LEFT JOIN product_internet_details inet ON ra.product_id = inet.product_id AND ra.product_type = 'internet'
    WHERE $whereClause
    ORDER BY sdl.created_at DESC
    LIMIT :limit OFFSET :offset
");

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $historyPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $historyOffset, PDO::PARAM_INT);
$stmt->execute();
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

$typeLabels = [
    'deposit' => ['label' => '충전', 'color' => '#10b981'],
    'withdraw' => ['label' => '차감', 'color' => '#ef4444'],
    'refund' => ['label' => '환불', 'color' => '#ef4444']
];

// product_type을 카테고리 라벨로 변환하는 함수
function getCategoryLabel($productType) {
    $labels = [
        'mno_sim' => '통신사단독유심',
        'mvno' => '알뜰폰',
        'mno' => '통신사폰',
        'internet' => '인터넷'
    ];
    return $labels[$productType] ?? $productType;
}

$depositStatusLabels = [
    'pending' => ['label' => '대기중', 'color' => '#f59e0b'],
    'confirmed' => ['label' => '입금', 'color' => '#10b981'],
    'unpaid' => ['label' => '미입금', 'color' => '#6b7280'],
    'refunded' => ['label' => '환불', 'color' => '#ef4444']
];

$taxInvoiceStatusLabels = [
    'unissued' => ['label' => '미발행', 'color' => '#64748b'],
    'issued' => ['label' => '발행', 'color' => '#10b981'],
    'cancelled' => ['label' => '취소', 'color' => '#ef4444']
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
    
    <!-- 탭 메뉴 -->
    <div class="content-box" style="background: #fff; border-radius: 12px; padding: 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 24px; overflow: hidden;">
        <div style="display: flex; border-bottom: 2px solid #e2e8f0;">
            <button onclick="switchTab('requests')" class="deposit-tab <?= $activeTab === 'requests' ? 'active' : '' ?>" style="flex: 1; padding: 16px 24px; background: none; border: none; font-size: 16px; font-weight: 600; color: #64748b; cursor: pointer; transition: all 0.3s; border-bottom: 3px solid transparent; margin-bottom: -2px;">
                입금 신청 내역
                <?php if ($depositRequestTotalCount > 0): ?>
                <span style="margin-left: 8px; padding: 2px 8px; background: #e2e8f0; border-radius: 12px; font-size: 13px; font-weight: 600;"><?= $depositRequestTotalCount ?></span>
                <?php endif; ?>
            </button>
            <button onclick="switchTab('history')" class="deposit-tab <?= $activeTab === 'history' ? 'active' : '' ?>" style="flex: 1; padding: 16px 24px; background: none; border: none; font-size: 16px; font-weight: 600; color: #64748b; cursor: pointer; transition: all 0.3s; border-bottom: 3px solid transparent; margin-bottom: -2px;">
                예치금 거래 내역
                <?php if ($totalCount > 0): ?>
                <span style="margin-left: 8px; padding: 2px 8px; background: #e2e8f0; border-radius: 12px; font-size: 13px; font-weight: 600;"><?= $totalCount ?></span>
                <?php endif; ?>
            </button>
        </div>
    </div>
    
    <!-- 입금 신청 내역 탭 -->
    <div class="content-box tab-content" id="tab-requests" style="background: #fff; border-radius: 12px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: <?= $activeTab === 'requests' ? 'block' : 'none' ?>;">
        <div style="margin-bottom: 24px;">
            <h2 style="margin: 0 0 16px 0; font-size: 20px; font-weight: 600;">입금 신청 내역</h2>
            
            <div style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap; justify-content: space-between;">
                <form method="GET" style="display: flex; gap: 16px; align-items: center;">
                    <input type="hidden" name="tab" value="requests">
                    <input type="hidden" name="request_page" value="1">
                    <input type="hidden" name="request_per_page" value="<?= $requestPerPage ?>">
                    <select name="status" style="padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; width: 200px;">
                        <option value="">전체</option>
                        <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>대기중</option>
                        <option value="confirmed" <?= $statusFilter === 'confirmed' ? 'selected' : '' ?>>입금</option>
                        <option value="unpaid" <?= $statusFilter === 'unpaid' ? 'selected' : '' ?>>미입금</option>
                        <option value="refunded" <?= $statusFilter === 'refunded' ? 'selected' : '' ?>>환불</option>
                    </select>
                    
                    <button type="submit" style="padding: 10px 20px; background: #6366f1; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                        조회
                    </button>
                </form>
                
                <form method="GET" style="display: flex; gap: 8px; align-items: center;">
                    <input type="hidden" name="tab" value="requests">
                    <input type="hidden" name="request_page" value="<?= $requestPage ?>">
                    <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
                    <label style="font-size: 14px; color: #64748b; font-weight: 500;">페이지당:</label>
                    <select name="request_per_page" onchange="this.form.submit()" style="padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; cursor: pointer;">
                        <option value="10" <?= $requestPerPage === 10 ? 'selected' : '' ?>>10개</option>
                        <option value="50" <?= $requestPerPage === 50 ? 'selected' : '' ?>>50개</option>
                        <option value="100" <?= $requestPerPage === 100 ? 'selected' : '' ?>>100개</option>
                    </select>
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
                            <th style="padding: 12px; text-align: right; font-weight: 600; border-bottom: 2px solid #e2e8f0;">입금금액<br><span style="font-size: 11px; font-weight: 400; color: #64748b;">(부가세 포함)</span></th>
                            <th style="padding: 12px; text-align: center; font-weight: 600; border-bottom: 2px solid #e2e8f0;">입금상태</th>
                            <th style="padding: 12px; text-align: center; font-weight: 600; border-bottom: 2px solid #e2e8f0;">계산서발행</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // 역순 번호 계산 (최신 항목이 큰 번호)
                        $orderNumber = $depositRequestTotalCount - ($requestPage - 1) * $requestPerPage;
                        foreach ($depositRequests as $request): ?>
                            <tr style="border-bottom: 1px solid #e2e8f0;">
                                <td style="padding: 12px; text-align: center;">
                                    <?= $orderNumber-- ?>
                                </td>
                                <td style="padding: 12px;">
                                    <?= date('Y-m-d H:i', strtotime($request['created_at'])) ?>
                                </td>
                                <td style="padding: 12px;"><?= htmlspecialchars($request['depositor_name'] ?? '-') ?></td>
                                <td style="padding: 12px; font-size: 13px; color: #64748b;">
                                    <?php if (!empty($request['bank_name']) || !empty($request['account_number'])): ?>
                                        <?= htmlspecialchars($request['bank_name'] ?? '-') ?><br>
                                        <?= htmlspecialchars($request['account_number'] ?? '-') ?><br>
                                        <?= htmlspecialchars($request['account_holder'] ?? '-') ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <?php
                                // 환불 여부 확인
                                $isRefunded = !empty($request['refunded_at']) && $request['status'] === 'confirmed';
                                $amountSign = $isRefunded ? '-' : '';
                                ?>
                                <td style="padding: 12px; text-align: right; font-weight: 600; color: <?= $isRefunded ? '#ef4444' : '#374151' ?>;">
                                    <?= $amountSign ?><?= number_format(floatval($request['amount'] ?? 0), 0) ?>원
                                </td>
                                <td style="padding: 12px; text-align: center;">
                                    <?php
                                    $depositStatus = $request['status'];
                                    // 환불 여부 확인 (confirmed 상태이고 refunded_at이 있으면 환불로 표시)
                                    $isRefunded = !empty($request['refunded_at']) && $depositStatus === 'confirmed';
                                    if ($isRefunded) {
                                        $displayStatus = 'refunded';
                                    } else {
                                        $displayStatus = $depositStatus;
                                    }
                                    
                                    $statusInfo = $depositStatusLabels[$displayStatus] ?? ['label' => $displayStatus, 'color' => '#64748b'];
                                    ?>
                                    <div style="display: flex; flex-direction: column; align-items: center; gap: 4px;">
                                        <span style="padding: 4px 12px; background: <?= $statusInfo['color'] ?>20; color: <?= $statusInfo['color'] ?>; border-radius: 4px; font-size: 14px; font-weight: 500;">
                                            <?= $statusInfo['label'] ?>
                                        </span>
                                    </div>
                                </td>
                                <td style="padding: 12px; text-align: center;">
                                    <?php
                                    $taxInvoiceStatus = $request['tax_invoice_status'] ?? 'unissued';
                                    $taxInvoiceStatusInfo = $taxInvoiceStatusLabels[$taxInvoiceStatus] ?? $taxInvoiceStatusLabels['unissued'];
                                    ?>
                                    <span style="padding: 4px 12px; background: <?= $taxInvoiceStatusInfo['color'] ?>20; color: <?= $taxInvoiceStatusInfo['color'] ?>; border-radius: 4px; font-size: 14px; font-weight: 500;">
                                        <?= $taxInvoiceStatusInfo['label'] ?>
                                    </span>
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
                        <a href="?tab=requests&request_page=<?= $requestPage - 1 ?>&request_per_page=<?= $requestPerPage ?><?= $statusFilter ? '&status=' . htmlspecialchars($statusFilter) : '' ?>" 
                           style="padding: 8px 16px; background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; color: #374151; text-decoration: none; font-weight: 500;">
                            이전
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    $startPage = max(1, $requestPage - 2);
                    $endPage = min($depositRequestTotalPages, $requestPage + 2);
                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                        <a href="?tab=requests&request_page=<?= $i ?>&request_per_page=<?= $requestPerPage ?><?= $statusFilter ? '&status=' . htmlspecialchars($statusFilter) : '' ?>" 
                           style="padding: 8px 16px; background: <?= $i == $requestPage ? '#6366f1' : '#fff' ?>; border: 1px solid #e2e8f0; border-radius: 6px; color: <?= $i == $requestPage ? '#fff' : '#374151' ?>; text-decoration: none; font-weight: 500;">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($requestPage < $depositRequestTotalPages): ?>
                        <a href="?tab=requests&request_page=<?= $requestPage + 1 ?>&request_per_page=<?= $requestPerPage ?><?= $statusFilter ? '&status=' . htmlspecialchars($statusFilter) : '' ?>" 
                           style="padding: 8px 16px; background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; color: #374151; text-decoration: none; font-weight: 500;">
                            다음
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <!-- 예치금 거래 내역 탭 -->
    <div class="content-box tab-content" id="tab-history" style="background: #fff; border-radius: 12px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: <?= $activeTab === 'history' ? 'block' : 'none' ?>;">
        <div style="margin-bottom: 24px; display: flex; justify-content: flex-end;">
            <form method="GET" style="display: flex; gap: 8px; align-items: center;">
                <input type="hidden" name="tab" value="history">
                <input type="hidden" name="history_page" value="<?= $historyPage ?>">
                <label style="font-size: 14px; color: #64748b; font-weight: 500;">페이지당:</label>
                <select name="history_per_page" onchange="this.form.submit()" style="padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; cursor: pointer;">
                    <option value="10" <?= $historyPerPage === 10 ? 'selected' : '' ?>>10개</option>
                    <option value="50" <?= $historyPerPage === 50 ? 'selected' : '' ?>>50개</option>
                    <option value="100" <?= $historyPerPage === 100 ? 'selected' : '' ?>>100개</option>
                </select>
            </form>
        </div>
        
        <?php if (empty($history)): ?>
            <div style="text-align: center; padding: 60px 20px; color: #64748b;">
                <div style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;">💳</div>
                <div style="font-size: 18px; font-weight: 600; margin-bottom: 8px; color: #374151;">거래 내역이 없습니다</div>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden;">
                    <thead>
                        <tr style="background: #f1f5f9;">
                            <th style="padding: 12px; text-align: center; font-weight: 600; border-bottom: 2px solid #e2e8f0;">순서</th>
                            <th style="padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0;">일시</th>
                            <th style="padding: 12px; text-align: center; font-weight: 600; border-bottom: 2px solid #e2e8f0;">구분</th>
                            <th style="padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0;">카테고리</th>
                            <th style="padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0;">상품명</th>
                            <th style="padding: 12px; text-align: right; font-weight: 600; border-bottom: 2px solid #e2e8f0;">금액</th>
                            <th style="padding: 12px; text-align: right; font-weight: 600; border-bottom: 2px solid #e2e8f0;">거래 전 잔액</th>
                            <th style="padding: 12px; text-align: right; font-weight: 600; border-bottom: 2px solid #e2e8f0;">거래 후 잔액</th>
                            <th style="padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0;">내용</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // 역순 번호 계산 (최신 항목이 큰 번호)
                        $orderNumber = $totalCount - ($historyPage - 1) * $historyPerPage;
                        foreach ($history as $item): 
                            // description이 "광고 취소 환불"인 경우 refund로 처리
                            $transactionType = $item['transaction_type'];
                            if ($transactionType === 'deposit' && strpos($item['description'] ?? '', '광고 취소 환불') !== false) {
                                $transactionType = 'refund';
                            }
                            $typeInfo = $typeLabels[$transactionType] ?? ['label' => $item['transaction_type'], 'color' => '#64748b'];
                        ?>
                            <tr style="border-bottom: 1px solid #e2e8f0;">
                                <td style="padding: 12px; text-align: center;">
                                    <?= $orderNumber-- ?>
                                </td>
                                <td style="padding: 12px;">
                                    <?= date('Y-m-d H:i', strtotime($item['created_at'])) ?>
                                </td>
                                <td style="padding: 12px; text-align: center;">
                                    <span style="padding: 4px 12px; background: <?= $typeInfo['color'] ?>20; color: <?= $typeInfo['color'] ?>; border-radius: 4px; font-size: 14px; font-weight: 500;">
                                        <?= $typeInfo['label'] ?>
                                    </span>
                                </td>
                                <td style="padding: 12px; font-size: 14px; color: #64748b;">
                                    <?php if (!empty($item['product_type'])): ?>
                                        <?= htmlspecialchars(getCategoryLabel($item['product_type'])) ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px; font-size: 14px; color: #374151; font-weight: 500;">
                                    <?php if (!empty($item['product_name'])): ?>
                                        <?= htmlspecialchars($item['product_name']) ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px; text-align: right; font-size: 14px; font-weight: 600; color: <?= $transactionType === 'deposit' ? $typeInfo['color'] : '#ef4444' ?>;">
                                    <?php 
                                    // amount의 절댓값 사용 (이미 음수로 저장되어 있을 수 있음)
                                    $amountValue = abs(floatval($item['amount']));
                                    // deposit만 +, refund와 withdraw는 -
                                    $sign = ($transactionType === 'deposit' ? '+' : '-');
                                    ?>
                                    <?= $sign ?><?= number_format($amountValue, 0) ?>원
                                </td>
                                <td style="padding: 12px; text-align: right; font-size: 14px; color: #64748b;">
                                    <?= number_format($item['balance_before'], 0) ?>원
                                </td>
                                <td style="padding: 12px; text-align: right; font-size: 14px; font-weight: 600; color: #0f172a;">
                                    <?= number_format($item['balance_after'], 0) ?>원
                                </td>
                                <td style="padding: 12px; font-size: 14px; color: #64748b;">
                                    <?= htmlspecialchars($item['description'] ?? '-') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- 페이지네이션 -->
            <?php if ($totalPages > 1): ?>
                <div style="margin-top: 24px; display: flex; justify-content: center; align-items: center; gap: 8px;">
                    <?php if ($historyPage > 1): ?>
                        <a href="?tab=history&history_page=<?= $historyPage - 1 ?>&history_per_page=<?= $historyPerPage ?>" 
                           style="padding: 8px 16px; background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; color: #374151; text-decoration: none; font-weight: 500;">
                            이전
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    $startPage = max(1, $historyPage - 2);
                    $endPage = min($totalPages, $historyPage + 2);
                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                        <a href="?tab=history&history_page=<?= $i ?>&history_per_page=<?= $historyPerPage ?>" 
                           style="padding: 8px 16px; background: <?= $i == $historyPage ? '#6366f1' : '#fff' ?>; border: 1px solid #e2e8f0; border-radius: 6px; color: <?= $i == $historyPage ? '#fff' : '#374151' ?>; text-decoration: none; font-weight: 500;">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($historyPage < $totalPages): ?>
                        <a href="?tab=history&history_page=<?= $historyPage + 1 ?>&history_per_page=<?= $historyPerPage ?>" 
                           style="padding: 8px 16px; background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; color: #374151; text-decoration: none; font-weight: 500;">
                            다음
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.deposit-tab.active {
    color: #6366f1 !important;
    border-bottom-color: #6366f1 !important;
    background: linear-gradient(to top, rgba(99, 102, 241, 0.05), transparent) !important;
}
.deposit-tab:hover {
    background: linear-gradient(to top, rgba(99, 102, 241, 0.03), transparent) !important;
    color: #6366f1 !important;
}
</style>

<script>
function switchTab(tab) {
    const params = new URLSearchParams(window.location.search);
    params.set('tab', tab);
    
    // 탭에 따라 페이지 초기화
    if (tab === 'requests') {
        params.set('request_page', '1');
    } else {
        params.set('history_page', '1');
    }
    
    window.location.href = '?' + params.toString();
}
</script>

<?php include __DIR__ . '/../includes/seller-footer.php'; ?>
