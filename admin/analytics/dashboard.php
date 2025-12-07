<?php
/**
 * 웹 통계 대시보드
 */

$pageTitle = '웹 통계';
include __DIR__ . '/../includes/admin-header.php';

require_once __DIR__ . '/../../includes/data/analytics-functions.php';

// 오늘의 통계
$todayStats = getTodayStats();

// 최근 7일 통계
$weekStats = getPeriodStats(date('Y-m-d', strtotime('-7 days')), date('Y-m-d'));

// 최근 30일 통계
$monthStats = getPeriodStats(date('Y-m-d', strtotime('-30 days')), date('Y-m-d'));

// 인기 페이지
$popularPages = getPopularPages(7, 10);

// 시간대별 통계
$hourlyStats = getHourlyStats();

// 상품 통계
$productStats = getProductStats(null, 7);

// 이벤트 통계
$productViews = count(getEventStats('product_view', 1));
$productApplications = count(getEventStats('product_application', 1));
?>

<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 32px;
    }
    
    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    
    .stat-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }
    
    .stat-card-title {
        font-size: 14px;
        color: #6b7280;
        font-weight: 500;
    }
    
    .stat-card-icon {
        font-size: 24px;
    }
    
    .stat-card-value {
        font-size: 32px;
        font-weight: 700;
        color: #1f2937;
    }
    
    .stat-card-change {
        font-size: 14px;
        color: #10b981;
        margin-top: 8px;
    }
    
    .chart-container {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        margin-bottom: 24px;
    }
    
    .chart-title {
        font-size: 18px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 16px;
    }
    
    .bar-chart {
        display: flex;
        align-items: flex-end;
        height: 200px;
        gap: 4px;
    }
    
    .bar-item {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    .bar {
        width: 100%;
        background: linear-gradient(to top, #6366f1, #8b5cf6);
        border-radius: 4px 4px 0 0;
        min-height: 4px;
        transition: all 0.3s;
    }
    
    .bar:hover {
        opacity: 0.8;
    }
    
    .bar-label {
        font-size: 11px;
        color: #6b7280;
        margin-top: 8px;
        text-align: center;
    }
    
    .table-container {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        margin-bottom: 24px;
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
</style>

<!-- 오늘의 통계 카드 -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-header">
            <div class="stat-card-title">오늘 방문자</div>
            <div class="stat-card-icon">👥</div>
        </div>
        <div class="stat-card-value"><?php echo number_format($todayStats['unique_visitors']); ?></div>
        <div class="stat-card-change">유니크 방문자</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-card-header">
            <div class="stat-card-title">오늘 페이지뷰</div>
            <div class="stat-card-icon">📄</div>
        </div>
        <div class="stat-card-value"><?php echo number_format($todayStats['pageviews']); ?></div>
        <div class="stat-card-change">총 조회수</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-card-header">
            <div class="stat-card-title">오늘 상품 조회</div>
            <div class="stat-card-icon">👁️</div>
        </div>
        <div class="stat-card-value"><?php echo number_format($productViews); ?></div>
        <div class="stat-card-change">상품 조회수</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-card-header">
            <div class="stat-card-title">오늘 신청</div>
            <div class="stat-card-icon">📝</div>
        </div>
        <div class="stat-card-value"><?php echo number_format($productApplications); ?></div>
        <div class="stat-card-change">상품 신청수</div>
    </div>
</div>

<!-- 최근 7일 방문자 추이 -->
<div class="chart-container">
    <h2 class="chart-title">최근 7일 방문자 추이</h2>
    <div class="bar-chart">
        <?php
        $maxPageviews = 0;
        foreach ($weekStats as $dayStats) {
            if ($dayStats['pageviews'] > $maxPageviews) {
                $maxPageviews = $dayStats['pageviews'];
            }
        }
        
        foreach ($weekStats as $dayStats):
            $date = new DateTime($dayStats['date']);
            $height = $maxPageviews > 0 ? ($dayStats['pageviews'] / $maxPageviews * 100) : 0;
        ?>
            <div class="bar-item">
                <div class="bar" style="height: <?php echo $height; ?>%;" title="<?php echo number_format($dayStats['pageviews']); ?>"></div>
                <div class="bar-label"><?php echo $date->format('m/d'); ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- 시간대별 통계 -->
<div class="chart-container">
    <h2 class="chart-title">시간대별 방문자 분포 (오늘)</h2>
    <div class="bar-chart">
        <?php
        $maxHourly = max($hourlyStats);
        foreach ($hourlyStats as $hour => $count):
            $height = $maxHourly > 0 ? ($count / $maxHourly * 100) : 0;
        ?>
            <div class="bar-item">
                <div class="bar" style="height: <?php echo $height; ?>%;" title="<?php echo number_format($count); ?>"></div>
                <div class="bar-label"><?php echo str_pad($hour, 2, '0', STR_PAD_LEFT); ?>시</div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- 인기 페이지 -->
<div class="table-container">
    <h2 class="chart-title">인기 페이지 (최근 7일)</h2>
    <table>
        <thead>
            <tr>
                <th>순위</th>
                <th>페이지</th>
                <th>조회수</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $rank = 1;
            foreach ($popularPages as $page => $count):
            ?>
                <tr>
                    <td><?php echo $rank++; ?></td>
                    <td><?php echo htmlspecialchars($page); ?></td>
                    <td><?php echo number_format($count); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- 상품 통계 -->
<div class="table-container">
    <h2 class="chart-title">상품 통계 (최근 7일)</h2>
    <table>
        <thead>
            <tr>
                <th>순위</th>
                <th>타입</th>
                <th>상품 ID</th>
                <th>조회수</th>
                <th>신청수</th>
                <th>전환율</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $rank = 1;
            foreach (array_slice($productStats, 0, 10) as $product):
                $conversionRate = $product['views'] > 0 
                    ? round(($product['applications'] / $product['views']) * 100, 2) 
                    : 0;
                $badgeClass = 'badge-' . $product['type'];
            ?>
                <tr>
                    <td><?php echo $rank++; ?></td>
                    <td>
                        <span class="badge <?php echo $badgeClass; ?>">
                            <?php 
                            $typeNames = [
                                'mvno' => '알뜰폰',
                                'mno' => '통신사폰',
                                'internet' => '인터넷'
                            ];
                            echo $typeNames[$product['type']] ?? $product['type'];
                            ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($product['id']); ?></td>
                    <td><?php echo number_format($product['views']); ?></td>
                    <td><?php echo number_format($product['applications']); ?></td>
                    <td><?php echo $conversionRate; ?>%</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>







