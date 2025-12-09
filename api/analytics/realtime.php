<?php
/**
 * 실시간 통계 API
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/data/analytics-functions.php';

$activeUsers = getCurrentActiveUsers();
$realtimePageviews = getRealTimePageviews(1);
$todayStats = getTodayStats();

// 실시간 페이지뷰 목록 포맷팅
$realtimePageviewsList = [];
foreach (array_reverse(array_slice($realtimePageviews, -20)) as $pageview) {
    $timestamp = strtotime($pageview['timestamp']);
    $timeAgo = time() - $timestamp;
    
    if ($timeAgo < 60) {
        $timeAgoText = $timeAgo . '초 전';
    } else {
        $timeAgoText = floor($timeAgo / 60) . '분 전';
    }
    
    $realtimePageviewsList[] = [
        'page' => $pageview['page'],
        'timestamp' => date('H:i:s', $timestamp),
        'time_ago' => $timeAgoText
    ];
}

echo json_encode([
    'success' => true,
    'active_users' => $activeUsers,
    'realtime_pageviews' => count($realtimePageviews),
    'today_pageviews' => $todayStats['pageviews'],
    'today_visitors' => $todayStats['unique_visitors'],
    'realtime_pageviews_list' => $realtimePageviewsList
]);

















