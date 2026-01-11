<?php
/**
 * 이메일 발송 디버깅 테스트 스크립트
 */

// 경로 설정
require_once __DIR__ . '/includes/data/path-config.php';

// 세션 시작
session_start();

// 인증 함수 포함
require_once __DIR__ . '/includes/data/auth-functions.php';

// 메일 헬퍼 포함
require_once __DIR__ . '/includes/data/mail-helper.php';

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>이메일 발송 디버깅</title>
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
            background: #d1fae5;
            border-color: #10b981;
            color: #065f46;
        }
        .info-box.error {
            background: #fee2e2;
            border-color: #ef4444;
            color: #991b1b;
        }
        .info-box.warning {
            background: #fef3c7;
            border-color: #f59e0b;
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
            width: 200px;
            color: #6b7280;
        }
        .info-value {
            flex: 1;
            color: #1f2937;
            word-break: break-all;
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
        .btn-secondary {
            background: #6b7280;
        }
        .btn-secondary:hover {
            background: #4b5563;
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
        .log-section {
            margin-top: 16px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 이메일 발송 디버깅</h1>
        
        <?php
        // 현재 사용자 확인
        $currentUser = getCurrentUser();
        $isLoggedIn = isLoggedIn();
        
        // 환경 정보
        $host = $_SERVER['HTTP_HOST'] ?? 'unknown';
        $isLocalhost = (
            strpos($host, 'localhost') !== false || 
            strpos($host, '127.0.0.1') !== false ||
            strpos($host, '::1') !== false
        );
        
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
        
        // 테스트 이메일 발송
        $testResult = null;
        $testError = null;
        
        if (isset($_POST['test_email']) && $_POST['test_email']) {
            $testEmail = filter_var($_POST['test_email'], FILTER_SANITIZE_EMAIL);
            
            if (filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
                // 테스트 인증번호 생성
                $testCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                
                error_log("=== 이메일 발송 테스트 시작 ===");
                error_log("테스트 이메일: {$testEmail}");
                error_log("테스트 인증번호: {$testCode}");
                
                try {
                    $testResult = sendVerificationEmail($testEmail, $testCode, 'email_change', '테스트');
                    error_log("테스트 결과: " . ($testResult ? '성공' : '실패'));
                    error_log("=== 이메일 발송 테스트 종료 ===");
                } catch (Exception $e) {
                    $testError = $e->getMessage();
                    error_log("테스트 예외: " . $testError);
                }
            } else {
                $testError = '올바른 이메일 주소를 입력해주세요.';
            }
        }
        
        // 로그 파일 확인 (프로덕션 환경에서는 ini_get('error_log') 사용)
        $phpLogPath = ini_get('error_log');
        if (empty($phpLogPath) || !file_exists($phpLogPath)) {
            // 대체 경로 시도
            $phpLogPath = 'C:/xampp/php/logs/php_error_log';
            if (!file_exists($phpLogPath)) {
                $phpLogPath = '/var/log/php_errors.log';
                if (!file_exists($phpLogPath)) {
                    $phpLogPath = null;
                }
            }
        }
        $apacheLogPath = 'C:/xampp/apache/logs/error.log';
        if (!file_exists($apacheLogPath)) {
            $apacheLogPath = '/var/log/apache2/error.log';
            if (!file_exists($apacheLogPath)) {
                $apacheLogPath = null;
            }
        }
        
        $phpLogExists = $phpLogPath && file_exists($phpLogPath);
        $apacheLogExists = $apacheLogPath && file_exists($apacheLogPath);
        
        $recentLogs = '';
        if ($phpLogExists) {
            $phpLogContent = file_get_contents($phpLogPath);
            $phpLogLines = explode("\n", $phpLogContent);
            $emailLogLines = array_filter($phpLogLines, function($line) {
                return stripos($line, 'email') !== false || 
                       stripos($line, 'mail') !== false || 
                       stripos($line, 'smtp') !== false ||
                       stripos($line, 'sendEmail') !== false ||
                       stripos($line, 'sendVerificationEmail') !== false ||
                       stripos($line, 'PHPMailer') !== false ||
                       stripos($line, 'SMTP') !== false;
            });
            $recentEmailLogs = array_slice($emailLogLines, -100); // 최근 100줄
            $recentLogs = implode("\n", $recentEmailLogs);
        }
        ?>
        
        <!-- 환경 정보 -->
        <h2>🌐 환경 정보</h2>
        <div class="info-box">
            <div class="info-row">
                <div class="info-label">호스트:</div>
                <div class="info-value"><?php echo htmlspecialchars($host); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">환경:</div>
                <div class="info-value"><?php echo $isLocalhost ? '로컬 (개발)' : '프로덕션 (실제 서버)'; ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">로그인 상태:</div>
                <div class="info-value">
                    <?php if ($isLoggedIn && $currentUser): ?>
                        로그인됨 (<?php echo htmlspecialchars($currentUser['user_id']); ?>)
                    <?php else: ?>
                        로그인 안됨
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- 메일 설정 정보 -->
        <h2>⚙️ 메일 설정</h2>
        <div class="info-box">
            <div class="info-row">
                <div class="info-label">메일 방식:</div>
                <div class="info-value"><?php echo htmlspecialchars($mailMethod); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">PHPMailer:</div>
                <div class="info-value">
                    <?php echo $phpmailerExists ? '✅ 설치됨' : '❌ 설치 안됨'; ?>
                    <?php if ($phpmailerExists): ?>
                        <br><small style="color: #6b7280;">경로: <?php echo htmlspecialchars($phpmailerPath); ?></small>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($mailMethod === 'smtp' || $mailMethod === 'auto'): ?>
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
            <?php endif; ?>
            <div class="info-row">
                <div class="info-label">발신자 이메일:</div>
                <div class="info-value"><?php echo htmlspecialchars($smtpFromEmail); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">발신자 이름:</div>
                <div class="info-value"><?php echo htmlspecialchars($smtpFromName); ?></div>
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
                        value="<?php echo $isLoggedIn && $currentUser ? htmlspecialchars($currentUser['email'] ?? '') : ''; ?>"
                        placeholder="test@example.com" 
                        required
                        style="width: 100%; max-width: 400px; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;"
                    >
                </div>
                <button type="submit" class="btn">테스트 이메일 발송</button>
                <a href="?" class="btn btn-secondary" style="text-decoration: none; display: inline-block;">새로고침</a>
            </form>
            
            <?php if ($testResult !== null): ?>
                <div class="info-box <?php echo $testResult ? 'success' : 'error'; ?>">
                    <strong><?php echo $testResult ? '✅ 이메일 발송 성공' : '❌ 이메일 발송 실패'; ?></strong>
                    <br>
                    <?php if ($testResult): ?>
                        이메일이 발송되었습니다. 받은편지함(또는 스팸함)을 확인해주세요.
                        <br><small>로그에 상세한 발송 정보가 기록되었습니다.</small>
                    <?php else: ?>
                        이메일 발송에 실패했습니다. 아래 로그를 확인해주세요.
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($testError): ?>
                <div class="info-box error">
                    <strong>오류:</strong> <?php echo htmlspecialchars($testError); ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- 로그 파일 정보 -->
        <h2>📋 로그 파일</h2>
        <div class="info-box">
            <div class="info-row">
                <div class="info-label">PHP 오류 로그:</div>
                <div class="info-value">
                    <?php if ($phpLogExists): ?>
                        ✅ 존재
                        <br><small style="color: #6b7280;">경로: <?php echo htmlspecialchars($phpLogPath); ?></small>
                        <br><small style="color: #6b7280;">크기: <?php echo number_format(filesize($phpLogPath)); ?> bytes</small>
                    <?php else: ?>
                        ❌ 없음
                        <?php if ($phpLogPath): ?>
                            <br><small style="color: #dc2626;">시도한 경로: <?php echo htmlspecialchars($phpLogPath); ?></small>
                        <?php else: ?>
                            <br><small style="color: #dc2626;">PHP error_log 설정이 없습니다. ini_get('error_log'): <?php echo htmlspecialchars(ini_get('error_log') ?: 'null'); ?></small>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">Apache 오류 로그:</div>
                <div class="info-value">
                    <?php if ($apacheLogExists): ?>
                        ✅ 존재
                        <br><small style="color: #6b7280;">경로: <?php echo htmlspecialchars($apacheLogPath); ?></small>
                        <br><small style="color: #6b7280;">크기: <?php echo number_format(filesize($apacheLogPath)); ?> bytes</small>
                    <?php else: ?>
                        ❌ 없음
                        <?php if ($apacheLogPath): ?>
                            <br><small style="color: #dc2626;">시도한 경로: <?php echo htmlspecialchars($apacheLogPath); ?></small>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- 최근 이메일 관련 로그 -->
        <?php if ($recentLogs): ?>
        <h2>📝 최근 이메일 관련 로그 (최근 100줄)</h2>
        <div class="log-section">
            <pre><?php echo htmlspecialchars($recentLogs); ?></pre>
        </div>
        <?php else: ?>
        <h2>📝 최근 이메일 관련 로그</h2>
        <div class="info-box warning">
            이메일 관련 로그가 없습니다. 테스트 이메일을 발송하면 로그가 생성됩니다.
            <?php if (!$phpLogExists): ?>
                <br><small style="color: #dc2626;">PHP 오류 로그 파일을 찾을 수 없습니다. 로그가 기록되지 않을 수 있습니다.</small>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- 디버깅 안내 -->
        <h2>💡 디버깅 안내</h2>
        <div class="info-box">
            <p><strong>1. 테스트 이메일 발송:</strong></p>
            <p>위의 "테스트 이메일 발송" 버튼을 클릭하여 실제 이메일 발송을 테스트할 수 있습니다.</p>
            
            <p style="margin-top: 16px;"><strong>2. 로그 확인:</strong></p>
            <p>이메일 발송 시도 시 PHP 오류 로그에 상세한 정보가 기록됩니다:</p>
            <ul style="margin-left: 20px;">
                <li>어떤 메일 방식(SMTP/mail())이 사용되는지</li>
                <li>SMTP 설정이 무엇인지</li>
                <li>이메일 발송이 성공했는지 실패했는지</li>
                <li>실패한 경우 어떤 오류가 발생했는지</li>
            </ul>
            
            <p style="margin-top: 16px;"><strong>3. 문제 해결:</strong></p>
            <ul style="margin-left: 20px;">
                <li>SMTP 사용 시: SMTP 설정(호스트, 포트, 사용자명, 비밀번호)이 올바른지 확인</li>
                <li>mail() 함수 사용 시: 호스팅 업체에서 mail() 함수가 활성화되어 있는지 확인</li>
                <li>프로덕션 환경에서는 이메일 발송 실패 시 오류 메시지가 반환됩니다</li>
            </ul>
        </div>
    </div>
</body>
</html>
