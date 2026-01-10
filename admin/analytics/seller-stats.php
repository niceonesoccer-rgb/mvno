<?php
/**
 * 판매자별 통계 페이지
 */

require_once __DIR__ . '/../../includes/data/path-config.php';
$pageTitle = '판매자별 통계';
include __DIR__ . '/../includes/admin-header.php';

require_once __DIR__ . '/../../includes/data/analytics-functions.php';
require_once __DIR__ . '/../../includes/data/seller-statistics-functions.php';
require_once __DIR__ . '/../../includes/data/auth-functions.php';

$days = $_GET['days'] ?? 30;
$sellerId = $_GET['seller_id'] ?? null;

// 판매자별 통계 (별점/리뷰 포함)
if ($sellerId) {
    $sellerStatsData = getSellerStatistics($sellerId, $days);
    // admin 페이지 형식에 맞게 변환
    $sellerStats = [
        'favorites' => $sellerStatsData['total_favorites'] ?? 0,
        'applications' => $sellerStatsData['total_applications'] ?? 0,
        'shares' => $sellerStatsData['total_shares'] ?? 0,
        'views' => $sellerStatsData['total_views'] ?? 0,
        'reviews' => $sellerStatsData['total_reviews'] ?? 0,
        'average_rating' => $sellerStatsData['average_rating'] ?? 0,
        'products' => $sellerStatsData['products'] ?? []
    ];
    $currentSeller = getUserById($sellerId);
} else {
    // 전체 판매자 통계
    $pdo = getDBConnection();
    $allSellersStats = [];
    if ($pdo) {
        try {
            $startDate = date('Y-m-d', strtotime("-{$days} days"));
            $stmt = $pdo->query("
                SELECT DISTINCT seller_id 
                FROM products 
                WHERE status != 'deleted' 
                AND seller_id IS NOT NULL
            ");
            $sellerIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($sellerIds as $sid) {
                $stats = getSellerStatistics($sid, $days);
                $seller = getUserById($sid);
                $allSellersStats[] = [
                    'seller_id' => $sid,
                    'seller_name' => $seller['name'] ?? $sid,
                    'favorites' => $stats['total_favorites'] ?? 0,
                    'applications' => $stats['total_applications'] ?? 0,
                    'shares' => $stats['total_shares'] ?? 0,
                    'views' => $stats['total_views'] ?? 0,
                    'reviews' => $stats['total_reviews'] ?? 0,
                    'average_rating' => $stats['average_rating'] ?? 0,
                    'products_count' => $stats['total_products'] ?? 0
                ];
            }
            
            // 찜 개수 순으로 정렬
            usort($allSellersStats, function($a, $b) {
                return $b['favorites'] - $a['favorites'];
            });
        } catch (PDOException $e) {
            error_log("getAllSellersStats error: " . $e->getMessage());
            $allSellersStats = [];
        }
    }
}
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
    
    .seller-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
    }
    
    .seller-name {
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 8px;
    }
    
    .seller-id {
        font-size: 14px;
        opacity: 0.9;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 24px;
    }
    
    .stat-card {
        background: #f9fafb;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
    }
    
    .stat-label {
        font-size: 13px;
        color: #6b7280;
        margin-bottom: 8px;
    }
    
    .stat-value {
        font-size: 32px;
        font-weight: 700;
        color: #6366f1;
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
    
    .stat-number {
        font-weight: 600;
        color: #6366f1;
    }
    
    .seller-link {
        color: #6366f1;
        text-decoration: none;
    }
    
    .seller-link:hover {
        text-decoration: underline;
    }
</style>

<div class="filter-bar">
    <label style="font-size: 14px; color: #374151; font-weight: 500;">기간:</label>
    <select class="filter-select" onchange="window.location.href='?days=' + this.value<?php echo $sellerId ? ' + \'&seller_id=' . $sellerId . '\'' : ''; ?>'">
        <option value="7" <?php echo $days == 7 ? 'selected' : ''; ?>>최근 7일</option>
        <option value="30" <?php echo $days == 30 ? 'selected' : ''; ?>>최근 30일</option>
        <option value="90" <?php echo $days == 90 ? 'selected' : ''; ?>>최근 90일</option>
    </select>
    
    <?php if ($sellerId): ?>
        <a href="<?php echo getAssetPath('/admin/analytics/seller-stats.php'); ?>?days=<?php echo $days; ?>" style="margin-left: auto; padding: 8px 16px; background: #f3f4f6; color: #374151; border-radius: 6px; text-decoration: none; font-size: 14px;">
            전체 판매자 보기
        </a>
    <?php endif; ?>
</div>

<?php if ($sellerId && $currentSeller): ?>
    <!-- 특정 판매자 상세 통계 -->
    <div class="seller-header">
        <div class="seller-name"><?php echo htmlspecialchars($currentSeller['name'] ?? $sellerId); ?></div>
        <div class="seller-id">아이디: <?php echo htmlspecialchars($sellerId); ?></div>
    </div>
    
    <div class="stats-section">
        <h2 class="section-title">전체 통계</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">찜 개수</div>
                <div class="stat-value"><?php echo number_format($sellerStats['favorites']); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">신청 수</div>
                <div class="stat-value"><?php echo number_format($sellerStats['applications']); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">공유 수</div>
                <div class="stat-value"><?php echo number_format($sellerStats['shares']); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">조회 수</div>
                <div class="stat-value"><?php echo number_format($sellerStats['views']); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">리뷰 수</div>
                <div class="stat-value"><?php echo number_format($sellerStats['reviews'] ?? 0); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">평균 별점</div>
                <div class="stat-value">
                    <?php if (isset($sellerStats['average_rating']) && $sellerStats['average_rating'] > 0): ?>
                        ⭐ <?php echo number_format($sellerStats['average_rating'], 1); ?>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="stats-section">
        <h2 class="section-title">상품별 통계</h2>
        <table>
            <thead>
                <tr>
                    <th>타입</th>
                    <th>상품 ID</th>
                    <th>찜</th>
                    <th>신청</th>
                    <th>공유</th>
                    <th>리뷰</th>
                    <th>별점</th>
                    <th>조회</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $typeNames = [
                    'mvno' => '알뜰폰',
                    'mno' => '통신사폰',
                    'internet' => '인터넷'
                ];
                
                foreach ($sellerStats['products'] as $product):
                ?>
                    <tr>
                        <td><?php echo $typeNames[$product['type']] ?? $product['type']; ?></td>
                        <td><?php echo htmlspecialchars($product['id']); ?></td>
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
                    </tr>
                <?php endforeach; ?>
                
                <?php if (empty($sellerStats['products'])): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px; color: #9ca3af;">
                            데이터가 없습니다.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <!-- 전체 판매자 목록 -->
    <div class="stats-section">
        <h2 class="section-title">판매자별 통계 (찜 개수 순)</h2>
        <table>
            <thead>
                <tr>
                    <th>순위</th>
                    <th>판매자</th>
                    <th>아이디</th>
                    <th>찜 개수</th>
                    <th>신청 수</th>
                    <th>공유 수</th>
                    <th>리뷰 수</th>
                    <th>평균 별점</th>
                    <th>조회 수</th>
                    <th>등록 상품</th>
                    <th>작업</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $rank = 1;
                foreach ($allSellersStats as $seller):
                ?>
                    <tr>
                        <td><?php echo $rank++; ?></td>
                        <td><?php echo htmlspecialchars($seller['seller_name']); ?></td>
                        <td><?php echo htmlspecialchars($seller['seller_id']); ?></td>
                        <td><span class="stat-number"><?php echo number_format($seller['favorites']); ?></span></td>
                        <td><span class="stat-number"><?php echo number_format($seller['applications']); ?></span></td>
                        <td><span class="stat-number"><?php echo number_format($seller['shares']); ?></span></td>
                        <td><span class="stat-number"><?php echo number_format($seller['reviews'] ?? 0); ?></span></td>
                        <td>
                            <?php if (isset($seller['average_rating']) && $seller['average_rating'] > 0): ?>
                                <span style="color: #f59e0b; font-weight: 600;">⭐ <?php echo number_format($seller['average_rating'], 1); ?></span>
                            <?php else: ?>
                                <span style="color: #9ca3af;">-</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo number_format($seller['views']); ?></td>
                        <td><?php echo number_format($seller['products_count']); ?>개</td>
                        <td>
                            <a href="<?php echo getAssetPath('/admin/analytics/seller-stats.php'); ?>?seller_id=<?php echo urlencode($seller['seller_id']); ?>&days=<?php echo $days; ?>" class="seller-link">
                                상세보기
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                
                <?php if (empty($allSellersStats)): ?>
                    <tr>
                        <td colspan="11" style="text-align: center; padding: 40px; color: #9ca3af;">
                            데이터가 없습니다.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>

