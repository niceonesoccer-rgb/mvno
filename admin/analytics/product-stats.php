<?php
/**
 * 게시물별 통계 페이지
 */

$pageTitle = '게시물별 통계';
include __DIR__ . '/../includes/admin-header.php';

require_once __DIR__ . '/../../includes/data/analytics-functions.php';

$days = $_GET['days'] ?? 30;
$sortBy = $_GET['sort'] ?? 'favorites';

// 인기 게시물 통계 (별점/리뷰 포함)
$popularProducts = getPopularProductsWithReviews($sortBy, 50, $days);

// 정렬 옵션
$sortOptions = [
    'favorites' => '찜 개수',
    'applications' => '신청 수',
    'shares' => '공유 수',
    'reviews' => '리뷰 수',
    'views' => '조회 수'
];
?>

<style>
    .filter-bar {
        display: flex;
        gap: 12px;
        align-items: center;
        margin-bottom: 24px;
        background: white;
        padding: 16px;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    
    .filter-select {
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        background: white;
    }
    
    .stats-section {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        margin-bottom: 24px;
    }
    
    .section-title {
        font-size: 18px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 16px;
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
    }
    
    th, td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
    }
    
    th {
        background: #f9fafb;
        font-weight: 600;
        color: #374151;
        font-size: 14px;
    }
    
    td {
        font-size: 14px;
        color: #1f2937;
    }
    
    .badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .badge-mvno {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .badge-mno {
        background: #fce7f3;
        color: #9f1239;
    }
    
    .badge-internet {
        background: #dcfce7;
        color: #166534;
    }
    
    .stat-number {
        font-weight: 600;
        color: #6366f1;
    }
    
    .product-link {
        color: #6366f1;
        text-decoration: none;
    }
    
    .product-link:hover {
        text-decoration: underline;
    }
</style>

<div class="filter-bar">
    <label style="font-size: 14px; color: #374151; font-weight: 500;">기간:</label>
    <select class="filter-select" onchange="window.location.href='?days=' + this.value + '&sort=<?php echo $sortBy; ?>'">
        <option value="7" <?php echo $days == 7 ? 'selected' : ''; ?>>최근 7일</option>
        <option value="30" <?php echo $days == 30 ? 'selected' : ''; ?>>최근 30일</option>
        <option value="90" <?php echo $days == 90 ? 'selected' : ''; ?>>최근 90일</option>
    </select>
    
    <label style="font-size: 14px; color: #374151; font-weight: 500; margin-left: 16px;">정렬:</label>
    <select class="filter-select" onchange="window.location.href='?days=<?php echo $days; ?>&sort=' + this.value">
        <?php foreach ($sortOptions as $key => $label): ?>
            <option value="<?php echo $key; ?>" <?php echo $sortBy === $key ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($label); ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

<div class="stats-section">
    <h2 class="section-title">게시물별 통계 (<?php echo $sortOptions[$sortBy]; ?> 순)</h2>
    <table>
        <thead>
            <tr>
                <th>순위</th>
                <th>타입</th>
                <th>상품 ID</th>
                <th>찜</th>
                <th>신청</th>
                <th>공유</th>
                <th>리뷰</th>
                <th>별점</th>
                <th>조회</th>
                <th>전환율</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $rank = 1;
            foreach ($popularProducts as $product):
                $conversionRate = $product['views'] > 0 
                    ? round(($product['applications'] / $product['views']) * 100, 2) 
                    : 0;
                $badgeClass = 'badge-' . $product['type'];
                $typeNames = [
                    'mvno' => '알뜰폰',
                    'mno' => '통신사폰',
                    'internet' => '인터넷'
                ];
            ?>
                <tr>
                    <td><?php echo $rank++; ?></td>
                    <td>
                        <span class="badge <?php echo $badgeClass; ?>">
                            <?php echo $typeNames[$product['type']] ?? $product['type']; ?>
                        </span>
                    </td>
                    <td>
                        <a href="#" class="product-link" onclick="showProductDetail('<?php echo htmlspecialchars($product['type']); ?>', '<?php echo htmlspecialchars($product['id']); ?>'); return false;">
                            <?php echo htmlspecialchars($product['id']); ?>
                        </a>
                    </td>
                    <td><span class="stat-number"><?php echo number_format($product['favorites']); ?></span></td>
                    <td><span class="stat-number"><?php echo number_format($product['applications']); ?></span></td>
                    <td><span class="stat-number"><?php echo number_format($product['shares']); ?></span></td>
                    <td><span class="stat-number"><?php echo number_format($product['reviews'] ?? 0); ?></span></td>
                    <td>
                        <?php if (isset($product['average_rating']) && $product['average_rating'] > 0): ?>
                            <span style="color: #f59e0b; font-weight: 600;">⭐ <?php echo number_format($product['average_rating'], 1); ?></span>
                        <?php else: ?>
                            <span style="color: #9ca3af;">-</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo number_format($product['views']); ?></td>
                    <td><?php echo $conversionRate; ?>%</td>
                </tr>
            <?php endforeach; ?>
            
            <?php if (empty($popularProducts)): ?>
                <tr>
                    <td colspan="10" style="text-align: center; padding: 40px; color: #9ca3af;">
                        데이터가 없습니다.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function showProductDetail(type, id) {
    // 상품 상세 정보 모달 표시 (나중에 구현)
    showAlert('상품 상세: ' + type + ' - ' + id, '상품 정보');
}
</script>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>

