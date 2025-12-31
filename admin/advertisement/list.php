<?php
/**
 * 광고 목록 페이지 (관리자)
 * 경로: /admin/advertisement/list.php
 */

require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../../includes/data/db-config.php';
require_once __DIR__ . '/../../includes/data/auth-functions.php';

$pdo = getDBConnection();

if (!$pdo) {
    die('데이터베이스 연결에 실패했습니다.');
}

// 필터 처리
$categoryFilter = $_GET['category'] ?? '';
$statusFilter = $_GET['status'] ?? '';

// 광고 목록 조회
$whereConditions = [];
$params = [];

if ($categoryFilter && in_array($categoryFilter, ['mvno', 'mno', 'internet', 'mno_sim'])) {
    $whereConditions[] = "ra.product_type = :product_type";
    $params[':product_type'] = $categoryFilter;
}

if ($statusFilter && in_array($statusFilter, ['active', 'expired', 'cancelled'])) {
    $whereConditions[] = "ra.status = :status";
    $params[':status'] = $statusFilter;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

$stmt = $pdo->prepare("
    SELECT 
        ra.*,
        p.status as product_status
    FROM rotation_advertisements ra
    LEFT JOIN products p ON ra.product_id = p.id
    $whereClause
    ORDER BY ra.created_at DESC
");

$stmt->execute($params);
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
?>

<div class="admin-content-wrapper">
    <div class="admin-content">
        <div class="page-header">
            <h1>광고 목록</h1>
            <p>신청된 광고 목록을 조회하고 관리합니다.</p>
        </div>
        
        <div class="content-box">
            <div style="padding: 24px;">
                <!-- 필터 -->
                <div style="margin-bottom: 24px;">
                    <form method="GET" style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap;">
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
                                    <th style="padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0;">신청일시</th>
                                    <th style="padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0;">판매자</th>
                                    <th style="padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0;">상품ID</th>
                                    <th style="padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0;">카테고리</th>
                                    <th style="padding: 12px; text-align: center; font-weight: 600; border-bottom: 2px solid #e2e8f0;">기간</th>
                                    <th style="padding: 12px; text-align: right; font-weight: 600; border-bottom: 2px solid #e2e8f0;">금액</th>
                                    <th style="padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0;">기간</th>
                                    <th style="padding: 12px; text-align: center; font-weight: 600; border-bottom: 2px solid #e2e8f0;">상태</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($advertisements as $ad): ?>
                                    <?php
                                    $statusInfo = $statusLabels[$ad['status']] ?? ['label' => $ad['status'], 'color' => '#64748b'];
                                    $now = new DateTime();
                                    $startDate = new DateTime($ad['start_datetime']);
                                    $endDate = new DateTime($ad['end_datetime']);
                                    $isProductActive = ($ad['product_status'] ?? 'inactive') === 'active';
                                    $isAdRunning = $ad['status'] === 'active' && $endDate > $now;
                                    ?>
                                    <tr style="border-bottom: 1px solid #e2e8f0;">
                                        <td style="padding: 12px;">
                                            <?= date('Y-m-d H:i', strtotime($ad['created_at'])) ?>
                                        </td>
                                        <td style="padding: 12px; font-weight: 500;"><?= htmlspecialchars($ad['seller_id']) ?></td>
                                        <td style="padding: 12px;"><?= $ad['product_id'] ?></td>
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
                                            <?php if ($isAdRunning && !$isProductActive): ?>
                                                <div style="font-size: 11px; color: #f59e0b; margin-top: 4px;">(상품 판매종료)</div>
                                            <?php endif; ?>
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
