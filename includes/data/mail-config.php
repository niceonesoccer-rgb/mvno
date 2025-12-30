<?php
/**
 * 이메일 발송 설정 파일
 * 
 * 데이터베이스에서 설정을 읽어옵니다.
 * 관리자 페이지에서 설정을 변경할 수 있습니다: /MVNO/admin/settings/email-settings.php
 * 
 * 사용 방법:
 * 1. XAMPP (로컬 PC): SMTP 설정 필요 또는 PHPMailer 사용
 * 2. 웹 호스팅: 호스팅 업체의 SMTP 정보 입력
 */

// app-settings.php가 있으면 데이터베이스에서 설정 읽기
if (file_exists(__DIR__ . '/app-settings.php')) {
    require_once __DIR__ . '/app-settings.php';
    
    // 기본 설정값
    $defaultSettings = [
        'mail_method' => 'auto',
        'smtp_host' => 'smtp.gmail.com',
        'smtp_port' => 587,
        'smtp_secure' => 'tls',
        'smtp_username' => '',
        'smtp_password' => '',
        'smtp_from_email' => 'noreply@mvno.com',
        'smtp_from_name' => 'MVNO 서비스',
        'mail_reply_to' => 'support@mvno.com',
        'mail_site_name' => 'MVNO',
        'mail_site_url' => 'https://mvno.com',
        'mail_support_email' => 'support@mvno.com'
    ];
    
    // 데이터베이스에서 설정 읽기
    $emailSettings = getAppSettings('email', $defaultSettings);
    
    // 상수 정의
    define('MAIL_METHOD', $emailSettings['mail_method'] ?? 'auto');
    define('SMTP_HOST', $emailSettings['smtp_host'] ?? 'smtp.gmail.com');
    define('SMTP_PORT', $emailSettings['smtp_port'] ?? 587);
    define('SMTP_SECURE', $emailSettings['smtp_secure'] ?? 'tls');
    define('SMTP_USERNAME', $emailSettings['smtp_username'] ?? '');
    define('SMTP_PASSWORD', $emailSettings['smtp_password'] ?? '');
    define('SMTP_FROM_EMAIL', $emailSettings['smtp_from_email'] ?? 'noreply@mvno.com');
    define('SMTP_FROM_NAME', $emailSettings['smtp_from_name'] ?? 'MVNO 서비스');
    define('MAIL_REPLY_TO', $emailSettings['mail_reply_to'] ?? 'support@mvno.com');
    define('MAIL_SITE_NAME', $emailSettings['mail_site_name'] ?? 'MVNO');
    define('MAIL_SITE_URL', $emailSettings['mail_site_url'] ?? 'https://mvno.com');
    define('MAIL_SUPPORT_EMAIL', $emailSettings['mail_support_email'] ?? 'support@mvno.com');
} else {
    // app-settings.php가 없으면 기본값 사용 (하위 호환성)
    // 환경 자동 감지
    $isLocalhost = (
        strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false ||
        strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false ||
        strpos($_SERVER['HTTP_HOST'] ?? '', '::1') !== false
    );
    
    define('MAIL_METHOD', 'auto');
    define('SMTP_HOST', 'smtp.gmail.com');
    define('SMTP_PORT', 587);
    define('SMTP_SECURE', 'tls');
    define('SMTP_USERNAME', 'your-email@gmail.com');
    define('SMTP_PASSWORD', 'your-app-password');
    define('SMTP_FROM_EMAIL', 'noreply@mvno.com');
    define('SMTP_FROM_NAME', 'MVNO 서비스');
    define('MAIL_REPLY_TO', 'support@mvno.com');
    define('MAIL_SITE_NAME', 'MVNO');
    define('MAIL_SITE_URL', 'https://mvno.com');
    define('MAIL_SUPPORT_EMAIL', 'support@mvno.com');
}

// 환경 자동 감지 (mail-helper.php에서 사용)
$isLocalhost = (
    strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false ||
    strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false ||
    strpos($_SERVER['HTTP_HOST'] ?? '', '::1') !== false
);

// Gmail 사용 시 참고:
// 1. Google 계정 설정 → 보안 → 2단계 인증 활성화
// 2. 앱 비밀번호 생성: https://myaccount.google.com/apppasswords
// 3. 생성된 16자리 앱 비밀번호를 SMTP_PASSWORD에 입력

// 네이버 메일 사용 시:
// SMTP_HOST: 'smtp.naver.com'
// SMTP_PORT: 587
// SMTP_SECURE: 'tls'
// SMTP_USERNAME: 네이버 이메일 주소
// SMTP_PASSWORD: 네이버 비밀번호 (또는 앱 비밀번호)

// 카페24, 가비아 등 호스팅 업체 사용 시:
// 호스팅 업체에서 제공하는 SMTP 정보를 입력하세요.
// 일반적으로 호스팅 업체의 메일 서버 주소와 포트를 사용합니다.








