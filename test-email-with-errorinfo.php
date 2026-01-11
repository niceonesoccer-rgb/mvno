<?php
/**
 * 메일 발송 테스트 (ErrorInfo 포함)
 */

header('Content-Type: text/html; charset=UTF-8');

// 경로 설정
require_once __DIR__ . '/includes/data/path-config.php';

// 메일 헬퍼 포함
require_once __DIR__ . '/includes/data/mail-helper.php';

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>메일 발송 테스트 (ErrorInfo 확인)</title>
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
        pre {
            background: #1f2937;
            color: #f9fafb;
            padding: 16px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 13px;
            line-height: 1.6;
            max-height: 600px;
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
        input[type="email"] {
            width: 100%;
            max-width: 400px;
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 메일 발송 테스트 (ErrorInfo 확인)</h1>
        
        <?php
        // 테스트 이메일 발송
        $testResult = null;
        $testErrorInfo = null;
        $testError = null;
        $testDebugOutput = '';
        
        if (isset($_POST['test_email']) && $_POST['test_email']) {
            $testEmail = filter_var($_POST['test_email'], FILTER_SANITIZE_EMAIL);
            
            if (filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
                // PHPMailer 직접 사용하여 ErrorInfo 확인
                $phpmailerPath = __DIR__ . '/vendor/autoload.php';
                if (file_exists($phpmailerPath)) {
                    require_once $phpmailerPath;
                    
                    try {
                        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                        
                        // SMTP 설정
                        $smtpHost = defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com';
                        $smtpUsername = defined('SMTP_USERNAME') ? SMTP_USERNAME : '';
                        $smtpPort = defined('SMTP_PORT') ? SMTP_PORT : 587;
                        $smtpSecure = defined('SMTP_SECURE') ? SMTP_SECURE : 'tls';
                        $smtpPassword = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '';
                        $smtpFromEmail = defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'noreply@mvno.com';
                        $smtpFromName = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'MVNO';
                        
                        // 출력 버퍼링 시작 (SMTPDebug 출력 캡처)
                        ob_start();
                        
                        $mail->isSMTP();
                        $mail->Host = $smtpHost;
                        $mail->SMTPAuth = true;
                        $mail->Username = $smtpUsername;
                        $mail->Password = $smtpPassword;
                        $mail->SMTPSecure = $smtpSecure;
                        $mail->Port = $smtpPort;
                        $mail->CharSet = 'UTF-8';
                        $mail->SMTPKeepAlive = false;
                        $mail->SMTPOptions = [
                            'ssl' => [
                                'verify_peer' => false,
                                'verify_peer_name' => false,
                                'allow_self_signed' => true
                            ]
                        ];
                        
                        // 디버깅 모드 활성화
                        $mail->SMTPDebug = 2; // 클라이언트 및 서버 메시지
                        $mail->Debugoutput = function($str, $level) {
                            // error_log에도 기록
                            error_log("PHPMailer Debug: " . $str);
                            // 출력 버퍼에도 기록
                            echo $str . "\n";
                        };
                        
                        // 발신자/수신자 설정
                        $mail->setFrom($smtpFromEmail, $smtpFromName);
                        $mail->addAddress($testEmail);
                        $mail->isHTML(true);
                        
                        // 테스트 메일 내용
                        $mail->Subject = '[테스트] 메일 발송 확인';
                        $mail->Body = '<h1>테스트 메일</h1><p>이 메일은 메일 발송 테스트용입니다.</p>';
                        
                        // 발송 시도
                        $testResult = $mail->send();
                        
                        // ErrorInfo 확인
                        $testErrorInfo = $mail->ErrorInfo ?? '';
                        
                        // 출력 버퍼 내용 가져오기
                        $testDebugOutput = ob_get_clean();
                        
                        // ErrorInfo가 있으면 실패로 간주
                        if ($testResult && !empty($testErrorInfo)) {
                            $testResult = false;
                        }
                        
                    } catch (\PHPMailer\PHPMailer\Exception $e) {
                        $testError = $e->getMessage();
                        $testErrorInfo = $mail->ErrorInfo ?? 'N/A';
                        $testDebugOutput = ob_get_clean();
                    } catch (\Exception $e) {
                        $testError = $e->getMessage();
                        $testDebugOutput = ob_get_clean();
                    }
                } else {
                    $testError = 'PHPMailer가 설치되지 않았습니다.';
                }
            } else {
                $testError = '올바른 이메일 주소를 입력해주세요.';
            }
        }
        ?>
        
        <!-- 테스트 이메일 발송 -->
        <div class="info-box">
            <form method="POST" style="margin-bottom: 16px;">
                <div style="margin-bottom: 12px;">
                    <label>테스트 이메일 주소:</label>
                    <input 
                        type="email" 
                        name="test_email" 
                        value=""
                        placeholder="test@example.com" 
                        required
                    >
                </div>
                <button type="submit" class="btn btn-success">테스트 메일 발송</button>
                <a href="?" class="btn">새로고침</a>
            </form>
            
            <?php if ($testResult !== null): ?>
                <div class="info-box <?php echo $testResult && empty($testErrorInfo) ? 'success' : 'error'; ?>">
                    <strong><?php echo $testResult && empty($testErrorInfo) ? '✅ 테스트 메일 발송 성공' : '❌ 테스트 메일 발송 실패'; ?></strong>
                    <br><br>
                    <strong>send() 반환값:</strong> <?php echo $testResult ? 'true' : 'false'; ?>
                    <?php if (!empty($testErrorInfo)): ?>
                        <br><br>
                        <strong>ErrorInfo:</strong>
                        <pre style="margin-top: 8px; background: #1f2937; color: #fca5a5;"><?php echo htmlspecialchars($testErrorInfo); ?></pre>
                    <?php endif; ?>
                    <?php if ($testError): ?>
                        <br><br>
                        <strong>예외 메시지:</strong>
                        <pre style="margin-top: 8px; background: #1f2937; color: #fca5a5;"><?php echo htmlspecialchars($testError); ?></pre>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- SMTP 디버그 출력 -->
        <?php if ($testDebugOutput): ?>
        <div class="info-box">
            <h2>📋 SMTP 디버그 출력</h2>
            <pre><?php echo htmlspecialchars($testDebugOutput); ?></pre>
        </div>
        <?php endif; ?>
        
        <!-- 안내 -->
        <div class="info-box warning">
            <p><strong>💡 참고 사항:</strong></p>
            <ul style="margin-left: 20px;">
                <li><code>send()</code> 메서드가 <code>true</code>를 반환해도 <code>ErrorInfo</code>가 있으면 실제로는 발송 실패일 수 있습니다.</li>
                <li><code>ErrorInfo</code>에는 PHPMailer의 상세한 오류 메시지가 포함되어 있습니다.</li>
                <li>위의 "SMTP 디버그 출력"에는 SMTP 서버와의 통신 내용이 기록되어 있습니다.</li>
            </ul>
        </div>
    </div>
</body>
</html>
