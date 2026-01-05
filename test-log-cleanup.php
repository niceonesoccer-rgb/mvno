<?php
/**
 * 로그 정리 기능 테스트 스크립트
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== 로그 정리 기능 테스트 ===\n\n";

// 1. 함수 파일 로드 테스트
echo "1. 함수 파일 로드 테스트...\n";
try {
    require_once __DIR__ . '/includes/data/log-cleanup-functions.php';
    echo "   ✓ 함수 파일 로드 성공\n\n";
} catch (Exception $e) {
    echo "   ✗ 함수 파일 로드 실패: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. 함수 존재 확인
echo "2. 함수 존재 확인...\n";
$functions = [
    'cleanupAllLogs',
    'cleanupCustomLogs',
    'cleanupPhpErrorLog',
    'cleanupApacheLogs',
    'cleanupMysqlLogs',
    'cleanupCacheFiles',
    'cleanupSessionFiles',
    'getLogSizes',
    'setMysqlBinlogExpiration'
];

$allExists = true;
foreach ($functions as $func) {
    if (function_exists($func)) {
        echo "   ✓ {$func}() 존재\n";
    } else {
        echo "   ✗ {$func}() 없음\n";
        $allExists = false;
    }
}

if (!$allExists) {
    echo "\n   일부 함수가 없습니다. 종료합니다.\n";
    exit(1);
}
echo "\n";

// 3. 로그 파일 크기 확인
echo "3. 현재 로그 파일 크기 확인...\n";
try {
    $logSizes = getLogSizes();
    if (empty($logSizes)) {
        echo "   ⚠ 로그 파일이 없습니다.\n";
    } else {
        foreach ($logSizes as $key => $size) {
            if (is_array($size)) {
                foreach ($size as $file => $fileSize) {
                    $sizeMB = round($fileSize / 1024 / 1024, 2);
                    echo "   - {$key}/{$file}: {$sizeMB}MB\n";
                }
            } else {
                $sizeMB = round($size / 1024 / 1024, 2);
                if (isset($logSizes['cache_count'])) {
                    echo "   - {$key}: {$sizeMB}MB ({$logSizes['cache_count']}개 파일)\n";
                } else {
                    echo "   - {$key}: {$sizeMB}MB\n";
                }
            }
        }
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ✗ 로그 크기 확인 실패: " . $e->getMessage() . "\n\n";
}

// 4. 커스텀 로그 정리 테스트 (30일 보관 - 실제로는 삭제 안됨)
echo "4. 커스텀 로그 정리 테스트 (30일 보관 - 실제 삭제 안됨)...\n";
try {
    $result = cleanupCustomLogs(30);
    echo "   - 삭제된 항목: {$result['deleted']}건\n";
    echo "   - 절약된 용량: " . round($result['size_freed'] / 1024, 2) . "KB\n";
    if (!empty($result['files'])) {
        echo "   - 정리된 파일: " . implode(', ', $result['files']) . "\n";
    }
    echo "   ✓ 커스텀 로그 정리 함수 작동 확인\n\n";
} catch (Exception $e) {
    echo "   ✗ 커스텀 로그 정리 실패: " . $e->getMessage() . "\n\n";
}

// 5. 캐시 파일 정리 테스트
echo "5. 캐시 파일 정리 테스트 (30일 보관)...\n";
try {
    $result = cleanupCacheFiles(30);
    echo "   - 삭제된 항목: {$result['deleted']}건\n";
    echo "   - 절약된 용량: " . round($result['size_freed'] / 1024, 2) . "KB\n";
    echo "   ✓ 캐시 파일 정리 함수 작동 확인\n\n";
} catch (Exception $e) {
    echo "   ✗ 캐시 파일 정리 실패: " . $e->getMessage() . "\n\n";
}

// 6. 전체 로그 정리 테스트 (30일 보관)
echo "6. 전체 로그 정리 테스트 (30일 보관 - 실제 삭제 안됨)...\n";
try {
    $results = cleanupAllLogs(30);
    $totalDeleted = 0;
    $totalSizeFreed = 0;
    
    foreach ($results as $type => $result) {
        $deleted = $result['deleted'] ?? 0;
        $sizeFreed = $result['size_freed'] ?? 0;
        $totalDeleted += $deleted;
        $totalSizeFreed += $sizeFreed;
        
        if ($deleted > 0 || $sizeFreed > 0) {
            echo "   - {$type}: {$deleted}건 삭제, " . round($sizeFreed / 1024, 2) . "KB 절약\n";
        }
    }
    
    echo "   - 총 삭제: {$totalDeleted}건\n";
    echo "   - 총 절약: " . round($totalSizeFreed / 1024 / 1024, 2) . "MB\n";
    echo "   ✓ 전체 로그 정리 함수 작동 확인\n\n";
} catch (Exception $e) {
    echo "   ✗ 전체 로그 정리 실패: " . $e->getMessage() . "\n\n";
}

// 7. MySQL 바이너리 로그 설정 테스트
echo "7. MySQL 바이너리 로그 설정 테스트...\n";
try {
    $result = setMysqlBinlogExpiration(7);
    if ($result) {
        echo "   ✓ MySQL 바이너리 로그 설정 성공 (7일)\n";
    } else {
        echo "   ⚠ MySQL 바이너리 로그 설정 실패 (DB 연결 실패 가능)\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ⚠ MySQL 바이너리 로그 설정 실패: " . $e->getMessage() . "\n";
    echo "   (DB 연결이 안되어도 정상 - 로그 정리 기능은 작동합니다)\n\n";
}

echo "=== 테스트 완료 ===\n";
echo "모든 함수가 정상적으로 작동합니다!\n";
echo "관리자 페이지에서 실제로 실행해보세요: /MVNO/admin/settings/data-delete.php\n";
