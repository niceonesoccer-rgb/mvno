<?php
/**
 * 판매자 통계 페이지
 * 경로: /MVNO/seller/statistics/
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/db-config.php';
require_once __DIR__ . '/../../includes/data/seller-statistics-functions.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUser = getCurrentUser();

// 판매자 로그인 체크
if (!$currentUser || $currentUser['role'] !== 'seller') {
    header('Location: /MVNO/seller/login.php');
    exit;
}

// 판매자 승인 체크
$isApproved = isset($currentUser['seller_approved']) && $currentUser['seller_approved'] === true;
if (!$isApproved) {
    header('Location: /MVNO/seller/waiting.php');
    exit;
}

$sellerId = (string)$currentUser['user_id'];
$days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = intval($_GET['per_page'] ?? 20);
// 허용된 per_page 값만 사용 (10, 20, 50, 100)
if (!in_array($perPage, [10, 20, 50, 100])) {
    $perPage = 20;
}

// 통계 데이터 조회
$statistics = getSellerStatistics($sellerId, $days);
$typeStatistics = getSellerStatisticsByType($sellerId);

// 카테고리 필터
$filterType = $_GET['filter_type'] ?? 'all';
$validTypes = ['all', 'mvno', 'mno', 'mno-sim', 'internet'];
if (!in_array($filterType, $validTypes)) {
    $filterType = 'all';
}

// 필터링된 상품 목록
$filteredProducts = $statistics['products'] ?? [];
if ($filterType !== 'all') {
    $filteredProducts = array_filter($filteredProducts, function($product) use ($filterType) {
        return $product['type'] === $filterType;
    });
    // array_filter는 인덱스를 유지하므로 array_values로 재인덱싱
    $filteredProducts = array_values($filteredProducts);
}

// 각 카테고리별 순서 계산 (역순)
// 먼저 각 타입별 총 개수 계산
$typeCounts = [];
foreach ($filteredProducts as $product) {
    $productType = $product['type'];
    if (!isset($typeCounts[$productType])) {
        $typeCounts[$productType] = 0;
    }
    $typeCounts[$productType]++;
}

// 각 타입별 순서 카운터 (역순을 위해)
$typeOrderCounters = [];
foreach ($filteredProducts as &$product) {
    $productType = $product['type'];
    if (!isset($typeOrderCounters[$productType])) {
        $typeOrderCounters[$productType] = $typeCounts[$productType]; // 총 개수부터 시작
    }
    $product['type_order'] = $typeOrderCounters[$productType];
    $typeOrderCounters[$productType]--; // 역순으로 감소
}
unset($product); // 참조 해제

// 페이지네이션을 위한 상품 목록 처리
$totalProducts = count($filteredProducts);
$totalPages = ceil($totalProducts / $perPage);
$offset = ($page - 1) * $perPage;
$paginatedProducts = array_slice($filteredProducts, $offset, $perPage);

// 현재 페이지 설정
$current_page = 'statistics';
$is_main_page = false;

// 페이지별 스타일
$pageStyles = '
    .statistics-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 40px 24px;
    }
    
    .statistics-header {
        margin-bottom: 32px;
    }
    
    .statistics-header h1 {
        font-size: 36px;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 8px;
        letter-spacing: -0.5px;
    }
    
    .statistics-header p {
        font-size: 16px;
        color: #64748b;
        font-weight: 500;
    }
    
    .filter-bar {
        display: flex;
        gap: 12px;
        align-items: center;
        margin-bottom: 32px;
        background: white;
        padding: 16px 24px;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    
    .filter-label {
        font-size: 14px;
        color: #374151;
        font-weight: 600;
    }
    
    .filter-select {
        padding: 8px 16px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        background: white;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .filter-select:hover {
        border-color: #6366f1;
    }
    
    .filter-select:focus {
        outline: none;
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }
    
    .stats-section {
        background: white;
        border-radius: 16px;
        padding: 32px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        margin-bottom: 24px;
    }
    
    .section-title {
        font-size: 24px;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    
    .section-title::before {
        content: "";
        width: 4px;
        height: 24px;
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        border-radius: 2px;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 24px;
    }
    
    .stat-card {
        background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
        border-radius: 12px;
        padding: 24px;
        border: 1px solid #e2e8f0;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .stat-card::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #6366f1 0%, #8b5cf6 100%);
    }
    
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    }
    
    .stat-card.primary::before {
        background: linear-gradient(90deg, #6366f1 0%, #8b5cf6 100%);
    }
    
    .stat-card.success::before {
        background: linear-gradient(90deg, #10b981 0%, #059669 100%);
    }
    
    .stat-card.warning::before {
        background: linear-gradient(90deg, #f59e0b 0%, #d97706 100%);
    }
    
    .stat-card.info::before {
        background: linear-gradient(90deg, #3b82f6 0%, #2563eb 100%);
    }
    
    .stat-label {
        font-size: 14px;
        color: #64748b;
        margin-bottom: 8px;
        font-weight: 500;
    }
    
    .stat-value {
        font-size: 32px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1;
        margin-bottom: 4px;
    }
    
    .stat-subvalue {
        font-size: 12px;
        color: #94a3b8;
        font-weight: 500;
    }
    
    .type-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
    }
    
    .type-stat-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border-radius: 12px;
        padding: 24px;
        border: 1px solid #e2e8f0;
    }
    
    .type-stat-title {
        font-size: 18px;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 2px solid #e2e8f0;
    }
    
    .type-stat-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    
    .type-stat-item:last-child {
        border-bottom: none;
    }
    
    .type-stat-label {
        font-size: 14px;
        color: #64748b;
        font-weight: 500;
    }
    
    .type-stat-value {
        font-size: 16px;
        font-weight: 700;
        color: #0f172a;
    }
    
    .products-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 16px;
    }
    
    .products-table th {
        background: #f8fafc;
        padding: 12px 16px;
        text-align: left;
        font-weight: 600;
        font-size: 14px;
        color: #374151;
        border-bottom: 2px solid #e2e8f0;
    }
    
    .products-table td {
        padding: 16px;
        font-size: 14px;
        color: #1f2937;
        border-bottom: 1px solid #e2e8f0;
    }
    
    .products-table tr:hover {
        background: #f8fafc;
    }
    
    .product-type-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        text-align: center;
    }
    
    .product-type-badge.mvno {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .product-type-badge.mno {
        background: #fce7f3;
        color: #9f1239;
    }
    
    .product-type-badge.mno-sim {
        background: #e9d5ff;
        color: #6b21a8;
    }
    
    .product-type-badge.internet {
        background: #dcfce7;
        color: #14532d;
    }
    
    .product-status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .product-status-badge.active {
        background: #d1fae5;
        color: #065f46;
    }
    
    .product-status-badge.inactive {
        background: #fef3c7;
        color: #92400e;
    }
    
    .rating-display {
        color: #f59e0b;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .number-display {
        font-weight: 600;
        color: #6366f1;
    }
    
    .no-data {
        text-align: center;
        padding: 60px 20px;
        color: #94a3b8;
    }
    
    .no-data-icon {
        font-size: 48px;
        margin-bottom: 16px;
        opacity: 0.5;
    }
    
    .no-data-text {
        font-size: 16px;
        font-weight: 500;
    }
    
    .pagination {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-top: 24px;
        padding: 20px;
    }
    
    .pagination-btn {
        padding: 8px 16px;
        font-size: 14px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        background: white;
        color: #374151;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.2s;
    }
    
    .pagination-btn:hover:not(.disabled):not(.active) {
        background: #f9fafb;
        border-color: #6366f1;
        color: #6366f1;
    }
    
    .pagination-btn.active {
        background: #6366f1;
        color: white;
        border-color: #6366f1;
        font-weight: 600;
    }
    
    .pagination-btn.disabled {
        opacity: 0.5;
        cursor: not-allowed;
        pointer-events: none;
    }
';

include '../includes/seller-header.php';
?>

<div class="statistics-container">
    <div class="statistics-header">
        <h1>통계</h1>
        <p>상품 및 주문 통계를 확인할 수 있습니다.</p>
    </div>
    
    <!-- 기간 필터 -->
    <div class="filter-bar">
        <label class="filter-label">조회 기간:</label>
        <select class="filter-select" id="filter_days" onchange="updateFilters()">
            <option value="7" <?php echo $days == 7 ? 'selected' : ''; ?>>최근 7일</option>
            <option value="30" <?php echo $days == 30 ? 'selected' : ''; ?>>최근 30일</option>
            <option value="90" <?php echo $days == 90 ? 'selected' : ''; ?>>최근 90일</option>
            <option value="365" <?php echo $days == 365 ? 'selected' : ''; ?>>최근 1년</option>
        </select>
        
        <label class="filter-label" style="margin-left: 24px;">카테고리:</label>
        <select class="filter-select" id="filter_type" onchange="updateFilters()">
            <option value="all" <?php echo $filterType == 'all' ? 'selected' : ''; ?>>전체</option>
            <option value="mvno" <?php echo $filterType == 'mvno' ? 'selected' : ''; ?>>알뜰폰</option>
            <option value="mno" <?php echo $filterType == 'mno' ? 'selected' : ''; ?>>통신사폰</option>
            <option value="mno-sim" <?php echo $filterType == 'mno-sim' ? 'selected' : ''; ?>>통신사단독유심</option>
            <option value="internet" <?php echo $filterType == 'internet' ? 'selected' : ''; ?>>인터넷</option>
        </select>
        
    </div>
    
    <!-- 전체 통계 -->
    <div class="stats-section">
        <h2 class="section-title">전체 통계</h2>
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-label">등록 상품</div>
                <div class="stat-value"><?php echo number_format($statistics['total_products'] ?? 0); ?></div>
                <div class="stat-subvalue">판매 중: <?php echo number_format($statistics['active_products'] ?? 0); ?>개</div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-label">찜 개수</div>
                <div class="stat-value"><?php echo number_format($statistics['total_favorites'] ?? 0); ?></div>
                <div class="stat-subvalue">최근 <?php echo $days; ?>일: <?php echo number_format($statistics['period']['favorites'] ?? 0); ?></div>
            </div>
            
            <div class="stat-card info">
                <div class="stat-label">신청 수</div>
                <div class="stat-value"><?php echo number_format($statistics['total_applications'] ?? 0); ?></div>
                <div class="stat-subvalue">최근 <?php echo $days; ?>일: <?php echo number_format($statistics['period']['applications'] ?? 0); ?></div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-label">공유 수</div>
                <div class="stat-value"><?php echo number_format($statistics['total_shares'] ?? 0); ?></div>
                <div class="stat-subvalue">최근 <?php echo $days; ?>일: <?php echo number_format($statistics['period']['shares'] ?? 0); ?></div>
            </div>
            
            <div class="stat-card primary">
                <div class="stat-label">조회 수</div>
                <div class="stat-value"><?php echo number_format($statistics['total_views'] ?? 0); ?></div>
                <div class="stat-subvalue">전체 상품 합계</div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-label">리뷰 수</div>
                <div class="stat-value"><?php echo number_format($statistics['total_reviews'] ?? 0); ?></div>
                <div class="stat-subvalue">
                    <?php if (($statistics['average_rating'] ?? 0) > 0): ?>
                        평균 별점: ⭐ <?php echo number_format($statistics['average_rating'], 1); ?>
                    <?php else: ?>
                        최근 <?php echo $days; ?>일: <?php echo number_format($statistics['period']['reviews'] ?? 0); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 상품 타입별 통계 -->
    <div class="stats-section">
        <h2 class="section-title">상품 타입별 통계</h2>
        <div class="type-stats-grid">
            <?php
            $typeNames = [
                'mvno' => '알뜰폰',
                'mno' => '통신사폰',
                'mno-sim' => '통신사단독유심',
                'internet' => '인터넷'
            ];
            
            foreach (['mvno', 'mno', 'mno-sim', 'internet'] as $type):
                $typeStat = $typeStatistics[$type] ?? [];
            ?>
            <div class="type-stat-card">
                <div class="type-stat-title"><?php echo $typeNames[$type]; ?></div>
                <div class="type-stat-item">
                    <span class="type-stat-label">상품 수</span>
                    <span class="type-stat-value"><?php echo number_format($typeStat['count'] ?? 0); ?>개</span>
                </div>
                <div class="type-stat-item">
                    <span class="type-stat-label">조회 수</span>
                    <span class="type-stat-value"><?php echo number_format($typeStat['views'] ?? 0); ?></span>
                </div>
                <div class="type-stat-item">
                    <span class="type-stat-label">찜 개수</span>
                    <span class="type-stat-value"><?php echo number_format($typeStat['favorites'] ?? 0); ?></span>
                </div>
                <div class="type-stat-item">
                    <span class="type-stat-label">신청 수</span>
                    <span class="type-stat-value"><?php echo number_format($typeStat['applications'] ?? 0); ?></span>
                </div>
                <div class="type-stat-item">
                    <span class="type-stat-label">공유 수</span>
                    <span class="type-stat-value"><?php echo number_format($typeStat['shares'] ?? 0); ?></span>
                </div>
                <?php if (in_array($type, ['mvno', 'mno'])): ?>
                <div class="type-stat-item">
                    <span class="type-stat-label">리뷰 수</span>
                    <span class="type-stat-value"><?php echo number_format($typeStat['reviews'] ?? 0); ?></span>
                </div>
                <div class="type-stat-item">
                    <span class="type-stat-label">평균 별점</span>
                    <span class="type-stat-value">
                        <?php if (($typeStat['average_rating'] ?? 0) > 0): ?>
                            <span class="rating-display">⭐ <?php echo number_format($typeStat['average_rating'], 1); ?></span>
                        <?php else: ?>
                            <span style="color: #94a3b8;">-</span>
                        <?php endif; ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- 상품별 상세 통계 -->
    <div class="stats-section">
        <h2 class="section-title">
            <span>상품별 상세 통계 (총 <?php echo number_format($totalProducts); ?>개)</span>
            <div style="display: flex; align-items: center; gap: 8px; margin-left: auto;">
                <label style="font-size: 14px; font-weight: 600; color: #64748b;">표시 개수:</label>
                <select class="filter-select" id="filter_per_page_bottom" onchange="updateFilters()" style="padding: 6px 12px; font-size: 14px;">
                    <option value="10" <?php echo $perPage == 10 ? 'selected' : ''; ?>>10개</option>
                    <option value="20" <?php echo $perPage == 20 ? 'selected' : ''; ?>>20개</option>
                    <option value="50" <?php echo $perPage == 50 ? 'selected' : ''; ?>>50개</option>
                    <option value="100" <?php echo $perPage == 100 ? 'selected' : ''; ?>>100개</option>
                </select>
            </div>
        </h2>
        <?php if (!empty($paginatedProducts)): ?>
        <div style="overflow-x: auto;">
            <table class="products-table">
                <thead>
                    <tr>
                        <th>순서</th>
                        <th>타입</th>
                        <th>상품명</th>
                        <th>상태</th>
                        <th>조회</th>
                        <th>찜</th>
                        <th>신청</th>
                        <th>공유</th>
                        <th>리뷰</th>
                        <th>별점</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // 타입 이름 정의 (상품 테이블용)
                    $typeNames = [
                        'mvno' => '알뜰폰',
                        'mno' => '통신사폰',
                        'mno-sim' => '통신사단독유심',
                        'internet' => '인터넷'
                    ];
                    foreach ($paginatedProducts as $product): ?>
                    <tr>
                        <td><?php echo $product['type_order'] ?? '-'; ?></td>
                        <td>
                            <span class="product-type-badge <?php echo htmlspecialchars($product['type']); ?>">
                                <?php echo $typeNames[$product['type']] ?? $product['type']; ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            // 상품 타입별 수정 페이지 URL
                            $editUrls = [
                                'mvno' => '/MVNO/seller/products/mvno.php?id=' . $product['id'],
                                'mno' => '/MVNO/seller/products/mno.php?id=' . $product['id'],
                                'mno-sim' => '/MVNO/seller/products/mno-sim.php?id=' . $product['id'],
                                'internet' => '/MVNO/seller/products/internet.php?id=' . $product['id']
                            ];
                            $editUrl = $editUrls[$product['type']] ?? '#';
                            ?>
                            <a href="<?php echo htmlspecialchars($editUrl); ?>" style="color: #6366f1; text-decoration: none; font-weight: 600;">
                                <strong><?php echo htmlspecialchars($product['name'] ?: '상품명 없음'); ?></strong>
                            </a>
                        </td>
                        <td>
                            <span class="product-status-badge <?php echo htmlspecialchars($product['status']); ?>">
                                <?php 
                                echo $product['status'] === 'active' ? '판매 중' : '판매 종료';
                                ?>
                            </span>
                        </td>
                        <td><span class="number-display"><?php echo number_format($product['views']); ?></span></td>
                        <td><span class="number-display"><?php echo number_format($product['favorites']); ?></span></td>
                        <td><span class="number-display"><?php echo number_format($product['applications']); ?></span></td>
                        <td><span class="number-display"><?php echo number_format($product['shares']); ?></span></td>
                        <td><span class="number-display"><?php echo number_format($product['reviews']); ?></span></td>
                        <td>
                            <?php if (in_array($product['type'], ['mvno', 'mno', 'mno-sim']) && ($product['average_rating'] ?? 0) > 0): ?>
                                <span class="rating-display">⭐ <?php echo number_format($product['average_rating'], 1); ?></span>
                            <?php else: ?>
                                <span style="color: #94a3b8;">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="no-data">
            <div class="no-data-icon">📊</div>
            <div class="no-data-text">등록된 상품이 없습니다.</div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function updateFilters() {
    const days = document.getElementById('filter_days').value;
    const filterType = document.getElementById('filter_type').value;
    // 하단의 표시 개수 선택 드롭다운에서 값 가져오기
    const perPageBottom = document.getElementById('filter_per_page_bottom');
    const perPage = perPageBottom ? perPageBottom.value : '20';
    
    const params = new URLSearchParams();
    params.set('days', days);
    params.set('filter_type', filterType);
    params.set('per_page', perPage);
    params.set('page', '1'); // 필터 변경 시 첫 페이지로
    
    window.location.href = '?' + params.toString();
}
</script>

<?php include '../includes/seller-footer.php'; ?>
