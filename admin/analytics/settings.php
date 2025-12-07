<?php
/**
 * 통계 설정 관리 페이지
 */

$pageTitle = '통계 설정';
include __DIR__ . '/../includes/admin-header.php';

require_once __DIR__ . '/../../includes/data/analytics-functions.php';

$message = '';
$messageType = '';

// 설정 저장
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $settings = [
        'max_pageviews' => intval($_POST['max_pageviews'] ?? 10000),
        'max_events' => intval($_POST['max_events'] ?? 10000),
        'max_sessions' => intval($_POST['max_sessions'] ?? 10000),
        'auto_cleanup_years' => intval($_POST['auto_cleanup_years'] ?? 2),
        'auto_cleanup_enabled' => isset($_POST['auto_cleanup_enabled'])
    ];
    
    // 유효성 검사
    if ($settings['max_pageviews'] < 1000 || $settings['max_pageviews'] > 1000000) {
        $message = '페이지뷰 최대 개수는 1,000 ~ 1,000,000 사이여야 합니다.';
        $messageType = 'error';
    } elseif ($settings['max_events'] < 1000 || $settings['max_events'] > 1000000) {
        $message = '이벤트 최대 개수는 1,000 ~ 1,000,000 사이여야 합니다.';
        $messageType = 'error';
    } elseif ($settings['max_sessions'] < 1000 || $settings['max_sessions'] > 1000000) {
        $message = '세션 최대 개수는 1,000 ~ 1,000,000 사이여야 합니다.';
        $messageType = 'error';
    } elseif ($settings['auto_cleanup_years'] < 1 || $settings['auto_cleanup_years'] > 10) {
        $message = '자동 정리 연수는 1 ~ 10년 사이여야 합니다.';
        $messageType = 'error';
    } else {
        saveAnalyticsSettings($settings);
        $message = '설정이 저장되었습니다.';
        $messageType = 'success';
    }
}

// 현재 설정 읽기
$settings = getAnalyticsSettings();

// 현재 데이터 현황
$data = getAnalyticsData();
$currentPageviews = count($data['pageviews'] ?? []);
$currentEvents = count($data['events'] ?? []);
$currentSessions = count($data['session_data'] ?? []);

// 사이트 규모 분석
function analyzeSiteScale($pageviews) {
    // 최근 7일 평균 페이지뷰 계산
    $recentPageviews = array_slice($pageviews, -1000); // 최근 1000개만 분석
    $dates = [];
    foreach ($recentPageviews as $pv) {
        $date = $pv['date'] ?? date('Y-m-d', strtotime($pv['timestamp']));
        if (!isset($dates[$date])) {
            $dates[$date] = 0;
        }
        $dates[$date]++;
    }
    
    if (empty($dates)) {
        return [
            'scale' => 'unknown',
            'daily_avg' => 0,
            'description' => '데이터가 없습니다.'
        ];
    }
    
    $dailyAvg = array_sum($dates) / count($dates);
    
    if ($dailyAvg < 100) {
        return [
            'scale' => 'small',
            'daily_avg' => round($dailyAvg),
            'description' => '소규모 사이트',
            'color' => '#10b981'
        ];
    } elseif ($dailyAvg < 1000) {
        return [
            'scale' => 'medium',
            'daily_avg' => round($dailyAvg),
            'description' => '중소규모 사이트',
            'color' => '#3b82f6'
        ];
    } elseif ($dailyAvg < 5000) {
        return [
            'scale' => 'large',
            'daily_avg' => round($dailyAvg),
            'description' => '중규모 사이트',
            'color' => '#f59e0b'
        ];
    } else {
        return [
            'scale' => 'very_large',
            'daily_avg' => round($dailyAvg),
            'description' => '대규모 사이트',
            'color' => '#ef4444'
        ];
    }
}

$siteScale = analyzeSiteScale($data['pageviews'] ?? []);

// 보관 기간 계산
function calculateRetentionDays($maxCount, $dailyAvg) {
    if ($dailyAvg <= 0) return 0;
    return round($maxCount / $dailyAvg, 1);
}

$pageviewsRetention = calculateRetentionDays($settings['max_pageviews'], $siteScale['daily_avg']);
$eventsRetention = calculateRetentionDays($settings['max_events'], $siteScale['daily_avg']);
$sessionsRetention = calculateRetentionDays($settings['max_sessions'], $siteScale['daily_avg']);
?>

<style>
    .alert {
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 24px;
    }
    
    .alert-success {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #10b981;
    }
    
    .alert-error {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #ef4444;
    }
    
    .settings-section {
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
    
    .site-scale-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
    }
    
    .site-scale-title {
        font-size: 14px;
        opacity: 0.9;
        margin-bottom: 8px;
    }
    
    .site-scale-value {
        font-size: 32px;
        font-weight: 700;
        margin-bottom: 4px;
    }
    
    .site-scale-description {
        font-size: 16px;
        opacity: 0.95;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    
    .stat-item {
        background: #f9fafb;
        border-radius: 8px;
        padding: 16px;
    }
    
    .stat-label {
        font-size: 13px;
        color: #6b7280;
        margin-bottom: 8px;
    }
    
    .stat-value {
        font-size: 24px;
        font-weight: 700;
        color: #1f2937;
    }
    
    .stat-info {
        font-size: 11px;
        color: #9ca3af;
        margin-top: 4px;
    }
    
    .form-group {
        margin-bottom: 24px;
    }
    
    .form-label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }
    
    .form-input {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
    }
    
    .form-input:focus {
        outline: none;
        border-color: #6366f1;
    }
    
    .form-help {
        font-size: 13px;
        color: #6b7280;
        margin-top: 4px;
    }
    
    .form-checkbox {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .form-checkbox input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
        accent-color: #6366f1;
    }
    
    .form-checkbox label {
        font-size: 14px;
        color: #374151;
        cursor: pointer;
    }
    
    .retention-info {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 8px;
        padding: 12px;
        margin-top: 8px;
        font-size: 13px;
        color: #1e3a8a;
    }
    
    .btn {
        padding: 10px 20px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        border: none;
        transition: all 0.2s;
    }
    
    .btn-primary {
        background: #6366f1;
        color: white;
    }
    
    .btn-primary:hover {
        background: #4f46e5;
    }
    
    .preset-buttons {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 8px;
    }
    
    .preset-btn {
        padding: 6px 12px;
        background: #f3f4f6;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .preset-btn:hover {
        background: #e5e7eb;
    }
</style>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<!-- 사이트 규모 분석 -->
<div class="site-scale-card">
    <div class="site-scale-title">현재 사이트 규모</div>
    <div class="site-scale-value"><?php echo number_format($siteScale['daily_avg']); ?> 페이지뷰/일</div>
    <div class="site-scale-description"><?php echo htmlspecialchars($siteScale['description']); ?></div>
</div>

<!-- 현재 데이터 현황 -->
<div class="settings-section">
    <h2 class="section-title">현재 데이터 현황</h2>
    <div class="stats-grid">
        <div class="stat-item">
            <div class="stat-label">페이지뷰</div>
            <div class="stat-value"><?php echo number_format($currentPageviews); ?></div>
            <div class="stat-info">최대: <?php echo number_format($settings['max_pageviews']); ?>개</div>
        </div>
        <div class="stat-item">
            <div class="stat-label">이벤트</div>
            <div class="stat-value"><?php echo number_format($currentEvents); ?></div>
            <div class="stat-info">최대: <?php echo number_format($settings['max_events']); ?>개</div>
        </div>
        <div class="stat-item">
            <div class="stat-label">세션</div>
            <div class="stat-value"><?php echo number_format($currentSessions); ?></div>
            <div class="stat-info">최대: <?php echo number_format($settings['max_sessions']); ?>개</div>
        </div>
    </div>
</div>

<!-- 데이터 보관 설정 -->
<div class="settings-section">
    <h2 class="section-title">데이터 보관 설정</h2>
    <form method="POST">
        <!-- 페이지뷰 최대 개수 -->
        <div class="form-group">
            <label class="form-label">페이지뷰 최대 보관 개수</label>
            <input 
                type="number" 
                name="max_pageviews" 
                class="form-input" 
                value="<?php echo htmlspecialchars($settings['max_pageviews']); ?>"
                min="1000" 
                max="1000000" 
                required
            >
            <div class="form-help">
                최대 보관할 페이지뷰 개수입니다. (1,000 ~ 1,000,000)
            </div>
            <div class="preset-buttons">
                <button type="button" class="preset-btn" onclick="document.querySelector('[name=max_pageviews]').value=10000">10,000개</button>
                <button type="button" class="preset-btn" onclick="document.querySelector('[name=max_pageviews]').value=50000">50,000개</button>
                <button type="button" class="preset-btn" onclick="document.querySelector('[name=max_pageviews]').value=100000">100,000개</button>
                <button type="button" class="preset-btn" onclick="document.querySelector('[name=max_pageviews]').value=500000">500,000개</button>
            </div>
            <?php if ($siteScale['daily_avg'] > 0): ?>
                <div class="retention-info">
                    현재 설정으로 약 <strong><?php echo $pageviewsRetention; ?>일</strong>간 데이터를 보관할 수 있습니다.
                    (일평균 <?php echo number_format($siteScale['daily_avg']); ?> 페이지뷰 기준)
                </div>
            <?php endif; ?>
        </div>
        
        <!-- 이벤트 최대 개수 -->
        <div class="form-group">
            <label class="form-label">이벤트 최대 보관 개수</label>
            <input 
                type="number" 
                name="max_events" 
                class="form-input" 
                value="<?php echo htmlspecialchars($settings['max_events']); ?>"
                min="1000" 
                max="1000000" 
                required
            >
            <div class="form-help">
                최대 보관할 이벤트 개수입니다. (1,000 ~ 1,000,000)
            </div>
            <div class="preset-buttons">
                <button type="button" class="preset-btn" onclick="document.querySelector('[name=max_events]').value=10000">10,000개</button>
                <button type="button" class="preset-btn" onclick="document.querySelector('[name=max_events]').value=50000">50,000개</button>
                <button type="button" class="preset-btn" onclick="document.querySelector('[name=max_events]').value=100000">100,000개</button>
            </div>
        </div>
        
        <!-- 세션 최대 개수 -->
        <div class="form-group">
            <label class="form-label">세션 최대 보관 개수</label>
            <input 
                type="number" 
                name="max_sessions" 
                class="form-input" 
                value="<?php echo htmlspecialchars($settings['max_sessions']); ?>"
                min="1000" 
                max="1000000" 
                required
            >
            <div class="form-help">
                최대 보관할 세션 개수입니다. (1,000 ~ 1,000,000)
            </div>
            <div class="preset-buttons">
                <button type="button" class="preset-btn" onclick="document.querySelector('[name=max_sessions]').value=10000">10,000개</button>
                <button type="button" class="preset-btn" onclick="document.querySelector('[name=max_sessions]').value=50000">50,000개</button>
                <button type="button" class="preset-btn" onclick="document.querySelector('[name=max_sessions]').value=100000">100,000개</button>
            </div>
        </div>
        
        <!-- 자동 정리 설정 -->
        <div class="form-group">
            <label class="form-label">자동 정리 설정</label>
            <div class="form-checkbox">
                <input 
                    type="checkbox" 
                    id="auto_cleanup_enabled" 
                    name="auto_cleanup_enabled" 
                    <?php echo ($settings['auto_cleanup_enabled'] ?? true) ? 'checked' : ''; ?>
                >
                <label for="auto_cleanup_enabled">자동 정리 활성화</label>
            </div>
            <div class="form-help" style="margin-top: 8px;">
                활성화 시 지정한 연수 이상 된 데이터를 자동으로 삭제합니다.
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">자동 정리 연수</label>
            <input 
                type="number" 
                name="auto_cleanup_years" 
                class="form-input" 
                value="<?php echo htmlspecialchars($settings['auto_cleanup_years']); ?>"
                min="1" 
                max="10" 
                required
            >
            <div class="form-help">
                몇 년 이상 된 데이터를 자동으로 삭제할지 설정합니다. (1 ~ 10년)
            </div>
        </div>
        
        <div style="display: flex; gap: 12px;">
            <button type="submit" name="save_settings" class="btn btn-primary">
                설정 저장
            </button>
        </div>
    </form>
</div>

<!-- 가이드 -->
<div class="settings-section">
    <h2 class="section-title">보관 개수 가이드</h2>
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #f9fafb;">
                <th style="padding: 12px; text-align: left; font-size: 14px; color: #374151;">일일 페이지뷰</th>
                <th style="padding: 12px; text-align: left; font-size: 14px; color: #374151;">권장 보관 개수</th>
                <th style="padding: 12px; text-align: left; font-size: 14px; color: #374151;">보관 기간</th>
            </tr>
        </thead>
        <tbody>
            <tr style="border-bottom: 1px solid #e5e7eb;">
                <td style="padding: 12px; font-size: 14px;">~ 100개</td>
                <td style="padding: 12px; font-size: 14px;">10,000개</td>
                <td style="padding: 12px; font-size: 14px;">약 100일 (3개월)</td>
            </tr>
            <tr style="border-bottom: 1px solid #e5e7eb;">
                <td style="padding: 12px; font-size: 14px;">100 ~ 500개</td>
                <td style="padding: 12px; font-size: 14px;">50,000개</td>
                <td style="padding: 12px; font-size: 14px;">약 100일 (3개월)</td>
            </tr>
            <tr style="border-bottom: 1px solid #e5e7eb;">
                <td style="padding: 12px; font-size: 14px;">500 ~ 1,000개</td>
                <td style="padding: 12px; font-size: 14px;">100,000개</td>
                <td style="padding: 12px; font-size: 14px;">약 100일 (3개월)</td>
            </tr>
            <tr style="border-bottom: 1px solid #e5e7eb;">
                <td style="padding: 12px; font-size: 14px;">1,000 ~ 5,000개</td>
                <td style="padding: 12px; font-size: 14px;">500,000개</td>
                <td style="padding: 12px; font-size: 14px;">약 100일 (3개월)</td>
            </tr>
            <tr>
                <td style="padding: 12px; font-size: 14px;">5,000개 이상</td>
                <td style="padding: 12px; font-size: 14px;">1,000,000개</td>
                <td style="padding: 12px; font-size: 14px;">약 200일 (6개월)</td>
            </tr>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>










