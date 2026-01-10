<?php
/**
 * 게시물별 통계 페이지
 */

require_once __DIR__ . '/../../includes/data/path-config.php';
$pageTitle = '게시물별 통계';
include __DIR__ . '/../includes/admin-header.php';

require_once __DIR__ . '/../../includes/data/analytics-functions.php';
require_once __DIR__ . '/../../includes/data/db-config.php';

$days = $_GET['days'] ?? 30;
$sortBy = $_GET['sort'] ?? 'favorites';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = in_array((int)($_GET['per_page'] ?? 50), [10, 50, 100, 500]) ? (int)($_GET['per_page'] ?? 50) : 50;
$offset = ($page - 1) * $perPage;

// 인기 게시물 통계 (별점/리뷰 포함)
$popularProducts = [];
$totalCount = 0;
$pdo = getDBConnection();
if ($pdo) {
    try {
        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        
        // 정렬 기준
        $orderBy = 'favorite_count DESC';
        if ($sortBy === 'applications') {
            $orderBy = 'application_count DESC';
        } elseif ($sortBy === 'shares') {
            $orderBy = 'share_count DESC';
        } elseif ($sortBy === 'reviews') {
            $orderBy = 'review_count DESC';
        } elseif ($sortBy === 'views') {
            $orderBy = 'view_count DESC';
        }
        
        // 총 개수 조회
        $countStmt = $pdo->query("
            SELECT COUNT(*) as total
            FROM products p
            LEFT JOIN product_mvno_details mvno ON p.id = mvno.product_id AND p.product_type = 'mvno'
            LEFT JOIN product_mno_details mno ON p.id = mno.product_id AND p.product_type = 'mno'
            LEFT JOIN product_mno_sim_details mno_sim ON p.id = mno_sim.product_id AND p.product_type = 'mno-sim'
            LEFT JOIN product_internet_details i ON p.id = i.product_id AND p.product_type = 'internet'
            WHERE p.status != 'deleted'
            AND (
                (p.product_type = 'mvno' AND mvno.product_id IS NOT NULL) OR
                (p.product_type = 'mno' AND mno.product_id IS NOT NULL) OR
                (p.product_type = 'mno-sim' AND mno_sim.product_id IS NOT NULL) OR
                (p.product_type = 'internet' AND i.product_id IS NOT NULL)
            )
        ");
        $totalCount = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // 상품 목록 조회 (판매자 정보 포함)
        $stmt = $pdo->query("
            SELECT 
                p.id,
                p.product_type as type,
                p.seller_id,
                p.view_count as views,
                p.favorite_count as favorites,
                p.share_count as shares,
                p.application_count as applications,
                p.review_count as reviews,
                CASE p.product_type
                    WHEN 'mvno' THEN mvno.plan_name
                    WHEN 'mno' THEN mno.device_name
                    WHEN 'mno-sim' THEN mno_sim.plan_name
                    WHEN 'internet' THEN i.registration_place
                END AS name,
                u.user_id as seller_user_id,
                COALESCE(u.seller_name, u.name, u.user_id) as seller_name,
                u.company_name as seller_company_name
            FROM products p
            LEFT JOIN product_mvno_details mvno ON p.id = mvno.product_id AND p.product_type = 'mvno'
            LEFT JOIN product_mno_details mno ON p.id = mno.product_id AND p.product_type = 'mno'
            LEFT JOIN product_mno_sim_details mno_sim ON p.id = mno_sim.product_id AND p.product_type = 'mno-sim'
            LEFT JOIN product_internet_details i ON p.id = i.product_id AND p.product_type = 'internet'
            LEFT JOIN users u ON p.seller_id = u.user_id AND u.role = 'seller'
            WHERE p.status != 'deleted'
            AND (
                (p.product_type = 'mvno' AND mvno.product_id IS NOT NULL) OR
                (p.product_type = 'mno' AND mno.product_id IS NOT NULL) OR
                (p.product_type = 'mno-sim' AND mno_sim.product_id IS NOT NULL) OR
                (p.product_type = 'internet' AND i.product_id IS NOT NULL)
            )
            ORDER BY {$orderBy}
            LIMIT {$perPage} OFFSET {$offset}
        ");
        
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($products as $product) {
            // 평균 별점 계산 (MVNO, MNO, MNO-SIM만)
            $avgRating = 0;
            if (in_array($product['type'], ['mvno', 'mno', 'mno-sim'])) {
                $ratingStmt = $pdo->prepare("
                    SELECT AVG(rating) as avg_rating
                    FROM product_reviews
                    WHERE product_id = :product_id AND status = 'approved'
                ");
                $ratingStmt->execute([':product_id' => $product['id']]);
                $ratingResult = $ratingStmt->fetch(PDO::FETCH_ASSOC);
                if ($ratingResult && $ratingResult['avg_rating']) {
                    $avgRating = round((float)$ratingResult['avg_rating'], 1);
                }
            }
            
            // 상품 상세 페이지 URL 생성
            $detailUrl = '#';
            if ($product['type'] === 'mvno') {
                $detailUrl = getAssetPath('/mvno/mvno-plan-detail.php?id=' . $product['id']);
            } elseif ($product['type'] === 'mno') {
                $detailUrl = getAssetPath('/mno/mno-phone-detail.php?id=' . $product['id']);
            } elseif ($product['type'] === 'mno-sim') {
                $detailUrl = getAssetPath('/mno-sim/mno-sim-detail.php?id=' . $product['id']);
            } elseif ($product['type'] === 'internet') {
                $detailUrl = getAssetPath('/internets/internet-detail.php?id=' . $product['id']);
            }
            
            $popularProducts[] = [
                'id' => $product['id'],
                'type' => $product['type'],
                'name' => $product['name'] ?? '',
                'seller_id' => $product['seller_user_id'] ?? ($product['seller_id'] ?? '-'),
                'seller_name' => $product['seller_name'] ?? '-',
                'seller_company_name' => $product['seller_company_name'] ?? '-',
                'favorites' => (int)$product['favorites'],
                'applications' => (int)$product['applications'],
                'shares' => (int)$product['shares'],
                'reviews' => (int)$product['reviews'],
                'views' => (int)$product['views'],
                'average_rating' => $avgRating,
                'detail_url' => $detailUrl
            ];
        }
    } catch (PDOException $e) {
        error_log("getPopularProductsWithReviews error: " . $e->getMessage());
        $popularProducts = [];
    }
}

$totalPages = $totalCount > 0 ? ceil($totalCount / $perPage) : 1;

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
    
    .badge-mno-sim {
        background: #fef3c7;
        color: #92400e;
    }
    
    .badge-internet {
        background: #dcfce7;
        color: #166534;
    }
    
    .pagination-btn:hover {
        background: #f3f4f6 !important;
        border-color: #9ca3af !important;
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
    <select class="filter-select" onchange="updateUrlParam('days', this.value)">
        <option value="7" <?php echo $days == 7 ? 'selected' : ''; ?>>최근 7일</option>
        <option value="30" <?php echo $days == 30 ? 'selected' : ''; ?>>최근 30일</option>
        <option value="90" <?php echo $days == 90 ? 'selected' : ''; ?>>최근 90일</option>
    </select>
    
    <label style="font-size: 14px; color: #374151; font-weight: 500; margin-left: 16px;">정렬:</label>
    <select class="filter-select" onchange="updateUrlParam('sort', this.value)">
        <?php foreach ($sortOptions as $key => $label): ?>
            <option value="<?php echo $key; ?>" <?php echo $sortBy === $key ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($label); ?>
            </option>
        <?php endforeach; ?>
    </select>
    
    <label style="font-size: 14px; color: #374151; font-weight: 500; margin-left: 16px;">표시 개수:</label>
    <select class="filter-select" onchange="updateUrlParam('per_page', this.value)">
        <option value="10" <?php echo $perPage == 10 ? 'selected' : ''; ?>>10개</option>
        <option value="50" <?php echo $perPage == 50 ? 'selected' : ''; ?>>50개</option>
        <option value="100" <?php echo $perPage == 100 ? 'selected' : ''; ?>>100개</option>
        <option value="500" <?php echo $perPage == 500 ? 'selected' : ''; ?>>500개</option>
    </select>
</div>

<div class="stats-section">
    <h2 class="section-title">게시물별 통계 (<?php echo $sortOptions[$sortBy]; ?> 순)</h2>
    <table>
        <thead>
            <tr>
                <th>순위</th>
                <th>타입</th>
                <th>판매자 ID</th>
                <th>판매자명</th>
                <th>상품명</th>
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
            $rank = $offset + 1;
            foreach ($popularProducts as $product):
                $conversionRate = $product['views'] > 0 
                    ? round(($product['applications'] / $product['views']) * 100, 2) 
                    : 0;
                $badgeClass = 'badge-' . $product['type'];
                $typeNames = [
                    'mvno' => '알뜰폰',
                    'mno' => '통신사폰',
                    'mno-sim' => '통신사단독유심',
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
                    <td><?php echo htmlspecialchars($product['seller_id']); ?></td>
                    <td><?php echo htmlspecialchars($product['seller_name']); ?></td>
                    <td><?php echo htmlspecialchars($product['name'] ?: '-'); ?></td>
                    <td>
                        <a href="<?php echo htmlspecialchars($product['detail_url']); ?>" class="product-link" target="_blank">
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
                    <td colspan="13" style="text-align: center; padding: 40px; color: #9ca3af;">
                        데이터가 없습니다.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- 페이지네이션 -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination-container" style="margin-top: 24px; display: flex; justify-content: center; align-items: center; gap: 8px;">
        <?php
        $queryParams = [
            'days' => $days,
            'sort' => $sortBy,
            'per_page' => $perPage
        ];
        
        // 첫 페이지
        if ($page > 1):
            $queryParams['page'] = 1;
            $firstUrl = '?' . http_build_query($queryParams);
        ?>
            <a href="<?php echo htmlspecialchars($firstUrl); ?>" class="pagination-btn" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; text-decoration: none; color: #374151; background: white;">« 처음</a>
        <?php endif; ?>
        
        <?php
        // 이전 페이지
        if ($page > 1):
            $queryParams['page'] = $page - 1;
            $prevUrl = '?' . http_build_query($queryParams);
        ?>
            <a href="<?php echo htmlspecialchars($prevUrl); ?>" class="pagination-btn" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; text-decoration: none; color: #374151; background: white;">‹ 이전</a>
        <?php endif; ?>
        
        <?php
        // 페이지 번호 (현재 페이지 기준 ±2)
        $startPage = max(1, $page - 2);
        $endPage = min($totalPages, $page + 2);
        
        for ($i = $startPage; $i <= $endPage; $i++):
            $queryParams['page'] = $i;
            $pageUrl = '?' . http_build_query($queryParams);
            $isActive = $i === $page;
        ?>
            <a href="<?php echo htmlspecialchars($pageUrl); ?>" 
               class="pagination-btn" 
               style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; text-decoration: none; color: <?php echo $isActive ? 'white' : '#374151'; ?>; background: <?php echo $isActive ? '#6366f1' : 'white'; ?>; font-weight: <?php echo $isActive ? '600' : 'normal'; ?>;">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
        
        <?php
        // 다음 페이지
        if ($page < $totalPages):
            $queryParams['page'] = $page + 1;
            $nextUrl = '?' . http_build_query($queryParams);
        ?>
            <a href="<?php echo htmlspecialchars($nextUrl); ?>" class="pagination-btn" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; text-decoration: none; color: #374151; background: white;">다음 ›</a>
        <?php endif; ?>
        
        <?php
        // 마지막 페이지
        if ($page < $totalPages):
            $queryParams['page'] = $totalPages;
            $lastUrl = '?' . http_build_query($queryParams);
        ?>
            <a href="<?php echo htmlspecialchars($lastUrl); ?>" class="pagination-btn" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; text-decoration: none; color: #374151; background: white;">마지막 »</a>
        <?php endif; ?>
    </div>
    
    <div style="text-align: center; margin-top: 12px; color: #6b7280; font-size: 14px;">
        전체 <?php echo number_format($totalCount); ?>개 중 <?php echo number_format($offset + 1); ?>-<?php echo number_format(min($offset + $perPage, $totalCount)); ?>개 표시 (페이지 <?php echo $page; ?>/<?php echo $totalPages; ?>)
    </div>
    <?php endif; ?>
</div>

<script>
function updateUrlParam(key, value) {
    const url = new URL(window.location);
    url.searchParams.set(key, value);
    // 페이지 변경 시 첫 페이지로 리셋
    if (key !== 'page') {
        url.searchParams.set('page', '1');
    }
    window.location.href = url.toString();
}
</script>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>

