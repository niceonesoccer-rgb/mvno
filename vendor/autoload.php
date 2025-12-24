<?php
/**
 * PHPMailer Autoloader
 * Composer가 없을 때 사용하는 간단한 autoloader
 */

// PHPMailer 네임스페이스 매핑
spl_autoload_register(function ($class) {
    // PHPMailer 네임스페이스 처리
    if (strpos($class, 'PHPMailer\\PHPMailer\\') === 0) {
        $class = str_replace('PHPMailer\\PHPMailer\\', '', $class);
        $file = __DIR__ . '/PHPMailer-master/src/' . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// PHPMailer 핵심 클래스들 미리 로드
require_once __DIR__ . '/PHPMailer-master/src/Exception.php';
require_once __DIR__ . '/PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer-master/src/SMTP.php';

