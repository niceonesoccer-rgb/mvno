<?php
/**
 * 하이웍스 이메일 설정 스크립트
 * 
 * 사용 방법:
 * 1. 브라우저에서 http://localhost/mvno/setup-hiworks-email.php 접속
 * 2. 설정이 자동으로 저장됨
 * 3. 보안을 위해 실행 후 이 파일을 삭제하세요!
 */

require_once __DIR__ . '/includes/data/app-settings.php';
require_once __DIR__ . '/includes/data/auth-functions.php';

// 관리자 확인 (보안)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isAdmin()) {
    die('관리자만 접근할 수 있습니다. 관리자 페이지에서 설정하세요: /MVNO/admin/settings/email-settings.php');
}

// 하이웍스 이메일 설정
$emailSettings = [
    'mail_method' => 'smtp',
    'smtp_host' => 'smtps.hiworks.com',
    'smtp_port' => 465,
    'smtp_secure' => 'ssl',
    'smtp_username' => 'danora',
    'smtp_password' => 'hgaf#sdafa3',
    'smtp_from_email' => 'danora@ganadamobile.co.kr',
    'smtp_from_name' => '유심킹',
    'mail_reply_to' => 'danora@ganadamobile.co.kr',
    'mail_site_name' => '유심킹',
    'mail_site_url' => 'https://ganadamobile.co.kr',
    'mail_support_email' => 'danora@ganadamobile.co.kr'
];

// 설정 저장
$result = saveAppSettings('email', $emailSettings, 'admin');

if ($result) {
    echo '<h1>✅ 하이웍스 이메일 설정 완료</h1>';
    echo '<p>이메일 설정이 성공적으로 저장되었습니다.</p>';
    echo '<hr>';
    echo '<h2>설정 내용:</h2>';
    echo '<ul>';
    echo '<li><strong>SMTP 서버:</strong> smtps.hiworks.com</li>';
    echo '<li><strong>포트:</strong> 465</li>';
    echo '<li><strong>보안:</strong> SSL</li>';
    echo '<li><strong>사용자명:</strong> danora</li>';
    echo '<li><strong>발신자 이메일:</strong> danora@ganadamobile.co.kr</li>';
    echo '</ul>';
    echo '<hr>';
    echo '<p><strong>다음 단계:</strong></p>';
    echo '<ol>';
    echo '<li>비밀번호 변경 페이지에서 이메일 인증번호 발송 테스트</li>';
    echo '<li>이 파일을 삭제하세요 (보안상 중요)</li>';
    echo '</ol>';
    echo '<hr>';
    echo '<p><a href="/MVNO/admin/settings/email-settings.php">이메일 설정 페이지로 이동</a></p>';
} else {
    echo '<h1>❌ 설정 저장 실패</h1>';
    echo '<p>데이터베이스 연결 오류 또는 저장 실패가 발생했습니다.</p>';
    echo '<p>관리자 페이지에서 직접 설정하세요: <a href="/MVNO/admin/settings/email-settings.php">이메일 설정</a></p>';
}
