<?php
/**
 * 광고 내역 페이지 (판매자)
 * 경로: /seller/advertisement/list.php
 */

require_once __DIR__ . '/../includes/seller-header.php';
require_once __DIR__ . '/../../includes/data/db-config.php';
require_once __DIR__ . '/../../includes/data/auth-functions.php';

$pdo = getDBConnection();

if (!$pdo) {
    die('데이터베이스 연결에 실패했습니다.');
}

$currentUser = getCurrentUser();
$sellerId = $currentUser['user_id'] ?? '';

if (empty($sellerId)) {
    header('Location: /MVNO/seller/login.php');
    exit;
}

// 탭 파라미터 (기본값: 통신사단독유심)
$activeTab = $_GET['tab'] ?? 'mno_sim';
$validTabs = ['mno_sim', 'mvno', 'mno', 'internet'];
if (!in_array($activeTab, $validTabs)) {
    $activeTab = 'mno_sim';
}

// 상태 필터 (기본값: 전체)
$statusFilter = $_GET['status'] ?? '';

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;

// WHERE 조건 구성
$whereConditions = ["ra.seller_id = :seller_id", "ra.product_type = :product_type"];
$params = [':seller_id' => $sellerId, ':product_type' => $activeTab];

$whereClause = implode(' AND ', $whereConditions);

// 광고 목록 조회
$countStmt = $pdo->prepare("
    SELECT COUNT(*) as total
    FROM rotation_advertisements ra
    WHERE $whereClause
");
$countStmt->execute($params);
$totalAds = $countStmt->fetchColumn();
$totalPages = ceil($totalAds / $perPage);

$offset = ($page - 1) * $perPage;
$stmt = $pdo->prepare("
    SELECT 
        ra.*,
        p.status as product_status,
        CASE ra.product_type
            WHEN 'mno_sim' THEN mno_sim.plan_name
            WHEN 'mvno' THEN mvno.plan_name
            WHEN 'mno' THEN mno.device_name
            WHEN 'internet' THEN CONCAT(COALESCE(inet.registration_place, ''), ' ', COALESCE(inet.speed_option, ''))
            ELSE CONCAT('상품 ID: ', ra.product_id)
        END AS product_name
    FROM rotation_advertisements ra
    LEFT JOIN products p ON ra.product_id = p.id
    LEFT JOIN product_mno_sim_details mno_sim ON ra.product_id = mno_sim.product_id AND ra.product_type = 'mno_sim'
    LEFT JOIN product_mvno_details mvno ON ra.product_id = mvno.product_id AND ra.product_type = 'mvno'
    LEFT JOIN product_mno_details mno ON ra.product_id = mno.product_id AND ra.product_type = 'mno'
    LEFT JOIN product_internet_details inet ON ra.product_id = inet.product_id AND ra.product_type = 'internet'
    WHERE $whereClause
    ORDER BY ra.created_at DESC
    LIMIT :limit OFFSET :offset
");

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$advertisements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 탭별 광고 개수 조회
$tabCounts = ['mno_sim' => 0, 'mvno' => 0, 'mno' => 0, 'internet' => 0];
try {
    $countStmt = $pdo->prepare("
        SELECT product_type, COUNT(*) as count
        FROM rotation_advertisements
        WHERE seller_id = :seller_id
        GROUP BY product_type
    ");
    $countStmt->execute([':seller_id' => $sellerId]);
    $typeCounts = $countStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $tabCounts['mno_sim'] = $typeCounts['mno_sim'] ?? 0;
    $tabCounts['mvno'] = $typeCounts['mvno'] ?? 0;
    $tabCounts['mno'] = $typeCounts['mno'] ?? 0;
    $tabCounts['internet'] = $typeCounts['internet'] ?? 0;
} catch (PDOException $e) {
    error_log("Error fetching tab counts: " . $e->getMessage());
}

// 상태 계산 및 필터링
$now = new DateTime();
$filteredAds = [];

foreach ($advertisements as $ad) {
    $startDate = new DateTime($ad['start_datetime']);
    $endDate = new DateTime($ad['end_datetime']);
    $isAdRunning = $ad['status'] === 'active' && $endDate > $now;
    $isProductActive = ($ad['product_status'] ?? 'inactive') === 'active';
    
    // 표시 상태 계산
    if ($ad['status'] === 'expired' || $ad['status'] === 'cancelled') {
        $displayStatus = 'expired';
    } elseif ($isAdRunning && $isProductActive) {
        $displayStatus = 'active';
    } elseif ($isAdRunning && !$isProductActive) {
        $displayStatus = 'stopped';
    } else {
        $displayStatus = 'expired';
    }
    
    // 상태 필터 적용
    if ($statusFilter === '' || $displayStatus === $statusFilter) {
        $filteredAds[] = array_merge($ad, ['display_status' => $displayStatus]);
    }
}

$productTypeLabels = [
    'mno_sim' => '통신사단독유심',
    'mvno' => '알뜰폰',
    'mno' => '통신사폰',
    'internet' => '인터넷'
];

$displayStatusLabels = [
    'active' => ['label' => '광고중', 'color' => '#f59e0b'],
    'stopped' => ['label' => '광고중지', 'color' => '#f59e0b'],
    'expired' => ['label' => '광고종료', 'color' => '#64748b']
];
?>

<style>
.product-tabs {
    background: white;
    border-radius: 12px;
    padding: 8px;
    margin-bottom: 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
    display: flex;
    gap: 8px;
    overflow-x: auto;
}

.product-tab {
    flex: 1;
    min-width: 120px;
    padding: 12px 20px;
    text-align: center;
    font-size: 15px;
    font-weight: 600;
    color: #6b7280;
    background: transparent;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
}

.product-tab:hover {
    background: #f9fafb;
    color: #374151;
}

.product-tab.active {
    background: #6366f1;
    color: white;
}

.product-tab.active:hover {
    background: #4f46e5;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 24px;
}

.pagination-btn {
    padding: 8px 16px;
    font-size: 14px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background: white;
    color: #374151;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s;
}

.pagination-btn:hover {
    background: #f9fafb;
    border-color: #6366f1;
}

.pagination-btn.active {
    background: #6366f1;
    color: white;
    border-color: #6366f1;
}
</style>

<div class="seller-center-container">
    <div class="page-header" style="margin-bottom: 32px;">
        <h1 style="font-size: 28px; font-weight: 800; color: #0f172a; margin-bottom: 8px;">광고 내역</h1>
        <p style="font-size: 16px; color: #64748b;">신청한 광고 내역을 조회하고 관리합니다.</p>
    </div>
    
    <!-- 탭 메뉴 -->
    <div class="product-tabs">
        <button class="product-tab <?= $activeTab === 'mno_sim' ? 'active' : '' ?>" onclick="switchTab('mno_sim')">
            통신사단독유심 (<?= $tabCounts['mno_sim'] ?>)
        </button>
        <button class="product-tab <?= $activeTab === 'mvno' ? 'active' : '' ?>" onclick="switchTab('mvno')">
            알뜰폰 (<?= $tabCounts['mvno'] ?>)
        </button>
        <button class="product-tab <?= $activeTab === 'mno' ? 'active' : '' ?>" onclick="switchTab('mno')">
            통신사폰 (<?= $tabCounts['mno'] ?>)
        </button>
        <button class="product-tab <?= $activeTab === 'internet' ? 'active' : '' ?>" onclick="switchTab('internet')">
            인터넷 (<?= $tabCounts['internet'] ?>)
        </button>
    </div>
    
    <div class="content-box" style="background: #fff; border-radius: 12px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <!-- 필터 -->
        <div style="margin-bottom: 24px;">
            <form method="GET" id="filterForm" style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap;">
                <input type="hidden" name="tab" value="<?= $activeTab ?>">
                <input type="hidden" name="page" value="1">
                
                <select name="status" style="padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; width: 200px;">
                    <option value="">전체 상태</option>
                    <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>광고중</option>
                    <option value="stopped" <?= $statusFilter === 'stopped' ? 'selected' : '' ?>>광고중지</option>
                    <option value="expired" <?= $statusFilter === 'expired' ? 'selected' : '' ?>>광고종료</option>
                </select>
                
                <button type="submit" style="padding: 10px 20px; background: #6366f1; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                    조회
                </button>
            </form>
        </div>
        
        <?php if (empty($filteredAds)): ?>
            <div style="text-align: center; padding: 60px 20px; color: #64748b;">
                <div style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;">📢</div>
                <div style="font-size: 18px; font-weight: 600; margin-bottom: 8px; color: #374151;">광고 내역이 없습니다</div>
                <a href="register.php" style="display: inline-block; margin-top: 16px; padding: 12px 24px; background: #6366f1; color: #fff; text-decoration: none; border-radius: 8px; font-weight: 600;">
                    광고 신청하기
                </a>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden;">
                    <thead>
                        <tr style="background: #f1f5f9;">
                            <th style="padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0;">신청일시</th>
                            <th style="padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0;">상품명</th>
                            <th style="padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0;">카테고리</th>
                            <th style="padding: 12px; text-align: center; font-weight: 600; border-bottom: 2px solid #e2e8f0;">광고기간</th>
                            <th style="padding: 12px; text-align: right; font-weight: 600; border-bottom: 2px solid #e2e8f0;">금액</th>
                            <th style="padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0;">기간</th>
                            <th style="padding: 12px; text-align: center; font-weight: 600; border-bottom: 2px solid #e2e8f0;">상태</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filteredAds as $ad): ?>
                            <?php
                            $displayStatus = $ad['display_status'];
                            $statusInfo = $displayStatusLabels[$displayStatus] ?? ['label' => $displayStatus, 'color' => '#64748b'];
                            $startDate = new DateTime($ad['start_datetime']);
                            $endDate = new DateTime($ad['end_datetime']);
                            ?>
                            <tr style="border-bottom: 1px solid #e2e8f0;">
                                <td style="padding: 12px;">
                                    <?= date('Y-m-d H:i', strtotime($ad['created_at'])) ?>
                                </td>
                                <td style="padding: 12px; font-weight: 500;">
                                    <?php
                                    $productId = $ad['product_id'];
                                    $productType = $ad['product_type'];
                                    $displayStatus = $ad['display_status'];
                                    
                                    // 광고 상태에 따라 링크 결정
                                    if ($displayStatus === 'active') {
                                        // 광고중인 경우: 고객용 상세 페이지
                                        $urls = [
                                            'mvno' => '/MVNO/mvno/mvno-plan-detail.php?id=' . $productId,
                                            'mno_sim' => '/MVNO/mno-sim/mno-sim-detail.php?id=' . $productId,
                                            'mno' => '/MVNO/mno/mno-phone-detail.php?id=' . $productId,
                                            'internet' => '/MVNO/internets/internet-detail.php?id=' . $productId
                                        ];
                                        $linkUrl = $urls[$productType] ?? '#';
                                        $target = 'target="_blank"';
                                    } else {
                                        // 광고중지 또는 광고종료인 경우: 판매자용 수정 페이지
                                        $urls = [
                                            'mvno' => '/MVNO/seller/products/mvno.php?id=' . $productId,
                                            'mno_sim' => '/MVNO/seller/products/mno-sim.php?id=' . $productId,
                                            'mno' => '/MVNO/seller/products/mno.php?id=' . $productId,
                                            'internet' => '/MVNO/seller/products/internet.php?id=' . $productId
                                        ];
                                        $linkUrl = $urls[$productType] ?? '#';
                                        $target = '';
                                    }
                                    
                                    $productName = !empty($ad['product_name']) ? htmlspecialchars($ad['product_name']) : ('상품 ID: ' . $productId);
                                    ?>
                                    <a href="<?= $linkUrl ?>" <?= $target ?> style="color: #6366f1; text-decoration: none; font-weight: 500;" onmouseover="this.style.textDecoration='underline';" onmouseout="this.style.textDecoration='none';">
                                        <?= $productName ?>
                                    </a>
                                </td>
                                <td style="padding: 12px;"><?= $productTypeLabels[$ad['product_type']] ?? $ad['product_type'] ?></td>
                                <td style="padding: 12px; text-align: center;">
                                    <?= $ad['advertisement_days'] ?>일
                                </td>
                                <td style="padding: 12px; text-align: right; font-weight: 600;">
                                    <?= number_format(floatval($ad['price'] ?? 0), 0) ?>원
                                </td>
                                <td style="padding: 12px; font-size: 13px; color: #64748b;">
                                    <?= date('Y-m-d H:i', strtotime($ad['start_datetime'])) ?><br>
                                    ~ <?= date('Y-m-d H:i', strtotime($ad['end_datetime'])) ?>
                                </td>
                                <td style="padding: 12px; text-align: center;">
                                    <span style="padding: 4px 12px; background: <?= $statusInfo['color'] ?>20; color: <?= $statusInfo['color'] ?>; border-radius: 4px; font-size: 14px; font-weight: 500;">
                                        <?= $statusInfo['label'] ?>
                                    </span>
                                    <?php if ($displayStatus === 'expired'): ?>
                                        <div style="margin-top: 8px;">
                                            <a href="register.php?tab=<?= $ad['product_type'] ?>" 
                                               style="display: inline-block; padding: 4px 12px; background: #6366f1; color: #fff; text-decoration: none; border-radius: 4px; font-size: 12px; font-weight: 500;">
                                                다시 광고신청
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- 페이지네이션 -->
            <?php if ($totalPages > 1): ?>
                <?php
                // 페이지 그룹 계산 (10개씩 그룹화)
                $pageGroupSize = 10;
                $currentGroup = ceil($page / $pageGroupSize);
                $startPage = ($currentGroup - 1) * $pageGroupSize + 1;
                $endPage = min($currentGroup * $pageGroupSize, $totalPages);
                $prevGroupLastPage = ($currentGroup - 1) * $pageGroupSize;
                $nextGroupFirstPage = $currentGroup * $pageGroupSize + 1;
                ?>
                <div class="pagination">
                    <?php if ($currentGroup > 1): ?>
                        <a href="?tab=<?= $activeTab ?>&status=<?= htmlspecialchars($statusFilter) ?>&page=<?= $prevGroupLastPage ?>" class="pagination-btn">이전</a>
                    <?php else: ?>
                        <span class="pagination-btn disabled">이전</span>
                    <?php endif; ?>
                    
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <a href="?tab=<?= $activeTab ?>&status=<?= htmlspecialchars($statusFilter) ?>&page=<?= $i ?>" class="pagination-btn <?= $i === $page ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($nextGroupFirstPage <= $totalPages): ?>
                        <a href="?tab=<?= $activeTab ?>&status=<?= htmlspecialchars($statusFilter) ?>&page=<?= $nextGroupFirstPage ?>" class="pagination-btn">다음</a>
                    <?php else: ?>
                        <span class="pagination-btn disabled">다음</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function switchTab(tab) {
    const params = new URLSearchParams(window.location.search);
    params.set('tab', tab);
    params.delete('page'); // 탭 변경 시 첫 페이지로
    params.delete('status'); // 탭 변경 시 상태 필터 초기화
    window.location.href = '?' + params.toString();
}
</script>

<?php include __DIR__ . '/../includes/seller-footer.php'; ?>
