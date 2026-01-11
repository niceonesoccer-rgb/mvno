<?php
/**
 * PHP 오류 로그 설정 확인 및 테스트 스크립트
 */

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP 오류 로그 확인 및 설정</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Noto Sans KR", Arial, sans-serif;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f7fb;
        }
        .container {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        h1 {
            color: #1f2937;
            margin-bottom: 24px;
        }
        h2 {
            color: #374151;
            margin-top: 24px;
            margin-bottom: 16px;
            font-size: 20px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 8px;
        }
        .info-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 16px;
        }
        .info-box.success {
            background: #f0fdf4;
            border-color: #86efac;
            color: #166534;
        }
        .info-box.error {
            background: #fef2f2;
            border-color: #fca5a5;
            color: #991b1b;
        }
        .info-box.warning {
            background: #fffbeb;
            border-color: #fcd34d;
            color: #92400e;
        }
        .info-row {
            display: flex;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            width: 250px;
            color: #6b7280;
        }
        .info-value {
            flex: 1;
            color: #1f2937;
            word-break: break-all;
        }
        pre {
            background: #1f2937;
            color: #f9fafb;
            padding: 16px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 13px;
            line-height: 1.6;
        }
        .btn {
            background: #6366f1;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            margin-right: 8px;
            margin-bottom: 8px;
        }
        .btn:hover {
            background: #4f46e5;
        }
        .btn-success {
            background: #10b981;
        }
        .btn-success:hover {
            background: #059669;
        }
        .btn-danger {
            background: #ef4444;
        }
        .btn-danger:hover {
            background: #dc2626;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📋 PHP 오류 로그 설정 확인</h1>
        
        <?php
        // PHP 설정 확인
        $logErrors = ini_get('log_errors');
        $displayErrors = ini_get('display_errors');
        $errorReporting = ini_get('error_reporting');
        $errorLog = ini_get('error_log');
        $errorLogDisplay = $errorLog ?: '(설정 안됨 - 시스템 기본값 사용)';
        
        // 로그 파일 경로 확인
        $defaultLogPath = 'C:/xampp/php/logs/php_error_log';
        $logDir = 'C:/xampp/php/logs';
        $logFile = $defaultLogPath;
        
        $logDirExists = is_dir($logDir);
        $logFileExists = file_exists($logFile);
        $logFileWritable = false;
        $logDirWritable = false;
        
        if ($logDirExists) {
            $logDirWritable = is_writable($logDir);
        }
        
        if ($logFileExists) {
            $logFileWritable = is_writable($logFile);
            $logFileSize = filesize($logFile);
            $logFileModified = filemtime($logFile);
        } else {
            // 로그 파일이 없으면 생성 시도
            if ($logDirExists && $logDirWritable) {
                @touch($logFile);
                $logFileExists = file_exists($logFile);
                if ($logFileExists) {
                    $logFileWritable = is_writable($logFile);
                }
            }
        }
        
        // 테스트 로그 기록
        $testLogResult = null;
        if (isset($_GET['test_log'])) {
            $testMessage = date('Y-m-d H:i:s') . " - PHP 오류 로그 테스트 메시지\n";
            $result = @error_log($testMessage, 3, $logFile);
            if ($result) {
                $testLogResult = true;
            } else {
                $testLogResult = false;
            }
        }
        ?>
        
        <!-- PHP 설정 정보 -->
        <h2>⚙️ PHP 설정 정보</h2>
        <div class="info-box">
            <div class="info-row">
                <div class="info-label">log_errors:</div>
                <div class="info-value">
                    <?php echo htmlspecialchars($logErrors); ?>
                    <?php if ($logErrors == '1' || $logErrors == 'On'): ?>
                        <span style="color: #10b981;">✅ 활성화됨</span>
                    <?php else: ?>
                        <span style="color: #ef4444;">❌ 비활성화됨</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">display_errors:</div>
                <div class="info-value">
                    <?php echo htmlspecialchars($displayErrors); ?>
                    <?php if ($displayErrors == '1' || $displayErrors == 'On'): ?>
                        <span style="color: #f59e0b;">⚠️ 화면에 표시됨</span>
                    <?php else: ?>
                        <span style="color: #10b981;">✅ 화면에 표시 안됨 (권장)</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">error_reporting:</div>
                <div class="info-value">
                    <?php echo htmlspecialchars($errorReporting); ?>
                    (<?php echo error_reporting(); ?>)
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">error_log (php.ini):</div>
                <div class="info-value">
                    <?php echo htmlspecialchars($errorLogDisplay); ?>
                </div>
            </div>
        </div>
        
        <!-- 로그 파일 정보 -->
        <h2>📁 로그 파일 정보</h2>
        <div class="info-box <?php echo $logFileExists && $logFileWritable ? 'success' : ($logFileExists ? 'warning' : 'error'); ?>">
            <div class="info-row">
                <div class="info-label">로그 디렉토리:</div>
                <div class="info-value">
                    <?php echo htmlspecialchars($logDir); ?>
                    <?php if ($logDirExists): ?>
                        <span style="color: #10b981;">✅ 존재</span>
                        <?php if ($logDirWritable): ?>
                            <span style="color: #10b981;">✅ 쓰기 가능</span>
                        <?php else: ?>
                            <span style="color: #ef4444;">❌ 쓰기 불가능</span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span style="color: #ef4444;">❌ 존재하지 않음</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">로그 파일:</div>
                <div class="info-value">
                    <?php echo htmlspecialchars($logFile); ?>
                    <?php if ($logFileExists): ?>
                        <span style="color: #10b981;">✅ 존재</span>
                        <?php if ($logFileWritable): ?>
                            <span style="color: #10b981;">✅ 쓰기 가능</span>
                            <?php if (isset($logFileSize)): ?>
                                <br><small style="color: #6b7280;">
                                    크기: <?php echo number_format($logFileSize); ?> bytes
                                    | 수정일: <?php echo date('Y-m-d H:i:s', $logFileModified); ?>
                                </small>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="color: #ef4444;">❌ 쓰기 불가능</span>
                            <br><small style="color: #dc2626;">권한이 없어 로그를 기록할 수 없습니다.</small>
                        <?php endif; ?>
                    <?php else: ?>
                        <span style="color: #ef4444;">❌ 존재하지 않음</span>
                        <?php if ($logDirExists && $logDirWritable): ?>
                            <br><small style="color: #059669;">디렉토리는 쓰기 가능하므로 파일을 생성할 수 있습니다.</small>
                        <?php else: ?>
                            <br><small style="color: #dc2626;">디렉토리가 없거나 쓰기 권한이 없어 파일을 생성할 수 없습니다.</small>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- 테스트 결과 -->
        <?php if ($testLogResult !== null): ?>
        <div class="info-box <?php echo $testLogResult ? 'success' : 'error'; ?>">
            <strong><?php echo $testLogResult ? '✅ 테스트 로그 기록 성공' : '❌ 테스트 로그 기록 실패'; ?></strong>
            <?php if ($testLogResult): ?>
                <br>로그 파일에 테스트 메시지가 기록되었습니다.
            <?php else: ?>
                <br>로그 파일에 메시지를 기록할 수 없습니다. 권한을 확인해주세요.
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- 테스트 로그 기록 -->
        <?php if ($logFileExists && $logFileWritable): ?>
        <h2>🧪 테스트</h2>
        <div class="info-box">
            <p>로그 파일에 테스트 메시지를 기록하여 로그 기능이 정상 작동하는지 확인할 수 있습니다.</p>
            <a href="?test_log=1" class="btn btn-success">테스트 로그 기록</a>
            <a href="?" class="btn">새로고침</a>
        </div>
        <?php endif; ?>
        
        <!-- 최근 로그 내용 -->
        <?php if ($logFileExists && is_readable($logFile)): ?>
        <h2>📝 최근 로그 내용 (최근 50줄)</h2>
        <div class="info-box">
            <?php
            $logContent = @file_get_contents($logFile);
            if ($logContent !== false) {
                $lines = explode("\n", $logContent);
                $recentLines = array_slice($lines, -50);
                if (count($recentLines) > 0) {
                    echo '<pre>' . htmlspecialchars(implode("\n", $recentLines)) . '</pre>';
                } else {
                    echo '<p>로그 파일이 비어있습니다.</p>';
                }
            } else {
                echo '<p style="color: #dc2626;">로그 파일을 읽을 수 없습니다.</p>';
            }
            ?>
        </div>
        <?php endif; ?>
        
        <!-- 설정 안내 -->
        <h2>💡 설정 안내</h2>
        <div class="info-box">
            <p><strong>php.ini 설정 확인:</strong></p>
            <p>PHP 오류 로그가 정상 작동하려면 다음 설정이 필요합니다:</p>
            <ul style="margin-left: 20px;">
                <li><code>log_errors = On</code> - 오류 로그 기록 활성화</li>
                <li><code>error_log = C:/xampp/php/logs/php_error_log</code> - 로그 파일 경로</li>
                <li><code>error_reporting = E_ALL</code> - 모든 오류 보고</li>
            </ul>
            
            <p style="margin-top: 16px;"><strong>php.ini 파일 위치:</strong></p>
            <p><code>C:\xampp\php\php.ini</code></p>
            <p><small>설정을 변경한 후 Apache를 재시작해야 적용됩니다.</small></p>
        </div>
    </div>
</body>
</html>
