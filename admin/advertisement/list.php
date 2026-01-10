<?php
/**
 * 광고 목록 페이지 (관리자)
 * 경로: /admin/advertisement/list.php
 */

require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../../includes/data/path-config.php';
require_once __DIR__ . '/../../includes/data/db-config.php';
require_once __DIR__ . '/../../includes/data/auth-functions.php';

$pdo = getDBConnection();

if (!$pdo) {
    die('데이터베이스 연결에 실패했습니다.');
}

// 필터 및 검색 파라미터 처리
$categoryFilter = $_GET['category'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$searchKeyword = trim($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = intval($_GET['per_page'] ?? 10);
if (!in_array($perPage, [10, 50, 100, 500])) {
    $perPage = 10;
}

// 광고 목록 조회
$whereConditions = [];
$params = [];

if ($categoryFilter && in_array($categoryFilter, ['mvno', 'mno', 'internet', 'mno_sim'])) {
    $whereConditions[] = "ra.product_type = :product_type";
    $params[':product_type'] = $categoryFilter;
}

if ($statusFilter && in_array($statusFilter, ['active', 'expired', 'cancelled'])) {
    if ($statusFilter === 'expired') {
        // 광고 종료: status가 expired이거나 cancelled이거나, active이지만 end_datetime이 지난 경우
        $whereConditions[] = "(ra.status = 'expired' OR ra.status = 'cancelled' OR (ra.status = 'active' AND ra.end_datetime <= NOW()))";
    } elseif ($statusFilter === 'active') {
        // 광고중: status가 active이고 end_datetime이 아직 지나지 않은 경우
        $whereConditions[] = "(ra.status = 'active' AND ra.end_datetime > NOW())";
    } else {
        // 취소됨
        $whereConditions[] = "ra.status = :status";
        $params[':status'] = $statusFilter;
    }
}

// 판매자 아이디 검색
if (!empty($searchKeyword)) {
    $whereConditions[] = "ra.seller_id LIKE :search_seller";
    $params[':search_seller'] = '%' . $searchKeyword . '%';
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// 전체 개수 조회
$countStmt = $pdo->prepare("
    SELECT COUNT(*) as total
    FROM rotation_advertisements ra
    $whereClause
");
$countStmt->execute($params);
$totalAds = $countStmt->fetchColumn();
$totalPages = ceil($totalAds / $perPage);

// 페이지네이션
$offset = ($page - 1) * $perPage;

// 광고 목록 조회 (상품명 포함)
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
    $whereClause
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

$productTypeLabels = [
    'mno_sim' => '통신사단독유심',
    'mvno' => '알뜰폰',
    'mno' => '통신사폰',
    'internet' => '인터넷'
];

$statusLabels = [
    'active' => ['label' => '광고중', 'color' => '#10b981'],
    'expired' => ['label' => '광고종료', 'color' => '#64748b'],
    'cancelled' => ['label' => '취소됨', 'color' => '#ef4444']
];

// 페이지네이션 파라미터
$paginationParams = [];
if ($categoryFilter) $paginationParams['category'] = $categoryFilter;
if ($statusFilter) $paginationParams['status'] = $statusFilter;
if ($searchKeyword) $paginationParams['search'] = $searchKeyword;
if ($perPage != 10) $paginationParams['per_page'] = $perPage;
?>

<style>
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    margin-top: 24px;
    flex-wrap: wrap;
}

.pagination a,
.pagination span {
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    text-decoration: none;
    color: #374151;
    font-size: 14px;
    transition: all 0.2s;
}

.pagination a:hover {
    background: #f3f4f6;
    border-color: #9ca3af;
}

.pagination .current {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}
</style>

<div class="admin-content-wrapper">
    <div class="admin-content">
        <div class="page-header">
            <h1>광고 목록</h1>
            <p>신청된 광고 목록을 조회하고 관리합니다.</p>
        </div>
        
        <div class="content-box">
            <div style="padding: 24px;">
                <!-- 필터 및 검색 -->
                <div style="margin-bottom: 24px;">
                    <form method="GET" style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap;">
                        <input type="hidden" name="page" value="1">
                        
                        <select name="category" style="padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; background: #fff; width: 200px;">
                            <option value="">전체 카테고리</option>
                            <option value="mno_sim" <?= $categoryFilter === 'mno_sim' ? 'selected' : '' ?>>통신사단독유심</option>
                            <option value="mvno" <?= $categoryFilter === 'mvno' ? 'selected' : '' ?>>알뜰폰</option>
                            <option value="mno" <?= $categoryFilter === 'mno' ? 'selected' : '' ?>>통신사폰</option>
                            <option value="internet" <?= $categoryFilter === 'internet' ? 'selected' : '' ?>>인터넷</option>
                        </select>
                        
                        <select name="status" style="padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; background: #fff; width: 200px;">
                            <option value="">전체 상태</option>
                            <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>광고중</option>
                            <option value="expired" <?= $statusFilter === 'expired' ? 'selected' : '' ?>>광고종료</option>
                            <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>취소됨</option>
                        </select>
                        
                        <input type="text" name="search" placeholder="판매자 아이디 검색" value="<?= htmlspecialchars($searchKeyword) ?>" 
                               style="padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; background: #fff; width: 200px;">
                        
                        <select name="per_page" style="padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; background: #fff; width: 150px;" onchange="this.form.submit()">
                            <option value="10" <?= $perPage === 10 ? 'selected' : '' ?>>페이지당 10개</option>
                            <option value="50" <?= $perPage === 50 ? 'selected' : '' ?>>페이지당 50개</option>
                            <option value="100" <?= $perPage === 100 ? 'selected' : '' ?>>페이지당 100개</option>
                            <option value="500" <?= $perPage === 500 ? 'selected' : '' ?>>페이지당 500개</option>
                        </select>
                        
                        <button type="submit" style="padding: 10px 24px; background: #6366f1; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px;">
                            조회
                        </button>
                    </form>
                </div>
                
                <!-- 광고 목록 -->
                <?php if (empty($advertisements)): ?>
                    <div style="text-align: center; padding: 60px 20px; color: #64748b;">
                        <div style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;">📢</div>
                        <div style="font-size: 18px; font-weight: 600; margin-bottom: 8px; color: #374151;">광고 내역이 없습니다</div>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden;">
                            <thead>
                                <tr style="background: #f1f5f9;">
                                    <th style="padding: 12px; text-align: center; font-weight: 600; border-bottom: 2px solid #e2e8f0; width: 60px;">번호</th>
                                    <th style="padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0;">신청일시</th>
                                    <th style="padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0;">판매자</th>
                                    <th style="padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0;">상품ID</th>
                                    <th style="padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0;">상품명</th>
                                    <th style="padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0;">카테고리</th>
                                    <th style="padding: 12px; text-align: center; font-weight: 600; border-bottom: 2px solid #e2e8f0;">기간</th>
                                    <th style="padding: 12px; text-align: right; font-weight: 600; border-bottom: 2px solid #e2e8f0;">금액</th>
                                    <th style="padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0;">기간</th>
                                    <th style="padding: 12px; text-align: center; font-weight: 600; border-bottom: 2px solid #e2e8f0;">상태</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $rowNum = $totalAds - ($page - 1) * $perPage;
                                foreach ($advertisements as $ad): 
                                    $now = new DateTime();
                                    $startDate = new DateTime($ad['start_datetime']);
                                    $endDate = new DateTime($ad['end_datetime']);
                                    $isProductActive = ($ad['product_status'] ?? 'inactive') === 'active';
                                    
                                    // 실제 표시 상태 계산
                                    $displayStatus = $ad['status'];
                                    if ($ad['status'] === 'active' && $endDate <= $now) {
                                        // status는 active이지만 종료일이 지난 경우 expired로 표시
                                        $displayStatus = 'expired';
                                    }
                                    
                                    $statusInfo = $statusLabels[$displayStatus] ?? ['label' => $displayStatus, 'color' => '#64748b'];
                                    $isAdRunning = $displayStatus === 'active' && $endDate > $now;
                                ?>
                                    <tr style="border-bottom: 1px solid #e2e8f0;">
                                        <td style="padding: 12px; text-align: center;"><?= $rowNum-- ?></td>
                                        <td style="padding: 12px;">
                                            <?= date('Y-m-d H:i', strtotime($ad['created_at'])) ?>
                                        </td>
                                        <td style="padding: 12px; font-weight: 500;"><?= htmlspecialchars($ad['seller_id']) ?></td>
                                        <td style="padding: 12px;"><?= $ad['product_id'] ?></td>
                                        <td style="padding: 12px; font-weight: 500;">
                                            <?= !empty($ad['product_name']) ? htmlspecialchars($ad['product_name']) : ('상품 ID: ' . $ad['product_id']) ?>
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
                                            <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                                                <span style="padding: 4px 12px; background: <?= $statusInfo['color'] ?>20; color: <?= $statusInfo['color'] ?>; border-radius: 4px; font-size: 14px; font-weight: 500;">
                                                    <?= $statusInfo['label'] ?>
                                                </span>
                                                <?php if ($isAdRunning && !$isProductActive): ?>
                                                    <div style="font-size: 11px; color: #f59e0b;">(상품 판매종료)</div>
                                                <?php endif; ?>
                                                <?php if ($displayStatus !== 'cancelled' && $displayStatus !== 'expired'): ?>
                                                    <button type="button" 
                                                            onclick="openStatusEditModal(<?= $ad['id'] ?>, '<?= htmlspecialchars($ad['seller_id'], ENT_QUOTES) ?>', '<?= htmlspecialchars($ad['product_name'] ?? '', ENT_QUOTES) ?>', <?= floatval($ad['price'] ?? 0) ?>)"
                                                            style="padding: 4px 12px; background: #6366f1; color: #fff; border: none; border-radius: 4px; font-size: 12px; font-weight: 500; cursor: pointer;">
                                                        수정
                                                    </button>
                                                <?php endif; ?>
                                            </div>
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
                                <a href="?<?php echo http_build_query(array_merge($paginationParams, ['page' => $prevGroupLastPage])); ?>">이전</a>
                            <?php endif; ?>
                            
                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span class="current"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?<?php echo http_build_query(array_merge($paginationParams, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($nextGroupFirstPage <= $totalPages): ?>
                                <a href="?<?php echo http_build_query(array_merge($paginationParams, ['page' => $nextGroupFirstPage])); ?>">다음</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- 상태 수정 모달 -->
<div id="statusEditModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; padding: 32px; width: 90%; max-width: 500px; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h2 style="margin: 0; font-size: 20px; font-weight: 600;">광고 상태 수정</h2>
            <button type="button" onclick="closeStatusEditModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b;">&times;</button>
        </div>
        
        <div style="margin-bottom: 24px;">
            <div style="padding: 16px; background: #f8fafc; border-radius: 8px; margin-bottom: 16px;">
                <div style="font-size: 14px; color: #64748b; margin-bottom: 4px;">판매자</div>
                <div style="font-size: 16px; font-weight: 600;" id="modalSellerId"></div>
            </div>
            
            <div style="padding: 16px; background: #f8fafc; border-radius: 8px; margin-bottom: 16px;">
                <div style="font-size: 14px; color: #64748b; margin-bottom: 4px;">상품명</div>
                <div style="font-size: 16px; font-weight: 600;" id="modalProductName"></div>
            </div>
            
            <div style="padding: 16px; background: #f8fafc; border-radius: 8px; margin-bottom: 16px;">
                <div style="font-size: 14px; color: #64748b; margin-bottom: 4px;">광고 금액 (공급가액)</div>
                <div style="font-size: 16px; font-weight: 600;" id="modalPrice"></div>
            </div>
            
            <div style="padding: 16px; background: #fee2e2; border-radius: 8px; margin-bottom: 16px;">
                <div style="font-size: 14px; color: #991b1b; margin-bottom: 4px;">환불 금액 (부가세 포함)</div>
                <div style="font-size: 18px; font-weight: 700; color: #991b1b;" id="modalRefundAmount"></div>
            </div>
        </div>
        
        <div id="modalErrorMessage" style="display: none; padding: 12px; background: #fee2e2; color: #991b1b; border-radius: 6px; margin-bottom: 16px; font-size: 14px;"></div>
        
        <div style="display: flex; gap: 12px;">
            <button type="button" id="modalCancelBtn" onclick="cancelAdvertisement()"
                    style="flex: 1; padding: 12px 24px; background: #ef4444; color: #fff; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer;">
                취소 처리
            </button>
            <button type="button" onclick="closeStatusEditModal()" style="flex: 1; padding: 12px 24px; background: #f3f4f6; color: #374151; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer;">
                닫기
            </button>
        </div>
    </div>
</div>

<script>
let currentAdvertisementId = null;

function openStatusEditModal(adId, sellerId, productName, price) {
    currentAdvertisementId = adId;
    
    document.getElementById('modalSellerId').textContent = sellerId;
    document.getElementById('modalProductName').textContent = productName || '상품명 없음';
    
    // 공급가액 표시
    const supplyAmount = parseFloat(price);
    document.getElementById('modalPrice').textContent = new Intl.NumberFormat('ko-KR').format(Math.round(supplyAmount)) + '원';
    
    // 환불 금액 계산 (부가세 포함)
    const taxAmount = supplyAmount * 0.1;
    const totalRefundAmount = supplyAmount + taxAmount;
    document.getElementById('modalRefundAmount').textContent = new Intl.NumberFormat('ko-KR').format(Math.round(totalRefundAmount)) + '원';
    
    document.getElementById('modalErrorMessage').style.display = 'none';
    document.getElementById('statusEditModal').style.display = 'flex';
}

function closeStatusEditModal() {
    document.getElementById('statusEditModal').style.display = 'none';
    currentAdvertisementId = null;
}

// 모달 배경 클릭 시 닫기
document.getElementById('statusEditModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeStatusEditModal();
    }
});

async function cancelAdvertisement() {
    if (!currentAdvertisementId) {
        return;
    }
    
    const errorDiv = document.getElementById('modalErrorMessage');
    const cancelBtn = document.getElementById('modalCancelBtn');
    
    // 확인
    if (!confirm('정말로 이 광고를 취소하시겠습니까?\n취소 시 광고가 종료되고 판매자에게 환불이 처리됩니다.')) {
        return;
    }
    
    // 버튼 비활성화
    cancelBtn.disabled = true;
    cancelBtn.textContent = '처리 중...';
    errorDiv.style.display = 'none';
    
    try {
        const formData = new FormData();
        formData.append('advertisement_id', currentAdvertisementId);
        
        const response = await fetch('<?php echo getApiPath('/api/cancel-advertisement.php'); ?>', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('광고가 취소되었고 환불이 완료되었습니다.');
            window.location.reload();
        } else {
            errorDiv.textContent = data.message || '취소 처리 중 오류가 발생했습니다.';
            errorDiv.style.display = 'block';
            cancelBtn.disabled = false;
            cancelBtn.textContent = '취소 처리';
        }
    } catch (error) {
        console.error('Error:', error);
        errorDiv.textContent = '오류가 발생했습니다. 다시 시도해주세요.';
        errorDiv.style.display = 'block';
        cancelBtn.disabled = false;
        cancelBtn.textContent = '취소 처리';
    }
}
</script>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
