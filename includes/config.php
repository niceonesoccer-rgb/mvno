<?php
declare(strict_types=1);

/**
 * 기본 환경설정
 * 실제 서버에 배포할 때에는 이 값을 환경변수나 별도 비밀 설정파일로 분리하세요.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'mvno');
define('DB_USER', 'mvno_user');
define('DB_PASS', 'change_me');
define('DB_PORT', 3306);

define('MVNO_CHARSET', 'utf8mb4');
define('MVNO_TIMEZONE', 'Asia/Seoul');

date_default_timezone_set(MVNO_TIMEZONE);

