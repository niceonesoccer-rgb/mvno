<?php
/**
 * 통계 데이터 자동 정리 크론잡
 * 
 * 실행 방법:
 * - Windows 작업 스케줄러: 매일 02:00에 실행
 * - Linux Cron: 0 2 * * * php /path/to/admin/cron/cleanup-analytics.php
 * 
 * 또는 브라우저에서 수동 실행:
 * http://localhost/MVNO/admin/cron/cleanup-analytics.php
 */

// 한국 시간대 설정
date_default_timezone_set('Asia/Seoul');

require_once __DIR__ . '/../../includes/data/db-config.php';
require_once __DIR__ . '/../../includes/data/app-settings.php';

// HTML 출력 모드 확인
$isWeb = isset($_SERVER['HTTP_HOST']);

if ($isWeb) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>통계 데이터 자동 정리</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
            .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            h1 { color: #333; border-bottom: 2px solid #10b981; padding-bottom: 10px; }
            .success { color: #10b981; background: #d1fae5; padding: 10px; border-radius: 4px; margin: 10px 0; }
            .error { color: #f44336; background: #fee2e2; padding: 10px; border-radius: 4px; margin: 10px 0; }
            .info { color: #2196F3; background: #dbeafe; padding: 10px; border-radius: 4px; margin: 10px 0; }
            .warning { color: #f59e0b; background: #fef3c7; padding: 10px; border-radius: 4px; margin: 10px 0; }
        </style>
    </head>
    <body>
    <div class='container'>";
}

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception("데이터베이스 연결 실패");
    }
    
    if ($isWeb) {
        echo "<h1>📊 통계 데이터 자동 정리</h1>";
    } else {
        echo "통계 데이터 자동 정리 시작...\n";
    }
    
    // 저장된 보관 기간 불러오기
    $analyticsSettings = getAppSettings('analytics_cleanup_settings', ['retention_days' => 90]);
    $retentionDays = (int)($analyticsSettings['retention_days'] ?? 90);
    
    if ($isWeb) {
        echo "<div class='info'><strong>보관 기간:</strong> {$retentionDays}일</div>";
    } else {
        echo "보관 기간: {$retentionDays}일\n\n";
    }
    
    $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$retentionDays} days"));
    
    if ($isWeb) {
        echo "<div class='info'><strong>삭제 기준 날짜:</strong> {$cutoffDate} (이 날짜 이전의 데이터가 삭제됩니다)</div>";
    } else {
        echo "삭제 기준 날짜: {$cutoffDate}\n\n";
    }
    
    $deletedCounts = [
        'impressions' => 0,
        'clicks' => 0,
        'analytics' => 0
    ];
    
    $pdo->beginTransaction();
    
    // advertisement_impressions 삭제
    try {
        $stmt = $pdo->prepare("DELETE FROM advertisement_impressions WHERE created_at < :cutoff_date");
        $stmt->execute([':cutoff_date' => $cutoffDate]);
        $deletedCounts['impressions'] = $stmt->rowCount();
        
        if ($isWeb) {
            echo "<div class='success'>✅ 광고 노출 데이터: {$deletedCounts['impressions']}건 삭제</div>";
        } else {
            echo "✅ 광고 노출 데이터: {$deletedCounts['impressions']}건 삭제\n";
        }
    } catch (PDOException $e) {
        if ($isWeb) {
            echo "<div class='warning'>⚠️ 광고 노출 데이터 삭제 건너뜀: " . htmlspecialchars($e->getMessage()) . "</div>";
        } else {
            echo "⚠️ 광고 노출 데이터 삭제 건너뜀: " . $e->getMessage() . "\n";
        }
    }
    
    // advertisement_clicks 삭제
    try {
        $stmt = $pdo->prepare("DELETE FROM advertisement_clicks WHERE created_at < :cutoff_date");
        $stmt->execute([':cutoff_date' => $cutoffDate]);
        $deletedCounts['clicks'] = $stmt->rowCount();
        
        if ($isWeb) {
            echo "<div class='success'>✅ 광고 클릭 데이터: {$deletedCounts['clicks']}건 삭제</div>";
        } else {
            echo "✅ 광고 클릭 데이터: {$deletedCounts['clicks']}건 삭제\n";
        }
    } catch (PDOException $e) {
        if ($isWeb) {
            echo "<div class='warning'>⚠️ 광고 클릭 데이터 삭제 건너뜀: " . htmlspecialchars($e->getMessage()) . "</div>";
        } else {
            echo "⚠️ 광고 클릭 데이터 삭제 건너뜀: " . $e->getMessage() . "\n";
        }
    }
    
    // advertisement_analytics 삭제
    try {
        $stmt = $pdo->prepare("DELETE FROM advertisement_analytics WHERE stat_date < DATE(:cutoff_date)");
        $stmt->execute([':cutoff_date' => $cutoffDate]);
        $deletedCounts['analytics'] = $stmt->rowCount();
        
        if ($isWeb) {
            echo "<div class='success'>✅ 광고 통계 집계 데이터: {$deletedCounts['analytics']}건 삭제</div>";
        } else {
            echo "✅ 광고 통계 집계 데이터: {$deletedCounts['analytics']}건 삭제\n";
        }
    } catch (PDOException $e) {
        if ($isWeb) {
            echo "<div class='warning'>⚠️ 광고 통계 집계 데이터 삭제 건너뜀: " . htmlspecialchars($e->getMessage()) . "</div>";
        } else {
            echo "⚠️ 광고 통계 집계 데이터 삭제 건너뜀: " . $e->getMessage() . "\n";
        }
    }
    
    $pdo->commit();
    
    // 일반 통계 분석 데이터 정리
    require_once __DIR__ . '/../../includes/data/analytics-functions.php';
    
    $generalAnalyticsSettings = getAppSettings('general_analytics_cleanup_settings', ['retention_days' => 90]);
    $generalRetentionDays = (int)($generalAnalyticsSettings['retention_days'] ?? 90);
    $generalCutoffTimestamp = strtotime("-{$generalRetentionDays} days");
    $generalCutoffDate = date('Y-m-d', $generalCutoffTimestamp);
    $generalCutoffDateTime = date('Y-m-d H:i:s', $generalCutoffTimestamp);
    
    $generalDeletedCounts = [
        'pageviews' => 0,
        'events' => 0,
        'sessions' => 0,
        'daily_stats' => 0
    ];
    
    try {
        $data = getAnalyticsData();
        
        // 페이지뷰 데이터 정리
        if (isset($data['pageviews']) && is_array($data['pageviews'])) {
            $beforeCount = count($data['pageviews']);
            $data['pageviews'] = array_filter($data['pageviews'], function($pv) use ($generalCutoffDate) {
                return isset($pv['date']) && $pv['date'] >= $generalCutoffDate;
            });
            $generalDeletedCounts['pageviews'] = $beforeCount - count($data['pageviews']);
        }
        
        // 이벤트 데이터 정리
        if (isset($data['events']) && is_array($data['events'])) {
            $beforeCount = count($data['events']);
            $data['events'] = array_filter($data['events'], function($event) use ($generalCutoffDateTime) {
                return isset($event['timestamp']) && $event['timestamp'] >= $generalCutoffDateTime;
            });
            $generalDeletedCounts['events'] = $beforeCount - count($data['events']);
        }
        
        // 세션 데이터 정리
        if (isset($data['session_data']) && is_array($data['session_data'])) {
            $beforeCount = count($data['session_data']);
            $data['session_data'] = array_filter($data['session_data'], function($session) use ($generalCutoffDateTime) {
                return isset($session['start_time']) && $session['start_time'] >= $generalCutoffDateTime;
            }, ARRAY_FILTER_USE_KEY);
            $generalDeletedCounts['sessions'] = $beforeCount - count($data['session_data']);
        }
        
        // 일별 통계 정리
        if (isset($data['daily_stats']) && is_array($data['daily_stats'])) {
            $beforeCount = count($data['daily_stats']);
            $data['daily_stats'] = array_filter($data['daily_stats'], function($stat) use ($generalCutoffDate) {
                return isset($stat['date']) && $stat['date'] >= $generalCutoffDate;
            }, ARRAY_FILTER_USE_KEY);
            $generalDeletedCounts['daily_stats'] = $beforeCount - count($data['daily_stats']);
        }
        
        // 활성 세션 정리
        if (isset($data['active_sessions']) && is_array($data['active_sessions'])) {
            $now = time();
            foreach ($data['active_sessions'] as $sid => $session) {
                if (isset($session['last_activity']) && ($now - $session['last_activity']) > ($generalRetentionDays * 86400)) {
                    unset($data['active_sessions'][$sid]);
                }
            }
        }
        
        // 정리된 데이터 저장
        saveAnalyticsData($data);
        
        if ($isWeb) {
            echo "<div class='success'>✅ 일반 통계 분석 데이터 정리 완료 (페이지뷰: {$generalDeletedCounts['pageviews']}건, 이벤트: {$generalDeletedCounts['events']}건, 세션: {$generalDeletedCounts['sessions']}건, 일별통계: {$generalDeletedCounts['daily_stats']}건)</div>";
        } else {
            echo "✅ 일반 통계 분석 데이터 정리 완료 (페이지뷰: {$generalDeletedCounts['pageviews']}건, 이벤트: {$generalDeletedCounts['events']}건, 세션: {$generalDeletedCounts['sessions']}건, 일별통계: {$generalDeletedCounts['daily_stats']}건)\n";
        }
    } catch (Exception $e) {
        if ($isWeb) {
            echo "<div class='warning'>⚠️ 일반 통계 분석 데이터 정리 건너뜀: " . htmlspecialchars($e->getMessage()) . "</div>";
        } else {
            echo "⚠️ 일반 통계 분석 데이터 정리 건너뜀: " . $e->getMessage() . "\n";
        }
    }
    
    $totalDeleted = $deletedCounts['impressions'] + $deletedCounts['clicks'] + $deletedCounts['analytics'];
    $generalTotalDeleted = $generalDeletedCounts['pageviews'] + $generalDeletedCounts['events'] + $generalDeletedCounts['sessions'] + $generalDeletedCounts['daily_stats'];
    
    // 결과 요약
    if ($isWeb) {
        echo "<h2>정리 결과</h2>";
        echo "<div class='success'>";
        echo "<strong>광고 분석 데이터 삭제:</strong> {$totalDeleted}건<br>";
        echo "&nbsp;&nbsp;- 노출 데이터: {$deletedCounts['impressions']}건<br>";
        echo "&nbsp;&nbsp;- 클릭 데이터: {$deletedCounts['clicks']}건<br>";
        echo "&nbsp;&nbsp;- 통계 집계 데이터: {$deletedCounts['analytics']}건<br><br>";
        echo "<strong>일반 통계 분석 데이터 삭제:</strong> {$generalTotalDeleted}건<br>";
        echo "&nbsp;&nbsp;- 페이지뷰: {$generalDeletedCounts['pageviews']}건<br>";
        echo "&nbsp;&nbsp;- 이벤트: {$generalDeletedCounts['events']}건<br>";
        echo "&nbsp;&nbsp;- 세션: {$generalDeletedCounts['sessions']}건<br>";
        echo "&nbsp;&nbsp;- 일별통계: {$generalDeletedCounts['daily_stats']}건<br><br>";
        echo "<strong>총 삭제:</strong> " . ($totalDeleted + $generalTotalDeleted) . "건";
        echo "</div>";
        
        if ($totalDeleted === 0 && $generalTotalDeleted === 0) {
            echo "<div class='info'>삭제할 데이터가 없습니다. (광고 분석 보관 기간: {$retentionDays}일, 일반 통계 분석 보관 기간: {$generalRetentionDays}일)</div>";
        }
        
        echo "<h2>자동 실행 설정</h2>";
        echo "<div class='info'>";
        echo "<strong>Windows 작업 스케줄러:</strong><br>";
        echo "프로그램: C:\\xampp\\php\\php.exe<br>";
        echo "인수: C:\\xampp\\htdocs\\mvno\\admin\\cron\\cleanup-analytics.php<br>";
        echo "일정: 매일 02:00<br><br>";
        echo "<strong>Linux Cron:</strong><br>";
        echo "<code>0 2 * * * /usr/bin/php /path/to/mvno/admin/cron/cleanup-analytics.php</code><br><br>";
        echo "<strong>보관 기간 변경:</strong><br>";
        echo "<a href='/MVNO/admin/settings/data-delete.php' style='color: #2563eb;'>데이터 삭제 관리</a> 페이지에서 보관 기간을 설정할 수 있습니다.";
        echo "</div>";
        
    } else {
        echo "\n=== 정리 결과 ===\n";
        echo "광고 분석 데이터 삭제: {$totalDeleted}건\n";
        echo "  - 노출 데이터: {$deletedCounts['impressions']}건\n";
        echo "  - 클릭 데이터: {$deletedCounts['clicks']}건\n";
        echo "  - 통계 집계 데이터: {$deletedCounts['analytics']}건\n\n";
        echo "일반 통계 분석 데이터 삭제: {$generalTotalDeleted}건\n";
        echo "  - 페이지뷰: {$generalDeletedCounts['pageviews']}건\n";
        echo "  - 이벤트: {$generalDeletedCounts['events']}건\n";
        echo "  - 세션: {$generalDeletedCounts['sessions']}건\n";
        echo "  - 일별통계: {$generalDeletedCounts['daily_stats']}건\n\n";
        echo "총 삭제: " . ($totalDeleted + $generalTotalDeleted) . "건\n";
        
        if ($totalDeleted === 0 && $generalTotalDeleted === 0) {
            echo "\n삭제할 데이터가 없습니다. (광고 분석 보관 기간: {$retentionDays}일, 일반 통계 분석 보관 기간: {$generalRetentionDays}일)\n";
        }
    }
    
} catch (Exception $e) {
    $errorMsg = "오류 발생: " . $e->getMessage();
    
    if ($isWeb) {
        echo "<div class='error'><strong>❌ {$errorMsg}</strong></div>";
    } else {
        echo "❌ {$errorMsg}\n";
    }
    exit(1);
}

if ($isWeb) {
    echo "</div></body></html>";
}
