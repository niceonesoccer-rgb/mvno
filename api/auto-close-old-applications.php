<?php
/**
 * 15일 이상 미처리 주문 자동 종료 처리 스크립트
 * 
 * 이 스크립트는 cron job이나 scheduled task로 실행되어야 합니다.
 * 
 * 사용법:
 * - Linux/Unix: crontab에 추가 (예: 매일 자정 실행)
 *   0 0 * * * /usr/bin/php /path/to/api/auto-close-old-applications.php
 * 
 * - Windows: 작업 스케줄러에 추가
 *   php.exe C:\xampp\htdocs\mvno\api\auto-close-old-applications.php
 */

// 실행 시간 측정 시작
$startTime = microtime(true);

// 한국 시간대 설정
date_default_timezone_set('Asia/Seoul');

require_once __DIR__ . '/../includes/data/product-functions.php';
require_once __DIR__ . '/../includes/data/db-config.php';

// CLI 환경 체크 (웹 브라우저에서 직접 접근 방지 - 선택사항)
if (php_sapi_name() !== 'cli' && (!isset($_GET['key']) || $_GET['key'] !== 'auto-close-2024')) {
    // 웹 브라우저 접근인 경우 보안 키 필요
    http_response_code(403);
    die('Forbidden');
}

header('Content-Type: text/plain; charset=utf-8');

echo "=== 15일 이상 미처리 주문 자동 종료 처리 시작 ===\n";
echo "실행 시간: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // 자동 종료 처리 실행
    $result = autoCloseOldApplications();
    
    $executionTime = round(microtime(true) - $startTime, 2);
    
    echo "처리 완료\n";
    echo "처리된 건수: {$result['processed']}건\n";
    echo "오류 개수: {$result['errors']}건\n";
    echo "실행 시간: {$executionTime}초\n";
    echo "\n=== 처리 완료 ===\n";
    
    // 웹 브라우저 접근인 경우
    if (php_sapi_name() !== 'cli') {
        echo "\n이 스크립트는 주로 cron job으로 실행됩니다.\n";
    }
    
} catch (Exception $e) {
    $executionTime = round(microtime(true) - $startTime, 2);
    
    echo "오류 발생: " . $e->getMessage() . "\n";
    echo "실행 시간: {$executionTime}초\n";
    echo "\n=== 처리 실패 ===\n";
    
    error_log("auto-close-old-applications.php 오류: " . $e->getMessage());
    
    http_response_code(500);
    exit(1);
}
