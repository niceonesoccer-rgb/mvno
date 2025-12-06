<?php
/**
 * 데이터 정리 관리 페이지
 */

$pageTitle = '데이터 정리 관리';
include __DIR__ . '/../includes/admin-header.php';

require_once __DIR__ . '/../../includes/data/analytics-functions.php';

$message = '';
$messageType = '';

// 수동 정리 실행
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cleanup_now'])) {
    $years = intval($_POST['years'] ?? 2);
    
    if ($years < 1 || $years > 10) {
        $message = '유효하지 않은 연수입니다. (1-10년)';
        $messageType = 'error';
    } else {
        $cleaned = cleanupOldAnalyticsData($years);
        
        if ($cleaned) {
            $message = "{$years}년 이상 된 데이터가 정리되었습니다.";
            $messageType = 'success';
        } else {
            $message = "정리할 데이터가 없습니다.";
            $messageType = 'info';
        }
    }
}

// 통계 데이터 크기 확인
$data = getAnalyticsData();
$pageviewsCount = count($data['pageviews'] ?? []);
$eventsCount = count($data['events'] ?? []);
$sessionsCount = count($data['session_data'] ?? []);
$dailyStatsCount = count($data['daily_stats'] ?? []);

// 가장 오래된 데이터 날짜 찾기
$oldestDate = null;
if (!empty($data['pageviews'])) {
    $oldestTimestamp = null;
    foreach ($data['pageviews'] as $pageview) {
        $timestamp = strtotime($pageview['timestamp']);
        if ($oldestTimestamp === null || $timestamp < $oldestTimestamp) {
            $oldestTimestamp = $timestamp;
        }
    }
    if ($oldestTimestamp) {
        $oldestDate = date('Y-m-d', $oldestTimestamp);
    }
}

// 정리 대상 데이터 확인
$cutoffDate = date('Y-m-d', strtotime('-2 years'));
$oldDataCount = 0;
if (!empty($data['pageviews'])) {
    foreach ($data['pageviews'] as $pageview) {
        if ($pageview['date'] < $cutoffDate) {
            $oldDataCount++;
        }
    }
}
?>

<style>
    .info-section {
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
    
    .alert-info {
        background: #dbeafe;
        color: #1e40af;
        border: 1px solid #3b82f6;
    }
    
    .cleanup-form {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }
    
    .form-select {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        background: white;
    }
    
    .form-help {
        font-size: 13px;
        color: #6b7280;
        margin-top: 4px;
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
    
    .btn-danger {
        background: #ef4444;
        color: white;
    }
    
    .btn-danger:hover {
        background: #dc2626;
    }
    
    .info-box {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 8px;
        padding: 16px;
        margin-bottom: 24px;
    }
    
    .info-box-title {
        font-size: 14px;
        font-weight: 600;
        color: #1e40af;
        margin-bottom: 8px;
    }
    
    .info-box-text {
        font-size: 13px;
        color: #1e3a8a;
        line-height: 1.6;
    }
</style>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<!-- 정보 박스 -->
<div class="info-box">
    <div class="info-box-title">ℹ️ 자동 정리 시스템</div>
    <div class="info-box-text">
        시스템은 자동으로 24시간마다 2년 이상 된 데이터를 정리합니다.<br>
        또한 성능 최적화를 위해 각 데이터 타입별로 최대 보관 개수가 제한됩니다.<br>
        보관 개수는 <a href="/MVNO/admin/analytics/settings.php" style="color: #6366f1; text-decoration: underline;">통계 설정</a>에서 변경할 수 있습니다.<br><br>
        필요시 아래에서 수동으로 정리할 수도 있습니다.
    </div>
</div>

<!-- 데이터 통계 -->
<div class="info-section">
    <h2 class="section-title">현재 데이터 현황</h2>
    <?php
    require_once __DIR__ . '/../../includes/data/analytics-functions.php';
    $settings = getAnalyticsSettings();
    ?>
    <div class="stats-grid">
        <div class="stat-item">
            <div class="stat-label">페이지뷰</div>
            <div class="stat-value"><?php echo number_format($pageviewsCount); ?></div>
            <div style="font-size: 11px; color: #9ca3af; margin-top: 4px;">최대 <?php echo number_format($settings['max_pageviews']); ?>개</div>
        </div>
        <div class="stat-item">
            <div class="stat-label">이벤트</div>
            <div class="stat-value"><?php echo number_format($eventsCount); ?></div>
            <div style="font-size: 11px; color: #9ca3af; margin-top: 4px;">최대 <?php echo number_format($settings['max_events']); ?>개</div>
        </div>
        <div class="stat-item">
            <div class="stat-label">세션</div>
            <div class="stat-value"><?php echo number_format($sessionsCount); ?></div>
            <div style="font-size: 11px; color: #9ca3af; margin-top: 4px;">최대 <?php echo number_format($settings['max_sessions']); ?>개</div>
        </div>
        <div class="stat-item">
            <div class="stat-label">일별 통계</div>
            <div class="stat-value"><?php echo number_format($dailyStatsCount); ?></div>
            <div style="font-size: 11px; color: #9ca3af; margin-top: 4px;"><?php echo $settings['auto_cleanup_years']; ?>년간 보관</div>
        </div>
    </div>
    
    <?php if ($oldestDate): ?>
        <div style="margin-top: 16px; padding: 12px; background: #f9fafb; border-radius: 6px;">
            <div style="font-size: 13px; color: #6b7280;">가장 오래된 데이터: <strong><?php echo htmlspecialchars($oldestDate); ?></strong></div>
            <?php if ($oldDataCount > 0): ?>
                <div style="font-size: 13px; color: #991b1b; margin-top: 4px;">
                    정리 대상 데이터: <strong><?php echo number_format($oldDataCount); ?>개</strong> (2년 이상)
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- 수동 정리 -->
<div class="cleanup-form">
    <h2 class="section-title">수동 데이터 정리</h2>
    <form method="POST">
        <div class="form-group">
            <label class="form-label">정리할 연수</label>
            <select name="years" class="form-select" required>
                <option value="1">1년 이상</option>
                <option value="2" selected>2년 이상</option>
                <option value="3">3년 이상</option>
                <option value="5">5년 이상</option>
            </select>
            <div class="form-help">
                선택한 연수 이상 된 데이터가 삭제됩니다. 이 작업은 되돌릴 수 없습니다.
            </div>
        </div>
        
        <div style="display: flex; gap: 12px;">
            <button type="submit" name="cleanup_now" class="btn btn-danger" onclick="event.preventDefault(); showConfirm('정말로 오래된 데이터를 삭제하시겠습니까? 이 작업은 되돌릴 수 없습니다.', '데이터 삭제 확인').then(result => { if(result) this.closest('form').submit(); }); return false;">
                데이터 정리 실행
            </button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>

