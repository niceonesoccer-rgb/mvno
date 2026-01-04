<?php
/**
 * 5년 경과한 약관 버전 자동 삭제 스크립트
 * 
 * 사용법:
 * - 매일 실행: cron job 또는 스케줄러로 설정
 * - 수동 실행: php api/auto-delete-old-terms-versions.php
 * 
 * 약관/개인정보처리방침 버전은 시행일자 기준 5년 경과 시 자동으로 삭제됩니다.
 * 단, 현재 활성 버전(is_active=1)은 삭제되지 않습니다.
 * 
 * 예시 cron job (Linux/Unix):
 * 0 0 * * * /usr/bin/php /path/to/mvno/api/auto-delete-old-terms-versions.php
 * 
 * Windows 작업 스케줄러:
 * php.exe C:\xampp\htdocs\mvno\api\auto-delete-old-terms-versions.php
 */

// 실행 시간 측정 시작
$startTime = microtime(true);

// 한국 시간대 설정
date_default_timezone_set('Asia/Seoul');

require_once __DIR__ . '/../includes/data/terms-functions.php';

// CLI 환경 체크 (웹 브라우저에서 직접 접근 방지 - 선택사항)
if (php_sapi_name() !== 'cli' && (!isset($_GET['key']) || $_GET['key'] !== 'auto-delete-terms-2025')) {
    // 웹 브라우저 접근인 경우 보안 키 필요
    http_response_code(403);
    die('Forbidden');
}

header('Content-Type: text/plain; charset=utf-8');

echo "=== 5년 경과 약관 버전 자동 삭제 처리 시작 ===\n";
echo "실행 시간: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // 5년 경과 버전 삭제 처리 실행
    $result = deleteOldTermsVersions();
    
    $executionTime = round(microtime(true) - $startTime, 2);
    
    echo "처리 완료\n";
    echo "삭제된 건수: {$result['deleted']}건\n";
    echo "오류 개수: {$result['errors']}건\n";
    echo "실행 시간: {$executionTime}초\n";
    echo "\n=== 처리 완료 ===\n";
    
    // 웹 브라우저 접근인 경우
    if (php_sapi_name() !== 'cli') {
        echo "\n이 스크립트는 주로 cron job으로 실행됩니다.\n";
    }
    
    exit(0);
    
} catch (Exception $e) {
    $executionTime = round(microtime(true) - $startTime, 2);
    echo "\n오류 발생: " . $e->getMessage() . "\n";
    echo "실행 시간: {$executionTime}초\n";
    error_log('auto-delete-old-terms-versions error: ' . $e->getMessage());
    exit(1);
}
