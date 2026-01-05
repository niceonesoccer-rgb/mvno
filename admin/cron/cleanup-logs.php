<?php
/**
 * 로그 파일 자동 정리 스크립트
 * 
 * 사용법:
 * - Windows 작업 스케줄러: 매일 새벽 2시 실행
 * - Linux Cron: 0 2 * * * php /path/to/admin/cron/cleanup-logs.php
 * 
 * 실행 방법:
 * php admin/cron/cleanup-logs.php
 * 또는
 * php admin/cron/cleanup-logs.php --days=7
 */

// CLI에서만 실행 가능
if (php_sapi_name() !== 'cli' && !isset($_GET['cron_key'])) {
    die('This script can only be run from command line or with cron_key parameter.');
}

// cron_key 확인 (보안)
$cronKey = 'CHANGE_THIS_TO_RANDOM_STRING_' . date('Y');
if (php_sapi_name() !== 'cli' && (!isset($_GET['cron_key']) || $_GET['cron_key'] !== $cronKey)) {
    die('Invalid cron key.');
}

// 경로 설정
$baseDir = dirname(dirname(__DIR__));
chdir($baseDir);

// 파일 포함
require_once __DIR__ . '/../../includes/data/db-config.php';
require_once __DIR__ . '/../../includes/data/log-cleanup-functions.php';

// 한국 시간대 설정
date_default_timezone_set('Asia/Seoul');

// 보관 기간 설정 (기본 7일)
$days = 7;
if (isset($argv)) {
    foreach ($argv as $arg) {
        if (preg_match('/--days=(\d+)/', $arg, $matches)) {
            $days = (int)$matches[1];
        }
    }
}
if (isset($_GET['days'])) {
    $days = (int)$_GET['days'];
}

// 로그 정리 실행
$startTime = microtime(true);
$results = cleanupAllLogs($days);

// MySQL 바이너리 로그 설정
setMysqlBinlogExpiration($days);

// 결과 요약
$totalDeleted = 0;
$totalSizeFreed = 0;
$details = [];

foreach ($results as $type => $result) {
    $deleted = $result['deleted'] ?? 0;
    $sizeFreed = $result['size_freed'] ?? 0;
    $totalDeleted += $deleted;
    $totalSizeFreed += $sizeFreed;
    
    if ($deleted > 0 || $sizeFreed > 0) {
        $details[] = sprintf(
            "  %s: %d건 삭제, %s 절약",
            ucfirst($type),
            $deleted,
            formatBytes($sizeFreed)
        );
    }
}

$endTime = microtime(true);
$executionTime = round($endTime - $startTime, 2);

// 결과 출력
if (php_sapi_name() === 'cli') {
    echo "========================================\n";
    echo "로그 파일 자동 정리 완료\n";
    echo "========================================\n";
    echo "실행 시간: " . date('Y-m-d H:i:s') . "\n";
    echo "보관 기간: {$days}일\n";
    echo "총 삭제: {$totalDeleted}건\n";
    echo "절약 용량: " . formatBytes($totalSizeFreed) . "\n";
    echo "실행 시간: {$executionTime}초\n";
    if (!empty($details)) {
        echo "\n상세 내역:\n";
        echo implode("\n", $details) . "\n";
    }
    echo "========================================\n";
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'days' => $days,
        'total_deleted' => $totalDeleted,
        'total_size_freed' => $totalSizeFreed,
        'total_size_freed_mb' => round($totalSizeFreed / 1024 / 1024, 2),
        'execution_time' => $executionTime,
        'details' => $results
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

/**
 * 바이트를 읽기 쉬운 형식으로 변환
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}
