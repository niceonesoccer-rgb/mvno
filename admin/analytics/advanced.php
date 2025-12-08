<?php
/**
 * 고급 분석 페이지
 */

$pageTitle = '고급 분석';
include __DIR__ . '/../includes/admin-header.php';

require_once __DIR__ . '/../../includes/data/analytics-functions.php';

// 분석 데이터 가져오기
$days = $_GET['days'] ?? 7;
$userPaths = getUserPaths(20);
$bounceRate = getBounceRate($days);
$avgSessionTime = getAverageSessionTime($days);
$pageBounceRates = getPageBounceRates($days);
?>

<style>
    .analysis-section {
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
    
    .metric-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 24px;
    }
    
    .metric-card {
        background: #f9fafb;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
    }
    
    .metric-value {
        font-size: 32px;
        font-weight: 700;
        color: #6366f1;
        margin-bottom: 8px;
    }
    
    .metric-label {
        font-size: 14px;
        color: #6b7280;
    }
    
    .bounce-rate-indicator {
        display: inline-block;
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        margin-top: 8px;
    }
    
    .bounce-rate-low {
        background: #d1fae5;
        color: #065f46;
    }
    
    .bounce-rate-medium {
        background: #fef3c7;
        color: #92400e;
    }
    
    .bounce-rate-high {
        background: #fee2e2;
        color: #991b1b;
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
    
    .path-item {
        font-family: 'Courier New', monospace;
        font-size: 13px;
        color: #374151;
    }
    
    .path-arrow {
        color: #9ca3af;
        margin: 0 8px;
    }
    
    .filter-bar {
        display: flex;
        gap: 12px;
        align-items: center;
        margin-bottom: 24px;
    }
    
    .filter-select {
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        background: white;
    }
</style>

<div class="filter-bar">
    <label for="daysFilter" style="font-size: 14px; color: #374151; font-weight: 500;">기간:</label>
    <select id="daysFilter" class="filter-select" onchange="window.location.href='?days=' + this.value">
        <option value="7" <?php echo $days == 7 ? 'selected' : ''; ?>>최근 7일</option>
        <option value="30" <?php echo $days == 30 ? 'selected' : ''; ?>>최근 30일</option>
        <option value="90" <?php echo $days == 90 ? 'selected' : ''; ?>>최근 90일</option>
    </select>
</div>

<!-- 주요 지표 -->
<div class="analysis-section">
    <h2 class="section-title">주요 지표</h2>
    <div class="metric-grid">
        <div class="metric-card">
            <div class="metric-value"><?php echo number_format($bounceRate, 1); ?>%</div>
            <div class="metric-label">이탈률</div>
            <div class="bounce-rate-indicator <?php 
                echo $bounceRate < 40 ? 'bounce-rate-low' : ($bounceRate < 70 ? 'bounce-rate-medium' : 'bounce-rate-high');
            ?>">
                <?php 
                echo $bounceRate < 40 ? '좋음' : ($bounceRate < 70 ? '보통' : '높음');
                ?>
            </div>
        </div>
        
        <div class="metric-card">
            <div class="metric-value"><?php echo formatSessionTime($avgSessionTime); ?></div>
            <div class="metric-label">평균 세션 시간</div>
        </div>
    </div>
</div>

<!-- 사용자 경로 분석 -->
<div class="analysis-section">
    <h2 class="section-title">사용자 경로 분석</h2>
    <p style="font-size: 14px; color: #6b7280; margin-bottom: 16px;">
        사용자들이 어떤 경로로 사이트를 탐색하는지 보여줍니다.
    </p>
    <table>
        <thead>
            <tr>
                <th>순위</th>
                <th>경로</th>
                <th>세션 수</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $rank = 1;
            foreach ($userPaths as $pathKey => $pathData):
            ?>
                <tr>
                    <td><?php echo $rank++; ?></td>
                    <td>
                        <div class="path-item">
                            <?php
                            $pathDisplay = [];
                            foreach ($pathData['path'] as $page) {
                                $pathDisplay[] = htmlspecialchars(basename($page) ?: '/');
                            }
                            echo implode('<span class="path-arrow">→</span>', $pathDisplay);
                            ?>
                        </div>
                    </td>
                    <td><?php echo number_format($pathData['count']); ?></td>
                </tr>
            <?php endforeach; ?>
            
            <?php if (empty($userPaths)): ?>
                <tr>
                    <td colspan="3" style="text-align: center; padding: 40px; color: #9ca3af;">
                        데이터가 없습니다.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- 페이지별 이탈률 -->
<div class="analysis-section">
    <h2 class="section-title">페이지별 이탈률</h2>
    <p style="font-size: 14px; color: #6b7280; margin-bottom: 16px;">
        각 페이지에서 사용자가 한 페이지만 보고 떠나는 비율입니다.
    </p>
    <table>
        <thead>
            <tr>
                <th>페이지</th>
                <th>입장 수</th>
                <th>이탈 수</th>
                <th>이탈률</th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach (array_slice($pageBounceRates, 0, 20) as $pageData):
                $bounceClass = $pageData['bounce_rate'] < 40 ? 'bounce-rate-low' : 
                              ($pageData['bounce_rate'] < 70 ? 'bounce-rate-medium' : 'bounce-rate-high');
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($pageData['page']); ?></td>
                    <td><?php echo number_format($pageData['total_entrances']); ?></td>
                    <td><?php echo number_format($pageData['bounces']); ?></td>
                    <td>
                        <span class="bounce-rate-indicator <?php echo $bounceClass; ?>">
                            <?php echo number_format($pageData['bounce_rate'], 1); ?>%
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
            
            <?php if (empty($pageBounceRates)): ?>
                <tr>
                    <td colspan="4" style="text-align: center; padding: 40px; color: #9ca3af;">
                        데이터가 없습니다.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>















