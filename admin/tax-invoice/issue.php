<?php
/**
 * 세금계산서 발행 페이지 (관리자)
 * 경로: /admin/tax-invoice/issue.php
 * 
 * 기능: 기간별 입금 금액 확인 및 입금 건별 세금계산서 발행 상태 관리
 * 실제 세금계산서 발행은 외부에서 처리하며, 여기서는 발행 상태만 관리
 */

// POST 처리는 헤더 출력 전에 처리해야 함 (리다이렉트를 위해)
require_once __DIR__ . '/../../includes/data/db-config.php';
require_once __DIR__ . '/../../includes/data/auth-functions.php';

// GET 파라미터 읽기 (리다이렉트 URL 구성에 필요)
$periodStart = $_GET['period_start'] ?? '';
$periodEnd = $_GET['period_end'] ?? '';
$depositStatusFilter = $_GET['deposit_status'] ?? ''; // 입금 상태 필터
$taxInvoiceStatusFilter = $_GET['tax_invoice_status'] ?? ''; // 세금계산서 발행 상태 필터
$sellerIdFilter = $_GET['seller_id'] ?? ''; // 판매자 아이디 필터
$page = max(1, intval($_GET['page'] ?? 1)); // 페이지 번호
$perPage = intval($_GET['per_page'] ?? 10); // 페이지당 항목 수
// 허용된 per_page 값만 사용 (10, 50, 100)
if (!in_array($perPage, [10, 50, 100])) {
    $perPage = 10;
}

// 세금계산서 상태 일괄 업데이트 처리 (헤더 출력 전에 처리)
$errorMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $pdo = getDBConnection();
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
                if ($sellerIdFilter) $queryParams['seller_id'] = $sellerIdFilter;
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

// 이제 헤더 출력 가능 (리다이렉트가 필요한 경우 이미 처리됨)
require_once __DIR__ . '/../includes/admin-header.php';

$pdo = getDBConnection();

// 기간별 입금 내역 조회
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
    
    // 기간 조건: 신청일시(created_at) 기준
    $whereConditions[] = "(DATE(dr.created_at) >= :period_start 
                          AND DATE(dr.created_at) <= :period_end)";
    $params[':period_start'] = $periodStart;
    $params[':period_end'] = $periodEnd;
    
    // 입금 상태 필터
    if ($depositStatusFilter && in_array($depositStatusFilter, ['pending', 'confirmed', 'unpaid'])) {
        $whereConditions[] = "dr.status = :deposit_status";
        $params[':deposit_status'] = $depositStatusFilter;
    }
    
    // 세금계산서 발행 상태 필터
    if ($taxInvoiceStatusFilter && in_array($taxInvoiceStatusFilter, ['issued', 'unissued', 'cancelled'])) {
        $whereConditions[] = "dr.tax_invoice_status = :tax_invoice_status";
        $params[':tax_invoice_status'] = $taxInvoiceStatusFilter;
    }
    
    // 판매자 아이디 필터
    if ($sellerIdFilter && trim($sellerIdFilter) !== '') {
        $whereConditions[] = "dr.seller_id LIKE :seller_id";
        $params[':seller_id'] = '%' . trim($sellerIdFilter) . '%';
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // 전체 개수 조회 (합계 계산용)
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM deposit_requests dr
        $whereClause
    ");
    $countStmt->execute($params);
    $totalCount = $countStmt->fetchColumn();
    $totalPages = ceil($totalCount / $perPage);
    $offset = ($page - 1) * $perPage;
    
    // 합계 계산 (전체 데이터 기준)
    $summaryStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_count,
            COALESCE(SUM(dr.supply_amount), 0) as total_supply_amount,
            COALESCE(SUM(dr.tax_amount), 0) as total_tax_amount,
            COALESCE(SUM(dr.amount), 0) as total_amount
        FROM deposit_requests dr
        $whereClause
    ");
    $summaryStmt->execute($params);
    $summaryData = $summaryStmt->fetch(PDO::FETCH_ASSOC);
    $summary = [
        'total_count' => intval($summaryData['total_count'] ?? 0),
        'total_supply_amount' => floatval($summaryData['total_supply_amount'] ?? 0),
        'total_tax_amount' => floatval($summaryData['total_tax_amount'] ?? 0),
        'total_amount' => floatval($summaryData['total_amount'] ?? 0)
    ];
    
    // 페이지별 데이터 조회
    $stmt = $pdo->prepare("
        SELECT 
            dr.*,
            ba.bank_name,
            ba.account_number,
            ba.account_holder
        FROM deposit_requests dr
        LEFT JOIN bank_accounts ba ON dr.bank_account_id = ba.id
        $whereClause
        ORDER BY dr.created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    
    // 파라미터 바인딩
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $deposits = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                                    <option value="pending" <?= $depositStatusFilter === 'pending' ? 'selected' : '' ?>>대기중</option>
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
                            
                            <!-- 판매자 아이디 검색 -->
                            <div style="flex: 0 0 auto;">
                                <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px;">판매자 아이디</label>
                                <input type="text" name="seller_id" value="<?= htmlspecialchars($sellerIdFilter) ?>" 
                                       placeholder="아이디 검색"
                                       style="width: 150px; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; background: #fff;">
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
                                </div>
                            </div>
                            
                            <!-- 버튼 영역 (가로 배치) -->
                            <div style="flex: 0 0 auto; display: flex; flex-direction: row; gap: 8px;">
                                <button type="submit" 
                                        style="padding: 10px 24px; background: #6366f1; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; white-space: nowrap;">
                                    검색
                                </button>
                                <a href="<?= $_SERVER['PHP_SELF'] ?>?per_page=<?= $perPage ?>" 
                                   style="padding: 10px 24px; background: #6b7280; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; white-space: nowrap; text-decoration: none; display: inline-block; text-align: center;">
                                    초기화
                                </a>
                            </div>
                            
                            <!-- 페이지당 표시 개수 -->
                            <div style="flex: 0 0 auto; margin-left: auto;">
                                <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px;">표시 개수</label>
                                <select name="per_page" onchange="this.form.submit()" 
                                        style="width: 100px; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; background: #fff;">
                                    <option value="10" <?= $perPage == 10 ? 'selected' : '' ?>>10</option>
                                    <option value="50" <?= $perPage == 50 ? 'selected' : '' ?>>50</option>
                                    <option value="100" <?= $perPage == 100 ? 'selected' : '' ?>>100</option>
                                </select>
                            </div>
                        </div>
                        <input type="hidden" name="page" value="1">
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
                                        <th style="padding: 12px; text-align: center; font-weight: 600; border-bottom: 2px solid #e2e8f0;">순번</th>
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
                                            <td colspan="10" style="padding: 40px; text-align: center; color: #64748b;">
                                                해당 조건에 맞는 입금 내역이 없습니다.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php 
                                        // 순번 계산 준비 (역순) - 전체 개수 기준으로 계산
                                        // 현재 페이지의 첫 번째 항목 번호 = 전체 개수 - (페이지-1) * 페이지당개수
                                        $currentPageStartNumber = isset($totalCount) ? $totalCount - (($page - 1) * $perPage) : count($deposits);
                                        foreach ($deposits as $index => $deposit): 
                                            // 역순 번호: 가장 최신(첫 번째) 항목이 가장 큰 번호
                                            $sequenceNumber = $currentPageStartNumber - $index;
                                        ?>
                                            <tr style="border-bottom: 1px solid #e2e8f0;">
                                                <td style="padding: 12px;">
                                                    <input type="checkbox" name="deposit_ids[]" value="<?= $deposit['id'] ?>" 
                                                           class="deposit-checkbox" style="cursor: pointer;">
                                                </td>
                                                <td style="padding: 12px; text-align: center; color: #64748b;">
                                                    <?= number_format($sequenceNumber) ?>
                                                </td>
                                                <td style="padding: 12px;">
                                                    <?php
                                                    // 신청일시는 created_at (판매자가 예치금 충전을 신청한 시간)
                                                    $displayDate = $deposit['created_at'];
                                                    if ($displayDate) {
                                                        // MySQL DATETIME 값을 그대로 표시 (YYYY-MM-DD HH:MM:SS 형식)
                                                        // 타임존 변환 없이 저장된 값 그대로 사용
                                                        if (strlen($displayDate) >= 16) {
                                                            // YYYY-MM-DD HH:MM:SS 형식이면 시간까지 표시
                                                            echo substr($displayDate, 0, 16);
                                                        } else {
                                                            // 다른 형식이면 기존 방식 사용
                                                            echo date('Y-m-d H:i', strtotime($displayDate));
                                                        }
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
                                                        'pending' => ['label' => '대기중', 'color' => '#f59e0b'],
                                                        'confirmed' => ['label' => '입금', 'color' => '#10b981'],
                                                        'unpaid' => ['label' => '미입금', 'color' => '#6b7280']
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
                        
                        <!-- 페이지네이션 -->
                        <?php if (isset($totalPages) && $totalPages > 1): ?>
                            <?php
                            // 페이지네이션 URL 파라미터 구성
                            $paginationParams = [
                                'period_start' => $periodStart,
                                'period_end' => $periodEnd,
                                'per_page' => $perPage
                            ];
                            if ($depositStatusFilter) $paginationParams['deposit_status'] = $depositStatusFilter;
                            if ($taxInvoiceStatusFilter) $paginationParams['tax_invoice_status'] = $taxInvoiceStatusFilter;
                            if ($sellerIdFilter) $paginationParams['seller_id'] = $sellerIdFilter;
                            $paginationBaseUrl = '?' . http_build_query($paginationParams);
                            ?>
                            <div style="margin-top: 24px; display: flex; justify-content: center; align-items: center; gap: 8px;">
                                <?php if ($page > 1): ?>
                                    <a href="<?= $paginationBaseUrl ?>&page=<?= $page - 1 ?>" 
                                       style="padding: 8px 16px; background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; color: #374151; text-decoration: none; font-weight: 500;">
                                        이전
                                    </a>
                                <?php endif; ?>
                                
                                <?php
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);
                                
                                for ($i = $startPage; $i <= $endPage; $i++):
                                ?>
                                    <a href="<?= $paginationBaseUrl ?>&page=<?= $i ?>" 
                                       style="padding: 8px 16px; background: <?= $i === $page ? '#6366f1' : '#fff' ?>; border: 1px solid #e2e8f0; border-radius: 6px; color: <?= $i === $page ? '#fff' : '#374151' ?>; text-decoration: none; font-weight: <?= $i === $page ? '600' : '500' ?>;">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <a href="<?= $paginationBaseUrl ?>&page=<?= $page + 1 ?>" 
                                       style="padding: 8px 16px; background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; color: #374151; text-decoration: none; font-weight: 500;">
                                        다음
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
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
// 날짜를 YYYY-MM-DD 형식으로 변환 (타임존 변환 없이)
function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// 이번달 버튼 클릭 시
document.getElementById('btnThisMonth')?.addEventListener('click', function() {
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    
    document.querySelector('input[name="period_start"]').value = formatDate(firstDay);
    document.querySelector('input[name="period_end"]').value = formatDate(today);
});

// 지난달 버튼 클릭 시
document.getElementById('btnLastMonth')?.addEventListener('click', function() {
    const today = new Date();
    const firstDayLastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
    const lastDayLastMonth = new Date(today.getFullYear(), today.getMonth(), 0);
    
    document.querySelector('input[name="period_start"]').value = formatDate(firstDayLastMonth);
    document.querySelector('input[name="period_end"]').value = formatDate(lastDayLastMonth);
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
