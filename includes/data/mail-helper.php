<?php
/**
 * 이메일 발송 헬퍼 함수
 */

// 설정 파일 로드
if (file_exists(__DIR__ . '/mail-config.php')) {
    require_once __DIR__ . '/mail-config.php';
}

/**
 * 이메일 발송 함수 (SMTP 또는 기본 mail 함수 사용)
 * 
 * @param string $to 수신자 이메일
 * @param string $subject 메일 제목
 * @param string $message 메일 내용 (HTML)
 * @param string $from 발신자 이메일 (선택)
 * @return bool 발송 성공 여부
 */
function sendEmail($to, $subject, $message, $from = null) {
    // 설정 확인
    $mailMethod = defined('MAIL_METHOD') ? MAIL_METHOD : 'mail';
    
    // 환경 자동 감지
    $isLocalhost = (
        strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false ||
        strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false ||
        strpos($_SERVER['HTTP_HOST'] ?? '', '::1') !== false
    );
    
    // 'auto' 모드: 환경에 따라 자동 선택
    if ($mailMethod === 'auto') {
        // 로컬 환경이고 PHPMailer가 있으면 SMTP 시도, 없으면 mail() 사용
        $phpmailerPath = __DIR__ . '/../../vendor/autoload.php';
        if ($isLocalhost && file_exists($phpmailerPath)) {
            $mailMethod = 'smtp';
        } else {
            $mailMethod = 'mail';
        }
    }
    
    if ($mailMethod === 'smtp' && function_exists('sendEmailViaSMTP')) {
        // SMTP 사용
        $fromEmail = defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : ($from ?: 'noreply@mvno.com');
        $fromName = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'MVNO';
        return sendEmailViaSMTP($to, $subject, $message, $fromEmail, $fromName);
    } else {
        // 기본 mail() 함수 사용 (호스팅에서 대부분 작동)
        if (empty($from)) {
            $from = defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'noreply@mvno.com';
        }
        
        // 헤더 설정
        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=UTF-8';
        $headers[] = 'From: ' . $from;
        $headers[] = 'Reply-To: ' . $from;
        $headers[] = 'X-Mailer: PHP/' . phpversion();
        
        $headersString = implode("\r\n", $headers);
        
        // 이메일 발송
        $result = @mail($to, $subject, $message, $headersString);
        
        // 로그 기록
        if (!$result) {
            error_log("이메일 발송 실패: {$to} - {$subject}");
        } else {
            error_log("이메일 발송 성공: {$to} - {$subject}");
        }
        
        return $result;
    }
}

/**
 * SMTP를 통한 이메일 발송 함수
 * PHPMailer가 설치되어 있으면 사용, 없으면 기본 mail() 함수 사용
 * 
 * @param string $to 수신자 이메일
 * @param string $subject 메일 제목
 * @param string $message 메일 내용 (HTML)
 * @param string $fromEmail 발신자 이메일
 * @param string $fromName 발신자 이름
 * @return bool 발송 성공 여부
 */
function sendEmailViaSMTP($to, $subject, $message, $fromEmail, $fromName) {
    // PHPMailer 사용 시도
    $phpmailerPath = __DIR__ . '/../../vendor/autoload.php';
    if (file_exists($phpmailerPath)) {
        require_once $phpmailerPath;
        
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // SMTP 설정
            $mail->isSMTP();
            $mail->Host = defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = defined('SMTP_USERNAME') ? SMTP_USERNAME : '';
            $mail->Password = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '';
            $mail->SMTPSecure = defined('SMTP_SECURE') ? SMTP_SECURE : 'tls';
            $mail->Port = defined('SMTP_PORT') ? SMTP_PORT : 587;
            $mail->CharSet = 'UTF-8';
            
            // 발신자/수신자 설정
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to);
            $mail->isHTML(true);
            
            // 메일 내용
            $mail->Subject = $subject;
            $mail->Body = $message;
            
            // 발송
            $result = $mail->send();
            
            if ($result) {
                error_log("SMTP 이메일 발송 성공: {$to} - {$subject}");
            }
            
            return $result;
            
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            error_log("SMTP 이메일 발송 오류: " . $e->getMessage());
            // PHPMailer 실패 시 기본 mail() 함수로 폴백
            return sendEmailViaMailFunction($to, $subject, $message, $fromEmail);
        } catch (\Exception $e) {
            error_log("SMTP 이메일 발송 일반 오류: " . $e->getMessage());
            // PHPMailer 실패 시 기본 mail() 함수로 폴백
            return sendEmailViaMailFunction($to, $subject, $message, $fromEmail);
        }
    } else {
        // PHPMailer가 없으면 기본 mail() 함수 사용
        error_log("PHPMailer가 설치되지 않음. 기본 mail() 함수 사용");
        return sendEmailViaMailFunction($to, $subject, $message, $fromEmail);
    }
}

/**
 * 기본 mail() 함수를 사용한 이메일 발송
 */
function sendEmailViaMailFunction($to, $subject, $message, $from) {
    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=UTF-8';
    $headers[] = 'From: ' . $from;
    $headers[] = 'Reply-To: ' . $from;
    $headers[] = 'X-Mailer: PHP/' . phpversion();
    
    $headersString = implode("\r\n", $headers);
    $result = @mail($to, $subject, $message, $headersString);
    
    if (!$result) {
        error_log("mail() 함수 이메일 발송 실패: {$to} - {$subject}");
    }
    
    return $result;
}

/**
 * 이메일 인증번호 발송
 * 
 * @param string $to 수신자 이메일
 * @param string $verificationCode 인증번호
 * @param string $type 인증 타입 ('email_change' 또는 'password_change')
 * @param string $userName 사용자 이름 (선택)
 * @return bool 발송 성공 여부
 */
function sendVerificationEmail($to, $verificationCode, $type = 'email_change', $userName = '') {
    $typeNames = [
        'email_change' => '이메일 주소 변경',
        'password_change' => '비밀번호 변경'
    ];
    
    $typeName = $typeNames[$type] ?? '인증';
    
    // 사이트 정보 가져오기
    $siteName = defined('MAIL_SITE_NAME') ? MAIL_SITE_NAME : 'MVNO';
    $siteUrl = defined('MAIL_SITE_URL') ? MAIL_SITE_URL : 'https://mvno.com';
    $supportEmail = defined('MAIL_SUPPORT_EMAIL') ? MAIL_SUPPORT_EMAIL : 'support@mvno.com';
    
    $subject = "[{$siteName}] {$typeName} 인증번호";
    
    $greeting = !empty($userName) ? "{$userName}님" : "고객님";
    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <style>
            body { font-family: 'Malgun Gothic', 'Apple SD Gothic Neo', Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background: #f3f4f6; }
            .email-wrapper { max-width: 600px; margin: 0 auto; background: white; }
            .header { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: white; padding: 30px 20px; text-align: center; }
            .header h1 { margin: 0; font-size: 24px; font-weight: 700; }
            .content { padding: 40px 30px; background: white; }
            .greeting { font-size: 16px; color: #1f2937; margin-bottom: 20px; }
            .description { font-size: 15px; color: #4b5563; margin-bottom: 30px; line-height: 1.8; }
            .code-box { background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%); border: 2px dashed #6366f1; border-radius: 12px; padding: 30px 20px; text-align: center; margin: 30px 0; }
            .code { font-size: 36px; font-weight: 700; color: #6366f1; letter-spacing: 8px; font-family: 'Courier New', monospace; }
            .code-label { font-size: 13px; color: #6b7280; margin-top: 10px; }
            .info-box { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 25px 0; border-radius: 4px; }
            .info-box p { margin: 5px 0; font-size: 14px; color: #92400e; }
            .warning-box { background: #fee2e2; border-left: 4px solid #ef4444; padding: 15px; margin: 25px 0; border-radius: 4px; }
            .warning-box p { margin: 5px 0; font-size: 14px; color: #991b1b; }
            .footer { background: #f9fafb; padding: 25px 30px; border-top: 1px solid #e5e7eb; text-align: center; }
            .footer-text { font-size: 12px; color: #6b7280; margin: 5px 0; }
            .footer-link { color: #6366f1; text-decoration: none; }
            .footer-link:hover { text-decoration: underline; }
            .divider { height: 1px; background: #e5e7eb; margin: 25px 0; }
        </style>
    </head>
    <body>
        <div class='email-wrapper'>
            <div class='header'>
                <h1>{$siteName}</h1>
                <p style='margin: 10px 0 0 0; font-size: 14px; opacity: 0.9;'>{$typeName} 인증번호</p>
            </div>
            
            <div class='content'>
                <div class='greeting'>
                    안녕하세요, <strong>{$greeting}</strong>
                </div>
                
                <div class='description'>
                    {$siteName} 서비스에서 <strong>{$typeName}</strong>을 위해 아래 인증번호를 발송해드립니다.<br>
                    인증번호를 입력하여 인증을 완료해주세요.
                </div>
                
                <div class='code-box'>
                    <div class='code'>{$verificationCode}</div>
                    <div class='code-label'>위 인증번호를 입력해주세요</div>
                </div>
                
                <div class='info-box'>
                    <p><strong>📌 인증번호 유효시간</strong></p>
                    <p>인증번호는 발송 시점부터 <strong>30분간</strong> 유효합니다.</p>
                    <p>만료된 경우 '인증번호 다시 받기'를 클릭하여 새 인증번호를 발송받으세요.</p>
                </div>
                
                <div class='warning-box'>
                    <p><strong>⚠️ 보안 안내</strong></p>
                    <p>본인이 요청하지 않은 경우 이 메일을 무시하세요.</p>
                    <p>인증번호를 타인에게 알려주지 마세요.</p>
                </div>
                
                <div class='divider'></div>
                
                <div style='font-size: 13px; color: #6b7280; line-height: 1.8;'>
                    <p><strong>문의사항이 있으신가요?</strong></p>
                    <p>고객 지원: <a href='mailto:{$supportEmail}' style='color: #6366f1; text-decoration: none;'>{$supportEmail}</a></p>
                    <p>사이트: <a href='{$siteUrl}' class='footer-link' target='_blank'>{$siteUrl}</a></p>
                </div>
            </div>
            
            <div class='footer'>
                <p class='footer-text'><strong>이 메일은 발신 전용입니다.</strong></p>
                <p class='footer-text'>이 메일 주소로는 회신이 불가능합니다.</p>
                <p class='footer-text'>문의사항은 <a href='mailto:{$supportEmail}' class='footer-link'>{$supportEmail}</a>로 연락주세요.</p>
                <div class='divider' style='margin: 20px 0;'></div>
                <p class='footer-text'>© {$siteName}. All rights reserved.</p>
                <p class='footer-text' style='font-size: 11px; color: #9ca3af; margin-top: 10px;'>
                    본 메일은 {$siteName} 서비스의 계정 보안을 위해 자동으로 발송되었습니다.
                </p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($to, $subject, $message);
}

/**
 * 이메일 인증 링크 발송
 * 
 * @param string $to 수신자 이메일
 * @param string $verificationToken 인증 토큰
 * @param string $type 인증 타입
 * @param string $userName 사용자 이름 (선택)
 * @return bool 발송 성공 여부
 */
function sendVerificationLinkEmail($to, $verificationToken, $type = 'email_change', $userName = '') {
    $typeNames = [
        'email_change' => '이메일 주소 변경',
        'password_change' => '비밀번호 변경'
    ];
    
    $typeName = $typeNames[$type] ?? '인증';
    
    // 사이트 정보 가져오기
    $siteName = defined('MAIL_SITE_NAME') ? MAIL_SITE_NAME : 'MVNO';
    $siteUrl = defined('MAIL_SITE_URL') ? MAIL_SITE_URL : 'https://mvno.com';
    $supportEmail = defined('MAIL_SUPPORT_EMAIL') ? MAIL_SUPPORT_EMAIL : 'support@mvno.com';
    
    $subject = "[{$siteName}] {$typeName} 인증 링크";
    
    $greeting = !empty($userName) ? "{$userName}님" : "고객님";
    
    // 인증 링크 생성
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $verificationUrl = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/MVNO/api/verify-email-link.php?token={$verificationToken}&type={$type}";
    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <style>
            body { font-family: 'Malgun Gothic', 'Apple SD Gothic Neo', Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background: #f3f4f6; }
            .email-wrapper { max-width: 600px; margin: 0 auto; background: white; }
            .header { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: white; padding: 30px 20px; text-align: center; }
            .header h1 { margin: 0; font-size: 24px; font-weight: 700; }
            .content { padding: 40px 30px; background: white; }
            .greeting { font-size: 16px; color: #1f2937; margin-bottom: 20px; }
            .description { font-size: 15px; color: #4b5563; margin-bottom: 30px; line-height: 1.8; }
            .button-container { text-align: center; margin: 30px 0; }
            .button { display: inline-block; background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: white; padding: 16px 32px; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px; }
            .link-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 15px; margin: 20px 0; word-break: break-all; }
            .link { color: #6366f1; font-size: 13px; }
            .warning-box { background: #fee2e2; border-left: 4px solid #ef4444; padding: 15px; margin: 25px 0; border-radius: 4px; }
            .warning-box p { margin: 5px 0; font-size: 14px; color: #991b1b; }
            .footer { background: #f9fafb; padding: 25px 30px; border-top: 1px solid #e5e7eb; text-align: center; }
            .footer-text { font-size: 12px; color: #6b7280; margin: 5px 0; }
            .footer-link { color: #6366f1; text-decoration: none; }
            .footer-link:hover { text-decoration: underline; }
            .divider { height: 1px; background: #e5e7eb; margin: 25px 0; }
        </style>
    </head>
    <body>
        <div class='email-wrapper'>
            <div class='header'>
                <h1>{$siteName}</h1>
                <p style='margin: 10px 0 0 0; font-size: 14px; opacity: 0.9;'>{$typeName} 인증 링크</p>
            </div>
            
            <div class='content'>
                <div class='greeting'>
                    안녕하세요, <strong>{$greeting}</strong>
                </div>
                
                <div class='description'>
                    {$siteName} 서비스에서 <strong>{$typeName}</strong>을 위해 아래 링크를 클릭해주세요.
                </div>
                
                <div class='button-container'>
                    <a href='{$verificationUrl}' class='button'>인증하기</a>
                </div>
                
                <div class='link-box'>
                    <p style='margin: 0 0 8px 0; font-size: 13px; color: #6b7280;'>링크가 작동하지 않는 경우, 아래 URL을 복사하여 브라우저에 붙여넣으세요:</p>
                    <p class='link'>{$verificationUrl}</p>
                </div>
                
                <div class='warning-box'>
                    <p><strong>⚠️ 보안 안내</strong></p>
                    <p>인증 링크는 발송 시점부터 <strong>30분간</strong> 유효합니다.</p>
                    <p>본인이 요청하지 않은 경우 이 메일을 무시하세요.</p>
                </div>
                
                <div class='divider'></div>
                
                <div style='font-size: 13px; color: #6b7280; line-height: 1.8;'>
                    <p><strong>문의사항이 있으신가요?</strong></p>
                    <p>고객 지원: <a href='mailto:{$supportEmail}' style='color: #6366f1; text-decoration: none;'>{$supportEmail}</a></p>
                    <p>사이트: <a href='{$siteUrl}' class='footer-link' target='_blank'>{$siteUrl}</a></p>
                </div>
            </div>
            
            <div class='footer'>
                <p class='footer-text'><strong>이 메일은 발신 전용입니다.</strong></p>
                <p class='footer-text'>이 메일 주소로는 회신이 불가능합니다.</p>
                <p class='footer-text'>문의사항은 <a href='mailto:{$supportEmail}' class='footer-link'>{$supportEmail}</a>로 연락주세요.</p>
                <div class='divider' style='margin: 20px 0;'></div>
                <p class='footer-text'>© {$siteName}. All rights reserved.</p>
                <p class='footer-text' style='font-size: 11px; color: #9ca3af; margin-top: 10px;'>
                    본 메일은 {$siteName} 서비스의 계정 보안을 위해 자동으로 발송되었습니다.
                </p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($to, $subject, $message);
}








