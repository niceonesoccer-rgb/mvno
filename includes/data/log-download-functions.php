<?php
/**
 * 로그 파일 다운로드 관련 함수
 */

/**
 * 로그 파일 경로 가져오기
 */
function getLogFilePath($logType) {
    $baseDir = __DIR__ . '/../..';
    
    $logPaths = [
        'connections' => $baseDir . '/logs/connections.log',
        'sessions' => $baseDir . '/logs/sessions.log',
        'event_debug' => $baseDir . '/logs/event_debug.log',
        'php_error' => ini_get('error_log') ?: 'C:\\xampp\\php\\logs\\php_error_log',
        'apache_error' => 'C:\\xampp\\apache\\logs\\error.log',
        'apache_access' => 'C:\\xampp\\apache\\logs\\access.log',
    ];
    
    return $logPaths[$logType] ?? null;
}

/**
 * 파일 경로로 로그 파일 다운로드
 */
function downloadLogFileByPath($filePath) {
    if (!file_exists($filePath) || !is_readable($filePath)) {
        return false;
    }
    
    $fileName = basename($filePath);
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    
    readfile($filePath);
    return true;
}

/**
 * 모든 로그 파일 목록 가져오기
 */
function getAllLogFiles() {
    $baseDir = __DIR__ . '/../..';
    $logFiles = [];
    
    // 커스텀 로그
    $logDir = $baseDir . '/logs';
    if (is_dir($logDir)) {
        $files = glob($logDir . '/*.log');
        foreach ($files as $file) {
            if (is_file($file)) {
                $logFiles[] = [
                    'type' => 'custom',
                    'name' => basename($file),
                    'path' => $file,
                    'size' => filesize($file),
                    'modified' => filemtime($file)
                ];
            }
        }
    }
    
    // PHP 에러 로그
    $phpLog = ini_get('error_log');
    if ($phpLog && file_exists($phpLog)) {
        $logFiles[] = [
            'type' => 'php_error',
            'name' => basename($phpLog),
            'path' => $phpLog,
            'size' => filesize($phpLog),
            'modified' => filemtime($phpLog)
        ];
    }
    
    // Apache 로그
    $apacheErrorLog = 'C:\\xampp\\apache\\logs\\error.log';
    if (file_exists($apacheErrorLog)) {
        $logFiles[] = [
            'type' => 'apache_error',
            'name' => 'error.log',
            'path' => $apacheErrorLog,
            'size' => filesize($apacheErrorLog),
            'modified' => filemtime($apacheErrorLog)
        ];
    }
    
    $apacheAccessLog = 'C:\\xampp\\apache\\logs\\access.log';
    if (file_exists($apacheAccessLog)) {
        $logFiles[] = [
            'type' => 'apache_access',
            'name' => 'access.log',
            'path' => $apacheAccessLog,
            'size' => filesize($apacheAccessLog),
            'modified' => filemtime($apacheAccessLog)
        ];
    }
    
    // MySQL 로그
    $mysqlLogDir = 'C:\\xampp\\mysql\\data';
    if (is_dir($mysqlLogDir)) {
        $errFiles = glob($mysqlLogDir . '/*.err');
        foreach ($errFiles as $errFile) {
            if (is_file($errFile)) {
                $logFiles[] = [
                    'type' => 'mysql',
                    'name' => basename($errFile),
                    'path' => $errFile,
                    'size' => filesize($errFile),
                    'modified' => filemtime($errFile)
                ];
            }
        }
    }
    
    return $logFiles;
}

/**
 * 모든 로그 파일을 ZIP으로 다운로드
 */
function downloadAllLogsAsZip() {
    if (!class_exists('ZipArchive')) {
        die('ZIP 확장이 설치되어 있지 않습니다.');
    }
    
    $logFiles = getAllLogFiles();
    if (empty($logFiles)) {
        die('다운로드할 로그 파일이 없습니다.');
    }
    
    $zip = new ZipArchive();
    $zipFileName = 'logs_' . date('Y-m-d_His') . '.zip';
    $zipPath = sys_get_temp_dir() . '/' . $zipFileName;
    
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        die('ZIP 파일을 생성할 수 없습니다.');
    }
    
    foreach ($logFiles as $logFile) {
        if (file_exists($logFile['path']) && is_readable($logFile['path'])) {
            // 파일 크기가 100MB 이상이면 스킵 (메모리 문제 방지)
            if ($logFile['size'] > 100 * 1024 * 1024) {
                continue;
            }
            
            $zip->addFile($logFile['path'], $logFile['name']);
        }
    }
    
    $zip->close();
    
    if (!file_exists($zipPath)) {
        die('ZIP 파일 생성에 실패했습니다.');
    }
    
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
    header('Content-Length: ' . filesize($zipPath));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    
    readfile($zipPath);
    unlink($zipPath); // 임시 파일 삭제
    exit;
}
