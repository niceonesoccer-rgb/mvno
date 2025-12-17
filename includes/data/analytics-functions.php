<?php
/**
 * 웹 통계 관련 함수
 */

// 한국 시간대 설정 (KST, UTC+9)
date_default_timezone_set('Asia/Seoul');

/**
 * 통계 데이터 파일 경로
 */
function getAnalyticsFilePath() {
    return __DIR__ . '/analytics.json';
}

/**
 * 통계 설정 파일 경로
 */
function getAnalyticsSettingsPath() {
    return __DIR__ . '/analytics-settings.json';
}

/**
 * 통계 설정 읽기
 */
function getAnalyticsSettings() {
    $file = getAnalyticsSettingsPath();
    if (!file_exists($file)) {
        return [
            'max_pageviews' => 10000,
            'max_events' => 10000,
            'max_sessions' => 10000,
            'auto_cleanup_years' => 2,
            'auto_cleanup_enabled' => true
        ];
    }
    $content = file_get_contents($file);
    return json_decode($content, true) ?: [
        'max_pageviews' => 10000,
        'max_events' => 10000,
        'max_sessions' => 10000,
        'auto_cleanup_years' => 2,
        'auto_cleanup_enabled' => true
    ];
}

/**
 * 통계 설정 저장
 */
function saveAnalyticsSettings($settings) {
    $file = getAnalyticsSettingsPath();
    file_put_contents($file, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/**
 * 통계 데이터 읽기
 */
function getAnalyticsData() {
    $file = getAnalyticsFilePath();
    if (!file_exists($file)) {
        return [
            'pageviews' => [],
            'sessions' => [],
            'events' => [],
            'daily_stats' => [],
            'active_sessions' => [],
            'session_data' => []
        ];
    }
    $content = file_get_contents($file);
    return json_decode($content, true) ?: [
        'pageviews' => [],
        'sessions' => [],
        'events' => [],
        'daily_stats' => [],
        'active_sessions' => [],
        'session_data' => []
    ];
}

/**
 * 통계 데이터 저장
 */
function saveAnalyticsData($data) {
    $file = getAnalyticsFilePath();
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/**
 * 페이지뷰 기록
 */
function trackPageView($page, $userId = null) {
    // 자동 정리 실행 (24시간마다 한 번씩)
    autoCleanupOldData();
    
    $data = getAnalyticsData();
    
    $pageview = [
        'page' => $page,
        'user_id' => $userId,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'referrer' => $_SERVER['HTTP_REFERER'] ?? '',
        'timestamp' => date('Y-m-d H:i:s'),
        'date' => date('Y-m-d'),
        'hour' => (int)date('H')
    ];
    
    $data['pageviews'][] = $pageview;
    
    // 설정에서 읽은 최대 개수만 유지 (성능 고려)
    $settings = getAnalyticsSettings();
    $maxPageviews = $settings['max_pageviews'] ?? 10000;
    if (count($data['pageviews']) > $maxPageviews) {
        $data['pageviews'] = array_slice($data['pageviews'], -$maxPageviews);
    }
    
    // 일별 통계 업데이트
    updateDailyStats($data, $pageview);
    
    saveAnalyticsData($data);
    
    return true;
}

/**
 * 이벤트 기록 (상품 조회, 신청 등)
 */
function trackEvent($eventType, $eventData = []) {
    $data = getAnalyticsData();
    
    $event = [
        'type' => $eventType,
        'data' => $eventData,
        'timestamp' => date('Y-m-d H:i:s'),
        'date' => date('Y-m-d'),
        'user_id' => getCurrentUserId()
    ];
    
    $data['events'][] = $event;
    
    // 설정에서 읽은 최대 개수만 유지 (성능 고려)
    $settings = getAnalyticsSettings();
    $maxEvents = $settings['max_events'] ?? 10000;
    if (count($data['events']) > $maxEvents) {
        $data['events'] = array_slice($data['events'], -$maxEvents);
    }
    
    saveAnalyticsData($data);
    
    return true;
}

/**
 * 일별 통계 업데이트
 */
function updateDailyStats(&$data, $pageview) {
    $date = $pageview['date'];
    
    if (!isset($data['daily_stats'][$date])) {
        $data['daily_stats'][$date] = [
            'pageviews' => 0,
            'unique_visitors' => [],
            'pages' => []
        ];
    }
    
    $data['daily_stats'][$date]['pageviews']++;
    
    // 유니크 방문자 추적 (IP 기반)
    $ip = $pageview['ip'];
    if (!in_array($ip, $data['daily_stats'][$date]['unique_visitors'])) {
        $data['daily_stats'][$date]['unique_visitors'][] = $ip;
    }
    
    // 페이지별 카운트
    $page = $pageview['page'];
    if (!isset($data['daily_stats'][$date]['pages'][$page])) {
        $data['daily_stats'][$date]['pages'][$page] = 0;
    }
    $data['daily_stats'][$date]['pages'][$page]++;
}

/**
 * 현재 사용자 ID 가져오기
 */
if (!function_exists('getCurrentUserId')) {
    function getCurrentUserId() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['user_id'] ?? null;
    }
}

/**
 * 오늘의 통계 가져오기
 */
function getTodayStats() {
    $data = getAnalyticsData();
    $today = date('Y-m-d');
    
    if (!isset($data['daily_stats'][$today])) {
        return [
            'pageviews' => 0,
            'unique_visitors' => 0,
            'pages' => []
        ];
    }
    
    $todayStats = $data['daily_stats'][$today];
    
    return [
        'pageviews' => $todayStats['pageviews'] ?? 0,
        'unique_visitors' => count($todayStats['unique_visitors'] ?? []),
        'pages' => $todayStats['pages'] ?? []
    ];
}

/**
 * 기간별 통계 가져오기
 */
function getPeriodStats($startDate, $endDate) {
    $data = getAnalyticsData();
    $stats = [];
    
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $end->modify('+1 day'); // 포함하기 위해
    
    $period = new DatePeriod($start, new DateInterval('P1D'), $end);
    
    foreach ($period as $date) {
        $dateStr = $date->format('Y-m-d');
        if (isset($data['daily_stats'][$dateStr])) {
            $dayStats = $data['daily_stats'][$dateStr];
            $stats[$dateStr] = [
                'date' => $dateStr,
                'pageviews' => $dayStats['pageviews'] ?? 0,
                'unique_visitors' => count($dayStats['unique_visitors'] ?? []),
                'pages' => $dayStats['pages'] ?? []
            ];
        } else {
            $stats[$dateStr] = [
                'date' => $dateStr,
                'pageviews' => 0,
                'unique_visitors' => 0,
                'pages' => []
            ];
        }
    }
    
    return $stats;
}

/**
 * 인기 페이지 가져오기 (기간별)
 */
function getPopularPages($days = 7, $limit = 10) {
    $data = getAnalyticsData();
    $endDate = date('Y-m-d');
    $startDate = date('Y-m-d', strtotime("-{$days} days"));
    
    $periodStats = getPeriodStats($startDate, $endDate);
    
    $pageCounts = [];
    
    foreach ($periodStats as $dayStats) {
        foreach ($dayStats['pages'] as $page => $count) {
            if (!isset($pageCounts[$page])) {
                $pageCounts[$page] = 0;
            }
            $pageCounts[$page] += $count;
        }
    }
    
    arsort($pageCounts);
    
    return array_slice($pageCounts, 0, $limit, true);
}

/**
 * 시간대별 통계 가져오기
 */
function getHourlyStats($date = null) {
    if ($date === null) {
        $date = date('Y-m-d');
    }
    
    $data = getAnalyticsData();
    $hourlyStats = array_fill(0, 24, 0);
    
    foreach ($data['pageviews'] as $pageview) {
        if ($pageview['date'] === $date) {
            $hour = (int)$pageview['hour'];
            $hourlyStats[$hour]++;
        }
    }
    
    return $hourlyStats;
}

/**
 * 이벤트 통계 가져오기
 */
function getEventStats($eventType = null, $days = 7) {
    $data = getAnalyticsData();
    $startDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
    
    $events = array_filter($data['events'], function($event) use ($startDate, $eventType) {
        if ($eventType && $event['type'] !== $eventType) {
            return false;
        }
        return $event['timestamp'] >= $startDate;
    });
    
    return array_values($events);
}

/**
 * 상품 조회수 기록
 */
function trackProductView($productType, $productId) {
    trackEvent('product_view', [
        'type' => $productType, // 'mvno', 'mno', 'internet'
        'id' => $productId
    ]);
}

/**
 * 상품 신청 기록
 */
function trackProductApplication($productType, $productId, $sellerId = null) {
    trackEvent('product_application', [
        'type' => $productType,
        'id' => $productId,
        'seller_id' => $sellerId
    ]);
}

/**
 * 찜 추가 기록
 */
function trackFavorite($productType, $productId, $sellerId = null, $action = 'add') {
    trackEvent('favorite_' . $action, [
        'type' => $productType,
        'id' => $productId,
        'seller_id' => $sellerId
    ]);
}

/**
 * 공유 기록
 */
function trackShare($productType, $productId, $shareMethod, $sellerId = null) {
    trackEvent('share', [
        'type' => $productType,
        'id' => $productId,
        'method' => $shareMethod, // 'kakao', 'facebook', 'twitter', 'link', etc.
        'seller_id' => $sellerId
    ]);
}

/**
 * 리뷰 작성 기록
 */
function trackReview($productType, $productId, $rating, $sellerId = null) {
    trackEvent('review', [
        'type' => $productType,
        'id' => $productId,
        'rating' => floatval($rating), // 평균 별점
        'seller_id' => $sellerId
    ]);
}

/**
 * 상품별 통계 가져오기
 */
function getProductStats($productType = null, $days = 30) {
    $events = getEventStats('product_view', $days);
    
    $productCounts = [];
    
    foreach ($events as $event) {
        if ($productType && $event['data']['type'] !== $productType) {
            continue;
        }
        
        $key = $event['data']['type'] . '_' . $event['data']['id'];
        if (!isset($productCounts[$key])) {
            $productCounts[$key] = [
                'type' => $event['data']['type'],
                'id' => $event['data']['id'],
                'views' => 0,
                'applications' => 0
            ];
        }
        $productCounts[$key]['views']++;
    }
    
    // 신청 통계 추가
    $applicationEvents = getEventStats('product_application', $days);
    foreach ($applicationEvents as $event) {
        if ($productType && $event['data']['type'] !== $productType) {
            continue;
        }
        
        $key = $event['data']['type'] . '_' . $event['data']['id'];
        if (isset($productCounts[$key])) {
            $productCounts[$key]['applications']++;
        }
    }
    
    // 조회수 순으로 정렬
    usort($productCounts, function($a, $b) {
        return $b['views'] - $a['views'];
    });
    
    return $productCounts;
}

/**
 * 세션 ID 생성/가져오기
 */
function getSessionId() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['analytics_session_id'])) {
        $_SESSION['analytics_session_id'] = uniqid('sess_', true);
        $_SESSION['analytics_session_start'] = time();
    }
    
    return $_SESSION['analytics_session_id'];
}

/**
 * 활성 세션 업데이트 (실시간 접속자 추적)
 */
function updateActiveSession($page) {
    $data = getAnalyticsData();
    
    if (!isset($data['active_sessions'])) {
        $data['active_sessions'] = [];
    }
    
    $sessionId = getSessionId();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $now = time();
    
    // 기존 세션 업데이트 또는 새 세션 생성
    $data['active_sessions'][$sessionId] = [
        'session_id' => $sessionId,
        'ip' => $ip,
        'user_agent' => $userAgent,
        'current_page' => $page,
        'last_activity' => $now,
        'start_time' => $_SESSION['analytics_session_start'] ?? $now,
        'page_count' => ($data['active_sessions'][$sessionId]['page_count'] ?? 0) + 1
    ];
    
    // 5분 이상 비활성 세션 제거
    foreach ($data['active_sessions'] as $sid => $session) {
        if ($now - $session['last_activity'] > 300) { // 5분
            unset($data['active_sessions'][$sid]);
        }
    }
    
    // 세션 데이터에 페이지 경로 추가
    if (!isset($data['session_data'][$sessionId])) {
        $data['session_data'][$sessionId] = [
            'session_id' => $sessionId,
            'ip' => $ip,
            'start_time' => date('Y-m-d H:i:s', $_SESSION['analytics_session_start'] ?? $now),
            'pages' => [],
            'referrer' => $_SERVER['HTTP_REFERER'] ?? ''
        ];
    }
    
    $data['session_data'][$sessionId]['pages'][] = [
        'page' => $page,
        'timestamp' => date('Y-m-d H:i:s', $now)
    ];
    
    // 설정에서 읽은 최대 개수만 유지 (성능 고려)
    $settings = getAnalyticsSettings();
    $maxSessions = $settings['max_sessions'] ?? 10000;
    if (count($data['session_data']) > $maxSessions) {
        $data['session_data'] = array_slice($data['session_data'], -$maxSessions, null, true);
    }
    
    saveAnalyticsData($data);
    
    return true;
}

/**
 * 현재 접속자 수 가져오기
 */
function getCurrentActiveUsers() {
    $data = getAnalyticsData();
    
    if (!isset($data['active_sessions'])) {
        return 0;
    }
    
    $now = time();
    $activeCount = 0;
    
    foreach ($data['active_sessions'] as $session) {
        // 최근 5분 이내 활동이 있으면 활성 사용자로 간주
        if ($now - $session['last_activity'] <= 300) {
            $activeCount++;
        }
    }
    
    return $activeCount;
}

/**
 * 실시간 페이지뷰 가져오기 (최근 1분)
 */
function getRealTimePageviews($minutes = 1) {
    $data = getAnalyticsData();
    $cutoffTime = time() - ($minutes * 60);
    
    $recentPageviews = [];
    
    foreach ($data['pageviews'] as $pageview) {
        $timestamp = strtotime($pageview['timestamp']);
        if ($timestamp >= $cutoffTime) {
            $recentPageviews[] = $pageview;
        }
    }
    
    return $recentPageviews;
}

/**
 * 사용자 경로 분석
 */
function getUserPaths($limit = 20) {
    $data = getAnalyticsData();
    
    if (!isset($data['session_data'])) {
        return [];
    }
    
    $paths = [];
    
    foreach ($data['session_data'] as $sessionId => $session) {
        if (count($session['pages']) < 2) {
            continue; // 1페이지만 본 세션은 제외
        }
        
        $path = [];
        foreach ($session['pages'] as $pageData) {
            $path[] = $pageData['page'];
        }
        
        $pathKey = implode(' → ', $path);
        
        if (!isset($paths[$pathKey])) {
            $paths[$pathKey] = [
                'path' => $path,
                'count' => 0,
                'sessions' => []
            ];
        }
        
        $paths[$pathKey]['count']++;
        $paths[$pathKey]['sessions'][] = $sessionId;
    }
    
    // 빈도순으로 정렬
    uasort($paths, function($a, $b) {
        return $b['count'] - $a['count'];
    });
    
    return array_slice($paths, 0, $limit, true);
}

/**
 * 이탈률 계산
 */
function getBounceRate($days = 7) {
    $data = getAnalyticsData();
    $startDate = date('Y-m-d', strtotime("-{$days} days"));
    $endDate = date('Y-m-d');
    
    $totalSessions = 0;
    $bouncedSessions = 0;
    
    foreach ($data['session_data'] as $sessionId => $session) {
        $sessionDate = date('Y-m-d', strtotime($session['start_time']));
        
        if ($sessionDate >= $startDate && $sessionDate <= $endDate) {
            $totalSessions++;
            
            // 1페이지만 본 세션 = 이탈
            if (count($session['pages']) === 1) {
                $bouncedSessions++;
            }
        }
    }
    
    if ($totalSessions === 0) {
        return 0;
    }
    
    return round(($bouncedSessions / $totalSessions) * 100, 2);
}

/**
 * 평균 세션 시간 계산
 */
function getAverageSessionTime($days = 7) {
    $data = getAnalyticsData();
    $startDate = date('Y-m-d', strtotime("-{$days} days"));
    $endDate = date('Y-m-d');
    
    $totalTime = 0;
    $sessionCount = 0;
    
    foreach ($data['session_data'] as $sessionId => $session) {
        $sessionDate = date('Y-m-d', strtotime($session['start_time']));
        
        if ($sessionDate >= $startDate && $sessionDate <= $endDate) {
            if (count($session['pages']) < 2) {
                continue; // 1페이지만 본 세션은 시간 계산 불가
            }
            
            $startTime = strtotime($session['pages'][0]['timestamp']);
            $endTime = strtotime($session['pages'][count($session['pages']) - 1]['timestamp']);
            
            $sessionTime = $endTime - $startTime;
            
            // 최대 30분으로 제한 (비정상적으로 긴 세션 제외)
            if ($sessionTime <= 1800) {
                $totalTime += $sessionTime;
                $sessionCount++;
            }
        }
    }
    
    if ($sessionCount === 0) {
        return 0;
    }
    
    return round($totalTime / $sessionCount);
}

/**
 * 세션 시간을 읽기 쉬운 형식으로 변환
 */
function formatSessionTime($seconds) {
    if ($seconds < 60) {
        return $seconds . '초';
    } elseif ($seconds < 3600) {
        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;
        return $minutes . '분 ' . ($secs > 0 ? $secs . '초' : '');
    } else {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        return $hours . '시간 ' . ($minutes > 0 ? $minutes . '분' : '');
    }
}

/**
 * 오래된 데이터 자동 삭제 (2년 이상)
 */
function cleanupOldAnalyticsData($years = 2) {
    $data = getAnalyticsData();
    $cutoffDate = date('Y-m-d', strtotime("-{$years} years"));
    $cutoffTimestamp = strtotime($cutoffDate . ' 00:00:00');
    $cleaned = false;
    
    // 페이지뷰 데이터 정리
    if (isset($data['pageviews']) && is_array($data['pageviews'])) {
        $originalCount = count($data['pageviews']);
        $data['pageviews'] = array_filter($data['pageviews'], function($pageview) use ($cutoffTimestamp) {
            $pageviewTimestamp = strtotime($pageview['timestamp']);
            return $pageviewTimestamp >= $cutoffTimestamp;
        });
        $data['pageviews'] = array_values($data['pageviews']); // 인덱스 재정렬
        
        if (count($data['pageviews']) < $originalCount) {
            $cleaned = true;
        }
    }
    
    // 이벤트 데이터 정리
    if (isset($data['events']) && is_array($data['events'])) {
        $originalCount = count($data['events']);
        $data['events'] = array_filter($data['events'], function($event) use ($cutoffTimestamp) {
            $eventTimestamp = strtotime($event['timestamp']);
            return $eventTimestamp >= $cutoffTimestamp;
        });
        $data['events'] = array_values($data['events']); // 인덱스 재정렬
        
        if (count($data['events']) < $originalCount) {
            $cleaned = true;
        }
    }
    
    // 세션 데이터 정리
    if (isset($data['session_data']) && is_array($data['session_data'])) {
        $originalCount = count($data['session_data']);
        foreach ($data['session_data'] as $sessionId => $session) {
            $sessionTimestamp = strtotime($session['start_time']);
            if ($sessionTimestamp < $cutoffTimestamp) {
                unset($data['session_data'][$sessionId]);
                $cleaned = true;
            }
        }
    }
    
    // 활성 세션 정리 (2년 이상 된 것은 이미 비활성이므로 제거)
    if (isset($data['active_sessions']) && is_array($data['active_sessions'])) {
        $originalCount = count($data['active_sessions']);
        foreach ($data['active_sessions'] as $sessionId => $session) {
            if (isset($session['start_time'])) {
                $sessionTimestamp = is_numeric($session['start_time']) 
                    ? $session['start_time'] 
                    : strtotime($session['start_time']);
                
                if ($sessionTimestamp < $cutoffTimestamp) {
                    unset($data['active_sessions'][$sessionId]);
                    $cleaned = true;
                }
            }
        }
    }
    
    // 일별 통계 정리
    if (isset($data['daily_stats']) && is_array($data['daily_stats'])) {
        $originalCount = count($data['daily_stats']);
        foreach ($data['daily_stats'] as $date => $stats) {
            if ($date < $cutoffDate) {
                unset($data['daily_stats'][$date]);
                $cleaned = true;
            }
        }
    }
    
    if ($cleaned) {
        saveAnalyticsData($data);
    }
    
    return $cleaned;
}

/**
 * 자동 정리 실행 (성능 고려하여 가끔씩만 실행)
 */
function autoCleanupOldData() {
    $settings = getAnalyticsSettings();
    
    // 자동 정리가 비활성화되어 있으면 실행하지 않음
    if (isset($settings['auto_cleanup_enabled']) && $settings['auto_cleanup_enabled'] === false) {
        return;
    }
    
    $cleanupFile = __DIR__ . '/analytics-cleanup-last-run.txt';
    $lastRun = file_exists($cleanupFile) ? (int)file_get_contents($cleanupFile) : 0;
    $now = time();
    
    // 마지막 실행으로부터 24시간이 지났을 때만 실행
    if ($now - $lastRun > 86400) {
        $years = $settings['auto_cleanup_years'] ?? 2;
        cleanupOldAnalyticsData($years);
        file_put_contents($cleanupFile, $now);
    }
}

/**
 * 페이지별 이탈률 계산
 */
function getPageBounceRates($days = 7) {
    $data = getAnalyticsData();
    $startDate = date('Y-m-d', strtotime("-{$days} days"));
    $endDate = date('Y-m-d');
    
    $pageStats = [];
    
    foreach ($data['session_data'] as $sessionId => $session) {
        $sessionDate = date('Y-m-d', strtotime($session['start_time']));
        
        if ($sessionDate >= $startDate && $sessionDate <= $endDate) {
            if (count($session['pages']) === 1) {
                // 이탈 세션
                $firstPage = $session['pages'][0]['page'];
                
                if (!isset($pageStats[$firstPage])) {
                    $pageStats[$firstPage] = [
                        'total_entrances' => 0,
                        'bounces' => 0
                    ];
                }
                
                $pageStats[$firstPage]['total_entrances']++;
                $pageStats[$firstPage]['bounces']++;
            } else {
                // 첫 페이지만 카운트
                $firstPage = $session['pages'][0]['page'];
                
                if (!isset($pageStats[$firstPage])) {
                    $pageStats[$firstPage] = [
                        'total_entrances' => 0,
                        'bounces' => 0
                    ];
                }
                
                $pageStats[$firstPage]['total_entrances']++;
            }
        }
    }
    
    // 이탈률 계산
    $bounceRates = [];
    foreach ($pageStats as $page => $stats) {
        $bounceRate = $stats['total_entrances'] > 0 
            ? round(($stats['bounces'] / $stats['total_entrances']) * 100, 2)
            : 0;
        
        $bounceRates[$page] = [
            'page' => $page,
            'total_entrances' => $stats['total_entrances'],
            'bounces' => $stats['bounces'],
            'bounce_rate' => $bounceRate
        ];
    }
    
    // 이탈률 높은 순으로 정렬
    uasort($bounceRates, function($a, $b) {
        return $b['bounce_rate'] - $a['bounce_rate'];
    });
    
    return $bounceRates;
}

