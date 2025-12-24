<?php
/**
 * PHPMailer 설치 확인 테스트
 */

require_once __DIR__ . '/vendor/autoload.php';

echo "<h2>PHPMailer 설치 확인</h2>";

// PHPMailer 클래스 확인
if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    echo "<p style='color: green;'>✅ PHPMailer 클래스 로드 성공!</p>";
    
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        echo "<p style='color: green;'>✅ PHPMailer 인스턴스 생성 성공!</p>";
        echo "<p>PHPMailer 버전: " . PHPMailer\PHPMailer\PHPMailer::VERSION . "</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ PHPMailer 인스턴스 생성 실패: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ PHPMailer 클래스를 찾을 수 없습니다.</p>";
}

// SMTP 클래스 확인
if (class_exists('PHPMailer\PHPMailer\SMTP')) {
    echo "<p style='color: green;'>✅ SMTP 클래스 로드 성공!</p>";
} else {
    echo "<p style='color: red;'>❌ SMTP 클래스를 찾을 수 없습니다.</p>";
}

// Exception 클래스 확인
if (class_exists('PHPMailer\PHPMailer\Exception')) {
    echo "<p style='color: green;'>✅ Exception 클래스 로드 성공!</p>";
} else {
    echo "<p style='color: red;'>❌ Exception 클래스를 찾을 수 없습니다.</p>";
}

echo "<hr>";
echo "<p><a href='/MVNO/mypage/account-management.php'>계정 설정으로 돌아가기</a></p>";

