<?php
/**
 * 로그 파일 자동 정리 함수
 * 로그 로테이션 및 오래된 로그 삭제
 */

// 한국 시간대 설정
date_default_timezone_set('Asia/Seoul');

// DB 설정 포함 (MySQL 바이너리 로그 설정용)
require_once __DIR__ . '/db-config.php';

/**
 * 모든 로그 파일 정리
 * @param int $days 보관 기간 (일)
 * @return array 정리 결과
 */
function cleanupAllLogs($days = 7) {
    $results = [
        'custom_logs' => cleanupCustomLogs($days),
        'php_error_log' => cleanupPhpErrorLog($days),
        'apache_logs' => cleanupApacheLogs($days),
        'mysql_logs' => cleanupMysqlLogs($days),
        'cache_files' => cleanupCacheFiles($days),
        'session_files' => cleanupSessionFiles($days)
    ];
    
    return $results;
}

/**
 * 커스텀 로그 파일 정리 (logs/ 디렉토리)
 */
function cleanupCustomLogs($days = 7) {
    $logDir = __DIR__ . '/../../logs';
    if (!is_dir($logDir)) {
        return ['deleted' => 0, 'size_freed' => 0, 'files' => []];
    }
    
    $cutoff = time() - ($days * 24 * 60 * 60);
    $deleted = 0;
    $sizeFreed = 0;
    $files = [];
    
    // connections.log 정리
    $connectionsLog = $logDir . '/connections.log';
    if (file_exists($connectionsLog)) {
        $result = cleanupJsonLogFile($connectionsLog, $cutoff);
        $deleted += $result['deleted'];
        $sizeFreed += $result['size_freed'];
        if ($result['deleted'] > 0) {
            $files[] = 'connections.log';
        }
    }
    
    // sessions.log 정리
    $sessionsLog = $logDir . '/sessions.log';
    if (file_exists($sessionsLog)) {
        $result = cleanupJsonLogFile($sessionsLog, $cutoff);
        $deleted += $result['deleted'];
        $sizeFreed += $result['size_freed'];
        if ($result['deleted'] > 0) {
            $files[] = 'sessions.log';
        }
    }
    
    // event_debug.log 정리 (30일 이상 된 것만)
    $eventDebugLog = $logDir . '/event_debug.log';
    if (file_exists($eventDebugLog)) {
        $eventCutoff = time() - (30 * 24 * 60 * 60); // 30일
        $oldSize = filesize($eventDebugLog);
        $lines = file($eventDebugLog);
        $kept = [];
        
        foreach ($lines as $line) {
            // 로그 형식: "Time: YYYY-MM-DD HH:MM:SS" 추출
            if (preg_match('/Time:\s*(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})/', $line, $matches)) {
                $logTime = strtotime($matches[1]);
                if ($logTime >= $eventCutoff) {
                    $kept[] = $line;
                }
            } else {
                // 시간 정보가 없으면 보관 (최근 블록일 수 있음)
                $kept[] = $line;
            }
        }
        
        if (count($kept) < count($lines)) {
            file_put_contents($eventDebugLog, implode('', $kept), LOCK_EX);
            $newSize = filesize($eventDebugLog);
            $sizeFreed += ($oldSize - $newSize);
            $deleted += (count($lines) - count($kept));
            $files[] = 'event_debug.log';
        }
    }
    
    return [
        'deleted' => $deleted,
        'size_freed' => $sizeFreed,
        'files' => $files
    ];
}

/**
 * JSON 형식 로그 파일 정리
 */
function cleanupJsonLogFile($filePath, $cutoff) {
    if (!file_exists($filePath)) {
        return ['deleted' => 0, 'size_freed' => 0];
    }
    
    $oldSize = filesize($filePath);
    $lines = file($filePath);
    $kept = [];
    $deleted = 0;
    
    foreach ($lines as $line) {
        $data = json_decode(trim($line), true);
        if ($data && isset($data['time']) && $data['time'] >= $cutoff) {
            $kept[] = $line;
        } else {
            $deleted++;
        }
    }
    
    if ($deleted > 0) {
        file_put_contents($filePath, implode('', $kept), LOCK_EX);
        $newSize = filesize($filePath);
        $sizeFreed = $oldSize - $newSize;
    } else {
        $sizeFreed = 0;
    }
    
    return [
        'deleted' => $deleted,
        'size_freed' => $sizeFreed
    ];
}

/**
 * PHP 에러 로그 정리
 */
function cleanupPhpErrorLog($days = 7) {
    $logPaths = [
        ini_get('error_log'),
        'C:\\xampp\\php\\logs\\php_error_log',
        'C:\\xampp\\php\\logs\\error.log',
        __DIR__ . '/../../error.log'
    ];
    
    $deleted = 0;
    $sizeFreed = 0;
    $files = [];
    
    foreach ($logPaths as $logPath) {
        if (!$logPath || !file_exists($logPath)) {
            continue;
        }
        
        $cutoff = time() - ($days * 24 * 60 * 60);
        $oldSize = filesize($logPath);
        $lines = file($logPath);
        $kept = [];
        
        foreach ($lines as $line) {
            // PHP 로그 형식: "[날짜 시간] 메시지" 추출
            if (preg_match('/\[([^\]]+)\]/', $line, $matches)) {
                $logTime = strtotime($matches[1]);
                if ($logTime >= $cutoff) {
                    $kept[] = $line;
                }
            } else {
                // 시간 정보가 없으면 보관
                $kept[] = $line;
            }
        }
        
        if (count($kept) < count($lines)) {
            file_put_contents($logPath, implode('', $kept), LOCK_EX);
            $newSize = filesize($logPath);
            $sizeFreed += ($oldSize - $newSize);
            $deleted += (count($lines) - count($kept));
            $files[] = basename($logPath);
        }
    }
    
    return [
        'deleted' => $deleted,
        'size_freed' => $sizeFreed,
        'files' => $files
    ];
}

/**
 * Apache 로그 정리
 */
function cleanupApacheLogs($days = 7) {
    $logPaths = [
        'C:\\xampp\\apache\\logs\\error.log',
        'C:\\xampp\\apache\\logs\\access.log'
    ];
    
    $deleted = 0;
    $sizeFreed = 0;
    $files = [];
    
    foreach ($logPaths as $logPath) {
        if (!file_exists($logPath)) {
            continue;
        }
        
        // Apache 로그는 로테이션 방식으로 처리
        $cutoff = time() - ($days * 24 * 60 * 60);
        $oldSize = filesize($logPath);
        
        // 로그 파일이 너무 크면(100MB 이상) 압축 후 보관
        if ($oldSize > 100 * 1024 * 1024) {
            $archivePath = $logPath . '.' . date('Y-m-d') . '.gz';
            if (function_exists('gzencode')) {
                $content = file_get_contents($logPath);
                $compressed = gzencode($content, 9);
                file_put_contents($archivePath, $compressed);
                file_put_contents($logPath, ''); // 로그 파일 초기화
                $sizeFreed += $oldSize;
                $files[] = basename($logPath);
            }
        } else {
            // 작은 파일은 오래된 항목만 삭제
            $lines = file($logPath);
            $kept = [];
            
            foreach ($lines as $line) {
                // Apache 로그 형식: "[날짜 시간] 메시지" 추출
                if (preg_match('/\[([^\]]+)\]/', $line, $matches)) {
                    $logTime = strtotime($matches[1]);
                    if ($logTime >= $cutoff) {
                        $kept[] = $line;
                    }
                } else {
                    $kept[] = $line;
                }
            }
            
            if (count($kept) < count($lines)) {
                file_put_contents($logPath, implode('', $kept), LOCK_EX);
                $newSize = filesize($logPath);
                $sizeFreed += ($oldSize - $newSize);
                $deleted += (count($lines) - count($kept));
                $files[] = basename($logPath);
            }
        }
    }
    
    return [
        'deleted' => $deleted,
        'size_freed' => $sizeFreed,
        'files' => $files
    ];
}

/**
 * MySQL 로그 정리
 */
function cleanupMysqlLogs($days = 7) {
    $logDir = 'C:\\xampp\\mysql\\data';
    if (!is_dir($logDir)) {
        return ['deleted' => 0, 'size_freed' => 0, 'files' => []];
    }
    
    $cutoff = time() - ($days * 24 * 60 * 60);
    $deleted = 0;
    $sizeFreed = 0;
    $files = [];
    
    // .err 파일 찾기
    $errFiles = glob($logDir . '/*.err');
    foreach ($errFiles as $errFile) {
        $fileTime = filemtime($errFile);
        if ($fileTime < $cutoff) {
            $size = filesize($errFile);
            if (@unlink($errFile)) {
                $sizeFreed += $size;
                $deleted++;
                $files[] = basename($errFile);
            }
        }
    }
    
    return [
        'deleted' => $deleted,
        'size_freed' => $sizeFreed,
        'files' => $files
    ];
}

/**
 * 캐시 파일 정리
 */
function cleanupCacheFiles($days = 7) {
    $cacheDir = __DIR__ . '/../../cache';
    if (!is_dir($cacheDir)) {
        return ['deleted' => 0, 'size_freed' => 0, 'files' => []];
    }
    
    $cutoff = time() - ($days * 24 * 60 * 60);
    $deleted = 0;
    $sizeFreed = 0;
    $files = [];
    
    $cacheFiles = glob($cacheDir . '/*.cache');
    foreach ($cacheFiles as $cacheFile) {
        $fileTime = filemtime($cacheFile);
        if ($fileTime < $cutoff) {
            $size = filesize($cacheFile);
            if (@unlink($cacheFile)) {
                $sizeFreed += $size;
                $deleted++;
                $files[] = basename($cacheFile);
            }
        }
    }
    
    return [
        'deleted' => $deleted,
        'size_freed' => $sizeFreed,
        'files' => $files
    ];
}

/**
 * 세션 파일 정리
 */
function cleanupSessionFiles($days = 1) {
    $sessionPath = session_save_path();
    if (empty($sessionPath) || !is_dir($sessionPath)) {
        // 기본 세션 경로 시도
        $sessionPath = sys_get_temp_dir();
    }
    
    if (!is_dir($sessionPath)) {
        return ['deleted' => 0, 'size_freed' => 0, 'files' => []];
    }
    
    $cutoff = time() - ($days * 24 * 60 * 60);
    $deleted = 0;
    $sizeFreed = 0;
    $files = [];
    
    // 세션 파일 찾기 (sess_로 시작)
    $sessionFiles = glob($sessionPath . '/sess_*');
    foreach ($sessionFiles as $sessionFile) {
        $fileTime = filemtime($sessionFile);
        if ($fileTime < $cutoff) {
            $size = filesize($sessionFile);
            if (@unlink($sessionFile)) {
                $sizeFreed += $size;
                $deleted++;
            }
        }
    }
    
    return [
        'deleted' => $deleted,
        'size_freed' => $sizeFreed,
        'files' => []
    ];
}

/**
 * MySQL 바이너리 로그 정리 (expire_logs_days 설정)
 */
function setMysqlBinlogExpiration($days = 7) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("SET GLOBAL expire_logs_days = ?");
        $stmt->execute([$days]);
        return true;
    } catch (PDOException $e) {
        error_log("MySQL 바이너리 로그 설정 실패: " . $e->getMessage());
        return false;
    }
}

/**
 * 로그 파일 크기 확인
 */
function getLogSizes() {
    $sizes = [];
    
    // 커스텀 로그
    $logDir = __DIR__ . '/../../logs';
    if (is_dir($logDir)) {
        $files = glob($logDir . '/*.log');
        foreach ($files as $file) {
            $sizes['custom'][basename($file)] = filesize($file);
        }
    }
    
    // PHP 에러 로그
    $phpLog = ini_get('error_log');
    if ($phpLog && file_exists($phpLog)) {
        $sizes['php_error_log'] = filesize($phpLog);
    }
    
    // Apache 로그
    $apacheErrorLog = 'C:\\xampp\\apache\\logs\\error.log';
    if (file_exists($apacheErrorLog)) {
        $sizes['apache_error_log'] = filesize($apacheErrorLog);
    }
    
    $apacheAccessLog = 'C:\\xampp\\apache\\logs\\access.log';
    if (file_exists($apacheAccessLog)) {
        $sizes['apache_access_log'] = filesize($apacheAccessLog);
    }
    
    // 캐시 파일
    $cacheDir = __DIR__ . '/../../cache';
    if (is_dir($cacheDir)) {
        $cacheFiles = glob($cacheDir . '/*.cache');
        $totalCacheSize = 0;
        foreach ($cacheFiles as $file) {
            $totalCacheSize += filesize($file);
        }
        $sizes['cache'] = $totalCacheSize;
        $sizes['cache_count'] = count($cacheFiles);
    }
    
    return $sizes;
}
