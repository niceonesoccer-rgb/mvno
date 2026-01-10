<?php
/**
 * 실시간 통계 페이지
 */

require_once __DIR__ . '/../../includes/data/path-config.php';
$pageTitle = '실시간 통계';
include __DIR__ . '/../includes/admin-header.php';

require_once __DIR__ . '/../../includes/data/analytics-functions.php';
?>

<style>
    .realtime-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 32px;
    }
    
    .realtime-card {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        position: relative;
        overflow: hidden;
    }
    
    .realtime-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #6366f1, #8b5cf6);
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
    
    .realtime-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }
    
    .realtime-card-title {
        font-size: 14px;
        color: #6b7280;
        font-weight: 500;
    }
    
    .realtime-badge {
        background: #10b981;
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        animation: blink 1s infinite;
    }
    
    @keyframes blink {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }
    
    .realtime-card-value {
        font-size: 36px;
        font-weight: 700;
        color: #1f2937;
    }
    
    .realtime-card-subtitle {
        font-size: 13px;
        color: #9ca3af;
        margin-top: 8px;
    }
    
    .realtime-list {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        margin-bottom: 24px;
    }
    
    .realtime-list-title {
        font-size: 18px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 16px;
    }
    
    .realtime-item {
        padding: 12px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .realtime-item:last-child {
        border-bottom: none;
    }
    
    .realtime-item-info {
        flex: 1;
    }
    
    .realtime-item-page {
        font-size: 14px;
        color: #374151;
        font-weight: 500;
    }
    
    .realtime-item-time {
        font-size: 12px;
        color: #9ca3af;
        margin-top: 4px;
    }
    
    .realtime-item-badge {
        background: #dbeafe;
        color: #1e40af;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 500;
    }
    
    .auto-refresh-indicator {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: #6366f1;
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        font-size: 13px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 1000;
    }
</style>

<!-- 실시간 통계 카드 -->
<div class="realtime-stats" id="realtimeStats">
    <div class="realtime-card">
        <div class="realtime-card-header">
            <div class="realtime-card-title">현재 접속자</div>
            <span class="realtime-badge">실시간</span>
        </div>
        <div class="realtime-card-value" id="activeUsers">-</div>
        <div class="realtime-card-subtitle">5분 이내 활동 사용자</div>
    </div>
    
    <div class="realtime-card">
        <div class="realtime-card-header">
            <div class="realtime-card-title">실시간 페이지뷰</div>
            <span class="realtime-badge">실시간</span>
        </div>
        <div class="realtime-card-value" id="realtimePageviews">-</div>
        <div class="realtime-card-subtitle">최근 1분간 조회수</div>
    </div>
    
    <div class="realtime-card">
        <div class="realtime-card-header">
            <div class="realtime-card-title">오늘 페이지뷰</div>
        </div>
        <div class="realtime-card-value" id="todayPageviews">-</div>
        <div class="realtime-card-subtitle">오늘 전체 조회수</div>
    </div>
    
    <div class="realtime-card">
        <div class="realtime-card-header">
            <div class="realtime-card-title">오늘 방문자</div>
        </div>
        <div class="realtime-card-value" id="todayVisitors">-</div>
        <div class="realtime-card-subtitle">오늘 유니크 방문자</div>
    </div>
</div>

<!-- 실시간 페이지뷰 목록 -->
<div class="realtime-list">
    <h2 class="realtime-list-title">실시간 페이지뷰 (최근 1분)</h2>
    <div id="realtimePageviewList">
        <div style="text-align: center; padding: 40px; color: #9ca3af;">
            로딩 중...
        </div>
    </div>
</div>

<div class="auto-refresh-indicator" id="refreshIndicator">
    🔄 자동 갱신 중... (<span id="refreshCountdown">5</span>초)
</div>

<script>
    let refreshCountdown = 5;
    let refreshInterval;
    
    function updateRealtimeStats() {
        fetch('<?php echo getApiPath('/api/analytics/realtime.php'); ?>')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('activeUsers').textContent = data.active_users || 0;
                    document.getElementById('realtimePageviews').textContent = data.realtime_pageviews || 0;
                    document.getElementById('todayPageviews').textContent = data.today_pageviews || 0;
                    document.getElementById('todayVisitors').textContent = data.today_visitors || 0;
                    
                    // 실시간 페이지뷰 목록 업데이트
                    const listContainer = document.getElementById('realtimePageviewList');
                    if (data.realtime_pageviews_list && data.realtime_pageviews_list.length > 0) {
                        listContainer.innerHTML = data.realtime_pageviews_list.map(item => `
                            <div class="realtime-item">
                                <div class="realtime-item-info">
                                    <div class="realtime-item-page">${escapeHtml(item.page)}</div>
                                    <div class="realtime-item-time">${item.time_ago}</div>
                                </div>
                                <span class="realtime-item-badge">${item.timestamp}</span>
                            </div>
                        `).join('');
                    } else {
                        listContainer.innerHTML = '<div style="text-align: center; padding: 40px; color: #9ca3af;">최근 페이지뷰가 없습니다.</div>';
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }
    
    function startRefreshCountdown() {
        refreshCountdown = 5;
        const countdownElement = document.getElementById('refreshCountdown');
        
        const countdownInterval = setInterval(() => {
            refreshCountdown--;
            if (countdownElement) {
                countdownElement.textContent = refreshCountdown;
            }
            
            if (refreshCountdown <= 0) {
                clearInterval(countdownInterval);
                updateRealtimeStats();
                startRefreshCountdown();
            }
        }, 1000);
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // 초기 로드
    updateRealtimeStats();
    startRefreshCountdown();
    
    // 5초마다 자동 갱신
    setInterval(updateRealtimeStats, 5000);
</script>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>


















