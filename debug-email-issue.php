<?php
/**
 * 메일 발송 문제 진단 스크립트
 * 오전 5시 53분 이후 발송 실패 원인 파악
 */

header('Content-Type: text/html; charset=UTF-8');

// 경로 설정
require_once __DIR__ . '/includes/data/path-config.php';

// 세션 시작
session_start();

// 메일 헬퍼 포함
require_once __DIR__ . '/includes/data/mail-helper.php';

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>메일 발송 문제 진단</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Noto Sans KR", Arial, sans-serif;
            max-width: 1200px;
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
            max-height: 500px;
            overflow-y: auto;
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
            text-decoration: none;
            display: inline-block;
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
        .log-entry {
            padding: 8px;
            margin: 4px 0;
            border-left: 4px solid #e5e7eb;
            background: #f9fafb;
        }
        .log-entry.error {
            border-left-color: #ef4444;
            background: #fef2f2;
        }
        .log-entry.success {
            border-left-color: #10b981;
            background: #f0fdf4;
        }
        .log-entry.warning {
            border-left-color: #f59e0b;
            background: #fffbeb;
        }
        .time-filter {
            margin: 16px 0;
            padding: 16px;
            background: #f9fafb;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 메일 발송 문제 진단</h1>
        
        <?php
        // 현재 시간 및 설정 확인
        $currentTime = date('Y-m-d H:i:s');
        $host = $_SERVER['HTTP_HOST'] ?? 'unknown';
        $isProduction = strpos($host, 'ganadamobile.co.kr') !== false;
        
        // 메일 설정 정보
        $mailMethod = defined('MAIL_METHOD') ? MAIL_METHOD : 'mail';
        $smtpHost = defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com';
        $smtpPort = defined('SMTP_PORT') ? SMTP_PORT : 587;
        $smtpSecure = defined('SMTP_SECURE') ? SMTP_SECURE : 'tls';
        $smtpUsername = defined('SMTP_USERNAME') ? SMTP_USERNAME : '';
        $smtpPassword = defined('SMTP_PASSWORD') ? (strlen(SMTP_PASSWORD) > 0 ? '***설정됨***' : '설정 안됨') : '설정 안됨';
        $smtpFromEmail = defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'noreply@mvno.com';
        $smtpFromName = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'MVNO';
        
        // PHPMailer 확인
        $phpmailerPath = __DIR__ . '/vendor/autoload.php';
        $phpmailerExists = file_exists($phpmailerPath);
        
        // 로그 파일 경로 확인
        $phpLogPath = ini_get('error_log');
        if (empty($phpLogPath) || !file_exists($phpLogPath)) {
            $phpLogPath = $isProduction ? '/var/log/php_errors.log' : 'C:/xampp/php/logs/php_error_log';
            if (!file_exists($phpLogPath)) {
                $phpLogPath = null;
            }
        }
        
        // 테스트 이메일 발송
        $testResult = null;
        $testError = null;
        $testLogs = '';
        
        if (isset($_POST['test_email']) && $_POST['test_email']) {
            $testEmail = filter_var($_POST['test_email'], FILTER_SANITIZE_EMAIL);
            
            if (filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
                // 출력 버퍼링 시작 (SMTPDebug 출력 캡처)
                ob_start();
                
                // 테스트 인증번호 생성
                $testCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                
                error_log("=== 메일 발송 테스트 시작 (진단) ===");
                error_log("테스트 이메일: {$testEmail}");
                error_log("테스트 인증번호: {$testCode}");
                error_log("현재 시간: {$currentTime}");
                
                try {
                    $testResult = sendVerificationEmail($testEmail, $testCode, 'email_change', '테스트');
                    error_log("테스트 결과: " . ($testResult ? '성공' : '실패'));
                } catch (Exception $e) {
                    $testError = $e->getMessage();
                    error_log("테스트 예외: " . $testError);
                }
                
                error_log("=== 메일 발송 테스트 종료 (진단) ===");
                
                // 출력 버퍼 내용 가져오기
                $testLogs = ob_get_clean();
            } else {
                $testError = '올바른 이메일 주소를 입력해주세요.';
            }
        }
        
        // 로그 파일에서 메일 관련 로그 추출 (오전 5시 53분 이후)
        $emailLogs = [];
        if ($phpLogPath && file_exists($phpLogPath) && is_readable($phpLogPath)) {
            $logContent = file_get_contents($phpLogPath);
            $lines = explode("\n", $logContent);
            
            // 오전 5시 53분 기준 시간 (2026-01-11 05:53:00)
            $thresholdTime = strtotime('2026-01-11 05:53:00');
            
            foreach ($lines as $line) {
                // 메일 관련 로그만 필터링
                if (stripos($line, 'email') !== false || 
                    stripos($line, 'mail') !== false || 
                    stripos($line, 'smtp') !== false ||
                    stripos($line, 'sendEmail') !== false ||
                    stripos($line, 'sendVerificationEmail') !== false ||
                    stripos($line, 'PHPMailer') !== false ||
                    stripos($line, 'SMTP') !== false) {
                    
                    // 로그 시간 추출 (형식에 따라 다를 수 있음)
                    $logTime = null;
                    if (preg_match('/\[([^\]]+)\]/', $line, $matches)) {
                        $logTime = strtotime($matches[1]);
                    } elseif (preg_match('/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/', $line, $matches)) {
                        $logTime = strtotime($matches[1]);
                    }
                    
                    // 오전 5시 53분 이후 로그만 포함
                    if ($logTime === null || $logTime >= $thresholdTime) {
                        $emailLogs[] = [
                            'time' => $logTime,
                            'line' => $line
                        ];
                    }
                }
            }
            
            // 시간순 정렬 (최신순)
            usort($emailLogs, function($a, $b) {
                if ($a['time'] === null && $b['time'] === null) return 0;
                if ($a['time'] === null) return 1;
                if ($b['time'] === null) return -1;
                return $b['time'] - $a['time'];
            });
            
            // 최근 100개만 표시
            $emailLogs = array_slice($emailLogs, 0, 100);
        }
        ?>
        
        <!-- 현재 상태 -->
        <h2>📊 현재 상태</h2>
        <div class="info-box">
            <div class="info-row">
                <div class="info-label">현재 시간:</div>
                <div class="info-value"><?php echo htmlspecialchars($currentTime); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">환경:</div>
                <div class="info-value"><?php echo $isProduction ? '프로덕션 (ganadamobile.co.kr)' : '로컬 (localhost)'; ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">메일 방식:</div>
                <div class="info-value"><?php echo htmlspecialchars($mailMethod); ?></div>
            </div>
        </div>
        
        <!-- 메일 설정 -->
        <h2>⚙️ 메일 설정</h2>
        <div class="info-box">
            <div class="info-row">
                <div class="info-label">SMTP 호스트:</div>
                <div class="info-value"><?php echo htmlspecialchars($smtpHost); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">SMTP 포트:</div>
                <div class="info-value"><?php echo htmlspecialchars($smtpPort); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">SMTP 보안:</div>
                <div class="info-value"><?php echo htmlspecialchars($smtpSecure); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">SMTP 사용자명:</div>
                <div class="info-value"><?php echo htmlspecialchars($smtpUsername ?: '설정 안됨'); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">SMTP 비밀번호:</div>
                <div class="info-value"><?php echo htmlspecialchars($smtpPassword); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">발신자 이메일:</div>
                <div class="info-value"><?php echo htmlspecialchars($smtpFromEmail); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">PHPMailer:</div>
                <div class="info-value">
                    <?php echo $phpmailerExists ? '✅ 설치됨' : '❌ 설치 안됨'; ?>
                </div>
            </div>
        </div>
        
        <!-- 테스트 이메일 발송 -->
        <h2>🧪 테스트 이메일 발송</h2>
        <div class="info-box">
            <form method="POST" style="margin-bottom: 16px;">
                <div style="margin-bottom: 12px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px;">테스트 이메일 주소:</label>
                    <input 
                        type="email" 
                        name="test_email" 
                        value=""
                        placeholder="test@example.com" 
                        required
                        style="width: 100%; max-width: 400px; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;"
                    >
                </div>
                <button type="submit" class="btn btn-success">테스트 메일 발송</button>
                <a href="?" class="btn">새로고침</a>
            </form>
            
            <?php if ($testResult !== null): ?>
                <div class="info-box <?php echo $testResult ? 'success' : 'error'; ?>">
                    <strong><?php echo $testResult ? '✅ 테스트 메일 발송 성공' : '❌ 테스트 메일 발송 실패'; ?></strong>
                    <?php if (!$testResult && $testError): ?>
                        <br><strong>오류:</strong> <?php echo htmlspecialchars($testError); ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($testLogs): ?>
                <div style="margin-top: 16px;">
                    <strong>SMTP 디버그 출력:</strong>
                    <pre><?php echo htmlspecialchars($testLogs); ?></pre>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- 최근 메일 관련 로그 (오전 5시 53분 이후) -->
        <h2>📝 최근 메일 관련 로그 (오전 5시 53분 이후)</h2>
        <div class="info-box">
            <?php if (empty($emailLogs)): ?>
                <p>오전 5시 53분 이후 메일 관련 로그가 없습니다.</p>
                <?php if (!$phpLogPath || !file_exists($phpLogPath)): ?>
                    <p style="color: #dc2626;">로그 파일을 찾을 수 없습니다: <?php echo htmlspecialchars($phpLogPath ?: '경로 없음'); ?></p>
                <?php endif; ?>
            <?php else: ?>
                <p>총 <?php echo count($emailLogs); ?>개의 로그 항목이 있습니다.</p>
                <div style="max-height: 600px; overflow-y: auto; margin-top: 16px;">
                    <?php foreach ($emailLogs as $log): ?>
                        <div class="log-entry <?php 
                            echo stripos($log['line'], '실패') !== false || stripos($log['line'], 'fail') !== false || stripos($log['line'], 'error') !== false ? 'error' : 
                            (stripos($log['line'], '성공') !== false || stripos($log['line'], 'success') !== false ? 'success' : 'warning');
                        ?>">
                            <?php if ($log['time']): ?>
                                <small style="color: #6b7280;"><?php echo date('Y-m-d H:i:s', $log['time']); ?></small><br>
                            <?php endif; ?>
                            <code style="font-size: 12px;"><?php echo htmlspecialchars($log['line']); ?></code>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- 문제 해결 방법 -->
        <h2>💡 문제 해결 방법</h2>
        <div class="info-box">
            <p><strong>오전 5시 53분 이후 메일 발송이 안되는 일반적인 원인:</strong></p>
            <ul style="margin-left: 20px;">
                <li><strong>SMTP 서버 연결 제한:</strong> 일일 발송 한도 초과 또는 IP 차단</li>
                <li><strong>인증 정보 변경:</strong> SMTP 비밀번호 변경 또는 계정 정지</li>
                <li><strong>네트워크 문제:</strong> 방화벽 규칙 변경 또는 네트워크 장애</li>
                <li><strong>시간대별 제한:</strong> 특정 시간대 발송 제한 정책</li>
                <li><strong>SSL/TLS 인증서 문제:</strong> SMTP 서버의 인증서 만료 또는 변경</li>
            </ul>
            
            <p style="margin-top: 16px;"><strong>확인 사항:</strong></p>
            <ol style="margin-left: 20px;">
                <li>위의 "테스트 메일 발송" 버튼으로 실제 발송 시도</li>
                <li>로그에서 오류 메시지 확인 (특히 SMTP 연결 오류)</li>
                <li>SMTP 서버 관리자에게 일일 발송 한도 확인</li>
                <li>SMTP 비밀번호 및 계정 상태 확인</li>
                <li>네트워크 연결 상태 확인</li>
            </ol>
        </div>
    </div>
</body>
</html>
