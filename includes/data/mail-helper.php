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
    // 디버깅: sendEmail 함수 시작 로그
    $host = $_SERVER['HTTP_HOST'] ?? 'unknown';
    error_log("sendEmail 시작 - 수신자: {$to}, 호스트: {$host}");
    
    // 설정 확인
    $mailMethod = defined('MAIL_METHOD') ? MAIL_METHOD : 'mail';
    error_log("sendEmail - 메일 방식 설정: {$mailMethod}");
    
    // 환경 자동 감지
    $isLocalhost = (
        strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false ||
        strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false ||
        strpos($_SERVER['HTTP_HOST'] ?? '', '::1') !== false
    );
    error_log("sendEmail - 환경: " . ($isLocalhost ? '로컬' : '프로덕션'));
    
    // 'auto' 모드: 환경에 따라 자동 선택
    if ($mailMethod === 'auto') {
        // 로컬 환경이고 PHPMailer가 있으면 SMTP 시도, 없으면 mail() 사용
        $phpmailerPath = __DIR__ . '/../../vendor/autoload.php';
        if ($isLocalhost && file_exists($phpmailerPath)) {
            $mailMethod = 'smtp';
            error_log("sendEmail - auto 모드: SMTP 선택 (PHPMailer 발견)");
        } else {
            $mailMethod = 'mail';
            error_log("sendEmail - auto 모드: mail() 함수 선택");
        }
    }
    
    if ($mailMethod === 'smtp' && function_exists('sendEmailViaSMTP')) {
        // SMTP 사용
        $fromEmail = defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : ($from ?: 'noreply@mvno.com');
        $fromName = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'MVNO';
        error_log("sendEmail - SMTP 방식 사용 - 발신자: {$fromEmail} ({$fromName})");
        $result = sendEmailViaSMTP($to, $subject, $message, $fromEmail, $fromName);
        error_log("sendEmail - SMTP 결과: " . ($result ? '성공' : '실패') . " - 수신자: {$to}");
        return $result;
    } else {
        // 기본 mail() 함수 사용 (호스팅에서 대부분 작동)
        if (empty($from)) {
            $from = defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'noreply@mvno.com';
        }
        error_log("sendEmail - mail() 함수 사용 - 발신자: {$from}");
        
        // 헤더 설정
        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=UTF-8';
        $headers[] = 'From: ' . $from;
        $headers[] = 'Reply-To: ' . $from;
        $headers[] = 'X-Mailer: PHP/' . phpversion();
        
        $headersString = implode("\r\n", $headers);
        
        // 이메일 발송
        error_log("sendEmail - mail() 함수 호출 시도 - 수신자: {$to}, 제목: {$subject}");
        $result = @mail($to, $subject, $message, $headersString);
        
        // 로그 기록
        if (!$result) {
            error_log("sendEmail - mail() 함수 발송 실패: {$to} - {$subject}");
        } else {
            error_log("sendEmail - mail() 함수 발송 성공 (반환값 true): {$to} - {$subject}");
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
            $smtpHost = defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com';
            $smtpUsername = defined('SMTP_USERNAME') ? SMTP_USERNAME : '';
            $smtpPort = defined('SMTP_PORT') ? SMTP_PORT : 587;
            $smtpSecure = defined('SMTP_SECURE') ? SMTP_SECURE : 'tls';
            
            error_log("sendEmailViaSMTP - SMTP 설정: Host={$smtpHost}, Port={$smtpPort}, Secure={$smtpSecure}, Username={$smtpUsername}");
            
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUsername;
            $mail->Password = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '';
            $mail->SMTPSecure = $smtpSecure;
            $mail->Port = $smtpPort;
            $mail->CharSet = 'UTF-8';
            $mail->SMTPKeepAlive = false; // 매번 새 연결 (연결 재사용 문제 방지)
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
            
            // 디버깅 모드 활성화 (상세 오류 로그)
            $mail->SMTPDebug = 2; // 0 = off, 1 = client, 2 = client and server
            $mail->Debugoutput = 'error_log'; // 디버그 출력을 error_log로 전송
            
            // 발신자/수신자 설정
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to);
            $mail->isHTML(true);
            
            // 메일 내용
            $mail->Subject = $subject;
            $mail->Body = $message;
            
            error_log("sendEmailViaSMTP - 이메일 발송 시도 - 수신자: {$to}, 제목: {$subject}");
            
            // 발송
            $result = $mail->send();
            
            // ErrorInfo 확인 (send()가 true를 반환해도 ErrorInfo가 있으면 실제로는 실패)
            $errorInfo = $mail->ErrorInfo ?? '';
            if ($result && empty($errorInfo)) {
                error_log("sendEmailViaSMTP - 이메일 발송 성공: {$to} - {$subject}");
                return true;
            } else {
                // 실패한 경우 (반환값이 false이거나 ErrorInfo가 있는 경우)
                if (!empty($errorInfo)) {
                    error_log("sendEmailViaSMTP - 이메일 발송 실패 (ErrorInfo 있음): {$to} - {$subject} - ErrorInfo: {$errorInfo}");
                } else {
                    error_log("sendEmailViaSMTP - 이메일 발송 실패 (반환값 false): {$to} - {$subject}");
                }
                return false;
            }
            
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            error_log("sendEmailViaSMTP - PHPMailer 예외: " . $e->getMessage());
            $errorInfo = (isset($mail) && isset($mail->ErrorInfo)) ? $mail->ErrorInfo : 'N/A';
            error_log("sendEmailViaSMTP - ErrorInfo: " . $errorInfo);
            // PHPMailer 실패 시 기본 mail() 함수로 폴백
            error_log("sendEmailViaSMTP - mail() 함수로 폴백 시도");
            return sendEmailViaMailFunction($to, $subject, $message, $fromEmail);
        } catch (\Exception $e) {
            error_log("sendEmailViaSMTP - 일반 예외: " . $e->getMessage());
            // PHPMailer 실패 시 기본 mail() 함수로 폴백
            error_log("sendEmailViaSMTP - mail() 함수로 폴백 시도");
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
    // 호스트 확인 (실제 서버인지 개발 환경인지)
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $isProduction = (
        strpos($host, 'localhost') === false && 
        strpos($host, '127.0.0.1') === false &&
        strpos($host, '::1') === false &&
        strpos($host, '.') !== false // 도메인이 있는 경우 (예: ganadamobile.co.kr)
    );
    
    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=UTF-8';
    $headers[] = 'From: ' . $from;
    $headers[] = 'Reply-To: ' . $from;
    $headers[] = 'X-Mailer: PHP/' . phpversion();
    
    $headersString = implode("\r\n", $headers);
    
    // PHP 오류 발생 시 캡처
    $lastError = null;
    set_error_handler(function($errno, $errstr) use (&$lastError) {
        $lastError = $errstr;
    }, E_WARNING | E_NOTICE);
    
    $result = @mail($to, $subject, $message, $headersString);
    
    restore_error_handler();
    
    // 실제 서버에서는 더 엄격하게 체크
    if ($isProduction) {
        // 실제 서버에서는 mail() 함수가 false를 반환하거나 오류가 발생하면 실패로 간주
        if (!$result || $lastError !== null) {
            error_log("mail() 함수 이메일 발송 실패 (실제 서버): {$to} - {$subject} - 오류: " . ($lastError ?? 'mail() 반환값 false'));
            return false;
        }
        
        // 실제 서버에서는 mail()가 true를 반환해도 실제 발송 여부는 확실하지 않음
        // 하지만 일단 true를 반환하고, 서버 로그를 확인하도록 함
        error_log("mail() 함수 이메일 발송 시도 (실제 서버): {$to} - {$subject} - mail() 반환값: " . ($result ? 'true' : 'false'));
    } else {
        // 개발 환경
        if (!$result) {
            error_log("mail() 함수 이메일 발송 실패 (개발 환경): {$to} - {$subject}");
        } else {
            error_log("mail() 함수 이메일 발송 시도 (개발 환경): {$to} - {$subject}");
        }
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
    // 디버깅: 함수 시작 로그
    error_log("sendVerificationEmail 시작 - 수신자: {$to}, 타입: {$type}, 인증번호: {$verificationCode}");
    
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
    
    $preheaderText = $siteName . ' ' . $typeName . '을 위한 인증번호를 확인해주세요. (30분 유효)';
    
    $message = "
    <!-- Preheader -->
    <div style='display:none; font-size:1px; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden;'>
      {$preheaderText}
    </div>

    <table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0' style='background:#f5f7fb;'>
      <tr>
        <td align='center' style='padding:32px 16px;'>
          <table role='presentation' width='600' cellpadding='0' cellspacing='0' border='0' style='max-width:600px; width:100%;'>

            <!-- Header -->
            <tr>
              <td style='padding-bottom:14px;'>
                <div style='
                  font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,\"Noto Sans KR\",Arial,sans-serif;
                  font-size:18px;
                  font-weight:800;
                  color:#111827;
                '>
                  {$siteName}
                </div>
              </td>
            </tr>

            <!-- Card -->
            <tr>
              <td style='
                background:#ffffff;
                border:1px solid #e5e7eb;
                border-radius:16px;
                padding:28px 24px;
              '>
                <div style='
                  font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,\"Noto Sans KR\",Arial,sans-serif;
                  color:#111827;
                '>
                  <div style='font-size:18px; font-weight:800; margin-bottom:8px;'>
                    {$typeName} 인증번호
                  </div>

                  <div style='font-size:14px; line-height:1.7; color:#374151; margin-bottom:18px;'>
                    안녕하세요, <strong>{$greeting}</strong><br />
                    {$siteName} 서비스에서 <strong>{$typeName}</strong>을 위해 아래 인증번호를 발송해드립니다.<br />
                    인증번호를 입력하여 인증을 완료해주세요.
                  </div>

                  <!-- 인증번호 -->
                  <table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0' style='margin:18px 0;'>
                    <tr>
                      <td align='center' style='
                        background:#f3f4f6;
                        border:1px solid #e5e7eb;
                        border-radius:14px;
                        padding:26px 20px;
                      '>
                        <div style='
                          font-size:72px;
                          font-weight:900;
                          letter-spacing:16px;
                          color:#111827;
                          line-height:1.2;
                        '>
                          {$verificationCode}
                        </div>
                        <div style='font-size:12px; color:#6b7280; margin-top:10px;'>
                          위 인증번호를 입력해주세요.
                        </div>
                      </td>
                    </tr>
                  </table>

                  <!-- Info -->
                  <div style='border-top:1px solid #e5e7eb; padding-top:16px;'>
                    <div style='font-size:13px; line-height:1.7; color:#374151; margin-bottom:12px;'>
                      <strong style='color:#111827;'>인증번호 유효시간</strong><br />
                      인증번호는 발송 시점부터 <strong>30분</strong>간 유효합니다.<br />
                      만료된 경우 '인증번호 다시 받기'를 클릭하여 새 인증번호를 발송받으세요.
                    </div>

                    <div style='font-size:13px; line-height:1.7; color:#374151;'>
                      <strong style='color:#111827;'>보안 안내</strong><br />
                      본인이 요청하지 않은 경우 이 메일을 무시하세요.<br />
                      인증번호를 타인에게 알려주지 마세요.<br />
                      이 메일은 발신 전용입니다.
                    </div>
                  </div>
                </div>
              </td>
            </tr>

            <!-- Footer -->
            <tr>
              <td style='padding-top:14px;'>
                <div style='
                  font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,\"Noto Sans KR\",Arial,sans-serif;
                  font-size:12px;
                  line-height:1.6;
                  color:#6b7280;
                '>
                  이 메일 주소로는 회신이 불가능합니다.<br />
                  © {$siteName}. All rights reserved.<br />
                  본 메일은 {$siteName} 서비스의 계정 보안을 위해 자동으로 발송되었습니다.
                </div>
              </td>
            </tr>

          </table>
        </td>
      </tr>
    </table>
    ";
    
    // 디버깅: 이메일 발송 전 로그
    error_log("sendVerificationEmail - sendEmail 호출 전 - 수신자: {$to}, 제목: {$subject}");
    $result = sendEmail($to, $subject, $message);
    error_log("sendVerificationEmail - sendEmail 반환값: " . ($result ? 'true (성공)' : 'false (실패)') . " - 수신자: {$to}");
    return $result;
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
    
    $preheaderText = $siteName . ' ' . $typeName . '을 위한 인증 링크를 확인해주세요. (30분 유효)';
    
    $message = "
    <!-- Preheader -->
    <div style=\"display:none; font-size:1px; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden;\">
      {$preheaderText}
    </div>

    <table role=\"presentation\" width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"background:#f5f7fb;\">
      <tr>
        <td align=\"center\" style=\"padding:32px 16px;\">
          <table role=\"presentation\" width=\"600\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"max-width:600px; width:100%;\">

            <!-- Header -->
            <tr>
              <td style=\"padding-bottom:14px;\">
                <div style=\"
                  font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Noto Sans KR',Arial,sans-serif;
                  font-size:18px;
                  font-weight:800;
                  color:#111827;
                \">
                  {$siteName}
                </div>
              </td>
            </tr>

            <!-- Card -->
            <tr>
              <td style=\"
                background:#ffffff;
                border:1px solid #e5e7eb;
                border-radius:16px;
                padding:28px 24px;
              \">
                <div style=\"
                  font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Noto Sans KR',Arial,sans-serif;
                  color:#111827;
                \">
                  <div style=\"font-size:18px; font-weight:800; margin-bottom:8px;\">
                    {$typeName} 인증 링크
                  </div>

                  <div style=\"font-size:14px; line-height:1.7; color:#374151; margin-bottom:18px;\">
                    안녕하세요, <strong>{$greeting}</strong><br />
                    {$siteName} 서비스에서 <strong>{$typeName}</strong>을 위해 아래 링크를 클릭해주세요.
                  </div>

                  <!-- Button -->
                  <table role=\"presentation\" width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"margin:18px 0;\">
                    <tr>
                      <td align=\"center\">
                        <a href=\"{$verificationUrl}\" style=\"
                          display:inline-block;
                          background:#111827;
                          color:#ffffff;
                          padding:14px 28px;
                          text-decoration:none;
                          border-radius:12px;
                          font-weight:600;
                          font-size:15px;
                          font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Noto Sans KR',Arial,sans-serif;
                        \">인증하기</a>
                      </td>
                    </tr>
                  </table>

                  <!-- Link Info -->
                  <table role=\"presentation\" width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"margin:18px 0;\">
                    <tr>
                      <td style=\"
                        background:#f9fafb;
                        border:1px solid #e5e7eb;
                        border-radius:14px;
                        padding:14px 16px;
                      \">
                        <div style=\"font-size:12px; color:#6b7280; margin-bottom:8px;\">
                          링크가 작동하지 않는 경우, 아래 URL을 복사하여 브라우저에 붙여넣으세요:
                        </div>
                        <div style=\"font-size:12px; color:#111827; word-break:break-all; font-family:monospace;\">
                          {$verificationUrl}
                        </div>
                      </td>
                    </tr>
                  </table>

                  <!-- Security -->
                  <div style=\"border-top:1px solid #e5e7eb; padding-top:16px;\">
                    <div style=\"font-size:13px; line-height:1.7; color:#374151;\">
                      <strong style=\"color:#111827;\">보안 안내</strong><br />
                      인증 링크는 발송 시점부터 <strong>30분간</strong> 유효합니다.<br />
                      본인이 요청하지 않은 경우 이 메일을 무시하세요.<br />
                      이 메일은 발신 전용입니다.
                    </div>
                  </div>
                </div>
              </td>
            </tr>

            <!-- Footer -->
            <tr>
              <td style=\"padding-top:14px;\">
                <div style=\"
                  font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Noto Sans KR',Arial,sans-serif;
                  font-size:12px;
                  line-height:1.6;
                  color:#6b7280;
                \">
                  이 메일 주소로는 회신이 불가능합니다.<br />
                  © {$siteName}. All rights reserved.
                </div>
              </td>
            </tr>

          </table>
        </td>
      </tr>
    </table>
    ";
    
    return sendEmail($to, $subject, $message);
}

/**
 * 이메일 변경 완료 알림 메일 발송
 * 
 * @param string $newEmail 새 이메일 주소
 * @param string $oldEmail 기존 이메일 주소
 * @param string $userName 사용자 이름 (선택)
 * @return bool 발송 성공 여부
 */
function sendEmailChangeNotification($newEmail, $oldEmail, $userName = '') {
    // 사이트 정보 가져오기
    $siteName = defined('MAIL_SITE_NAME') ? MAIL_SITE_NAME : 'MVNO';
    $siteUrl = defined('MAIL_SITE_URL') ? MAIL_SITE_URL : 'https://mvno.com';
    $supportEmail = defined('MAIL_SUPPORT_EMAIL') ? MAIL_SUPPORT_EMAIL : 'support@mvno.com';
    
    $subject = "[{$siteName}] 이메일 주소가 변경되었습니다";
    
    $greeting = !empty($userName) ? "{$userName}님" : "고객님";
    
    $message = "
    <!-- Preheader -->
    <div style=\"display:none; font-size:1px; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden;\">
      {$siteName} 이메일 주소 변경이 완료되었습니다.
    </div>

    <table role=\"presentation\" width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"background:#f5f7fb;\">
      <tr>
        <td align=\"center\" style=\"padding:32px 16px;\">
          <table role=\"presentation\" width=\"600\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"max-width:600px; width:100%;\">

            <!-- Header -->
            <tr>
              <td style=\"padding-bottom:14px;\">
                <div style=\"
                  font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Noto Sans KR',Arial,sans-serif;
                  font-size:18px;
                  font-weight:800;
                  color:#111827;
                \">
                  {$siteName}
                </div>
              </td>
            </tr>

            <!-- Card -->
            <tr>
              <td style=\"
                background:#ffffff;
                border:1px solid #e5e7eb;
                border-radius:16px;
                padding:28px 24px;
              \">
                <div style=\"
                  font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Noto Sans KR',Arial,sans-serif;
                  color:#111827;
                \">
                  <div style=\"font-size:18px; font-weight:800; margin-bottom:8px;\">
                    이메일 주소 변경 완료
                  </div>

                  <div style=\"font-size:14px; line-height:1.7; color:#374151; margin-bottom:18px;\">
                    안녕하세요, <strong>{$userName}</strong>님<br />
                    {$siteName} 서비스에서 이메일 주소가 변경되었음을 알려드립니다.
                  </div>

                  <!-- Email Change Info -->
                  <table role=\"presentation\" width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"margin:18px 0;\">
                    <tr>
                      <td style=\"
                        background:#f9fafb;
                        border:1px solid #e5e7eb;
                        border-radius:14px;
                        padding:18px 16px;
                      \">
                        <div style=\"font-size:13px; color:#6b7280; margin-bottom:6px;\">
                          기존 이메일
                        </div>
                        <div style=\"font-size:14px; font-weight:700; color:#111827; margin-bottom:14px;\">
                          {$oldEmail}
                        </div>

                        <div style=\"font-size:13px; color:#6b7280; margin-bottom:6px;\">
                          새 이메일
                        </div>
                        <div style=\"font-size:14px; font-weight:800; color:#111827;\">
                          {$newEmail}
                        </div>
                      </td>
                    </tr>
                  </table>

                  <!-- Notice -->
                  <div style=\"
                    background:#f3f4f6;
                    border:1px solid #e5e7eb;
                    border-radius:12px;
                    padding:14px 16px;
                    margin-bottom:18px;
                  \">
                    <div style=\"font-size:13px; line-height:1.7; color:#374151;\">
                      이메일 주소가 성공적으로 변경되었습니다.<br />
                      이제 새 이메일 주소로 로그인하시면 됩니다.
                    </div>
                  </div>

                  <!-- Security -->
                  <div style=\"border-top:1px solid #e5e7eb; padding-top:16px;\">
                    <div style=\"font-size:13px; line-height:1.7; color:#374151;\">
                      <strong style=\"color:#111827;\">보안 안내</strong><br />
                      본인이 요청하지 않은 경우 즉시 고객 지원팀에 연락해주세요.<br />
                      비밀번호가 유출되었을 수 있으므로 비밀번호 변경을 권장합니다.<br />
                      이 메일은 발신 전용입니다.
                    </div>
                  </div>
                </div>
              </td>
            </tr>

            <!-- Footer -->
            <tr>
              <td style=\"padding-top:14px;\">
                <div style=\"
                  font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Noto Sans KR',Arial,sans-serif;
                  font-size:12px;
                  line-height:1.6;
                  color:#6b7280;
                \">
                  이 메일 주소로는 회신이 불가능합니다.<br />
                  © {$siteName}. All rights reserved.
                </div>
              </td>
            </tr>

          </table>
        </td>
      </tr>
    </table>
    ";
    
    // 새 이메일 주소로 알림 메일 발송
    return sendEmail($newEmail, $subject, $message);
}








